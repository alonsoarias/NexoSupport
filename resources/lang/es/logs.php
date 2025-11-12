<?php

/**
 * Traducciones de registros del sistema - Español
 *
 * @package ISER\Resources\Lang
 */

return [
    // Títulos
    'title' => 'Registros del Sistema',
    'view' => 'Ver Registros',
    'search' => 'Buscar en Registros',

    // Niveles de log
    'levels' => [
        'emergency' => 'Emergencia',
        'alert' => 'Alerta',
        'critical' => 'Crítico',
        'error' => 'Error',
        'warning' => 'Advertencia',
        'notice' => 'Aviso',
        'info' => 'Información',
        'debug' => 'Depuración',
    ],

    // Canales
    'channels' => [
        'application' => 'Aplicación',
        'security' => 'Seguridad',
        'database' => 'Base de Datos',
        'authentication' => 'Autenticación',
        'authorization' => 'Autorización',
        'api' => 'API',
        'email' => 'Correo Electrónico',
        'cache' => 'Caché',
        'queue' => 'Cola',
    ],

    // Filtros
    'filters' => [
        'level' => 'Nivel',
        'channel' => 'Canal',
        'date_from' => 'Desde',
        'date_to' => 'Hasta',
        'user' => 'Usuario',
        'ip' => 'Dirección IP',
        'message' => 'Mensaje',
    ],

    // Campos
    'timestamp' => 'Fecha y Hora',
    'level' => 'Nivel',
    'channel' => 'Canal',
    'message' => 'Mensaje',
    'context' => 'Contexto',
    'user' => 'Usuario',
    'ip' => 'IP',
    'user_agent' => 'Navegador',
    'url' => 'URL',
    'method' => 'Método',
    'stack_trace' => 'Traza de Pila',

    // Tipos de eventos
    'events' => [
        'user_login' => 'Inicio de sesión de usuario',
        'user_logout' => 'Cierre de sesión de usuario',
        'login_failed' => 'Intento de inicio de sesión fallido',
        'user_created' => 'Usuario creado',
        'user_updated' => 'Usuario actualizado',
        'user_deleted' => 'Usuario eliminado',
        'password_changed' => 'Contraseña cambiada',
        'password_reset' => 'Contraseña restablecida',
        'role_assigned' => 'Rol asignado',
        'permission_changed' => 'Permiso modificado',
        'settings_updated' => 'Configuración actualizada',
        'file_uploaded' => 'Archivo subido',
        'database_query' => 'Consulta de base de datos',
        'api_request' => 'Solicitud API',
        'error_occurred' => 'Error ocurrido',
        'exception_thrown' => 'Excepción lanzada',
    ],

    // Acciones
    'view_details' => 'Ver Detalles',
    'export' => 'Exportar',
    'clear_logs' => 'Limpiar Registros',
    'download' => 'Descargar',
    'refresh' => 'Actualizar',

    // Mensajes
    'no_logs' => 'No hay registros para el período seleccionado',
    'loading' => 'Cargando registros...',
    'exported_successfully' => 'Registros exportados exitosamente',
    'cleared_successfully' => 'Registros limpiados exitosamente',
    'clear_confirm' => '¿Está seguro de que desea limpiar los registros?',
    'clear_warning' => 'Esta acción no se puede deshacer',

    // Estadísticas
    'stats' => [
        'total_entries' => 'Total de Entradas',
        'errors_today' => 'Errores Hoy',
        'warnings_today' => 'Advertencias Hoy',
        'by_level' => 'Por Nivel',
        'by_channel' => 'Por Canal',
        'most_common' => 'Más Comunes',
    ],

    // Configuración de logs
    'configuration' => [
        'title' => 'Configuración de Registros',
        'log_level' => 'Nivel de Registro',
        'log_channel' => 'Canal de Registro',
        'max_files' => 'Archivos Máximos',
        'max_file_size' => 'Tamaño Máximo de Archivo',
        'rotation' => 'Rotación de Archivos',
        'daily' => 'Diaria',
        'weekly' => 'Semanal',
        'monthly' => 'Mensual',
    ],

    // Tabla
    'table' => [
        'showing' => 'Mostrando :from a :to de :total registros',
        'per_page' => 'Por página',
        'no_results' => 'No se encontraron resultados',
    ],
];
