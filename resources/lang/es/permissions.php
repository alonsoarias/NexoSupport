<?php

/**
 * Traducciones de gestión de permisos - Español
 *
 * @package ISER\Resources\Lang
 */

return [
    // Títulos
    'management_title' => 'Gestión de Permisos',
    'list_title' => 'Lista de Permisos',
    'by_module' => 'Permisos por Módulo',
    'role_permissions' => 'Permisos del Rol',

    // Módulos
    'modules' => [
        'users' => 'Usuarios',
        'roles' => 'Roles',
        'permissions' => 'Permisos',
        'dashboard' => 'Panel de Control',
        'settings' => 'Configuración',
        'logs' => 'Registros',
        'audit' => 'Auditoría',
        'reports' => 'Reportes',
        'sessions' => 'Sesiones',
        'plugins' => 'Plugins',
    ],

    // Acciones de permisos
    'actions' => [
        'view' => 'Ver',
        'create' => 'Crear',
        'update' => 'Actualizar',
        'delete' => 'Eliminar',
        'restore' => 'Restaurar',
        'export' => 'Exportar',
        'import' => 'Importar',
        'manage' => 'Gestionar',
        'assign' => 'Asignar',
    ],

    // Niveles de permisos
    'levels' => [
        'inherit' => 'Heredar',
        'allow' => 'Permitir',
        'prevent' => 'Prevenir',
        'prohibit' => 'Prohibir',
    ],

    // Descripciones de niveles
    'level_descriptions' => [
        'inherit' => 'Heredar permisos del rol padre o configuración por defecto',
        'allow' => 'Permitir explícitamente esta acción',
        'prevent' => 'Prevenir esta acción, pero puede ser sobrescrita por otro rol',
        'prohibit' => 'Prohibir absolutamente esta acción, no puede ser sobrescrita',
    ],

    // Descripciones de permisos por módulo
    'descriptions' => [
        'users' => [
            'view' => 'Ver lista y detalles de usuarios',
            'create' => 'Crear nuevos usuarios',
            'update' => 'Actualizar información de usuarios existentes',
            'delete' => 'Eliminar usuarios (soft delete)',
            'restore' => 'Restaurar usuarios eliminados',
            'export' => 'Exportar datos de usuarios',
        ],
        'roles' => [
            'view' => 'Ver lista y detalles de roles',
            'create' => 'Crear nuevos roles',
            'update' => 'Actualizar roles existentes',
            'delete' => 'Eliminar roles personalizados',
            'assign' => 'Asignar roles a usuarios',
        ],
        'permissions' => [
            'view' => 'Ver permisos del sistema',
            'manage' => 'Gestionar permisos de roles',
        ],
        'settings' => [
            'view' => 'Ver configuración del sistema',
            'update' => 'Actualizar configuración del sistema',
        ],
        'logs' => [
            'view' => 'Ver registros del sistema',
            'export' => 'Exportar registros',
        ],
        'audit' => [
            'view' => 'Ver registros de auditoría',
            'export' => 'Exportar auditoría',
        ],
        'reports' => [
            'view' => 'Ver reportes',
            'create' => 'Generar nuevos reportes',
            'export' => 'Exportar reportes',
        ],
    ],

    // Mensajes
    'created_message' => 'Permiso :name creado correctamente',
    'updated_message' => 'Permiso :name actualizado correctamente',
    'deleted_message' => 'Permiso :name eliminado correctamente',
    'no_permissions' => 'No tiene permisos para realizar esta acción',
    'permission_denied' => 'Acceso denegado',
    'name_required' => 'El nombre del permiso es requerido',

    // Búsqueda y filtros
    'search_placeholder' => 'Buscar permisos...',
    'filter_by_module' => 'Filtrar por Módulo',
    'filter_by_level' => 'Filtrar por Nivel',
];
