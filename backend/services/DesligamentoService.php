<?php

namespace App\Services;

require_once __DIR__ . '/EmpresaService.php';

/**
 * Alerta de desligamento no Portal do Colaborador.
 *
 * Quando chega a data de demissão, o colaborador vê um aviso (modal) com os links do Bookings (Outlook)
 * para agendar a homologação com o DP e a retirada da documentação, e o Guia de Desligamento da empresa.
 * Ele marca "Já agendei" em cada um; com os dois agendados o aviso some.
 *
 * Data de demissão, nesta ordem: campo data_demissao do cadastro do colaborador (integração),
 * desligamento registrado pelo RH no painel (data/desligamentos.json) ou a data informada
 * num agendamento de homologação sindical.
 */
class DesligamentoService
{
    public const LINK_HOMOLOGACAO = 'https://abrir.link/qjoqu';
    public const LINK_DOCUMENTOS = 'https://abrir.link/WNujP';
    private const TIPOS = ['homologacao' => 'homologacao_agendada_em', 'documentos' => 'documentos_agendados_em'];

    private string $arquivo;

    public function __construct(private ColaboradorService $colaboradores)
    {
        date_default_timezone_set('America/Sao_Paulo');
        $this->arquivo = dirname(__DIR__) . '/data/desligamentos.json';
    }

    /**
     * Links do Bookings da empresa. O RH cadastra por empresa no painel (data/desligamento_config.json);
     * sem cadastro, vale o padrão (variáveis VB_LINK_* ou as constantes acima).
     */
    public static function links(?string $empresa = null): array
    {
        $padrao = self::linksPadrao();
        $cfg = self::configuracoes()[$empresa ?? ''] ?? [];
        return [
            'homologacao' => ($cfg['homologacao'] ?? '') ?: $padrao['homologacao'],
            'documentos' => ($cfg['documentos'] ?? '') ?: $padrao['documentos'],
        ];
    }

    public static function linksPadrao(): array
    {
        return [
            'homologacao' => getenv('VB_LINK_HOMOLOGACAO') ?: self::LINK_HOMOLOGACAO,
            'documentos' => getenv('VB_LINK_DOCUMENTOS') ?: self::LINK_DOCUMENTOS,
        ];
    }

    // ---------------------------------------------------------------- links e guia por empresa (RH)

    private const GUIA_TAMANHO_MAXIMO = 10 * 1024 * 1024;

    private static function dataDir(): string
    {
        return dirname(__DIR__) . '/data';
    }

    private static function arquivoConfig(): string
    {
        return self::dataDir() . '/desligamento_config.json';
    }

    /** { "Vila Boa": { "homologacao": "https://...", "documentos": "https://..." } } */
    private static function configuracoes(): array
    {
        $arq = self::arquivoConfig();
        if (!is_file($arq)) return [];
        $d = json_decode((string)file_get_contents($arq), true);
        return is_array($d) ? $d : [];
    }

