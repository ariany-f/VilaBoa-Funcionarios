<?php

namespace App\Services;

require_once __DIR__ . '/EmpresaService.php';

class EpiService
{
    /** Termo padrão gravado nas fichas cadastradas pelo RH. */
    private const TERMO_PADRAO = 'Declaro para fins de direito que recebi no mês em epígrafe os Equipamentos de Proteção Individual abaixo relacionados, fornecidos gratuitamente pela empresa e destinados exclusivamente a minha proteção contra Acidentes e/ou doenças, nos termos Art.191 da C.L.T. e com a NR-6 da Portaria nº 3.214/78.';
    private const LIMITE_ITENS = 60;

    private string $dataFile;

    public function __construct(?string $dataFile = null)
    {
        $this->dataFile = $dataFile ?: dirname(__DIR__) . '/data/epis.json';
    }

    public function getAll(): array
    {
        if (!file_exists($this->dataFile)) {
            return [];
        }
        $content = file_get_contents($this->dataFile);
        $lista = json_decode($content, true) ?: [];
        return array_map([$this, 'normalizar'], $lista);
    }

    /**
     * Garante todos os campos da ficha física de EPI (competência, chapa, equipe,
     * e por item: data, quantidade, unidade, código, C.A., valor e situação).
     */
    private function normalizar(array $e): array
    {
        $dataIso = $e['data_entrega'] ?? '';
        if (empty($e['competencia']) && preg_match('/^(\d{4})-(\d{2})/', $dataIso, $m)) {
            $e['competencia'] = $m[2] . '/' . $m[1];
        }
        $e['competencia'] = $e['competencia'] ?? '';
        $e['chapa'] = $e['chapa'] ?? preg_replace('/\D/', '', (string)($e['matricula'] ?? ''));
        $e['equipe'] = $e['equipe'] ?? '';
        // Razão social como na ficha em papel (fichas antigas foram gravadas com "Limitada")
        $empresa = $e['empresa'] ?? '';
        if ($empresa === '' || strcasecmp($empresa, 'Vila Boa Construcoes e Servicos Limitada') === 0) {
            $empresa = 'VILA BOA CONSTRUCOES E SERVICOS LTDA.';
        }
        $e['empresa'] = $empresa;
        $e['cnpj'] = $e['cnpj'] ?? '02.785.261/0001-90';
        $e['local_data'] = $e['local_data'] ?? '';
        foreach (($e['itens'] ?? []) as $i => $it) {
            $it['data'] = $it['data'] ?? ($e['data_entrega_formatada'] ?? '');
            $it['quantidade'] = $it['quantidade'] ?? '';
            $it['unidade'] = $it['unidade'] ?? '';
            $it['codigo'] = $it['codigo'] ?? '';
            $it['ca'] = $it['ca'] ?? '';
            $it['valor'] = $it['valor'] ?? '';
            // A ficha só tem duas situações: Primeira Entrega ou Troca (reposição conta como troca)
            $situacao = trim((string)($it['situacao'] ?? ''));
            $it['situacao'] = ($situacao === '' || stripos($situacao, 'primeira') === 0) ? 'Primeira Entrega' : 'Troca';
            $e['itens'][$i] = $it;
        }
        return $e;
    }

