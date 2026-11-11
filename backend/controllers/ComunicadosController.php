<?php

namespace App\Controllers;

use App\Services\EmpresaService;

/**
 * Comunicados: mural de avisos internos, campanhas, prazos e mudanças de rotina.
 * O RH cria (rascunho, agendado ou publicado); o colaborador vê os publicados, dentro da janela
 * (publicar_em até validade), da empresa e do público dele. Fica em data/comunicados.json.
 *
 * Texto: guardado como texto simples com marcação leve — **negrito**, linhas "- item" e [texto](https://link).
 * Nunca guarda HTML: o backend remove tags e links inseguros; o portal escapa e converte ao exibir.
 *
 * Campos: titulo, texto, categoria, prioridade, fixado, empresa, publico ('todos'|'departamentos'|'colaboradores') + valores,
 * status ('rascunho'|'publicado'|'arquivado'), publicar_em ('Y-m-d H:i:s'), validade ('Y-m-d' ou vazio).
 */
class ComunicadosController
{
    private string $arquivo;

    public const CATEGORIAS = ['Aviso', 'Campanha', 'Prazo', 'Mudança de rotina'];
    public const PRIORIDADES = ['normal', 'importante'];
    public const PUBLICOS = ['todos', 'departamentos', 'colaboradores'];
    public const STATUS = ['rascunho', 'publicado', 'arquivado'];

    public function __construct()
    {
        $this->arquivo = dirname(__DIR__) . '/data/comunicados.json';
    }

    /** Comunicados que o colaborador vê. */
    public function listarParaColaborador(array $colaborador): array
    {
        $agora = date('Y-m-d H:i:s');
        $hoje = date('Y-m-d');
        $empresa = EmpresaService::doColaborador($colaborador);
        $lista = array_filter($this->todos(), function ($c) use ($agora, $hoje, $empresa, $colaborador) {
            if (($c['status'] ?? 'publicado') !== 'publicado') return false;
            if (($c['publicar_em'] ?? '') > $agora) return false;
            if (!empty($c['validade']) && $c['validade'] < $hoje) return false;
            if (($c['empresa'] ?? 'todas') !== 'todas' && EmpresaService::normalizar($c['empresa']) !== $empresa) return false;
            return match ($c['publico'] ?? 'todos') {
                'departamentos' => in_array($colaborador['departamento'] ?? '', $c['valores'] ?? [], true),
                'colaboradores' => in_array((string)($colaborador['matricula'] ?? ''), $c['valores'] ?? [], true),
                default => true,
            };
        });
        // O colaborador não precisa ver quem é o público nem o rascunho de edição
        return array_map(function ($c) {
            unset($c['valores'], $c['autor']);
            return $c;
        }, $this->ordenar(array_values($lista)));
    }

    /** Todos, para o RH, com a situação calculada: rascunho | agendado | publicado | vencido | arquivado. */
    public function listarParaRh(): array
    {
        return array_map(fn($c) => $this->comSituacao($c), $this->ordenar($this->todos()));
    }

    public function salvar(array $dados, string $autor): array
    {
        $titulo = trim(preg_replace('/\s+/', ' ', strip_tags((string)($dados['titulo'] ?? ''))));
        $texto = self::limparTexto((string)($dados['texto'] ?? ''));
        if ($titulo === '' || $texto === '') {
            return ['erro' => 'Informe o título e o texto do comunicado.', 'status' => 422];
        }
        $validade = (string)($dados['validade'] ?? '');
        if ($validade !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $validade)) {
            return ['erro' => 'Data de validade inválida.', 'status' => 422];
        }
        $publicarEm = self::normalizarDataHora((string)($dados['publicar_em'] ?? ''));
        if (!empty($dados['publicar_em']) && $publicarEm === '') {
            return ['erro' => 'Data de publicação inválida.', 'status' => 422];
        }
        if ($validade !== '' && $publicarEm !== '' && $validade < substr($publicarEm, 0, 10)) {
            return ['erro' => 'A validade não pode ser antes da publicação.', 'status' => 422];
        }
        $publico = in_array($dados['publico'] ?? '', self::PUBLICOS, true) ? $dados['publico'] : 'todos';
        $valores = $publico === 'todos' ? [] : array_values(array_unique(array_filter(array_map('strval', (array)($dados['valores'] ?? [])))));
        if ($publico !== 'todos' && !$valores) {
            return ['erro' => 'Escolha ao menos um ' . ($publico === 'departamentos' ? 'departamento' : 'colaborador') . ' ou selecione todos.', 'status' => 422];
        }
        $status = in_array($dados['status'] ?? '', ['rascunho', 'publicado'], true) ? $dados['status'] : 'publicado';

