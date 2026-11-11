<?php

namespace App\Controllers;

use App\Services\ColaboradorService;
use App\Services\HoleriteService;
use App\Services\EmpresaService;
use App\Services\EpiService;

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
        private CadernosController $cadernos,
        private ?AcademiaController $academia = null,
        private ?EpiService $epis = null,
        private ?ComunicadosController $comunicados = null
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
                        'competencia' => $h['competencia'],
                        'mes_nome' => $h['mes_nome'],
                        'ano' => $h['ano'],
                        'tipo' => $h['tipo_folha'] ?? 'Mensal',
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
                    'tipo' => $cad['tipo'] ?? 'politica',
                    'carga_horaria' => $cad['carga_horaria'] ?? '',
                    'prazo' => $cad['prazo'] ?? '',
                    'tags' => $cad['tags'] ?? [],
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

    /**
     * Base do painel narrativo de acompanhamento: o resumo de sempre + EPIs, Academia e Comunicados
     * por colaborador, com as datas necessárias para as séries por mês. Os filtros ficam no navegador.
     */
    public function indicadores(string $ano): array
    {
        $base = $this->resumo($ano);
        $todosEpis = $this->epis ? $this->epis->getAll() : [];
        $trilhasRh = $this->academia ? $this->academia->listarParaRh() : [];
        $certificados = [];

        foreach ($base['colaboradores'] as &$linha) {
            $mat = $linha['matricula'];

            $fichas = array_values(array_filter($todosEpis, fn($e) => ($e['matricula'] ?? '') === $mat));
            $linha['epis'] = [
                'total' => count($fichas),
                'assinados' => count(array_filter($fichas, fn($e) => ($e['status'] ?? '') !== 'pendente_assinatura')),
                'detalhe' => array_map(fn($e) => [
                    'id' => $e['id'],
                    'data_entrega' => $e['data_entrega'] ?? '',
                    'motivo' => $e['motivo'] ?? '',
                    'itens' => count($e['itens'] ?? []),
                    'assinado_em' => ($e['status'] ?? '') !== 'pendente_assinatura' ? ($e['assinatura']['data_hora'] ?? ($e['data_entrega'] ?? '')) : null
                ], $fichas)
            ];

            $trilhas = [];
            if ($this->academia) {
                $colab = null;
                foreach ($this->colaboradores->getAll() as $c) {
                    if ($c['matricula'] === $mat) { $colab = $c; break; }
                }
                foreach ($colab ? $this->academia->listarParaColaborador($colab) : [] as $t) {
                    $trilhas[] = [
                        'id' => $t['id'],
                        'titulo' => $t['titulo'],
                        'obrigatoria' => !empty($t['obrigatoria']),
                        'total' => $t['total'],
                        'concluidos' => $t['concluidos'],
                        'completa' => $t['completa'],
                        'certificado_em' => $t['certificado']['emitido_em'] ?? null
                    ];
                }
                foreach ($this->academia->certificadosDoColaborador($mat) as $cert) {
                    $certificados[] = [
                        'codigo' => $cert['codigo'] ?? '',
                        'matricula' => $mat,
                        'trilha_id' => $cert['trilha_id'] ?? '',
                        'trilha' => $cert['trilha'] ?? '',
                        'emitido_em' => $cert['emitido_em'] ?? ''
                    ];
                }
            }
            $linha['academia'] = ['trilhas' => $trilhas];
        }
        unset($linha);

        $base['trilhas'] = array_map(fn($t) => [
            'id' => $t['id'],
            'titulo' => $t['titulo'],
            'obrigatoria' => !empty($t['obrigatoria']),
            'publicada' => $t['publicada'] ?? true,
            'capa' => $t['capa'] ?? null,
            'funcoes' => $t['funcoes'] ?? [],
            'empresa' => $t['empresa'] ?? 'todas'
        ], $trilhasRh);
        $base['certificados'] = $certificados;
        $base['comunicados'] = $this->comunicados ? array_map(fn($c) => [
            'id' => $c['id'],
            'titulo' => $c['titulo'],
            'categoria' => $c['categoria'] ?? 'Aviso',
            'prioridade' => $c['prioridade'] ?? 'normal',
            'fixado' => !empty($c['fixado']),
            'publicado_em' => $c['publicado_em'] ?? '',
            'validade' => $c['validade'] ?? '',
            'vencido' => !empty($c['vencido']),
            'situacao' => $c['situacao'] ?? 'publicado',
            'publicar_em' => $c['publicar_em'] ?? ($c['publicado_em'] ?? ''),
            'empresa' => $c['empresa'] ?? 'todas'
        ], $this->comunicados->listarParaRh()) : [];
        $base['ano'] = (int)$ano;
        $base['hoje'] = date('Y-m-d');
        return $base;
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
     * Colunas opcionais do relatório completo: entrada3;saida3;ad_not;atraso;falta;abono;feriado (por dia) e
     * funcao;admissao;horario ("048|07:00-12:00 ...");saldo_anterior;evento ("0003|Adicional Noturno|001:30", vários separados por #);
     * ponte ("dd/mm/aaaa|início|fim|-08:00", vários separados por #) (por colaborador/mês, em qualquer linha).
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
            $grupos[$chave] ??= ['matricula' => $matricula, 'linhas' => [], 'link' => '', 'saldo' => '', 'extras' => ['dias' => [], 'eventos' => [], 'pontes' => []]];
            // Colunas opcionais do relatório de ponto completo
            $grupos[$chave]['extras']['dias'][$valor($cols, 'data')] = [
                'e3' => $valor($cols, 'entrada3'), 's3' => $valor($cols, 'saida3'), 'ad_not' => $valor($cols, 'ad_not'),
                'atraso' => $valor($cols, 'atraso'), 'falta' => $valor($cols, 'falta'), 'abono' => $valor($cols, 'abono'), 'feriado' => $valor($cols, 'feriado')
            ];
            foreach (['funcao', 'admissao', 'horario', 'saldo_anterior'] as $campo) {
                $grupos[$chave]['extras'][$campo] = ($grupos[$chave]['extras'][$campo] ?? '') ?: $valor($cols, $campo);
            }
            // evento: "0003|Adicional Noturno|001:30" · ponte: "dd/mm/aaaa|dd/mm/aaaa|dd/mm/aaaa|-08:00" (vários separados por #)
            foreach (array_filter(explode('#', $valor($cols, 'evento'))) as $ev) {
                [$cod, $desc, $hrs] = array_pad(explode('|', $ev), 3, '');
                $grupos[$chave]['extras']['eventos'][] = ['codigo' => trim($cod), 'descricao' => trim($desc), 'horas' => trim($hrs)];
            }
            foreach (array_filter(explode('#', $valor($cols, 'ponte'))) as $pt) {
                [$dt, $ini, $fim, $hrs] = array_pad(explode('|', $pt), 4, '');
                $grupos[$chave]['extras']['pontes'][] = ['data' => trim($dt), 'inicio' => trim($ini), 'fim' => trim($fim), 'horas' => trim($hrs)];
            }
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
            $r = $this->importarPonto($g['matricula'], $g['saldo'], implode("\n", $g['linhas']), $g['link'], $g['extras']);
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
    public function importarPonto(string $matricula, string $saldoBanco, string $csv, string $linkAssinatura = '', array $extras = []): array
    {
        if (!$this->colaboradorExiste($matricula)) {
            return ['erro' => 'Matrícula não encontrada.'];
        }

        $diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        $meses = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $hora = fn($h) => preg_match('/^\d{1,2}:\d{2}$/', (string)$h) ? str_pad($h, 5, '0', STR_PAD_LEFT) : '--:--';
        $dias = [];
        $saldoMes = 0;
        $competencia = null;
        $he50 = $he100 = $adNot = 0;

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

            $ex = $extras['dias'][$cols[0]] ?? [];
            $marcacoes = array_map($hora, array_slice(array_pad($cols, 5, ''), 1, 4));
            $e3 = $hora($ex['e3'] ?? '');
            $s3 = $hora($ex['s3'] ?? '');
            $ocorrencia = $cols[5] ?? '';
            $dow = (int)date('w', strtotime("$ano-$mes-$d"));
            $feriado = $ex['feriado'] ?? '';
            $minutos = $this->minutosTrabalhados([...$marcacoes, $e3, $s3], [[0, 1], [2, 3], [4, 5]]);
            $trabalhou = $minutos > 0;
            $saldo = $trabalhou ? $minutos - 480 : 0;
            $saldoMes += $saldo;
            if ($trabalhou) {
                if ($dow === 0 || $feriado !== '') $he100 += $minutos;
                elseif ($saldo > 0) $he50 += $saldo;
            }
            $adNotDia = $ex['ad_not'] ?? '';
            if (preg_match('/^(\d+):(\d{2})$/', $adNotDia, $am)) $adNot += (int)$am[1] * 60 + (int)$am[2];

            $dias[] = [
                'data' => "$ano-$mes-$d",
                'dia_semana' => $diasSemana[$dow],
                'e1' => $marcacoes[0], 's1' => $marcacoes[1], 'e2' => $marcacoes[2], 's2' => $marcacoes[3], 'e3' => $e3, 's3' => $s3,
                'total' => $this->formatarMinutos($minutos),
                'saldo' => $trabalhou ? (($saldo >= 0 ? '+' : '-') . $this->formatarMinutos(abs($saldo))) : '00:00',
                'tipo' => $ocorrencia !== '' ? $ocorrencia : ($trabalhou ? 'Trabalhado' : 'Folga'),
                'exec' => $trabalhou ? $this->formatarMinutos($minutos) : '',
                'ad_not' => $adNotDia,
                'atraso' => ($ex['atraso'] ?? '') !== '' ? $ex['atraso'] : ($trabalhou && $dow >= 1 && $dow <= 5 && $saldo < 0 ? $this->formatarMinutos(-$saldo) : ''),
                'falta' => $ex['falta'] ?? '',
                'abono' => $ex['abono'] ?? '',
                'feriado' => $feriado
            ];
        }

        if (!$dias) {
            return ['erro' => 'Nenhuma marcação encontrada no arquivo.'];
        }

        usort($dias, fn($a, $b) => strcmp($a['data'], $b['data']));
        [$mes, $ano] = explode('/', $competencia);

        // Dias ponte: horas a incrementar (+) ou decrementar (-) no banco
        $iso = fn($br) => preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $br, $q) ? "$q[3]-$q[2]-$q[1]" : '';
        $pontes = [];
        foreach ($extras['pontes'] ?? [] as $pt) {
            $pontes[] = ['data' => $iso($pt['data']), 'inicio' => $iso($pt['inicio']), 'fim' => $iso($pt['fim']), 'horas' => $pt['horas']];
            $saldoMes += $this->paraMinutosComSinal($pt['horas']);
        }

        // Eventos do movimento: os informados pelo RH ou, na falta, os calculados
        $eventos = $extras['eventos'] ?? [];
        if (!$eventos) {
            if ($adNot) $eventos[] = ['codigo' => '0003', 'descricao' => 'Adicional Noturno', 'horas' => $this->formatarMinutos($adNot, 3)];
            if ($he50) $eventos[] = ['codigo' => '0277', 'descricao' => 'Hora Extra 50%', 'horas' => $this->formatarMinutos($he50, 3)];
            if ($he100) $eventos[] = ['codigo' => '0281', 'descricao' => 'Hora Extra 100%', 'horas' => $this->formatarMinutos($he100, 3)];
        }

        $horario = [];
        if (!empty($extras['horario'])) {
            [$cod, $desc] = array_pad(explode('|', $extras['horario'], 2), 2, '');
            $horario[] = ['codigo' => trim($cod), 'descricao' => trim($desc)];
        }

        $anterior = $this->paraMinutosComSinal($extras['saldo_anterior'] ?? '');
        $sinal = $saldoMes >= 0 ? '+' : '-';
        $saldoAtual = $saldoBanco !== '' ? $this->paraMinutosComSinal($saldoBanco) : $anterior + $saldoMes;
        $sinalAtual = $saldoAtual >= 0 ? '+' : '-';
        $comp = array_sum(array_map(fn($p) => min(0, $this->paraMinutosComSinal($p['horas'])), $pontes));

        $this->ponto->salvarEspelho($matricula, [
            'competencia' => $competencia,
            'mes_nome' => $meses[(int)$mes] . " / $ano",
            'funcao' => $extras['funcao'] ?? '',
            'admissao' => $extras['admissao'] ?? '',
            'banco_horas' => [
                'saldo_total' => $sinalAtual . $this->formatarHoras(abs($saldoAtual)),
                'saldo_positivo' => $saldoAtual >= 0,
                'horas_mes_atual' => $sinal . $this->formatarHoras(abs($saldoMes)),
                'horas_compensadas' => $this->formatarHoras(abs($comp)),
                'limite_compensacao' => "31/12/$ano",
                'saldo_anterior' => ($anterior >= 0 ? '+' : '-') . $this->formatarMinutos(abs($anterior), 3),
                'saldo_periodo' => [
                    'inicio' => $dias[0]['data'], 'fim' => end($dias)['data'],
                    'valor' => $sinal . $this->formatarMinutos(abs($saldoMes), 3),
                    'situacao' => $saldoMes >= 0 ? 'A PAGAR' : 'A COMPENSAR'
                ],
                'saldo_atual' => $sinalAtual . $this->formatarMinutos(abs($saldoAtual), 3)
            ],
            'dias' => $dias,
            'eventos' => $eventos,
            'pontes' => $pontes,
            'horario' => $horario,
            // Documento do espelho no Autentique, onde o colaborador assina
            'link_assinatura' => filter_var($linkAssinatura, FILTER_VALIDATE_URL) ? $linkAssinatura : null
        ]);

        return ['sucesso' => true, 'competencia' => $competencia, 'dias' => count($dias)];
    }

    /** "+04:10", "-002:30" ou "+04h 10m" -> minutos com sinal. */
    private function paraMinutosComSinal(string $v): int
    {
        if (!preg_match('/^([+-]?)\s*(\d+)\s*(?:h|:)\s*(\d{2})/', trim($v), $q)) return 0;
        return ($q[1] === '-' ? -1 : 1) * ((int)$q[2] * 60 + (int)$q[3]);
    }

    private function colaboradorExiste(string $matricula): bool
    {
        foreach ($this->colaboradores->getAll() as $c) {
            if ($c['matricula'] === $matricula) return true;
        }
        return false;
    }

    private function minutosTrabalhados(array $m, array $pares = [[0, 1], [2, 3]]): int
    {
        $min = fn($h) => $h === '--:--' ? null : ((int)substr($h, 0, 2) * 60 + (int)substr($h, 3, 2));
        $total = 0;
        foreach ($pares as [$e, $s]) {
            $ini = $min($m[$e]);
            $fim = $min($m[$s]);
            if ($ini !== null && $fim !== null && $fim > $ini) $total += $fim - $ini;
        }
        return $total;
    }

    private function formatarMinutos(int $min, int $largura = 2): string
    {
        return sprintf("%0{$largura}d:%02d", intdiv($min, 60), $min % 60);
    }

    private function formatarHoras(int $min): string
    {
        return sprintf('%02dh %02dm', intdiv($min, 60), $min % 60);
    }
}
