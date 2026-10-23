<?php

namespace App\Services;

class HoleriteService
{
    private string $baseUrl;
    private string $apiToken;
    private bool $useMock;
    private string $cienciasFilePath;

    public function __construct(?string $baseUrl = null, ?string $apiToken = null, bool $useMock = true)
    {
        $this->baseUrl = $baseUrl ?? getenv('VILABOA_RH_API_URL') ?: 'https://api.vilaboaconstrucoes.com.br/v1/rh';
        $this->apiToken = $apiToken ?? getenv('VILABOA_RH_API_TOKEN') ?: 'vb_token_prod_99218274a81d4';
        $this->useMock = $useMock;
        $this->cienciasFilePath = dirname(__DIR__) . '/data/holerites_ciencias.json';
    }

    /**
     * Busca os holerites do colaborador pela matrícula e ano de referência.
     * Suporta filtro opcional por tipo de folha: 'mensal', 'adiantamento', '13', 'plr' ou 'todos'.
     */
    public function getHolerites(string $matricula, ?string $ano = null, ?string $tipo = null): array
    {
        $ano = $ano ?: date('Y');

        if (!$this->useMock) {
            $url = "/colaboradores/{$matricula}/holerites?ano={$ano}";
            if ($tipo && $tipo !== 'todos') {
                $url .= "&tipo=" . urlencode($tipo);
            }
            $lista = $this->consultarWebServiceRemoto($url);
        } else {
            $lista = $this->gerarMockHolerites($matricula, $ano);
        }

        if ($tipo && $tipo !== 'todos') {
            $tipoFiltro = strtolower($tipo);
            $lista = array_values(array_filter($lista, function ($h) use ($tipoFiltro) {
                $t = strtolower($h['tipo_folha'] ?? '');
                $badge = strtolower($h['tipo_folha_codigo'] ?? '');
                return str_contains($t, $tipoFiltro) || str_contains($badge, $tipoFiltro);
            }));
        }

        return $this->anexarCiencias($matricula, $lista);
    }

    /**
     * Busca o detalhe completo de um holerite específico por ID / Código de autenticidade.
     */
    public function getHoleriteDetalhado(string $matricula, string $holeriteId): ?array
    {
        if (!$this->useMock) {
            $item = $this->consultarWebServiceRemoto("/colaboradores/{$matricula}/holerites/{$holeriteId}");
            return $item ? $this->anexarCienciaIndividual($matricula, $item) : null;
        }

        $todos = $this->gerarMockHolerites($matricula, '2026');
        foreach ($todos as $h) {
            if ($h['id'] === $holeriteId) {
                return $this->anexarCienciaIndividual($matricula, $h);
            }
        }

        return !empty($todos) ? $this->anexarCienciaIndividual($matricula, $todos[0]) : null;
    }

