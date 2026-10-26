<?php

namespace App\Services;

use App\Database\Banco;

/**
 * Permissões por perfil, no mesmo modelo do sistema Vila Boa:
 * users -> users_roles -> roles -> roles_permission_actions -> permission_actions -> permission_screens
 * e roles_menus -> menus para os itens de menu.
 *
 * O colaborador é ligado ao usuário pelo CPF (users.cpf). Quem não tem usuário/perfil
 * acessa apenas a área do colaborador.
 */
class PermissaoService
{
    public function __construct(private Banco $banco)
    {
    }

    /** Usuário vinculado ao colaborador (pelo CPF, só dígitos). */
    public function usuarioDoColaborador(array $colaborador): ?array
    {
        $cpf = preg_replace('/\D/', '', $colaborador['cpf'] ?? '');
        return $cpf ? $this->banco->primeiro('users', ['cpf' => $cpf]) : null;
    }

    /** IDs dos perfis do usuário. */
    public function perfisDoUsuario(int $userId): array
    {
        return array_map(fn($r) => (int)$r['role_id'], $this->banco->buscar('users_roles', ['user_id' => $userId]));
    }

    /**
     * Matriz de permissões: [slug_da_tela][slug_da_acao] = true
     * (mesma estrutura do ActionPermissionService::buildMatrixForRoles do sistema Vila Boa).
     */
    public function matrizDoColaborador(array $colaborador): array
    {
        $usuario = $this->usuarioDoColaborador($colaborador);
        if (!$usuario) {
            return [];
        }
        $perfis = $this->perfisDoUsuario((int)$usuario['id']);
        if (!$perfis) {
            return [];
        }

        $acaoIds = array_map(fn($r) => (int)$r['permission_action_id'], $this->banco->buscar('roles_permission_actions', ['role_id' => $perfis]));
        $acoes = $this->banco->buscar('permission_actions', ['id' => $acaoIds, 'is_active' => 1]);
        $telas = [];
        foreach ($this->banco->buscar('permission_screens', ['is_active' => 1]) as $tela) {
            $telas[(int)$tela['id']] = $tela['slug'];
        }

        $matriz = [];
        foreach ($acoes as $acao) {
            $tela = $telas[(int)$acao['screen_id']] ?? null;
            if ($tela) {
                $matriz[$tela][$acao['slug']] = true;
            }
        }
        return $matriz;
    }

    /** Menus liberados para os perfis do colaborador (roles_menus). */
    public function menusDoColaborador(array $colaborador): array
    {
        $usuario = $this->usuarioDoColaborador($colaborador);
        if (!$usuario) {
            return [];
        }
        $perfis = $this->perfisDoUsuario((int)$usuario['id']);
        $menuIds = array_map(fn($r) => (int)$r['menu_id'], $this->banco->buscar('roles_menus', ['role_id' => $perfis ?: [0]]));
        $menus = $this->banco->buscar('menus', ['id' => $menuIds]);
        usort($menus, fn($a, $b) => $a['position'] <=> $b['position']);
        return array_map(fn($m) => ['name' => $m['name'], 'url' => $m['url'], 'icon' => $m['icon']], $menus);
    }

    public static function pode(array $matriz, string $tela, string $acao): bool
    {
        return !empty($matriz[$tela][$acao]);
    }

    /**
     * Grava em permission_screens/permission_actions o que está em config/permissoes.php
     * (equivalente ao "bin/cake action_permissions sync" do sistema Vila Boa).
     */
    public function sincronizar(): array
    {
        $resumo = ['telas' => 0, 'acoes' => 0];
        foreach (require dirname(__DIR__) . '/config/permissoes.php' as $posTela => $tela) {
            $dadosTela = [
                'slug' => $tela['slug'],
                'name' => $tela['name'],
                'description' => $tela['description'] ?? null,
                'controller' => $tela['controller'] ?? null,
                'position' => $posTela,
                'is_active' => 1,
            ];
            $existente = $this->banco->primeiro('permission_screens', ['slug' => $tela['slug']]);
            if ($existente) {
                $this->banco->atualizar('permission_screens', (int)$existente['id'], $dadosTela);
                $telaId = (int)$existente['id'];
            } else {
                $telaId = (int)$this->banco->inserir('permission_screens', $dadosTela)['id'];
                $resumo['telas']++;
            }

            foreach ($tela['actions'] as $posAcao => $acao) {
                [$metodo, $rota] = isset($acao['route']) ? explode(' ', $acao['route'], 2) : [null, null];
                $dadosAcao = [
                    'screen_id' => $telaId,
                    'slug' => $acao['slug'],
                    'name' => $acao['name'],
                    'description' => $acao['description'] ?? null,
                    'group_name' => $acao['group'] ?? null,
                    'action_kind' => 'execute',
                    'route_controller' => $rota ? 'Api' : null,
                    'route_action' => $rota,
                    'http_methods' => $metodo,
                    'enforce_route' => !empty($acao['enforce']) ? 1 : 0,
                    'ui_selector' => $acao['ui'] ?? null,
                    'meta' => isset($acao['meta']) ? json_encode($acao['meta']) : null,
                    'position' => $posAcao,
                    'is_active' => 1,
                ];
                $existenteAcao = $this->banco->primeiro('permission_actions', ['screen_id' => $telaId, 'slug' => $acao['slug']]);
                if ($existenteAcao) {
                    $this->banco->atualizar('permission_actions', (int)$existenteAcao['id'], $dadosAcao);
                } else {
                    $this->banco->inserir('permission_actions', $dadosAcao);
                    $resumo['acoes']++;
                }
            }
        }
        return $resumo;
    }
}
