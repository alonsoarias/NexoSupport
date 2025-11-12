<?php

/**
 * Traducciones del dashboard - Español
 *
 * @package ISER\Resources\Lang
 */

return [
    // Título
    'title' => 'Panel de Control',
    'welcome' => 'Bienvenido, :name',
    'welcome_message' => 'Bienvenido al sistema de autenticación ISER',

    // Widgets de estadísticas
    'stats' => [
        'total_users' => 'Usuarios Totales',
        'active_roles' => 'Roles Activos',
        'plugins_installed' => 'Plugins Instalados',
        'logins_today' => 'Accesos Hoy',
        'new_users_week' => 'Nuevos Usuarios (7 días)',
        'failed_logins_today' => 'Intentos Fallidos Hoy',
        'active_sessions' => 'Sesiones Activas',
        'system_health' => 'Salud del Sistema',
    ],

    // Gráficos
    'charts' => [
        'user_activity' => 'Actividad de Usuarios',
        'login_attempts' => 'Intentos de Acceso',
        'user_growth' => 'Crecimiento de Usuarios',
        'daily' => 'Diario',
        'weekly' => 'Semanal',
        'monthly' => 'Mensual',
        'success' => 'Exitosos',
        'failed' => 'Fallidos',
    ],

    // Acciones rápidas
    'quick_actions' => [
        'title' => 'Acciones Rápidas',
        'create_user' => 'Crear Usuario',
        'create_role' => 'Crear Rol',
        'view_logs' => 'Ver Registros',
        'view_audit' => 'Ver Auditoría',
        'system_reports' => 'Reportes del Sistema',
        'clear_cache' => 'Limpiar Caché',
    ],

    // Actividad reciente
    'recent_activity' => [
        'title' => 'Actividad Reciente',
        'no_activity' => 'No hay actividad reciente',
        'user_created' => ':user creó el usuario :target',
        'user_updated' => ':user actualizó el usuario :target',
        'user_deleted' => ':user eliminó el usuario :target',
        'role_created' => ':user creó el rol :target',
        'role_updated' => ':user actualizó el rol :target',
        'login_success' => ':user inició sesión',
        'login_failed' => 'Intento fallido de :ip',
        'settings_updated' => ':user actualizó la configuración',
    ],

    // Información del sistema
    'system_info' => [
        'title' => 'Información del Sistema',
        'version' => 'Versión',
        'php_version' => 'Versión de PHP',
        'database' => 'Base de Datos',
        'server_time' => 'Hora del Servidor',
        'uptime' => 'Tiempo Activo',
        'disk_space' => 'Espacio en Disco',
        'memory_usage' => 'Uso de Memoria',
    ],

    // Alertas
    'alerts' => [
        'title' => 'Alertas del Sistema',
        'no_alerts' => 'No hay alertas',
        'update_available' => 'Actualización disponible',
        'disk_space_low' => 'Espacio en disco bajo',
        'failed_logins_high' => 'Alto número de intentos fallidos',
        'certificate_expiring' => 'El certificado SSL expirará pronto',
    ],

    // Usuarios recientes
    'recent_users' => [
        'title' => 'Usuarios Recientes',
        'view_all' => 'Ver Todos',
        'online' => 'En Línea',
        'offline' => 'Fuera de Línea',
    ],

    // Calendario
    'calendar' => [
        'title' => 'Calendario',
        'today' => 'Hoy',
        'tomorrow' => 'Mañana',
        'no_events' => 'No hay eventos programados',
    ],
];