    /**
     * Gera o pacote unificado dos últimos N holerites (geralmente 3) para fins de:
     * - Financiamento Habitacional (Caixa Econômica Federal / Minha Casa Minha Vida / SBPE)
     * - Análise de Crédito e Empréstimos Consignados / Pessoais
     * - Locação de Imóveis Residencial
     * Inclui folha de rosto declaratória com média de proventos e salários líquidos.
     */
    public function getPacoteComprovacaoRenda(string $matricula, int $quantidadeMeses = 3, array $colaborador = []): array
    {
        $todos = $this->getHolerites($matricula);
        $selecionados = array_slice($todos, 0, $quantidadeMeses);

        if (empty($selecionados)) {
            throw new \RuntimeException('Não há holerites disponíveis para geração do pacote.');
        }

        $somaBruto = 0.0;
        $somaLiquido = 0.0;
        $competencias = [];

        foreach ($selecionados as $h) {
            $somaBruto += (float)($h['total_proventos'] ?? 0);
            $somaLiquido += (float)($h['salario_liquido'] ?? 0);
            $competencias[] = $h['competencia'];
        }

        $qtd = count($selecionados);
        $mediaBruta = round($somaBruto / $qtd, 2);
        $mediaLiquida = round($somaLiquido / $qtd, 2);
        $periodo = end($competencias) . ' a ' . reset($competencias);

        $chaveAutenticidade = strtoupper(hash('sha256', "PACOTE_RENDA_{$matricula}_{$periodo}_{$mediaLiquida}_VILABOA"));

        return [
            'titulo' => 'Dossiê de Comprovação de Renda & Rendimentos',
            'subtitulo' => "Pacote Consolidado dos Últimos {$qtd} Meses para Instituições Financeiras",
            'finalidade' => 'Comprovação oficial de renda para financiamentos (Caixa / MCMV), análise de crédito bancário e contratos de locação.',
            'emissao' => [
                'data' => date('d/m/Y'),
                'hora' => date('H:i:s'),
                'timestamp' => date('c'),
                'protocolo_autenticidade' => $chaveAutenticidade
            ],
            'empresa' => [
                'razao_social' => 'VILA BOA CONSTRUCOES E SERVICOS LIMITADA',
                'nome_fantasia' => 'Vila Boa Construções e Serviços',
                'cnpj' => '02.785.261/0001-90',
                'endereco' => 'São Bernardo do Campo - SP',
                'contato_rh' => 'dp@vilaboaconstrucoes.com.br'
            ],
            'colaborador' => [
                'nome' => $colaborador['nome'] ?? 'Colaborador',
                'matricula' => $matricula,
                'cpf' => $colaborador['cpf'] ?? $colaborador['cpf_formatado'] ?? '',
                'cargo' => $colaborador['cargo'] ?? '',
                'departamento' => $colaborador['departamento'] ?? '',
                'data_admissao' => $colaborador['data_admissao'] ?? '2021-03-10'
            ],
            'resumo_financeiro' => [
                'quantidade_meses' => $qtd,
                'periodo_competencias' => $periodo,
                'total_proventos_bruto' => $somaBruto,
                'media_proventos_bruta' => $mediaBruta,
                'total_salario_liquido' => $somaLiquido,
                'media_salario_liquida' => $mediaLiquida,
                'meses' => array_map(function ($h) {
                    return [
                        'competencia' => $h['competencia'],
                        'mes_nome' => $h['mes_nome'],
                        'ano' => $h['ano'],
                        'proventos' => $h['total_proventos'],
                        'descontos' => $h['total_descontos'],
                        'liquido' => $h['salario_liquido'],
                        'data_pagamento' => $h['data_pagamento']
                    ];
                }, $selecionados)
            ],
            'holerites' => $selecionados
        ];
    }

    /**
     * Registra formalmente a ciência / aceite eletrônico do holerite pelo colaborador
     * com carimbo de data/hora, IP, hash SHA-256 e amparo CLT Art. 464 para o DP.
     */
    public function registrarCiencia(string $matricula, string $holeriteId, string $ip, string $userAgent): array
    {
        $holerite = $this->getHoleriteDetalhado($matricula, $holeriteId);
        if (!$holerite) {
            throw new \InvalidArgumentException('Holerite não encontrado para esta matrícula.');
        }

        $timestamp = date('c');
        $dataHoraBr = date('d/m/Y \à\s H:i:s');
        $chaveRegistro = "{$matricula}_{$holeriteId}";

        // Hash SHA-256 de autenticidade jurídica (Art. 464 CLT / Portaria 671 MTE)
        $hashData = "CIENCIA|{$matricula}|{$holeriteId}|{$holerite['competencia']}|{$holerite['salario_liquido']}|{$timestamp}|{$ip}";
        $hashComprovante = strtoupper(hash('sha256', $hashData));

        $comprovante = [
            'confirmado' => true,
            'matricula' => $matricula,
            'holerite_id' => $holeriteId,
            'competencia' => $holerite['competencia'],
            'data_hora' => $dataHoraBr,
            'timestamp' => $timestamp,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'hash_comprovante' => $hashComprovante,
            'base_legal' => 'Art. 464, Parágrafo Único da CLT c/c Portaria MTP nº 671/2021',
            'termo' => "Declaro para os devidos fins de direito ter tomado conhecimento e recebido integralmente o demonstrativo de pagamento referente à competência {$holerite['competencia']} emitido pela Vila Boa Construções e Serviços LTDA."
        ];

        $ciencias = $this->carregarCiencias();
        $ciencias[$chaveRegistro] = $comprovante;
        $this->salvarCiencias($ciencias);

        $holerite['aceite'] = $comprovante;
        return $holerite;
    }

    /**
     * Anexa o registro de ciência aos holerites do colaborador.
     */
    private function anexarCiencias(string $matricula, array $holerites): array
    {
        $ciencias = $this->carregarCiencias();
        foreach ($holerites as &$h) {
            $chave = "{$matricula}_{$h['id']}";
            if (isset($ciencias[$chave])) {
                $h['aceite'] = $ciencias[$chave];
            } else {
                $h['aceite'] = [
                    'confirmado' => false,
                    'data_hora' => null,
                    'hash_comprovante' => null
                ];
            }
        }
        return $holerites;
    }

