<?php

namespace App\Services;

use App\Database\Banco;

/**
 * Tela "Usuários" do painel do RH: usuários do /admin (users), perfis (roles), as ações liberadas
 * para cada perfil (roles_permission_actions) e as empresas de cada usuário (users_empresas).
 *
 * Regras de segurança:
 *  - quem acessa só algumas empresas só dá acesso a essas empresas e não mexe em usuários que
 *    acessam empresas que ele não acessa (nem em quem acessa todas);
 *  - ninguém desativa a si mesmo;
 *  - sempre sobra pelo menos um usuário ativo que pode gerenciar usuários (rh_usuarios/editar).
 */
class UsuariosRhService
{
    private const SENHA_MINIMA = 8;

    public function __construct(private Banco $banco, private PermissaoService $permissoes)
    {
    }

    // ------------------------------------------------------------------ leitura

    /** Tudo o que a tela precisa: usuários, perfis, telas/ações e empresas que o usuário logado pode dar. */
    public function painel(?array $empresasDoGestor): array
    {
        $perfisPorUsuario = [];
        foreach ($this->banco->buscar('users_roles') as $ur) {
            $perfisPorUsuario[(int)$ur['user_id']][] = (int)$ur['role_id'];
        }

        $usuarios = array_map(function ($u) use ($perfisPorUsuario, $empresasDoGestor) {
            $empresas = EmpresaService::nomesDoUsuarioRh($u);
            return [
                'id' => (int)$u['id'],
                'nome' => $u['name'],
                'email' => $u['email'],
                'cpf' => $u['cpf'] ?? null,
                'ativo' => PermissaoService::ativo($u),
                'perfis' => array_values(array_unique($perfisPorUsuario[(int)$u['id']] ?? [])),
                'empresas' => $empresas, // null = todas
                'pode_gerenciar' => $this->gestorAlcanca($empresasDoGestor, $empresas),
                'criado_em' => $u['created'] ?? null,
            ];
        }, $this->banco->buscar('users'));
        usort($usuarios, fn($a, $b) => [!$a['ativo'], mb_strtolower($a['nome'])] <=> [!$b['ativo'], mb_strtolower($b['nome'])]);

        $acoesPorPerfil = [];
        foreach ($this->banco->buscar('roles_permission_actions') as $rpa) {
            $acoesPorPerfil[(int)$rpa['role_id']][] = (int)$rpa['permission_action_id'];
        }
        $perfis = array_map(fn($r) => [
            'id' => (int)$r['id'],
            'nome' => $r['nome'],
            'acoes' => array_values(array_unique($acoesPorPerfil[(int)$r['id']] ?? [])),
            'usuarios' => count(array_filter($usuarios, fn($u) => in_array((int)$r['id'], $u['perfis'], true))),
        ], $this->banco->buscar('roles'));
        usort($perfis, fn($a, $b) => strcasecmp($a['nome'], $b['nome']));

        $telas = array_values(array_filter($this->banco->buscar('permission_screens', ['is_active' => 1]), fn($t) => str_starts_with($t['slug'], 'rh_')));
        usort($telas, fn($a, $b) => (int)$a['position'] <=> (int)$b['position']);
        $acoes = $this->banco->buscar('permission_actions', ['is_active' => 1]);
        usort($acoes, fn($a, $b) => (int)$a['position'] <=> (int)$b['position']);

        return [
            'usuarios' => $usuarios,
            'perfis' => $perfis,
            'telas' => array_map(fn($t) => [
                'slug' => $t['slug'],
                'nome' => preg_replace('/^RH\s*[—-]\s*/u', '', $t['name']),
                'descricao' => $t['description'] ?? '',
                'acoes' => array_values(array_map(fn($a) => ['id' => (int)$a['id'], 'slug' => $a['slug'], 'nome' => $a['name']], array_filter($acoes, fn($a) => (int)$a['screen_id'] === (int)$t['id']))),
            ], $telas),
            'empresas' => $empresasDoGestor ?? EmpresaService::listar(),
            'acessa_todas' => $empresasDoGestor === null,
        ];
    }

    // ------------------------------------------------------------------ usuários

