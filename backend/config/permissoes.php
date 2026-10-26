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
            ['slug' => 'cobrar_pendencias', 'name' => 'Cobrar pendências pelo WhatsApp', 'group' => 'Linha'],
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
        'description' => 'Consulta dos espelhos de todos os colaboradores e importação de marcações.',
        'menu_url' => '/rh',
        'actions' => [
            ['slug' => 'visualizar', 'name' => 'Consultar espelhos de todos', 'group' => 'Tela'],
            ['slug' => 'importar', 'name' => 'Importar espelho de ponto', 'group' => 'Tela', 'route' => 'POST /rh/ponto', 'enforce' => true],
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
];
