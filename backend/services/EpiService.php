<?php

namespace App\Services;

class EpiService
{
    private string $dataFile;

    public function __construct(?string $dataFile = null)
    {
        $this->dataFile = $dataFile ?: dirname(__DIR__) . '/data/epis.json';
    }

    public function getAll(): array
    {
        if (!file_exists($this->dataFile)) {
            return [];
        }
        $content = file_get_contents($this->dataFile);
        return json_decode($content, true) ?: [];
    }

    private function salvar(array $epis): bool
    {
        return file_put_contents(
            $this->dataFile,
            json_encode($epis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ) !== false;
    }

    public function getEpisPorColaborador(int $colaboradorId): array
    {
        $todos = $this->getAll();
        $resultado = [
            'pendentes' => [],
            'assinados' => [],
            'total_pendentes' => 0,
            'total_assinados' => 0
        ];

        foreach ($todos as $e) {
            if ((int)$e['colaborador_id'] === $colaboradorId) {
                if ($e['status'] === 'pendente_assinatura') {
                    $resultado['pendentes'][] = $e;
                } else {
                    $resultado['assinados'][] = $e;
                }
            }
        }

        $resultado['total_pendentes'] = count($resultado['pendentes']);
        $resultado['total_assinados'] = count($resultado['assinados']);

        return $resultado;
    }

    public function findById(string $entregaId): ?array
    {
        $todos = $this->getAll();
        foreach ($todos as $e) {
            if ($e['id'] === $entregaId) {
                return $e;
            }
        }
        return null;
    }

    /**
     * Assina a entrega de EPI com validade jurídica (NR-06 / Portaria MTP 672/2021 e Lei 14.063/2020).
     */
    public function assinarEntrega(
        int $colaboradorId,
        string $entregaId,
        string $assinaturaBase64,
        string $ip,
        string $userAgent
    ): ?array {
        $todos = $this->getAll();
        $atualizado = null;
        $agora = date('Y-m-d H:i:s');
        $timestamp = microtime(true);

        foreach ($todos as &$e) {
            if ($e['id'] === $entregaId && (int)$e['colaborador_id'] === $colaboradorId) {
                // Gera a Chave de Autenticidade Criptográfica (Hash SHA-256)
                // Amarra: ID da entrega + CPF + Itens + Timestamp + Assinatura + IP
                $rawPayload = json_encode([
                    'entrega_id' => $e['id'],
                    'colaborador_id' => $colaboradorId,
                    'cpf' => $e['cpf'],
                    'itens' => $e['itens'],
                    'data_hora' => $agora,
                    'timestamp' => $timestamp,
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'assinatura_hash' => hash('sha256', $assinaturaBase64),
                    'empresa' => 'VILA BOA CONSTRUCOES E SERVICOS LIMITADA - CNPJ 02.785.261/0001-90'
                ]);

                $hashIntegridade = strtoupper(hash('sha256', $rawPayload));

                $e['status'] = 'assinado';
                $e['assinatura'] = [
                    'imagem_base64' => $assinaturaBase64,
                    'data_hora' => $agora,
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'hash_autenticidade' => $hashIntegridade,
                    'tipo_assinatura' => 'Assinatura Eletrônica Avançada / Biometria Touch (Portaria MTP 672/2021 / Lei 14.063/2020)',
                    'amparo_legal' => 'Art. 166 da CLT c/c Item 6.5.1 da NR-06 do Ministério do Trabalho e Emprego'
                ];

                $atualizado = $e;
                break;
            }
        }

        if ($atualizado) {
            $this->salvar($todos);
        }

        return $atualizado;
    }
}
