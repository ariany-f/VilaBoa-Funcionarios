<?php

namespace App\Services;

class ColaboradorService
{
    private string $dataFile;

    /** Empresas que o usuário do RH logado pode ver (null = todas). Só vale para o painel do RH. */
    private ?array $empresasPermitidas = null;

    public function __construct(?string $dataFile = null)
    {
        $this->dataFile = $dataFile ?: dirname(__DIR__) . '/data/colaboradores.json';
    }

    /**
     * Limita getAll() aos colaboradores dessas empresas (usuário do RH com acesso a só algumas).
     * Todo o painel do RH lê os colaboradores por aqui, então o filtro vale para resumo, seletores,
     * importação de ponto, desligamentos e homologações.
     */
    public function restringirEmpresas(?array $empresas): void
    {
        $this->empresasPermitidas = $empresas === null ? null : array_values($empresas);
    }

    /** Colaboradores visíveis (respeita restringirEmpresas). */
    public function getAll(): array
    {
        $todos = $this->lerTodos();
        if ($this->empresasPermitidas === null) {
            return $todos;
        }
        return array_values(array_filter($todos, fn($c) => in_array(EmpresaService::doColaborador($c), $this->empresasPermitidas, true)));
    }

    /** O colaborador está entre os visíveis para o usuário logado? */
    public function visivel(string $matricula): bool
    {
        foreach ($this->getAll() as $c) {
            if (($c['matricula'] ?? '') === $matricula) return true;
        }
        return false;
    }

    /** Todos os colaboradores do arquivo, sem filtro (para gravar sem perder ninguém). */
    private function lerTodos(): array
    {
        if (!file_exists($this->dataFile)) {
            return [];
        }
        $content = file_get_contents($this->dataFile);
        return json_decode($content, true) ?: [];
    }

