<?php

namespace App\Controllers;

/**
 * Espelho de ponto do colaborador com assinatura eletrônica.
 * Marcações: data/ponto.json ({matricula: {"mm/aaaa": espelho}}) · Assinaturas: data/ponto_assinaturas.json
 */
class PontoController
{
    private string $pontoFile;
    private string $assinaturasFile;

    public function __construct()
    {
        $this->pontoFile = dirname(__DIR__) . '/data/ponto.json';
        $this->assinaturasFile = dirname(__DIR__) . '/data/ponto_assinaturas.json';
    }

    /** Competências disponíveis do colaborador, da mais recente para a mais antiga. */
    public function competencias(string $matricula): array
    {
        $assinaturas = $this->lerJson($this->assinaturasFile);
        $lista = [];
        foreach ($this->espelhosDe($matricula) as $comp => $esp) {
            $lista[] = [
                'competencia' => $comp,
                'mes_nome' => $esp['mes_nome'],
                'assinado_em' => $assinaturas[$this->chave($matricula, $comp)]['data_hora'] ?? null
            ];
        }
        usort($lista, fn($a, $b) => strcmp($this->ordenavel($b['competencia']), $this->ordenavel($a['competencia'])));
        return $lista;
    }

    /** Espelho de uma competência (mm/aaaa) ou o mais recente quando não informada. */
    public function getEspelho(string $matricula, ?string $competencia = null): ?array
    {
        $espelhos = $this->espelhosDe($matricula);
        if (!$espelhos) {
            return null;
        }
        $competencia ??= $this->competencias($matricula)[0]['competencia'];
        $espelho = $espelhos[$competencia] ?? null;
        if (!$espelho) {
            return null;
        }

        $assinaturas = $this->lerJson($this->assinaturasFile);
        $assinatura = $assinaturas[$this->chave($matricula, $competencia)] ?? null;
        if ($assinatura) {
            unset($assinatura['imagem_base64']);
        }
        $espelho['assinatura'] = $assinatura;
        return $espelho;
    }

    /** Grava (ou substitui) o espelho de uma competência. */
    public function salvarEspelho(string $matricula, array $espelho): void
    {
        $todos = $this->lerJson($this->pontoFile);
        $todos[$matricula][$espelho['competencia']] = $espelho;
        file_put_contents($this->pontoFile, json_encode($todos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    private function espelhosDe(string $matricula): array
    {
        return $this->lerJson($this->pontoFile)[$matricula] ?? [];
    }

    /** "10/2026" -> "2026-10" para ordenar. */
    private function ordenavel(string $competencia): string
    {
        [$m, $a] = explode('/', $competencia);
        return "$a-$m";
    }

    public function assinar(array $colaborador, string $competencia, string $assinaturaBase64, string $ip, string $userAgent): ?array
    {
        $espelho = $this->getEspelho($colaborador['matricula'], $competencia);
        if (!$espelho) {
            return null;
        }
        if (!empty($espelho['assinatura'])) {
            return $espelho;
        }

        $agora = date('Y-m-d H:i:s');
        // Hash de integridade: amarra marcações + colaborador + momento + assinatura
        $hash = strtoupper(hash('sha256', json_encode([
            'matricula' => $colaborador['matricula'],
            'cpf' => $colaborador['cpf'] ?? '',
            'competencia' => $competencia,
            'dias' => $espelho['dias'],
            'banco_horas' => $espelho['banco_horas'],
            'data_hora' => $agora,
            'timestamp' => microtime(true),
            'ip' => $ip,
            'user_agent' => $userAgent,
            'assinatura_hash' => hash('sha256', $assinaturaBase64),
            'empresa' => 'VILA BOA CONSTRUCOES E SERVICOS LIMITADA - CNPJ 02.785.261/0001-90'
        ])));

        $assinaturas = $this->lerJson($this->assinaturasFile);
        $assinaturas[$this->chave($colaborador['matricula'], $competencia)] = [
            'imagem_base64' => $assinaturaBase64,
            'data_hora' => date('d/m/Y H:i:s'),
            'ip' => $ip,
            'user_agent' => $userAgent,
            'hash_autenticidade' => $hash,
            'amparo_legal' => 'Portaria MTP 671/2021 · Lei 14.063/2020 (assinatura eletrônica)'
        ];
        file_put_contents($this->assinaturasFile, json_encode($assinaturas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $this->getEspelho($colaborador['matricula'], $competencia);
    }

    private function chave(string $matricula, string $competencia): string
    {
        return $matricula . '|' . $competencia;
    }

    private function lerJson(string $arquivo): array
    {
        if (!file_exists($arquivo)) {
            return [];
        }
        return json_decode(file_get_contents($arquivo), true) ?: [];
    }
}
