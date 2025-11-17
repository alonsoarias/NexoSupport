<?php
/**
 * Strings for component 'tool_installaddon', language 'es'
 *
 * @package    tool_installaddon
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Instalador de complementos';
$string['installaddon'] = 'Instalar complemento';

// Upload form
$string['uploadplugin'] = 'Subir complemento';
$string['pluginfile'] = 'Archivo ZIP del complemento';
$string['selectfile'] = 'Seleccionar archivo';
$string['uploadpluginfile'] = 'Subir archivo del complemento';

// Plugin info
$string['pluginname_field'] = 'Nombre del complemento';
$string['plugintype'] = 'Tipo de complemento';
$string['pluginversion'] = 'Versión';
$string['pluginrequires'] = 'Requiere';
$string['pluginmaturity'] = 'Madurez';
$string['pluginrelease'] = 'Release';

// Plugin types
$string['type_auth'] = 'Método de autenticación';
$string['type_tool'] = 'Herramienta administrativa';
$string['type_theme'] = 'Tema';
$string['type_report'] = 'Reporte';
$string['type_factor'] = 'Factor MFA';
$string['type_unknown'] = 'Tipo desconocido';

// Validation
$string['validating'] = 'Validando complemento...';
$string['validationpassed'] = 'Validación exitosa';
$string['validationfailed'] = 'Validación fallida';
$string['extracting'] = 'Extrayendo archivos...';
$string['installing'] = 'Instalando complemento...';

// Installation steps
$string['step1'] = 'Paso 1: Subir archivo';
$string['step2'] = 'Paso 2: Validar complemento';
$string['step3'] = 'Paso 3: Confirmar instalación';
$string['step4'] = 'Paso 4: Instalar';

// Confirmation
$string['confirminstall'] = 'Confirmar instalación';
$string['confirminstallmsg'] = '¿Está seguro de que desea instalar este complemento?';
$string['plugindetails'] = 'Detalles del complemento';
$string['installnow'] = 'Instalar ahora';
$string['cancel'] = 'Cancelar';

// Results
$string['installsuccess'] = 'Complemento instalado exitosamente';
$string['installfailed'] = 'Error al instalar el complemento';
$string['upgraderequired'] = 'Se requiere actualización de base de datos';
$string['runupgrade'] = 'Ejecutar actualización';

// Errors
$string['errornopluginfile'] = 'No se ha seleccionado ningún archivo';
$string['errorinvalidzipfile'] = 'El archivo no es un ZIP válido';
$string['errornoversionfile'] = 'No se encontró el archivo version.php';
$string['errorinvalidplugintype'] = 'Tipo de complemento inválido';
$string['errorpluginexists'] = 'El complemento ya está instalado';
$string['errordirectorynotwritable'] = 'El directorio de plugins no tiene permisos de escritura';
$string['errorextractfailed'] = 'Error al extraer el archivo ZIP';
$string['errorinstallationfailed'] = 'Error durante la instalación';
$string['errorincompatibleversion'] = 'El complemento no es compatible con esta versión de NexoSupport';

// Permissions
$string['plugindirectory'] = 'Directorio de complementos';
$string['checkpermissions'] = 'Verificar permisos';
$string['permissionsok'] = 'Permisos correctos';
$string['permissionserror'] = 'Error de permisos';

// Security
$string['securitycheck'] = 'Verificación de seguridad';
$string['securitywarning'] = 'Advertencia de seguridad';
$string['securitymessage'] = 'Instalar complementos de fuentes no confiables puede comprometer la seguridad del sistema.';
$string['acceptrisk'] = 'Acepto el riesgo';

// Uninstall
$string['uninstall'] = 'Desinstalar';
$string['confirmuninstall'] = 'Confirmar desinstalación';
$string['uninstallsuccess'] = 'Complemento desinstalado exitosamente';
$string['uninstallfailed'] = 'Error al desinstalar el complemento';

// Capabilities
$string['tool_installaddon:install'] = 'Instalar complementos';
$string['tool_installaddon:uninstall'] = 'Desinstalar complementos';
$string['tool_installaddon:manage'] = 'Gestionar complementos';

// Privacy
$string['privacy:metadata'] = 'La herramienta de instalación de complementos no almacena datos personales.';
