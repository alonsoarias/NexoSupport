<?php
/**
 * Strings for component 'tool_logviewer', language 'es'
 *
 * @package    tool_logviewer
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Visor de registros';
$string['logviewer'] = 'Visor de logs';
$string['viewlogs'] = 'Ver registros';

// Log types
$string['auditlogs'] = 'Registros de auditoría';
$string['errorlogs'] = 'Registros de errores';
$string['systemlogs'] = 'Registros del sistema';
$string['accesslogs'] = 'Registros de acceso';
$string['debuglogs'] = 'Registros de depuración';

// Filters
$string['filters'] = 'Filtros';
$string['filterby'] = 'Filtrar por';
$string['logtype'] = 'Tipo de registro';
$string['loglevel'] = 'Nivel de registro';
$string['daterange'] = 'Rango de fechas';
$string['datefrom'] = 'Desde';
$string['dateto'] = 'Hasta';
$string['user'] = 'Usuario';
$string['action'] = 'Acción';
$string['module'] = 'Módulo';
$string['ipaddress'] = 'Dirección IP';

// Log levels
$string['emergency'] = 'Emergencia';
$string['alert'] = 'Alerta';
$string['critical'] = 'Crítico';
$string['error'] = 'Error';
$string['warning'] = 'Advertencia';
$string['notice'] = 'Aviso';
$string['info'] = 'Información';
$string['debug'] = 'Depuración';

// Display
$string['showlogs'] = 'Mostrar registros';
$string['nologs'] = 'No se encontraron registros';
$string['totalentries'] = 'Total de entradas: {$a}';
$string['showing'] = 'Mostrando {$a->from} - {$a->to} de {$a->total}';
$string['perpage'] = 'Por página';

// Table columns
$string['timestamp'] = 'Fecha y hora';
$string['level'] = 'Nivel';
$string['message'] = 'Mensaje';
$string['context'] = 'Contexto';
$string['details'] = 'Detalles';
$string['stacktrace'] = 'Stack trace';

// Actions
$string['viewdetails'] = 'Ver detalles';
$string['exportlogs'] = 'Exportar registros';
$string['clearlogs'] = 'Limpiar registros';
$string['refreshlogs'] = 'Actualizar registros';
$string['downloadlogs'] = 'Descargar registros';

// Export
$string['exportformat'] = 'Formato de exportación';
$string['exportcsv'] = 'Exportar como CSV';
$string['exportjson'] = 'Exportar como JSON';
$string['exportxml'] = 'Exportar como XML';
$string['exportall'] = 'Exportar todos';
$string['exportfiltered'] = 'Exportar filtrados';

// Settings
$string['logsettings'] = 'Configuración de registros';
$string['logretention'] = 'Retención de logs';
$string['logretention_desc'] = 'Número de días para mantener los registros';
$string['maxlogsize'] = 'Tamaño máximo de log';
$string['maxlogsize_desc'] = 'Tamaño máximo del archivo de log en MB';
$string['logenabled'] = 'Habilitar logging';
$string['logenabled_desc'] = 'Habilitar el registro de eventos del sistema';

// Maintenance
$string['maintenance'] = 'Mantenimiento';
$string['archivelogs'] = 'Archivar registros antiguos';
$string['archivelogs_desc'] = 'Archivar registros más antiguos que el período de retención';
$string['deletelogs'] = 'Eliminar registros antiguos';
$string['deletelogs_desc'] = 'Eliminar permanentemente registros archivados';
$string['compresslogs'] = 'Comprimir registros';
$string['compresslogs_desc'] = 'Comprimir archivos de registro antiguos';

// Confirmations
$string['confirmclear'] = 'Confirmar limpieza';
$string['confirmclearmessage'] = '¿Está seguro de que desea limpiar los registros? Esta acción no se puede deshacer.';
$string['confirmdelete'] = 'Confirmar eliminación';
$string['confirmdeletemessage'] = '¿Está seguro de que desea eliminar estos registros? Esta acción no se puede deshacer.';

// Results
$string['logscleared'] = 'Registros limpiados exitosamente';
$string['logsdeleted'] = '{$a} registro(s) eliminado(s)';
$string['logsarchived'] = '{$a} registro(s) archivado(s)';
$string['logsexported'] = 'Registros exportados exitosamente';

// Errors
$string['errorloadinglogs'] = 'Error al cargar los registros';
$string['errorexportinglogs'] = 'Error al exportar los registros';
$string['errorclearinglogs'] = 'Error al limpiar los registros';
$string['errorinvalidfilter'] = 'Filtro inválido';
$string['errornopermission'] = 'No tiene permiso para ver los registros';

// Real-time
$string['realtime'] = 'Tiempo real';
$string['autorefresh'] = 'Actualización automática';
$string['autorefreshinterval'] = 'Intervalo de actualización';
$string['seconds'] = 'segundos';
$string['livelogs'] = 'Registros en vivo';

// Statistics
$string['statistics'] = 'Estadísticas';
$string['logstatistics'] = 'Estadísticas de registros';
$string['bytype'] = 'Por tipo';
$string['bylevel'] = 'Por nivel';
$string['byuser'] = 'Por usuario';
$string['byaction'] = 'Por acción';
$string['topusers'] = 'Usuarios más activos';
$string['topactions'] = 'Acciones más frecuentes';

// Capabilities
$string['tool_logviewer:view'] = 'Ver registros';
$string['tool_logviewer:export'] = 'Exportar registros';
$string['tool_logviewer:delete'] = 'Eliminar registros';
$string['tool_logviewer:manage'] = 'Gestionar configuración de registros';

// Privacy
$string['privacy:metadata'] = 'El visor de registros no almacena datos personales adicionales.';
