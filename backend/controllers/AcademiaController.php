<?php

namespace App\Controllers;

use App\Services\EmpresaService;

/**
 * Academia Vila Boa: trilhas de aprendizagem com cadernos (PDF) e vídeos.
 *
 *  - Trilha obrigatória: vale para todos os colaboradores da empresa (ex.: Integração de segurança).
 *  - Trilha por função: vale só para os cargos listados em 'funcoes'.
 *  - Ao concluir a trilha, o colaborador assina e o sistema emite o certificado, assinado
 *    digitalmente pela empresa (selo HMAC do servidor) e pelo colaborador (registro eletrônico).
 *
 * Dados em backend/data: academia_trilhas.json, academia_progresso.json, academia_certificados.json.
 * A conclusão de um caderno vem das leituras do CadernosController; a de um vídeo, de academia_progresso.json.
 */
class AcademiaController
{
    private string $trilhasFile;
    private string $progressoFile;
    private string $certificadosFile;
    private string $segredoFile;

    public const SIGNATARIO_PADRAO = ['nome' => 'Vila Boa Construções e Serviços LTDA', 'cargo' => 'Departamento de Pessoal / RH'];

    public function __construct(private CadernosController $cadernos)
    {
        $dir = dirname(__DIR__) . '/data';
        $this->trilhasFile = $dir . '/academia_trilhas.json';
        $this->progressoFile = $dir . '/academia_progresso.json';
        $this->certificadosFile = $dir . '/academia_certificados.json';
        $this->segredoFile = $dir . '/academia_segredo.txt';
    }

    // ---------------------------------------------------------------- colaborador

    /** Trilhas do colaborador (obrigatórias + da função dele), com progresso e certificado. */
    public function listarParaColaborador(array $colaborador): array
    {
        $cadernos = [];
        foreach ($this->cadernos->listarComLeitura($colaborador) as $c) {
            $cadernos[$c['arquivo']] = $c;
        }
        $progresso = $this->ler($this->progressoFile);
        $certificados = $this->ler($this->certificadosFile);
        $matricula = $colaborador['matricula'];

        $saida = [];
        foreach ($this->trilhas() as $t) {
            if (!($t['publicada'] ?? true) || !$this->visivelPara($colaborador, $t)) continue; // rascunho não aparece para o colaborador
            $itens = [];
            foreach ($t['itens'] as $i) {
                $feito = null;
                if ($i['tipo'] === 'video') {
                    $feito = $progresso[$this->chaveItem($matricula, $t['id'], $i['id'])]['data_hora'] ?? null;
                } else {
                    $c = $cadernos[$i['arquivo'] ?? ''] ?? null;
                    if (!$c) continue; // caderno removido ou fora do público do colaborador
                    $feito = $c['leitura']['data_hora'] ?? null;
                    $i['titulo'] = $i['titulo'] !== '' ? $i['titulo'] : $c['titulo'];
                    $i['paginas'] = $c['paginas'] ?? null;
                    $i['capa'] = $c['capa'] ?? null;
                }
                $i['concluido_em'] = $feito;
                $itens[] = $i;
            }
            $t['itens'] = $itens;
            $t['total'] = count($itens);
            $t['concluidos'] = count(array_filter($itens, fn($i) => $i['concluido_em']));
            $t['completa'] = $t['total'] > 0 && $t['concluidos'] === $t['total'];
            $t['certificado'] = $certificados[$this->chaveCertificado($matricula, $t['id'])] ?? null;
            $t['capa'] = $this->versaoDaCapa($t['id']);
            $saida[] = $t;
        }
        // Obrigatórias primeiro, e dentro de cada grupo as que ainda faltam concluir
        usort($saida, fn($a, $b) => (!empty($b['obrigatoria']) <=> !empty($a['obrigatoria'])) ?: ((int)$a['completa'] <=> (int)$b['completa']) ?: strcasecmp($a['titulo'], $b['titulo']));
        return $saida;
    }

    /** Todos os certificados do colaborador (mesmo de trilhas que o RH removeu depois), do mais recente ao mais antigo. */
    public function certificadosDoColaborador(string $matricula): array
    {
        $lista = array_values(array_filter($this->ler($this->certificadosFile), fn($c) => ($c['matricula'] ?? '') === $matricula));
        usort($lista, fn($a, $b) => strcmp($this->dataIso($b['emitido_em'] ?? ''), $this->dataIso($a['emitido_em'] ?? '')));
        return $lista;
    }

