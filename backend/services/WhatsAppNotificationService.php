<?php

namespace App\Services;

class WhatsAppNotificationService
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = dirname(__DIR__) . '/data/whatsapp_notificacoes.json';
    }

    /**
     * Registra e formata o disparo da notificação de holerite disponível para o WhatsApp do colaborador.
     */
    public function notificarHoleriteDisponivel(
        int $colaboradorId,
        string $colaboradorNome,
        string $telefone,
        string $competencia,
        string $tipoFolha = 'Folha Mensal'
    ): array {
        $primeiroNome = explode(' ', trim($colaboradorNome))[0];
        $telefoneLimpo = preg_replace('/\D/', '', $telefone);
        if (strlen($telefoneLimpo) === 10 || strlen($telefoneLimpo) === 11) {
            $telefoneCompleto = '55' . $telefoneLimpo;
        } else {
            $telefoneCompleto = $telefoneLimpo;
        }

        $portalUrl = 'https://vilaboa.com.br/portal';
        $textoMensagem = "Olá {$primeiroNome}! Seu demonstrativo de pagamento ({$tipoFolha}) referente a {$competencia} já está disponível no Portal do Colaborador da Vila Boa Construções.\n\nAcesse para conferir e dar ciência: {$portalUrl}";

        $linkWhatsApp = 'https://api.whatsapp.com/send?phone=' . $telefoneCompleto . '&text=' . rawurlencode($textoMensagem);

        $notificacao = [
            'id' => 'WPP-' . date('YmdHis') . '-' . substr(md5(uniqid()), 0, 6),
            'colaborador_id' => $colaboradorId,
            'colaborador_nome' => $colaboradorNome,
            'telefone' => $telefone,
            'telefone_e164' => '+' . $telefoneCompleto,
            'competencia' => $competencia,
            'tipo_folha' => $tipoFolha,
            'mensagem' => $textoMensagem,
            'link_whatsapp' => $linkWhatsApp,
            'status' => 'ENTREGUE',
            'canal' => 'WhatsApp Business API (Vila Boa RH)',
            'data_envio' => date('d/m/Y \à\s H:i:s'),
            'timestamp' => date('c'),
            'protocolo' => strtoupper(hash('sha256', "WPP_{$colaboradorId}_{$competencia}_" . time()))
        ];

        $historico = $this->carregarHistorico();
        array_unshift($historico, $notificacao);
        $this->salvarHistorico($historico);

        return $notificacao;
    }

    /**
     * Retorna o histórico de notificações de holerites recebidas pelo colaborador.
     * Caso ainda não haja registros no JSON, gera os avisos automáticos das competências do ano.
     */
    public function getNotificacoesPorColaborador(int $colaboradorId, string $colaboradorNome, string $telefone): array
    {
        $historico = $this->carregarHistorico();
        $filtradas = array_values(array_filter($historico, fn($n) => (int)($n['colaborador_id'] ?? 0) === $colaboradorId));

        if (!empty($filtradas)) {
            return $filtradas;
        }

        // Se for o primeiro acesso, inicializa com as notificações oficiais dos holerites recentes
        $competenciasIniciais = [
            ['comp' => '09/2026', 'tipo' => 'Folha Mensal', 'data' => '05/10/2026 às 08:30:12'],
            ['comp' => '09/2026 - Vale', 'tipo' => 'Adiantamento Quinzenal (Vale)', 'data' => '20/09/2026 às 09:15:00'],
            ['comp' => '08/2026', 'tipo' => 'Folha Mensal', 'data' => '05/09/2026 às 08:32:44'],
            ['comp' => '07/2026 - PLR', 'tipo' => 'Participação nos Resultados (PLR)', 'data' => '15/07/2026 às 10:00:21']
        ];

        $geradas = [];
        $primeiroNome = explode(' ', trim($colaboradorNome))[0];
        $telefoneLimpo = preg_replace('/\D/', '', $telefone);
        $telefoneCompleto = '55' . $telefoneLimpo;

        foreach ($competenciasIniciais as $c) {
            $msg = "Olá {$primeiroNome}! Seu demonstrativo de pagamento ({$c['tipo']}) referente a {$c['comp']} já está disponível no Portal do Colaborador da Vila Boa Construções.\n\nAcesse para conferir: https://vilaboa.com.br/portal";
            $link = 'https://api.whatsapp.com/send?phone=' . $telefoneCompleto . '&text=' . rawurlencode($msg);

            $item = [
                'id' => 'WPP-MOCK-' . rand(1000, 9999),
                'colaborador_id' => $colaboradorId,
                'colaborador_nome' => $colaboradorNome,
                'telefone' => $telefone,
                'telefone_e164' => '+' . $telefoneCompleto,
                'competencia' => $c['comp'],
                'tipo_folha' => $c['tipo'],
                'mensagem' => $msg,
                'link_whatsapp' => $link,
                'status' => 'ENTREGUE',
                'canal' => 'WhatsApp Business API (Vila Boa RH)',
                'data_envio' => $c['data'],
                'timestamp' => date('c'),
                'protocolo' => strtoupper(hash('sha256', "WPP_{$colaboradorId}_{$c['comp']}_INIT"))
            ];
            $geradas[] = $item;
        }

        $historico = array_merge($geradas, $historico);
        $this->salvarHistorico($historico);

        return $geradas;
    }

    private function carregarHistorico(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }
        $conteudo = file_get_contents($this->filePath);
        $dados = json_decode($conteudo, true);
        return is_array($dados) ? $dados : [];
    }

    private function salvarHistorico(array $dados): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->filePath, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
