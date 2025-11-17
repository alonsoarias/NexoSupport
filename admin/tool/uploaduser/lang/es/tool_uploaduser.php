<?php
/**
 * Strings for component 'tool_uploaduser', language 'es'
 *
 * @package    tool_uploaduser
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Carga masiva de usuarios';
$string['uploadusers'] = 'Cargar usuarios desde archivo';

// Upload form
$string['csvfile'] = 'Archivo CSV';
$string['csvdelimiter'] = 'Delimitador CSV';
$string['csvencoding'] = 'Codificación';
$string['uploadfile'] = 'Subir archivo';
$string['selectfile'] = 'Seleccionar archivo';

// CSV format
$string['csvformat'] = 'Formato del archivo CSV';
$string['csvformatdesc'] = 'El archivo CSV debe contener las siguientes columnas: username, email, firstname, lastname, password (opcional)';
$string['csvexample'] = 'Ejemplo de CSV';
$string['requiredfields'] = 'Campos requeridos';
$string['optionalfields'] = 'Campos opcionales';

// Column headers
$string['col_username'] = 'username';
$string['col_email'] = 'email';
$string['col_firstname'] = 'firstname';
$string['col_lastname'] = 'lastname';
$string['col_password'] = 'password';
$string['col_role'] = 'role';

// Options
$string['createpasswords'] = 'Generar contraseñas automáticamente';
$string['sendwelcomeemail'] = 'Enviar correo de bienvenida';
$string['updateexisting'] = 'Actualizar usuarios existentes';
$string['skipexisting'] = 'Omitir usuarios existentes';

// Preview
$string['preview'] = 'Vista previa';
$string['previewrows'] = 'Previsualizar {$a} filas';
$string['rownum'] = 'Fila {$a}';
$string['validrows'] = '{$a} fila(s) válida(s)';
$string['invalidrows'] = '{$a} fila(s) inválida(s)';

// Results
$string['uploadresults'] = 'Resultados de la carga';
$string['userscreated'] = 'Usuarios creados: {$a}';
$string['usersupdated'] = 'Usuarios actualizados: {$a}';
$string['usersskipped'] = 'Usuarios omitidos: {$a}';
$string['userserrors'] = 'Errores: {$a}';
$string['uploadcomplete'] = 'Carga completada';

// Errors
$string['csvempty'] = 'El archivo CSV está vacío';
$string['invalidcsvfile'] = 'Archivo CSV inválido';
$string['missingrequiredfield'] = 'Falta campo requerido: {$a}';
$string['invalidrow'] = 'Fila inválida en línea {$a}';
$string['duplicateusername'] = 'Nombre de usuario duplicado: {$a}';
$string['duplicateemail'] = 'Correo electrónico duplicado: {$a}';
$string['invalidusername'] = 'Nombre de usuario inválido: {$a}';
$string['invalidemail'] = 'Correo electrónico inválido: {$a}';
$string['filetoobig'] = 'El archivo es demasiado grande. Tamaño máximo: {$a}';

// Download template
$string['downloadtemplate'] = 'Descargar plantilla CSV';
$string['templatefile'] = 'Archivo de plantilla';

// Help
$string['uploadusers_help'] = 'Cargar múltiples usuarios desde un archivo CSV. El archivo debe contener una fila de encabezados con los nombres de las columnas.';
$string['csvdelimiter_help'] = 'Carácter delimitador del archivo CSV. Usualmente coma (,) o punto y coma (;).';
$string['createpasswords_help'] = 'Si está marcado, se generarán contraseñas aleatorias para los usuarios que no tengan contraseña en el CSV.';
$string['updateexisting_help'] = 'Si está marcado, los usuarios existentes serán actualizados con la nueva información.';

// Capabilities
$string['tool_uploaduser:upload'] = 'Cargar usuarios desde CSV';
$string['tool_uploaduser:manage'] = 'Gestionar carga de usuarios';

// Privacy
$string['privacy:metadata'] = 'La herramienta de carga de usuarios no almacena datos personales.';
