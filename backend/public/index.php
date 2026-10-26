<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Cabeçalhos de Segurança da Informação & Proteção de Dados (OWASP & LGPD)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Se não for rota da API, tenta servir arquivos estáticos ou o frontend Vue (SPA)
$apiPos = strpos($requestUri, '/api');
if ($apiPos === false) {
    $subPath = preg_replace('#^.*/(assets|img)/#', '/$1/', $requestUri);
    $filePath = __DIR__ . $subPath;
    if ($requestUri !== '/' && file_exists($filePath) && is_file($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimes = ['css'=>'text/css','js'=>'application/javascript','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','svg'=>'image/svg+xml','ico'=>'image/x-icon','woff'=>'font/woff','woff2'=>'font/woff2'];
        if (isset($mimes[$ext])) header('Content-Type: ' . $mimes[$ext]);
        readfile($filePath);
        exit;
    }

    $spaIndex = __DIR__ . '/index.html';
    if (file_exists($spaIndex)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($spaIndex);
        exit;
    }
}

// Todas as rotas /api respondem em JSON
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../services/ColaboradorService.php';
require_once __DIR__ . '/../services/HoleriteService.php';
require_once __DIR__ . '/../services/EpiService.php';
require_once __DIR__ . '/../services/WhatsAppNotificationService.php';
require_once __DIR__ . '/../controllers/FeriasController.php';
require_once __DIR__ . '/../controllers/ChamadosController.php';
require_once __DIR__ . '/../controllers/CadernosController.php';
require_once __DIR__ . '/../controllers/PontoController.php';

use App\Services\ColaboradorService;
use App\Services\HoleriteService;
use App\Services\EpiService;
use App\Services\WhatsAppNotificationService;
use App\Controllers\FeriasController;
use App\Controllers\ChamadosController;
use App\Controllers\CadernosController;
use App\Controllers\PontoController;

$colaboradorService = new ColaboradorService();
$holeriteService = new HoleriteService();
$epiService = new EpiService();
$whatsAppService = new WhatsAppNotificationService();
$feriasController = new FeriasController();
$chamadosController = new ChamadosController();
$cadernosController = new CadernosController();
$pontoController = new PontoController();

$tentativasSegurancaFile = dirname(__DIR__) . '/data/tentativas_seguranca.json';

// Confirmação de login por token enviado ao celular.
// Desativada até a infra liberar o serviço de envio; enquanto isso o login usa as perguntas de segurança.
const TOKEN_SMS_ATIVO = false;

function getTentativasSeguranca(string $file): array {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function salvarTentativasSeguranca(string $file, array $data): void {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Normaliza o path da rota removendo prefixos
$route = preg_replace('#^/index\.php#', '', $requestUri);
if ($apiPos !== false) {
    $route = substr($requestUri, $apiPos + 4);
} else {
    $route = preg_replace('#^/api#', '', $route);
}
if ($route === '' || $route === '/') {
    $route = '/';
}

$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents('php://input'), true) ?? [];

// Chave Secreta Criptográfica (compatível com variável de ambiente .env em produção)
function getSecretKey(): string {
    return getenv('APP_SECRET') ?: 'VILA_BOA_SECRET_KEY_2026';
}

// Trilha de Auditoria e Accountability conforme Art. 46 e 48 da LGPD
function registrarAuditoriaLgpd(string $acao, ?string $matricula = null, array $detalhes = []): void {
    $logFile = dirname(__DIR__) . '/data/auditoria_lgpd.log';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    $registro = [
        'timestamp' => date('c'),
        'acao' => $acao,
        'matricula' => $matricula,
        'ip' => $ip,
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido', 0, 160),
        'detalhes' => $detalhes
    ];
    @file_put_contents($logFile, json_encode($registro, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Helper para gerar token de autenticação assinado HMAC-SHA256
function gerarToken(array $payload, int $ttl = 86400): string {
    $payload['exp'] = time() + $ttl;
    $json = json_encode($payload);
    $b64 = base64_encode($json);
    $sig = hash_hmac('sha256', $b64, getSecretKey());
    return $b64 . '.' . $sig;
}

function validarToken(?string $tokenString): ?array {
    if (!$tokenString) return null;
    $tokenString = str_replace('Bearer ', '', trim($tokenString));
    $parts = explode('.', $tokenString);
    if (count($parts) !== 2) return null;

    [$b64, $sig] = $parts;
    $expectedSig = hash_hmac('sha256', $b64, getSecretKey());
    if (!hash_equals($expectedSig, $sig)) {
        return null;
    }

    $payload = json_decode(base64_decode($b64), true);
    if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
        return null;
    }
    return $payload;
}

function getBearerToken(): ?string {
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return $_SERVER['HTTP_AUTHORIZATION'];
    }
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (!empty($headers['Authorization'])) {
            return $headers['Authorization'];
        }
        if (!empty($headers['authorization'])) {
            return $headers['authorization'];
        }
    }
    return null;
}

// ==================== ROTAS DA API ====================

// 1. Informações e Mock List para facilitar testes
if ($method === 'GET' && ($route === '/' || $route === '/colaboradores-demo')) {
    $colabs = $colaboradorService->getAll();
    // Esconde dados sensíveis brutos na listagem pública de demo
    $demoList = array_map(function ($c) {
        return [
            'id' => $c['id'],
            'nome' => $c['nome'],
            'matricula' => $c['matricula'],
            'cpf' => $c['cpf_formatado'],
            'data_nascimento' => $c['data_nascimento_br'],
            'cargo' => $c['cargo'],
            'telefone_atual' => $c['telefone_atual']
        ];
    }, $colabs);

    echo json_encode([
        'status' => 'online',
        'sistema' => 'API Portal do Funcionário Vila Boa Construções',
        'versao' => '1.0.0',
        'colaboradores_para_teste' => $demoList
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 1.1. Validador Público de Autenticidade de Holerite via QR Code (Acesso Livre para Bancos / Terceiros)
if ($method === 'GET' && $route === '/validar-holerite') {
    $id = $_GET['id'] ?? '';
    $auth = $_GET['auth'] ?? '';

    if (empty($id) && empty($auth)) {
        http_response_code(400);
        echo json_encode(['valido' => false, 'erro' => 'Identificador ou chave de autenticação não informados.']);
        exit;
    }

    $colabs = $colaboradorService->getAll();
    $holeriteEncontrado = null;
    $colaboradorDono = null;
    $pacoteEncontrado = null;

    foreach ($colabs as $c) {
        // 1. Busca por holerite individual
        $holerites = $holeriteService->getHolerites($c['matricula'], '2026');
        foreach ($holerites as $h) {
            if (($id && $h['id'] === $id) || ($auth && $h['autenticacao_digital'] === $auth)) {
                $holeriteEncontrado = $h;
                $colaboradorDono = $c;
                break 2;
            }
        }

        // 2. Busca por dossiê consolidado de 3 meses
        if ($auth && !$holeriteEncontrado) {
            try {
                $p = $holeriteService->getPacoteComprovacaoRenda($c['matricula'], 3, $c);
                if ($p['emissao']['protocolo_autenticidade'] === $auth) {
                    $pacoteEncontrado = $p;
                    $colaboradorDono = $c;
                    break;
                }
            } catch (\Throwable $e) {}
        }
    }

    // Se encontrou Dossiê de Renda Consolidado
    if ($pacoteEncontrado && $colaboradorDono) {
        $cpf = $colaboradorDono['cpf'] ?? '00000000000';
        $cpfMascarado = substr($cpf, 0, 3) . '.***.***-' . substr($cpf, -2);

        echo json_encode([
            'sucesso' => true,
            'valido' => true,
            'status' => 'AUTENTICO',
            'tipo' => 'DOSSIE_RENDA',
            'mensagem' => 'Dossiê Consolidado de Renda (3 Meses) Autêntico emitido por Vila Boa Construções e Serviços LTDA',
            'documento' => [
                'id' => $pacoteEncontrado['emissao']['protocolo_autenticidade'],
                'tipo_folha' => 'Dossiê de Comprovação de Renda (3 Meses)',
                'competencia' => $pacoteEncontrado['resumo_financeiro']['periodo_competencias'],
                'data_pagamento' => $pacoteEncontrado['emissao']['data'],
                'salario_liquido' => $pacoteEncontrado['resumo_financeiro']['media_salario_liquida'],
                'total_proventos' => $pacoteEncontrado['resumo_financeiro']['media_proventos_bruta'],
                'total_descontos' => round($pacoteEncontrado['resumo_financeiro']['media_proventos_bruta'] - $pacoteEncontrado['resumo_financeiro']['media_salario_liquida'], 2),
                'autenticacao_digital' => $pacoteEncontrado['emissao']['protocolo_autenticidade'],
                'ciencia_registrada' => true,
                'data_ciencia' => $pacoteEncontrado['emissao']['data'] . ' ' . $pacoteEncontrado['emissao']['hora'],
                'hash_ciencia' => $pacoteEncontrado['emissao']['protocolo_autenticidade']
            ],
            'colaborador' => [
                'nome' => $colaboradorDono['nome'],
                'matricula' => $colaboradorDono['matricula'],
                'cpf_mascarado' => $cpfMascarado,
                'cargo' => $colaboradorDono['cargo'],
                'departamento' => $colaboradorDono['departamento']
            ],
            'empresa' => [
                'razao_social' => 'VILA BOA CONSTRUCOES E SERVICOS LIMITADA',
                'cnpj' => '02.785.261/0001-90',
                'cidade' => 'São Bernardo do Campo - SP',
                'contato_rh' => 'dp@vilaboaconstrucoes.com.br'
            ],
            'validado_em' => date('d/m/Y \à\s H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$holeriteEncontrado || !$colaboradorDono) {
        http_response_code(404);
        echo json_encode([
            'valido' => false,
            'status' => 'NAO_LOCALIZADO',
            'erro' => 'Documento não localizado no banco de registros oficiais da Vila Boa Construções.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $cpf = $colaboradorDono['cpf'] ?? '00000000000';
    $cpfMascarado = substr($cpf, 0, 3) . '.***.***-' . substr($cpf, -2);

    echo json_encode([
        'sucesso' => true,
        'valido' => true,
        'status' => 'AUTENTICO',
        'mensagem' => 'Documento Autêntico e Válido emitido por Vila Boa Construções e Serviços LTDA',
        'documento' => [
            'id' => $holeriteEncontrado['id'],
            'tipo_folha' => $holeriteEncontrado['tipo_folha'],
            'competencia' => $holeriteEncontrado['competencia'],
            'mes_nome' => $holeriteEncontrado['mes_nome'],
            'ano' => $holeriteEncontrado['ano'],
            'data_pagamento' => $holeriteEncontrado['data_pagamento'],
            'salario_liquido' => $holeriteEncontrado['salario_liquido'],
            'total_proventos' => $holeriteEncontrado['total_proventos'],
            'total_descontos' => $holeriteEncontrado['total_descontos'],
            'autenticacao_digital' => $holeriteEncontrado['autenticacao_digital'],
            'ciencia_registrada' => $holeriteEncontrado['aceite']['confirmado'] ?? false,
            'data_ciencia' => $holeriteEncontrado['aceite']['data_hora'] ?? null,
            'hash_ciencia' => $holeriteEncontrado['aceite']['hash_comprovante'] ?? null
        ],
        'colaborador' => [
            'nome' => $colaboradorDono['nome'],
            'matricula' => $colaboradorDono['matricula'],
            'cpf_mascarado' => $cpfMascarado,
            'cargo' => $colaboradorDono['cargo'],
            'departamento' => $colaboradorDono['departamento']
        ],
        'empresa' => [
            'razao_social' => 'VILA BOA CONSTRUCOES E SERVICOS LIMITADA',
            'cnpj' => '02.785.261/0001-90',
            'cidade' => 'São Bernardo do Campo - SP',
            'contato_rh' => 'dp@vilaboaconstrucoes.com.br'
        ],
        'validado_em' => date('d/m/Y \à\s H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1.1 Rota Pública de Governança e Termo de Proteção LGPD (Art. 7º e 18 da Lei 13.709/2018)
if ($method === 'GET' && $route === '/lgpd/termo') {
    echo json_encode([
        'sucesso' => true,
        'versao' => '2026.1',
        'controlador' => [
            'razao_social' => 'VILA BOA CONSTRUCOES E SERVICOS LIMITADA',
            'cnpj' => '02.785.261/0001-90',
            'cidade' => 'São Bernardo do Campo - SP',
            'canal_privacidade' => 'dpo@vilaboaconstrucoes.com.br'
        ],
        'bases_legais' => [
            'Art. 7º, II (Cumprimento de obrigação legal trabalhista - CLT Art. 464 e eSocial)',
            'Art. 7º, V (Execução do contrato de trabalho CLT)'
        ],
        'seguranca' => [
            'Criptografia HMAC-SHA256 em sessões e assinaturas',
            'Mascaramento de dados sensíveis na validação pública de holerites',
            'Trilha de auditoria rastreável com IP e timestamp'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2.1. Validação do token recebido no celular (alternativa às perguntas de segurança)
if ($method === 'POST' && $route === '/login/token/validar') {
    if (!TOKEN_SMS_ATIVO) {
        http_response_code(503);
        echo json_encode(['erro' => 'A confirmação por token ainda não está disponível. Responda às perguntas de segurança.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Integração com o serviço de envio/validação de token: aguardando a infra.
    http_response_code(501);
    echo json_encode(['erro' => 'Serviço de token não configurado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Passo 1: Login com CPF + Data de Nascimento
if ($method === 'POST' && $route === '/login') {
    $cpf = $body['cpf'] ?? '';
    $dataNascimento = $body['data_nascimento'] ?? '';

    if (empty($cpf) || empty($dataNascimento)) {
        http_response_code(400);
        echo json_encode(['erro' => 'CPF e Data de Nascimento são obrigatórios.']);
        exit;
    }

    // Verifica se há bloqueio temporário ativo neste CPF (a partir de 3 erros prévios)
    $chaveLogin = 'login_' . preg_replace('/\D/', '', $cpf);
    $tentativas = getTentativasSeguranca($tentativasSegurancaFile);
    $registroLogin = $tentativas[$chaveLogin] ?? null;

    if ($registroLogin && isset($registroLogin['bloqueado_ate']) && $registroLogin['bloqueado_ate'] > time()) {
        $segundosRestantes = $registroLogin['bloqueado_ate'] - time();
        http_response_code(429);
        echo json_encode([
            'sucesso' => false,
            'bloqueado' => true,
            'segundos_restantes' => $segundosRestantes,
            'erro' => "Acesso temporariamente bloqueado após 3 erros consecutivos. Aguarde {$segundosRestantes} segundos para tentar novamente."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $colaborador = $colaboradorService->autenticar($cpf, $dataNascimento);

    if (!$colaborador) {
        $errosLogin = ($registroLogin['erros'] ?? 0) + 1;
        $segundosBloqueio = 0;
        $bloqueadoAte = 0;

        // Tempo de espera APENAS a partir da 3ª tentativa errada
        if ($errosLogin >= 3) {
            $segundosBloqueio = 60; // 60 segundos de espera na 3ª vez
            $bloqueadoAte = time() + $segundosBloqueio;
            $msgErro = "Você errou os dados de acesso 3 vezes. Aguarde 60 segundos para tentar novamente.";
        } else {
            $msgErro = "CPF ou Data de Nascimento não conferem com o cadastro da Vila Boa. (Tentativa {$errosLogin} de 3)";
        }

        $tentativas[$chaveLogin] = [
            'erros' => $errosLogin,
            'bloqueado_ate' => $bloqueadoAte,
            'ultimo_erro' => date('Y-m-d H:i:s')
        ];
        salvarTentativasSeguranca($tentativasSegurancaFile, $tentativas);

        http_response_code($segundosBloqueio > 0 ? 429 : 401);
        echo json_encode([
            'sucesso' => false,
            'bloqueado' => $segundosBloqueio > 0,
            'segundos_restantes' => $segundosBloqueio,
            'erro' => $msgErro
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Login com sucesso: limpa contador de erros da primeira etapa
    if (isset($tentativas[$chaveLogin])) {
        unset($tentativas[$chaveLogin]);
        salvarTentativasSeguranca($tentativasSegurancaFile, $tentativas);
    }

    // Verifica se este colaborador está atualmente bloqueado por tentativas erradas
    $tentativas = getTentativasSeguranca($tentativasSegurancaFile);
    $registroTentativa = $tentativas[(string)$colaborador['id']] ?? null;
    $ultimoTipoPergunta = $registroTentativa['ultimo_tipo_pergunta'] ?? null;

    if ($registroTentativa && isset($registroTentativa['bloqueado_ate']) && $registroTentativa['bloqueado_ate'] > time()) {
        $segundosRestantes = $registroTentativa['bloqueado_ate'] - time();
        
        // Garante que uma nova validação seja sorteada mesmo se tentar logar durante o bloqueio
        $novaPergunta = $colaboradorService->gerarPerguntaSeguranca($colaborador, $ultimoTipoPergunta);
        $tempToken = gerarToken([
            'etapa' => 'seguranca',
            'colaborador_id' => $colaborador['id'],
            'tipo_pergunta' => $novaPergunta['tipo'],
            'resposta_hash' => $novaPergunta['resposta_esperada_hash']
        ], 600);

        http_response_code(429);
        echo json_encode([
            'sucesso' => false,
            'bloqueado' => true,
            'segundos_restantes' => $segundosRestantes,
            'tentativas_erradas' => $registroTentativa['erros'] ?? 1,
            'erro' => "Tentativa temporariamente bloqueada por resposta incorreta anterior. Aguarde {$segundosRestantes} segundos.",
            'temp_token' => $tempToken,
            'colaborador' => [
                'id' => $colaborador['id'],
                'primeiro_nome' => explode(' ', $colaborador['nome'])[0],
                'matricula' => $colaborador['matricula'],
            ],
            'pergunta' => [
                'tipo' => $novaPergunta['tipo'],
                'enunciado' => $novaPergunta['enunciado'],
                'dica' => $novaPergunta['dica'],
                'opcoes' => $novaPergunta['opcoes'],
                'resposta_hash' => $novaPergunta['resposta_esperada_hash']
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Sorteia pergunta de segurança
    registrarAuditoriaLgpd('LOGIN_ETAPA1_SUCESSO', $colaborador['matricula']);
    // Gera o desafio de segurança (pergunta aleatória diferente da última se houver)
    $pergunta = $colaboradorService->gerarPerguntaSeguranca($colaborador, $ultimoTipoPergunta);

    // Token temporário para a fase de segurança (válido por 10 minutos)
    $tempToken = gerarToken([
        'etapa' => 'seguranca',
        'colaborador_id' => $colaborador['id'],
        'tipo_pergunta' => $pergunta['tipo'],
        'resposta_hash' => $pergunta['resposta_esperada_hash']
    ], 600);

    $telefoneDigitos = preg_replace('/\D/', '', $colaborador['telefone_atual'] ?? '');

    echo json_encode([
        'sucesso' => true,
        'etapa_necessaria' => 'verificacao_seguranca',
        'temp_token' => $tempToken,
        'token_sms' => [
            'ativo' => TOKEN_SMS_ATIVO,
            'telefone_mascarado' => $telefoneDigitos ? '(**) *****-' . substr($telefoneDigitos, -4) : null
        ],
        'colaborador' => [
            'id' => $colaborador['id'],
            'primeiro_nome' => explode(' ', $colaborador['nome'])[0],
            'matricula' => $colaborador['matricula'],
        ],
        'pergunta' => [
            'tipo' => $pergunta['tipo'],
            'enunciado' => $pergunta['enunciado'],
            'dica' => $pergunta['dica'],
            'opcoes' => $pergunta['opcoes'],
            'resposta_hash' => $pergunta['resposta_esperada_hash']
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Passo 2: Verificação do dado pessoal aleatório
if ($method === 'POST' && $route === '/verify-security') {
    $tempToken = $body['temp_token'] ?? '';
    $resposta = $body['resposta'] ?? '';
    $respostaHash = $body['resposta_hash'] ?? '';

    $sessao = validarToken($tempToken);
    if (!$sessao || ($sessao['etapa'] ?? '') !== 'seguranca') {
        http_response_code(403);
        echo json_encode(['erro' => 'Sessão de verificação expirada ou inválida. Faça login novamente.']);
        exit;
    }

    $colaboradorId = (int)$sessao['colaborador_id'];
    $hashEsperado = $sessao['resposta_hash'] ?? $respostaHash;
    $tipoPerguntaAtual = $sessao['tipo_pergunta'] ?? null;

    // 1. Verifica se está atualmente em período de bloqueio
    $tentativas = getTentativasSeguranca($tentativasSegurancaFile);
    $registroTentativa = $tentativas[(string)$colaboradorId] ?? null;

    if ($registroTentativa && isset($registroTentativa['bloqueado_ate']) && $registroTentativa['bloqueado_ate'] > time()) {
        $segundosRestantes = $registroTentativa['bloqueado_ate'] - time();
        http_response_code(429);
        echo json_encode([
            'sucesso' => false,
            'bloqueado' => true,
            'segundos_restantes' => $segundosRestantes,
            'tentativas_erradas' => $registroTentativa['erros'] ?? 1,
            'erro' => "Tentativa temporariamente bloqueada. Aguarde {$segundosRestantes} segundos para tentar novamente."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Valida a resposta informada
    if (!$colaboradorService->validarRespostaSeguranca($resposta, $hashEsperado)) {
        $erros = ($registroTentativa['erros'] ?? 0) + 1;

        // Regra solicitada: Tempo de espera APENAS a partir da 3ª vez errada
        if ($erros < 3) {
            $segundosBloqueio = 0;
            $bloqueadoAte = 0;
            if ($erros === 1) {
                $textoAviso = "Resposta incorreta (Tentativa 1 de 3). Uma nova pergunta foi sorteada.";
            } else {
                $textoAviso = "Resposta incorreta (Tentativa 2 de 3). Atenção: se errar a 3ª vez, haverá tempo de espera.";
            }
        } elseif ($erros === 3) {
            $segundosBloqueio = 60; // 1 minuto de espera na 3ª tentativa
            $bloqueadoAte = time() + $segundosBloqueio;
            $textoAviso = "Você errou 3 vezes a validação. Por segurança, aguarde 60 segundos para responder novamente.";
        } else {
            $segundosBloqueio = 300; // 5 minutos a partir da 4ª
            $bloqueadoAte = time() + $segundosBloqueio;
            $textoAviso = "Acesso temporariamente bloqueado por excesso de tentativas. Aguarde 5 minutos para responder.";
        }

        // Sorteia obrigatoriamente uma NOVA validação diferente da que acabou de ser errada
        $colaborador = $colaboradorService->findById($colaboradorId);
        $novaPergunta = $colaborador ? $colaboradorService->gerarPerguntaSeguranca($colaborador, $tipoPerguntaAtual) : null;
        
        $novoTempToken = null;
        if ($novaPergunta) {
            $novoTempToken = gerarToken([
                'etapa' => 'seguranca',
                'colaborador_id' => $colaboradorId,
                'tipo_pergunta' => $novaPergunta['tipo'],
                'resposta_hash' => $novaPergunta['resposta_esperada_hash']
            ], 600);
        }

        $tentativas[(string)$colaboradorId] = [
            'colaborador_id' => $colaboradorId,
            'erros' => $erros,
            'bloqueado_ate' => $bloqueadoAte,
            'ultimo_erro' => date('Y-m-d H:i:s'),
            'ultimo_tipo_pergunta' => $novaPergunta['tipo'] ?? null
        ];
        salvarTentativasSeguranca($tentativasSegurancaFile, $tentativas);

        http_response_code($segundosBloqueio > 0 ? 429 : 400);
        echo json_encode([
            'sucesso' => false,
            'bloqueado' => $segundosBloqueio > 0,
            'segundos_restantes' => $segundosBloqueio,
            'tentativas_erradas' => $erros,
            'erro' => $textoAviso,
            'temp_token' => $novoTempToken,
            'nova_pergunta' => $novaPergunta ? [
                'tipo' => $novaPergunta['tipo'],
                'enunciado' => $novaPergunta['enunciado'],
                'dica' => $novaPergunta['dica'],
                'opcoes' => $novaPergunta['opcoes'],
                'resposta_hash' => $novaPergunta['resposta_esperada_hash']
            ] : null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Se acertou: limpa o histórico de erros do colaborador
    if (isset($tentativas[(string)$colaboradorId])) {
        unset($tentativas[(string)$colaboradorId]);
        salvarTentativasSeguranca($tentativasSegurancaFile, $tentativas);
    }

    $colaborador = $colaboradorService->findById($colaboradorId);
    if (!$colaborador) {
        http_response_code(404);
        echo json_encode(['erro' => 'Colaborador não encontrado.']);
        exit;
    }

    // Token para o passo OBRIGATÓRIO de telefone
    $phoneToken = gerarToken([
        'etapa' => 'confirmacao_telefone',
        'colaborador_id' => $colaborador['id']
    ], 900);

    echo json_encode([
        'sucesso' => true,
        'etapa_necessaria' => 'confirmacao_telefone',
        'phone_token' => $phoneToken,
        'mensagem' => 'Identidade validada com sucesso!',
        'colaborador' => [
            'id' => $colaborador['id'],
            'nome' => $colaborador['nome'],
            'telefone_atual' => $colaborador['telefone_atual'],
            'telefone_confirmado_em' => $colaborador['telefone_confirmado_em']
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 4. Passo 3: OBJETIVO PRIMÁRIO - Confirmação/Atualização do Telefone
if ($method === 'POST' && $route === '/confirm-phone') {
    $phoneToken = $body['phone_token'] ?? '';
    $telefone = trim($body['telefone'] ?? '');

    $sessao = validarToken($phoneToken);
    if (!$sessao || ($sessao['etapa'] ?? '') !== 'confirmacao_telefone') {
        http_response_code(403);
        echo json_encode(['erro' => 'Autorização expirada para confirmação de contato.']);
        exit;
    }

    $digitos = preg_replace('/\D/', '', $telefone);
    if (strlen($digitos) < 10 || strlen($digitos) > 11) {
        http_response_code(400);
        echo json_encode(['erro' => 'Número de telefone inválido. Informe DDD + 8 ou 9 dígitos.']);
        exit;
    }

    $colaboradorId = (int)$sessao['colaborador_id'];
    $colaboradorAtualizado = $colaboradorService->confirmarTelefone($colaboradorId, $telefone);

    if (!$colaboradorAtualizado) {
        http_response_code(500);
        echo json_encode(['erro' => 'Não foi possível atualizar o telefone. Tente novamente.']);
        exit;
    }

    // Token final completo de acesso ao portal (válido por 30 dias)
    registrarAuditoriaLgpd('LOGIN_AUTENTICADO', $colaboradorAtualizado['matricula'], ['id' => $colaboradorAtualizado['id']]);
    $authToken = gerarToken([
        'etapa' => 'autenticado',
        'colaborador_id' => $colaboradorAtualizado['id'],
        'matricula' => $colaboradorAtualizado['matricula'],
        'nome' => $colaboradorAtualizado['nome']
    ], 86400 * 30);

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Telefone confirmado com sucesso!',
        'auth_token' => $authToken,
        'colaborador' => [
            'id' => $colaboradorAtualizado['id'],
            'nome' => $colaboradorAtualizado['nome'],
            'matricula' => $colaboradorAtualizado['matricula'],
            'cpf' => $colaboradorAtualizado['cpf_formatado'],
            'cargo' => $colaboradorAtualizado['cargo'],
            'departamento' => $colaboradorAtualizado['departamento'],
            'telefone_atual' => $colaboradorAtualizado['telefone_atual'],
            'telefone_confirmado_em' => $colaboradorAtualizado['telefone_confirmado_em']
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 5. Autenticação para rotas protegidas
$authHeader = getBearerToken();
$userSession = validarToken($authHeader);

if (!$userSession || ($userSession['etapa'] ?? '') !== 'autenticado') {
    http_response_code(401);
    echo json_encode(['erro' => 'Acesso não autorizado ou sessão expirada. Faça login.']);
    exit;
}

$colaboradorLogado = $colaboradorService->findById((int)$userSession['colaborador_id']);

// 6. Rota Me (Dados do Usuário Autenticado)
if ($method === 'GET' && $route === '/me') {
    echo json_encode([
        'sucesso' => true,
        'colaborador' => [
            'id' => $colaboradorLogado['id'],
            'nome' => $colaboradorLogado['nome'],
            'matricula' => $colaboradorLogado['matricula'],
            'cpf' => $colaboradorLogado['cpf_formatado'],
            'cargo' => $colaboradorLogado['cargo'],
            'departamento' => $colaboradorLogado['departamento'],
            'telefone_atual' => $colaboradorLogado['telefone_atual'],
            'telefone_confirmado_em' => $colaboradorLogado['telefone_confirmado_em'],
            'email' => $colaboradorLogado['email'] ?? null
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 7. Rota de Holerites
if ($method === 'GET' && $route === '/holerites') {
    $ano = $_GET['ano'] ?? '2026';
    $tipo = $_GET['tipo'] ?? null;
    $holerites = $holeriteService->getHolerites($colaboradorLogado['matricula'], $ano, $tipo);

    echo json_encode([
        'sucesso' => true,
        'matricula' => $colaboradorLogado['matricula'],
        'ano' => $ano,
        'tipo_filtro' => $tipo ?: 'todos',
        'total' => count($holerites),
        'holerites' => $holerites
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 7.1. Rota de Pacote de Comprovação de Renda (3 Últimos Holerites em 1 PDF para Financiamento / Caixa)
if ($method === 'GET' && $route === '/holerites/pacote-renda') {
    $meses = isset($_GET['meses']) ? (int)$_GET['meses'] : 3;
    $meses = max(1, min(6, $meses));

    try {
        $pacote = $holeriteService->getPacoteComprovacaoRenda(
            $colaboradorLogado['matricula'],
            $meses,
            $colaboradorLogado
        );

        echo json_encode([
            'sucesso' => true,
            'pacote' => $pacote
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (\Throwable $e) {
        http_response_code(400);
        echo json_encode(['erro' => $e->getMessage()]);
        exit;
    }
}

// 7.2. Rota de Listagem dos Informes de Rendimentos (IRPF / eSocial)
if ($method === 'GET' && $route === '/informes-rendimentos') {
    $informes = $holeriteService->getInformesRendimentos(
        $colaboradorLogado['matricula'],
        $colaboradorLogado
    );

    echo json_encode([
        'sucesso' => true,
        'informes' => $informes
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 7.3. Rota de Detalhe do Informe de Rendimentos por Ano-Calendário
if ($method === 'GET' && preg_match('#^/informes-rendimentos/([0-9]{4})$#', $route, $matches)) {
    $anoCalendario = $matches[1];
    $informe = $holeriteService->getInformeRendimentosDetalhado(
        $colaboradorLogado['matricula'],
        $anoCalendario,
        $colaboradorLogado
    );

    echo json_encode([
        'sucesso' => true,
        'informe' => $informe
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 7.4. Rota de Histórico de Notificações WhatsApp de Holerites
if ($method === 'GET' && $route === '/whatsapp/notificacoes') {
    $notificacoes = $whatsAppService->getNotificacoesPorColaborador(
        (int)$colaboradorLogado['id'],
        $colaboradorLogado['nome'],
        $colaboradorLogado['telefone_atual'] ?? ''
    );

    echo json_encode([
        'sucesso' => true,
        'telefone_confirmado' => $colaboradorLogado['telefone_atual'] ?? '',
        'total' => count($notificacoes),
        'notificacoes' => $notificacoes
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 7.5. Rota para Reenviar/Disparar Notificação de Holerite via WhatsApp
if ($method === 'POST' && $route === '/whatsapp/reenviar-holerite') {
    $competencia = $body['competencia'] ?? 'Atual';
    $tipoFolha = $body['tipo_folha'] ?? 'Folha Mensal';

    $notificacao = $whatsAppService->notificarHoleriteDisponivel(
        (int)$colaboradorLogado['id'],
        $colaboradorLogado['nome'],
        $colaboradorLogado['telefone_atual'] ?? '',
        $competencia,
        $tipoFolha
    );

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Aviso de holerite enviado para seu WhatsApp com sucesso!',
        'notificacao' => $notificacao
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 8. Rota de Detalhe do Holerite
if ($method === 'GET' && preg_match('#^/holerites/([^/]+)$#', $route, $matches)) {
    $holeriteId = $matches[1];
    $detalhe = $holeriteService->getHoleriteDetalhado($colaboradorLogado['matricula'], $holeriteId);

    if (!$detalhe) {
        http_response_code(404);
        echo json_encode(['erro' => 'Holerite não localizado.']);
        exit;
    }

    echo json_encode([
        'sucesso' => true,
        'holerite' => $detalhe,
        'colaborador' => [
            'nome' => $colaboradorLogado['nome'],
            'matricula' => $colaboradorLogado['matricula'],
            'cargo' => $colaboradorLogado['cargo'],
            'departamento' => $colaboradorLogado['departamento'],
            'cpf' => $colaboradorLogado['cpf_formatado']
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 8.1. Rota de Confirmação de Ciência / Aceite do Holerite (Art. 464 CLT para DP)
if ($method === 'POST' && $route === '/holerites/aceitar') {
    $holeriteId = $body['holerite_id'] ?? '';

    if (empty($holeriteId)) {
        http_response_code(400);
        echo json_encode(['erro' => 'Identificador do holerite é obrigatório.']);
        exit;
    }

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido';

    try {
        $holeriteAtualizado = $holeriteService->registrarCiencia(
            $colaboradorLogado['matricula'],
            $holeriteId,
            $ip,
            $userAgent
        );

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Comprovante de ciência registrado com sucesso!',
            'holerite' => $holeriteAtualizado,
            'aceite' => $holeriteAtualizado['aceite']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (\Throwable $e) {
        http_response_code(400);
        echo json_encode(['erro' => $e->getMessage()]);
        exit;
    }
}

// 9. Rota de EPIs do Colaborador (NR-06)
if ($method === 'GET' && $route === '/epis') {
    $dadosEpis = $epiService->getEpisPorColaborador($colaboradorLogado['id']);

    echo json_encode([
        'sucesso' => true,
        'colaborador' => [
            'id' => $colaboradorLogado['id'],
            'nome' => $colaboradorLogado['nome'],
            'matricula' => $colaboradorLogado['matricula']
        ],
        'total_pendentes' => $dadosEpis['total_pendentes'],
        'total_assinados' => $dadosEpis['total_assinados'],
        'pendentes' => $dadosEpis['pendentes'],
        'assinados' => $dadosEpis['assinados']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 10. Rota para Assinar Eletronicamente o Recibo de EPI
if ($method === 'POST' && $route === '/epis/assinar') {
    $entregaId = $body['entrega_id'] ?? '';
    $assinaturaBase64 = $body['assinatura_base64'] ?? '';

    if (empty($entregaId) || empty($assinaturaBase64)) {
        http_response_code(400);
        echo json_encode(['erro' => 'Identificador da entrega e imagem da assinatura são obrigatórios.']);
        exit;
    }

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido';

    $entregaAssinada = $epiService->assinarEntrega(
        $colaboradorLogado['id'],
        $entregaId,
        $assinaturaBase64,
        $ip,
        $userAgent
    );

    if (!$entregaAssinada) {
        http_response_code(400);
        echo json_encode(['erro' => 'Não foi possível registrar a assinatura do EPI. Verifique a entrega.']);
        exit;
    }

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Ficha de EPI assinada com sucesso! Recibo com validade jurídica emitido.',
        'entrega' => $entregaAssinada
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 11. Rota de Detalhe / Recibo de EPI
if ($method === 'GET' && preg_match('#^/epis/recibo/([^/]+)$#', $route, $matches)) {
    $entregaId = $matches[1];
    $entrega = $epiService->findById($entregaId);

    if (!$entrega || (int)$entrega['colaborador_id'] !== (int)$colaboradorLogado['id']) {
        http_response_code(404);
        echo json_encode(['erro' => 'Recibo de EPI não localizado ou sem autorização.']);
        exit;
    }

    echo json_encode([
        'sucesso' => true,
        'entrega' => $entrega
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


// 14. Rota de Férias
if ($method === 'GET' && $route === '/ferias') {
    $ferias = $feriasController->getDadosFerias($colaboradorLogado['matricula']);
    echo json_encode([
        'sucesso' => true,
        'ferias' => $ferias
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 15. Rota de Solicitação de Férias (Desabilitada - Módulo Exclusivo para Consulta)
if ($method === 'POST' && $route === '/ferias/solicitar') {
    http_response_code(403);
    echo json_encode([
        'sucesso' => false,
        'erro' => 'A solicitação de férias pelo portal está desabilitada. O alinhamento de férias é realizado exclusivamente junto à Engenharia de Campo e ao RH da obra.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 16. Rota de Chamados de RH (Autoatendimento)
if ($method === 'GET' && $route === '/chamados') {
    $chamados = $chamadosController->getChamados($colaboradorLogado['id']);
    echo json_encode([
        'sucesso' => true,
        'chamados' => $chamados
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST' && $route === '/chamados') {
    $resp = $chamadosController->abrirChamado($colaboradorLogado['id'], $colaboradorLogado['matricula'], $body);
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

// 17. Cadernos do colaborador (todos os PDFs de data/cadernos)
if ($method === 'GET' && $route === '/cadernos') {
    echo json_encode([
        'sucesso' => true,
        'cadernos' => $cadernosController->listar()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'GET' && preg_match('#^/cadernos/arquivo$#', $route)) {
    $caminho = $cadernosController->caminhoDoArquivo($_GET['nome'] ?? '');
    if (!$caminho) {
        http_response_code(404);
        echo json_encode(['erro' => 'Caderno não encontrado.']);
        exit;
    }
    header('Content-Type: application/pdf');
    header('Content-Length: ' . filesize($caminho));
    header('Content-Disposition: inline; filename="' . basename($caminho) . '"');
    readfile($caminho);
    exit;
}

// 18. Espelho de ponto com assinatura eletrônica
if ($method === 'GET' && $route === '/ponto') {
    echo json_encode([
        'sucesso' => true,
        'espelho' => $pontoController->getEspelho($colaboradorLogado['matricula'])
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST' && $route === '/ponto/assinar') {
    $competencia = $body['competencia'] ?? '';
    $assinaturaBase64 = $body['assinatura_base64'] ?? '';
    if ($competencia === '' || $assinaturaBase64 === '') {
        http_response_code(400);
        echo json_encode(['erro' => 'Competência e assinatura são obrigatórias.']);
        exit;
    }

    $espelho = $pontoController->assinar(
        $colaboradorLogado,
        $competencia,
        $assinaturaBase64,
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido'
    );
    if (!$espelho) {
        http_response_code(400);
        echo json_encode(['erro' => 'Não foi possível assinar este espelho de ponto.']);
        exit;
    }

    echo json_encode(['sucesso' => true, 'espelho' => $espelho], JSON_UNESCAPED_UNICODE);
    exit;
}

// Rota não encontrada
http_response_code(404);
echo json_encode(['erro' => 'Endpoint não encontrado.', 'rota' => $route]);
