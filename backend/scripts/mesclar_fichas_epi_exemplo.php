<?php
/**
 * Acrescenta ao backend/data/epis.json as fichas de EPI de exemplo que ainda não existem lá
 * (compara pelo "id"). Nunca altera nem remove fichas existentes e guarda uma cópia antes.
 *
 * Uso (no servidor, na raiz do projeto):  php backend/scripts/mesclar_fichas_epi_exemplo.php
 *
 * Necessário porque, em produção, o epis.json já existe e a cópia automática de
 * backend/data/exemplos (config/dados_iniciais.php) só cria arquivos que faltam.
 */
$dados = dirname(__DIR__) . '/data';
$origem = $dados . '/exemplos/epis.json';
$destino = $dados . '/epis.json';

$exemplos = json_decode((string)@file_get_contents($origem), true);
if (!is_array($exemplos)) {
    fwrite(STDERR, "Não consegui ler $origem\n");
    exit(1);
}
$atuais = is_file($destino) ? json_decode((string)file_get_contents($destino), true) : [];
if (!is_array($atuais)) {
    fwrite(STDERR, "O $destino não é um JSON válido; nada foi alterado.\n");
    exit(1);
}

$ids = array_column($atuais, 'id');
$novas = array_values(array_filter($exemplos, fn($e) => !in_array($e['id'] ?? null, $ids, true)));
if (!$novas) {
    echo "Nenhuma ficha nova: o epis.json já tem todas as de exemplo.\n";
    exit(0);
}

if (is_file($destino)) {
    copy($destino, $destino . '.bak-' . date('Ymd-His'));
}
$json = json_encode(array_merge($atuais, $novas), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if (file_put_contents($destino, $json, LOCK_EX) === false) {
    fwrite(STDERR, "Sem permissão para gravar em $destino.\n");
    exit(1);
}
echo 'Fichas acrescentadas: ' . implode(', ', array_column($novas, 'id')) . "\n";
