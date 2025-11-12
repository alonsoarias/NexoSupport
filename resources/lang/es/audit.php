<?php

/**
 * Traducciones de auditoría - Español
 *
 * @package ISER\Resources\Lang
 */

return [
    // Títulos
    'title' => 'Registro de Auditoría',
    'view' => 'Ver Auditoría',
    'search' => 'Buscar en Auditoría',
    'trail' => 'Rastro de Auditoría',

    // Tipos de eventos
    'event_types' => [
        'create' => 'Crear',
        'read' => 'Leer',
        'update' => 'Actualizar',
        'delete' => 'Eliminar',
        'restore' => 'Restaurar',
        'login' => 'Iniciar Sesión',
        'logout' => 'Cerrar Sesión',
        'failed_login' => 'Inicio de Sesión Fallido',
        'password_change' => 'Cambio de Contraseña',
        'password_reset' => 'Restablecimiento de Contraseña',
        'permission_change' => 'Cambio de Permiso',
        'role_change' => 'Cambio de Rol',
        'settings_change' => 'Cambio de Configuración',
        'export' => 'Exportar',
        'import' => 'Importar',
    ],

    // Entidades auditadas
    'entities' => [
        'user' => 'Usuario',
        'role' => 'Rol',
        'permission' => 'Permiso',
        'setting' => 'Configuración',
        'log' => 'Registro',
        'session' => 'Sesión',
        'plugin' => 'Plugin',
        'report' => 'Reporte',
    ],

    // Campos
    'id' => 'ID',
    'timestamp' => 'Fecha y Hora',
    'user' => 'Usuario',
    'event' => 'Evento',
    'entity_type' => 'Tipo de Entidad',
    'entity_id' => 'ID de Entidad',
    'description' => 'Descripción',
    'ip_address' => 'Dirección IP',
    'user_agent' => 'Navegador',
    'old_values' => 'Valores Anteriores',
    'new_values' => 'Valores Nuevos',
    'changes' => 'Cambios',
    'details' => 'Detalles',

    // Filtros
    'filters' => [
        'event_type' => 'Tipo de Evento',
        'entity_type' => 'Tipo de Entidad',
        'user' => 'Usuario',
        'date_from' => 'Desde',
        'date_to' => 'Hasta',
        'ip' => 'IP',
    ],

    // Descripciones de eventos
    'descriptions' => [
        'user_created' => ':user creó el usuario :target',
        'user_updated' => ':user actualizó el usuario :target',
        'user_deleted' => ':user eliminó el usuario :target',
        'user_restored' => ':user restauró el usuario :target',
        'role_created' => ':user creó el rol :target',
        'role_updated' => ':user actualizó el rol :target',
        'role_deleted' => ':user eliminó el rol :target',
        'role_assigned' => ':user asignó el rol :role al usuario :target',
        'role_removed' => ':user removió el rol :role del usuario :target',
        'permission_granted' => ':user otorgó el permiso :permission',
        'permission_revoked' => ':user revocó el permiso :permission',
        'settings_updated' => ':user actualizó la configuración :setting',
        'login_success' => ':user inició sesión desde :ip',
        'login_failed' => 'Intento fallido de inicio de sesión para :username desde :ip',
        'logout' => ':user cerró sesión',
        'password_changed' => ':user cambió su contraseña',
        'password_reset' => ':user restableció la contraseña de :target',
        'data_exported' => ':user exportó :entity',
        'data_imported' => ':user importó :entity',
    ],

    // Acciones
    'view_details' => 'Ver Detalles',
    'view_changes' => 'Ver Cambios',
    'export' => 'Exportar Auditoría',
    'filter' => 'Filtrar',
    'clear_filters' => 'Limpiar Filtros',
    'refresh' => 'Actualizar',

    // Mensajes
    'no_records' => 'No hay registros de auditoría para mostrar',
    'loading' => 'Cargando auditoría...',
    'exported_successfully' => 'Auditoría exportada exitosamente',

    // Estadísticas
    'stats' => [
        'total_events' => 'Total de Eventos',
        'events_today' => 'Eventos Hoy',
        'unique_users' => 'Usuarios Únicos',
        'by_event_type' => 'Por Tipo de Evento',
        'by_entity' => 'Por Entidad',
        'most_active_users' => 'Usuarios Más Activos',
        'recent_activity' => 'Actividad Reciente',
    ],

    // Detalles de cambios
    'change_details' => [
        'field' => 'Campo',
        'old_value' => 'Valor Anterior',
        'new_value' => 'Valor Nuevo',
        'no_changes' => 'Sin cambios registrados',
    ],

    // Tabla
    'table' => [
        'showing' => 'Mostrando :from a :to de :total registros',
        'per_page' => 'Por página',
        'no_results' => 'No se encontraron resultados',
    ],

    // Períodos
    'periods' => [
        'today' => 'Hoy',
        'yesterday' => 'Ayer',
        'last_7_days' => 'Últimos 7 Días',
        'last_30_days' => 'Últimos 30 Días',
        'this_month' => 'Este Mes',
        'last_month' => 'Mes Anterior',
        'custom' => 'Personalizado',
    ],
];
