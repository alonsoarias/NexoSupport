<?php
/**
 * Spanish language strings for email factor.
 *
 * @package    factor_email
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$string['pluginname'] = 'Verificación por email';
$string['factor:description'] = 'Recibir un código de verificación por correo electrónico';
$string['factor:setup'] = 'Los códigos de verificación se enviarán a tu dirección de correo electrónico registrada.';

// Formulario
$string['verificationcode'] = 'Código de verificación';
$string['email:checkyourinbox'] = 'Revisa tu bandeja de entrada para obtener el código de verificación. Puede tardar unos momentos en llegar.';

// Contenido del email
$string['email:subject'] = '{$a}: Tu código de verificación';
$string['email:greeting'] = 'Hola {$a},';
$string['email:message'] = 'Alguien está intentando iniciar sesión en tu cuenta en {$a->sitename}. Si fuiste tú, usa el código de abajo para verificar tu identidad.';
$string['email:validity'] = 'Este código es válido por {$a} y solo puede usarse una vez.';
$string['email:loginlink'] = 'O haz clic en este enlace para iniciar sesión automáticamente: {$a}';
$string['email:revokelink'] = 'Si no fuiste tú, haz clic aquí para bloquear este intento de inicio de sesión: {$a}';
$string['email:loginbutton'] = 'Iniciar sesión automáticamente';
$string['email:blockbutton'] = 'Bloquear este inicio de sesión';
$string['email:notme'] = '¿No fuiste tú?';
$string['email:ipinfo'] = 'Información de seguridad';
$string['email:originatingip'] = 'Dirección IP: {$a}';
$string['email:geoinfo'] = 'Ubicación:';
$string['email:uadescription'] = 'Dispositivo/Navegador:';

// Errores
$string['error:emptycode'] = 'Por favor ingresa el código de verificación';
$string['error:wrongverification'] = 'Código de verificación inválido o expirado';
$string['error:badcode'] = 'Enlace de verificación inválido';

// Revocación
$string['email:revokesuccess'] = 'Intento de inicio de sesión bloqueado exitosamente para {$a}. Todas las sesiones han sido terminadas.';
$string['email:revoketitle'] = 'Bloquear inicio de sesión no autorizado';
$string['email:revokeconfirm'] = '¿Estás seguro de que quieres bloquear este intento de inicio de sesión? Esto terminará todas las sesiones activas de esta cuenta.';

// Configuración
$string['settings:duration'] = 'Duración de validez del código';
$string['settings:duration_help'] = 'Cuánto tiempo permanece válido el código de verificación';
$string['settings:suspend'] = 'Suspender cuenta al bloquear';
$string['settings:suspend_help'] = 'Si está habilitado, la cuenta del usuario se suspenderá cuando reporte un intento de inicio de sesión no autorizado';
