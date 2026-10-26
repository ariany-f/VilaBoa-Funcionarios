<?php
/**
 * Grava as telas e ações de backend/config/permissoes.php nas tabelas
 * permission_screens e permission_actions (usa o driver de config/database.php).
 *
 * Uso: php backend/scripts/sincronizar_permissoes.php
 */

require_once dirname(__DIR__) . '/config/dados_iniciais.php';
prepararDadosIniciais();
require_once dirname(__DIR__) . '/database/Banco.php';
require_once dirname(__DIR__) . '/services/PermissaoService.php';

use App\Database\Banco;
use App\Services\PermissaoService;

$banco = Banco::conexao();
$resumo = (new PermissaoService($banco))->sincronizar();

echo "Driver: {$banco->driver()}\n";
echo "Telas novas: {$resumo['telas']} · Ações novas: {$resumo['acoes']}\n";
