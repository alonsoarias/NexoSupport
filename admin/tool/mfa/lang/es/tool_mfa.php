<?php
/**
 * Strings for component 'tool_mfa', language 'es'
 *
 * @package    tool_mfa
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Autenticación multifactor (MFA)';
$string['mfa'] = 'MFA';
$string['multifactorauthentication'] = 'Autenticación de múltiples factores';

// Settings
$string['enabled'] = 'Habilitar MFA';
$string['enabled_desc'] = 'Habilitar autenticación de múltiples factores para el sistema';
$string['requiremfa'] = 'Requerir MFA';
$string['requiremfa_desc'] = 'Requerir que todos los usuarios configuren al menos un factor MFA';
$string['graceper iod'] = 'Período de gracia';
$string['graceperiod_desc'] = 'Número de días que los usuarios tienen para configurar MFA antes de ser obligados';

// Factors
$string['factors'] = 'Factores de autenticación';
$string['availablefactors'] = 'Factores disponibles';
$string['enabledfactors'] = 'Factores habilitados';
$string['configuredfactors'] = 'Factores configurados';
$string['nofactors'] = 'No hay factores configurados';
$string['addfactor'] = 'Agregar factor';
$string['removefactor'] = 'Eliminar factor';

// Factor status
$string['factorsetup'] = 'Configurar factor';
$string['factorremove'] = 'Eliminar factor';
$string['factorenabled'] = 'Factor habilitado';
$string['factordisabled'] = 'Factor deshabilitado';
$string['factoractive'] = 'Activo';
$string['factorinactive'] = 'Inactivo';

// Setup
$string['setupmfa'] = 'Configurar MFA';
$string['setupfactor'] = 'Configurar factor';
$string['setupinstructions'] = 'Siga las instrucciones para configurar su factor de autenticación';
$string['setupcomplete'] = 'Configuración completa';
$string['setupfailed'] = 'Error en la configuración';

// Verification
$string['verify'] = 'Verificar';
$string['verification'] = 'Verificación';
$string['verificationcode'] = 'Código de verificación';
$string['verificationrequired'] = 'Se requiere verificación';
$string['verificationfailed'] = 'Verificación fallida';
$string['verificationsuccess'] = 'Verificación exitosa';
$string['entercode'] = 'Ingrese el código de verificación';
$string['resendcode'] = 'Reenviar código';

// Login
$string['mfarequired'] = 'Se requiere autenticación multifactor';
$string['selectfactor'] = 'Seleccione un factor de autenticación';
$string['continuelogin'] = 'Continuar inicio de sesión';
$string['cancellogin'] = 'Cancelar inicio de sesión';

// User preferences
$string['preferences'] = 'Preferencias de MFA';
$string['managedfactors'] = 'Gestionar factores';
$string['yourfactors'] = 'Sus factores configurados';
$string['addfactor'] = 'Agregar nuevo factor';

// States
$string['state_pass'] = 'Verificado';
$string['state_fail'] = 'Fallido';
$string['state_neutral'] = 'Neutral';
$string['state_unknown'] = 'Desconocido';

// Messages
$string['factorsetupsuccessfully'] = 'Factor configurado exitosamente';
$string['factorremovedsuccessfully'] = 'Factor eliminado exitosamente';
$string['factorverifiedsuccessfully'] = 'Factor verificado exitosamente';
$string['allrequiredfactorspassed'] = 'Todos los factores requeridos han sido verificados';

// Errors
$string['errorinvalidfactor'] = 'Factor inválido';
$string['errorfactornotfound'] = 'Factor no encontrado';
$string['errorfactornotenabled'] = 'El factor no está habilitado';
$string['errorverificationfailed'] = 'Error en la verificación';
$string['errorinvalidcode'] = 'Código inválido';
$string['errorcodeexpired'] = 'El código ha expirado';
$string['errortoomanyattempts'] = 'Demasiados intentos fallidos';
$string['errorsetupfailed'] = 'Error al configurar el factor';

// Help
$string['mfa_help'] = 'La autenticación multifactor agrega una capa adicional de seguridad al requerir múltiples formas de verificación de identidad.';
$string['factors_help'] = 'Puede configurar múltiples factores de autenticación. Durante el inicio de sesión, deberá verificar al menos uno de sus factores configurados.';
$string['setupmfa_help'] = 'Configure al menos un factor de autenticación para proteger su cuenta. Se recomienda configurar múltiples factores como respaldo.';

// Notifications
$string['mfarequirednotification'] = 'Debe configurar la autenticación multifactor';
$string['mfarequirednotification_desc'] = 'La autenticación multifactor es obligatoria para su cuenta. Por favor, configure al menos un factor de autenticación.';
$string['mfagraceperiod'] = 'Período de gracia de MFA';
$string['mfagraceperiod_desc'] = 'Tiene {$a} días restantes para configurar la autenticación multifactor.';

// Reports
$string['mfareport'] = 'Reporte de MFA';
$string['mfastatus'] = 'Estado de MFA';
$string['userswithmfa'] = 'Usuarios con MFA';
$string['userswithoutmfa'] = 'Usuarios sin MFA';
$string['mfacompliance'] = 'Cumplimiento de MFA';

// Capabilities
$string['tool_mfa:manage'] = 'Gestionar MFA';
$string['tool_mfa:configure'] = 'Configurar MFA';
$string['tool_mfa:view'] = 'Ver configuración de MFA';
$string['tool_mfa:require'] = 'Requerir MFA para usuarios';

// Privacy
$string['privacy:metadata'] = 'El plugin MFA almacena información de configuración de factores de autenticación de los usuarios.';
$string['privacy:metadata:tool_mfa_user_factors'] = 'Factores de autenticación configurados por el usuario';
$string['privacy:metadata:tool_mfa_user_factors:userid'] = 'ID del usuario';
$string['privacy:metadata:tool_mfa_user_factors:factor'] = 'Tipo de factor';
$string['privacy:metadata:tool_mfa_user_factors:timecreated'] = 'Fecha de creación';
