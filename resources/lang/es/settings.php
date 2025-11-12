<?php

/**
 * Traducciones de configuración del sistema - Español
 *
 * @package ISER\Resources\Lang
 */

return [
    // Título
    'title' => 'Configuración del Sistema',
    'system_title' => 'Configuración del Sistema',
    'description' => 'Configura todos los aspectos del sistema desde esta interfaz centralizada',

    // Tabs
    'tabs' => [
        'general' => 'General',
        'email' => 'Email',
        'security' => 'Seguridad',
        'appearance' => 'Apariencia',
        'advanced' => 'Avanzado',
    ],

    // Grupos de configuración
    'groups' => [
        'general' => 'Configuración General',
        'email' => 'Configuración de Correo Electrónico',
        'security' => 'Configuración de Seguridad',
        'appearance' => 'Configuración de Apariencia',
        'advanced' => 'Configuración Avanzada',
    ],

    // Fields
    'fields' => [
        // General
        'site_name' => 'Nombre del Sitio',
        'site_description' => 'Descripción del Sitio',
        'timezone' => 'Zona Horaria',
        'locale' => 'Idioma',
        'date_format' => 'Formato de Fecha',

        // Email
        'from_name' => 'Nombre del Remitente',
        'from_address' => 'Dirección de Email',
        'reply_to' => 'Responder a',
        'mail_driver' => 'Driver de Correo',

        // Security
        'session_lifetime' => 'Duración de Sesión (minutos)',
        'password_min_length' => 'Longitud Mínima de Contraseña',
        'require_email_verification' => 'Requerir Verificación de Email',
        'login_max_attempts' => 'Intentos Máximos de Login',
        'lockout_duration' => 'Duración de Bloqueo (minutos)',

        // Appearance
        'theme' => 'Tema',
        'items_per_page' => 'Elementos por Página',
        'default_language' => 'Idioma Predeterminado',

        // Advanced
        'cache_driver' => 'Driver de Caché',
        'log_level' => 'Nivel de Registro',
        'debug_mode' => 'Modo de Depuración',
        'maintenance_mode' => 'Modo de Mantenimiento',
    ],

    // Help texts
    'help' => [
        // General
        'site_name' => 'Nombre que aparecerá en todo el sistema',
        'site_description' => 'Breve descripción del propósito del sistema',
        'timezone' => 'Zona horaria para fechas y horas del sistema',
        'locale' => 'Idioma predeterminado de la interfaz',
        'date_format' => 'Formato de visualización de fechas',

        // Email
        'from_name' => 'Nombre que aparecerá como remitente de emails',
        'from_address' => 'Dirección de email para mensajes salientes',
        'reply_to' => 'Dirección para respuestas de usuarios',
        'mail_driver' => 'Método de envío de correos (SMTP recomendado)',

        // Security
        'session_lifetime' => 'Tiempo de inactividad antes de cerrar sesión automáticamente (5-1440 minutos)',
        'password_min_length' => 'Número mínimo de caracteres para contraseñas (6-32)',
        'require_email_verification' => 'Los usuarios deben verificar su email antes de acceder',
        'login_max_attempts' => 'Intentos fallidos permitidos antes de bloquear cuenta (3-20)',
        'lockout_duration' => 'Tiempo de bloqueo después de exceder intentos (1-1440 minutos)',

        // Appearance
        'theme' => 'Tema visual del sistema',
        'items_per_page' => 'Número de elementos en listas y tablas (10-100)',
        'default_language' => 'Idioma predeterminado para nuevos usuarios',

        // Advanced
        'cache_driver' => 'Sistema de almacenamiento de caché',
        'log_level' => 'Nivel de detalle en los registros del sistema',
        'debug_mode' => 'Mostrar errores detallados - SOLO PARA DESARROLLO',
        'maintenance_mode' => 'Desactivar el sitio para todos excepto administradores',
    ],

    // Messages
    'saved_message' => 'Configuración guardada exitosamente',
    'restored_message' => 'Configuración restaurada a valores predeterminados',
    'items_updated' => 'elementos actualizados',

    // Actions
    'actions' => [
        'save' => 'Guardar Cambios',
        'cancel' => 'Cancelar',
        'reset' => 'Restaurar Valores Predeterminados',
    ],

    // Warnings
    'warnings' => [
        'advanced' => 'ADVERTENCIA: Las configuraciones avanzadas pueden afectar el funcionamiento del sistema. Modifique con precaución.',
    ],

    // Badges
    'badges' => [
        'sensitive' => 'Sensible',
        'critical' => 'Crítico',
    ],

    // Confirmations
    'confirmations' => [
        'reset' => '¿Está seguro de que desea restaurar todas las configuraciones a sus valores predeterminados? Esta acción no se puede deshacer.',
        'sensitive' => 'ADVERTENCIA: Ha activado configuraciones sensibles que pueden afectar el funcionamiento del sistema:',
    ],
];
