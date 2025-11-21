<?php
/**
 * Spanish language strings for MFA tool.
 *
 * @package    tool_mfa
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$string['pluginname'] = 'Autenticación multifactor';
$string['mfa'] = 'MFA';

// Página de autenticación
$string['auth:title'] = 'Verifica tu identidad';
$string['auth:subtitle'] = 'Completa la verificación para continuar';
$string['auth:currentfactor'] = 'Método de verificación: {$a}';
$string['auth:submit'] = 'Verificar';
$string['auth:cancel'] = 'Cancelar';
$string['auth:logout'] = 'Cerrar sesión';

// Estados de factor
$string['factor:pass'] = 'Verificado';
$string['factor:fail'] = 'Fallido';
$string['factor:neutral'] = 'Pendiente';
$string['factor:locked'] = 'Bloqueado';
$string['factor:unknown'] = 'Desconocido';

// Fallback
$string['factor:fallback'] = 'Sin método de verificación';
$string['factor:fallback_message'] = 'No hay ningún método de verificación disponible para tu cuenta. Por favor contacta a un administrador.';

// Configuración
$string['settings:enabled'] = 'Habilitar MFA';
$string['settings:enabled_help'] = 'Habilitar autenticación multifactor para el sitio';
$string['settings:enablefactor'] = 'Habilitar factor';
$string['settings:enablefactor_help'] = 'Habilitar este método de verificación';
$string['settings:weight'] = 'Peso del factor';
$string['settings:weight_help'] = 'El peso determina cuánto contribuye este factor a completar MFA. Se requiere un total de 100 puntos para pasar.';
$string['settings:lockout'] = 'Umbral de bloqueo';
$string['settings:lockout_help'] = 'Número de intentos fallidos antes de bloquear el factor';
$string['settings:exemptadmins'] = 'Eximir administradores';
$string['settings:exemptadmins_help'] = 'Si está habilitado, los administradores del sitio no necesitarán completar MFA';
$string['settings:redir_exclusions'] = 'Exclusiones de URL';
$string['settings:redir_exclusions_help'] = 'URLs que no deben activar MFA (una por línea)';

// Errores
$string['error:mloopdetected'] = 'Se detectó un bucle de redirección MFA. Por favor intenta iniciar sesión de nuevo.';
$string['error:mfafailed'] = 'La autenticación multifactor falló. Por favor intenta de nuevo.';
$string['error:locked'] = 'Tu cuenta ha sido bloqueada debido a demasiados intentos fallidos. Por favor contacta a un administrador.';

// Eventos
$string['event:userpassed'] = 'Usuario pasó MFA';
$string['event:userfailed'] = 'Usuario falló MFA';
$string['event:factorsetup'] = 'Factor configurado';
$string['event:factorrevoked'] = 'Factor revocado';

// Configuración inicial
$string['setup:title'] = 'Configurar verificación';
$string['setup:intro'] = 'Tu cuenta requiere verificación adicional. Por favor configura uno de los siguientes métodos:';
$string['setup:complete'] = 'Configuración de verificación completa';

// Estado
$string['status:mfaenabled'] = 'MFA está habilitado';
$string['status:mfadisabled'] = 'MFA está deshabilitado';
$string['status:factorsenabled'] = '{$a} método(s) de verificación habilitado(s)';
