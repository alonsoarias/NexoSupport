<?php

/**
 * Traducciones de configuración del sistema - Español
 *
 * @package ISER\Resources\Lang
 */

return [
    // Título
    'title' => 'Configuración del Sistema',

    // Grupos de configuración
    'groups' => [
        'general' => 'General',
        'email' => 'Correo Electrónico',
        'security' => 'Seguridad',
        'cache' => 'Caché',
        'logs' => 'Registros',
        'regional' => 'Regional',
        'appearance' => 'Apariencia',
        'advanced' => 'Avanzado',
    ],

    // Configuraciones generales
    'general' => [
        'app_name' => 'Nombre de la Aplicación',
        'app_url' => 'URL de la Aplicación',
        'app_env' => 'Entorno',
        'app_debug' => 'Modo de Depuración',
        'maintenance_mode' => 'Modo de Mantenimiento',
        'timezone' => 'Zona Horaria',
        'locale' => 'Idioma',
    ],

    // Configuraciones de correo
    'email' => [
        'driver' => 'Driver de Correo',
        'host' => 'Servidor SMTP',
        'port' => 'Puerto',
        'username' => 'Usuario',
        'password' => 'Contraseña',
        'encryption' => 'Encriptación',
        'from_address' => 'Dirección de Envío',
        'from_name' => 'Nombre de Envío',
        'test_connection' => 'Probar Conexión',
    ],

    // Configuraciones de seguridad
    'security' => [
        'password_min_length' => 'Longitud Mínima de Contraseña',
        'password_require_uppercase' => 'Requerir Mayúsculas',
        'password_require_lowercase' => 'Requerir Minúsculas',
        'password_require_numbers' => 'Requerir Números',
        'password_require_symbols' => 'Requerir Símbolos',
        'password_expiry_days' => 'Días de Expiración de Contraseña',
        'max_login_attempts' => 'Intentos Máximos de Inicio de Sesión',
        'lockout_duration' => 'Duración del Bloqueo (minutos)',
        'session_lifetime' => 'Duración de Sesión (minutos)',
        'jwt_secret' => 'Clave Secreta JWT',
        'jwt_ttl' => 'TTL del Token JWT (minutos)',
        'mfa_enabled' => 'Habilitar Autenticación de Dos Factores',
    ],

    // Configuraciones de caché
    'cache' => [
        'driver' => 'Driver de Caché',
        'ttl' => 'Tiempo de Vida (segundos)',
        'prefix' => 'Prefijo de Claves',
        'clear_cache' => 'Limpiar Caché',
    ],

    // Configuraciones de logs
    'logs' => [
        'channel' => 'Canal de Registros',
        'level' => 'Nivel de Registro',
        'max_files' => 'Archivos Máximos',
        'rotation' => 'Rotación de Archivos',
    ],

    // Configuraciones regionales
    'regional' => [
        'default_timezone' => 'Zona Horaria Predeterminada',
        'default_locale' => 'Idioma Predeterminado',
        'available_locales' => 'Idiomas Disponibles',
        'date_format' => 'Formato de Fecha',
        'time_format' => 'Formato de Hora',
        'currency' => 'Moneda',
    ],

    // Mensajes
    'saved_message' => 'Configuración guardada exitosamente',
    'restored_message' => 'Configuración restaurada a valores predeterminados',
    'test_email_sent' => 'Correo de prueba enviado a :email',
    'cache_cleared' => 'Caché limpiada exitosamente',

    // Acciones
    'save' => 'Guardar Configuración',
    'restore_defaults' => 'Restaurar Valores Predeterminados',
    'cancel' => 'Cancelar',

    // Ayuda
    'help' => [
        'app_name' => 'Nombre que aparecerá en todo el sistema',
        'app_url' => 'URL base de la aplicación (sin barra final)',
        'app_debug' => 'Mostrar errores detallados (solo para desarrollo)',
        'password_min_length' => 'Mínimo de caracteres requeridos para contraseñas',
        'max_login_attempts' => 'Número de intentos fallidos antes de bloquear cuenta',
        'session_lifetime' => 'Tiempo de inactividad antes de cerrar sesión',
    ],
];
