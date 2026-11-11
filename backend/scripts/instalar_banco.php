<?php
/**
 * Instala/atualiza o banco do portal automaticamente (rode a cada deploy; é idempotente).
 *
 *   php backend/scripts/instalar_banco.php [--conexao=principal] [--origem=PASTA] [--sem-dados] [--sobrescrever]
 *
 * O que faz, em ordem:
 *   1. (PostgreSQL/MySQL) Se DB_ADMIN_USERNAME/DB_ADMIN_PASSWORD estiverem definidos, cria o usuário e o banco do
 *      portal quando ainda não existirem. O banco novo fica só do portal: nenhum outro banco do servidor é tocado.
 *   2. Cria as tabelas que faltam (backend/database/pgsql/schema.sql) sem apagar nada.
 *   3. Importa os dados de backend/data (ou de --origem) SEM sobrescrever o que já existe no banco
 *      (use --sobrescrever para atualizar linhas existentes com o conteúdo dos arquivos).
 *   4. Ajusta os contadores de id e grava as telas/ações de permissão novas (config/permissoes.php).
 *
 * Configuração: variáveis de ambiente ou backend/config/.env (veja backend/config/.env.exemplo).
 * Em produção, defina DB_DRIVER=pgsql, DB_HOST, DB_DATABASE, DB_USERNAME e DB_PASSWORD do portal e, só na instalação,
 * DB_ADMIN_USERNAME/DB_ADMIN_PASSWORD (pode ser removido depois).
 */

require_once dirname(__DIR__) . '/config/dados_iniciais.php';
require_once dirname(__DIR__) . '/database/Banco.php';
require_once dirname(__DIR__) . '/services/PermissaoService.php';

use App\Database\Banco;
use App\Services\PermissaoService;

$opcoes = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $a, $m)) $opcoes[$m[1]] = $m[2] ?? true;
}
function info(string $t): void { echo $t . "\n"; }
function falhar(string $t): never { fwrite(STDERR, "ERRO: $t\n"); exit(1); }

$config = require dirname(__DIR__) . '/config/database.php';
$nome = is_string($opcoes['conexao'] ?? null) ? $opcoes['conexao'] : $config['padrao'];
$cfg = $config['conexoes'][$nome] ?? falhar("Conexão não configurada: $nome");
$driver = $cfg['driver'];
if ($driver === 'json') falhar("A conexão \"$nome\" usa DB_DRIVER=json. Defina DB_DRIVER=pgsql (ou outro) e os dados de acesso.");
if ($cfg['database'] === '' || $cfg['username'] === '') falhar('Defina DB_DATABASE e DB_USERNAME.');

info("Conexão: $nome ($driver) · banco {$cfg['database']} em {$cfg['host']}");

