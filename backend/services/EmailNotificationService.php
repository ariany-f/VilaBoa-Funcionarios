<?php

namespace App\Services;

/**
 * Fila/log de e-mails do portal (data/email_notificacoes.json), no mesmo formato do log de WhatsApp.
 *
 * GANCHO DE ENVIO REAL: por padrão o e-mail só é registrado (status ENFILEIRADO).
 * Para enviar de verdade pelo mail() do PHP (servidor com SMTP configurado no php.ini), defina a
 * variável de ambiente VB_EMAIL_ENVIO=mail (opcional VB_EMAIL_REMETENTE). Para outro provedor
 * (SMTP/SES/SendGrid) troque o trecho marcado em enviar().
 */
class EmailNotificationService
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = dirname(__DIR__) . '/data/email_notificacoes.json';
    }

    public function enviar(int $colaboradorId, string $colaboradorNome, string $email, string $assunto, string $texto, string $tipo, array $extra = []): array
    {
        $email = trim($email);
        $valido = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        $status = $valido ? 'ENFILEIRADO' : 'SEM_DESTINO';

        if ($valido && getenv('VB_EMAIL_ENVIO') === 'mail') {
            $remetente = getenv('VB_EMAIL_REMETENTE') ?: 'rh@vilaboa.com.br';
            $cabecalhos = "From: Vila Boa RH <{$remetente}>\r\nContent-Type: text/plain; charset=UTF-8\r\n";
            $status = @mail($email, '=?UTF-8?B?' . base64_encode($assunto) . '?=', $texto, $cabecalhos) ? 'ENTREGUE' : 'FALHOU';
        }

        $registro = [
            'id' => 'EML-' . date('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 6),
            'tipo' => $tipo,
            'colaborador_id' => $colaboradorId,
            'colaborador_nome' => $colaboradorNome,
            'email' => $email,
            'assunto' => $assunto,
            'mensagem' => $texto,
            'status' => $status,
            'canal' => 'E-mail (Vila Boa RH)',
            'data_envio' => date('d/m/Y \à\s H:i:s'),
            'timestamp' => date('c'),
        ] + $extra;

        $historico = $this->carregar();
        array_unshift($historico, $registro);
        file_put_contents($this->filePath, json_encode($historico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $registro;
    }

    private function carregar(): array
    {
        if (!file_exists($this->filePath)) return [];
        $dados = json_decode((string)file_get_contents($this->filePath), true);
        return is_array($dados) ? $dados : [];
    }
}
