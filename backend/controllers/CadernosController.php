<?php

namespace App\Controllers;

/**
 * Cadernos do colaborador (manual, programas, políticas...).
 * Lista e entrega todo PDF colocado em backend/data/cadernos.
 */
class CadernosController
{
    private string $pasta;

    public function __construct()
    {
        $this->pasta = dirname(__DIR__) . '/data/cadernos';
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
                'atualizado_em' => date('d/m/Y', filemtime($caminho))
            ];
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

    /** "manual_do-colaborador.pdf" -> "Manual do colaborador" */
    private function tituloDoArquivo(string $arquivo): string
    {
        $nome = pathinfo($arquivo, PATHINFO_FILENAME);
        $nome = trim(preg_replace('/[\s_\-]+/', ' ', $nome));
        return mb_strtoupper(mb_substr($nome, 0, 1)) . mb_substr($nome, 1);
    }
}
