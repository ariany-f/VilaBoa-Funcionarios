<?php

namespace App\Services;

require_once __DIR__ . '/EmpresaService.php';

/**
 * Alerta de desligamento no Portal do Colaborador.
 *
 * Quando chega a data de demissão, o colaborador vê um aviso (modal) com os links do Bookings (Outlook)
 * para agendar a homologação com o DP e a retirada da documentação, e o Guia de Desligamento da empresa.
 * Ele marca "Já agendei" em cada um; com os dois agendados o aviso some.
 *
 * Data de demissão, nesta ordem: campo data_demissao do cadastro do colaborador (integração),
 * desligamento registrado pelo RH no painel (data/desligamentos.json) ou a data informada
 * num agendamento de homologação sindical.
 */
class DesligamentoService
{
    public const LINK_HOMOLOGACAO = 'https://abrir.link/qjoqu';
    public const LINK_DOCUMENTOS = 'https://abrir.link/WNujP';
    private const TIPOS = ['homologacao' => 'homologacao_agendada_em', 'documentos' => 'documentos_agendados_em'];

    private string $arquivo;

    public function __construct(private ColaboradorService $colaboradores)
    {
        date_default_timezone_set('America/Sao_Paulo');
        $this->arquivo = dirname(__DIR__) . '/data/desligamentos.json';
    }

    public static function links(): array
    {
        return [
            'homologacao' => getenv('VB_LINK_HOMOLOGACAO') ?: self::LINK_HOMOLOGACAO,
            'documentos' => getenv('VB_LINK_DOCUMENTOS') ?: self::LINK_DOCUMENTOS,
        ];
    }

    // ---------------------------------------------------------------- colaborador

    /** O que o portal do colaborador precisa para decidir se mostra o aviso. */
    public function paraColaborador(array $colab): array
    {
        $data = $this->dataDemissao($colab);
        $reg = $this->registro($colab['matricula']) ?? [];
        $homologacaoRh = $this->homologacaoDoRh($colab['matricula']);
        $homologacaoOk = !empty($reg['homologacao_agendada_em']) || $homologacaoRh !== null;
        $documentosOk = !empty($reg['documentos_agendados_em']);
        $chegou = $data !== null && $data <= date('Y-m-d');

        return [
            'mostrar' => $chegou && !($homologacaoOk && $documentosOk),
            'data_demissao' => $data,
            'empresa' => EmpresaService::doColaborador($colab),
            'links' => self::links(),
            'homologacao' => [
                'agendado' => $homologacaoOk,
                'agendado_em' => $reg['homologacao_agendada_em'] ?? null,
                // Se o RH já marcou a homologação no sindicato pelo painel, mostra os dados dela
                'pelo_rh' => $homologacaoRh ? ['data_hora' => $homologacaoRh['data_hora'], 'sindicato' => $homologacaoRh['sindicato'], 'local' => $homologacaoRh['local'] ?? ''] : null,
            ],
            'documentos' => ['agendado' => $documentosOk, 'agendado_em' => $reg['documentos_agendados_em'] ?? null],
            'tem_guia' => $this->caminhoDoGuia($colab) !== null,
        ];
    }

    /** O colaborador confirma que agendou pelo link (homologacao | documentos). Desfazer = false. */
    public function marcarAgendado(array $colab, string $tipo, bool $agendado = true): array
    {
        if (!isset(self::TIPOS[$tipo])) return ['erro' => 'Tipo inválido.', 'status' => 422];
        if ($this->dataDemissao($colab) === null) return ['erro' => 'Nenhum desligamento registrado.', 'status' => 404];
        $campo = self::TIPOS[$tipo];
        $this->transacao(function (array &$lista) use ($colab, $campo, $agendado) {
            $i = $this->indice($lista, $colab['matricula']);
            if ($i === null) {
                $lista[] = ['matricula' => $colab['matricula'], 'data_demissao' => null, 'origem' => 'colaborador'];
                $i = count($lista) - 1;
            }
            $lista[$i][$campo] = $agendado ? date('Y-m-d H:i:s') : null;
        });
        return ['sucesso' => true, 'desligamento' => $this->paraColaborador($colab)];
    }

    public function caminhoDoGuia(array $colab): ?string
    {
        $dir = dirname(__DIR__) . '/guias/';
        $empresa = EmpresaService::doColaborador($colab);
        foreach ([$empresa, 'Vila Boa'] as $nome) {
            $arq = $dir . 'Guia de Desligamento - ' . preg_replace('/[\/\\\\:*?"<>|]/', '', $nome) . '.pdf';
            if ($nome !== '' && is_file($arq)) return $arq;
        }
        return null;
    }

    // ---------------------------------------------------------------- RH

