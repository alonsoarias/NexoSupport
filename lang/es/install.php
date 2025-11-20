<?php
/**
 * Strings de idioma - Instalador - Español
 *
 * @package core
 * @subpackage install
 * @copyright NexoSupport
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// Stage indicators
$string['step'] = 'Paso {$a->current} de {$a->total}';
$string['stage_welcome'] = 'Bienvenida';
$string['stage_requirements'] = 'Requisitos del Sistema';
$string['stage_database'] = 'Configuración de Base de Datos';
$string['stage_install_db'] = 'Instalación de Base de Datos';
$string['stage_admin'] = 'Crear Usuario Administrador';
$string['stage_finish'] = 'Finalizar Instalación';

// Welcome stage
$string['welcome_title'] = 'NexoSupport';
$string['welcome_subtitle'] = 'Instalación del Sistema v{$a}';
$string['welcome_message'] = 'Bienvenido a NexoSupport';
$string['welcome_description'] = 'Este asistente le guiará a través del proceso de instalación.';
$string['about_nexosupport'] = 'NexoSupport es un sistema de gestión construido con arquitectura Frankenstyle de Moodle.';
$string['features_title'] = 'Características:';
$string['feature_plugins'] = 'Sistema de plugins extensible';
$string['feature_rbac'] = 'Control de acceso basado en roles (RBAC)';
$string['feature_navigation'] = 'Navegación moderna con Font Awesome 6';
$string['feature_cache'] = 'Sistema de caché avanzado (OPcache, i18n, Mustache)';
$string['feature_templates'] = 'Templates Mustache para personalización';
$string['feature_themes'] = 'Sistema de temas personalizable';
$string['button_start'] = 'Comenzar instalación';

// Requirements stage
$string['requirements_title'] = 'Verificación de Requisitos';
$string['requirements_subtitle'] = 'Comprobando que el servidor cumple los requisitos mínimos';
$string['requirements_success'] = '¡Perfecto! Su servidor cumple con todos los requisitos.';
$string['requirements_error'] = 'Su servidor no cumple con algunos requisitos. Por favor corrija los problemas antes de continuar.';
$string['requirement_phpversion'] = 'PHP Version >= {$a}';
$string['requirement_pdo'] = 'PDO Extension';
$string['requirement_pdo_mysql'] = 'PDO MySQL Driver';
$string['requirement_pdo_pgsql'] = 'PDO PostgreSQL Driver';
$string['requirement_json'] = 'JSON Extension';
$string['requirement_mbstring'] = 'mbstring Extension';
$string['requirement_writable'] = 'Writable: {$a}';
$string['status_installed'] = 'Instalado';
$string['status_not_installed'] = 'No instalado';
$string['status_writable'] = 'Escribible';
$string['status_not_writable'] = 'No escribible';
$string['button_check_again'] = 'Verificar nuevamente';

// Database stage
$string['database_title'] = 'Configuración de Base de Datos';
$string['database_subtitle'] = 'Configure la conexión a la base de datos';
$string['database_info'] = 'El archivo .env se generará automáticamente con la configuración proporcionada. La base de datos se creará si no existe (solo MySQL).';
$string['database_driver'] = 'Driver de Base de Datos';
$string['database_driver_mysql'] = 'MySQL / MariaDB';
$string['database_driver_pgsql'] = 'PostgreSQL';
$string['database_host'] = 'Host';
$string['database_host_help'] = 'Generalmente es "localhost" o "127.0.0.1"';
$string['database_name'] = 'Nombre de la Base de Datos';
$string['database_name_help'] = 'Solo letras, números y guiones bajos';
$string['database_user'] = 'Usuario';
$string['database_password'] = 'Contraseña';
$string['database_password_help'] = 'Dejar en blanco si no hay contraseña';
$string['database_prefix'] = 'Prefijo de Tablas';
$string['database_prefix_help'] = 'Prefijo para todas las tablas (ej: nxs_). Solo letras, números y guiones bajos.';
$string['button_test_connection'] = 'Probar Conexión y Continuar';

// Install DB stage
$string['installdb_title'] = 'Instalación de Base de Datos';
$string['installdb_subtitle'] = 'Creando tablas del sistema';
$string['installdb_installing'] = 'Instalando...';
$string['installdb_installing_message'] = 'Por favor espere mientras se crean las tablas del sistema.';
$string['installdb_success'] = 'Base de datos instalada correctamente';
$string['installdb_error_title'] = 'Error en Instalación';
$string['installdb_log_title'] = 'Log de instalación:';
$string['button_back_database'] = 'Volver a Configuración de BD';

// Admin stage
$string['admin_title'] = 'Crear Usuario Administrador';
$string['admin_subtitle'] = 'Configure la cuenta de administrador del sistema';
$string['admin_important'] = 'Importante:';
$string['admin_important_message'] = 'Esta cuenta tendrá acceso completo al sistema. Asegúrese de usar una contraseña segura.';
$string['admin_username'] = 'Nombre de Usuario';
$string['admin_username_help'] = 'Solo letras, números, guiones, puntos y guiones bajos';
$string['admin_email'] = 'Email';
$string['admin_firstname'] = 'Nombre';
$string['admin_lastname'] = 'Apellido';
$string['admin_password'] = 'Contraseña';
$string['admin_password_help'] = 'Mínimo 8 caracteres';
$string['admin_password_confirm'] = 'Confirmar Contraseña';
$string['admin_password_mismatch'] = 'Las contraseñas no coinciden';
$string['button_create_admin'] = 'Crear Administrador';

// Finish stage
$string['finish_title'] = 'Finalizando Instalación';
$string['finish_subtitle'] = 'Configurando sistema RBAC y completando instalación';
$string['finish_processing'] = 'Procesando...';
$string['finish_processing_message'] = 'Instalando sistema de roles y permisos, configurando sistema...';
$string['finish_complete_title'] = '¡Instalación Completada!';
$string['finish_complete_subtitle'] = 'NexoSupport está listo para usar';
$string['finish_congratulations'] = '¡Felicidades!';
$string['finish_congratulations_message'] = 'NexoSupport ha sido instalado exitosamente.';
$string['finish_tasks_title'] = 'Tareas completadas:';
$string['finish_nextsteps_title'] = 'Próximos pasos:';
$string['finish_nextstep_1'] = 'Inicie sesión con su cuenta de administrador';
$string['finish_nextstep_2'] = 'Configure el sistema desde el panel de administración';
$string['finish_nextstep_3'] = 'Cree usuarios y asigne roles';
$string['finish_nextstep_4'] = 'Personalice el tema y la apariencia';
$string['finish_error_title'] = 'Error en Finalización';
$string['button_go_system'] = 'Ir al Sistema';

// Common buttons
$string['button_back'] = 'Atrás';
$string['button_continue'] = 'Continuar';
$string['button_next'] = 'Siguiente';
$string['button_cancel'] = 'Cancelar';

// Common messages
$string['error'] = 'Error';
$string['information'] = 'Información';
$string['warning'] = 'Advertencia';
$string['attention'] = 'Atención';
$string['perfect'] = '¡Perfecto!';

// Installer class - Validation messages
$string['installer_invalid_driver'] = 'Driver de base de datos no válido';
$string['installer_invalid_dbname'] = 'Nombre de base de datos inválido (solo letras, números y guiones bajos)';
$string['installer_invalid_prefix'] = 'Prefijo de tablas inválido (solo letras, números y guiones bajos)';
$string['installer_required_fields'] = 'Campos requeridos faltantes';
$string['installer_dbconfig_not_found'] = 'Configuración de BD no encontrada';
$string['installer_existing_installation'] = 'Ya existe una instalación en esta base de datos';
$string['installer_invalid_email'] = 'Email inválido';
$string['installer_password_too_short'] = 'La contraseña debe tener al menos 8 caracteres';
$string['installer_incomplete_data'] = 'Datos de instalación incompletos';

// Installer class - Log messages
$string['installer_log_connected'] = 'Conexión a BD establecida';
$string['installer_log_empty_db'] = 'BD vacía, procediendo con instalación';
$string['installer_log_installing_schema'] = 'Instalando esquema desde lib/db/install.xml';
$string['installer_log_schema_installed'] = 'Esquema instalado correctamente';
$string['installer_log_system_context'] = 'Contexto SYSTEM creado';
$string['installer_log_rbac_installing'] = 'Instalando sistema RBAC desde lib/db/rbac.php';
$string['installer_log_rbac_installed'] = 'Sistema RBAC instalado';
$string['installer_log_role_assigned'] = 'Rol \'{$a}\' asignado al administrador';
$string['installer_log_config_created'] = 'Configuración inicial creada';
$string['installer_log_version_set'] = 'Versión del sistema establecida: {$a}';
$string['installer_log_installation_complete'] = 'Instalación completada exitosamente';

// Upgrader class - Messages
$string['upgrader_no_upgrade_needed'] = 'No se requiere actualización';
$string['upgrader_upgrade_required'] = 'Se requiere actualización de v{$a->current} a v{$a->target}';
$string['upgrader_requirements_failed'] = 'Requisitos no cumplidos';
$string['upgrader_no_db'] = 'Base de datos no disponible';
$string['upgrader_executing'] = 'Ejecutando actualización de v{$a->from} a v{$a->to}';
$string['upgrader_success'] = 'Actualización completada exitosamente';
$string['upgrader_failed'] = 'Error durante la actualización: {$a}';
$string['upgrader_log_start'] = 'Iniciando actualización desde versión {$a}';
$string['upgrader_log_requirements'] = 'Verificando requisitos previos';
$string['upgrader_log_backup'] = 'IMPORTANTE: Se recomienda hacer backup de la base de datos';
$string['upgrader_log_executing'] = 'Ejecutando actualizaciones';
$string['upgrader_log_purging'] = 'Purgando cachés del sistema';
$string['upgrader_log_complete'] = 'Actualización completada';
$string['upgrader_log_version_updated'] = 'Versión actualizada a {$a}';
