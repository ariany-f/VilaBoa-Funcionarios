<?php

namespace App\Services;

/**
 * Escala de folgas (PRC-153, item 2 "Gerar as Escalas").
 *
 * O RH exporta do RM a escala de cada letra (período de 21 a 20 do mês seguinte), publica no
 * painel e indica os colaboradores daquela letra. O colaborador consulta a escala no portal e
 * dá ciência digital, que substitui a assinatura no rodapé da escala impressa.
 *
 * Escalas: data/escalas_folga.json · Ciências: data/escalas_folga_ciencias.json
 */
class EscalaFolgaService
{
    private string $escalasFile;
    private string $cienciasFile;

    public function __construct(?string $dir = null)
    {
        $dir ??= dirname(__DIR__) . '/data';
        $this->escalasFile = $dir . '/escalas_folga.json';
        $this->cienciasFile = $dir . '/escalas_folga_ciencias.json';
    }

    // ==================== Colaborador ====================

    /**
     * Escalas do colaborador, da mais recente para a mais antiga. Se o RH publicou mais de uma
     * escala para ele no mesmo período, vale a última publicada.
     */
    public function doColaborador(string $matricula): array
    {
        $ciencias = $this->lerJson($this->cienciasFile);
        $porPeriodo = [];
        foreach ($this->todas() as $e) {
            if (!in_array($matricula, $e['matriculas'] ?? [], true)) {
                continue;
            }
            $chave = $e['periodo_inicio'];
            if (!isset($porPeriodo[$chave]) || $e['publicado_em'] > $porPeriodo[$chave]['publicado_em']) {
                $porPeriodo[$chave] = $e;
            }
        }
        $lista = array_map(function ($e) use ($matricula, $ciencias) {
            unset($e['matriculas']);
            $e['ciencia'] = $ciencias[$this->chave($matricula, $e['id'])] ?? null;
            return $e;
        }, array_values($porPeriodo));
        usort($lista, fn($a, $b) => strcmp($b['periodo_inicio'], $a['periodo_inicio']));
        return $lista;
    }

    /** Registra a ciência do colaborador (uma vez por escala). */
    public function registrarCiencia(array $colaborador, string $escalaId, string $ip, string $userAgent): ?array
    {
        $matricula = $colaborador['matricula'];
        $escala = null;
        foreach ($this->doColaborador($matricula) as $e) {
            if ($e['id'] === $escalaId) {
                $escala = $e;
            }
        }
        if (!$escala) {
            return null;
        }
        if ($escala['ciencia']) {
            return $escala;
        }

        $agora = date('Y-m-d H:i:s');
        $periodo = $this->periodoBr($escala);
        $termo = "Declaro que tomei ciência da minha escala de folgas ({$escala['titulo']}) do período de {$periodo}, "
            . 'com os dias de trabalho e de folga nela indicados.';
        // Hash de integridade: amarra colaborador + dias da escala + momento + origem
        $hash = strtoupper(hash('sha256', json_encode([
            'matricula' => $matricula,
            'cpf' => $colaborador['cpf'] ?? '',
            'escala_id' => $escala['id'],
            'dias' => $escala['dias'],
            'data_hora' => $agora,
            'timestamp' => microtime(true),
            'ip' => $ip,
            'user_agent' => $userAgent,
        ])));
        $ciencia = [
            'matricula' => $matricula,
            'escala_id' => $escala['id'],
            'data_hora' => date('d/m/Y H:i:s'),
            'timestamp' => date('c'),
            'ip' => $ip,
            'user_agent' => substr($userAgent, 0, 300),
            'hash_autenticidade' => $hash,
            'termo' => $termo,
            'amparo_legal' => 'Lei 14.063/2020 (assinatura eletrônica) · Art. 67 da CLT (descanso semanal)',
        ];

        $this->alterarJson($this->cienciasFile, function (array $todas) use ($matricula, $escalaId, $ciencia) {
            $todas[$this->chave($matricula, $escalaId)] ??= $ciencia;
            return [$todas, null];
        });

        $escala['ciencia'] = $ciencia;
        return $escala;
    }