    /** "10/11/2026 14:00:00" -> "2026-11-10 14:00:00" (para ordenar). */
    private function dataIso(string $br): string
    {
        return preg_match('#^(\d{2})/(\d{2})/(\d{4}) (.+)$#', $br, $m) ? "{$m[3]}-{$m[2]}-{$m[1]} {$m[4]}" : $br;
    }

    /** Marca um vídeo da trilha como assistido. */
    public function concluirVideo(array $colaborador, string $trilhaId, string $itemId, string $ip): array
    {
        $trilha = $this->trilhaVisivel($colaborador, $trilhaId);
        if (!$trilha) return ['erro' => 'Trilha não encontrada.', 'status' => 404];
        $video = null;
        foreach ($trilha['itens'] as $i) {
            if ($i['id'] === $itemId && $i['tipo'] === 'video') $video = $i;
        }
        if (!$video) return ['erro' => 'Vídeo não encontrado nesta trilha.', 'status' => 404];

        $chave = $this->chaveItem($colaborador['matricula'], $trilhaId, $itemId);
        $ok = $this->atualizar($this->progressoFile, function (array $p) use ($chave, $colaborador, $trilhaId, $itemId, $ip) {
            $p[$chave] ??= ['matricula' => $colaborador['matricula'], 'trilha' => $trilhaId, 'item' => $itemId, 'data_hora' => date('d/m/Y H:i:s'), 'ip' => $ip];
            return $p;
        });
        if (!$ok) return ['erro' => 'Não foi possível gravar o progresso no servidor. Avise o RH.', 'status' => 500];
        return ['sucesso' => true];
    }

    /**
     * Emite o certificado da trilha concluída. A assinatura do colaborador é o aceite eletrônico
     * (IP, data/hora e navegador, amarrados por hash); a da empresa é um selo HMAC do servidor.
     * Idempotente: se já foi emitido, devolve o mesmo.
     */
    public function emitirCertificado(array $colaborador, string $trilhaId, string $ip, string $userAgent): array
    {
        $trilha = null;
        foreach ($this->listarParaColaborador($colaborador) as $t) {
            if ($t['id'] === $trilhaId) $trilha = $t;
        }
        if (!$trilha) return ['erro' => 'Trilha não encontrada.', 'status' => 404];
        if (!$trilha['completa']) return ['erro' => 'Conclua todas as etapas da trilha para emitir o certificado.', 'status' => 422];
        if ($trilha['certificado']) return ['sucesso' => true, 'certificado' => $trilha['certificado']];

        $chave = $this->chaveCertificado($colaborador['matricula'], $trilhaId);
        $certificado = null;
        $ok = $this->atualizar($this->certificadosFile, function (array $todos) use (&$certificado, $chave, $colaborador, $trilha, $ip, $userAgent) {
            if (isset($todos[$chave])) { $certificado = $todos[$chave]; return $todos; }
            $agora = date('d/m/Y H:i:s');
            $signatario = $this->signatario();
            $codigo = strtoupper(substr(hash('sha256', $chave . '|' . $agora . '|' . random_bytes(8)), 0, 20));
            $dados = [
                'codigo' => $codigo,
                'matricula' => $colaborador['matricula'],
                'nome' => $colaborador['nome'],
                'trilha_id' => $trilha['id'],
                'trilha' => $trilha['titulo'],
                'carga_horaria' => $trilha['carga_horaria'] ?? '',
                'etapas' => $trilha['total'],
                'concluida_em' => $agora,
            ];
            $payload = json_encode($dados, JSON_UNESCAPED_UNICODE);
            $certificado = $dados + [
                'emitido_em' => $agora,
                'assinatura_empresa' => [
                    'signatario' => $signatario['nome'],
                    'cargo' => $signatario['cargo'],
                    'data_hora' => $agora,
                    'selo' => strtoupper(hash_hmac('sha256', $payload, $this->segredo())),
                ],
                'assinatura_colaborador' => [
                    'nome' => $colaborador['nome'],
                    'matricula' => $colaborador['matricula'],
                    'data_hora' => $agora,
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'selo' => strtoupper(hash('sha256', $payload . '|' . $colaborador['matricula'] . '|' . $agora . '|' . $ip . '|' . $userAgent)),
                ],
            ];
            $todos[$chave] = $certificado;
            return $todos;
        });
        if (!$ok || !$certificado) return ['erro' => 'Não foi possível gravar o certificado no servidor. Avise o RH.', 'status' => 500];
        return ['sucesso' => true, 'certificado' => $certificado];
    }

