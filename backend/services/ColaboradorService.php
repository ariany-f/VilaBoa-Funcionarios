<?php

namespace App\Services;

class ColaboradorService
{
    private string $dataFile;

    public function __construct(?string $dataFile = null)
    {
        $this->dataFile = $dataFile ?: dirname(__DIR__) . '/data/colaboradores.json';
    }

    public function getAll(): array
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
        $colaboradores = $this->getAll();
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