    // ==================== RH ====================

    /** Todas as escalas com o andamento das ciências (para o painel do RH). */
    public function listarParaRh(array $colaboradores): array
    {
        $ciencias = $this->lerJson($this->cienciasFile);
        $porMatricula = [];
        foreach ($colaboradores as $c) {
            $porMatricula[$c['matricula']] = $c;
        }
        $lista = array_map(function ($e) use ($ciencias, $porMatricula) {
            $e['colaboradores'] = array_map(function ($m) use ($e, $ciencias, $porMatricula) {
                $c = $porMatricula[$m] ?? null;
                $ci = $ciencias[$this->chave($m, $e['id'])] ?? null;
                return [
                    'matricula' => $m,
                    'nome' => $c['nome'] ?? $m,
                    'cargo' => $c['cargo'] ?? '',
                    'empresa' => $c ? EmpresaService::doColaborador($c) : '',
                    'telefone' => $c['telefone_atual'] ?? '',
                    'ciente_em' => $ci['data_hora'] ?? null,
                ];
            }, $e['matriculas'] ?? []);
            $e['total'] = count($e['colaboradores']);
            $e['cientes'] = count(array_filter($e['colaboradores'], fn($c) => $c['ciente_em']));
            return $e;
        }, $this->todas());
        usort($lista, fn($a, $b) => strcmp($b['periodo_inicio'] . $b['publicado_em'], $a['periodo_inicio'] . $a['publicado_em']));
        return $lista;
    }

    /** Publica uma escala lida da planilha (os dias chegam já interpretados pelo painel). */
    public function publicar(array $dados, string $publicadoPor): array
    {
        $dias = $this->normalizarDias($dados['dias'] ?? []);
        if (!$dias) {
            return ['erro' => 'A planilha não tem dias de escala válidos.'];
        }
        if (!array_filter($dias, fn($d) => $d['folga'])) {
            return ['erro' => 'Nenhuma folga encontrada na planilha. Confira se é a escala certa.'];
        }
        $titulo = trim((string)($dados['titulo'] ?? ''));
        if ($titulo === '') {
            return ['erro' => 'Informe o nome da escala (ex.: Escala 5x1 - Letra A).'];
        }
        $matriculas = $this->normalizarMatriculas($dados['matriculas'] ?? []);
        if (!$matriculas) {
            return ['erro' => 'Escolha os colaboradores desta escala.'];
        }

        $escala = [
            'id' => 'esc_' . bin2hex(random_bytes(6)),
            'titulo' => mb_substr($titulo, 0, 120),
            'letra' => strtoupper(mb_substr(trim((string)($dados['letra'] ?? '')), 0, 3)),
            'horario' => mb_substr(trim((string)($dados['horario'] ?? '')), 0, 80),
            'periodo_inicio' => $dias[0]['data'],
            'periodo_fim' => $dias[count($dias) - 1]['data'],
            'dias' => $dias,
            'arquivo' => mb_substr(basename((string)($dados['arquivo'] ?? '')), 0, 160),
            'matriculas' => $matriculas,
            'publicado_em' => date('Y-m-d H:i:s'),
            'publicado_por' => $publicadoPor,
        ];
        $movidos = $this->alterarJson($this->escalasFile, function (array $todas) use ($escala) {
            [$todas, $movidos] = $this->tirarDeOutrasEscalas($todas, $escala['id'], $escala['periodo_inicio'], $escala['matriculas']);
            $todas[] = $escala;
            return [$todas, $movidos];
        });
        return ['sucesso' => true, 'escala' => $escala, 'movidos' => $movidos];
    }