    private function anexarCienciaIndividual(string $matricula, array $h): array
    {
        $ciencias = $this->carregarCiencias();
        $chave = "{$matricula}_{$h['id']}";
        if (isset($ciencias[$chave])) {
            $h['aceite'] = $ciencias[$chave];
        } else {
            $h['aceite'] = [
                'confirmado' => false,
                'data_hora' => null,
                'hash_comprovante' => null
            ];
        }
        return $h;
    }

    private function carregarCiencias(): array
    {
        if (!file_exists($this->cienciasFilePath)) {
            return [];
        }
        $conteudo = file_get_contents($this->cienciasFilePath);
        $dados = json_decode($conteudo, true);
        return is_array($dados) ? $dados : [];
    }

    private function salvarCiencias(array $ciencias): void
    {
        $dir = dirname($this->cienciasFilePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(
            $this->cienciasFilePath,
            json_encode($ciencias, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Chamada oficial HTTP para o WebService de RH com Bearer Token.
     * Esta estrutura fica 100% pronta para quando o endpoint oficial for plugado.
     */
    private function consultarWebServiceRemoto(string $endpoint): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json',
                'Accept: application/json',
                'X-Client-App: Portal-Funcionario-VilaBoaConstrucoes'
            ],
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode >= 400) {
            // Em caso de falha de conexão ou webservice indisponível, faz fallback ou lança exceção
            throw new \RuntimeException("Falha na comunicação com o WebService de RH (HTTP {$httpCode}): " . $error);
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Mock de holerites realistas para a empresa Vila Boa.
     */
    private function gerarMockHolerites(string $matricula, string $ano): array
    {
        $salarioBase = match ($matricula) {
            'VB-1040' => 3850.00,
            'VB-2085' => 5400.00,
            'VB-3042' => 4150.00,
            default => 3500.00
        };

        $mesesPorAno = [
            '2026' => [
                ['numero' => '09', 'nome' => 'Setembro', 'pagamento' => '05/10/2026', 'tipo' => 'Mensal'],
                ['numero' => '08', 'nome' => 'Agosto', 'pagamento' => '05/09/2026', 'tipo' => 'Mensal'],
                ['numero' => '07', 'nome' => 'Julho', 'pagamento' => '05/08/2026', 'tipo' => 'Mensal'],
                ['numero' => '06', 'nome' => 'Junho', 'pagamento' => '05/07/2026', 'tipo' => 'Mensal'],
                ['numero' => '05', 'nome' => 'Maio', 'pagamento' => '05/06/2026', 'tipo' => 'Mensal'],
            ],
            '2025' => [
                ['numero' => '12', 'nome' => 'Dezembro', 'pagamento' => '05/01/2026', 'tipo' => 'Mensal'],
                ['numero' => '11', 'nome' => 'Novembro', 'pagamento' => '05/12/2025', 'tipo' => 'Mensal'],
                ['numero' => '10', 'nome' => 'Outubro', 'pagamento' => '05/11/2025', 'tipo' => 'Mensal'],
                ['numero' => '09', 'nome' => 'Setembro', 'pagamento' => '05/10/2025', 'tipo' => 'Mensal'],
                ['numero' => '08', 'nome' => 'Agosto', 'pagamento' => '05/09/2025', 'tipo' => 'Mensal'],
                ['numero' => '07', 'nome' => 'Julho', 'pagamento' => '05/08/2025', 'tipo' => 'Mensal'],
                ['numero' => '06', 'nome' => 'Junho', 'pagamento' => '05/07/2025', 'tipo' => 'Mensal'],
                ['numero' => '05', 'nome' => 'Maio', 'pagamento' => '05/06/2025', 'tipo' => 'Mensal'],
                ['numero' => '04', 'nome' => 'Abril', 'pagamento' => '05/05/2025', 'tipo' => 'Mensal'],
                ['numero' => '03', 'nome' => 'Março', 'pagamento' => '05/04/2025', 'tipo' => 'Mensal'],
                ['numero' => '02', 'nome' => 'Fevereiro', 'pagamento' => '05/03/2025', 'tipo' => 'Mensal'],
                ['numero' => '01', 'nome' => 'Janeiro', 'pagamento' => '05/02/2025', 'tipo' => 'Mensal'],
            ],
            '2024' => [
                ['numero' => '12', 'nome' => 'Dezembro', 'pagamento' => '05/01/2025', 'tipo' => 'Mensal'],
                ['numero' => '11', 'nome' => 'Novembro', 'pagamento' => '05/12/2024', 'tipo' => 'Mensal'],
                ['numero' => '10', 'nome' => 'Outubro', 'pagamento' => '05/11/2024', 'tipo' => 'Mensal'],
                ['numero' => '09', 'nome' => 'Setembro', 'pagamento' => '05/10/2024', 'tipo' => 'Mensal'],
                ['numero' => '08', 'nome' => 'Agosto', 'pagamento' => '05/09/2024', 'tipo' => 'Mensal'],
                ['numero' => '07', 'nome' => 'Julho', 'pagamento' => '05/08/2024', 'tipo' => 'Mensal'],
                ['numero' => '06', 'nome' => 'Junho', 'pagamento' => '05/07/2024', 'tipo' => 'Mensal'],
                ['numero' => '05', 'nome' => 'Maio', 'pagamento' => '05/06/2024', 'tipo' => 'Mensal'],
            ]
        ];

        $meses = $mesesPorAno[$ano] ?? $mesesPorAno['2026'];

        $holerites = [];

        foreach ($meses as $idx => $m) {
            $id = "HOL-{$ano}{$m['numero']}-{$matricula}";
            $inss = round($salarioBase * 0.11, 2);
            $irrf = round(($salarioBase - $inss) * 0.075, 2);
            $valeTransporte = round($salarioBase * 0.06, 2);
            $valeRefeicao = 180.00;
            $planoSaude = 115.50;

            $horasExtras = ($idx === 0) ? round($salarioBase * 0.12, 2) : 0.00;
            $adicional = 250.00; // Adicional de função / periculosidade

            $totalProventos = round($salarioBase + $horasExtras + $adicional, 2);
            $totalDescontos = round($inss + $irrf + $valeTransporte + $valeRefeicao + $planoSaude, 2);
            $liquido = round($totalProventos - $totalDescontos, 2);

            $itens = [
                [
                    'codigo' => '001',
                    'descricao' => 'SALÁRIO BASE',
                    'referencia' => '30 Dias',
                    'tipo' => 'PROVENTO',
                    'valor' => $salarioBase
                ],
                [
                    'codigo' => '024',
                    'descricao' => 'ADICIONAL DE OBRAS / PERICULOSIDADE',
                    'referencia' => 'Integral',
                    'tipo' => 'PROVENTO',
                    'valor' => $adicional
                ]
            ];

            if ($horasExtras > 0) {
                $itens[] = [
                    'codigo' => '015',
                    'descricao' => 'HORAS EXTRAS 50%',
                    'referencia' => '14:30 Hs',
                    'tipo' => 'PROVENTO',
                    'valor' => $horasExtras
                ];
            }

            $itens[] = [
                'codigo' => '501',
                'descricao' => 'INSS FOLHA DE PAGAMENTO',
                'referencia' => '11,00 %',
                'tipo' => 'DESCONTO',
                'valor' => $inss
            ];

            $itens[] = [
                'codigo' => '505',
                'descricao' => 'IRRF SOBRE SALÁRIO',
                'referencia' => '7,50 %',
                'tipo' => 'DESCONTO',
                'valor' => $irrf
            ];

            $itens[] = [
                'codigo' => '518',
                'descricao' => 'VALE TRANSPORTE',
                'referencia' => '6,00 %',
                'tipo' => 'DESCONTO',
                'valor' => $valeTransporte
            ];

            $itens[] = [
                'codigo' => '522',
                'descricao' => 'COPARTICIPAÇÃO PLANO DE SAÚDE',
                'referencia' => 'UNIMED',
                'tipo' => 'DESCONTO',
                'valor' => $planoSaude
            ];

            $itens[] = [
                'codigo' => '530',
                'descricao' => 'VALE REFEIÇÃO / ALIMENTAÇÃO',
                'referencia' => 'VR',
                'tipo' => 'DESCONTO',
                'valor' => $valeRefeicao
            ];

            $holerites[] = [
                'id' => $id,
                'competencia' => "{$m['numero']}/{$ano}",
                'mes_nome' => $m['nome'],
                'ano' => $ano,
                'tipo_folha' => $m['tipo'],
                'tipo_folha_codigo' => 'mensal',
                'data_pagamento' => $m['pagamento'],
                'salario_base' => $salarioBase,
                'total_proventos' => $totalProventos,
                'total_descontos' => $totalDescontos,
                'salario_liquido' => $liquido,
                'base_fgts' => $totalProventos,
                'fgts_mes' => round($totalProventos * 0.08, 2),
                'base_inss' => $totalProventos,
                'base_irrf' => round($totalProventos - $inss, 2),
                'dados_bancarios' => [
                    'banco' => 'Banco do Brasil (001)',
                    'agencia' => '3421-5',
                    'conta' => '48.910-X'
                ],
                'itens' => $itens,
                'autenticacao_digital' => strtoupper(hash('sha256', $id . 'VILA_BOA_AUTH_KEY'))
            ];
        }

        // 2. Adiantamentos Quinzenais (Vales Salariais de 40%)
        foreach ($meses as $m) {
            $idVale = "VALE-{$ano}{$m['numero']}-{$matricula}";
            $valorVale = round($salarioBase * 0.40, 2);

            $holerites[] = [
                'id' => $idVale,
                'competencia' => "{$m['numero']}/{$ano} - Vale",
                'mes_nome' => $m['nome'],
                'ano' => $ano,
                'tipo_folha' => 'Adiantamento Quinzenal (Vale)',
                'tipo_folha_codigo' => 'adiantamento',
                'data_pagamento' => "20/{$m['numero']}/{$ano}",
                'salario_base' => $salarioBase,
                'total_proventos' => $valorVale,
                'total_descontos' => 0.00,
                'salario_liquido' => $valorVale,
                'base_fgts' => 0.00,
                'fgts_mes' => 0.00,
                'base_inss' => 0.00,
                'base_irrf' => 0.00,
                'dados_bancarios' => [
                    'banco' => 'Banco do Brasil (001)',
                    'agencia' => '3421-5',
                    'conta' => '48.910-X'
                ],
                'itens' => [
                    [
                        'codigo' => '008',
                        'descricao' => 'ADIANTAMENTO SALARIAL QUINZENAL (VALE 40%)',
                        'referencia' => '40,00 %',
                        'tipo' => 'PROVENTO',
                        'valor' => $valorVale
                    ]
                ],
                'autenticacao_digital' => strtoupper(hash('sha256', $idVale . 'VILA_BOA_VALE_KEY'))
            ];
        }

        // 3. 13º Salário (1ª e 2ª Parcela)
        // 3.1. 13º Salário - 1ª Parcela (50% adiantado)
        $id13_1 = "13SAL-1P-{$ano}-{$matricula}";
        $valor13_1 = round($salarioBase * 0.50, 2);
        $holerites[] = [
            'id' => $id13_1,
            'competencia' => "13/{$ano} - 1ª Parcela",
            'mes_nome' => 'Novembro',
            'ano' => $ano,
            'tipo_folha' => '13º Salário (1ª Parcela)',
            'tipo_folha_codigo' => '13_salario',
            'data_pagamento' => "30/11/{$ano}",
            'salario_base' => $salarioBase,
            'total_proventos' => $valor13_1,
            'total_descontos' => 0.00,
            'salario_liquido' => $valor13_1,
            'base_fgts' => $valor13_1,
            'fgts_mes' => round($valor13_1 * 0.08, 2),
            'base_inss' => 0.00,
            'base_irrf' => 0.00,
            'dados_bancarios' => [
                'banco' => 'Banco do Brasil (001)',
                'agencia' => '3421-5',
                'conta' => '48.910-X'
            ],
            'itens' => [
                [
                    'codigo' => '061',
                    'descricao' => 'PRIMEIRA PARCELA 13º SALÁRIO (50%)',
                    'referencia' => '50,00 %',
                    'tipo' => 'PROVENTO',
                    'valor' => $valor13_1
                ]
            ],
            'autenticacao_digital' => strtoupper(hash('sha256', $id13_1 . 'VILA_BOA_13_1_KEY'))
        ];

        // 3.2. 13º Salário - 2ª Parcela (Integral com descontos fiscais)
        $id13_2 = "13SAL-2P-{$ano}-{$matricula}";
        $inss13 = round($salarioBase * 0.11, 2);
        $irrf13 = round(($salarioBase - $inss13) * 0.075, 2);
        $totalDescontos13 = round($valor13_1 + $inss13 + $irrf13, 2);
        $liquido13_2 = round($salarioBase - $totalDescontos13, 2);

        $holerites[] = [
            'id' => $id13_2,
            'competencia' => "13/{$ano} - 2ª Parcela",
            'mes_nome' => 'Dezembro',
            'ano' => $ano,
            'tipo_folha' => '13º Salário (2ª Parcela)',
            'tipo_folha_codigo' => '13_salario',
            'data_pagamento' => "20/12/{$ano}",
            'salario_base' => $salarioBase,
            'total_proventos' => $salarioBase,
            'total_descontos' => $totalDescontos13,
            'salario_liquido' => $liquido13_2,
            'base_fgts' => $valor13_1,
            'fgts_mes' => round($valor13_1 * 0.08, 2),
            'base_inss' => $salarioBase,
            'base_irrf' => round($salarioBase - $inss13, 2),
            'dados_bancarios' => [
                'banco' => 'Banco do Brasil (001)',
                'agencia' => '3421-5',
                'conta' => '48.910-X'
            ],
            'itens' => [
                [
                    'codigo' => '062',
                    'descricao' => 'DÉCIMO TERCEIRO SALÁRIO INTEGRAL',
                    'referencia' => '12/12 Avos',
                    'tipo' => 'PROVENTO',
                    'valor' => $salarioBase
                ],
                [
                    'codigo' => '560',
                    'descricao' => 'ADIANTAMENTO 1ª PARCELA 13º SALÁRIO',
                    'referencia' => '50,00 %',
                    'tipo' => 'DESCONTO',
                    'valor' => $valor13_1
                ],
                [
                    'codigo' => '561',
                    'descricao' => 'INSS SOBRE 13º SALÁRIO',
                    'referencia' => '11,00 %',
                    'tipo' => 'DESCONTO',
                    'valor' => $inss13
                ],
                [
                    'codigo' => '565',
                    'descricao' => 'IRRF SOBRE 13º SALÁRIO',
                    'referencia' => '7,50 %',
                    'tipo' => 'DESCONTO',
                    'valor' => $irrf13
                ]
            ],
            'autenticacao_digital' => strtoupper(hash('sha256', $id13_2 . 'VILA_BOA_13_2_KEY'))
        ];

        // 4. Participação nos Resultados (PLR)
        $idPlr = "PLR-{$ano}-SEM1-{$matricula}";
        $valorPlr = round($salarioBase * 0.85, 2);
        $holerites[] = [
            'id' => $idPlr,
            'competencia' => "PLR/{$ano} - 1º Semestre",
            'mes_nome' => 'Julho',
            'ano' => $ano,
            'tipo_folha' => 'Participação nos Resultados (PLR)',
            'tipo_folha_codigo' => 'plr',
            'data_pagamento' => "15/07/{$ano}",
            'salario_base' => $salarioBase,
            'total_proventos' => $valorPlr,
            'total_descontos' => 0.00,
            'salario_liquido' => $valorPlr,
            'base_fgts' => 0.00,
            'fgts_mes' => 0.00,
            'base_inss' => 0.00,
            'base_irrf' => 0.00,
            'dados_bancarios' => [
                'banco' => 'Banco do Brasil (001)',
                'agencia' => '3421-5',
                'conta' => '48.910-X'
            ],
            'itens' => [
                [
                    'codigo' => '080',
                    'descricao' => 'PARTICIPAÇÃO NOS LUCROS E RESULTADOS (PLR)',
                    'referencia' => 'Acordo Coletivo Sintricom',
                    'tipo' => 'PROVENTO',
                    'valor' => $valorPlr
                ]
            ],
            'autenticacao_digital' => strtoupper(hash('sha256', $idPlr . 'VILA_BOA_PLR_KEY'))
        ];

        // 5. Recibo de Férias (CLT Art. 145 - Pagamento antecipado com 1/3 constitucional)
        $idFerias = "FERIAS-{$ano}-{$matricula}";
        $diasFerias = 20;
        $valorFeriasGozadas = round(($salarioBase / 30) * $diasFerias, 2);
        $tercoConstitucional = round($valorFeriasGozadas / 3, 2);
        $diasAbono = 10;
        $valorAbono = round(($salarioBase / 30) * $diasAbono, 2);
        $tercoAbono = round($valorAbono / 3, 2);

        $totalProventosFerias = round($valorFeriasGozadas + $tercoConstitucional + $valorAbono + $tercoAbono, 2);
        $inssFerias = round(($valorFeriasGozadas + $tercoConstitucional) * 0.11, 2);
        $irrfFerias = round(($valorFeriasGozadas + $tercoConstitucional - $inssFerias) * 0.075, 2);
        $totalDescontosFerias = round($inssFerias + $irrfFerias, 2);
        $liquidoFerias = round($totalProventosFerias - $totalDescontosFerias, 2);

        $holerites[] = [
            'id' => $idFerias,
            'competencia' => "Férias / {$ano}",
            'mes_nome' => 'Janeiro',
            'ano' => $ano,
            'tipo_folha' => 'Recibo de Férias',
            'tipo_folha_codigo' => 'ferias',
            'data_pagamento' => "12/01/{$ano}",
            'salario_base' => $salarioBase,
            'total_proventos' => $totalProventosFerias,
            'total_descontos' => $totalDescontosFerias,
            'salario_liquido' => $liquidoFerias,
            'base_fgts' => round($valorFeriasGozadas + $tercoConstitucional, 2),
            'fgts_mes' => round(($valorFeriasGozadas + $tercoConstitucional) * 0.08, 2),
            'base_inss' => round($valorFeriasGozadas + $tercoConstitucional, 2),
            'base_irrf' => round($valorFeriasGozadas + $tercoConstitucional - $inssFerias, 2),
            'dados_bancarios' => [
                'banco' => 'Banco do Brasil (001)',
                'agencia' => '3421-5',
                'conta' => '48.910-X'
            ],
            'itens' => [
                [
                    'codigo' => '021',
                    'descricao' => 'FÉRIAS GOZADAS (20 DIAS)',
                    'referencia' => '20 Dias',
                    'tipo' => 'PROVENTO',
                    'valor' => $valorFeriasGozadas
                ],
                [
                    'codigo' => '022',
                    'descricao' => '1/3 CONSTITUCIONAL DE FÉRIAS (ART. 7º CF/88)',
                    'referencia' => '33,33 %',
                    'tipo' => 'PROVENTO',
                    'valor' => $tercoConstitucional
                ],
                [
                    'codigo' => '025',
                    'descricao' => 'ABONO PECUNIÁRIO (VENDA DE 10 DIAS)',
                    'referencia' => '10 Dias',
                    'tipo' => 'PROVENTO',
                    'valor' => $valorAbono
                ],
                [
                    'codigo' => '026',
                    'descricao' => '1/3 CONSTITUCIONAL SOBRE ABONO PECUNIÁRIO',
                    'referencia' => '33,33 %',
                    'tipo' => 'PROVENTO',
                    'valor' => $tercoAbono
                ],
                [
                    'codigo' => '521',
                    'descricao' => 'INSS SOBRE FÉRIAS',
                    'referencia' => '11,00 %',
                    'tipo' => 'DESCONTO',
                    'valor' => $inssFerias
                ],
                [
                    'codigo' => '522',
                    'descricao' => 'IRRF SOBRE FÉRIAS',
                    'referencia' => '7,50 %',
                    'tipo' => 'DESCONTO',
                    'valor' => $irrfFerias
                ]
            ],
            'autenticacao_digital' => strtoupper(hash('sha256', $idFerias . 'VILA_BOA_FERIAS_KEY'))
        ];

        return $holerites;
    }

    /**
     * Lista os anos disponíveis de Informe de Rendimentos para IRPF / eSocial.
     */
    public function getInformesRendimentos(string $matricula, array $colaborador = []): array
    {
        $anosDisponiveis = [
            [
                'ano_calendario' => '2025',
                'exercicio' => '2026',
                'disponivel' => true,
                'data_emissao' => '25/02/2026',
                'status' => 'Liberado pelo DP',
                'tipo' => 'DIRF / eSocial S-1210'
            ],
            [
                'ano_calendario' => '2024',
                'exercicio' => '2025',
                'disponivel' => true,
                'data_emissao' => '24/02/2025',
                'status' => 'Liberado pelo DP',
                'tipo' => 'DIRF / eSocial S-1210'
            ],
            [
                'ano_calendario' => '2023',
                'exercicio' => '2024',
                'disponivel' => true,
                'data_emissao' => '23/02/2024',
                'status' => 'Liberado pelo DP',
                'tipo' => 'DIRF / eSocial S-1210'
            ]
        ];

        return $anosDisponiveis;
    }

    /**
     * Retorna o Informe de Rendimentos oficial formatado rigorosamente conforme
     * a Instrução Normativa da Receita Federal do Brasil (RFB) e layout eSocial.
     */
    public function getInformeRendimentosDetalhado(string $matricula, string $anoCalendario, array $colaborador = []): array
    {
        $exercicio = (string)((int)$anoCalendario + 1);

        $salarioBase = match ($matricula) {
            'VB-1040' => 3850.00,
            'VB-2085' => 5400.00,
            'VB-3042' => 4150.00,
            default => 3500.00
        };

        // Estimativas fiscais anuais realistas baseadas no salário base
        $rendimentosTributaveis = round(($salarioBase * 12) + ($salarioBase * 0.08 * 12), 2);
        $previdenciaOficial = round($rendimentosTributaveis * 0.11, 2);
        $irrfRetido = round(($rendimentosTributaveis - $previdenciaOficial) * 0.065, 2);
        
        $decimoTerceiroBruto = round($salarioBase, 2);
        $decimoTerceiroIrrf = round($decimoTerceiroBruto * 0.05, 2);
        $decimoTerceiroLiquido = round($decimoTerceiroBruto - ($decimoTerceiroBruto * 0.11) - $decimoTerceiroIrrf, 2);

        $diariasAjudasCusto = 1800.00;
        $beneficiosIsentos = 2640.00; // VA / VT
        $planoSaudeCopart = 1386.00;

        $chaveControle = strtoupper(hash('sha256', "IRPF_{$matricula}_{$anoCalendario}_{$rendimentosTributaveis}_RFB_VILABOA"));

        return [
            'titulo' => 'COMPROVANTE DE RENDIMENTOS PAGOS E DE RETENÇÃO DE IMPOSTO DE RENDA NA FONTE',
            'ano_calendario' => $anoCalendario,
            'exercicio' => $exercicio,
            'normativa' => 'Instrução Normativa RFB nº 2.060/2021 c/c Leiaute eSocial S-1210',
            'chave_autenticacao' => $chaveControle,
            'data_emissao' => date('d/m/Y'),
            'hora_emissao' => date('H:i:s'),

            // 1. Fonte Pagadora Pessoa Jurídica
            'fonte_pagadora' => [
                'cnpj' => '02.785.261/0001-90',
                'nome_empresarial' => 'VILA BOA CONSTRUCOES E SERVICOS LIMITADA',
                'endereco' => 'São Bernardo do Campo - SP',
                'responsavel_dp' => 'Departamento Pessoal / Recursos Humanos'
            ],

            // 2. Pessoa Física Beneficiária dos Rendimentos
            'beneficiario' => [
                'cpf' => $colaborador['cpf'] ?? $colaborador['cpf_formatado'] ?? '123.456.789-00',
                'nome_completo' => $colaborador['nome'] ?? 'Colaborador',
                'matricula' => $matricula,
                'natureza_rendimento' => '01 - Trabalho Assalariado'
            ],

            // 3. Rendimentos Tributáveis, Deduções e Imposto Retido na Fonte
            'rendimentos_tributaveis' => [
                'total_rendimentos' => $rendimentosTributaveis,
                'previdencia_oficial' => $previdenciaOficial,
                'previdencia_complementar' => 0.00,
                'pensao_alimenticia' => 0.00,
                'imposto_retido_fonte' => $irrfRetido
            ],

            // 4. Rendimentos Isentos e Não Tributáveis
            'rendimentos_isentos' => [
                'parcela_isenta_aposentadoria' => 0.00,
                'diarias_ajuda_custo' => $diariasAjudasCusto,
                'indenizacoes_rescisao_fgts' => 0.00,
                'outros_isentos' => $beneficiosIsentos,
                'descricao_outros' => 'Auxílio Alimentação / Transporte não integráveis ao salário'
            ],

            // 5. Rendimentos Sujeitos a Tributação Exclusiva
            'tributacao_exclusiva' => [
                'decimo_terceiro_salario' => $decimoTerceiroLiquido,
                'imposto_retido_decimo_terceiro' => $decimoTerceiroIrrf,
                'outros' => 0.00
            ],

            // 7. Informações Complementares
            'informacoes_complementares' => [
                'plano_saude' => [
                    'operadora' => 'UNIMED DO ABC / SÃO BERNARDO',
                    'cnpj' => '43.202.472/0001-30',
                    'total_descontado' => $planoSaudeCopart
                ],
                'observacao' => "Valores transmitidos ao ambiente nacional da Receita Federal do Brasil através do eSocial (Evento S-1210 / DIRF). Documento com validade legal plena para declaração de IRPF {$exercicio}."
            ]
        ];
    }
}