    /** Validação pública pelo código impresso no certificado (confere o selo da empresa). */
    public function validarCertificado(string $codigo): ?array
    {
        $codigo = strtoupper(trim($codigo));
        foreach ($this->ler($this->certificadosFile) as $c) {
            if (($c['codigo'] ?? '') !== $codigo) continue;
            $dados = array_intersect_key($c, array_flip(['codigo', 'matricula', 'nome', 'trilha_id', 'trilha', 'carga_horaria', 'etapas', 'concluida_em']));
            $selo = strtoupper(hash_hmac('sha256', json_encode($dados, JSON_UNESCAPED_UNICODE), $this->segredo()));
            return [
                'valido' => hash_equals($selo, $c['assinatura_empresa']['selo'] ?? ''),
                'nome' => $c['nome'],
                'trilha' => $c['trilha'],
                'carga_horaria' => $c['carga_horaria'],
                'concluida_em' => $c['concluida_em'],
                'emitido_em' => $c['emitido_em'],
            ];
        }
        return null;
    }

    // ---------------------------------------------------------------- RH

    public function listarParaRh(): array
    {
        $certificados = $this->ler($this->certificadosFile);
        return array_map(function ($t) use ($certificados) {
            $t['certificados_emitidos'] = count(array_filter($certificados, fn($c) => ($c['trilha_id'] ?? '') === $t['id']));
            $t['capa'] = $this->versaoDaCapa($t['id']);
            return $t;
        }, $this->trilhas());
    }

    public function salvar(array $dados): array
    {
        $titulo = trim(preg_replace('/\s+/', ' ', (string)($dados['titulo'] ?? '')));
        if ($titulo === '') return ['erro' => 'Informe o nome da trilha.', 'status' => 422];

        $obrigatoria = !empty($dados['obrigatoria']);
        $funcoes = array_values(array_unique(array_filter(array_map(fn($f) => trim((string)$f), (array)($dados['funcoes'] ?? [])))));
        $publicada = !array_key_exists('publicada', $dados) || !empty($dados['publicada']);
        if ($publicada && !$obrigatoria && !$funcoes) {
            return ['erro' => 'Informe ao menos uma função ou marque a trilha como obrigatória para todos.', 'status' => 422];
        }

        $itens = [];
        foreach ((array)($dados['itens'] ?? []) as $i) {
            $tipo = ($i['tipo'] ?? '') === 'video' ? 'video' : 'caderno';
            $item = ['id' => preg_replace('/[^a-z0-9]/i', '', (string)($i['id'] ?? '')) ?: ('i' . bin2hex(random_bytes(4))), 'tipo' => $tipo, 'titulo' => mb_substr(trim((string)($i['titulo'] ?? '')), 0, 120), 'descricao' => mb_substr(trim((string)($i['descricao'] ?? '')), 0, 300)];
            if ($tipo === 'video') {
                $url = trim((string)($i['url'] ?? ''));
                if (!preg_match('#^https://#i', $url)) return ['erro' => 'O link do vídeo precisa começar com https://', 'status' => 422];
                if ($item['titulo'] === '') return ['erro' => 'Informe o título de cada vídeo.', 'status' => 422];
                $item['url'] = $url;
                $item['duracao'] = mb_substr(trim((string)($i['duracao'] ?? '')), 0, 20);
            } else {
                $arquivo = basename((string)($i['arquivo'] ?? ''));
                if (!$this->cadernos->caminhoDoArquivo($arquivo)) return ['erro' => 'Caderno não encontrado: ' . $arquivo, 'status' => 422];
                $item['arquivo'] = $arquivo;
            }
            // Pontos opcionais da etapa; sem valor, vale o padrão (todas as etapas com o mesmo peso)
            $pts = $this->pontos($i['pontos'] ?? null);
            if ($pts !== null) $item['pontos'] = $pts;
            $itens[] = $item;
        }
        if ($publicada && !$itens) return ['erro' => 'Adicione ao menos um caderno ou vídeo à trilha.', 'status' => 422];

        $campos = [
            'titulo' => mb_substr($titulo, 0, 120),
            'descricao' => mb_substr(trim((string)($dados['descricao'] ?? '')), 0, 600),
            'obrigatoria' => $obrigatoria,
            // Rascunho: o RH monta com calma e só publica quando estiver pronta
            'publicada' => $publicada,
            'funcoes' => $obrigatoria ? [] : $funcoes,
            'empresa' => EmpresaService::normalizar($dados['empresa'] ?? null) ?? 'todas',
            'carga_horaria' => mb_substr(trim((string)($dados['carga_horaria'] ?? '')), 0, 20),
            // Prazo opcional para concluir (AAAA-MM-DD); vazio = sem prazo
            'prazo' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($dados['prazo'] ?? '')) ? $dados['prazo'] : '',
            'itens' => $itens,
        ];
        // Pontos opcionais ao concluir a trilha; vazio = padrão
        $campos['pontos'] = $this->pontos($dados['pontos'] ?? null);

