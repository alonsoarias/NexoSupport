<?php
/**
 * Strings de idioma - Administración - Español
 *
 * @package core
 * @subpackage admin
 * @copyright NexoSupport
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// Administration index
$string['administration'] = 'Administración';
$string['admin_welcome'] = 'Bienvenido al panel de administración';
$string['admin_description'] = 'Gestione todos los aspectos del sistema desde aquí';
$string['quick_links'] = 'Enlaces Rápidos';
$string['user_management'] = 'Gestión de Usuarios';
$string['role_management'] = 'Gestión de Roles';
$string['system_settings'] = 'Configuración del Sistema';
$string['cache_management'] = 'Gestión de Caché';
$string['system_upgrade'] = 'Actualización del Sistema';

// Upgrade
$string['upgrade'] = 'Actualización';
$string['upgrade_title'] = 'Actualización del Sistema NexoSupport';
$string['upgrade_description'] = 'Actualizar el sistema a la última versión';
$string['current_version'] = 'Versión Actual';
$string['target_version'] = 'Versión Objetivo';
$string['database_version'] = 'Versión en Base de Datos';
$string['code_version'] = 'Versión del Código';
$string['upgrade_required'] = 'Se requiere actualización';
$string['upgrade_not_required'] = 'El sistema está actualizado';
$string['upgrade_inprogress'] = 'Actualización en progreso';
$string['upgrade_success'] = 'Actualización completada exitosamente';
$string['upgrade_error'] = 'Error durante la actualización';
$string['upgrade_button'] = 'Ejecutar Actualización';
$string['upgrade_requirements_title'] = 'Requisitos de Actualización';
$string['upgrade_requirements_ok'] = 'Todos los requisitos se cumplen';
$string['upgrade_requirements_failed'] = 'Algunos requisitos no se cumplen';
$string['upgrade_log'] = 'Log de Actualización';
$string['upgrade_warning'] = '⚠️ IMPORTANTE: Haga un backup de la base de datos antes de continuar';
$string['upgrade_info'] = 'Información de Actualización';

// Cache management
$string['cache'] = 'Caché';
$string['cache_purge'] = 'Purgar Caché';
$string['cache_purge_title'] = 'Purgar Caché del Sistema';
$string['cache_purge_description'] = 'Eliminar todos los archivos de caché para forzar regeneración';
$string['cache_purge_success'] = 'Caché purgado exitosamente';
$string['cache_purge_error'] = 'Error al purgar caché';
$string['cache_purge_button'] = 'Purgar Caché Ahora';
$string['cache_info'] = 'El caché mejora el rendimiento almacenando datos procesados. Púrguelo si experimenta problemas.';
$string['cache_confirm'] = '¿Está seguro de que desea purgar el caché?';

// Settings
$string['settings'] = 'Configuración';
$string['settings_saved'] = 'Configuración guardada exitosamente';
$string['settings_error'] = 'Error al guardar configuración';
$string['debugging'] = 'Depuración';
$string['debugging_title'] = 'Configuración de Depuración';
$string['debugging_description'] = 'Configurar nivel de depuración y visualización de errores';
$string['debug_level'] = 'Nivel de Depuración';
$string['debug_none'] = 'Ninguno';
$string['debug_minimal'] = 'Mínimo';
$string['debug_normal'] = 'Normal';
$string['debug_all'] = 'Todos (incluye developer)';
$string['debug_developer'] = 'Developer (máximo detalle)';
$string['display_debug_info'] = 'Mostrar información de depuración';
$string['debug_warning'] = '⚠️ Desactive la depuración en producción por seguridad y rendimiento';

// User management
$string['users'] = 'Usuarios';
$string['user_list'] = 'Lista de Usuarios';
$string['user_create'] = 'Crear Usuario';
$string['user_edit'] = 'Editar Usuario';
$string['user_delete'] = 'Eliminar Usuario';
$string['user_view'] = 'Ver Usuario';
$string['user_created'] = 'Usuario creado exitosamente';
$string['user_updated'] = 'Usuario actualizado exitosamente';
$string['user_deleted'] = 'Usuario eliminado exitosamente';
$string['user_error'] = 'Error al procesar usuario';
$string['user_notfound'] = 'Usuario no encontrado';
$string['user_confirm_delete'] = '¿Está seguro de que desea eliminar este usuario?';
$string['user_details'] = 'Detalles del Usuario';
$string['user_info'] = 'Información del Usuario';
$string['user_roles'] = 'Roles del Usuario';
$string['user_status'] = 'Estado';
$string['user_active'] = 'Activo';
$string['user_suspended'] = 'Suspendido';
$string['user_deleted_flag'] = 'Eliminado';

// Role management
$string['roles'] = 'Roles';
$string['role_list'] = 'Lista de Roles';
$string['role_create'] = 'Crear Rol';
$string['role_edit'] = 'Editar Rol';
$string['role_define'] = 'Definir Permisos de Rol';
$string['role_assign'] = 'Asignar Roles';
$string['role_delete'] = 'Eliminar Rol';
$string['role_created'] = 'Rol creado exitosamente';
$string['role_updated'] = 'Rol actualizado exitosamente';
$string['role_deleted'] = 'Rol eliminado exitosamente';
$string['role_error'] = 'Error al procesar rol';
$string['role_notfound'] = 'Rol no encontrado';
$string['role_name'] = 'Nombre del Rol';
$string['role_shortname'] = 'Nombre Corto';
$string['role_description'] = 'Descripción';
$string['role_permissions'] = 'Permisos';
$string['role_capabilities'] = 'Capacidades';
$string['role_archetype'] = 'Arquetipo';

// Permissions and capabilities
$string['allow'] = 'Permitir';
$string['prevent'] = 'Prevenir';
$string['prohibit'] = 'Prohibir';
$string['inherit'] = 'Heredar';
$string['notset'] = 'No establecido';
$string['permission_updated'] = 'Permiso actualizado';

// Common actions
$string['actions'] = 'Acciones';
$string['confirm'] = 'Confirmar';
$string['continue'] = 'Continuar';
$string['back_to_admin'] = 'Volver a Administración';
$string['no_data'] = 'No hay datos disponibles';
$string['loading'] = 'Cargando...';
$string['processing'] = 'Procesando...';

// Errors and messages
$string['error_occurred'] = 'Ocurrió un error';
$string['operation_success'] = 'Operación completada exitosamente';
$string['operation_failed'] = 'La operación falló';
$string['invalid_request'] = 'Solicitud inválida';
$string['missing_parameter'] = 'Parámetro faltante';
