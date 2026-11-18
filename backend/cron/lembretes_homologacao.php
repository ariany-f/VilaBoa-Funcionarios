<?php
/**
 * Lembretes automáticos de homologação sindical (WhatsApp + e-mail do funcionário).
 *
 * Agende no Agendador de Tarefas do Windows (ou cron) para rodar de hora em hora, por exemplo:
 *   schtasks /Create /SC HOURLY /TN "VilaBoa Lembretes Homologacao" /TR "php C:\caminho\backend\cron\lembretes_homologacao.php"
 *   0 * * * * php /var/www/backend/cron/lembretes_homologacao.php      (Linux)
 *
 * Cada agendamento recebe o lembrete uma única vez (lembrete_enviado_em); rodar várias vezes é seguro.
 *
 * Opções:  --simular            só lista o que seria enviado, sem enviar nem gravar
 *          --agora="2026-12-01 09:00"   finge outro horário (testes)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Execute apenas pela linha de comando.\n");
}

date_default_timezone_set('America/Sao_Paulo');
require_once dirname(__DIR__) . '/services/ColaboradorService.php';
require_once dirname(__DIR__) . '/services/HomologacaoService.php';

use App\Services\ColaboradorService;
use App\Services\HomologacaoService;

$simular = false;
$agora = null;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--simular') $simular = true;
    if (str_starts_with($arg, '--agora=')) {
        $agora = strtotime(substr($arg, 8));
        if ($agora === false) exit("Valor inválido em --agora.\n");
    }
}

$resumo = (new HomologacaoService(new ColaboradorService()))->enviarLembretesPendentes($agora, $simular);

echo '[' . date('Y-m-d H:i:s', $agora ?? time()) . '] Homologação: '
    . count($resumo['enviados']) . ($simular ? ' lembrete(s) seriam enviados' : ' lembrete(s) enviados')
    . ', ' . $resumo['ignorados'] . " fora da janela.\n";
foreach ($resumo['enviados'] as $linha) echo " - {$linha}\n";