        $trilha = null;
        $id = (string)($dados['id'] ?? '');
        $trilhas = $this->trilhas();
        foreach ($trilhas as &$t) {
            if ($id !== '' && $t['id'] === $id) { $t = array_merge($t, $campos); $trilha = $t; break; }
        }
        unset($t);
        if (!$trilha) {
            $trilha = ['id' => 'TR' . date('ymdHis') . random_int(10, 99)] + $campos;
            $trilhas[] = $trilha;
        }
        if (!$this->gravar($this->trilhasFile, $trilhas)) {
            return ['erro' => 'O servidor não conseguiu gravar a trilha. Peça para a TI liberar a escrita em backend/data.', 'status' => 500];
        }
        return ['sucesso' => true, 'trilha' => $trilha + ['capa' => $this->versaoDaCapa($trilha['id'])]];
    }

    public function remover(string $id): array
    {
        $trilhas = $this->trilhas();
        $restantes = array_values(array_filter($trilhas, fn($t) => $t['id'] !== $id));
        if (count($restantes) === count($trilhas)) return ['erro' => 'Trilha não encontrada.', 'status' => 404];
        if (!$this->gravar($this->trilhasFile, $restantes)) {
            return ['erro' => 'O servidor não conseguiu gravar a alteração. Peça para a TI liberar a escrita em backend/data.', 'status' => 500];
        }
        $this->removerCapa($id);
        return ['sucesso' => true];
    }

    // ---------------------------------------------------------------- capas (data/academia_capas/<id>.<ext>)

    private const CAPA_TIPOS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const CAPA_TAMANHO_MAXIMO = 3 * 1024 * 1024;

    private function pastaCapas(): string
    {
        return dirname(__DIR__) . '/data/academia_capas';
    }

    private function idSeguro(string $id): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', $id);
    }

    /** Caminho da capa da trilha, ou null. */
    public function caminhoDaCapa(string $id): ?string
    {
        $id = $this->idSeguro($id);
        if ($id === '') return null;
        foreach (self::CAPA_TIPOS as $ext) {
            $caminho = $this->pastaCapas() . '/' . $id . '.' . $ext;
            if (is_file($caminho)) return $caminho;
        }
        return null;
    }

    /** Versão da capa (data do arquivo) para o navegador atualizar a imagem; null = sem capa. */
    public function versaoDaCapa(string $id): ?string
    {
        $c = $this->caminhoDaCapa($id);
        return $c ? (string)filemtime($c) : null;
    }

    /** Salva (ou troca) a capa da trilha. Aceita JPG, PNG ou WEBP de até 3 MB. */
    public function salvarCapa(string $id, array $imagem): array
    {
        $id = $this->idSeguro($id);
        if (!array_filter($this->trilhas(), fn($t) => $t['id'] === $id)) return ['erro' => 'Trilha não encontrada.', 'status' => 404];
        if (($imagem['error'] ?? 1) !== UPLOAD_ERR_OK) return ['erro' => 'Falha no envio da imagem.'];
        if (($imagem['size'] ?? 0) > self::CAPA_TAMANHO_MAXIMO) return ['erro' => 'A imagem da capa pode ter no máximo 3 MB.'];
        $info = @getimagesize($imagem['tmp_name']);
        $ext = $info ? (self::CAPA_TIPOS[$info['mime']] ?? null) : null;
        if (!$ext) return ['erro' => 'Envie a capa em JPG, PNG ou WEBP.'];

        if (!is_dir($this->pastaCapas())) @mkdir($this->pastaCapas(), 0775, true);
        $this->removerCapa($id);
        $destino = $this->pastaCapas() . '/' . $id . '.' . $ext;
        if (!is_writable($this->pastaCapas()) || !move_uploaded_file($imagem['tmp_name'], $destino)) {
            return ['erro' => 'O servidor não tem permissão para gravar a capa. Peça para a TI liberar a escrita em backend/data.'];
        }
        return ['sucesso' => true, 'capa' => (string)filemtime($destino)];
    }

    public function removerCapa(string $id): void
    {
        $caminho = $this->caminhoDaCapa($id);
        if ($caminho) @unlink($caminho);
    }

    // ---------------------------------------------------------------- internos

    private function visivelPara(array $colaborador, array $trilha): bool
    {
        $empresa = $trilha['empresa'] ?? 'todas';
        if ($empresa !== 'todas' && EmpresaService::doColaborador($colaborador) !== EmpresaService::normalizar($empresa)) return false;
        if (!empty($trilha['obrigatoria'])) return true;
        $cargo = mb_strtolower(trim((string)($colaborador['cargo'] ?? '')));
        foreach ($trilha['funcoes'] ?? [] as $f) {
            if (mb_strtolower(trim($f)) === $cargo) return true;
        }
        return false;
    }

    private function trilhaVisivel(array $colaborador, string $id): ?array
    {
        foreach ($this->trilhas() as $t) {
            if ($t['id'] === $id && ($t['publicada'] ?? true) && $this->visivelPara($colaborador, $t)) return $t;
        }
        return null;
    }

    /** Pontos configurados pelo RH (0 a 1000) ou null quando não foi configurado. */
    private function pontos($v): ?int
    {
        if ($v === null || $v === '' || !is_numeric($v) || $v < 0) return null;
        return min(1000, (int)$v);
    }

    private function trilhas(): array
    {
        return array_values($this->ler($this->trilhasFile));
    }

    private function signatario(): array
    {
        $config = $this->ler(dirname(__DIR__) . '/data/academia_config.json');
        return array_merge(self::SIGNATARIO_PADRAO, array_intersect_key($config['signatario'] ?? [], self::SIGNATARIO_PADRAO));
    }

    /** Chave secreta do selo da empresa: gerada uma vez e guardada no servidor. */
    private function segredo(): string
    {
        if (is_file($this->segredoFile)) return trim((string)file_get_contents($this->segredoFile));
        $segredo = bin2hex(random_bytes(32));
        @file_put_contents($this->segredoFile, $segredo, LOCK_EX);
        return $segredo;
    }

    private function chaveItem(string $matricula, string $trilha, string $item): string
    {
        return $matricula . '|' . $trilha . '|' . $item;
    }

    private function chaveCertificado(string $matricula, string $trilha): string
    {
        return $matricula . '|' . $trilha;
    }

    private function ler(string $arquivo): array
    {
        return is_file($arquivo) ? (json_decode(file_get_contents($arquivo), true) ?: []) : [];
    }

    private function gravar(string $arquivo, array $dados): bool
    {
        return @file_put_contents($arquivo, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
    }

    /** Lê, altera e grava o JSON com trava, para dois acessos simultâneos não perderem dados. */
    private function atualizar(string $arquivo, callable $alterar): bool
    {
        $fp = @fopen($arquivo, 'c+');
        if (!$fp) return false;
        flock($fp, LOCK_EX);
        $atual = json_decode(stream_get_contents($fp), true) ?: [];
        $novo = $alterar($atual);
        ftruncate($fp, 0);
        rewind($fp);
        $ok = fwrite($fp, json_encode($novo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
        flock($fp, LOCK_UN);
        fclose($fp);
        return $ok;
    }
}
