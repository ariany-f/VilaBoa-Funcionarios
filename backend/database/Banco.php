<?php

namespace App\Database;

use InvalidArgumentException;
use PDO;

/**
 * Acesso às tabelas com a mesma interface para vários bancos (PostgreSQL, MySQL, SQL Server, SQLite)
 * e para a simulação em JSON. Cada tabela em JSON é um arquivo backend/data/db/<tabela>.json.
 *
 * O portal pode ter várias conexões (config/database.php):
 *   Banco::conexao()           -> conexão padrão
 *   Banco::conexao('legado')   -> conexão nomeada
 * Para consultas fora do CRUD simples: consultar() / executar() / pdo().
 */
class Banco
{
    /** @var array<string, Banco> */
    private static array $instancias = [];
    private ?PDO $pdo = null;
    private string $driver;
    private string $jsonDir;

    private function __construct(private string $nome, array $config, string $jsonDir)
    {
        $this->driver = $config['driver'];
        $this->jsonDir = $jsonDir;

        if ($this->driver !== 'json') {
            $this->pdo = new PDO(
                self::dsn($config),
                $this->driver === 'sqlite' ? null : $config['username'],
                $this->driver === 'sqlite' ? null : $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 10]
            );
        }
    }

    /** Conexão pelo nome (sem nome: a padrão de config/database.php). */
    public static function conexao(?string $nome = null): Banco
    {
        $config = require dirname(__DIR__) . '/config/database.php';
        $nome ??= $config['padrao'];
        if (!isset($config['conexoes'][$nome])) {
            throw new InvalidArgumentException("Conexão de banco não configurada: $nome");
        }
        return self::$instancias[$nome] ??= new Banco($nome, $config['conexoes'][$nome], $config['json_dir']);
    }

    /** Monta uma conexão avulsa (ex.: scripts de instalação, com credenciais de administrador). */
    public static function avulsa(array $config): Banco
    {
        return new Banco('avulsa', $config, dirname(__DIR__) . '/data/db');
    }

    public static function dsn(array $c): string
    {
        return match ($c['driver']) {
            'mysql' => "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset={$c['charset']}",
            'pgsql' => "pgsql:host={$c['host']};port={$c['port']};dbname={$c['database']}",
            'sqlsrv' => "sqlsrv:Server={$c['host']},{$c['port']};Database={$c['database']};TrustServerCertificate=1",
            'sqlite' => 'sqlite:' . $c['database'],
            default => throw new InvalidArgumentException('Driver de banco não suportado: ' . $c['driver']),
        };
    }

    public function nome(): string
    {
        return $this->nome;
    }

    public function driver(): string
    {
        return $this->driver;
    }

    /** PDO da conexão (null no modo JSON), para quem precisa de SQL próprio. */
    public function pdo(): ?PDO
    {
        return $this->pdo;
    }

    /** SELECT livre com parâmetros (só em bancos SQL). */
    public function consultar(string $sql, array $params = []): array
    {
        $stmt = $this->pdoObrigatorio()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** INSERT/UPDATE/DELETE/DDL livres; devolve as linhas afetadas. */
    public function executar(string $sql, array $params = []): int
    {
        $stmt = $this->pdoObrigatorio()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    private function pdoObrigatorio(): PDO
    {
        return $this->pdo ?? throw new InvalidArgumentException("A conexão \"{$this->nome}\" usa JSON e não aceita SQL.");
    }

    /** Nome de tabela/coluna entre aspas do dialeto. */
    private function q(string $identificador): string
    {
        $limpo = preg_replace('/[^A-Za-z0-9_]/', '', $identificador);
        return match ($this->driver) {
            'mysql' => "`$limpo`",
            'sqlsrv' => "[$limpo]",
            default => "\"$limpo\"",
        };
    }

    /** Linhas da tabela que atendem a todas as condições (igualdade simples ou lista para IN). */
    public function buscar(string $tabela, array $condicoes = []): array
    {
        if ($this->pdo) {
            [$where, $params] = $this->montarWhere($condicoes);
            $stmt = $this->pdo->prepare('SELECT * FROM ' . $this->q($tabela) . $where);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        return array_values(array_filter($this->lerJson($tabela), function ($linha) use ($condicoes) {
            foreach ($condicoes as $campo => $valor) {
                $atual = $linha[$campo] ?? null;
                if (is_array($valor) ? !in_array($atual, $valor) : $atual != $valor) {
                    return false;
                }
            }
            return true;
        }));
    }

    public function primeiro(string $tabela, array $condicoes = []): ?array
    {
        return $this->buscar($tabela, $condicoes)[0] ?? null;
    }

    /** Insere e devolve a linha com o id gerado. */
    public function inserir(string $tabela, array $dados): array
    {
        $agora = date('Y-m-d H:i:s');
        if ($this->pdo) {
            $colunas = array_keys($dados);
            $sql = sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $this->q($tabela),
                implode(', ', array_map(fn($c) => $this->q($c), $colunas)),
                implode(', ', array_fill(0, count($colunas), '?'))
            );
            if ($this->driver === 'pgsql') {
                $stmt = $this->pdo->prepare($sql . ' RETURNING id');
                $stmt->execute(array_values($dados));
                return ['id' => (int)$stmt->fetchColumn()] + $dados;
            }
            $this->pdo->prepare($sql)->execute(array_values($dados));
            return ['id' => (int)$this->pdo->lastInsertId()] + $dados;
        }

        return $this->alterarJson($tabela, function (array $linhas) use ($dados, $agora) {
            $id = $linhas ? max(array_column($linhas, 'id')) + 1 : 1;
            $nova = ['id' => $id] + $dados + ['created' => $agora, 'modified' => $agora];
            $linhas[] = $nova;
            return [$linhas, $nova];
        });
    }

    public function atualizar(string $tabela, int $id, array $dados): void
    {
        if ($this->pdo) {
            $sets = implode(', ', array_map(fn($c) => $this->q($c) . ' = ?', array_keys($dados)));
            $this->pdo->prepare('UPDATE ' . $this->q($tabela) . " SET $sets WHERE id = ?")->execute([...array_values($dados), $id]);
            return;
        }

        $this->alterarJson($tabela, function (array $linhas) use ($id, $dados) {
            foreach ($linhas as &$linha) {
                if ((int)$linha['id'] === $id) {
                    $linha = array_merge($linha, $dados, ['modified' => date('Y-m-d H:i:s')]);
                }
            }
            return [$linhas, null];
        });
    }

    public function excluir(string $tabela, array $condicoes): void
    {
        if ($this->pdo) {
            [$where, $params] = $this->montarWhere($condicoes);
            $this->pdo->prepare('DELETE FROM ' . $this->q($tabela) . $where)->execute($params);
            return;
        }

        $remover = $this->buscar($tabela, $condicoes);
        $this->alterarJson($tabela, fn(array $linhas) => [array_values(array_filter($linhas, fn($l) => !in_array($l, $remover))), null]);
    }

    private function montarWhere(array $condicoes): array
    {
        $partes = [];
        $params = [];
        foreach ($condicoes as $campo => $valor) {
            if (is_array($valor)) {
                if (!$valor) {
                    $partes[] = '1 = 0';
                    continue;
                }
                $partes[] = $this->q($campo) . ' IN (' . implode(', ', array_fill(0, count($valor), '?')) . ')';
                array_push($params, ...array_values($valor));
            } else {
                $partes[] = $this->q($campo) . ' = ?';
                $params[] = $valor;
            }
        }
        return [$partes ? ' WHERE ' . implode(' AND ', $partes) : '', $params];
    }

    private function arquivoJson(string $tabela): string
    {
        return $this->jsonDir . '/' . preg_replace('/[^a-z_]/', '', $tabela) . '.json';
    }

    private function lerJson(string $tabela): array
    {
        $arquivo = $this->arquivoJson($tabela);
        return is_file($arquivo) ? (json_decode(file_get_contents($arquivo), true) ?: []) : [];
    }

    /** Lê, altera e grava a tabela com trava de arquivo, evitando perder gravações simultâneas. */
    private function alterarJson(string $tabela, callable $alteracao): mixed
    {
        if (!is_dir($this->jsonDir)) {
            mkdir($this->jsonDir, 0775, true);
        }
        $fp = fopen($this->arquivoJson($tabela), 'c+');
        flock($fp, LOCK_EX);
        $linhas = json_decode(stream_get_contents($fp), true) ?: [];
        [$linhas, $retorno] = $alteracao($linhas);
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($linhas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
        fclose($fp);
        return $retorno;
    }
}
