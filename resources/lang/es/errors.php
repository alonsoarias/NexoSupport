<?php

/**
 * Traducciones de mensajes de error - Español
 *
 * @package ISER\Resources\Lang
 */

return [
    // Errores HTTP
    'http' => [
        '400' => 'Solicitud Incorrecta',
        '401' => 'No Autorizado',
        '403' => 'Acceso Prohibido',
        '404' => 'Página No Encontrada',
        '405' => 'Método No Permitido',
        '408' => 'Tiempo de Espera Agotado',
        '419' => 'Sesión Expirada',
        '429' => 'Demasiadas Solicitudes',
        '500' => 'Error Interno del Servidor',
        '502' => 'Puerta de Enlace Incorrecta',
        '503' => 'Servicio No Disponible',
        '504' => 'Tiempo de Espera de Puerta de Enlace',
    ],

    // Mensajes HTTP detallados
    'http_messages' => [
        '400' => 'La solicitud no pudo ser procesada debido a un error del cliente',
        '401' => 'Debe iniciar sesión para acceder a este recurso',
        '403' => 'No tiene permisos para acceder a este recurso',
        '404' => 'La página que busca no existe o ha sido movida',
        '405' => 'El método HTTP usado no está permitido para esta ruta',
        '408' => 'La solicitud tardó demasiado tiempo en procesarse',
        '419' => 'Su sesión ha expirado. Por favor, inicie sesión nuevamente',
        '429' => 'Ha realizado demasiadas solicitudes. Por favor, intente más tarde',
        '500' => 'Ha ocurrido un error inesperado en el servidor',
        '502' => 'Error de comunicación con el servidor',
        '503' => 'El servicio está temporalmente no disponible',
        '504' => 'El servidor no respondió a tiempo',
    ],

    // Errores de autenticación
    'auth' => [
        'invalid_credentials' => 'Credenciales inválidas',
        'user_not_found' => 'Usuario no encontrado',
        'account_suspended' => 'Su cuenta ha sido suspendida',
        'account_locked' => 'Su cuenta ha sido bloqueada temporalmente',
        'account_deleted' => 'Esta cuenta ha sido eliminada',
        'email_not_verified' => 'Debe verificar su correo electrónico',
        'too_many_attempts' => 'Demasiados intentos fallidos. Cuenta bloqueada por :minutes minutos',
        'invalid_token' => 'Token inválido o expirado',
        'token_expired' => 'El token ha expirado',
        'session_expired' => 'Su sesión ha expirado. Por favor, inicie sesión nuevamente',
    ],

    // Errores de autorización
    'authorization' => [
        'no_permission' => 'No tiene permisos para realizar esta acción',
        'access_denied' => 'Acceso denegado',
        'insufficient_privileges' => 'Privilegios insuficientes',
        'role_required' => 'Se requiere el rol :role para acceder',
    ],

    // Errores de base de datos
    'database' => [
        'connection_failed' => 'No se pudo conectar a la base de datos',
        'query_failed' => 'Error al ejecutar la consulta',
        'record_not_found' => 'Registro no encontrado',
        'duplicate_entry' => 'Este registro ya existe',
        'foreign_key_constraint' => 'No se puede eliminar debido a registros relacionados',
        'transaction_failed' => 'La transacción ha fallado',
    ],

    // Errores de archivos
    'file' => [
        'not_found' => 'Archivo no encontrado',
        'not_readable' => 'No se puede leer el archivo',
        'not_writable' => 'No se puede escribir en el archivo',
        'upload_failed' => 'Error al subir el archivo',
        'invalid_format' => 'Formato de archivo inválido',
        'file_too_large' => 'El archivo es demasiado grande (máximo: :max)',
        'extension_not_allowed' => 'Extensión de archivo no permitida',
    ],

    // Errores de validación general
    'validation' => [
        'required' => 'Este campo es requerido',
        'invalid' => 'El valor proporcionado es inválido',
        'too_short' => 'El valor es demasiado corto',
        'too_long' => 'El valor es demasiado largo',
        'out_of_range' => 'El valor está fuera de rango',
        'not_unique' => 'Este valor ya está en uso',
    ],

    // Errores del sistema
    'system' => [
        'maintenance' => 'El sistema está en mantenimiento. Por favor, intente más tarde',
        'unavailable' => 'El servicio no está disponible temporalmente',
        'configuration_error' => 'Error de configuración del sistema',
        'dependency_missing' => 'Falta una dependencia requerida',
        'cache_error' => 'Error al acceder al caché',
        'log_error' => 'Error al escribir en el registro',
    ],

    // Acciones sugeridas
    'actions' => [
        'go_home' => 'Ir al Inicio',
        'go_back' => 'Volver',
        'login' => 'Iniciar Sesión',
        'contact_admin' => 'Contactar al Administrador',
        'try_again' => 'Intentar Nuevamente',
        'reload' => 'Recargar Página',
    ],

    // General
    'something_went_wrong' => 'Algo salió mal',
    'please_try_again' => 'Por favor, intente nuevamente',
    'if_problem_persists' => 'Si el problema persiste, contacte al administrador',
];
