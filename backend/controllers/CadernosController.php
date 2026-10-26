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

    public const CATEGORIAS = ['Manuais', 'Programas', 'Políticas', 'Segurança do Trabalho', 'Treinamentos', 'Outros'];

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
            'categoria' => in_array($dados['categoria'] ?? '', self::CATEGORIAS, true) ? $dados['categoria'] : 'Outros',
            'obrigatorio' => !empty($dados['obrigatorio']),
            'publico' => $publico,
            'valores' => $publico === 'todos' ? [] : array_values(array_filter(array_map('strval', (array)($dados['valores'] ?? []))))
        ];
        $todas = $this->lerJson($this->configFile);
        $todas[basename($arquivo)] = $config;
        file_put_contents($this->configFile, json_encode($todas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $config;
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

        $fp = fopen($this->leiturasFile, 'c+');
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
