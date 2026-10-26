<?php
/**
 * Os arquivos de backend/data são gravados pelo portal (recebimentos, assinaturas,
 * leituras...) e por isso não ficam no git. Numa instalação nova, copia de
 * backend/data/exemplos tudo o que ainda não existir. Nunca sobrescreve dados.
 */
function prepararDadosIniciais(): void
{
    $dados = dirname(__DIR__) . '/data';
    $exemplos = $dados . '/exemplos';
    if (!is_dir($exemplos)) {
        return;
    }

    $itens = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($exemplos, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($itens as $item) {
        $destino = $dados . substr($item->getPathname(), strlen($exemplos));
        if ($item->isDir()) {
            if (!is_dir($destino)) {
                mkdir($destino, 0775, true);
            }
        } elseif (!file_exists($destino) && $item->getFilename() !== '.gitkeep') {
            copy($item->getPathname(), $destino);
        }
    }
}
