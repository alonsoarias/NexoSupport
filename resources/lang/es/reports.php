<?php

/**
 * Traducciones de reportes - Español
 *
 * @package ISER\Resources\Lang
 */

return [
    // Títulos
    'title' => 'Reportes',
    'generate' => 'Generar Reporte',
    'view' => 'Ver Reporte',
    'export' => 'Exportar Reporte',

    // Tipos de reportes
    'types' => [
        'users' => 'Reporte de Usuarios',
        'roles' => 'Reporte de Roles',
        'permissions' => 'Reporte de Permisos',
        'activity' => 'Reporte de Actividad',
        'logins' => 'Reporte de Accesos',
        'audit' => 'Reporte de Auditoría',
        'security' => 'Reporte de Seguridad',
        'performance' => 'Reporte de Rendimiento',
        'system' => 'Reporte del Sistema',
    ],

    // Períodos
    'periods' => [
        'today' => 'Hoy',
        'yesterday' => 'Ayer',
        'last_7_days' => 'Últimos 7 Días',
        'last_30_days' => 'Últimos 30 Días',
        'this_month' => 'Este Mes',
        'last_month' => 'Mes Anterior',
        'this_year' => 'Este Año',
        'custom' => 'Personalizado',
    ],

    // Formatos de exportación
    'formats' => [
        'pdf' => 'PDF',
        'excel' => 'Excel (XLSX)',
        'csv' => 'CSV',
        'json' => 'JSON',
        'html' => 'HTML',
    ],

    // Configuración del reporte
    'config' => [
        'title' => 'Configuración del Reporte',
        'type' => 'Tipo de Reporte',
        'period' => 'Período',
        'date_from' => 'Desde',
        'date_to' => 'Hasta',
        'format' => 'Formato',
        'include_charts' => 'Incluir Gráficos',
        'include_summary' => 'Incluir Resumen',
        'filters' => 'Filtros',
    ],

    // Métricas
    'metrics' => [
        'total' => 'Total',
        'active' => 'Activos',
        'inactive' => 'Inactivos',
        'new' => 'Nuevos',
        'deleted' => 'Eliminados',
        'growth' => 'Crecimiento',
        'percentage' => 'Porcentaje',
        'average' => 'Promedio',
    ],

    // Reporte de usuarios
    'users' => [
        'total_users' => 'Total de Usuarios',
        'new_users' => 'Usuarios Nuevos',
        'active_users' => 'Usuarios Activos',
        'suspended_users' => 'Usuarios Suspendidos',
        'by_role' => 'Usuarios por Rol',
        'by_status' => 'Usuarios por Estado',
        'registration_trend' => 'Tendencia de Registro',
    ],

    // Reporte de accesos
    'logins' => [
        'total_logins' => 'Total de Accesos',
        'successful_logins' => 'Accesos Exitosos',
        'failed_logins' => 'Accesos Fallidos',
        'unique_users' => 'Usuarios Únicos',
        'by_hour' => 'Accesos por Hora',
        'by_day' => 'Accesos por Día',
        'by_location' => 'Accesos por Ubicación',
        'peak_times' => 'Horarios Pico',
    ],

    // Reporte de seguridad
    'security' => [
        'failed_attempts' => 'Intentos Fallidos',
        'locked_accounts' => 'Cuentas Bloqueadas',
        'suspicious_activity' => 'Actividad Sospechosa',
        'ip_blocks' => 'IPs Bloqueadas',
        'password_resets' => 'Restablecimientos de Contraseña',
        'mfa_usage' => 'Uso de 2FA',
    ],

    // Reporte de actividad
    'activity' => [
        'user_actions' => 'Acciones de Usuarios',
        'most_active_users' => 'Usuarios Más Activos',
        'action_types' => 'Tipos de Acción',
        'activity_timeline' => 'Línea de Tiempo',
    ],

    // Mensajes
    'generating' => 'Generando reporte...',
    'generated_successfully' => 'Reporte generado exitosamente',
    'generation_failed' => 'Error al generar el reporte',
    'no_data' => 'No hay datos para el período seleccionado',
    'exported_successfully' => 'Reporte exportado exitosamente',

    // Acciones
    'generate_button' => 'Generar',
    'export_button' => 'Exportar',
    'print_button' => 'Imprimir',
    'share_button' => 'Compartir',
    'schedule_button' => 'Programar',
    'download_button' => 'Descargar',

    // Reportes programados
    'scheduled' => [
        'title' => 'Reportes Programados',
        'create' => 'Programar Reporte',
        'frequency' => 'Frecuencia',
        'daily' => 'Diario',
        'weekly' => 'Semanal',
        'monthly' => 'Mensual',
        'recipients' => 'Destinatarios',
        'next_run' => 'Próxima Ejecución',
        'last_run' => 'Última Ejecución',
    ],
];
