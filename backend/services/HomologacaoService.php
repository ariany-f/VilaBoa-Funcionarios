<?php

namespace App\Services;

require_once __DIR__ . '/WhatsAppNotificationService.php';
require_once __DIR__ . '/EmailNotificationService.php';
require_once __DIR__ . '/EmpresaService.php';

/**
 * Agendador de homologação sindical (rescisão de colaborador demitido assistida pelo sindicato).
 * O RH registra o agendamento e o funcionário recebe WhatsApp + e-mail: convite na criação,
 * lembrete antes da data (script cron/lembretes_homologacao.php) e aviso ao remarcar/cancelar.
 * Dados em data/homologacoes.json; as mensagens vão para as filas de WhatsApp e e-mail.
 *
 * Status: agendado (ainda sem aviso enviado) | enviado (funcionário avisado) | realizado | cancelado.
 */
class HomologacaoService
{
    public const STATUS = ['agendado', 'enviado', 'realizado', 'cancelado'];
    public const DOCUMENTOS_PADRAO = [
        'Documento de identidade (RG ou CNH)',
        'CPF',
        'Carteira de Trabalho (CTPS)',
        'Termo de Rescisão do Contrato de Trabalho (TRCT)',
        'Guias de seguro-desemprego',
        'Extrato do FGTS',
        'Comprovante de aviso prévio',
        'Exame demissional (ASO)',
    ];
    public const LEMBRETE_DIAS = [1, 2, 3, 5, 7];

    private string $arquivo;
    private WhatsAppNotificationService $whatsapp;
    private EmailNotificationService $email;

    public function __construct(private ColaboradorService $colaboradores)
    {
        // Data/hora do agendamento é horário de Brasília (o PHP daqui usa outro fuso por padrão)
        date_default_timezone_set('America/Sao_Paulo');
        $this->arquivo = dirname(__DIR__) . '/data/homologacoes.json';
        $this->whatsapp = new WhatsAppNotificationService();
        $this->email = new EmailNotificationService();
    }

    // ---------------------------------------------------------------- consulta

    public function listar(): array
    {
        $lista = $this->todos();
        usort($lista, function ($a, $b) {
            $ativoA = in_array($a['status'], ['agendado', 'enviado'], true) ? 0 : 1;
            $ativoB = in_array($b['status'], ['agendado', 'enviado'], true) ? 0 : 1;
            if ($ativoA !== $ativoB) return $ativoA <=> $ativoB;
            return $ativoA === 0 ? strcmp($a['data_hora'], $b['data_hora']) : strcmp($b['data_hora'], $a['data_hora']);
        });
        return $lista;
    }

    /** Colaboradores para o seletor do RH (com os contatos que receberão a mensagem). */
    public function colaboradoresParaSeletor(): array
    {
        return array_map(fn($c) => [
            'matricula' => $c['matricula'],
            'nome' => $c['nome'],
            'cargo' => $c['cargo'] ?? '',
            'empresa' => EmpresaService::doColaborador($c),
            'telefone' => $c['telefone_atual'] ?? '',
            'email' => $c['email'] ?? '',
        ], $this->colaboradores->getAll());
    }

    /** Sindicatos já usados, para sugerir no cadastro. */
    public function sindicatosUsados(): array
    {
        $vistos = [];
        foreach ($this->todos() as $h) {
            $chave = mb_strtolower($h['sindicato']);
            $vistos[$chave] ??= ['sindicato' => $h['sindicato'], 'local' => $h['local'] ?? ''];
        }
        return array_values($vistos);
    }

    // ---------------------------------------------------------------- ações do RH

