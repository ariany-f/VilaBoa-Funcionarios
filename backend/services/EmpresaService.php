<?php

namespace App\Services;

/**
 * Empresas do grupo (Vila Boa, Mondego...), cadastradas pelo RH em data/empresas.json.
 * O colaborador guarda o nome da empresa no campo "empresa" de colaboradores.json
 * e o caderno no campo "empresa" de cadernos_config.json ('todas' = caderno global).
 */
class EmpresaService
{
    public const PADRAO = ['Vila Boa', 'Mondego'];

    /** Siglas antigas gravadas antes do cadastro de empresas. */
    private const APELIDOS = ['VB' => 'Vila Boa'];

    private static function dataDir(): string
    {
        return dirname(__DIR__) . '/data';
    }

    public static function listar(): array
    {
        $arquivo = self::dataDir() . '/empresas.json';
        $lista = file_exists($arquivo) ? (json_decode(file_get_contents($arquivo), true) ?: []) : self::PADRAO;
        return array_values($lista);
    }

    /** Nome da empresa como está no cadastro (aceita siglas antigas), ou null. */
    public static function normalizar(?string $nome): ?string
    {
        $nome = self::APELIDOS[$nome ?? ''] ?? $nome;
        foreach (self::listar() as $e) {
            if (mb_strtolower($e) === mb_strtolower(trim((string)$nome))) return $e;
        }
        return null;
    }

    /** Empresa do colaborador; sem empresa no cadastro, vale a primeira (Vila Boa). */
    public static function doColaborador(array $colaborador): string
    {
        return self::normalizar($colaborador['empresa'] ?? null) ?? (self::listar()[0] ?? '');
    }

    public static function adicionar(string $nome): array
    {
        $nome = trim(preg_replace('/\s+/', ' ', $nome));
        if ($nome === '' || mb_strlen($nome) > 60) return ['erro' => 'Informe um nome de até 60 caracteres.'];
        if (mb_strtolower($nome) === 'todas') return ['erro' => 'Escolha outro nome.'];
        if (self::normalizar($nome)) return ['erro' => 'Essa empresa já existe.'];
        $lista = self::listar();
        $lista[] = $nome;
        self::salvar($lista);
        return ['sucesso' => true, 'empresas' => $lista];
    }

    /** Renomeia e leva junto os colaboradores e cadernos que usavam o nome antigo. */
    public static function renomear(string $atual, string $novo): array
    {
        $atual = self::normalizar($atual);
        $novo = trim(preg_replace('/\s+/', ' ', $novo));
        if (!$atual) return ['erro' => 'Empresa não encontrada.'];
        if ($novo === '' || mb_strlen($novo) > 60) return ['erro' => 'Informe um nome de até 60 caracteres.'];
        if (mb_strtolower($novo) === 'todas') return ['erro' => 'Escolha outro nome.'];
        $existente = self::normalizar($novo);
        if ($existente && $existente !== $atual) return ['erro' => 'Essa empresa já existe.'];

        $lista = array_map(fn($e) => $e === $atual ? $novo : $e, self::listar());
        self::salvar($lista);

        $colaboradores = self::lerJson('colaboradores.json');
        foreach ($colaboradores as &$c) {
            if (self::normalizarEm($c['empresa'] ?? null, $atual)) $c['empresa'] = $novo;
        }
        self::gravarJson('colaboradores.json', $colaboradores);

        $configs = self::lerJson('cadernos_config.json');
        foreach ($configs as &$cfg) {
            if (self::normalizarEm($cfg['empresa'] ?? null, $atual)) $cfg['empresa'] = $novo;
        }
        self::gravarJson('cadernos_config.json', $configs);

        return ['sucesso' => true, 'empresas' => $lista];
    }

    public static function remover(string $nome): array
    {
        $nome = self::normalizar($nome);
        if (!$nome) return ['erro' => 'Empresa não encontrada.'];
        $lista = self::listar();
        if (count($lista) === 1) return ['erro' => 'É preciso ter ao menos uma empresa.'];
        $colabs = array_filter(self::lerJson('colaboradores.json'), fn($c) => self::doColaborador($c) === $nome);
        if ($colabs) return ['erro' => 'Há ' . count($colabs) . ' colaborador(es) nesta empresa. Mude a empresa deles antes de remover.'];
        $cads = array_filter(self::lerJson('cadernos_config.json'), fn($cfg) => self::normalizarEm($cfg['empresa'] ?? null, $nome));
        if ($cads) return ['erro' => 'Há ' . count($cads) . ' caderno(s) desta empresa. Mude a empresa deles antes de remover.'];
        $lista = array_values(array_filter($lista, fn($e) => $e !== $nome));
        self::salvar($lista);
        return ['sucesso' => true, 'empresas' => $lista];
    }

    /** Troca a empresa de um colaborador (pela matrícula). */
    public static function definirDoColaborador(string $matricula, string $empresa): array
    {
        $empresa = self::normalizar($empresa);
        if (!$empresa) return ['erro' => 'Empresa não encontrada.'];
        $colaboradores = self::lerJson('colaboradores.json');
        foreach ($colaboradores as &$c) {
            if ($c['matricula'] === $matricula) {
                $c['empresa'] = $empresa;
                self::gravarJson('colaboradores.json', $colaboradores);
                return ['sucesso' => true, 'empresa' => $empresa];
            }
        }
        return ['erro' => 'Colaborador não encontrado.'];
    }

    private static function normalizarEm(?string $valor, string $nome): bool
    {
        return $valor !== null && $valor !== 'todas' && (self::APELIDOS[$valor] ?? $valor) === $nome;
    }

    private static function salvar(array $lista): void
    {
        self::gravarJson('empresas.json', array_values($lista));
    }

    private static function lerJson(string $nome): array
    {
        $arquivo = self::dataDir() . '/' . $nome;
        return file_exists($arquivo) ? (json_decode(file_get_contents($arquivo), true) ?: []) : [];
    }

    private static function gravarJson(string $nome, array $dados): void
    {
        file_put_contents(self::dataDir() . '/' . $nome, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}