// ------------------------------------------------------------------ 1. banco e usuário do portal
$adm = $config['admin'];
if ($adm['username'] !== '' && in_array($driver, ['pgsql', 'mysql'], true)) {
    $admCfg = [
        'driver' => $driver,
        'host' => $adm['host'] ?: $cfg['host'],
        'port' => $adm['port'] ?: $cfg['port'],
        'database' => $adm['database'] ?: ($driver === 'pgsql' ? 'postgres' : 'mysql'),
        'username' => $adm['username'],
        'password' => $adm['password'],
        'charset' => $cfg['charset'],
    ];
    try {
        $root = Banco::avulsa($admCfg);
    } catch (Throwable $e) {
        falhar("Não foi possível entrar como administrador (usuário \"{$adm['username']}\" em {$admCfg['host']}): confira DB_ADMIN_USERNAME e DB_ADMIN_PASSWORD no .env.\n" . $e->getMessage());
    }
    $pdo = $root->pdo();
    $ident = fn(string $s) => ($driver === 'mysql' ? '`' : '"') . preg_replace('/[^A-Za-z0-9_]/', '', $s) . ($driver === 'mysql' ? '`' : '"');

    if ($driver === 'pgsql') {
        if ($cfg['username'] !== $adm['username']) {
            if ($cfg['password'] === '') falhar('Defina DB_PASSWORD (a senha do usuário do portal).');
            if (!$root->consultar('SELECT 1 FROM pg_roles WHERE rolname = ?', [$cfg['username']])) {
                $pdo->exec('CREATE ROLE ' . $ident($cfg['username']) . ' LOGIN PASSWORD ' . $pdo->quote($cfg['password']));
                info("Usuário {$cfg['username']} criado.");
            } else {
                // O DB_PASSWORD do .env é a fonte da verdade: mantém o usuário existente com essa senha
                $pdo->exec('ALTER ROLE ' . $ident($cfg['username']) . ' LOGIN PASSWORD ' . $pdo->quote($cfg['password']));
                info("Usuário {$cfg['username']} já existe: senha ajustada para o DB_PASSWORD do .env.");
            }
        }
        if (!$root->consultar('SELECT 1 FROM pg_database WHERE datname = ?', [$cfg['database']])) {
            $dono = $cfg['username'] !== $adm['username'] ? ' OWNER ' . $ident($cfg['username']) : '';
            $pdo->exec('CREATE DATABASE ' . $ident($cfg['database']) . $dono . " ENCODING 'UTF8'");
            // Só o dono acessa o banco novo
            $pdo->exec('REVOKE ALL ON DATABASE ' . $ident($cfg['database']) . ' FROM PUBLIC');
            info("Banco {$cfg['database']} criado.");
        } else {
            info("Banco {$cfg['database']} já existe: mantido.");
        }
    } else {
        $pdo->exec('CREATE DATABASE IF NOT EXISTS ' . $ident($cfg['database']) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        info("Banco {$cfg['database']} garantido.");
    }
}

// ------------------------------------------------------------------ 2. tabelas
try {
    $banco = Banco::conexao($nome);
} catch (Throwable $e) {
    falhar('Não foi possível conectar: ' . $e->getMessage() . "\nSe o banco ainda não existe, informe DB_ADMIN_USERNAME e DB_ADMIN_PASSWORD para criá-lo.");
}
$esquema = dirname(__DIR__) . '/database/' . ($driver === 'pgsql' ? 'pgsql/schema.sql' : 'create_tables.sql');
if (!is_file($esquema)) falhar("Não há esquema para o driver $driver.");
$banco->pdo()->exec(file_get_contents($esquema));
info('Tabelas verificadas (' . basename(dirname($esquema)) . '/' . basename($esquema) . ').');

if ($driver !== 'pgsql') {
    info("Importação de dados disponível só para pgsql; o esquema do driver $driver cobre apenas login e permissões.");
    exit(0);
}
if (isset($opcoes['sem-dados'])) {
    info('Importação de dados ignorada (--sem-dados).');
} else {
    // ------------------------------------------------------------------ 3. dados
    prepararDadosIniciais();
    $origem = is_string($opcoes['origem'] ?? null) ? rtrim($opcoes['origem'], '/\\') : dirname(__DIR__) . '/data';
    $sobrescrever = isset($opcoes['sobrescrever']);
    info("Importando dados de $origem" . ($sobrescrever ? ' (sobrescrevendo existentes)' : ' (sem sobrescrever)'));

    $json = function (string $rel) use ($origem): array {
        $f = $origem . '/' . $rel;
        return is_file($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
    };
    $d = fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    $dia = fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    $dh = fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?/', $v) ? substr(str_replace('T', ' ', $v), 0, 19) : null;
    $sn = fn($v) => $v ? 1 : 0;
    $totais = [];

    $gravar = function (string $tabela, array $pk, array $linhas) use ($banco, $sobrescrever, &$totais): void {
        if (!$linhas) { $totais[$tabela] = 0; return; }
        $colunas = array_keys($linhas[0]);
        $q = fn($c) => '"' . $c . '"';
        $atualizaveis = array_values(array_diff($colunas, $pk));
        $acao = ($sobrescrever && $atualizaveis)
            ? 'DO UPDATE SET ' . implode(', ', array_map(fn($c) => $q($c) . ' = EXCLUDED.' . $q($c), $atualizaveis))
            : 'DO NOTHING';
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s) ON CONFLICT (%s) %s', $q($tabela), implode(', ', array_map($q, $colunas)), implode(', ', array_fill(0, count($colunas), '?')), implode(', ', array_map($q, $pk)), $acao);
        $stmt = $banco->pdo()->prepare($sql);
        $novas = 0;
        foreach ($linhas as $l) {
            $stmt->execute(array_values($l));
            $novas += $stmt->rowCount();
        }
        $totais[$tabela] = $novas;
    };

    $banco->pdo()->beginTransaction();
    try {
        // login e permissões (ordem respeita as chaves estrangeiras)
        foreach ([
            'roles' => ['id'], 'users' => ['id'], 'users_roles' => ['id'], 'menus' => ['id'], 'roles_menus' => ['id'],
            'permission_screens' => ['id'], 'permission_actions' => ['id'], 'roles_permission_actions' => ['id'],
        ] as $tabela => $pk) {
            $gravar($tabela, $pk, array_map(fn($l) => array_map(fn($v) => is_bool($v) ? (int)$v : $v, $l), $json("db/$tabela.json")));
        }

        $gravar('empresas', ['nome'], array_map(fn($e) => is_array($e) ? ['nome' => $e['nome'], 'logo' => $e['logo'] ?? null] : ['nome' => $e, 'logo' => null], $json('empresas.json')));

        $gravar('colaboradores', ['id'], array_map(fn($c) => [
            'id' => $c['id'], 'matricula' => $c['matricula'], 'cpf' => preg_replace('/\D/', '', $c['cpf'] ?? ''), 'nome' => $c['nome'],
            'cargo' => $c['cargo'] ?? null, 'departamento' => $c['departamento'] ?? null, 'empresa' => $c['empresa'] ?? null,
            'data_nascimento' => $dia($c['data_nascimento'] ?? null), 'data_admissao' => $dia($c['data_admissao'] ?? null), 'dados' => $d($c),
        ], $json('colaboradores.json')));

        $gravar('chamados', ['id'], array_map(fn($c) => [
            'id' => $c['id'], 'colaborador_id' => $c['colaborador_id'] ?? null, 'matricula' => $c['matricula'] ?? null, 'tipo' => $c['tipo'] ?? null,
            'assunto' => $c['assunto'] ?? null, 'status' => $c['status'] ?? null, 'data_abertura' => $dh($c['data_abertura'] ?? null), 'dados' => $d($c),
        ], $json('chamados.json')));

        $ferias = [];
        foreach ($json('ferias.json') as $matricula => $f) $ferias[] = ['matricula' => $matricula, 'dados' => $d($f)];
        $gravar('ferias', ['matricula'], $ferias);

        $gravar('epis', ['id'], array_map(fn($e) => [
            'id' => $e['id'], 'colaborador_id' => $e['colaborador_id'] ?? null, 'matricula' => $e['matricula'] ?? null,
            'data_entrega' => $dia($e['data_entrega'] ?? null), 'status' => $e['status'] ?? null, 'dados' => $d($e),
        ], $json('epis.json')));

        $ponto = [];
        foreach ($json('ponto.json') as $matricula => $competencias) {
            foreach ($competencias as $competencia => $espelho) $ponto[] = ['matricula' => $matricula, 'competencia' => $competencia, 'dados' => $d($espelho)];
        }
        $gravar('ponto', ['matricula', 'competencia'], $ponto);

        $ciencias = [];
        foreach ($json('holerites_ciencias.json') as $chave => $c) {
            $ciencias[] = ['chave' => $chave, 'matricula' => $c['matricula'] ?? null, 'holerite_id' => $c['holerite_id'] ?? null, 'competencia' => $c['competencia'] ?? null, 'dados' => $d($c)];
        }
        $gravar('holerites_ciencias', ['chave'], $ciencias);

        $gravar('whatsapp_notificacoes', ['id'], array_map(fn($w) => ['id' => $w['id'], 'colaborador_id' => $w['colaborador_id'] ?? null, 'dados' => $d($w)], $json('whatsapp_notificacoes.json')));

        $gravar('comunicados', ['id'], array_map(fn($c) => [
            'id' => $c['id'], 'publicado_em' => $dh($c['publicado_em'] ?? null), 'autor' => $c['autor'] ?? null, 'titulo' => $c['titulo'],
            'categoria' => $c['categoria'] ?? null, 'prioridade' => $c['prioridade'] ?? null, 'fixado' => $sn($c['fixado'] ?? false),
            'empresa' => $c['empresa'] ?? null, 'dados' => $d($c),
        ], $json('comunicados.json')));

        $gravar('cadernos_categorias', ['nome'], array_map(fn($n, $i) => ['nome' => $n, 'posicao' => $i], $json('cadernos_categorias.json'), array_keys($json('cadernos_categorias.json'))));

        $config = [];
        foreach ($json('cadernos_config.json') as $arquivo => $c) {
            $config[] = ['arquivo' => $arquivo, 'categoria' => $c['categoria'] ?? null, 'obrigatorio' => $sn($c['obrigatorio'] ?? false), 'publico' => $c['publico'] ?? null, 'dados' => $d($c)];
        }
        $gravar('cadernos_config', ['arquivo'], $config);

        $leituras = [];
        foreach ($json('cadernos_leituras.json') as $chave => $l) {
            $leituras[] = ['chave' => $chave, 'matricula' => $l['matricula'], 'arquivo' => $l['arquivo'], 'hash_arquivo' => $l['hash_arquivo'] ?? null, 'dados' => $d($l)];
        }
        $gravar('cadernos_leituras', ['chave'], $leituras);

        $trilhas = [];
        $itens = [];
        foreach ($json('academia_trilhas.json') as $t) {
            $trilhas[] = [
                'id' => $t['id'], 'titulo' => $t['titulo'], 'descricao' => $t['descricao'] ?? null, 'obrigatoria' => $sn($t['obrigatoria'] ?? false),
                'publicada' => $sn($t['publicada'] ?? true), 'empresa' => $t['empresa'] ?? 'todas', 'carga_horaria' => $t['carga_horaria'] ?? null,
                'prazo' => $dia($t['prazo'] ?? null), 'pontos' => isset($t['pontos']) && $t['pontos'] !== '' ? (int)$t['pontos'] : null,
                'funcoes' => $d($t['funcoes'] ?? []), 'dados' => $d(array_diff_key($t, ['itens' => 1])),
            ];
            foreach (($t['itens'] ?? []) as $n => $i) {
                $itens[] = [
                    'trilha_id' => $t['id'], 'id' => $i['id'], 'posicao' => $n, 'tipo' => $i['tipo'], 'titulo' => $i['titulo'] ?? null, 'descricao' => $i['descricao'] ?? null,
                    'url' => $i['url'] ?? null, 'arquivo' => $i['arquivo'] ?? null, 'duracao' => $i['duracao'] ?? null,
                    'pontos' => isset($i['pontos']) && $i['pontos'] !== '' ? (int)$i['pontos'] : null,
                ];
            }
        }
        $gravar('academia_trilhas', ['id'], $trilhas);
        $gravar('academia_itens', ['trilha_id', 'id'], $itens);

        $progresso = [];
        foreach ($json('academia_progresso.json') as $chave => $p) {
            $progresso[] = ['chave' => $chave, 'matricula' => $p['matricula'], 'trilha_id' => $p['trilha'], 'item_id' => $p['item'], 'dados' => $d($p)];
        }
        $gravar('academia_progresso', ['chave'], $progresso);

        $certificados = [];
        foreach ($json('academia_certificados.json') as $chave => $c) {
            $certificados[] = ['chave' => $chave, 'codigo' => $c['codigo'], 'matricula' => $c['matricula'], 'trilha_id' => $c['trilha_id'], 'dados' => $d($c)];
        }
        $gravar('academia_certificados', ['chave'], $certificados);

        // contadores de id (as linhas importadas trazem o id do JSON)
        foreach (['users', 'roles', 'users_roles', 'menus', 'roles_menus', 'permission_screens', 'permission_actions', 'roles_permission_actions'] as $t) {
            $banco->pdo()->exec("SELECT setval(pg_get_serial_sequence('$t', 'id'), GREATEST((SELECT COALESCE(MAX(id), 0) FROM \"$t\"), 1))");
        }
        $banco->pdo()->commit();
    } catch (Throwable $e) {
        $banco->pdo()->rollBack();
        falhar('Importação cancelada, nada foi gravado: ' . $e->getMessage());
    }

    foreach ($totais as $tabela => $n) info(sprintf('  %-24s %d %s', $tabela, $n, $sobrescrever ? 'gravadas' : 'novas'));
}

// ------------------------------------------------------------------ 4. permissões novas
$resumo = (new PermissaoService($banco))->sincronizar();
info("Permissões: {$resumo['telas']} telas novas · {$resumo['acoes']} ações novas");
info('Pronto.');