    public function criar(array $dados, string $autor): array
    {
        $colab = $this->colaboradorPorMatricula((string)($dados['matricula'] ?? ''));
        if (!$colab) return ['erro' => 'Escolha o colaborador demitido.', 'status' => 422];

        $campos = $this->validarAgendamento($dados);
        if (isset($campos['erro'])) return $campos;

        $telefone = trim((string)($dados['telefone'] ?? '')) ?: ($colab['telefone_atual'] ?? '');
        $email = trim((string)($dados['email'] ?? '')) ?: ($colab['email'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) return ['erro' => 'O e-mail informado não é válido.', 'status' => 422];
        if (preg_replace('/\D/', '', $telefone) === '' && $email === '') {
            return ['erro' => 'Informe ao menos um contato (WhatsApp ou e-mail) para avisar o funcionário.', 'status' => 422];
        }

        $dataDemissao = trim((string)($dados['data_demissao'] ?? ''));
        if ($dataDemissao !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataDemissao)) return ['erro' => 'Data da demissão inválida.', 'status' => 422];

        $dias = (int)($dados['lembrete_dias'] ?? 2);
        if (!in_array($dias, self::LEMBRETE_DIAS, true)) $dias = 2;

        $resposta = null;
        $this->transacao(function (array &$lista) use (&$resposta, $colab, $campos, $telefone, $email, $dataDemissao, $dias, $autor, $dados) {
            foreach ($lista as $h) {
                if ($h['matricula'] === $colab['matricula'] && in_array($h['status'], ['agendado', 'enviado'], true)) {
                    $resposta = ['erro' => 'Já existe uma homologação ativa para este colaborador. Use "Remarcar" no agendamento existente.', 'status' => 409];
                    return;
                }
            }
            $h = [
                'id' => 'HOM-' . date('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 5),
                'matricula' => $colab['matricula'],
                'colaborador_id' => (int)$colab['id'],
                'colaborador_nome' => $colab['nome'],
                'cargo' => $colab['cargo'] ?? '',
                'empresa' => EmpresaService::doColaborador($colab),
                'telefone' => $telefone,
                'email' => $email,
                'data_demissao' => $dataDemissao,
                'lembrete_dias' => $dias,
                'status' => 'agendado',
                'criado_por' => $autor,
                'criado_em' => date('Y-m-d H:i:s'),
                'lembrete_enviado_em' => null,
                'envios' => [],
                'historico' => [['em' => date('Y-m-d H:i:s'), 'por' => $autor, 'evento' => 'Agendamento criado']],
            ] + $campos;

            if (!empty($dados['enviar_convite'])) {
                $this->disparar($h, 'convite', $autor);
            }
            $lista[] = $h;
            $resposta = ['sucesso' => true, 'homologacao' => $h];
        });
        return $resposta;
    }

    /** acao: reenviar | remarcar | cancelar | realizar */
    public function acao(string $id, string $acao, array $dados, string $autor): array
    {
        $resposta = ['erro' => 'Agendamento não encontrado.', 'status' => 404];
        $this->transacao(function (array &$lista) use ($id, $acao, $dados, $autor, &$resposta) {
            foreach ($lista as &$h) {
                if ($h['id'] !== $id) continue;
                $ativo = in_array($h['status'], ['agendado', 'enviado'], true);
                if (!$ativo) {
                    $resposta = ['erro' => 'Este agendamento já está ' . $h['status'] . ' e não pode mais ser alterado.', 'status' => 409];
                    return;
                }
                switch ($acao) {
                    case 'reenviar':
                        $this->disparar($h, 'reenvio', $autor);
                        break;
                    case 'remarcar':
                        $campos = $this->validarAgendamento($dados + $h);
                        if (isset($campos['erro'])) { $resposta = $campos; return; }
                        $anterior = $h['data_hora'];
                        $h = $campos + $h;
                        $h['lembrete_enviado_em'] = null;
                        $h['historico'][] = ['em' => date('Y-m-d H:i:s'), 'por' => $autor, 'evento' => 'Remarcado de ' . $anterior . ' para ' . $h['data_hora'], 'motivo' => trim((string)($dados['motivo'] ?? ''))];
                        $h['motivo_alteracao'] = trim((string)($dados['motivo'] ?? ''));
                        $this->disparar($h, 'remarcacao', $autor);
                        break;
                    case 'cancelar':
                        $h['status'] = 'cancelado';
                        $h['motivo_alteracao'] = trim((string)($dados['motivo'] ?? ''));
                        $h['historico'][] = ['em' => date('Y-m-d H:i:s'), 'por' => $autor, 'evento' => 'Cancelado', 'motivo' => $h['motivo_alteracao']];
                        $this->disparar($h, 'cancelamento', $autor);
                        $h['status'] = 'cancelado';
                        break;
                    case 'realizar':
                        $h['status'] = 'realizado';
                        $h['historico'][] = ['em' => date('Y-m-d H:i:s'), 'por' => $autor, 'evento' => 'Marcado como realizado'];
                        break;
                    default:
                        $resposta = ['erro' => 'Ação inválida.', 'status' => 422];
                        return;
                }
                $resposta = ['sucesso' => true, 'homologacao' => $h];
                return;
            }
        });
        return $resposta;
    }

    public function remover(string $id): array
    {
        $achou = false;
        $this->transacao(function (array &$lista) use ($id, &$achou) {
            $antes = count($lista);
            $lista = array_values(array_filter($lista, fn($h) => $h['id'] !== $id));
            $achou = count($lista) < $antes;
        });
        return $achou ? ['sucesso' => true] : ['erro' => 'Agendamento não encontrado.', 'status' => 404];
    }

    // ---------------------------------------------------------------- lembretes (cron)

    /**
     * Envia os lembretes que já venceram a janela e ainda não foram enviados.
     * Idempotente: lembrete_enviado_em evita reenvio, mesmo se o cron rodar várias vezes.
     */
    public function enviarLembretesPendentes(?int $agora = null, bool $simular = false): array
    {
        $agora ??= time();
        $resumo = ['enviados' => [], 'ignorados' => 0];
        $this->transacao(function (array &$lista) use ($agora, $simular, &$resumo) {
            foreach ($lista as &$h) {
                if (!in_array($h['status'], ['agendado', 'enviado'], true) || !empty($h['lembrete_enviado_em'])) continue;
                $quando = strtotime($h['data_hora']);
                if ($quando === false || $quando <= $agora) { $resumo['ignorados']++; continue; }

                $janela = $quando - $h['lembrete_dias'] * 86400;
                $ultimoAviso = $this->ultimoAvisoDeData($h);
                // Se a data foi combinada já dentro da janela, o lembrete vai 3 horas antes
                if ($ultimoAviso !== null && $ultimoAviso >= $janela) $janela = $quando - 3 * 3600;
                if ($ultimoAviso !== null && $ultimoAviso >= $janela) { $resumo['ignorados']++; continue; }
                if ($agora < $janela) { $resumo['ignorados']++; continue; }

                $resumo['enviados'][] = $h['id'] . ' · ' . $h['colaborador_nome'] . ' · ' . $h['data_hora'];
                if ($simular) continue;
                $h['lembrete_enviado_em'] = date('Y-m-d H:i:s', $agora);
                $this->disparar($h, 'lembrete', 'cron');
            }
        });
        return $resumo;
    }

    // ---------------------------------------------------------------- internos

    private function ultimoAvisoDeData(array $h): ?int
    {
        $ult = null;
        foreach ($h['envios'] as $e) {
            if (in_array($e['tipo'], ['convite', 'remarcacao'], true)) $ult = max($ult ?? 0, strtotime($e['em']));
        }
        return $ult;
    }

    private function validarAgendamento(array $d): array
    {
        $sindicato = trim((string)($d['sindicato'] ?? ''));
        if ($sindicato === '') return ['erro' => 'Informe o sindicato.', 'status' => 422];
        $dataHora = str_replace('T', ' ', trim((string)($d['data_hora'] ?? '')));
        $ts = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', $dataHora) ? strtotime($dataHora) : false;
        if ($ts === false) return ['erro' => 'Informe a data e a hora da homologação.', 'status' => 422];
        if ($ts <= time()) return ['erro' => 'A data e hora precisam ser futuras.', 'status' => 422];

        $docs = array_values(array_unique(array_filter(array_map(fn($x) => trim((string)$x), (array)($d['documentos'] ?? [])), fn($x) => $x !== '')));
        return [
            'sindicato' => mb_substr($sindicato, 0, 120),
            'local' => mb_substr(trim((string)($d['local'] ?? '')), 0, 200),
            'data_hora' => date('Y-m-d H:i', $ts),
            'observacoes' => mb_substr(trim((string)($d['observacoes'] ?? '')), 0, 1000),
            'documentos' => array_slice(array_map(fn($x) => mb_substr($x, 0, 120), $docs), 0, 20),
        ];
    }

    /** Monta e registra as mensagens de WhatsApp e e-mail e atualiza o status. */
    private function disparar(array &$h, string $tipo, string $autor): void
    {
        [$assunto, $texto] = $this->mensagem($h, $tipo);
        $extra = ['homologacao_id' => $h['id'], 'evento' => $tipo];
        $wpp = $this->whatsapp->registrarMensagem((int)$h['colaborador_id'], $h['colaborador_nome'], (string)$h['telefone'], $texto, 'homologacao', $extra);
        $eml = $this->email->enviar((int)$h['colaborador_id'], $h['colaborador_nome'], (string)$h['email'], $assunto, $texto, 'homologacao', $extra);

        $h['envios'][] = [
            'tipo' => $tipo,
            'em' => date('Y-m-d H:i:s'),
            'por' => $autor,
            'whatsapp' => ['id' => $wpp['id'], 'status' => $wpp['status'], 'link' => $wpp['link_whatsapp']],
            'email' => ['id' => $eml['id'], 'status' => $eml['status']],
        ];
        $chegou = $wpp['status'] !== 'SEM_DESTINO' || $eml['status'] !== 'SEM_DESTINO';
        if ($chegou && $h['status'] === 'agendado' && $tipo !== 'cancelamento') $h['status'] = 'enviado';
    }

    private function mensagem(array $h, string $tipo): array
    {
        $nome = explode(' ', trim($h['colaborador_nome']))[0];
        $dias = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
        $ts = strtotime($h['data_hora']);
        $quando = $dias[(int)date('w', $ts)] . ', ' . date('d/m/Y', $ts) . ' às ' . date('H:i', $ts);
        $onde = $h['sindicato'] . (!empty($h['local']) ? " ({$h['local']})" : '');

        $detalhes = "📅 Quando: {$quando}\n📍 Onde: {$onde}";
        if (!empty($h['documentos'])) $detalhes .= "\n\n📄 Leve os documentos:\n" . implode("\n", array_map(fn($d) => "• {$d}", $h['documentos']));
        if (!empty($h['observacoes'])) $detalhes .= "\n\nℹ️ Observações: {$h['observacoes']}";
        $fecho = "\n\nEm caso de dúvida ou se não puder comparecer, fale com o RH da Vila Boa Construções.";

        switch ($tipo) {
            case 'lembrete':
                return ['Lembrete: homologação no sindicato - ' . date('d/m/Y \à\s H:i', $ts),
                    "Olá, {$nome}! Lembrete da sua homologação sindical:\n\n{$detalhes}{$fecho}"];
            case 'remarcacao':
                $motivo = !empty($h['motivo_alteracao']) ? "\nMotivo: {$h['motivo_alteracao']}" : '';
                return ['Homologação remarcada - ' . date('d/m/Y \à\s H:i', $ts),
                    "Olá, {$nome}! Sua homologação sindical foi REMARCADA.{$motivo}\n\nNova data:\n\n{$detalhes}{$fecho}"];
            case 'cancelamento':
                $motivo = !empty($h['motivo_alteracao']) ? "\nMotivo: {$h['motivo_alteracao']}" : '';
                return ['Homologação cancelada',
                    "Olá, {$nome}! O agendamento da sua homologação sindical de {$quando} ({$onde}) foi CANCELADO.{$motivo}\n\nO RH entrará em contato para combinar uma nova data."];
            case 'reenvio':
                return ['Sua homologação no sindicato - ' . date('d/m/Y \à\s H:i', $ts),
                    "Olá, {$nome}! Reenviamos os dados da sua homologação sindical:\n\n{$detalhes}{$fecho}"];
            default:
                return ['Homologação no sindicato agendada - ' . date('d/m/Y \à\s H:i', $ts),
                    "Olá, {$nome}! Sua homologação sindical foi agendada. Ela é necessária para concluir o seu desligamento da Vila Boa Construções.\n\n{$detalhes}{$fecho}"];
        }
    }

    private function colaboradorPorMatricula(string $matricula): ?array
    {
        foreach ($this->colaboradores->getAll() as $c) {
            if ($c['matricula'] === $matricula) return $c;
        }
        return null;
    }

    private function todos(): array
    {
        if (!file_exists($this->arquivo)) return [];
        $dados = json_decode((string)file_get_contents($this->arquivo), true);
        return is_array($dados) ? $dados : [];
    }

    /** Lê, altera e grava sob trava, para dois acessos (RH e cron) não se atropelarem. */
    private function transacao(callable $fn): void
    {
        $lock = fopen($this->arquivo . '.lock', 'c');
        flock($lock, LOCK_EX);
        try {
            $lista = $this->todos();
            $fn($lista);
            file_put_contents($this->arquivo, json_encode(array_values($lista), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
