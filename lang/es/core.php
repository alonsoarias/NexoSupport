<?php
/**
 * Strings de idioma - Español
 *
 * @package core
 * @copyright NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$string['sitename'] = 'NexoSupport';

// Errores comunes
$string['error'] = 'Error';
$string['success'] = 'Éxito';
$string['failed'] = 'Fallido';
$string['warning'] = 'Advertencia';
$string['info'] = 'Información';
$string['unknown'] = 'Desconocido';
$string['nopermissions'] = 'No tiene permisos para acceder a esta página';
$string['accessdenied'] = 'Acceso denegado';
$string['notfound'] = 'No encontrado';
$string['invaliddata'] = 'Datos inválidos';
$string['requiredfield'] = 'Este campo es obligatorio';
$string['invalidtoken'] = 'Token de sesión inválido';
$string['sessionexpired'] = 'Su sesión ha expirado. Por favor, inicie sesión nuevamente';

// Navegación
$string['home'] = 'Inicio';
$string['dashboard'] = 'Dashboard';
$string['administration'] = 'Administración';
$string['settings'] = 'Configuración';
$string['profile'] = 'Mi Perfil';
$string['logout'] = 'Cerrar sesión';
$string['login'] = 'Iniciar sesión';
$string['back'] = 'Volver';
$string['cancel'] = 'Cancelar';
$string['save'] = 'Guardar';
$string['delete'] = 'Eliminar';
$string['edit'] = 'Editar';
$string['create'] = 'Crear';
$string['view'] = 'Ver';
$string['search'] = 'Buscar';
$string['next'] = 'Siguiente';
$string['previous'] = 'Anterior';

// Autenticación
$string['username'] = 'Nombre de usuario';
$string['password'] = 'Contraseña';
$string['email'] = 'Correo electrónico';
$string['firstname'] = 'Nombre';
$string['lastname'] = 'Apellido';
$string['fullname'] = 'Nombre completo';
$string['loggedinas'] = 'Sesión iniciada como {$a}';
$string['welcome'] = 'Bienvenido';
$string['welcomeback'] = 'Bienvenido de nuevo, {$a}';
$string['invalidlogin'] = 'Usuario o contraseña incorrectos';
$string['loggedout'] = 'Ha cerrado sesión exitosamente';
$string['pleaselogin'] = 'Por favor, inicie sesión';

// Usuarios
$string['user'] = 'Usuario';
$string['users'] = 'Usuarios';
$string['usermanagement'] = 'Gestión de Usuarios';
$string['manageusers'] = 'Gestionar Usuarios';
$string['createuser'] = 'Crear Usuario';
$string['edituser'] = 'Editar Usuario';
$string['deleteuser'] = 'Eliminar Usuario';
$string['userdeleted'] = 'Usuario eliminado exitosamente';
$string['usercreated'] = 'Usuario creado exitosamente';
$string['userupdated'] = 'Usuario actualizado exitosamente';
$string['totalusers'] = 'Total Usuarios';
$string['activeusers'] = 'Usuarios Activos';
$string['suspendedusers'] = 'Usuarios Suspendidos';
$string['deletedusers'] = 'Usuarios Eliminados';
$string['suspended'] = 'Suspendido';
$string['active'] = 'Activo';
$string['deleted'] = 'Eliminado';
$string['lastlogin'] = 'Último acceso';
$string['timecreated'] = 'Fecha de creación';
$string['timemodified'] = 'Última modificación';
$string['nousers'] = 'No hay usuarios para mostrar';
$string['userinfo'] = 'Información del usuario';

// Roles y permisos
$string['role'] = 'Rol';
$string['roles'] = 'Roles';
$string['rolemanagement'] = 'Gestión de Roles';
$string['manageroles'] = 'Gestionar Roles';
$string['createrole'] = 'Crear Rol';
$string['editrole'] = 'Editar Rol';
$string['deleterole'] = 'Eliminar Rol';
$string['assignroles'] = 'Asignar Roles';
$string['rolename'] = 'Nombre del rol';
$string['roleshortname'] = 'Nombre corto';
$string['roledescription'] = 'Descripción';
$string['rolecreated'] = 'Rol creado exitosamente';
$string['roleupdated'] = 'Rol actualizado exitosamente';
$string['roledeleted'] = 'Rol eliminado exitosamente';
$string['roleassigned'] = 'Rol \'{$a}\' asignado exitosamente';
$string['roleunassigned'] = 'Rol \'{$a}\' removido exitosamente';
$string['noroles'] = 'No hay roles definidos en el sistema';
$string['totalroles'] = 'Roles del Sistema';
$string['capabilities'] = 'Capabilities';
$string['definecapabilities'] = 'Definir Capabilities';
$string['capability'] = 'Capability';
$string['permission'] = 'Permiso';
$string['inherit'] = 'Heredar';
$string['allow'] = 'Permitir';
$string['prevent'] = 'Prevenir';
$string['prohibit'] = 'Prohibir';
$string['nocapabilities'] = 'No hay capabilities definidas';
$string['capabilitiesupdated'] = 'Capabilities actualizadas exitosamente';

// Sesiones
$string['session'] = 'Sesión';
$string['sessions'] = 'Sesiones';
$string['activesessions'] = 'Sesiones Activas';
$string['sessiontimeout'] = 'Timeout de Sesión (segundos)';

// Dashboard
$string['quickactions'] = 'Acciones Rápidas';
$string['recentactivity'] = 'Actividad Reciente';
$string['statistics'] = 'Estadísticas';
$string['norecentactivity'] = 'No hay actividad reciente para mostrar';

// Configuración
$string['systemsettings'] = 'Configuración del Sistema';
$string['generalsettings'] = 'General';
$string['developmentsettings'] = 'Desarrollo';
$string['plugins'] = 'Plugins';
$string['authentication'] = 'Autenticación';
// sitename ya definido en línea 12
$string['sitenamehelp'] = 'Nombre que aparecerá en todo el sitio';
$string['debugmode'] = 'Modo Debug';
$string['debughelp'] = 'Habilita mensajes de debug en logs. Solo para desarrollo.';
$string['sessiontimeouthelp'] = 'Tiempo de inactividad antes de cerrar sesión. Rango: 10 min (600) - 24 hrs (86400). Valor recomendado: 7200 (2 horas)';
$string['configsaved'] = 'Configuración guardada exitosamente';
$string['systeminfo'] = 'Información del Sistema';
$string['systemversion'] = 'Versión del Sistema';
$string['phpversion'] = 'Versión de PHP';
$string['database'] = 'Base de Datos';
$string['tableprefix'] = 'Prefijo de Tablas';
$string['currentuser'] = 'Usuario Actual';

// Formularios
$string['required'] = 'Obligatorio';
$string['optional'] = 'Opcional';
$string['submit'] = 'Enviar';
$string['reset'] = 'Restablecer';
$string['confirm'] = 'Confirmar';
$string['confirmdelete'] = '¿Está seguro que desea eliminar este elemento?';
$string['yes'] = 'Sí';
$string['no'] = 'No';

// Acciones
$string['actions'] = 'Acciones';
$string['add'] = 'Agregar';
$string['remove'] = 'Remover';
$string['assign'] = 'Asignar';
$string['unassign'] = 'Desasignar';
$string['update'] = 'Actualizar';
$string['manage'] = 'Gestionar';
$string['configure'] = 'Configurar';

// Paginación
$string['page'] = 'Página';
$string['of'] = 'de';
$string['showing'] = 'Mostrando';
$string['to'] = 'a';
$string['entries'] = 'entradas';
$string['noresults'] = 'No se encontraron resultados';
$string['nomoreresults'] = 'No hay más resultados';

// Estados
$string['status'] = 'Estado';
$string['enabled'] = 'Habilitado';
$string['disabled'] = 'Deshabilitado';
$string['visible'] = 'Visible';
$string['hidden'] = 'Oculto';

// Instalación y upgrade
$string['installation'] = 'Instalación';
$string['upgrade'] = 'Actualización';
$string['installing'] = 'Instalando...';
$string['upgrading'] = 'Actualizando...';
$string['installcomplete'] = 'Instalación completa';
$string['upgradecomplete'] = 'Actualización completa';
$string['upgraderequired'] = 'Se requiere actualización';
$string['clicktoupgrade'] = 'Haga clic aquí para actualizar';

// Timestrings
$string['second'] = 'segundo';
$string['seconds'] = 'segundos';
$string['minute'] = 'minuto';
$string['minutes'] = 'minutos';
$string['hour'] = 'hora';
$string['hours'] = 'horas';
$string['day'] = 'día';
$string['days'] = 'días';
$string['week'] = 'semana';
$string['weeks'] = 'semanas';
$string['month'] = 'mes';
$string['months'] = 'meses';
$string['year'] = 'año';
$string['years'] = 'años';
$string['ago'] = 'hace {$a}';
$string['never'] = 'Nunca';

// Descripciones de capabilities
$string['capability:nexosupport/admin:viewdashboard'] = 'Ver dashboard administrativo';
$string['capability:nexosupport/admin:manageusers'] = 'Gestionar usuarios del sistema';
$string['capability:nexosupport/admin:manageroles'] = 'Gestionar roles y permisos';
$string['capability:nexosupport/admin:assignroles'] = 'Asignar roles a usuarios';
$string['capability:nexosupport/admin:manageconfig'] = 'Gestionar configuración del sistema';
$string['capability:nexosupport/user:editownprofile'] = 'Editar perfil propio';
$string['capability:nexosupport/user:viewprofile'] = 'Ver perfil de usuarios';

// Descripciones de tarjetas de acción
$string['manageusers_desc'] = 'Ver, crear y editar usuarios del sistema';
$string['manageroles_desc'] = 'Configurar roles y asignar capabilities';
$string['managesettings_desc'] = 'Configurar parámetros del sistema';
$string['myprofile_desc'] = 'Ver y editar mi información personal';

// Mensajes de validación
$string['invalidusername'] = 'Nombre de usuario inválido';
$string['invalidemailformat'] = 'Formato de correo electrónico inválido';
$string['usernameexists'] = 'El nombre de usuario ya existe';
$string['emailexists'] = 'El correo electrónico ya está registrado';
$string['passwordtooshort'] = 'La contraseña es demasiado corta (mínimo 8 caracteres)';
$string['passwordmismatch'] = 'Las contraseñas no coinciden';

// Otros
$string['description'] = 'Descripción';
$string['name'] = 'Nombre';
$string['code'] = 'Código';
$string['value'] = 'Valor';
$string['type'] = 'Tipo';
$string['component'] = 'Componente';
$string['context'] = 'Contexto';
$string['system'] = 'Sistema';
$string['unknown'] = 'Desconocido';
$string['none'] = 'Ninguno';
$string['all'] = 'Todos';
$string['any'] = 'Cualquiera';
$string['empty'] = 'Vacío';
$string['nodata'] = 'No hay datos disponibles';

// Additional strings for pages
$string['authpluginnotfound'] = 'Plugin de autenticación no encontrado';
$string['pleaseselectcriteria'] = 'No hay usuarios que mostrar con los criterios seleccionados.';
$string['nousersfound'] = 'No se encontraron usuarios';
$string['errorconfig'] = 'Error guardando configuración: {$a}';
$string['nouserswithcriteria'] = 'No hay usuarios con este rol';
$string['title_login'] = 'Iniciar sesión';
$string['adminarea'] = 'Área de Administración';
$string['welcome_admin'] = 'Bienvenido al panel de administración';
$string['managementtools'] = 'Herramientas de gestión';
$string['quickaccess'] = 'Acceso rápido';
$string['systeminformation'] = 'Información del sistema';
$string['usercount'] = '{$a} usuario(s) con este rol';
$string['roleassignmentcount'] = '{$a} asignación(es)';
$string['createfirstrole'] = 'Crear Primer Rol';
$string['usernotfound'] = 'Usuario no encontrado';
$string['rolenotfound'] = 'Rol no encontrado';
$string['mustselectuserrole'] = 'Debe especificar un usuario o rol';
$string['availableroles'] = 'Roles Disponibles';
$string['assignrole'] = 'Asignar Rol';
$string['removerole'] = 'Remover Rol';
$string['userswithrol'] = 'Usuarios con el Rol';
$string['nouserswithrole'] = 'Este rol no ha sido asignado a ningún usuario aún.';
$string['seeuser'] = 'Ver Usuario';
$string['noupdaterequired'] = 'No hay actualizaciones pendientes. El sistema está en la última versión.';
$string['currentversion'] = 'Versión actual';
$string['requiresupgrade'] = 'Se requiere actualización';
$string['newversion'] = 'Nueva versión';
$string['upgradenow'] = 'Actualizar ahora';
$string['systemreadyupgrade'] = 'El sistema está listo para actualizar a una nueva versión.';
$string['searchbyname'] = 'Buscar por nombre, usuario o email...';
$string['allstatuses'] = 'Todos los estados';
$string['showingusers'] = 'Mostrando usuarios {$a->from} - {$a->to} de {$a->total}';
$string['newuser'] = 'Nuevo Usuario';
$string['basicinfo'] = 'Información Básica';
$string['accountsettings'] = 'Configuración de Cuenta';
$string['suspenduser'] = 'Suspender Usuario';
$string['userissuspended'] = 'El usuario está suspendido y no puede iniciar sesión';
$string['backtolist'] = 'Volver al listado';
$string['roleinfo'] = 'Información del Rol';
$string['systemrole'] = 'Rol del Sistema';
$string['systemrolewarning'] = 'Los roles del sistema no pueden ser eliminados';
$string['cannotrename'] = 'No se puede cambiar el nombre corto de un rol del sistema';
$string['deleteconfirm'] = '¿Estás seguro de que deseas eliminar este rol?';
$string['assignedtousers'] = 'Asignado a {$a} usuarios';
$string['dangerzone'] = 'Zona Peligrosa';
$string['definepermissions'] = 'Definir Permisos para el Rol';
$string['selectpermissions'] = 'Selecciona los permisos para cada capability';
$string['capabilityname'] = 'Nombre de la Capability';
$string['savepermissions'] = 'Guardar Permisos';
$string['backtoroles'] = 'Volver a Roles';
$string['assignuserinfo'] = 'Información del usuario';
$string['roleassignments'] = 'Asignaciones de Roles';
$string['close'] = 'Cerrar';

// User management actions (v1.1.3)
$string['usernotfound'] = 'Usuario no encontrado';
$string['userconfirmed'] = 'Usuario confirmado exitosamente';
$string['usernotconfirmed'] = 'No se pudo confirmar el usuario';
$string['alreadyconfirmed'] = 'El usuario ya está confirmado';
$string['emailconfirmsent'] = 'Email de confirmación enviado';
$string['emailconfirmsentfailure'] = 'Error al enviar email de confirmación';
$string['userdeleted'] = 'Usuario eliminado correctamente';
$string['cannotdeleteadmin'] = 'No se puede eliminar el administrador del sistema';
$string['userdeletionerror'] = 'Error al eliminar el usuario';
$string['usersuspended'] = 'Usuario suspendido correctamente';
$string['cannotsuspenduser'] = 'No se puede suspender este usuario';
$string['userunsuspended'] = 'Usuario reactivado correctamente';
$string['cannotunsuspenduser'] = 'No se puede reactivar este usuario';
$string['userunlocked'] = 'Cuenta de usuario desbloqueada';
$string['cannotunlockuser'] = 'No se puede desbloquear este usuario';
$string['confirmdelete'] = '¿Está seguro de que desea eliminar este usuario?';
$string['confirmdeleteuser'] = '¿Está seguro de que desea eliminar permanentemente al usuario {$a}?';
$string['never'] = 'Nunca';

// Role management actions (v1.1.3)
$string['moveup'] = 'Subir';
$string['movedown'] = 'Bajar';
$string['confirmdeleterole'] = '¿Está seguro de que desea eliminar este rol?';
$string['cannotdeletesystemrole'] = 'No se puede eliminar un rol del sistema';
$string['errordeletingrole'] = 'Error al eliminar el rol';

// Admin settings (v1.1.4)
$string['systemsettings'] = 'Configuración del Sistema';
$string['generalsettings'] = 'Configuración General';
$string['configgeneralsettings'] = 'Configuración general del sitio';
$string['usersettings'] = 'Configuración de Usuarios';
$string['configusersettings'] = 'Opciones predeterminadas para usuarios';
$string['security'] = 'Seguridad';
$string['sessions'] = 'Sesiones';
$string['configsessionsettings'] = 'Configuración de sesiones de usuario';
$string['developmentsettings'] = 'Desarrollo';
$string['configdebugsettings'] = 'Opciones de depuración y desarrollo';
$string['passwordpolicy'] = 'Política de Contraseñas';
$string['configpasswordpolicy'] = 'Requisitos de seguridad para contraseñas';
$string['configsaved'] = 'Configuración guardada exitosamente';
$string['errorconfig'] = 'Error al guardar configuración: {$a}';
$string['pagenotfound'] = 'Página no encontrada';
$string['nopermission'] = 'No tiene permiso para acceder a esta página';
$string['unknown'] = 'Desconocido';
// sitenamehelp ya definido arriba
$string['sitedescription'] = 'Descripción del sitio';
$string['sitedescriptionhelp'] = 'Descripción breve del propósito del sitio';
$string['sessiontimeout'] = 'Tiempo de espera de sesión';
$string['sessiontimeouthelp'] = 'Tiempo en segundos antes de que expire una sesión inactiva';
$string['sessioncookie'] = 'Duración de cookie de sesión';
$string['sessioncookiehelp'] = 'Tiempo en segundos que dura la cookie de sesión';
$string['debugmode'] = 'Modo de Depuración';
$string['debughelp'] = 'Activar mensajes de depuración (solo para desarrollo)';
$string['debugdisplay'] = 'Mostrar errores en pantalla';
$string['debugdisplayhelp'] = 'Mostrar errores PHP en pantalla (solo desarrollo)';
$string['systeminfo'] = 'Información del Sistema';
$string['systemversion'] = 'Versión del Sistema';
$string['phpversion'] = 'Versión de PHP';
$string['database'] = 'Base de Datos';
$string['tableprefix'] = 'Prefijo de Tablas';
$string['currentuser'] = 'Usuario Actual';
$string['savechanges'] = 'Guardar Cambios';
$string['defaultlang'] = 'Idioma Predeterminado';
$string['defaultlanghelp'] = 'Idioma predeterminado para nuevos usuarios';
$string['requireconfirmemail'] = 'Requerir confirmación de email';
$string['requireconfirmemailhelp'] = 'Los usuarios deben confirmar su dirección de correo';
$string['minpasswordlength'] = 'Longitud mínima de contraseña';
$string['minpasswordlengthhelp'] = 'Número mínimo de caracteres para contraseñas';
$string['passwordrequiredigit'] = 'Requerir números';
$string['passwordrequiredigithelp'] = 'Las contraseñas deben contener al menos un número';
$string['passwordrequirelower'] = 'Requerir minúsculas';
$string['passwordrequirelowerhelp'] = 'Las contraseñas deben contener al menos una letra minúscula';
$string['passwordrequireupper'] = 'Requerir mayúsculas';
$string['passwordrequireupperhelp'] = 'Las contraseñas deben contener al menos una letra mayúscula';
$string['validateerror'] = 'Error de validación';
$string['notnumeric'] = 'El valor debe ser numérico';
$string['numbertoosmall'] = 'El número es muy pequeño (mínimo: {$a})';
$string['numbertoobig'] = 'El número es muy grande (máximo: {$a})';

// Password management (v1.1.4)
$string['changepassword'] = 'Cambiar contraseña';
$string['oldpassword'] = 'Contraseña actual';
$string['newpassword'] = 'Nueva contraseña';
$string['passwordchanged'] = 'Contraseña cambiada';
$string['passwordsdiffer'] = 'Las contraseñas no coinciden';
$string['mustchangepassword'] = 'La nueva contraseña debe ser diferente de la actual';
$string['logoutothersessions'] = 'Cerrar sesión en otros dispositivos';
$string['forcepasswordchangenotice'] = 'Debe cambiar su contraseña para continuar';
$string['passwordforgotten'] = 'Contraseña olvidada';
$string['passwordforgotteninstructions2'] = 'Para restablecer su contraseña, envíe su nombre de usuario o dirección de correo electrónico. Si lo encontramos en la base de datos, le enviaremos un correo electrónico con instrucciones sobre cómo obtener acceso nuevamente.';
$string['searchbyusername'] = 'Buscar por nombre de usuario';
$string['searchbyemail'] = 'Buscar por dirección de correo';
$string['usernameoremail'] = 'Ingrese su nombre de usuario o correo electrónico';
$string['emailnotfound'] = 'La dirección de correo no está registrada en el sistema';
$string['usernamenotfound'] = 'El nombre de usuario no existe en la base de datos';
$string['confirmednot'] = 'Su cuenta aún no ha sido confirmada. Revise su correo electrónico.';
$string['emailpasswordconfirmmaybesent'] = 'Si la información proporcionada es correcta, se ha enviado un correo a su dirección.';
$string['emailpasswordconfirmnotsent'] = 'Los datos que proporcionó no coinciden con ninguna cuenta. Verifique e intente nuevamente.';
$string['emailpasswordconfirmnoemail'] = 'La cuenta no tiene dirección de correo registrada.';
$string['emailalreadysent'] = 'Ya se ha enviado un correo de restablecimiento de contraseña recientemente. Por favor revise su bandeja de entrada.';
$string['emailpasswordconfirmsent'] = 'Se ha enviado un correo a ******@{$a}';
$string['emailresetconfirmsent'] = 'Se ha enviado un correo a ******@{$a} con instrucciones para restablecer su contraseña.';
$string['emailresetconfirmation'] = 'Hola {$a->firstname} {$a->lastname},

Se ha solicitado un restablecimiento de contraseña para su cuenta en {$a->sitename}.

Para confirmar este cambio y establecer una nueva contraseña, haga clic en el siguiente enlace:

{$a->link}

Si no solicitó esto, por favor ignore este mensaje.

En la mayoría de programas de correo, esto debería aparecer como un enlace en el que puede hacer clic. Si no funciona, copie y pegue la dirección en la barra de direcciones de su navegador.

Si necesita ayuda, póngase en contacto con el administrador del sitio.';
$string['emailresetconfirmationsubject'] = '{$a}: Confirmación de cambio de contraseña';
$string['emailpasswordchangeinfo'] = 'Hola {$a->firstname} {$a->lastname},

Se ha solicitado un restablecimiento de contraseña para su cuenta en \'{$a->sitename}\'.

Para cambiar su contraseña, póngase en contacto con el administrador del sitio:

{$a->supportemail}';
$string['emailpasswordchangeinfosubject'] = '{$a}: Información de cambio de contraseña';
$string['setpassword'] = 'Establecer contraseña';
$string['setpasswordinstructions'] = 'Ingrese su nueva contraseña a continuación y luego haga clic en "Guardar cambios".';
$string['passwordset'] = 'Su contraseña ha sido establecida';
$string['noresetrecord'] = 'No hay registro de esta solicitud de restablecimiento de contraseña. Por favor inicie una nueva solicitud.';
$string['resetrecordexpired'] = 'El enlace de restablecimiento de contraseña que utilizó ha expirado. El enlace es válido solo por {$a} minutos. Por favor inicie una nueva solicitud.';
$string['cannotresetguestpwd'] = 'No puede restablecer la contraseña del usuario invitado';
$string['cannotmailconfirm'] = 'No se pudo enviar el correo de confirmación';
$string['alreadyconfirmed'] = 'Su cuenta ya ha sido confirmada';
$string['confirmed'] = 'Su cuenta ha sido confirmada';
$string['invalidconfirmdata'] = 'Datos de confirmación inválidos';
$string['errorwhenconfirming'] = 'Error al confirmar la cuenta';
$string['thanks'] = 'Gracias';
$string['participants'] = 'Participantes';
$string['again'] = 'otra vez';
$string['continue'] = 'Continuar';
$string['required'] = 'Requerido';
$string['loginalready'] = 'Ya ha iniciado sesión';
$string['administrator'] = 'Administrador';

// Event names (v1.1.6)
$string['eventuserloggedin'] = 'Usuario ingresó al sistema';
$string['eventusercreated'] = 'Usuario creado';
$string['eventuserupdated'] = 'Usuario actualizado';
$string['eventuserdeleted'] = 'Usuario eliminado';
$string['eventusersuspended'] = 'Usuario suspendido';
$string['eventuserunsuspended'] = 'Usuario reactivado';
$string['eventroleassigned'] = 'Rol asignado';
$string['eventroleunassigned'] = 'Rol removido';
$string['eventcapabilityassigned'] = 'Capability asignada';

// Navigation v1.1.7
$string['navigation'] = 'Navegación';
$string['browselistofusers'] = 'Explorar lista de usuarios';
$string['addnewuser'] = 'Agregar nuevo usuario';
$string['manageroles'] = 'Administrar roles';
$string['defineroles'] = 'Definir roles';
$string['assignroles'] = 'Asignar roles';
$string['pleaselogin'] = 'Por favor inicie sesión para continuar';
$string['eventcapabilityupdated'] = 'Capability actualizada';
$string['collapse'] = 'Contraer';
$string['expand'] = 'Expandir';
$string['breadcrumbs'] = 'Ruta de navegación';

// Cache v1.1.8
$string['cache'] = 'Caché';
$string['cachepurge'] = 'Purgar Caché';
$string['cachepurgedall'] = 'Todos los cachés han sido purgados correctamente';
$string['cachepurgedopcache'] = 'OPcache ha sido purgado correctamente';
$string['cachepurgedmustache'] = 'Caché de Mustache purgado correctamente';
$string['cachepurgedi18n'] = 'Caché de i18n purgado correctamente';
$string['cachepurgedapp'] = 'Caché de aplicación purgado correctamente';
$string['cachepurgedrbac'] = 'Caché de RBAC purgado correctamente';
$string['invalidpurgetype'] = 'Tipo de purga inválido';
$string['purgeresults'] = 'Resultados de la Purga';
$string['cachestatus'] = 'Estado del Caché';
$string['memoryused'] = 'Memoria Usada';
$string['memoryfree'] = 'Memoria Libre';
$string['memorywasted'] = 'Memoria Desperdiciada';
$string['cachedscripts'] = 'Scripts en Caché';
$string['hits'] = 'Aciertos';
$string['misses'] = 'Fallos';
$string['hitrate'] = 'Tasa de Aciertos';
$string['opcachedisabled'] = 'OPcache no está habilitado';
$string['purgecache'] = 'Purgar Caché';
$string['purgecachehelp'] = 'Purgar el caché puede resolver problemas de código antiguo ejecutándose. Use con cuidado en producción.';
$string['purgeallcaches'] = 'Purgar Todos los Cachés';
$string['purgeopcache'] = 'Purgar OPcache';
$string['purgemustachecache'] = 'Purgar Caché de Mustache';
$string['purgei18ncache'] = 'Purgar Caché de i18n';
$string['purgeappcache'] = 'Purgar Caché de Aplicación';
$string['purgerbaccache'] = 'Purgar Caché de RBAC';
$string['aboutcaches'] = 'Acerca de los Cachés';
$string['opcachehelp'] = 'Almacena bytecode PHP compilado en memoria. Purgar cuando actualice código.';
$string['mustachecachehelp'] = 'Templates Mustache compilados. Purgar después de cambios en templates.';
$string['i18ncachehelp'] = 'Cadenas de idioma en memoria. Purgar después de modificar archivos de idioma.';
$string['appcachehelp'] = 'Cachés internos de la aplicación (configuración, etc.)';
$string['rbaccachehelp'] = 'Caché de permisos y capacidades del sistema RBAC';
$string['applicationcache'] = 'Caché de Aplicación';
$string['mustachecache'] = 'Caché de Mustache';
$string['i18ncache'] = 'Caché de i18n';
$string['cachedtemplates'] = 'Templates Compilados';
$string['cachesize'] = 'Tamaño del Caché';
$string['managecaches'] = 'Gestionar Cachés';

// Navigation v1.1.10 - Moodle-style
$string['editprofile'] = 'Editar perfil';
$string['preferences'] = 'Preferencias';
$string['changepassword'] = 'Cambiar contraseña';
$string['notificationpreferences'] = 'Preferencias de notificaciones';
$string['bulkuseractions'] = 'Acciones masivas de usuarios';
$string['accounts'] = 'Cuentas';
$string['cohorts'] = 'Cohortes';
$string['userprofilefields'] = 'Campos de perfil de usuario';
$string['permissions'] = 'Permisos';
$string['capabilityreport'] = 'Informe de capacidades';
$string['courses'] = 'Cursos';
$string['managecourses'] = 'Gestionar cursos';
$string['coursecategories'] = 'Categorías de cursos';
$string['pluginsoverview'] = 'Resumen de plugins';
$string['installplugins'] = 'Instalar plugins';
$string['manageauth'] = 'Gestionar autenticación';
$string['mfafactors'] = 'Factores MFA';
$string['managefactors'] = 'Gestionar factores';
$string['admintools'] = 'Herramientas de administración';
$string['localplugins'] = 'Plugins locales';
$string['blocks'] = 'Bloques';
$string['appearance'] = 'Apariencia';
$string['themes'] = 'Temas';
$string['themeselector'] = 'Selector de temas';
$string['themesettings'] = 'Configuración de temas';
$string['htmlsettings'] = 'Configuración HTML';
$string['additionalhtml'] = 'HTML adicional';
$string['server'] = 'Servidor';
$string['systempaths'] = 'Rutas del sistema';
$string['supportcontact'] = 'Contacto de soporte';
$string['sessionhandling'] = 'Manejo de sesiones';
$string['httpsettings'] = 'Configuración HTTP';
$string['maintenancemode'] = 'Modo de mantenimiento';
$string['environment'] = 'Entorno';
$string['phpinfo'] = 'Info de PHP';
$string['reports'] = 'Informes';
$string['logs'] = 'Registros';
$string['livelogs'] = 'Registros en vivo';
$string['configchanges'] = 'Cambios de configuración';
$string['securityreport'] = 'Informe de seguridad';
$string['performance'] = 'Rendimiento';
$string['development'] = 'Desarrollo';
$string['debugging'] = 'Depuración';
$string['purgecaches'] = 'Purgar cachés';
$string['maketestsite'] = 'Crear sitio de pruebas';
$string['xmldbeditor'] = 'Editor XMLDB';
$string['wstestclient'] = 'Cliente de prueba de servicios web';
$string['upgradenow'] = 'Actualizar ahora';

// Debugging Settings v1.1.10
$string['debuglevel'] = 'Nivel de depuración';
$string['debuglevel_desc'] = 'Controla qué mensajes de error se registran. Niveles más altos muestran más información.';
$string['debugnone'] = 'Ninguno';
$string['debugnone_desc'] = 'No registrar mensajes de depuración. Usar en producción.';
$string['debugminimal'] = 'Mínimo';
$string['debugminimal_desc'] = 'Solo errores críticos (E_ERROR, E_PARSE).';
$string['debugnormal'] = 'Normal';
$string['debugnormal_desc'] = 'Errores, advertencias y avisos (E_ERROR, E_PARSE, E_WARNING, E_NOTICE).';
$string['debugdeveloper'] = 'Desarrollador';
$string['debugdeveloper_desc'] = 'Todos los mensajes excepto strict y deprecated. Recomendado para desarrollo.';
$string['debugall'] = 'Todos';
$string['debugall_desc'] = 'TODOS los mensajes incluyendo strict y deprecated. Solo para depuración avanzada.';
$string['debugdisplay'] = 'Mostrar errores en pantalla';
$string['debugdisplay_desc'] = 'Controla si los mensajes de error se muestran en el navegador.';
$string['displayerrors'] = 'Mostrar errores en pantalla';
$string['displayerrors_help'] = 'Cuando está activado, los errores PHP se muestran en el navegador. NUNCA activar en producción.';
$string['debuggingwarning'] = 'La depuración debe estar DESACTIVADA en sitios de producción. Los mensajes de depuración pueden exponer información sensible.';
$string['recommended_production'] = 'Recomendado para producción';
$string['developer_only'] = 'Solo desarrollo';
$string['experts_only'] = 'Solo expertos';
$string['performancenotice'] = 'Aviso de rendimiento';
$string['debugperformancewarning'] = 'Los niveles de depuración altos pueden afectar el rendimiento del sistema. Use DEBUG_NONE en producción.';
$string['currentsettings'] = 'Configuración actual';
$string['invaliddebug level'] = 'Nivel de depuración inválido';

// User profile and preferences v1.1.11
$string['emailexists'] = 'Este correo ya está siendo usado por otro usuario';
$string['cannotchangeusername'] = 'El nombre de usuario no puede ser cambiado';
$string['usernamecannotbechanged'] = 'El nombre de usuario no puede ser modificado una vez creado';
$string['notificationpreferencesdesc'] = 'Configure cómo desea recibir notificaciones del sistema.';
$string['emailnotifications'] = 'Notificaciones por correo electrónico';
$string['receivemailnotifications'] = 'Recibir notificaciones por correo electrónico';
$string['receivemailnotificationshelp'] = 'Cuando está activado, recibirá notificaciones por correo electrónico sobre actividades importantes.';
$string['digesttype'] = 'Tipo de resumen';
$string['nodigest'] = 'Sin resumen';
$string['nodigesthelp'] = 'Recibir notificaciones individuales por cada evento.';
$string['dailydigest'] = 'Resumen diario';
$string['dailydigesthelp'] = 'Recibir un único correo al día con todas las notificaciones acumuladas.';
$string['weeklydigest'] = 'Resumen semanal';
$string['weeklydigesthelp'] = 'Recibir un único correo a la semana con todas las notificaciones acumuladas.';
$string['preferencessaved'] = 'Preferencias guardadas exitosamente';
$string['coming_soon'] = 'Próximamente';

// Admin settings additional (v1.2.0)
$string['general'] = 'General';
$string['sitesettings'] = 'Configuración del Sitio';
$string['sitesettingshelp'] = 'Configuración general del sitio web';
$string['passwordrequirespecial'] = 'Requerir caracteres especiales';
$string['passwordrequirespecialhelp'] = 'Las contraseñas deben contener caracteres especiales (!@#$%^&*)';
$string['wrongpassword'] = 'La contraseña actual es incorrecta';
$string['exists'] = 'Existe';
$string['notexists'] = 'No existe';
$string['writable'] = 'Escribible';
$string['notwritable'] = 'No escribible';

// Authentication and Login (v1.2.1)
$string['accountlocked'] = 'Cuenta bloqueada';
$string['accountlockednotification'] = 'Hola {$a->firstname},

Su cuenta en {$a->sitename} ha sido bloqueada debido a múltiples intentos de inicio de sesión fallidos.

Para desbloquear su cuenta, haga clic en el siguiente enlace:
{$a->link}

Si no solicitó esto, puede ignorar este mensaje.

Saludos,
El equipo de {$a->sitename}';
$string['accountunlocked'] = 'Su cuenta ha sido desbloqueada. Ahora puede iniciar sesión.';
$string['accountsuspended'] = 'Su cuenta ha sido suspendida. Por favor contacte al administrador.';
$string['toomanyloginattempts'] = 'Demasiados intentos de inicio de sesión fallidos. Su cuenta ha sido bloqueada temporalmente.';

// Email confirmation
$string['emailconfirmationsubject'] = '{$a}: Confirma tu cuenta';
$string['emailconfirmation'] = 'Hola {$a->firstname},

Se ha creado una nueva cuenta en {$a->sitename} usando tu dirección de correo.

Para confirmar tu cuenta, por favor haz clic en el siguiente enlace:
{$a->link}

En la mayoría de programas de correo, esto aparecerá como un enlace azul en el que puedes hacer clic. Si esto no funciona, copia y pega la dirección en la barra de direcciones de tu navegador.

Si necesitas ayuda, por favor contacta al administrador del sitio.

{$a->admin}';

// Password reset
$string['emailresetconfirmation'] = 'Hola {$a->firstname},

Alguien (probablemente usted) ha solicitado restablecer la contraseña de su cuenta en {$a->sitename}.

Para confirmar esta solicitud y establecer una nueva contraseña, haga clic en el siguiente enlace:
{$a->link}

Este enlace es válido por {$a->resetminutes} minutos.

Si no solicitó restablecer su contraseña, puede ignorar este mensaje.

Saludos,
{$a->admin}';
$string['emailresetconfirmationsubject'] = '{$a}: Solicitud de restablecimiento de contraseña';
$string['passwordforgotteninstructions2'] = 'Para restablecer su contraseña, ingrese su nombre de usuario o su dirección de correo electrónico. Si encontramos su cuenta, le enviaremos un correo con instrucciones para restablecer su contraseña.';
$string['setpasswordinstructions'] = 'Por favor ingrese su nueva contraseña a continuación.';
$string['passwordset'] = 'Su contraseña ha sido cambiada exitosamente.';
$string['noresetrecord'] = 'El enlace de restablecimiento de contraseña no es válido o ha expirado. Por favor solicite uno nuevo.';
$string['resetrecordexpired'] = 'El enlace de restablecimiento de contraseña ha expirado. Los enlaces son válidos por {$a} minutos. Por favor solicite uno nuevo.';
$string['cannotresetguestpwd'] = 'La contraseña del usuario invitado no puede ser restablecida.';
$string['emailpasswordconfirmmaybesent'] = 'Si su cuenta existe y tiene una dirección de correo válida, se le ha enviado un correo con instrucciones.';
$string['emailpasswordconfirmnotsent'] = 'No se encontró ninguna cuenta con esa información.';
$string['emailpasswordconfirmnoemail'] = 'La cuenta no tiene una dirección de correo registrada.';
$string['emailalreadysent'] = 'Ya se ha enviado un correo de restablecimiento. Por favor espere antes de solicitar otro.';
$string['emailpasswordconfirmsent'] = 'Se ha enviado un correo a {$a} con instrucciones para restablecer su contraseña.';
$string['emailresetconfirmsent'] = 'Se ha enviado un correo a {$a} con instrucciones para restablecer su contraseña.';
$string['usernameoremail'] = 'Por favor ingrese su nombre de usuario O su correo electrónico, no ambos.';
$string['emailnotfound'] = 'No se encontró ninguna cuenta con ese correo electrónico.';
$string['usernamenotfound'] = 'No se encontró ninguna cuenta con ese nombre de usuario.';
$string['confirmednot'] = 'Su cuenta aún no ha sido confirmada. Se ha reenviado el correo de confirmación.';

// Password change
$string['changepassword'] = 'Cambiar contraseña';
$string['currentpassword'] = 'Contraseña actual';
$string['newpassword'] = 'Nueva contraseña';
$string['newpassword2'] = 'Confirmar nueva contraseña';
$string['passwordchanged'] = 'Su contraseña ha sido cambiada exitosamente.';
$string['passwordsdiffer'] = 'Las contraseñas no coinciden.';
$string['wrongcurrentpassword'] = 'La contraseña actual es incorrecta.';
$string['nopasswordchange'] = 'Su método de autenticación no permite cambiar la contraseña.';
$string['errorpasswordupdate'] = 'Error al actualizar la contraseña.';
$string['logoutothersessions'] = 'Cerrar otras sesiones';
$string['logoutothersessionshelp'] = 'Al cambiar su contraseña, cierre sesión en todos los demás dispositivos donde haya iniciado sesión.';

// Email related
$string['emailpasswordchangeinfo'] = 'Hola {$a->firstname},

Alguien (probablemente usted) ha solicitado cambiar la contraseña de su cuenta en {$a->sitename}.

Sin embargo, su tipo de cuenta no permite cambiar la contraseña a través del sitio web.

Por favor contacte a {$a->supportemail} para obtener ayuda.

Saludos,
{$a->admin}';
$string['emailpasswordchangeinfosubject'] = '{$a}: Información sobre cambio de contraseña';

// General auth strings
$string['administrator'] = 'Administrador';
$string['cannotmailconfirm'] = 'No se pudo enviar el correo de confirmación.';

// Not implemented pages
$string['notimplemented'] = 'No Implementado';
$string['pagenotimplemented'] = 'Esta pagina aun no esta implementada. Estara disponible en una version futura.';
$string['backtoadmin'] = 'Volver a Administracion';
