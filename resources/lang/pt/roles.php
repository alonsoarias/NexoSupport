<?php

/**
 * Traducciones de gestión de roles - Portugués
 *
 * @package ISER\Resources\Lang
 */

return [
    // Títulos
    'management_title' => 'Gerenciamento de Funções',
    'list_title' => 'Lista de Funções',
    'create_title' => 'Criar Função',
    'edit_title' => 'Editar Função',
    'view_title' => 'Visualizar Função',
    'permissions_title' => 'Permissões da Função',

    // Campos
    'id' => 'ID',
    'name' => 'Nome',
    'shortname' => 'Nome Curto',
    'description' => 'Descrição',
    'permissions' => 'Permissões',
    'users_count' => 'Usuários Atribuídos',
    'is_system' => 'Função do Sistema',
    'created_at' => 'Data de Criação',
    'updated_at' => 'Última Atualização',

    // Acciones
    'create_button' => 'Criar Função',
    'edit_button' => 'Editar',
    'delete_button' => 'Excluir',
    'clone_button' => 'Clonar',
    'assign_permissions' => 'Atribuir Permissões',
    'view_users' => 'Visualizar Usuários',
    'assign_to_user' => 'Atribuir a Usuário',

    // Mensajes
    'created_message' => 'Função :name criada com sucesso',
    'updated_message' => 'Função :name atualizada com sucesso',
    'deleted_message' => 'Função :name excluída com sucesso',
    'cloned_message' => 'Função :name clonada como :new_name',
    'permissions_updated' => 'Permissões da função :name atualizadas',
    'system_role_warning' => 'Esta é uma função do sistema e não pode ser excluída',
    'system_role_error' => 'Funções do sistema não podem ser excluídas',
    'users_assigned_warning' => 'Esta função tem :count usuários atribuídos',

    // Placeholders
    'name_placeholder' => 'Ex: Administrador',
    'shortname_placeholder' => 'Ex: admin',
    'description_placeholder' => 'Descrição da função...',
    'search_placeholder' => 'Pesquisar funções...',

    // Confirmaciones
    'delete_confirm' => 'Tem certeza de que deseja excluir a função :name?',
    'delete_with_users_confirm' => 'Esta função tem :count usuários atribuídos. Tem certeza de que deseja excluí-la?',

    // Validaciones
    'name_required' => 'O nome da função é obrigatório',
    'name_unique' => 'Uma função com este nome já existe',
    'shortname_required' => 'O nome curto é obrigatório',
    'shortname_unique' => 'Uma função com este nome curto já existe',
    'shortname_format' => 'O nome curto pode conter apenas letras minúsculas, números e hífens',

    // Roles predefinidos
    'roles' => [
        'admin' => 'Administrador',
        'manager' => 'Gerente',
        'user' => 'Usuário',
        'guest' => 'Convidado',
    ],

    // Estadísticas
    'total_roles' => 'Total de Funções',
    'system_roles' => 'Funções do Sistema',
    'custom_roles' => 'Funções Personalizadas',

    // Contadores
    'count_label' => '{0} Nenhuma função|{1} 1 função|[2,*] :count funções',
    'users_count_label' => '{0} Sem usuários|{1} 1 usuário|[2,*] :count usuários',
];
