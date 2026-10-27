<?php

namespace App\Controllers;

/**
 * Cadernos do colaborador (manual, programas, políticas...).
 * Lista e entrega todo PDF colocado em backend/data/cadernos.
 */
class CadernosController
{
    private string $pasta;
    private string $leiturasFile;
    private string $configFile;

    /** Categorias iniciais (o RH cadastra outras em data/cadernos_categorias.json). */
    public const CATEGORIAS_PADRAO = ['Manuais', 'Programas', 'Políticas', 'Segurança do Trabalho', 'Treinamentos', 'Outros'];

    public function __construct()
    {
        $this->pasta = dirname(__DIR__) . '/data/cadernos';
        $this->leiturasFile = dirname(__DIR__) . '/data/cadernos_leituras.json';
        $this->configFile = dirname(__DIR__) . '/data/cadernos_config.json';
    }

    /**
     * Configuração de cada caderno (definida pelo RH):
     *  categoria   - agrupamento exibido ao colaborador
     *  obrigatorio - leitura obrigatória para o público ou apenas opcional
     *  publico     - 'todos' | 'departamentos' | 'colaboradores', com a lista em 'valores'
     */
    public function config(string $arquivo): array
    {
        $todas = $this->lerJson($this->configFile);
        return array_merge(
            ['categoria' => 'Outros', 'obrigatorio' => false, 'publico' => 'todos', 'valores' => []],
            $todas[$arquivo] ?? []
        );
    }

