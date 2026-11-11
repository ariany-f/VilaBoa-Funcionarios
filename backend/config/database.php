<?php
/**
 * Conexões de banco do portal (uma ou várias, de arquiteturas diferentes).
 *
 * Cada conexão tem um nome e um driver:
 *   json   -> arquivos de backend/data/db/*.json (simulação, padrão enquanto o banco não existe)
 *   pgsql  -> PostgreSQL        mysql -> MySQL/MariaDB        sqlsrv -> SQL Server        sqlite -> arquivo SQLite
 *
 * Como configurar (variáveis de ambiente ou arquivo backend/config/.env, não versionado):
 *   DB_CONEXOES=principal            nomes das conexões, separados por vírgula (padrão: principal)
 *   DB_PADRAO=principal              conexão usada por Banco::conexao() sem nome (padrão: a primeira)
 *
 *   A conexão "principal" usa o prefixo DB_:        DB_DRIVER, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
 *   As demais usam DB_<NOME>_ (ex.: "ponto"):       DB_PONTO_DRIVER, DB_PONTO_HOST, DB_PONTO_PORT ...
 *   (sqlite: informe só o caminho do arquivo em DB_DATABASE)
 *
 * Exemplo com duas conexões:
 *   DB_CONEXOES=principal,legado
 *   DB_DRIVER=pgsql ... DB_LEGADO_DRIVER=sqlsrv ...
 *
 * No código: Banco::conexao() (padrão) ou Banco::conexao('legado'). Veja backend/config/.env.exemplo.
 */

// Função anônima: as variáveis daqui não vazam para quem faz require deste arquivo
return (static function (): array {
$env = [];
$arquivoEnv = __DIR__ . '/.env';
if (is_file($arquivoEnv)) {
    foreach (file($arquivoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
        if (str_starts_with(trim($linha), '#') || !str_contains($linha, '=')) {
            continue;
        }
        [$chave, $valor] = array_map('trim', explode('=', $linha, 2));
        $env[$chave] = trim($valor, "\"'");
    }
}

$ler = fn(string $chave, string $padrao = '') => getenv($chave) !== false ? getenv($chave) : ($env[$chave] ?? $padrao);

$portasPadrao = ['mysql' => 3306, 'pgsql' => 5432, 'sqlsrv' => 1433];
$nomes = array_values(array_filter(array_map('trim', explode(',', $ler('DB_CONEXOES', 'principal')))));
$nomes = $nomes ?: ['principal'];

$conexoes = [];
foreach ($nomes as $nome) {
    $prefixo = $nome === 'principal' ? 'DB_' : 'DB_' . strtoupper(preg_replace('/[^a-z0-9]/i', '_', $nome)) . '_';
    $driver = strtolower($ler($prefixo . 'DRIVER', $nome === 'principal' ? 'json' : ''));
    if ($driver === '') {
        continue; // conexão nomeada sem driver: ignorada
    }
    $conexoes[$nome] = [
        'driver' => $driver,
        'host' => $ler($prefixo . 'HOST', '127.0.0.1'),
        'port' => (int)$ler($prefixo . 'PORT', (string)($portasPadrao[$driver] ?? 0)),
        'database' => $ler($prefixo . 'DATABASE', $driver === 'pgsql' ? 'portal_funcionarios' : 'portal_funcionario_vb'),
        'username' => $ler($prefixo . 'USERNAME', $driver === 'pgsql' ? 'postgres' : 'root'),
        'password' => $ler($prefixo . 'PASSWORD', ''),
        'charset' => $ler($prefixo . 'CHARSET', 'utf8mb4'),
    ];
}

return [
    'padrao' => $ler('DB_PADRAO', array_key_first($conexoes) ?? 'principal'),
    'json_dir' => dirname(__DIR__) . '/data/db',
    // Só para a instalação (scripts/instalar_banco.php): usuário com permissão de criar banco/usuário no servidor.
    // Não precisa ficar configurado no dia a dia.
    'admin' => [
        'host' => $ler('DB_ADMIN_HOST', ''),
        'port' => (int)$ler('DB_ADMIN_PORT', '0'),
        'database' => $ler('DB_ADMIN_DATABASE', ''),
        'username' => $ler('DB_ADMIN_USERNAME', ''),
        'password' => $ler('DB_ADMIN_PASSWORD', ''),
    ],
    'conexoes' => $conexoes ?: ['principal' => ['driver' => 'json']],
];
})();