    private function salvar(array $colaboradores): bool
    {
        return file_put_contents(
            $this->dataFile,
            json_encode($colaboradores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ) !== false;
    }

    // ================================================================ Cadastro pelo RH

    public static function ativo(array $c): bool
    {
        return ($c['ativo'] ?? true) !== false;
    }

    /** Colaboradores para a tela do RH (respeita o filtro de empresas do usuário). */
    public function listarRh(): array
    {
        $lista = array_map(fn($c) => [
            'id' => (int)($c['id'] ?? 0),
            'matricula' => $c['matricula'] ?? '',
            'nome' => $c['nome'] ?? '',
            'cpf' => $c['cpf_formatado'] ?? self::formatarCpf((string)($c['cpf'] ?? '')),
            'data_nascimento' => $c['data_nascimento'] ?? '',
            'cargo' => $c['cargo'] ?? '',
            'departamento' => $c['departamento'] ?? '',
            'empresa' => EmpresaService::doColaborador($c),
            'data_admissao' => $c['data_admissao'] ?? '',
            'data_demissao' => $c['data_demissao'] ?? '',
            'rg_primeiros_3_digitos' => (string)($c['rg_primeiros_3_digitos'] ?? ''),
            'nome_mae' => $c['nome_mae'] ?? '',
            'telefone_atual' => $c['telefone_atual'] ?? '',
            'telefone_confirmado_em' => $c['telefone_confirmado_em'] ?? null,
            'email' => $c['email'] ?? '',
            'ativo' => self::ativo($c),
            'alterado_por' => $c['alterado_por'] ?? null,
            'alterado_em' => $c['alterado_em'] ?? null,
        ], $this->getAll());
        usort($lista, fn($a, $b) => strcmp($a['nome'], $b['nome']));
        return $lista;
    }

    /**
     * Cria (sem matrícula existente) ou altera um colaborador. A matrícula não muda depois
     * de criada: holerites, EPIs, ponto e desligamentos são ligados a ela.
     * $empresasPermitidas: empresas que o usuário do RH pode usar (null = todas).
     */
    public function salvarRh(array $d, bool $novo, string $autor, ?array $empresasPermitidas): array
    {
        $texto = fn($v, $max = 120) => mb_substr(trim(preg_replace('/\s+/', ' ', (string)$v)), 0, $max);
        $matricula = mb_strtoupper($texto($d['matricula'] ?? '', 20));
        $nome = $texto($d['nome'] ?? '');
        $cpf = preg_replace('/\D/', '', (string)($d['cpf'] ?? ''));
        $nascimento = (string)($d['data_nascimento'] ?? '');
        $admissao = (string)($d['data_admissao'] ?? '');
        $demissao = trim((string)($d['data_demissao'] ?? ''));
        $empresa = EmpresaService::normalizar((string)($d['empresa'] ?? ''));
        $rg = preg_replace('/\D/', '', (string)($d['rg_primeiros_3_digitos'] ?? ''));
        $telefone = preg_replace('/\D/', '', (string)($d['telefone_atual'] ?? ''));
        $email = mb_strtolower(trim((string)($d['email'] ?? '')));

        if ($matricula === '' || !preg_match('/^[A-Z0-9][A-Z0-9.\-\/]*$/', $matricula)) return ['erro' => 'Informe a matrícula (letras, números, ponto ou hífen).', 'status' => 422];
        if (mb_strlen($nome) < 3) return ['erro' => 'Informe o nome completo.', 'status' => 422];
        if (strlen($cpf) !== 11) return ['erro' => 'Informe o CPF com 11 dígitos.', 'status' => 422];
        if (!self::dataValida($nascimento)) return ['erro' => 'Informe a data de nascimento.', 'status' => 422];
        if (!$empresa) return ['erro' => 'Escolha a empresa.', 'status' => 422];
        if ($empresasPermitidas !== null && !in_array($empresa, $empresasPermitidas, true)) return ['erro' => 'Você não tem acesso a esta empresa.', 'status' => 403];
        if (!self::dataValida($admissao)) return ['erro' => 'Informe a data de admissão (usada na pergunta de segurança do login).', 'status' => 422];
        if ($demissao !== '' && !self::dataValida($demissao)) return ['erro' => 'Data de demissão inválida.', 'status' => 422];
        if (strlen($rg) < 3) return ['erro' => 'Informe os 3 primeiros dígitos do RG (usados na pergunta de segurança do login).', 'status' => 422];
        if ($telefone !== '' && !preg_match('/^\d{10,11}$/', $telefone)) return ['erro' => 'Telefone inválido: use DDD + número.', 'status' => 422];
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) return ['erro' => 'E-mail inválido.', 'status' => 422];

        $resposta = null;
        $this->transacao(function (array &$todos) use (&$resposta, $novo, $matricula, $nome, $cpf, $nascimento, $admissao, $demissao, $empresa, $rg, $telefone, $email, $d, $texto, $autor, $empresasPermitidas) {
            $indice = null;
            foreach ($todos as $i => $c) {
                $mesmaMatricula = strcasecmp((string)($c['matricula'] ?? ''), $matricula) === 0;
                if ($mesmaMatricula) $indice = $i;
                if (!$mesmaMatricula && preg_replace('/\D/', '', (string)($c['cpf'] ?? '')) === $cpf) {
                    $resposta = ['erro' => 'Este CPF já está cadastrado para ' . ($c['nome'] ?? '') . ' (' . ($c['matricula'] ?? '') . ').', 'status' => 409];
                    return;
                }
            }
            if ($novo && $indice !== null) { $resposta = ['erro' => 'Já existe um colaborador com esta matrícula.', 'status' => 409]; return; }
            if (!$novo && $indice === null) { $resposta = ['erro' => 'Colaborador não encontrado.', 'status' => 404]; return; }
            if (!$novo && $empresasPermitidas !== null && !in_array(EmpresaService::doColaborador($todos[$indice]), $empresasPermitidas, true)) {
                $resposta = ['erro' => 'Você não tem acesso a este colaborador.', 'status' => 403];
                return;
            }

            $atual = $novo ? ['id' => max(array_merge([0], array_map(fn($c) => (int)($c['id'] ?? 0), $todos))) + 1, 'matricula' => $matricula, 'ativo' => true, 'telefone_confirmado_em' => null] : $todos[$indice];
            $telefoneFormatado = $telefone === '' ? '' : self::formatarTelefone($telefone);
            // Telefone trocado pelo RH volta a pedir a confirmação do colaborador no próximo acesso
            if (($atual['telefone_atual'] ?? '') !== $telefoneFormatado) $atual['telefone_confirmado_em'] = null;
            $atual = array_merge($atual, [
                'cpf' => $cpf,
                'cpf_formatado' => self::formatarCpf($cpf),
                'data_nascimento' => $nascimento,
                'data_nascimento_br' => implode('/', array_reverse(explode('-', $nascimento))),
                'nome' => $nome,
                'cargo' => $texto($d['cargo'] ?? ''),
                'departamento' => $texto($d['departamento'] ?? ''),
                'empresa' => $empresa,
                'data_admissao' => $admissao,
                'ano_admissao' => substr($admissao, 0, 4),
                'rg_primeiros_3_digitos' => substr($rg, 0, 3),
                'nome_mae' => $texto($d['nome_mae'] ?? ''),
                'telefone_atual' => $telefoneFormatado,
                'email' => $email,
                'alterado_por' => $autor,
                'alterado_em' => date('Y-m-d H:i:s'),
            ]);
            if ($demissao !== '') $atual['data_demissao'] = $demissao; else unset($atual['data_demissao']);
            if ($novo) $todos[] = $atual; else $todos[$indice] = $atual;
            $resposta = ['sucesso' => true, 'matricula' => $matricula];
        });
        return $resposta;
    }