    /** Cria (sem id) ou edita um usuário: nome, e-mail, CPF, perfis e empresas. Senha só na criação. */
    public function salvar(array $dados, array $gestor, ?array $empresasDoGestor): array
    {
        $id = (int)($dados['id'] ?? 0);
        $atual = $id ? $this->banco->primeiro('users', ['id' => $id]) : null;
        if ($id && !$atual) return ['erro' => 'Usuário não encontrado.', 'status' => 404];
        if ($atual && !$this->gestorAlcanca($empresasDoGestor, EmpresaService::nomesDoUsuarioRh($atual))) {
            return ['erro' => 'Este usuário acessa empresas que você não acessa. Peça para quem acessa todas as empresas.', 'status' => 403];
        }

        $nome = trim(preg_replace('/\s+/', ' ', (string)($dados['nome'] ?? '')));
        if ($nome === '' || mb_strlen($nome) > 120) return ['erro' => 'Informe o nome (até 120 caracteres).', 'status' => 422];

        $email = mb_strtolower(trim((string)($dados['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['erro' => 'Informe um e-mail válido.', 'status' => 422];
        $outro = $this->permissoes->usuarioPorEmail($email);
        if ($outro && (int)$outro['id'] !== $id) return ['erro' => 'Já existe um usuário com este e-mail.', 'status' => 409];

        $cpf = preg_replace('/\D/', '', (string)($dados['cpf'] ?? ''));
        if ($cpf !== '' && strlen($cpf) !== 11) return ['erro' => 'O CPF precisa ter 11 dígitos (ou deixe em branco).', 'status' => 422];
        if ($cpf !== '') {
            $comCpf = $this->banco->primeiro('users', ['cpf' => $cpf]);
            if ($comCpf && (int)$comCpf['id'] !== $id) return ['erro' => 'Este CPF já está ligado a outro usuário.', 'status' => 409];
        }

        $todosPerfis = array_map(fn($r) => (int)$r['id'], $this->banco->buscar('roles'));
        $perfis = array_values(array_unique(array_filter(array_map('intval', (array)($dados['perfis'] ?? [])), fn($p) => in_array($p, $todosPerfis, true))));
        if (!$perfis) return ['erro' => 'Escolha pelo menos um perfil.', 'status' => 422];

        $empresas = $this->empresasPedidas($dados, $empresasDoGestor);
        if (isset($empresas['erro'])) return $empresas;
        $empresas = $empresas['empresas'];

        $senha = (string)($dados['senha'] ?? '');
        if (!$atual && mb_strlen($senha) < self::SENHA_MINIMA) return ['erro' => 'A senha precisa ter pelo menos ' . self::SENHA_MINIMA . ' caracteres.', 'status' => 422];

        // Antes de gravar: a mudança não pode deixar o painel sem ninguém para gerenciar usuários
        if ($atual && !$this->sobraGestor($id, ['perfis' => $perfis])) {
            return ['erro' => 'Com esses perfis ninguém mais poderia gerenciar usuários. Mantenha um perfil com essa permissão.', 'status' => 409];
        }

        $campos = ['name' => $nome, 'email' => $email, 'cpf' => $cpf !== '' ? $cpf : null];
        if ($atual) {
            $this->banco->atualizar('users', $id, $campos);
        } else {
            $id = (int)$this->banco->inserir('users', $campos + ['password' => password_hash($senha, PASSWORD_DEFAULT), 'is_active' => 1])['id'];
        }

        $this->banco->excluir('users_roles', ['user_id' => $id]);
        foreach ($perfis as $p) {
            $this->banco->inserir('users_roles', ['user_id' => $id, 'role_id' => $p]);
        }
        EmpresaService::definirDoUsuarioRh($id, $email, $empresas);

        return ['sucesso' => true, 'id' => $id, 'criado' => !$atual];
    }

    public function definirAtivo(int $id, bool $ativo, array $gestor, ?array $empresasDoGestor): array
    {
        $usuario = $this->banco->primeiro('users', ['id' => $id]);
        if (!$usuario) return ['erro' => 'Usuário não encontrado.', 'status' => 404];
        if (!$this->gestorAlcanca($empresasDoGestor, EmpresaService::nomesDoUsuarioRh($usuario))) {
            return ['erro' => 'Este usuário acessa empresas que você não acessa.', 'status' => 403];
        }
        if (!$ativo && $id === (int)$gestor['id']) return ['erro' => 'Você não pode desativar o seu próprio usuário.', 'status' => 409];
        if (!$ativo && !$this->sobraGestor($id, ['ativo' => false])) {
            return ['erro' => 'Este é o último usuário ativo que gerencia usuários. Dê essa permissão a outra pessoa antes.', 'status' => 409];
        }
        $this->banco->atualizar('users', $id, ['is_active' => $ativo ? 1 : 0]);
        return ['sucesso' => true];
    }

    public function redefinirSenha(int $id, string $senha, ?array $empresasDoGestor): array
    {
        $usuario = $this->banco->primeiro('users', ['id' => $id]);
        if (!$usuario) return ['erro' => 'Usuário não encontrado.', 'status' => 404];
        if (!$this->gestorAlcanca($empresasDoGestor, EmpresaService::nomesDoUsuarioRh($usuario))) {
            return ['erro' => 'Este usuário acessa empresas que você não acessa.', 'status' => 403];
        }
        if (mb_strlen($senha) < self::SENHA_MINIMA) return ['erro' => 'A senha precisa ter pelo menos ' . self::SENHA_MINIMA . ' caracteres.', 'status' => 422];
        $this->banco->atualizar('users', $id, ['password' => password_hash($senha, PASSWORD_DEFAULT)]);
        return ['sucesso' => true];
    }

    // ------------------------------------------------------------------ perfis

    /** Cria (sem id) ou edita um perfil: nome e as ações liberadas. */
    public function salvarPerfil(array $dados): array
    {
        $id = (int)($dados['id'] ?? 0);
        if ($id && !$this->banco->primeiro('roles', ['id' => $id])) return ['erro' => 'Perfil não encontrado.', 'status' => 404];

        $nome = trim(preg_replace('/\s+/', ' ', (string)($dados['nome'] ?? '')));
        if ($nome === '' || mb_strlen($nome) > 60) return ['erro' => 'Informe o nome do perfil (até 60 caracteres).', 'status' => 422];
        foreach ($this->banco->buscar('roles') as $r) {
            if ((int)$r['id'] !== $id && mb_strtolower($r['nome']) === mb_strtolower($nome)) return ['erro' => 'Já existe um perfil com este nome.', 'status' => 409];
        }

        $validas = array_map(fn($a) => (int)$a['id'], $this->banco->buscar('permission_actions', ['is_active' => 1]));
        $acoes = array_values(array_unique(array_filter(array_map('intval', (array)($dados['acoes'] ?? [])), fn($a) => in_array($a, $validas, true))));

        if ($id && !$this->sobraGestor(0, ['perfil' => $id, 'acoes' => $acoes])) {
            return ['erro' => 'Sem a permissão de gerenciar usuários neste perfil, ninguém mais poderia gerenciar usuários.', 'status' => 409];
        }

        if ($id) {
            $this->banco->atualizar('roles', $id, ['nome' => $nome]);
        } else {
            $id = (int)$this->banco->inserir('roles', ['nome' => $nome])['id'];
        }

        // Ações: mantém as que continuam (preserva datas) e troca o resto
        $existentes = $this->banco->buscar('roles_permission_actions', ['role_id' => $id]);
        foreach ($existentes as $rpa) {
            if (!in_array((int)$rpa['permission_action_id'], $acoes, true)) {
                $this->banco->excluir('roles_permission_actions', ['id' => (int)$rpa['id']]);
            }
        }
        $jaTem = array_map(fn($r) => (int)$r['permission_action_id'], $existentes);
        foreach (array_diff($acoes, $jaTem) as $acao) {
            $this->banco->inserir('roles_permission_actions', ['role_id' => $id, 'permission_action_id' => $acao]);
        }

        // Todo perfil do RH entra pelo menu do painel (sem menu, o login do /admin recusa)
        $menuRh = $this->banco->primeiro('menus', ['url' => '/rh']);
        if ($menuRh && !$this->banco->primeiro('roles_menus', ['role_id' => $id, 'menu_id' => (int)$menuRh['id']])) {
            $this->banco->inserir('roles_menus', ['role_id' => $id, 'menu_id' => (int)$menuRh['id']]);
        }

        return ['sucesso' => true, 'id' => $id];
    }

    public function removerPerfil(int $id): array
    {
        $perfil = $this->banco->primeiro('roles', ['id' => $id]);
        if (!$perfil) return ['erro' => 'Perfil não encontrado.', 'status' => 404];
        $usando = count($this->banco->buscar('users_roles', ['role_id' => $id]));
        if ($usando) return ['erro' => "Há {$usando} usuário(s) com este perfil. Troque o perfil deles antes de remover.", 'status' => 409];
        $this->banco->excluir('roles_permission_actions', ['role_id' => $id]);
        $this->banco->excluir('roles_menus', ['role_id' => $id]);
        $this->banco->excluir('roles', ['id' => $id]);
        return ['sucesso' => true];
    }

    // ------------------------------------------------------------------ apoio

    /** O gestor (empresas dele; null = todas) pode mexer num usuário com estas empresas? */
    private function gestorAlcanca(?array $empresasDoGestor, ?array $empresasDoUsuario): bool
    {
        if ($empresasDoGestor === null) return true;
        if ($empresasDoUsuario === null) return false;
        return !array_diff($empresasDoUsuario, $empresasDoGestor);
    }

    /** Empresas escolhidas no formulário: ['empresas' => null (todas) | [nomes]] ou ['erro' => ...]. */
    private function empresasPedidas(array $dados, ?array $empresasDoGestor): array
    {
        if (!empty($dados['todas_empresas'])) {
            if ($empresasDoGestor !== null) return ['erro' => 'Só quem acessa todas as empresas pode dar acesso a todas.', 'status' => 403];
            return ['empresas' => null];
        }
        $nomes = [];
        foreach ((array)($dados['empresas'] ?? []) as $n) {
            $nome = EmpresaService::normalizar((string)$n);
            if (!$nome) return ['erro' => 'Empresa não encontrada: ' . $n, 'status' => 422];
            if ($empresasDoGestor !== null && !in_array($nome, $empresasDoGestor, true)) {
                return ['erro' => "Você não pode dar acesso à {$nome}, porque você não acessa essa empresa.", 'status' => 403];
            }
            $nomes[] = $nome;
        }
        $nomes = array_values(array_unique($nomes));
        if (!$nomes) return ['erro' => 'Escolha pelo menos uma empresa (ou "Todas as empresas").', 'status' => 422];
        return ['empresas' => $nomes];
    }

    /**
     * Depois da mudança simulada, ainda existe um usuário ativo com rh_usuarios/editar?
     * $mudanca: ['ativo' => false] ou ['perfis' => [...]] para o usuário $userId,
     * ou ['perfil' => id, 'acoes' => [...]] para as ações de um perfil.
     */
    private function sobraGestor(int $userId, array $mudanca): bool
    {
        $tela = $this->banco->primeiro('permission_screens', ['slug' => 'rh_usuarios']);
        $acao = $tela ? $this->banco->primeiro('permission_actions', ['screen_id' => (int)$tela['id'], 'slug' => 'editar']) : null;
        if (!$acao) return true;
        $acaoId = (int)$acao['id'];

        $perfisGestores = [];
        foreach ($this->banco->buscar('roles') as $r) {
            $rid = (int)$r['id'];
            $tem = isset($mudanca['perfil']) && $mudanca['perfil'] === $rid
                ? in_array($acaoId, $mudanca['acoes'], true)
                : (bool)$this->banco->primeiro('roles_permission_actions', ['role_id' => $rid, 'permission_action_id' => $acaoId]);
            if ($tem) $perfisGestores[] = $rid;
        }

        foreach ($this->banco->buscar('users') as $u) {
            $uid = (int)$u['id'];
            $ativo = $uid === $userId && array_key_exists('ativo', $mudanca) ? $mudanca['ativo'] : PermissaoService::ativo($u);
            if (!$ativo) continue;
            $perfis = $uid === $userId && isset($mudanca['perfis'])
                ? $mudanca['perfis']
                : $this->permissoes->perfisDoUsuario($uid);
            if (array_intersect($perfis, $perfisGestores)) return true;
        }
        return false;
    }
}
