<?php
/**
 * Strings de idioma - Administración - Español
 *
 * @package core
 * @subpackage admin
 * @copyright NexoSupport
 * @license    Proprietary - NexoSupport
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
$string['cache_status'] = 'Estado';
$string['cache_enabled'] = 'Habilitado';
$string['cache_disabled'] = 'Deshabilitado';
$string['cache_memory_used'] = 'Memoria Usada';
$string['cache_memory_free'] = 'Memoria Libre';
$string['cache_memory_wasted'] = 'Memoria Desperdiciada';
$string['cache_scripts'] = 'Scripts en Caché';
$string['cache_hits'] = 'Aciertos';
$string['cache_misses'] = 'Fallos';
$string['cache_hit_rate'] = 'Tasa de Aciertos';
$string['cache_templates'] = 'Plantillas en Caché';
$string['cache_size'] = 'Tamaño de Caché';
$string['cache_opcache_disabled'] = 'OPcache está deshabilitado';
$string['cache_mustache_disabled'] = 'Caché de Mustache deshabilitado';
$string['cache_purge_help'] = 'Seleccione qué cachés desea purgar';
$string['cache_purge_all'] = 'Purgar Todos los Cachés';
$string['cache_purge_opcache'] = 'Purgar OPcache';
$string['cache_purge_mustache'] = 'Purgar Caché de Mustache';
$string['cache_purge_i18n'] = 'Purgar Caché de i18n';
$string['cache_purge_app'] = 'Purgar Caché de Aplicación';
$string['cache_purge_rbac'] = 'Purgar Caché de RBAC';
$string['cache_about'] = 'Acerca de los Cachés';
$string['cache_opcache_help'] = 'Caché de opcodes de PHP - acelera la ejecución de PHP';
$string['cache_mustache_help'] = 'Caché de plantillas Mustache compiladas';
$string['cache_i18n_help'] = 'Caché de strings de idioma';
$string['cache_app_help'] = 'Caché de nivel de aplicación para varios datos';
$string['cache_rbac_help'] = 'Caché de definiciones de roles y permisos';
$string['cache_purge_results'] = 'Resultados de Purga';

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

// Dashboard quick links
$string['plugins'] = 'Plugins';
$string['plugins_description'] = 'Gestionar plugins (Fase 2)';
$string['themes'] = 'Temas';
$string['themes_description'] = 'Personalizar apariencia (Fase 6)';
$string['reports'] = 'Reportes';
$string['reports_description'] = 'Ver reportes del sistema (Fase 5)';
$string['manage_users_description'] = 'Crear, editar y gestionar cuentas de usuario';
$string['manage_roles_description'] = 'Definir roles y asignar permisos';
$string['manage_settings_description'] = 'Configurar ajustes del sistema';

// Errors and messages
$string['error_occurred'] = 'Ocurrió un error';
$string['operation_success'] = 'Operación completada exitosamente';
$string['operation_failed'] = 'La operación falló';
$string['invalid_request'] = 'Solicitud inválida';
$string['missing_parameter'] = 'Parámetro faltante';

// Dashboard statistics
$string['total_users'] = 'Total de Usuarios';
$string['total_roles'] = 'Total de Roles';
$string['system_version'] = 'Versión del Sistema';
$string['cache_description'] = 'Gestionar y purgar caché del sistema';
$string['continue'] = 'Continuar';

// Server settings
$string['server'] = 'Servidor';
$string['systempaths'] = 'Rutas del Sistema';
$string['systempathshelp'] = 'Configure las rutas de directorios del sistema';
$string['dataroot'] = 'Directorio de Datos';
$string['dataroothelp'] = 'Ruta al directorio de datos del sistema (fuera del directorio web)';
$string['tempdir'] = 'Directorio Temporal';
$string['tempdirhelp'] = 'Ruta al directorio de archivos temporales';
$string['cachedir'] = 'Directorio de Caché';
$string['cachedirhelp'] = 'Ruta al directorio de caché';

// HTTP settings
$string['http'] = 'HTTP';
$string['httphelp'] = 'Configure los ajustes de conexión HTTP';
$string['wwwroot'] = 'Dirección del sitio web';
$string['wwwroothelp'] = 'URL completa del sitio (ej: https://ejemplo.com)';
$string['sslproxy'] = 'Detrás de proxy SSL';
$string['sslproxyhelp'] = 'Habilitar si el sitio está detrás de un proxy SSL (reverse proxy)';
$string['proxysettings'] = 'Configuración de Proxy';
$string['proxysettingshelp'] = 'Configure un proxy para conexiones salientes';
$string['proxyhost'] = 'Host del Proxy';
$string['proxyhosthelp'] = 'Dirección del servidor proxy';
$string['proxyport'] = 'Puerto del Proxy';
$string['proxyporthelp'] = 'Puerto del servidor proxy';
$string['configproxybypass'] = 'Lista de hosts que no usan proxy (separados por coma)';

// Maintenance mode
$string['maintenancemode'] = 'Modo de Mantenimiento';
$string['maintenancemodehelp'] = 'Configure el modo de mantenimiento del sitio';
$string['enablemaintenancemode'] = 'Habilitar modo de mantenimiento';
$string['enablemaintenancemodehelp'] = 'Cuando está habilitado, solo los administradores pueden acceder';
$string['maintenancemessage'] = 'Mensaje de mantenimiento';
$string['maintenancemessagehelp'] = 'Mensaje mostrado a los usuarios durante el mantenimiento';
$string['sitemaintenancewarning'] = 'El sitio está en mantenimiento. Por favor, vuelva más tarde.';
$string['maintenancemodeactive'] = 'Modo de mantenimiento ACTIVO';
$string['siteisactive'] = 'Sitio activo';
$string['maintenancemodewarning'] = 'Cuando el modo de mantenimiento está activo, solo los administradores pueden acceder al sitio.';
$string['maintenancemodedesc'] = 'Impide el acceso a usuarios no administradores';

// User settings
$string['addnewuser'] = 'Agregar usuario';
$string['browselistofusers'] = 'Ver lista de usuarios';
$string['allowselfregistration'] = 'Permitir registro de usuarios';
$string['allowselfregistrationhelp'] = 'Permite que los usuarios se registren por sí mismos';

// Role management
$string['defineroles'] = 'Definir roles';
$string['assignroles'] = 'Asignar roles';

// Debug settings
$string['debug'] = 'Depuración';
$string['debughelp'] = 'Nivel de depuración para mensajes de error';
$string['debugnone'] = 'Ninguno (recomendado para producción)';
$string['debugminimal'] = 'Mínimo - Solo errores fatales';
$string['debugnormal'] = 'Normal - Errores y advertencias';
$string['debugall'] = 'Todos - Incluye avisos';
$string['debugdeveloper'] = 'Desarrollador - Máximo detalle';
$string['perfdebug'] = 'Información de rendimiento';
$string['perfdebughelp'] = 'Mostrar información de rendimiento en las páginas';

// Plugins
$string['localplugins'] = 'Plugins locales';
$string['blocks'] = 'Bloques';

// Plugin management
$string['installedplugins'] = 'Plugins instalados';
$string['installplugin'] = 'Instalar plugin';
$string['plugintype'] = 'Tipo de plugin';
$string['pluginzipfile'] = 'Archivo ZIP del plugin';
$string['plugininstallhelp'] = 'Suba un archivo ZIP del plugin. El tipo de plugin se detectará automáticamente desde el archivo version.php.';
$string['nopluginsinstalled'] = 'No hay plugins instalados';
$string['nopluginsinstalledhelp'] = 'Instale plugins para extender la funcionalidad del sistema';
$string['pluginuninstalled'] = 'Plugin "{$a}" desinstalado exitosamente';
$string['pluginuninstallfailed'] = 'Error al desinstalar el plugin';
$string['plugincannotuninstall'] = 'Este plugin no puede ser desinstalado';
$string['plugininstalled'] = 'Plugin "{$a}" instalado exitosamente';
$string['plugininstallfailed'] = 'Error al instalar el plugin';
$string['pluginnofileuploaded'] = 'No se subió ningún archivo';
$string['pluginupgraded'] = 'Plugin "{$a}" actualizado exitosamente';
$string['pluginupgradefailed'] = 'Error al actualizar el plugin';
$string['confirmpluginuninstall'] = '¿Está seguro de que desea desinstalar este plugin? Esta acción no puede deshacerse.';
$string['uninstall'] = 'Desinstalar';
$string['installed'] = 'Instalado';
$string['notinstalled'] = 'No instalado';
$string['upgraderequired'] = 'Actualización requerida';
$string['dbversion'] = 'Versión BD';
$string['tools'] = 'Herramientas';
// Plugin install errors
$string['pluginzipnotfound'] = 'Archivo ZIP no encontrado';
$string['pluginzipopenfailed'] = 'Error al abrir el archivo ZIP';
$string['plugininvalidstructure'] = 'Estructura de plugin inválida en el archivo ZIP';
$string['pluginversionnotfound'] = 'No se encontró version.php del plugin';
$string['plugintypenotdetected'] = 'No se pudo detectar el tipo de plugin desde version.php. Asegúrese de que $plugin->component esté definido.';
$string['plugintypemismatch'] = 'Tipo de plugin no coincide: detectado "{$a->detected}" pero seleccionado "{$a->selected}"';
$string['plugintypeinvalid'] = 'Tipo de plugin inválido: {$a}';
$string['pluginmovefailed'] = 'Error al mover archivos del plugin al directorio destino';

// Plugin types
$string['plugintype_report'] = 'Reportes';
$string['plugintype_tool'] = 'Herramientas';
$string['plugintype_theme'] = 'Temas';
$string['plugintype_auth'] = 'Autenticación';
$string['plugintype_block'] = 'Bloques';

// External pages
$string['phpinfo'] = 'Información PHP';
$string['environment'] = 'Entorno del sistema';
$string['purgecaches'] = 'Purgar todos los cachés';

// IP blocking
$string['ipblocker'] = 'Bloqueo por IP';
$string['ipblockerhelp'] = 'Configure restricciones de acceso por dirección IP';
$string['allowedips'] = 'IPs permitidas';
$string['allowedipshelp'] = 'Lista de IPs permitidas (una por línea). Si está vacío, todas las IPs están permitidas';
$string['blockedips'] = 'IPs bloqueadas';
$string['blockedipshelp'] = 'Lista de IPs bloqueadas (una por línea)';

// Support contact
$string['supportcontact'] = 'Contacto de soporte';
$string['supportcontacthelp'] = 'Información de contacto para soporte técnico';
$string['supportname'] = 'Nombre de soporte';
$string['supportnamehelp'] = 'Nombre que aparecerá en los correos de soporte';
$string['supportemail'] = 'Correo de soporte';
$string['supportemailhelp'] = 'Correo electrónico para solicitudes de soporte';

// Error messages
$string['errorwritingsetting'] = 'Error al guardar la configuración';
