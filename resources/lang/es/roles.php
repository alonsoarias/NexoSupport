<?php

/**
 * Traducciones de gestión de roles - Español
 *
 * @package ISER\Resources\Lang
 */

return [
    // Títulos
    'management_title' => 'Gestión de Roles',
    'list_title' => 'Lista de Roles',
    'create_title' => 'Crear Rol',
    'edit_title' => 'Editar Rol',
    'view_title' => 'Ver Rol',
    'permissions_title' => 'Permisos del Rol',

    // Campos
    'id' => 'ID',
    'name' => 'Nombre',
    'shortname' => 'Nombre Corto',
    'description' => 'Descripción',
    'permissions' => 'Permisos',
    'users_count' => 'Usuarios Asignados',
    'is_system' => 'Rol del Sistema',
    'created_at' => 'Fecha de Creación',
    'updated_at' => 'Última Actualización',

    // Acciones
    'create_button' => 'Crear Rol',
    'edit_button' => 'Editar',
    'delete_button' => 'Eliminar',
    'clone_button' => 'Clonar',
    'assign_permissions' => 'Asignar Permisos',
    'view_users' => 'Ver Usuarios',
    'assign_to_user' => 'Asignar a Usuario',

    // Mensajes
    'created_message' => 'Rol :name creado exitosamente',
    'updated_message' => 'Rol :name actualizado exitosamente',
    'deleted_message' => 'Rol :name eliminado exitosamente',
    'cloned_message' => 'Rol :name clonado como :new_name',
    'permissions_updated' => 'Permisos del rol :name actualizados',
    'system_role_warning' => 'Este es un rol del sistema y no puede ser eliminado',
    'system_role_error' => 'Los roles del sistema no pueden ser eliminados',
    'users_assigned_warning' => 'Este rol tiene :count usuarios asignados',

    // Placeholders
    'name_placeholder' => 'Ej: Administrador',
    'shortname_placeholder' => 'Ej: admin',
    'description_placeholder' => 'Descripción del rol...',
    'search_placeholder' => 'Buscar roles...',

    // Confirmaciones
    'delete_confirm' => '¿Está seguro de que desea eliminar el rol :name?',
    'delete_with_users_confirm' => 'Este rol tiene :count usuarios asignados. ¿Está seguro de que desea eliminarlo?',

    // Validaciones
    'name_required' => 'El nombre del rol es requerido',
    'name_min_length' => 'El nombre del rol debe tener al menos :min caracteres',
    'name_unique' => 'Ya existe un rol con este nombre',
    'slug_required' => 'El slug del rol es requerido',
    'slug_format' => 'El slug solo puede contener letras minúsculas, números y guiones bajos',
    'shortname_required' => 'El nombre corto es requerido',
    'shortname_unique' => 'Ya existe un rol con este nombre corto',
    'shortname_format' => 'El nombre corto solo puede contener letras minúsculas, números y guiones',

    // Roles predefinidos
    'roles' => [
        'admin' => 'Administrador',
        'manager' => 'Gerente',
        'user' => 'Usuario',
        'guest' => 'Invitado',
    ],

    // Estadísticas
    'total_roles' => 'Total de Roles',
    'system_roles' => 'Roles del Sistema',
    'custom_roles' => 'Roles Personalizados',

    // Contadores
    'count_label' => '{0} No hay roles|{1} 1 rol|[2,*] :count roles',
    'users_count_label' => '{0} Sin usuarios|{1} 1 usuario|[2,*] :count usuarios',
];
