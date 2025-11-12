<?php

/**
 * Traducciones de gestión de permisos - Portugués
 *
 * @package ISER\Resources\Lang
 */

return [
    // Títulos
    'management_title' => 'Gerenciamento de Permissões',
    'list_title' => 'Lista de Permissões',
    'by_module' => 'Permissões por Módulo',
    'role_permissions' => 'Permissões da Função',

    // Módulos
    'modules' => [
        'users' => 'Usuários',
        'roles' => 'Funções',
        'permissions' => 'Permissões',
        'dashboard' => 'Painel de Controle',
        'settings' => 'Configurações',
        'logs' => 'Registros',
        'audit' => 'Auditoria',
        'reports' => 'Relatórios',
        'sessions' => 'Sessões',
        'plugins' => 'Plugins',
    ],

    // Acciones de permisos
    'actions' => [
        'view' => 'Visualizar',
        'create' => 'Criar',
        'update' => 'Atualizar',
        'delete' => 'Excluir',
        'restore' => 'Restaurar',
        'export' => 'Exportar',
        'import' => 'Importar',
        'manage' => 'Gerenciar',
        'assign' => 'Atribuir',
    ],

    // Niveles de permisos
    'levels' => [
        'inherit' => 'Herdar',
        'allow' => 'Permitir',
        'prevent' => 'Prevenir',
        'prohibit' => 'Proibir',
    ],

    // Descripciones de niveles
    'level_descriptions' => [
        'inherit' => 'Herdar permissões da função pai ou configuração padrão',
        'allow' => 'Permitir explicitamente esta ação',
        'prevent' => 'Prevenir esta ação, mas pode ser substituída por outra função',
        'prohibit' => 'Proibir absolutamente esta ação, não pode ser substituída',
    ],

    // Descripciones de permisos por módulo
    'descriptions' => [
        'users' => [
            'view' => 'Visualizar lista e detalhes de usuários',
            'create' => 'Criar novos usuários',
            'update' => 'Atualizar informações de usuários existentes',
            'delete' => 'Excluir usuários (exclusão suave)',
            'restore' => 'Restaurar usuários excluídos',
            'export' => 'Exportar dados de usuários',
        ],
        'roles' => [
            'view' => 'Visualizar lista e detalhes de funções',
            'create' => 'Criar novas funções',
            'update' => 'Atualizar funções existentes',
            'delete' => 'Excluir funções personalizadas',
            'assign' => 'Atribuir funções a usuários',
        ],
        'permissions' => [
            'view' => 'Visualizar permissões do sistema',
            'manage' => 'Gerenciar permissões de funções',
        ],
        'settings' => [
            'view' => 'Visualizar configurações do sistema',
            'update' => 'Atualizar configurações do sistema',
        ],
        'logs' => [
            'view' => 'Visualizar registros do sistema',
            'export' => 'Exportar registros',
        ],
        'audit' => [
            'view' => 'Visualizar registros de auditoria',
            'export' => 'Exportar auditoria',
        ],
        'reports' => [
            'view' => 'Visualizar relatórios',
            'create' => 'Gerar novos relatórios',
            'export' => 'Exportar relatórios',
        ],
    ],

    // Mensajes
    'updated_message' => 'Permissões atualizadas com sucesso',
    'no_permissions' => 'Você não tem permissão para executar esta ação',
    'permission_denied' => 'Acesso negado',

    // Búsqueda y filtros
    'search_placeholder' => 'Pesquisar permissões...',
    'filter_by_module' => 'Filtrar por Módulo',
    'filter_by_level' => 'Filtrar por Nível',
];