    public function salvarConfig(string $arquivo, array $dados): ?array
    {
        if (!$this->caminhoDoArquivo($arquivo)) {
            return null;
        }
        $publico = in_array($dados['publico'] ?? '', ['todos', 'departamentos', 'colaboradores'], true) ? $dados['publico'] : 'todos';
        $config = [
            'categoria' => in_array($dados['categoria'] ?? '', $this->categorias(), true) ? $dados['categoria'] : 'Outros',
            'obrigatorio' => !empty($dados['obrigatorio']),
            'publico' => $publico,
            'valores' => $publico === 'todos' ? [] : array_values(array_filter(array_map('strval', (array)($dados['valores'] ?? []))))
        ];
        $todas = $this->lerJson($this->configFile);
        $todas[basename($arquivo)] = $config;
        file_put_contents($this->configFile, json_encode($todas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $config;
    }

    public function categorias(): array
    {
        $arquivo = dirname(__DIR__) . '/data/cadernos_categorias.json';
        $lista = file_exists($arquivo) ? (json_decode(file_get_contents($arquivo), true) ?: []) : self::CATEGORIAS_PADRAO;
        if (!in_array('Outros', $lista, true)) $lista[] = 'Outros';
        return array_values($lista);
    }

    public function adicionarCategoria(string $nome): array
    {
        $nome = trim(preg_replace('/\s+/', ' ', $nome));
        if ($nome === '' || mb_strlen($nome) > 40) {
            return ['erro' => 'Informe um nome de até 40 caracteres.'];
        }
        $lista = $this->categorias();
        foreach ($lista as $c) {
            if (mb_strtolower($c) === mb_strtolower($nome)) return ['erro' => 'Essa categoria já existe.'];
        }
        $lista[] = $nome;
        usort($lista, fn($a, $b) => $a === 'Outros' ? 1 : ($b === 'Outros' ? -1 : strcasecmp($a, $b)));
        $this->salvarCategorias($lista);
        return ['sucesso' => true, 'categorias' => $lista];
    }

    public function removerCategoria(string $nome): array
    {
        if (!in_array($nome, $this->categorias(), true)) return ['erro' => 'Categoria não encontrada.'];
        if ($nome === 'Outros') return ['erro' => 'A categoria "Outros" não pode ser removida.'];
        $emUso = array_filter($this->listar(), fn($c) => $c['categoria'] === $nome);
        if ($emUso) return ['erro' => 'Há ' . count($emUso) . ' caderno(s) nesta categoria. Mude a categoria deles antes de remover.'];
        $lista = array_values(array_filter($this->categorias(), fn($c) => $c !== $nome));
        $this->salvarCategorias($lista);
        return ['sucesso' => true, 'categorias' => $lista];
    }

    private function salvarCategorias(array $lista): void
    {
        file_put_contents(dirname(__DIR__) . '/data/cadernos_categorias.json', json_encode(array_values($lista), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /** O colaborador faz parte do público deste caderno? */
    public function visivelPara(array $colaborador, array $caderno): bool
    {
        return match ($caderno['publico']) {
            'departamentos' => in_array($colaborador['departamento'] ?? '', $caderno['valores'], true),
            'colaboradores' => in_array($colaborador['matricula'], $caderno['valores'], true),
            default => true,
        };
    }

    /** Cadernos que o colaborador pode ver, com a confirmação de leitura dele. */
    public function listarComLeitura(array $colaborador): array
    {
        $leituras = $this->lerLeituras();
        $visiveis = array_filter($this->listar(), fn($c) => $this->visivelPara($colaborador, $c));
        return array_values(array_map(function ($c) use ($colaborador, $leituras) {
            $c['leitura'] = $leituras[$this->chave($colaborador['matricula'], $c['arquivo'], $c['hash'])] ?? null;
            return $c;
        }, $visiveis));
    }

    /**
     * Registra "li e estou ciente". A leitura fica amarrada ao hash do arquivo:
     * se o PDF for substituído por uma nova versão, a leitura precisa ser feita de novo.
     */
    public function confirmarLeitura(array $colaborador, string $arquivo, string $ip, string $userAgent): ?array
    {
        $caminho = $this->caminhoDoArquivo($arquivo);
        if (!$caminho) {
            return null;
        }

        $hashArquivo = hash_file('sha256', $caminho);
        $chave = $this->chave($colaborador['matricula'], basename($caminho), $hashArquivo);

        $fp = @fopen($this->leiturasFile, 'c+');
        if (!$fp) {
            // Normalmente falta permissão de escrita do servidor web em backend/data
            throw new \RuntimeException('Não foi possível gravar a leitura no servidor.');
        }
        flock($fp, LOCK_EX);
        $leituras = json_decode(stream_get_contents($fp), true) ?: [];

        if (empty($leituras[$chave])) {
            $agora = date('d/m/Y H:i:s');
            $leituras[$chave] = [
                'matricula' => $colaborador['matricula'],
                'arquivo' => basename($caminho),
                'hash_arquivo' => $hashArquivo,
                'data_hora' => $agora,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'comprovante' => strtoupper(hash('sha256', $colaborador['matricula'] . '|' . $hashArquivo . '|' . $agora . '|' . $ip))
            ];
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($leituras, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        flock($fp, LOCK_UN);
        fclose($fp);

        return $leituras[$chave];
    }

    /** Todas as leituras válidas (da versão atual de cada arquivo), por matrícula. */
    public function leiturasPorMatricula(): array
    {
        $atuais = [];
        foreach ($this->listar() as $c) {
            $atuais[$c['arquivo']] = $c['hash'];
        }
        $porMatricula = [];
        foreach ($this->lerLeituras() as $l) {
            if (($atuais[$l['arquivo']] ?? null) === $l['hash_arquivo']) {
                $porMatricula[$l['matricula']][$l['arquivo']] = $l;
            }
        }
        return $porMatricula;
    }

    /** Salva um PDF enviado pelo RH. */
    public function salvarUpload(array $arquivoEnviado): array
    {
        if (($arquivoEnviado['error'] ?? 1) !== UPLOAD_ERR_OK) {
            return ['erro' => 'Falha no envio do arquivo.'];
        }
        $nome = basename($arquivoEnviado['name']);
        $nome = preg_replace('#[\\\\/:*?"<>|]+#', '', $nome);
        if (strtolower(pathinfo($nome, PATHINFO_EXTENSION)) !== 'pdf') {
            return ['erro' => 'Envie apenas arquivos PDF.'];
        }
        $cabecalho = file_get_contents($arquivoEnviado['tmp_name'], false, null, 0, 5);
        if ($cabecalho !== '%PDF-') {
            return ['erro' => 'O arquivo enviado não é um PDF válido.'];
        }
        if (!is_dir($this->pasta)) {
            mkdir($this->pasta, 0775, true);
        }
        move_uploaded_file($arquivoEnviado['tmp_name'], $this->pasta . '/' . $nome);
        return ['sucesso' => true, 'arquivo' => $nome];
    }

    public function remover(string $arquivo): bool
    {
        $caminho = $this->caminhoDoArquivo($arquivo);
        return $caminho ? unlink($caminho) : false;
    }

    private function chave(string $matricula, string $arquivo, string $hash): string
    {
        return $matricula . '|' . $arquivo . '|' . $hash;
    }

    private function lerLeituras(): array
    {
        return $this->lerJson($this->leiturasFile);
    }

    private function lerJson(string $arquivo): array
    {
        if (!file_exists($arquivo)) {
            return [];
        }
        return json_decode(file_get_contents($arquivo), true) ?: [];
    }

    public function listar(): array
    {
        if (!is_dir($this->pasta)) {
            return [];
        }

        $cadernos = [];
        foreach (glob($this->pasta . '/*.{pdf,PDF}', GLOB_BRACE) ?: [] as $caminho) {
            $arquivo = basename($caminho);
            $cadernos[] = [
                'arquivo' => $arquivo,
                'titulo' => $this->tituloDoArquivo($arquivo),
                'tamanho_bytes' => filesize($caminho),
                'hash' => hash_file('sha256', $caminho),
                'paginas' => $this->contarPaginas($caminho),
                'atualizado_em' => date('d/m/Y', filemtime($caminho))
            ] + $this->config($arquivo);
        }

        usort($cadernos, fn($a, $b) => strcasecmp($a['titulo'], $b['titulo']));
        return $cadernos;
    }

    /** Caminho seguro do PDF pedido, ou null se não existir dentro da pasta. */
    public function caminhoDoArquivo(string $arquivo): ?string
    {
        $arquivo = basename($arquivo);
        if (strtolower(pathinfo($arquivo, PATHINFO_EXTENSION)) !== 'pdf') {
            return null;
        }

        $caminho = realpath($this->pasta . '/' . $arquivo);
        $base = realpath($this->pasta);
        if (!$caminho || !$base || strpos($caminho, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($caminho)) {
            return null;
        }
        return $caminho;
    }

    /** Contagem aproximada de páginas lendo os objetos /Type /Page do PDF. */
    private function contarPaginas(string $caminho): ?int
    {
        $total = preg_match_all('#/Type\s*/Page(?![a-zA-Z])#', file_get_contents($caminho));
        return $total ?: null;
    }

    /** "manual_do-colaborador.pdf" -> "Manual do colaborador" */
    private function tituloDoArquivo(string $arquivo): string
    {
        $nome = pathinfo($arquivo, PATHINFO_FILENAME);
        $nome = trim(preg_replace('/[\s_\-]+/', ' ', $nome));
        return mb_strtoupper(mb_substr($nome, 0, 1)) . mb_substr($nome, 1);
    }
}
