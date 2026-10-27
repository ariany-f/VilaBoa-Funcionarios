<?php
/**
 * Cria (ou atualiza a senha de) um usuário do Painel do RH (/admin) e liga ao perfil.
 *
 * Uso: php backend/scripts/criar_usuario_admin.php email@empresa.com.br "Nome Completo" "senha" [perfil]
 *      perfil: RH (padrão), Administrador ou "RH - Consulta"
 */

require_once dirname(__DIR__) . '/config/dados_iniciais.php';
prepararDadosIniciais();
require_once dirname(__DIR__) . '/database/Banco.php';

use App\Database\Banco;

[$_, $email, $nome, $senha, $perfil] = array_pad($argv, 5, null);
if (!$email || !$nome || !$senha) {
    fwrite(STDERR, "Uso: php backend/scripts/criar_usuario_admin.php email \"Nome\" senha [perfil]\n");
    exit(1);
}
if (strlen($senha) < 8) {
    fwrite(STDERR, "A senha precisa ter pelo menos 8 caracteres.\n");
    exit(1);
}

$banco = Banco::conexao();
$role = $banco->primeiro('roles', ['nome' => $perfil ?: 'RH']);
if (!$role) {
    fwrite(STDERR, "Perfil não encontrado: " . ($perfil ?: 'RH') . "\n");
    exit(1);
}

$hash = password_hash($senha, PASSWORD_DEFAULT);
$existente = null;
foreach ($banco->buscar('users') as $u) {
    if (mb_strtolower($u['email']) === mb_strtolower($email)) {
        $existente = $u;
    }
}

if ($existente) {
    $banco->atualizar('users', (int)$existente['id'], ['name' => $nome, 'password' => $hash]);
    $userId = (int)$existente['id'];
} else {
    $userId = (int)$banco->inserir('users', ['name' => $nome, 'email' => $email, 'password' => $hash, 'cpf' => null])['id'];
}

if (!$banco->primeiro('users_roles', ['user_id' => $userId, 'role_id' => (int)$role['id']])) {
    $banco->inserir('users_roles', ['user_id' => $userId, 'role_id' => (int)$role['id']]);
}

echo "Usuário {$email} pronto no perfil {$role['nome']} (driver {$banco->driver()}).\n";
