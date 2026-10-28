<?php

namespace App\Controllers;

use App\Services\ColaboradorService;
use App\Services\HoleriteService;
use App\Services\EmpresaService;

/**
 * Painel do RH: acompanhamento de holerites recebidos, espelhos de ponto assinados
 * e cadernos lidos por colaborador, além da importação de espelhos de ponto.
 * Acesso controlado pelas permissões do perfil (config/permissoes.php).
 */
class RhController
{
    public function __construct(
        private ColaboradorService $colaboradores,
        private HoleriteService $holerites,
        private PontoController $ponto,
        private CadernosController $cadernos
    ) {
    }

    public function resumo(string $ano): array
    {
        $cadernos = $this->cadernos->listar();
        $leituras = $this->cadernos->leiturasPorMatricula();

        $linhas = [];
        foreach ($this->colaboradores->getAll() as $c) {
            $hols = $this->holerites->getHolerites($c['matricula'], $ano);
            $recebidos = array_values(array_filter($hols, fn($h) => !empty($h['aceite']['confirmado'])));
            $pendentesHolerite = array_values(array_filter($hols, fn($h) => empty($h['aceite']['confirmado'])));

            $espelho = $this->ponto->getEspelho($c['matricula']);
            $lidos = $leituras[$c['matricula']] ?? [];

            $linhas[] = [
                'matricula' => $c['matricula'],
                'nome' => $c['nome'],
                'cargo' => $c['cargo'],
                'departamento' => $c['departamento'] ?? '',
                'empresa' => EmpresaService::doColaborador($c),
                'telefone' => $c['telefone_atual'] ?? '',
                'holerites' => [
                    'total' => count($hols),
                    'recebidos' => count($recebidos),
                    'pendentes' => array_map(fn($h) => [
                        'competencia' => $h['competencia'],
                        'descricao' => "{$h['mes_nome']} / {$h['ano']} · " . ($h['tipo_folha'] ?? 'Mensal')
                    ], $pendentesHolerite),
                    'detalhe' => array_map(fn($h) => [
                        'id' => $h['id'],
                        'descricao' => "{$h['mes_nome']} / {$h['ano']} · " . ($h['tipo_folha'] ?? 'Mensal'),
                        'recebido_em' => $h['aceite']['data_hora'] ?? null
                    ], $hols)
                ],
                'ponto' => $espelho ? [
                    'competencia' => $espelho['competencia'],
                    'mes_nome' => $espelho['mes_nome'],
                    'assinado_em' => $espelho['assinatura']['data_hora'] ?? null,
                    'competencias' => $this->ponto->competencias($c['matricula'])
                ] : null,
                'cadernos' => $this->resumoCadernos($c, $cadernos, $lidos)
            ];
        }

        return [
            'colaboradores' => $linhas,
            'cadernos' => array_map(function ($cad) use ($leituras) {
                $publico = array_filter($this->colaboradores->getAll(), fn($c) => $this->cadernos->visivelPara($c, $cad));
                $leram = array_filter($publico, fn($c) => isset($leituras[$c['matricula']][$cad['arquivo']]));
                return [
                    'arquivo' => $cad['arquivo'],
                    'titulo' => $cad['titulo'],
                    'atualizado_em' => $cad['atualizado_em'],
                    'paginas' => $cad['paginas'] ?? null,
                    'capa' => $cad['capa'] ?? null,
                    'categoria' => $cad['categoria'],
                    'obrigatorio' => $cad['obrigatorio'],
                    'publico' => $cad['publico'],
                    'valores' => $cad['valores'],
                    'empresa' => $cad['empresa'],
                    'total_publico' => count($publico),
                    'total_leram' => count($leram),
                    'leitores' => array_values(array_map(fn($c) => [
                        'matricula' => $c['matricula'],
                        'nome' => $c['nome'],
                        'cargo' => $c['cargo'],
                        'telefone' => $c['telefone_atual'] ?? '',
                        'lido_em' => $leituras[$c['matricula']][$cad['arquivo']]['data_hora'] ?? null
                    ], $publico))
                ];
            }, $cadernos),
            'categorias' => $this->cadernos->categorias(),
            'empresas' => EmpresaService::listar(),
            'empresas_logos' => array_column(EmpresaService::listarComLogo(), 'logo', 'nome'),
            'departamentos' => array_values(array_unique(array_filter(array_map(fn($c) => $c['departamento'] ?? '', $this->colaboradores->getAll()))))
        ];
    }