    private static function gravarConfiguracoes(array $cfg): bool
    {
        return file_put_contents(self::arquivoConfig(), json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
    }

    /** Links e guia de cada empresa, para a tela do RH. */
    public static function configuracaoPorEmpresa(): array
    {
        $cfg = self::configuracoes();
        $padrao = self::linksPadrao();
        $resultado = [];
        foreach (EmpresaService::listar() as $empresa) {
            $enviado = self::caminhoGuiaEnviado($empresa);
            $resultado[$empresa] = [
                'links' => self::links($empresa),
                'links_personalizados' => [
                    'homologacao' => !empty($cfg[$empresa]['homologacao']),
                    'documentos' => !empty($cfg[$empresa]['documentos']),
                ],
                'links_padrao' => $padrao,
                'guia' => [
                    'tem' => self::caminhoDoGuiaDaEmpresa($empresa) !== null,
                    // enviado = arquivo que o RH subiu; senão é o guia que vem com o sistema
                    'enviado' => $enviado !== null,
                    'atualizado_em' => $enviado ? date('Y-m-d H:i:s', filemtime($enviado)) : null,
                ],
            ];
        }
        return $resultado;
    }

    /** Grava os links de uma empresa. Link em branco volta ao padrão. */
    public static function salvarLinks(string $empresa, string $homologacao, string $documentos): array
    {
        $empresa = EmpresaService::normalizar($empresa);
        if (!$empresa) return ['erro' => 'Empresa não encontrada.', 'status' => 422];
        $links = ['homologacao' => trim($homologacao), 'documentos' => trim($documentos)];
        foreach ($links as $tipo => $url) {
            if ($url === '') continue;
            if (!preg_match('#^https?://#i', $url) || !filter_var($url, FILTER_VALIDATE_URL) || mb_strlen($url) > 500) {
                $nome = $tipo === 'homologacao' ? 'homologação' : 'retirada de documentos';
                return ['erro' => "O link de $nome precisa ser um endereço completo, começando com https://", 'status' => 422];
            }
        }
        $cfg = self::configuracoes();
        $cfg[$empresa] = array_filter($links) + ($cfg[$empresa] ?? []);
        foreach ($links as $tipo => $url) if ($url === '') unset($cfg[$empresa][$tipo]);
        if (!$cfg[$empresa]) unset($cfg[$empresa]);
        if (!self::gravarConfiguracoes($cfg)) return ['erro' => 'O servidor não tem permissão para gravar. Peça para a TI liberar a escrita em backend/data.', 'status' => 500];
        return ['sucesso' => true, 'links' => self::links($empresa)];
    }

    private static function pastaGuias(): string
    {
        return self::dataDir() . '/guias_desligamento';
    }

    /** Nome do arquivo sem caracteres problemáticos ("Vila Boa" -> "vila-boa"). */
    private static function baseDoArquivo(string $empresa): string
    {
        $base = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $empresa) ?: $empresa);
        return trim(preg_replace('/[^a-z0-9]+/', '-', $base), '-') ?: 'empresa';
    }

    private static function caminhoGuiaEnviado(string $empresa): ?string
    {
        $arq = self::pastaGuias() . '/' . self::baseDoArquivo($empresa) . '.pdf';
        return is_file($arq) ? $arq : null;
    }

    /** Guia que vem com o sistema (backend/guias), pelo nome da empresa. */
    private static function caminhoGuiaDoSistema(string $empresa): ?string
    {
        $arq = dirname(__DIR__) . '/guias/Guia de Desligamento - ' . preg_replace('/[\/\\\\:*?"<>|]/', '', $empresa) . '.pdf';
        return ($empresa !== '' && is_file($arq)) ? $arq : null;
    }

    /** Guia da empresa: o enviado pelo RH ou o que vem com o sistema. */
    public static function caminhoDoGuiaDaEmpresa(string $empresa): ?string
    {
        return self::caminhoGuiaEnviado($empresa) ?? self::caminhoGuiaDoSistema($empresa);
    }

    /** Maior arquivo que o PHP aceita receber (o menor entre o limite do guia e o do php.ini), em bytes. */
    public static function limiteUpload(): int
    {
        $bytes = function (string $v): int {
            $n = (int)$v;
            return match (strtolower(substr(trim($v), -1))) { 'g' => $n * 1024 ** 3, 'm' => $n * 1024 ** 2, 'k' => $n * 1024, default => $n };
        };
        $limites = array_filter([self::GUIA_TAMANHO_MAXIMO, $bytes((string)ini_get('upload_max_filesize')), $bytes((string)ini_get('post_max_size'))]);
        return min($limites);
    }

    private static function formatarTamanho(int $bytes): string
    {
        return $bytes >= 1024 ** 2 ? round($bytes / 1024 ** 2, 1) . ' MB' : round($bytes / 1024) . ' KB';
    }

    public static function salvarGuia(string $empresa, array $arquivo): array
    {
        $empresa = EmpresaService::normalizar($empresa);
        if (!$empresa) return ['erro' => 'Empresa não encontrada.', 'status' => 422];
        if (empty($arquivo['tmp_name']) || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $grande = in_array($arquivo['error'] ?? null, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
            $limite = self::formatarTamanho(self::limiteUpload());
            return ['erro' => $grande ? "O arquivo passou do limite do servidor ($limite). Peça para a TI aumentar upload_max_filesize e post_max_size do PHP." : 'Escolha o PDF do guia.', 'status' => 422];
        }
        if (($arquivo['size'] ?? 0) > self::GUIA_TAMANHO_MAXIMO) return ['erro' => 'O guia pode ter no máximo ' . self::formatarTamanho(self::GUIA_TAMANHO_MAXIMO) . '.', 'status' => 422];
        $inicio = (string)@file_get_contents($arquivo['tmp_name'], false, null, 0, 5);
        if ($inicio !== '%PDF-') return ['erro' => 'O guia precisa ser um arquivo PDF.', 'status' => 422];
        $pasta = self::pastaGuias();
        if (!is_dir($pasta)) @mkdir($pasta, 0775, true);
        $destino = $pasta . '/' . self::baseDoArquivo($empresa) . '.pdf';
        $ok = is_uploaded_file($arquivo['tmp_name']) ? move_uploaded_file($arquivo['tmp_name'], $destino) : @rename($arquivo['tmp_name'], $destino);
        if (!is_writable($pasta) || !$ok) {
            return ['erro' => 'O servidor não tem permissão para gravar o guia. Peça para a TI liberar a escrita em backend/data.', 'status' => 500];
        }
        return ['sucesso' => true];
    }

    /** Apaga o guia enviado pelo RH; volta a valer o que vem com o sistema (se houver). */
    public static function removerGuia(string $empresa): array
    {
        $empresa = EmpresaService::normalizar($empresa);
        if (!$empresa) return ['erro' => 'Empresa não encontrada.', 'status' => 422];
        $arq = self::caminhoGuiaEnviado($empresa);
        if (!$arq) return ['erro' => 'Esta empresa não tem guia enviado pelo RH.', 'status' => 404];
        @unlink($arq);
        return ['sucesso' => true];
    }

    /** Empresa renomeada no cadastro: leva junto os links e o guia enviado. */
    public static function aoRenomearEmpresa(string $atual, string $novo): void
    {
        $cfg = self::configuracoes();
        if (isset($cfg[$atual])) {
            $cfg[$novo] = $cfg[$atual];
            unset($cfg[$atual]);
            self::gravarConfiguracoes($cfg);
        }
        $guia = self::pastaGuias() . '/' . self::baseDoArquivo($atual) . '.pdf';
        if (is_file($guia)) @rename($guia, self::pastaGuias() . '/' . self::baseDoArquivo($novo) . '.pdf');
    }

    /** Empresa removida do cadastro: apaga os links e o guia enviado dela. */
    public static function aoRemoverEmpresa(string $empresa): void
    {
        $cfg = self::configuracoes();
        if (isset($cfg[$empresa])) {
            unset($cfg[$empresa]);
            self::gravarConfiguracoes($cfg);
        }
        $guia = self::pastaGuias() . '/' . self::baseDoArquivo($empresa) . '.pdf';
        if (is_file($guia)) @unlink($guia);
    }

    // ---------------------------------------------------------------- colaborador

    /** O que o portal do colaborador precisa para decidir se mostra o aviso. */
    public function paraColaborador(array $colab): array
    {
        $data = $this->dataDemissao($colab);
        $reg = $this->registro($colab['matricula']) ?? [];
        $homologacaoRh = $this->homologacaoDoRh($colab['matricula']);
        $homologacaoOk = !empty($reg['homologacao_agendada_em']) || $homologacaoRh !== null;
        $documentosOk = !empty($reg['documentos_agendados_em']);
        $chegou = $data !== null && $data <= date('Y-m-d');

        return [
            'mostrar' => $chegou && !($homologacaoOk && $documentosOk),
            'data_demissao' => $data,
            'empresa' => EmpresaService::doColaborador($colab),
            'links' => self::links(EmpresaService::doColaborador($colab)),
            'homologacao' => [
                'agendado' => $homologacaoOk,
                'agendado_em' => $reg['homologacao_agendada_em'] ?? null,
                // Se o RH já marcou a homologação no sindicato pelo painel, mostra os dados dela
                'pelo_rh' => $homologacaoRh ? ['data_hora' => $homologacaoRh['data_hora'], 'sindicato' => $homologacaoRh['sindicato'], 'local' => $homologacaoRh['local'] ?? ''] : null,
            ],
            'documentos' => ['agendado' => $documentosOk, 'agendado_em' => $reg['documentos_agendados_em'] ?? null],
            'tem_guia' => $this->caminhoDoGuia($colab) !== null,
        ];
    }

    /** O colaborador confirma que agendou pelo link (homologacao | documentos). Desfazer = false. */
    public function marcarAgendado(array $colab, string $tipo, bool $agendado = true): array
    {
        if (!isset(self::TIPOS[$tipo])) return ['erro' => 'Tipo inválido.', 'status' => 422];
        if ($this->dataDemissao($colab) === null) return ['erro' => 'Nenhum desligamento registrado.', 'status' => 404];
        $campo = self::TIPOS[$tipo];
        $this->transacao(function (array &$lista) use ($colab, $campo, $agendado) {
            $i = $this->indice($lista, $colab['matricula']);
            if ($i === null) {
                $lista[] = ['matricula' => $colab['matricula'], 'data_demissao' => null, 'origem' => 'colaborador'];
                $i = count($lista) - 1;
            }
            $lista[$i][$campo] = $agendado ? date('Y-m-d H:i:s') : null;
        });
        return ['sucesso' => true, 'desligamento' => $this->paraColaborador($colab)];
    }

    /** Guia da empresa do colaborador; sem guia da empresa, vale o da Vila Boa. */
    public function caminhoDoGuia(array $colab): ?string
    {
        $empresa = EmpresaService::doColaborador($colab);
        return self::caminhoDoGuiaDaEmpresa($empresa) ?? self::caminhoDoGuiaDaEmpresa('Vila Boa');
    }

    // ---------------------------------------------------------------- RH

    /** Colaboradores com data de demissão (de qualquer origem) e a situação dos agendamentos. */
    public function listar(): array
    {
        $lista = [];
        foreach ($this->colaboradores->getAll() as $c) {
            $data = $this->dataDemissao($c);
            if ($data === null) continue;
            $info = $this->paraColaborador($c);
            $reg = $this->registro($c['matricula']) ?? [];
            $lista[] = [
                'matricula' => $c['matricula'],
                'nome' => $c['nome'],
                'cargo' => $c['cargo'] ?? '',
                'empresa' => $info['empresa'],
                'data_demissao' => $data,
                'origem' => $this->origemDaData($c),
                'aviso_ativo' => $info['mostrar'],
                'homologacao' => $info['homologacao'],
                'documentos' => $info['documentos'],
                'registrado_por' => $reg['registrado_por'] ?? null,
            ];
        }
        usort($lista, fn($a, $b) => strcmp($b['data_demissao'], $a['data_demissao']));
        return $lista;
    }

    public function registrar(array $dados, string $autor): array
    {
        $matricula = (string)($dados['matricula'] ?? '');
        $colab = null;
        foreach ($this->colaboradores->getAll() as $c) {
            if ($c['matricula'] === $matricula) { $colab = $c; break; }
        }
        if (!$colab) return ['erro' => 'Escolha o colaborador.', 'status' => 422];
        $data = trim((string)($dados['data_demissao'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || !strtotime($data)) return ['erro' => 'Informe a data da demissão.', 'status' => 422];

        $this->transacao(function (array &$lista) use ($colab, $data, $autor) {
            $i = $this->indice($lista, $colab['matricula']);
            $base = $i === null ? ['matricula' => $colab['matricula'], 'homologacao_agendada_em' => null, 'documentos_agendados_em' => null] : $lista[$i];
            $novo = ['data_demissao' => $data, 'origem' => 'rh', 'registrado_por' => $autor, 'registrado_em' => date('Y-m-d H:i:s')] + $base;
            if ($i === null) $lista[] = $novo; else $lista[$i] = $novo;
        });
        return ['sucesso' => true];
    }

    /** acao: remover (tira o desligamento registrado pelo RH) | reabrir (zera os "Já agendei" e o aviso volta). */
    public function acaoRh(string $matricula, string $acao): array
    {
        if (!in_array($acao, ['remover', 'reabrir'], true)) return ['erro' => 'Ação inválida.', 'status' => 422];
        $achou = false;
        $this->transacao(function (array &$lista) use ($matricula, $acao, &$achou) {
            $i = $this->indice($lista, $matricula);
            if ($i === null) return;
            $achou = true;
            if ($acao === 'remover') {
                array_splice($lista, $i, 1);
            } else {
                $lista[$i]['homologacao_agendada_em'] = null;
                $lista[$i]['documentos_agendados_em'] = null;
            }
        });
        return $achou ? ['sucesso' => true] : ['erro' => 'Nada registrado no painel para este colaborador.', 'status' => 404];
    }

    // ---------------------------------------------------------------- internos

    public function dataDemissao(array $colab): ?string
    {
        foreach ([$colab['data_demissao'] ?? null, $this->registro($colab['matricula'])['data_demissao'] ?? null] as $d) {
            if (is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}/', $d)) return substr($d, 0, 10);
        }
        foreach ($this->homologacoes() as $h) {
            if ($h['matricula'] === $colab['matricula'] && $h['status'] !== 'cancelado' && !empty($h['data_demissao'])) return $h['data_demissao'];
        }
        return null;
    }

    private function origemDaData(array $colab): string
    {
        if (!empty($colab['data_demissao'])) return 'cadastro';
        if (!empty($this->registro($colab['matricula'])['data_demissao'])) return 'rh';
        return 'homologacao';
    }

    private function homologacaoDoRh(string $matricula): ?array
    {
        foreach ($this->homologacoes() as $h) {
            if ($h['matricula'] === $matricula && in_array($h['status'], ['agendado', 'enviado', 'realizado'], true)) return $h;
        }
        return null;
    }

    private function homologacoes(): array
    {
        $arq = dirname(__DIR__) . '/data/homologacoes.json';
        if (!is_file($arq)) return [];
        $d = json_decode((string)file_get_contents($arq), true);
        return is_array($d) ? $d : [];
    }

    private function registro(string $matricula): ?array
    {
        $lista = $this->todos();
        $i = $this->indice($lista, $matricula);
        return $i === null ? null : $lista[$i];
    }

    private function indice(array $lista, string $matricula): ?int
    {
        foreach ($lista as $i => $r) {
            if (($r['matricula'] ?? '') === $matricula) return $i;
        }
        return null;
    }

    private function todos(): array
    {
        if (!is_file($this->arquivo)) return [];
        $d = json_decode((string)file_get_contents($this->arquivo), true);
        return is_array($d) ? array_values($d) : [];
    }

    private function transacao(callable $fn): void
    {
        $lock = fopen($this->arquivo . '.lock', 'c');
        flock($lock, LOCK_EX);
        try {
            $lista = $this->todos();
            $fn($lista);
            file_put_contents($this->arquivo, json_encode(array_values($lista), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