    private function salvar(array $epis): bool
    {
        return file_put_contents(
            $this->dataFile,
            json_encode($epis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ) !== false;
    }

    // ================================================================ Cadastro pelo RH

    /**
     * Fichas para o painel do RH, sem a imagem da assinatura (pesada) e com a empresa do grupo
     * do colaborador (Vila Boa, Mondego...) para o filtro da empresa escolhida no painel.
     */
    public function listarRh(array $colaboradores): array
    {
        $porMatricula = [];
        foreach ($colaboradores as $c) $porMatricula[$c['matricula']] = $c;
        $lista = [];
        foreach ($this->getAll() as $e) {
            $colab = $porMatricula[$e['matricula'] ?? ''] ?? null;
            $assinatura = $e['assinatura'] ?? null;
            unset($e['assinatura']);
            $e['assinado_em'] = is_array($assinatura) ? ($assinatura['data_hora'] ?? null) : null;
            $e['empresa_grupo'] = $colab ? EmpresaService::doColaborador($colab) : '';
            $lista[] = $e;
        }
        // Competência mais recente primeiro (MM/AAAA -> AAAAMM), depois o nome
        usort($lista, function ($a, $b) {
            $ca = substr($a['competencia'], 3) . substr($a['competencia'], 0, 2);
            $cb = substr($b['competencia'], 3) . substr($b['competencia'], 0, 2);
            return strcmp($cb, $ca) ?: strcmp($a['nome_colaborador'] ?? '', $b['nome_colaborador'] ?? '');
        });
        return $lista;
    }

    /**
     * Cria (sem id) ou altera (com id) uma ficha. Ficha assinada não muda: a assinatura
     * amarra os itens no hash de autenticidade.
     */
    public function salvarRh(array $dados, array $colaboradores, string $autor): array
    {
        $ficha = $this->montarFicha($dados, $colaboradores);
        if (isset($ficha['erro'])) return $ficha + ['status' => 422];
        $id = trim((string)($dados['id'] ?? ''));
        $resposta = null;

        $this->transacao(function (array &$lista) use ($ficha, $id, $autor, &$resposta) {
            $agora = date('Y-m-d H:i:s');
            if ($id === '') {
                $ficha['id'] = $this->proximoId($lista, $ficha['competencia']);
                $ficha['status'] = 'pendente_assinatura';
                $ficha['assinatura'] = null;
                $ficha['termo_responsabilidade'] = self::TERMO_PADRAO;
                $ficha['cadastrado_por'] = $autor;
                $ficha['cadastrado_em'] = $agora;
                $lista[] = $ficha;
                $resposta = ['sucesso' => true, 'id' => $ficha['id']];
                return;
            }
            foreach ($lista as $i => $e) {
                if (($e['id'] ?? '') !== $id) continue;
                if (($e['status'] ?? '') !== 'pendente_assinatura') {
                    $resposta = ['erro' => 'Esta ficha já foi assinada pelo colaborador e não pode mais ser alterada.', 'status' => 409];
                    return;
                }
                $lista[$i] = array_merge($e, $ficha, ['alterado_por' => $autor, 'alterado_em' => $agora]);
                $resposta = ['sucesso' => true, 'id' => $id];
                return;
            }
            $resposta = ['erro' => 'Ficha não encontrada.', 'status' => 404];
        });
        return $resposta;
    }

    /**
     * Importação da planilha: cada ficha (colaborador + competência) entra como nova ou,
     * se já houver uma ficha pendente do mesmo colaborador na mesma competência, os itens
     * são acrescentados a ela. Fichas com erro são devolvidas sem gravar nada delas.
     */
    public function importarRh(array $fichas, array $colaboradores, string $autor): array
    {
        $validas = [];
        $erros = [];
        foreach (array_values($fichas) as $n => $dados) {
            $ficha = is_array($dados) ? $this->montarFicha($dados, $colaboradores) : ['erro' => 'Linha inválida.'];
            if (isset($ficha['erro'])) {
                $erros[] = ['ficha' => $n + 1, 'matricula' => (string)($dados['matricula'] ?? ''), 'erro' => $ficha['erro']];
                continue;
            }
            $validas[] = $ficha;
        }
        $criadas = 0;
        $acrescidas = 0;
        if ($validas) {
            $this->transacao(function (array &$lista) use ($validas, $autor, &$criadas, &$acrescidas) {
                $agora = date('Y-m-d H:i:s');
                foreach ($validas as $ficha) {
                    foreach ($lista as $i => $e) {
                        if (($e['matricula'] ?? '') === $ficha['matricula'] && ($e['competencia'] ?? '') === $ficha['competencia'] && ($e['status'] ?? '') === 'pendente_assinatura') {
                            $itens = array_merge($e['itens'] ?? [], $ficha['itens']);
                            foreach ($itens as $k => &$it) $it['item'] = $k + 1;
                            unset($it);
                            $lista[$i]['itens'] = $itens;
                            if (strcmp($ficha['data_entrega'], $e['data_entrega'] ?? '9999') < 0) {
                                $lista[$i]['data_entrega'] = $ficha['data_entrega'];
                                $lista[$i]['data_entrega_formatada'] = $ficha['data_entrega_formatada'];
                            }
                            $lista[$i]['alterado_por'] = $autor;
                            $lista[$i]['alterado_em'] = $agora;
                            $acrescidas++;
                            continue 2;
                        }
                    }
                    $ficha['id'] = $this->proximoId($lista, $ficha['competencia']);
                    $ficha['status'] = 'pendente_assinatura';
                    $ficha['assinatura'] = null;
                    $ficha['termo_responsabilidade'] = self::TERMO_PADRAO;
                    $ficha['cadastrado_por'] = $autor;
                    $ficha['cadastrado_em'] = $agora;
                    $lista[] = $ficha;
                    $criadas++;
                }
            });
        }
        return ['sucesso' => true, 'criadas' => $criadas, 'acrescidas' => $acrescidas, 'erros' => $erros];
    }

    public function removerRh(string $id): array
    {
        $resposta = ['erro' => 'Ficha não encontrada.', 'status' => 404];
        $this->transacao(function (array &$lista) use ($id, &$resposta) {
            foreach ($lista as $i => $e) {
                if (($e['id'] ?? '') !== $id) continue;
                if (($e['status'] ?? '') !== 'pendente_assinatura') {
                    $resposta = ['erro' => 'Ficha assinada não pode ser removida: ela é o comprovante da entrega.', 'status' => 409];
                    return;
                }
                array_splice($lista, $i, 1);
                $resposta = ['sucesso' => true];
                return;
            }
        });
        return $resposta;
    }

    /** Valida os dados do formulário/planilha e devolve a ficha no formato do epis.json. */
    private function montarFicha(array $d, array $colaboradores): array
    {
        $matricula = trim((string)($d['matricula'] ?? ''));
        $colab = null;
        foreach ($colaboradores as $c) {
            if (strcasecmp($c['matricula'], $matricula) === 0) { $colab = $c; break; }
        }
        if (!$colab) return ['erro' => $matricula === '' ? 'Escolha o colaborador.' : "Colaborador $matricula não encontrado."];

        $competencia = $this->normalizarCompetencia((string)($d['competencia'] ?? ''));
        if (!$competencia) return ['erro' => 'Informe a competência no formato MM/AAAA.'];

        $texto = fn($v, $max = 200) => mb_substr(trim(preg_replace('/\s+/', ' ', (string)$v)), 0, $max);
        $itens = [];
        foreach ((array)($d['itens'] ?? []) as $n => $it) {
            if (!is_array($it)) continue;
            $descricao = $texto($it['descricao'] ?? '');
            $vazio = $descricao === '' && trim((string)($it['codigo'] ?? '')) === '' && trim((string)($it['quantidade'] ?? '')) === '';
            if ($vazio) continue;
            $linha = $n + 1;
            if ($descricao === '') return ['erro' => "Item $linha: informe a descrição do EPI."];
            $data = $this->normalizarData((string)($it['data'] ?? ''));
            if (!$data) return ['erro' => "Item $linha ($descricao): informe a data da entrega (DD/MM/AAAA)."];
            $qtd = str_replace(',', '.', trim((string)($it['quantidade'] ?? '')));
            if (!is_numeric($qtd) || (float)$qtd <= 0) return ['erro' => "Item $linha ($descricao): informe a quantidade."];
            $valor = trim((string)($it['valor'] ?? ''));
            if ($valor !== '') {
                $num = $this->paraNumero($valor);
                if ($num === null || $num < 0) return ['erro' => "Item $linha ($descricao): valor inválido."];
                $valor = number_format($num, 2, ',', '.');
            }
            $situacao = stripos(trim((string)($it['situacao'] ?? '')), 'troca') === 0 ? 'Troca' : 'Primeira Entrega';
            $itens[] = [
                'item' => count($itens) + 1,
                'data' => $data,
                'quantidade' => (float)$qtd == (int)$qtd ? (int)$qtd : (float)$qtd,
                'unidade' => mb_strtoupper($texto($it['unidade'] ?? '', 10)) ?: 'UN',
                'codigo' => $texto($it['codigo'] ?? '', 30),
                'descricao' => $descricao,
                'ca' => $texto($it['ca'] ?? '', 20),
                'valor' => $valor,
                'situacao' => $situacao,
            ];
        }
        if (!$itens) return ['erro' => 'Inclua ao menos um item na ficha.'];
        if (count($itens) > self::LIMITE_ITENS) return ['erro' => 'Uma ficha pode ter no máximo ' . self::LIMITE_ITENS . ' itens.'];

        // Data de entrega da ficha = a primeira data dos itens
        $datasIso = array_map(fn($it) => implode('-', array_reverse(explode('/', $it['data']))), $itens);
        sort($datasIso);
        $dataIso = $datasIso[0];
        $chapa = preg_replace('/\D/', '', (string)($d['chapa'] ?? ''));
        // Sem chapa/equipe no formulário ou na planilha, repete as da última ficha do colaborador
        $anterior = [];
        foreach ($this->getAll() as $e) {
            if (($e['matricula'] ?? '') === $colab['matricula'] && strcmp($e['data_entrega'] ?? '', $anterior['data_entrega'] ?? '') >= 0) $anterior = $e;
        }

        $ficha = [
            'colaborador_id' => (int)($colab['id'] ?? 0),
            'matricula' => $colab['matricula'],
            'nome_colaborador' => $colab['nome'],
            'cpf' => $colab['cpf_formatado'] ?? $colab['cpf'] ?? '',
            'cargo' => $texto($d['cargo'] ?? '', 120) ?: ($colab['cargo'] ?? ''),
            'departamento' => $colab['departamento'] ?? '',
            'chapa' => $chapa !== '' ? $chapa : (($anterior['chapa'] ?? '') ?: preg_replace('/\D/', '', $colab['matricula'])),
            'equipe' => $texto($d['equipe'] ?? '', 40) ?: (string)($anterior['equipe'] ?? ''),
            'competencia' => $competencia,
            'data_entrega' => $dataIso,
            'data_entrega_formatada' => implode('/', array_reverse(explode('-', $dataIso))),
            'canteiro_obra' => $texto($d['canteiro_obra'] ?? '', 160),
            'tecnico_responsavel' => $texto($d['tecnico_responsavel'] ?? '', 160),
            'motivo' => $texto($d['motivo'] ?? '', 160),
            'local_data' => $texto($d['local_data'] ?? '', 160),
            'itens' => $itens,
        ];
        foreach (['empresa' => 120, 'cnpj' => 20] as $campo => $max) {
            $v = $texto($d[$campo] ?? '', $max);
            if ($v !== '') $ficha[$campo] = $v;
        }
        return $ficha;
    }

    private function normalizarCompetencia(string $v): ?string
    {
        $v = trim($v);
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $v, $m)) [$mes, $ano] = [(int)$m[2], $m[1]];
        elseif (preg_match('/^(\d{1,2})[\/\-.](\d{4})$/', $v, $m)) [$mes, $ano] = [(int)$m[1], $m[2]];
        else return null;
        return ($mes >= 1 && $mes <= 12) ? sprintf('%02d/%s', $mes, $ano) : null;
    }

    /** Aceita DD/MM/AAAA, DD/MM/AA ou AAAA-MM-DD e devolve DD/MM/AAAA. */
    private function normalizarData(string $v): ?string
    {
        $v = trim($v);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $v, $m)) [$d, $mes, $a] = [(int)$m[3], (int)$m[2], (int)$m[1]];
        elseif (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2}|\d{4})$/', $v, $m)) [$d, $mes, $a] = [(int)$m[1], (int)$m[2], (int)(strlen($m[3]) === 2 ? '20' . $m[3] : $m[3])];
        else return null;
        return checkdate($mes, $d, $a) ? sprintf('%02d/%02d/%04d', $d, $mes, $a) : null;
    }

    /** "1.234,56", "1234,56" ou "1234.56" -> 1234.56 */
    private function paraNumero(string $v): ?float
    {
        $v = preg_replace('/[^\d,.\-]/', '', $v);
        if (str_contains($v, ',')) $v = str_replace(',', '.', str_replace('.', '', $v));
        return is_numeric($v) ? (float)$v : null;
    }

    /** EPI-<ano da competência>-NNN, sem repetir os já usados. */
    private function proximoId(array $lista, string $competencia): string
    {
        $ano = substr($competencia, 3);
        $maior = 0;
        foreach ($lista as $e) {
            if (preg_match('/^EPI-' . $ano . '-(\d+)$/', (string)($e['id'] ?? ''), $m)) $maior = max($maior, (int)$m[1]);
        }
        return sprintf('EPI-%s-%03d', $ano, $maior + 1);
    }

    /** Lê, altera e grava o epis.json com trava, para dois usuários do RH não se sobrescreverem. */
    private function transacao(callable $fn): void
    {
        $lock = fopen($this->dataFile . '.lock', 'c');
        flock($lock, LOCK_EX);
        try {
            $lista = file_exists($this->dataFile) ? (json_decode((string)file_get_contents($this->dataFile), true) ?: []) : [];
            $antes = $lista;
            $fn($lista);
            if ($lista !== $antes) $this->salvar(array_values($lista));
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function getEpisPorColaborador(int $colaboradorId): array
    {
        $todos = $this->getAll();
        $resultado = [
            'pendentes' => [],
            'assinados' => [],
            'total_pendentes' => 0,
            'total_assinados' => 0
        ];

        foreach ($todos as $e) {
            if ((int)$e['colaborador_id'] === $colaboradorId) {
                if ($e['status'] === 'pendente_assinatura') {
                    $resultado['pendentes'][] = $e;
                } else {
                    $resultado['assinados'][] = $e;
                }
            }
        }

        $resultado['total_pendentes'] = count($resultado['pendentes']);
        $resultado['total_assinados'] = count($resultado['assinados']);

        return $resultado;
    }

    public function findById(string $entregaId): ?array
    {
        $todos = $this->getAll();
        foreach ($todos as $e) {
            if ($e['id'] === $entregaId) {
                return $e;
            }
        }
        return null;
    }

    /**
     * Assina a entrega de EPI com validade jurídica (NR-06 / Portaria MTP 672/2021 e Lei 14.063/2020).
     */
    public function assinarEntrega(
        int $colaboradorId,
        string $entregaId,
        string $assinaturaBase64,
        string $ip,
        string $userAgent
    ): ?array {
        $todos = $this->getAll();
        $atualizado = null;
        $agora = date('Y-m-d H:i:s');
        $timestamp = microtime(true);

        foreach ($todos as &$e) {
            if ($e['id'] === $entregaId && (int)$e['colaborador_id'] === $colaboradorId) {
                // Gera a Chave de Autenticidade Criptográfica (Hash SHA-256)
                // Amarra: ID da entrega + CPF + Itens + Timestamp + Assinatura + IP
                $rawPayload = json_encode([
                    'entrega_id' => $e['id'],
                    'colaborador_id' => $colaboradorId,
                    'cpf' => $e['cpf'],
                    'itens' => $e['itens'],
                    'data_hora' => $agora,
                    'timestamp' => $timestamp,
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'assinatura_hash' => hash('sha256', $assinaturaBase64),
                    'empresa' => 'VILA BOA CONSTRUCOES E SERVICOS LIMITADA - CNPJ 02.785.261/0001-90'
                ]);

                $hashIntegridade = strtoupper(hash('sha256', $rawPayload));

                $e['status'] = 'assinado';
                $e['assinatura'] = [
                    'imagem_base64' => $assinaturaBase64,
                    'data_hora' => $agora,
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'hash_autenticidade' => $hashIntegridade,
                    'tipo_assinatura' => 'Assinatura Eletrônica Avançada / Biometria Touch (Portaria MTP 672/2021 / Lei 14.063/2020)',
                    'amparo_legal' => 'Art. 166 da CLT c/c Item 6.5.1 da NR-06 do Ministério do Trabalho e Emprego'
                ];

                $atualizado = $e;
                break;
            }
        }

        if ($atualizado) {
            $this->salvar($todos);
        }

        return $atualizado;
    }
}
