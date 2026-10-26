<?php
/**
 * Conexão de banco do portal.
 *
 * DB_DRIVER=json  -> usa os arquivos de backend/data/db/*.json (simulação, padrão enquanto o banco não existe)
 * DB_DRIVER=mysql -> usa o MySQL configurado abaixo (crie as tabelas com backend/database/create_tables.sql)
 *
 * Os valores vêm de variáveis de ambiente ou do arquivo backend/config/.env (não versionado).
 */

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

return [
    'driver' => $ler('DB_DRIVER', 'json'),
    'json_dir' => dirname(__DIR__) . '/data/db',
    'mysql' => [
        'host' => $ler('DB_HOST', '127.0.0.1'),
        'port' => (int)$ler('DB_PORT', '3306'),
        'database' => $ler('DB_DATABASE', 'portal_funcionario_vb'),
        'username' => $ler('DB_USERNAME', 'root'),
        'password' => $ler('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
];
