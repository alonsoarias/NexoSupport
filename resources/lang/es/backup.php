<?php

/**
 * Traducciones del módulo de respaldos - Español
 *
 * @package ISER\Resources\Lang
 */

return [
    // Page titles
    'page_title' => 'Respaldos de Base de Datos',
    'title' => 'Gestión de Respaldos de Base de Datos',

    // Actions
    'create_backup' => 'Crear Respaldo',
    'backup_list' => 'Lista de Respaldos',
    'download' => 'Descargar',
    'delete' => 'Eliminar',

    // Table columns
    'filename' => 'Nombre de Archivo',
    'size' => 'Tamaño',
    'date' => 'Fecha de Creación',
    'actions' => 'Acciones',

    // Messages
    'no_backups_yet' => 'Aún no hay respaldos. Cree uno nuevo para empezar.',
    'backup_created_success' => 'Respaldo creado exitosamente',
    'backup_creation_failed' => 'Error al crear el respaldo',
    'backup_deleted_success' => 'Respaldo eliminado exitosamente',
    'backup_deletion_failed' => 'Error al eliminar el respaldo',
    'error_listing_backups' => 'Error al listar los respaldos',
    'error_creating_backup' => 'Error al crear el respaldo',

    // UI Labels
    'creating_backup' => 'Creando respaldo... Por favor espere...',
    'backup_dir_warning' => 'Advertencia: El directorio de respaldos no tiene permisos de escritura',
    'total_backup_size' => 'Tamaño total de respaldos',

    // Warnings and Instructions
    'warning_restore' => 'Advertencia de Seguridad',
    'restore_instructions' => 'La restauración de respaldos es una operación potencialmente peligrosa que requiere acceso directo a la línea de comandos del servidor. No se proporciona una interfaz web para esta operación por razones de seguridad. Si necesita restaurar un respaldo, contacte al administrador del sistema del servidor.',

    // Backup info
    'backup_info' => 'Información del Respaldo',
    'backup_directory' => 'Directorio de Respaldos',
    'backup_location' => 'Ubicación: :path',
    'backup_permissions' => 'Permisos del Directorio',
    'writable' => 'Escribible',
    'not_writable' => 'No escribible',

    // Success messages
    'success' => [
        'backup_created' => 'Respaldo creado exitosamente',
        'backup_downloaded' => 'Descarga del respaldo iniciada',
        'backup_deleted' => 'Respaldo eliminado exitosamente',
    ],

    // Error messages
    'errors' => [
        'backup_creation_failed' => 'Error al crear el respaldo',
        'backup_download_failed' => 'Error al descargar el respaldo',
        'backup_deletion_failed' => 'Error al eliminar el respaldo',
        'invalid_backup_file' => 'Archivo de respaldo no válido',
        'backup_directory_not_writable' => 'El directorio de respaldos no tiene permisos de escritura',
        'insufficient_disk_space' => 'Espacio en disco insuficiente',
    ],
];