    /** Leituras do colaborador separadas em obrigatórias e opcionais, só entre os cadernos do seu público. */
    private function resumoCadernos(array $colaborador, array $cadernos, array $lidos): array
    {
        $doPublico = array_values(array_filter($cadernos, fn($cad) => $this->cadernos->visivelPara($colaborador, $cad)));
        $obrigatorios = array_filter($doPublico, fn($cad) => $cad['obrigatorio']);
        $opcionais = array_filter($doPublico, fn($cad) => !$cad['obrigatorio']);
        $foiLido = fn($cad) => isset($lidos[$cad['arquivo']]);

        return [
            'obrigatorios_total' => count($obrigatorios),
            'obrigatorios_lidos' => count(array_filter($obrigatorios, $foiLido)),
            'opcionais_total' => count($opcionais),
            'opcionais_lidos' => count(array_filter($opcionais, $foiLido)),
            'detalhe' => array_map(fn($cad) => [
                'arquivo' => $cad['arquivo'],
                'titulo' => $cad['titulo'],
                'categoria' => $cad['categoria'],
                'obrigatorio' => $cad['obrigatorio'],
                'lido_em' => $lidos[$cad['arquivo']]['data_hora'] ?? null
            ], $doPublico)
        ];
    }

    /**
     * Importa uma planilha com os espelhos de vários colaboradores. Colunas (cabeçalho obrigatório):
     * matricula;data;entrada1;saida1;entrada2;saida2;ocorrencia;link_assinatura;saldo_banco
     * Cada colaborador + mês vira um espelho (um documento no Autentique). O link de assinatura
     * e o saldo do banco podem vir em qualquer linha do grupo (normalmente na primeira).
     */
    public function importarPlanilha(string $csv): array
    {
        $linhas = preg_split('/\r\n|\r|\n/', trim($csv));
        $separador = str_contains($linhas[0] ?? '', ';') ? ';' : ',';
        $cabecalho = array_map(fn($c) => mb_strtolower(trim($c, " \t\xEF\xBB\xBF")), str_getcsv(array_shift($linhas) ?? '', $separador));
        $col = array_flip($cabecalho);
        foreach (['matricula', 'data'] as $obrigatoria) {
            if (!isset($col[$obrigatoria])) {
                return ['erro' => "A planilha precisa da coluna \"$obrigatoria\". Baixe o modelo."];
            }
        }
        $valor = fn(array $cols, string $nome) => isset($col[$nome]) ? trim($cols[$col[$nome]] ?? '') : '';

        // Agrupa por colaborador + mês
        $grupos = [];
        foreach ($linhas as $n => $linha) {
            if (trim($linha) === '') continue;
            $cols = str_getcsv($linha, $separador);
            $matricula = $valor($cols, 'matricula');
            if (!preg_match('#^\d{2}/(\d{2})/(\d{4})$#', $valor($cols, 'data'), $m)) {
                return ['erro' => 'Linha ' . ($n + 2) . ': data inválida (use dd/mm/aaaa).'];
            }
            $chave = "$matricula|{$m[1]}/{$m[2]}";
            $grupos[$chave] ??= ['matricula' => $matricula, 'linhas' => [], 'link' => '', 'saldo' => ''];
            $grupos[$chave]['linhas'][] = implode(';', [
                $valor($cols, 'data'), $valor($cols, 'entrada1'), $valor($cols, 'saida1'),
                $valor($cols, 'entrada2'), $valor($cols, 'saida2'), $valor($cols, 'ocorrencia')
            ]);
            $grupos[$chave]['link'] = $grupos[$chave]['link'] ?: $valor($cols, 'link_assinatura');
            $grupos[$chave]['saldo'] = $grupos[$chave]['saldo'] ?: $valor($cols, 'saldo_banco');
        }
        if (!$grupos) {
            return ['erro' => 'Nenhuma marcação encontrada na planilha.'];
        }

        $importados = [];
        $erros = [];
        foreach ($grupos as $g) {
            $r = $this->importarPonto($g['matricula'], $g['saldo'], implode("\n", $g['linhas']), $g['link']);
            if (!empty($r['erro'])) $erros[] = "{$g['matricula']}: {$r['erro']}";
            else $importados[] = ['matricula' => $g['matricula'], 'competencia' => $r['competencia'], 'dias' => $r['dias'], 'com_link' => $g['link'] !== ''];
        }
        return ['sucesso' => !$erros, 'importados' => $importados, 'erros' => $erros];
    }

