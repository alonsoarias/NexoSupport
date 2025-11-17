<?php
/**
 * Strings for component 'tool_logviewer', language 'en'
 *
 * @package    tool_logviewer
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Log viewer';
$string['logviewer'] = 'Log viewer';
$string['viewlogs'] = 'View logs';

// Log types
$string['auditlogs'] = 'Audit logs';
$string['errorlogs'] = 'Error logs';
$string['systemlogs'] = 'System logs';
$string['accesslogs'] = 'Access logs';
$string['debuglogs'] = 'Debug logs';

// Filters
$string['filters'] = 'Filters';
$string['filterby'] = 'Filter by';
$string['logtype'] = 'Log type';
$string['loglevel'] = 'Log level';
$string['daterange'] = 'Date range';
$string['datefrom'] = 'From';
$string['dateto'] = 'To';
$string['user'] = 'User';
$string['action'] = 'Action';
$string['module'] = 'Module';
$string['ipaddress'] = 'IP address';

// Log levels
$string['emergency'] = 'Emergency';
$string['alert'] = 'Alert';
$string['critical'] = 'Critical';
$string['error'] = 'Error';
$string['warning'] = 'Warning';
$string['notice'] = 'Notice';
$string['info'] = 'Information';
$string['debug'] = 'Debug';

// Display
$string['showlogs'] = 'Show logs';
$string['nologs'] = 'No logs found';
$string['totalentries'] = 'Total entries: {$a}';
$string['showing'] = 'Showing {$a->from} - {$a->to} of {$a->total}';
$string['perpage'] = 'Per page';

// Table columns
$string['timestamp'] = 'Date and time';
$string['level'] = 'Level';
$string['message'] = 'Message';
$string['context'] = 'Context';
$string['details'] = 'Details';
$string['stacktrace'] = 'Stack trace';

// Actions
$string['viewdetails'] = 'View details';
$string['exportlogs'] = 'Export logs';
$string['clearlogs'] = 'Clear logs';
$string['refreshlogs'] = 'Refresh logs';
$string['downloadlogs'] = 'Download logs';

// Export
$string['exportformat'] = 'Export format';
$string['exportcsv'] = 'Export as CSV';
$string['exportjson'] = 'Export as JSON';
$string['exportxml'] = 'Export as XML';
$string['exportall'] = 'Export all';
$string['exportfiltered'] = 'Export filtered';

// Settings
$string['logsettings'] = 'Log settings';
$string['logretention'] = 'Log retention';
$string['logretention_desc'] = 'Number of days to keep logs';
$string['maxlogsize'] = 'Maximum log size';
$string['maxlogsize_desc'] = 'Maximum log file size in MB';
$string['logenabled'] = 'Enable logging';
$string['logenabled_desc'] = 'Enable system event logging';

// Maintenance
$string['maintenance'] = 'Maintenance';
$string['archivelogs'] = 'Archive old logs';
$string['archivelogs_desc'] = 'Archive logs older than the retention period';
$string['deletelogs'] = 'Delete old logs';
$string['deletelogs_desc'] = 'Permanently delete archived logs';
$string['compresslogs'] = 'Compress logs';
$string['compresslogs_desc'] = 'Compress old log files';

// Confirmations
$string['confirmclear'] = 'Confirm clear';
$string['confirmclearmessage'] = 'Are you sure you want to clear the logs? This action cannot be undone.';
$string['confirmdelete'] = 'Confirm deletion';
$string['confirmdeletemessage'] = 'Are you sure you want to delete these logs? This action cannot be undone.';

// Results
$string['logscleared'] = 'Logs cleared successfully';
$string['logsdeleted'] = '{$a} log(s) deleted';
$string['logsarchived'] = '{$a} log(s) archived';
$string['logsexported'] = 'Logs exported successfully';

// Errors
$string['errorloadinglogs'] = 'Error loading logs';
$string['errorexportinglogs'] = 'Error exporting logs';
$string['errorclearinglogs'] = 'Error clearing logs';
$string['errorinvalidfilter'] = 'Invalid filter';
$string['errornopermission'] = 'You do not have permission to view logs';

// Real-time
$string['realtime'] = 'Real-time';
$string['autorefresh'] = 'Auto refresh';
$string['autorefreshinterval'] = 'Refresh interval';
$string['seconds'] = 'seconds';
$string['livelogs'] = 'Live logs';

// Statistics
$string['statistics'] = 'Statistics';
$string['logstatistics'] = 'Log statistics';
$string['bytype'] = 'By type';
$string['bylevel'] = 'By level';
$string['byuser'] = 'By user';
$string['byaction'] = 'By action';
$string['topusers'] = 'Most active users';
$string['topactions'] = 'Most frequent actions';

// Capabilities
$string['tool_logviewer:view'] = 'View logs';
$string['tool_logviewer:export'] = 'Export logs';
$string['tool_logviewer:delete'] = 'Delete logs';
$string['tool_logviewer:manage'] = 'Manage log settings';

// Privacy
$string['privacy:metadata'] = 'The log viewer does not store additional personal data.';
