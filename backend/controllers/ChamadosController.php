<?php

namespace App\Controllers;

class ChamadosController
{
    private string $dataFile;

    public function __construct()
    {
        $this->dataFile = dirname(__DIR__) . '/data/chamados.json';
    }

    public function getChamados(int $colaboradorId): array
    {
        if (!file_exists($this->dataFile)) {
            return [];
        }

        $todos = json_decode(file_get_contents($this->dataFile), true) ?: [];
        return array_values(array_filter($todos, fn($c) => (int)$c['colaborador_id'] === $colaboradorId));
    }

    public function abrirChamado(int $colaboradorId, string $matricula, array $dados): array
    {
        $tipo = $dados['tipo'] ?? 'Outros';
        $assunto = $dados['assunto'] ?? 'Solicitação de RH';

        $novo = [
            'id' => 'CHAM-' . date('Y') . '-' . rand(200, 999),
            'colaborador_id' => $colaboradorId,
            'matricula' => $matricula,
            'tipo' => $tipo,
            'assunto' => $assunto,
            'data_abertura' => date('Y-m-d H:i:s'),
            'status' => 'Em Análise pelo RH',
            'resposta_rh' => 'Sua solicitação foi protocolada e será respondida em até 48 horas úteis.',
            'data_fechamento' => null,
            'documento_disponivel' => false
        ];

        $todos = file_exists($this->dataFile) ? json_decode(file_get_contents($this->dataFile), true) ?: [] : [];
        array_unshift($todos, $novo);

        file_put_contents($this->dataFile, json_encode($todos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return [
            'sucesso' => true,
            'mensagem' => 'Chamado registrado com sucesso!',
            'chamado' => $novo
        ];
    }
}