    /** Inativa (bloqueia o acesso ao portal) ou reativa. Os dados e históricos continuam. */
    public function definirAtivoRh(string $matricula, bool $ativo, string $autor): array
    {
        if (!$this->visivel($matricula)) return ['erro' => 'Colaborador não encontrado.', 'status' => 404];
        $resposta = ['erro' => 'Colaborador não encontrado.', 'status' => 404];
        $this->transacao(function (array &$todos) use ($matricula, $ativo, $autor, &$resposta) {
            foreach ($todos as $i => $c) {
                if (($c['matricula'] ?? '') !== $matricula) continue;
                $todos[$i]['ativo'] = $ativo;
                $todos[$i]['alterado_por'] = $autor;
                $todos[$i]['alterado_em'] = date('Y-m-d H:i:s');
                $resposta = ['sucesso' => true];
                return;
            }
        });
        return $resposta;
    }

    private static function dataValida(string $iso): bool
    {
        return (bool)preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1]) && $m[1] >= 1900;
    }

    private static function formatarCpf(string $cpf): string
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        return strlen($cpf) === 11 ? substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9) : $cpf;
    }

    private static function formatarTelefone(string $tel): string
    {
        return strlen($tel) === 11
            ? '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7)
            : '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6);
    }

    /** Lê, altera e grava o colaboradores.json inteiro (sem filtro de empresa) com trava. */
    private function transacao(callable $fn): void
    {
        $lock = fopen($this->dataFile . '.lock', 'c');
        flock($lock, LOCK_EX);
        try {
            $todos = $this->lerTodos();
            $antes = $todos;
            $fn($todos);
            if ($todos !== $antes) {
                file_put_contents($this->dataFile, json_encode(array_values($todos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function autenticar(string $cpf, string $dataNascimento): ?array
    {
        $cpfLimpo = preg_replace('/\D/', '', $cpf);
        $dataNascimentoLimpa = trim($dataNascimento);

        // Se veio no formato DD/MM/AAAA, converte para YYYY-MM-DD
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dataNascimentoLimpa)) {
            $partes = explode('/', $dataNascimentoLimpa);
            $dataIso = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
        } else {
            $dataIso = $dataNascimentoLimpa;
        }

        $colaboradores = $this->getAll();

        foreach ($colaboradores as $c) {
            $cpfColab = preg_replace('/\D/', '', $c['cpf']);
            if ($cpfColab === $cpfLimpo) {
                // Checa tanto ISO quanto BR
                if ($c['data_nascimento'] === $dataIso || ($c['data_nascimento_br'] ?? '') === $dataNascimentoLimpa) {
                    return $c;
                }
            }
        }

        return null;
    }

    /**
     * Sorteia uma pergunta de segurança baseada em dados pessoais do colaborador.
     * Gera opções plausíveis no estilo Gov.br / Serasa para UX rápida em smartphones.
     * Permite excluir o tipo de pergunta anterior para que, em caso de erro, a validação mude obrigatoriamente.
     */
    public function gerarPerguntaSeguranca(array $colaborador, ?string $tipoAnterior = null): array
    {
        $perguntasPossiveis = [
            'ano_admissao',
            'rg_primeiros_3_digitos',
            'nome_mae',
            'dia_nascimento',
            'mes_nascimento',
            'cargo'
        ];

        // Se uma validação anterior foi informada (ex: errou a pergunta anterior), exclui para mudar obrigatoriamente
        if ($tipoAnterior) {
            $filtradas = array_values(array_filter($perguntasPossiveis, fn($t) => $t !== $tipoAnterior));
            if (!empty($filtradas)) {
                $perguntasPossiveis = $filtradas;
            }
        }

        // Sorteia uma das perguntas
        $tipo = $perguntasPossiveis[array_rand($perguntasPossiveis)];

        switch ($tipo) {
            case 'ano_admissao':
                $correto = (string)$colaborador['ano_admissao'];
                $opcoes = [
                    $correto,
                    (string)((int)$correto - 1),
                    (string)((int)$correto + 1),
                    (string)((int)$correto - 2)
                ];
                shuffle($opcoes);
                return [
                    'tipo' => 'ano_admissao',
                    'enunciado' => 'Em qual ano você foi admitido na Vila Boa Construções?',
                    'dica' => 'Selecione o ano constante no seu registro profissional',
                    'opcoes' => array_values(array_unique($opcoes)),
                    'resposta_esperada_hash' => hash('sha256', $correto . '_VB_SALT_')
                ];

            case 'rg_primeiros_3_digitos':
                $correto = (string)$colaborador['rg_primeiros_3_digitos'];
                $opcoes = [
                    $correto,
                    str_pad((string)((int)$correto + 15), 3, '0', STR_PAD_LEFT),
                    str_pad((string)abs((int)$correto - 27), 3, '0', STR_PAD_LEFT),
                    str_pad((string)((int)$correto + 42), 3, '0', STR_PAD_LEFT)
                ];
                shuffle($opcoes);
                return [
                    'tipo' => 'rg_primeiros_3_digitos',
                    'enunciado' => 'Quais são os 3 primeiros dígitos do seu documento de RG?',
                    'dica' => 'Confirme a numeração inicial do seu documento oficial',
                    'opcoes' => array_values(array_unique($opcoes)),
                    'resposta_esperada_hash' => hash('sha256', $correto . '_VB_SALT_')
                ];

            case 'nome_mae':
                $correto = $colaborador['nome_mae'];
                $nomesFalsos = [
                    'Maria Aparecida da Silva',
                    'Francisca Helena de Oliveira',
                    'Terezinha de Jesus Ferreira',
                    'Lourdes Conceição Ribeiro'
                ];
                // Pega 3 falsos diferentes do verdadeiro
                $falsos = array_values(array_filter($nomesFalsos, fn($n) => $n !== $correto));
                $opcoes = array_slice($falsos, 0, 3);
                $opcoes[] = $correto;
                shuffle($opcoes);
                return [
                    'tipo' => 'nome_mae',
                    'enunciado' => 'Qual é o nome completo da sua mãe?',
                    'dica' => 'Informação constante na sua filiação de cadastro',
                    'opcoes' => array_values($opcoes),
                    'resposta_esperada_hash' => hash('sha256', $correto . '_VB_SALT_')
                ];

            case 'mes_nascimento':
                $meses = [
                    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                ];
                $numMes = (int)date('m', strtotime($colaborador['data_nascimento']));
                $correto = $meses[$numMes] ?? 'Abril';
                $outrosMeses = array_values(array_filter($meses, fn($m) => $m !== $correto));
                shuffle($outrosMeses);
                $opcoes = array_slice($outrosMeses, 0, 3);
                $opcoes[] = $correto;
                shuffle($opcoes);
                return [
                    'tipo' => 'mes_nascimento',
                    'enunciado' => 'Em qual mês você faz aniversário?',
                    'dica' => 'Selecione o mês correto do seu nascimento',
                    'opcoes' => array_values($opcoes),
                    'resposta_esperada_hash' => hash('sha256', $correto . '_VB_SALT_')
                ];

            case 'cargo':
                $correto = $colaborador['cargo'];
                $outrosCargos = [
                    'Pedreiro Especializado',
                    'Ajudante Geral de Obras',
                    'Eletricista de Manutenção',
                    'Carpinteiro de Fôrmas',
                    'Armador de Estrutura'
                ];
                $falsos = array_values(array_filter($outrosCargos, fn($c) => $c !== $correto));
                shuffle($falsos);
                $opcoes = array_slice($falsos, 0, 3);
                $opcoes[] = $correto;
                shuffle($opcoes);
                return [
                    'tipo' => 'cargo',
                    'enunciado' => 'Qual é a sua função/cargo registrado na Vila Boa?',
                    'dica' => 'Selecione o cargo registrado no seu contrato de trabalho',
                    'opcoes' => array_values($opcoes),
                    'resposta_esperada_hash' => hash('sha256', $correto . '_VB_SALT_')
                ];

            case 'dia_nascimento':
            default:
                $dia = date('d', strtotime($colaborador['data_nascimento']));
                $opcoes = [
                    $dia,
                    str_pad((string)((((int)$dia + 3) % 28) + 1), 2, '0', STR_PAD_LEFT),
                    str_pad((string)((((int)$dia + 8) % 28) + 1), 2, '0', STR_PAD_LEFT),
                    str_pad((string)((((int)$dia + 14) % 28) + 1), 2, '0', STR_PAD_LEFT)
                ];
                shuffle($opcoes);
                return [
                    'tipo' => 'dia_nascimento',
                    'enunciado' => 'Qual é o dia do mês em que você nasceu?',
                    'dica' => 'Selecione apenas o dia correto do seu aniversário',
                    'opcoes' => array_values(array_unique($opcoes)),
                    'resposta_esperada_hash' => hash('sha256', $dia . '_VB_SALT_')
                ];
        }
    }

    /**
     * Valida a resposta do desafio.
     */
    public function validarRespostaSeguranca(string $resposta, string $respostaHashEsperada): bool
    {
        $hashInformado = hash('sha256', trim($resposta) . '_VB_SALT_');
        return hash_equals($respostaHashEsperada, $hashInformado);
    }

    /**
     * OBJETIVO PRIMÁRIO: Atualiza e confirma o telefone do colaborador.
     */
    /**
     * Telefone novo não é trocado na hora: vira uma solicitação que o líder da equipe
     * conclui (a tarefa é aberta no uMov). Fica em data/alteracoes_telefone.json.
     */
    public function solicitarAlteracaoTelefone(array $colaborador, string $novoTelefone): array
    {
        $arquivo = dirname($this->dataFile) . '/alteracoes_telefone.json';
        $solicitacoes = file_exists($arquivo) ? (json_decode(file_get_contents($arquivo), true) ?: []) : [];
        $solicitacao = [
            'protocolo' => 'TEL-' . date('Ymd-His') . '-' . $colaborador['id'],
            'colaborador_id' => $colaborador['id'],
            'matricula' => $colaborador['matricula'],
            'nome' => $colaborador['nome'],
            'telefone_atual' => $colaborador['telefone_atual'],
            'telefone_novo' => $novoTelefone,
            'status' => 'aguardando_lider',
            'criado_em' => date('Y-m-d H:i:s')
        ];
        $solicitacoes[] = $solicitacao;
        file_put_contents($arquivo, json_encode($solicitacoes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $solicitacao;
    }

    public function confirmarTelefone(int $colaboradorId, string $novoTelefone): ?array
    {
        $colaboradores = $this->lerTodos();
        $atualizado = null;

        foreach ($colaboradores as &$c) {
            if ((int)$c['id'] === $colaboradorId) {
                $c['telefone_atual'] = $novoTelefone;
                $c['telefone_confirmado_em'] = date('Y-m-d H:i:s');
                $atualizado = $c;
                break;
            }
        }

        if ($atualizado) {
            $this->salvar($colaboradores);
        }

        return $atualizado;
    }

    public function findById(int $id): ?array
    {
        $colaboradores = $this->getAll();
        foreach ($colaboradores as $c) {
            if ((int)$c['id'] === $id) {
                return $c;
            }
        }
        return null;
    }
}