    /** Colaboradores com data de demissão (de qualquer origem) e a situação dos agendamentos. */
    public function listar(): array
    {
        $lista = [];
        foreach ($this->colaboradores->getAll() as $c) {
            $data = $this->dataDemissao($c);
            if ($data === null) continue;
            $info = $this->paraColaborador($c);
            $reg = $this->registro($c['matricula']) ?? [];
            $lista[] = [
                'matricula' => $c['matricula'],
                'nome' => $c['nome'],
                'cargo' => $c['cargo'] ?? '',
                'empresa' => $info['empresa'],
                'data_demissao' => $data,
                'origem' => $this->origemDaData($c),
                'aviso_ativo' => $info['mostrar'],
                'homologacao' => $info['homologacao'],
                'documentos' => $info['documentos'],
                'registrado_por' => $reg['registrado_por'] ?? null,
            ];
        }
        usort($lista, fn($a, $b) => strcmp($b['data_demissao'], $a['data_demissao']));
        return $lista;
    }

    public function registrar(array $dados, string $autor): array
    {
        $matricula = (string)($dados['matricula'] ?? '');
        $colab = null;
        foreach ($this->colaboradores->getAll() as $c) {
            if ($c['matricula'] === $matricula) { $colab = $c; break; }
        }
        if (!$colab) return ['erro' => 'Escolha o colaborador.', 'status' => 422];
        $data = trim((string)($dados['data_demissao'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || !strtotime($data)) return ['erro' => 'Informe a data da demissão.', 'status' => 422];

        $this->transacao(function (array &$lista) use ($colab, $data, $autor) {
            $i = $this->indice($lista, $colab['matricula']);
            $base = $i === null ? ['matricula' => $colab['matricula'], 'homologacao_agendada_em' => null, 'documentos_agendados_em' => null] : $lista[$i];
            $novo = ['data_demissao' => $data, 'origem' => 'rh', 'registrado_por' => $autor, 'registrado_em' => date('Y-m-d H:i:s')] + $base;
            if ($i === null) $lista[] = $novo; else $lista[$i] = $novo;
        });
        return ['sucesso' => true];
    }

    /** acao: remover (tira o desligamento registrado pelo RH) | reabrir (zera os "Já agendei" e o aviso volta). */
    public function acaoRh(string $matricula, string $acao): array
    {
        if (!in_array($acao, ['remover', 'reabrir'], true)) return ['erro' => 'Ação inválida.', 'status' => 422];
        $achou = false;
        $this->transacao(function (array &$lista) use ($matricula, $acao, &$achou) {
            $i = $this->indice($lista, $matricula);
            if ($i === null) return;
            $achou = true;
            if ($acao === 'remover') {
                array_splice($lista, $i, 1);
            } else {
                $lista[$i]['homologacao_agendada_em'] = null;
                $lista[$i]['documentos_agendados_em'] = null;
            }
        });
        return $achou ? ['sucesso' => true] : ['erro' => 'Nada registrado no painel para este colaborador.', 'status' => 404];
    }

    // ---------------------------------------------------------------- internos

    public function dataDemissao(array $colab): ?string
    {
        foreach ([$colab['data_demissao'] ?? null, $this->registro($colab['matricula'])['data_demissao'] ?? null] as $d) {
            if (is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}/', $d)) return substr($d, 0, 10);
        }
        foreach ($this->homologacoes() as $h) {
            if ($h['matricula'] === $colab['matricula'] && $h['status'] !== 'cancelado' && !empty($h['data_demissao'])) return $h['data_demissao'];
        }
        return null;
    }

    private function origemDaData(array $colab): string
    {
        if (!empty($colab['data_demissao'])) return 'cadastro';
        if (!empty($this->registro($colab['matricula'])['data_demissao'])) return 'rh';
        return 'homologacao';
    }

    private function homologacaoDoRh(string $matricula): ?array
    {
        foreach ($this->homologacoes() as $h) {
            if ($h['matricula'] === $matricula && in_array($h['status'], ['agendado', 'enviado', 'realizado'], true)) return $h;
        }
        return null;
    }

    private function homologacoes(): array
    {
        $arq = dirname(__DIR__) . '/data/homologacoes.json';
        if (!is_file($arq)) return [];
        $d = json_decode((string)file_get_contents($arq), true);
        return is_array($d) ? $d : [];
    }

    private function registro(string $matricula): ?array
    {
        $lista = $this->todos();
        $i = $this->indice($lista, $matricula);
        return $i === null ? null : $lista[$i];
    }

    private function indice(array $lista, string $matricula): ?int
    {
        foreach ($lista as $i => $r) {
            if (($r['matricula'] ?? '') === $matricula) return $i;
        }
        return null;
    }

    private function todos(): array
    {
        if (!is_file($this->arquivo)) return [];
        $d = json_decode((string)file_get_contents($this->arquivo), true);
        return is_array($d) ? array_values($d) : [];
    }

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