        $campos = [
            'titulo' => mb_substr($titulo, 0, 120),
            'texto' => $texto,
            'categoria' => in_array($dados['categoria'] ?? '', self::CATEGORIAS, true) ? $dados['categoria'] : 'Aviso',
            'prioridade' => in_array($dados['prioridade'] ?? '', self::PRIORIDADES, true) ? $dados['prioridade'] : 'normal',
            'fixado' => !empty($dados['fixado']),
            'validade' => $validade,
            'empresa' => EmpresaService::normalizar($dados['empresa'] ?? null) ?? 'todas',
            'publico' => $publico,
            'valores' => $valores,
            'status' => $status,
        ];

        $todos = $this->todos();
        $id = (string)($dados['id'] ?? '');
        $comunicado = null;
        foreach ($todos as &$c) {
            if ($id !== '' && $c['id'] === $id) {
                $jaPublicado = ($c['status'] ?? 'publicado') === 'publicado';
                $campos['publicar_em'] = $status === 'rascunho' ? $publicarEm : ($publicarEm ?: ($jaPublicado && !empty($c['publicar_em']) && $c['publicar_em'] <= date('Y-m-d H:i:s') ? $c['publicar_em'] : date('Y-m-d H:i:s')));
                $c = array_merge($c, $campos, ['publicado_em' => $campos['publicar_em'] ?: ($c['publicado_em'] ?? date('Y-m-d H:i:s')), 'atualizado_em' => date('Y-m-d H:i:s')]);
                $comunicado = $c;
                break;
            }
        }
        unset($c);
        if (!$comunicado) {
            $campos['publicar_em'] = $status === 'rascunho' ? $publicarEm : ($publicarEm ?: date('Y-m-d H:i:s'));
            $comunicado = ['id' => 'COM-' . date('YmdHis') . '-' . random_int(100, 999), 'publicado_em' => $campos['publicar_em'] ?: date('Y-m-d H:i:s'), 'autor' => $autor] + $campos;
            $todos[] = $comunicado;
        }
        if (!$this->gravar($todos)) {
            return ['erro' => 'O servidor não conseguiu gravar o comunicado. Peça para a TI liberar a escrita em backend/data.', 'status' => 500];
        }
        return ['sucesso' => true, 'comunicado' => $this->comSituacao($comunicado)];
    }

    /** Ações rápidas da lista: fixar, desafixar, arquivar, reativar e duplicar (cópia vira rascunho). */
    public function acao(string $id, string $acao, string $autor): array
    {
        $todos = $this->todos();
        $indice = null;
        foreach ($todos as $i => $c) {
            if ($c['id'] === $id) { $indice = $i; break; }
        }
        if ($indice === null) return ['erro' => 'Comunicado não encontrado.', 'status' => 404];

        switch ($acao) {
            case 'fixar':
            case 'desafixar':
                $todos[$indice]['fixado'] = $acao === 'fixar';
                break;
            case 'arquivar':
                $todos[$indice]['status'] = 'arquivado';
                break;
            case 'reativar':
                $todos[$indice]['status'] = 'publicado';
                if (empty($todos[$indice]['publicar_em'])) $todos[$indice]['publicar_em'] = date('Y-m-d H:i:s');
                break;
            case 'duplicar':
                $copia = $todos[$indice];
                $copia['id'] = 'COM-' . date('YmdHis') . '-' . random_int(100, 999);
                $copia['titulo'] = mb_substr('Cópia de ' . $copia['titulo'], 0, 120);
                $copia['status'] = 'rascunho';
                $copia['fixado'] = false;
                $copia['publicar_em'] = '';
                $copia['validade'] = '';
                $copia['publicado_em'] = date('Y-m-d H:i:s');
                $copia['autor'] = $autor;
                unset($copia['atualizado_em']);
                $todos[] = $copia;
                $resultado = $copia;
                break;
            default:
                return ['erro' => 'Ação inválida.', 'status' => 422];
        }
        if (!$this->gravar($todos)) {
            return ['erro' => 'O servidor não conseguiu gravar a alteração. Peça para a TI liberar a escrita em backend/data.', 'status' => 500];
        }
        return ['sucesso' => true, 'comunicado' => $this->comSituacao($resultado ?? $todos[$indice])];
    }

    public function remover(string $id): array
    {
        $todos = $this->todos();
        $restantes = array_values(array_filter($todos, fn($c) => $c['id'] !== $id));
        if (count($restantes) === count($todos)) return ['erro' => 'Comunicado não encontrado.', 'status' => 404];
        if (!$this->gravar($restantes)) {
            return ['erro' => 'O servidor não conseguiu gravar a alteração. Peça para a TI liberar a escrita em backend/data.', 'status' => 500];
        }
        return ['sucesso' => true];
    }

    /** Texto seguro: sem tags HTML, sem caracteres de controle, links só http(s)/mailto. */
    public static function limparTexto(string $texto): string
    {
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $texto);
        $texto = strip_tags($texto);
        $texto = str_replace(['<', '>'], ['&lt;', '&gt;'], $texto);
        $texto = preg_replace_callback('/\[([^\]\n]{1,200})\]\(([^)\s]*)\)/u', function ($m) {
            return preg_match('#^(https?://|mailto:)[^\s<>"\']+$#i', $m[2]) ? $m[0] : $m[1];
        }, $texto);
        $texto = preg_replace("/\n{3,}/", "\n\n", trim($texto));
        return mb_substr($texto, 0, 5000);
    }

    private static function normalizarDataHora(string $v): string
    {
        $v = trim(str_replace('T', ' ', $v));
        if ($v === '') return '';
        if (!preg_match('/^(\d{4}-\d{2}-\d{2})(?: (\d{2}):(\d{2})(?::(\d{2}))?)?$/', $v, $m)) return '';
        if (!checkdate((int)substr($m[1], 5, 2), (int)substr($m[1], 8, 2), (int)substr($m[1], 0, 4))) return '';
        return sprintf('%s %02d:%02d:%02d', $m[1], (int)($m[2] ?? 0), (int)($m[3] ?? 0), (int)($m[4] ?? 0));
    }

    private function comSituacao(array $c): array
    {
        $status = $c['status'] ?? 'publicado';
        $c['status'] = $status;
        $c['publico'] = $c['publico'] ?? 'todos';
        $c['valores'] = $c['valores'] ?? [];
        $c['publicar_em'] = $c['publicar_em'] ?? ($c['publicado_em'] ?? '');
        $hoje = date('Y-m-d');
        $c['vencido'] = !empty($c['validade']) && $c['validade'] < $hoje;
        $c['situacao'] = match (true) {
            $status === 'rascunho' => 'rascunho',
            $status === 'arquivado' => 'arquivado',
            $c['vencido'] => 'vencido',
            $c['publicar_em'] > date('Y-m-d H:i:s') => 'agendado',
            default => 'publicado',
        };
        return $c;
    }

    /** Fixados primeiro, depois os mais recentes. */
    private function ordenar(array $lista): array
    {
        $data = fn($c) => ($c['publicar_em'] ?? '') ?: ($c['publicado_em'] ?? '');
        usort($lista, fn($a, $b) => (!empty($b['fixado']) <=> !empty($a['fixado'])) ?: strcmp($data($b), $data($a)));
        return $lista;
    }

    private function todos(): array
    {
        return file_exists($this->arquivo) ? (json_decode(file_get_contents($this->arquivo), true) ?: []) : [];
    }

    private function gravar(array $lista): bool
    {
        return @file_put_contents($this->arquivo, json_encode(array_values($lista), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
    }
}
