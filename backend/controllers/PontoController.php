<?php

namespace App\Controllers;

/**
 * Espelho de ponto do colaborador com assinatura eletrônica.
 * Marcações: data/ponto.json · Assinaturas: data/ponto_assinaturas.json
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

    public function getEspelho(string $matricula): ?array
    {
        $todos = $this->lerJson($this->pontoFile);
        $espelho = $todos[$matricula] ?? null;
        if (!$espelho) {
            return null;
        }

        $assinaturas = $this->lerJson($this->assinaturasFile);
        $assinatura = $assinaturas[$this->chave($matricula, $espelho['competencia'])] ?? null;
        if ($assinatura) {
            unset($assinatura['imagem_base64']);
        }
        $espelho['assinatura'] = $assinatura;
        return $espelho;
    }

    public function assinar(array $colaborador, string $competencia, string $assinaturaBase64, string $ip, string $userAgent): ?array
    {
        $espelho = $this->getEspelho($colaborador['matricula']);
        if (!$espelho || $espelho['competencia'] !== $competencia) {
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

        return $this->getEspelho($colaborador['matricula']);
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