    /** Troca os colaboradores de uma escala (quem já deu ciência mantém o registro). */
    public function alterarColaboradores(string $id, array $matriculas): array
    {
        $matriculas = $this->normalizarMatriculas($matriculas);
        if (!$matriculas) {
            return ['erro' => 'A escala precisa de pelo menos um colaborador. Para tirar todos, remova a escala.'];
        }
        $achou = $this->alterarJson($this->escalasFile, function (array $todas) use ($id, $matriculas) {
            $atual = null;
            foreach ($todas as $i => $e) {
                if ($e['id'] === $id) {
                    $todas[$i]['matriculas'] = $matriculas;
                    $atual = $e;
                }
            }
            if (!$atual) {
                return [$todas, null];
            }
            return $this->tirarDeOutrasEscalas($todas, $id, $atual['periodo_inicio'], $matriculas);
        });
        return $achou === null ? ['erro' => 'Escala não encontrada.'] : ['sucesso' => true, 'movidos' => $achou];
    }

    public function remover(string $id): array
    {
        $achou = $this->alterarJson($this->escalasFile, function (array $todas) use ($id) {
            $restantes = array_values(array_filter($todas, fn($e) => $e['id'] !== $id));
            return [$restantes, count($restantes) < count($todas)];
        });
        return $achou ? ['sucesso' => true] : ['erro' => 'Escala não encontrada.'];
    }

    // ==================== Apoio ====================

    /**
     * Cada colaborador fica em uma só escala por período: ao entrar nesta, sai das outras do mesmo
     * período (ex.: mudou da Letra A para a B). As ciências já dadas continuam gravadas.
     */
    private function tirarDeOutrasEscalas(array $todas, string $idAtual, string $periodoInicio, array $matriculas): array
    {
        $movidos = 0;
        foreach ($todas as $i => $e) {
            if ($e['id'] === $idAtual || $e['periodo_inicio'] !== $periodoInicio) {
                continue;
            }
            $restantes = array_values(array_diff($e['matriculas'] ?? [], $matriculas));
            $movidos += count($e['matriculas'] ?? []) - count($restantes);
            $todas[$i]['matriculas'] = $restantes;
        }
        return [$todas, $movidos];
    }

    private function todas(): array
    {
        return $this->lerJson($this->escalasFile);
    }

    private function normalizarDias(array $dias): array
    {
        $nomes = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB'];
        $saida = [];
        foreach ($dias as $d) {
            $data = (string)($d['data'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || !strtotime($data)) {
                continue;
            }
            $horarios = array_map(fn($h) => mb_substr(trim((string)$h), 0, 20), array_slice((array)($d['horarios'] ?? []), 0, 4));
            $saida[$data] = [
                'data' => $data,
                'dia' => $nomes[(int)date('w', strtotime($data))],
                'folga' => !empty($d['folga']),
                'horarios' => $horarios,
            ];
        }
        ksort($saida);
        return array_slice(array_values($saida), 0, 62);
    }

    private function normalizarMatriculas(array $matriculas): array
    {
        $limpas = array_filter(array_map(fn($m) => trim((string)$m), $matriculas), fn($m) => $m !== '');
        return array_values(array_unique($limpas));
    }

    private function periodoBr(array $e): string
    {
        $br = fn($iso) => implode('/', array_reverse(explode('-', $iso)));
        return $br($e['periodo_inicio']) . ' a ' . $br($e['periodo_fim']);
    }

    private function chave(string $matricula, string $escalaId): string
    {
        return $matricula . '|' . $escalaId;
    }

    private function lerJson(string $arquivo): array
    {
        return is_file($arquivo) ? (json_decode(file_get_contents($arquivo), true) ?: []) : [];
    }

    /** Lê, altera e grava com trava de arquivo. O callback devolve [dados, retorno]. */
    private function alterarJson(string $arquivo, callable $alteracao): mixed
    {
        $fp = @fopen($arquivo, 'c+');
        if (!$fp) {
            throw new \RuntimeException('Não foi possível gravar a escala no servidor.');
        }
        flock($fp, LOCK_EX);
        $dados = json_decode(stream_get_contents($fp), true) ?: [];
        [$dados, $retorno] = $alteracao($dados);
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
        fclose($fp);
        return $retorno;
    }
}
