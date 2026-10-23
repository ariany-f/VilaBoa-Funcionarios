<?php

namespace App\Controllers;

class FeriasController
{
    private string $dataFile;

    public function __construct()
    {
        $this->dataFile = dirname(__DIR__) . '/data/ferias.json';
    }

    public function getDadosFerias(string $matricula): array
    {
        if (!file_exists($this->dataFile)) {
            return $this->gerarFeriasPadrao();
        }

        $todos = json_decode(file_get_contents($this->dataFile), true) ?: [];
        return $todos[$matricula] ?? $this->gerarFeriasPadrao();
    }

    public function solicitarFerias(string $matricula, array $dados): array
    {
        $protocolo = 'FER-' . date('Y') . '-' . rand(100, 999);
        return [
            'sucesso' => true,
            'protocolo' => $protocolo,
            'mensagem' => 'Solicitação de férias registrada com sucesso e encaminhada para alinhamento com a Engenharia de Campo e RH.'
        ];
    }

    private function gerarFeriasPadrao(): array
    {
        return [
            'dias_direito' => 30,
            'dias_saldo' => 30,
            'periodo_aquisitivo_atual' => [
                'inicio' => '01/01/' . (date('Y') - 1),
                'fim' => '31/12/' . (date('Y') - 1),
                'limite_concessivo' => '30/11/' . date('Y'),
                'status' => 'Adquirido (Pronto para Gozo)'
            ],
            'solicitacao_atual' => null,
            'historico' => []
        ];
    }
}