    /**
     * Importa o espelho de um colaborador a partir de um CSV com as colunas:
     * data;entrada1;saida1;entrada2;saida2;ocorrencia   (data em dd/mm/aaaa, horas em hh:mm)
     * A jornada de referência para o saldo diário é de 8 horas.
     */
    public function importarPonto(string $matricula, string $saldoBanco, string $csv, string $linkAssinatura = ''): array
    {
        if (!$this->colaboradorExiste($matricula)) {
            return ['erro' => 'Matrícula não encontrada.'];
        }

        $diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        $meses = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $dias = [];
        $saldoMes = 0;
        $competencia = null;

        foreach (preg_split('/\r\n|\r|\n/', trim($csv)) as $n => $linha) {
            $cols = array_map('trim', str_getcsv($linha, str_contains($linha, ';') ? ';' : ','));
            if (!preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $cols[0] ?? '', $m)) {
                if ($n === 0) continue; // cabeçalho
                return ['erro' => 'Linha ' . ($n + 1) . ': data inválida (use dd/mm/aaaa).'];
            }
            [$_, $d, $mes, $ano] = $m;
            $competencia ??= "$mes/$ano";
            if ("$mes/$ano" !== $competencia) {
                return ['erro' => 'Linha ' . ($n + 1) . ': todas as datas devem ser do mesmo mês.'];
            }

            $marcacoes = array_map(fn($h) => preg_match('/^\d{1,2}:\d{2}$/', $h) ? str_pad($h, 5, '0', STR_PAD_LEFT) : '--:--', array_slice(array_pad($cols, 5, ''), 1, 4));
            $ocorrencia = $cols[5] ?? '';
            $minutos = $this->minutosTrabalhados($marcacoes);
            $trabalhou = $minutos > 0;
            $saldo = $trabalhou ? $minutos - 480 : 0;
            $saldoMes += $saldo;

            $dias[] = [
                'data' => "$ano-$mes-$d",
                'dia_semana' => $diasSemana[(int)date('w', strtotime("$ano-$mes-$d"))],
                'e1' => $marcacoes[0], 's1' => $marcacoes[1], 'e2' => $marcacoes[2], 's2' => $marcacoes[3],
                'total' => $this->formatarMinutos($minutos),
                'saldo' => $trabalhou ? (($saldo >= 0 ? '+' : '-') . $this->formatarMinutos(abs($saldo))) : '00:00',
                'tipo' => $ocorrencia !== '' ? $ocorrencia : ($trabalhou ? 'Trabalhado' : 'Folga')
            ];
        }

        if (!$dias) {
            return ['erro' => 'Nenhuma marcação encontrada no arquivo.'];
        }

        usort($dias, fn($a, $b) => strcmp($a['data'], $b['data']));
        [$mes, $ano] = explode('/', $competencia);
        $sinal = $saldoMes >= 0 ? '+' : '-';

        $this->ponto->salvarEspelho($matricula, [
            'competencia' => $competencia,
            'mes_nome' => $meses[(int)$mes] . " / $ano",
            'banco_horas' => [
                'saldo_total' => $saldoBanco !== '' ? $saldoBanco : $sinal . $this->formatarHoras(abs($saldoMes)),
                'saldo_positivo' => $saldoBanco !== '' ? !str_starts_with($saldoBanco, '-') : $saldoMes >= 0,
                'horas_mes_atual' => $sinal . $this->formatarHoras(abs($saldoMes)),
                'horas_compensadas' => '00h 00m',
                'limite_compensacao' => "31/12/$ano"
            ],
            'dias' => $dias,
            // Documento do espelho no Autentique, onde o colaborador assina
            'link_assinatura' => filter_var($linkAssinatura, FILTER_VALIDATE_URL) ? $linkAssinatura : null
        ]);

        return ['sucesso' => true, 'competencia' => $competencia, 'dias' => count($dias)];
    }

    private function colaboradorExiste(string $matricula): bool
    {
        foreach ($this->colaboradores->getAll() as $c) {
            if ($c['matricula'] === $matricula) return true;
        }
        return false;
    }

    private function minutosTrabalhados(array $m): int
    {
        $min = fn($h) => $h === '--:--' ? null : ((int)substr($h, 0, 2) * 60 + (int)substr($h, 3, 2));
        $total = 0;
        foreach ([[0, 1], [2, 3]] as [$e, $s]) {
            $ini = $min($m[$e]);
            $fim = $min($m[$s]);
            if ($ini !== null && $fim !== null && $fim > $ini) $total += $fim - $ini;
        }
        return $total;
    }

    private function formatarMinutos(int $min): string
    {
        return sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
    }

    private function formatarHoras(int $min): string
    {
        return sprintf('%02dh %02dm', intdiv($min, 60), $min % 60);
    }
}
