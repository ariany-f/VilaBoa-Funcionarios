<?php
/**
 * FONTE ÚNICA das telas e ações de permissão do portal (mesmo modelo do sistema Vila Boa).
 *
 * Ao adicionar/alterar/remover uma ação:
 * 1. Edite SOMENTE este arquivo
 * 2. Execute: php backend/scripts/sincronizar_permissoes.php
 *    (grava as telas em permission_screens e as ações em permission_actions)
 * 3. Libere as ações para os perfis em roles_permission_actions
 *
 * As áreas do colaborador (holerites, ponto, cadernos) não dependem de permissão:
 * todo colaborador autenticado acessa os próprios dados.
 */

return [
    [
        'slug' => 'rh_acompanhamento',
        'name' => 'RH — Acompanhamento',
        'description' => 'Painel com recebimentos de holerite, assinaturas de ponto e leituras de cadernos.',
        'menu_url' => '/rh',
        'actions' => [
            ['slug' => 'visualizar', 'name' => 'Visualizar acompanhamento', 'group' => 'Tela'],
            ['slug' => 'cobrar_pendencias', 'name' => 'Enviar avisos pelo WhatsApp', 'group' => 'Linha'],
        ],
    ],
    [
        'slug' => 'rh_cadernos',
        'name' => 'RH — Cadernos',
        'description' => 'Publicação e configuração dos cadernos do colaborador.',
        'menu_url' => '/rh',
        'actions' => [
            ['slug' => 'visualizar', 'name' => 'Visualizar cadernos e leituras', 'group' => 'Tela'],
            ['slug' => 'publicar', 'name' => 'Publicar caderno', 'group' => 'Tela', 'route' => 'POST /rh/cadernos', 'enforce' => true],
            ['slug' => 'editar', 'name' => 'Editar categoria e público', 'group' => 'Linha', 'route' => 'POST /rh/cadernos/config', 'enforce' => true],
            ['slug' => 'remover', 'name' => 'Remover caderno', 'group' => 'Linha', 'route' => 'POST /rh/cadernos/remover', 'enforce' => true],
        ],
    ],
    [
        'slug' => 'rh_ponto',
        'name' => 'RH — Espelho de ponto',
        'description' => 'Consulta dos espelhos de todos os colaboradores, importação de marcações e publicação da escala de folgas.',
        'menu_url' => '/rh',
        'actions' => [
            ['slug' => 'visualizar', 'name' => 'Consultar espelhos de todos', 'group' => 'Tela'],
            ['slug' => 'importar', 'name' => 'Importar espelho de ponto e publicar escala de folgas', 'group' => 'Tela', 'route' => 'POST /rh/ponto', 'enforce' => true],
        ],
    ],
    [
        'slug' => 'rh_holerites',
        'name' => 'RH — Holerites',
        'description' => 'Consulta dos holerites de todos os colaboradores.',
        'menu_url' => '/rh',
        'actions' => [
            ['slug' => 'visualizar', 'name' => 'Consultar holerites de todos', 'group' => 'Tela'],
        ],
    ],
    [
        'slug' => 'rh_colaboradores',
        'name' => 'RH — Colaboradores',
        'description' => 'Cadastro dos colaboradores que acessam o portal: dados pessoais, empresa, contato e acesso.',
        'menu_url' => '/rh',
        'actions' => [
            ['slug' => 'visualizar', 'name' => 'Ver colaboradores', 'group' => 'Tela'],
            ['slug' => 'cadastrar', 'name' => 'Cadastrar colaborador', 'group' => 'Tela', 'route' => 'POST /rh/colaboradores', 'enforce' => true],
            ['slug' => 'editar', 'name' => 'Editar dados do colaborador', 'group' => 'Linha', 'route' => 'POST /rh/colaboradores', 'enforce' => true],
            ['slug' => 'inativar', 'name' => 'Inativar e reativar o acesso ao portal', 'group' => 'Linha', 'route' => 'POST /rh/colaboradores/status', 'enforce' => true],
        ],
    ],
    [
        'slug' => 'rh_usuarios',
        'name' => 'RH — Usuários',
        'description' => 'Usuários do painel do RH: cadastro, senha, perfis de permissão e empresas que cada um acessa.',
        'menu_url' => '/rh',
        'actions' => [
            ['slug' => 'visualizar', 'name' => 'Ver usuários e perfis', 'group' => 'Tela'],
            ['slug' => 'editar', 'name' => 'Criar, editar e desativar usuários e redefinir senhas', 'group' => 'Tela', 'route' => 'POST /rh/usuarios', 'enforce' => true],
            ['slug' => 'perfis', 'name' => 'Criar perfis e mudar as permissões de cada perfil', 'group' => 'Tela', 'route' => 'POST /rh/perfis', 'enforce' => true],
        ],
    ],
];
