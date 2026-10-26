<?php

namespace App\Database;

use PDO;

/**
 * Acesso às tabelas com a mesma interface para o MySQL e para a simulação em JSON.
 * Cada tabela em JSON é um arquivo backend/data/db/<tabela>.json com a lista de linhas.
 */
class Banco
{
    private static ?Banco $instancia = null;
    private ?PDO $pdo = null;
    private string $driver;
    private string $jsonDir;

    private function __construct(array $config)
    {
        $this->driver = $config['driver'];
        $this->jsonDir = $config['json_dir'];

        if ($this->driver === 'mysql') {
            $m = $config['mysql'];
            $this->pdo = new PDO(
                "mysql:host={$m['host']};port={$m['port']};dbname={$m['database']};charset={$m['charset']}",
                $m['username'],
                $m['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        }
    }

    public static function conexao(): Banco
    {
        return self::$instancia ??= new Banco(require dirname(__DIR__) . '/config/database.php');
    }

    public function driver(): string
    {
        return $this->driver;
    }

    /** Linhas da tabela que atendem a todas as condições (igualdade simples ou lista para IN). */
    public function buscar(string $tabela, array $condicoes = []): array
    {
        if ($this->pdo) {
            [$where, $params] = $this->montarWhere($condicoes);
            $stmt = $this->pdo->prepare("SELECT * FROM `$tabela`" . $where);
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
                'INSERT INTO `%s` (`%s`) VALUES (%s)',
                $tabela,
                implode('`, `', $colunas),
                implode(', ', array_fill(0, count($colunas), '?'))
            );
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
            $sets = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($dados)));
            $this->pdo->prepare("UPDATE `$tabela` SET $sets WHERE id = ?")->execute([...array_values($dados), $id]);
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
            $this->pdo->prepare("DELETE FROM `$tabela`" . $where)->execute($params);
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
                $partes[] = "`$campo` IN (" . implode(', ', array_fill(0, count($valor), '?')) . ')';
                array_push($params, ...array_values($valor));
            } else {
                $partes[] = "`$campo` = ?";
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
