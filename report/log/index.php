<?php
/**
 * Audit log report main page
 *
 * @package    report_log
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Verificar permisos de administrador
admin_externalpage_setup('reportlog', '', null, '', ['pagelayout' => 'report']);

$page     = optional_param('page', 0, PARAM_INT);
$perpage  = optional_param('perpage', 50, PARAM_INT);
$userid   = optional_param('userid', 0, PARAM_INT);
$action   = optional_param('action', '', PARAM_ALPHANUMEXT);
$datefrom = optional_param('datefrom', 0, PARAM_INT);
$dateto   = optional_param('dateto', 0, PARAM_INT);

// Configuración de la página
$PAGE->set_url(new moodle_url('/report/log/index.php'));
$PAGE->set_title(get_string('pluginname', 'report_log'));
$PAGE->set_heading(get_string('pluginname', 'report_log'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('auditlogs', 'report_log'));

// Mostrar filtros
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out(false)]);
echo html_writer::start_div('row');

// Filtro por usuario
echo html_writer::start_div('col-md-3');
echo html_writer::label(get_string('user'), 'userid');
echo html_writer::empty_tag('input', [
    'type' => 'number',
    'name' => 'userid',
    'id' => 'userid',
    'value' => $userid,
    'class' => 'form-control',
    'placeholder' => get_string('allusers', 'report_log'),
]);
echo html_writer::end_div();

// Filtro por acción
echo html_writer::start_div('col-md-3');
echo html_writer::label(get_string('action'), 'action');
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'action',
    'id' => 'action',
    'value' => $action,
    'class' => 'form-control',
    'placeholder' => get_string('allactions', 'report_log'),
]);
echo html_writer::end_div();

// Filtro de fecha desde
echo html_writer::start_div('col-md-2');
echo html_writer::label(get_string('from'), 'datefrom');
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'datefrom',
    'id' => 'datefrom',
    'value' => $datefrom ? date('Y-m-d', $datefrom) : '',
    'class' => 'form-control',
]);
echo html_writer::end_div();

// Filtro de fecha hasta
echo html_writer::start_div('col-md-2');
echo html_writer::label(get_string('to'), 'dateto');
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'dateto',
    'id' => 'dateto',
    'value' => $dateto ? date('Y-m-d', $dateto) : '',
    'class' => 'form-control',
]);
echo html_writer::end_div();

// Botón de filtrar
echo html_writer::start_div('col-md-2');
echo html_writer::empty_tag('br');
echo html_writer::tag('button', get_string('filter', 'report_log'), [
    'type' => 'submit',
    'class' => 'btn btn-primary',
]);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_tag('form');

echo html_writer::empty_tag('hr');

// Construir filtros
$filters = [];
if ($userid > 0) {
    $filters['user_id'] = $userid;
}
if (!empty($action)) {
    $filters['action'] = $action;
}
if ($datefrom > 0) {
    $filters['date_from'] = $datefrom;
}
if ($dateto > 0) {
    $filters['date_to'] = $dateto;
}

// Obtener los logs
$entries = report_log_get_entries($filters, $page, $perpage);
$totalcount = report_log_count_entries($filters);

// Mostrar tabla de logs
if (empty($entries)) {
    echo html_writer::tag('p', get_string('nologs', 'report_log'), ['class' => 'alert alert-info']);
} else {
    $table = new html_table();
    $table->head = [
        get_string('id', 'report_log'),
        get_string('user'),
        get_string('action', 'report_log'),
        get_string('ipaddress', 'report_log'),
        get_string('details', 'report_log'),
        get_string('date', 'report_log'),
    ];
    $table->attributes['class'] = 'generaltable';

    foreach ($entries as $entry) {
        $row = [];
        $row[] = $entry->id;
        $row[] = $entry->username ?? get_string('unknown', 'report_log');
        $row[] = html_writer::tag('code', $entry->action);
        $row[] = $entry->ip_address ?? 'N/A';
        $row[] = !empty($entry->details) ? s(substr($entry->details, 0, 100)) : '';
        $row[] = userdate($entry->created_at, get_string('strftimedatetime'));

        $table->data[] = $row;
    }

    echo html_writer::table($table);

    // Paginación
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $PAGE->url);
}

// Botón de exportar
echo html_writer::empty_tag('hr');
$exporturl = new moodle_url('/report/log/export.php', $filters);
echo html_writer::link($exporturl, get_string('exportcsv', 'report_log'), [
    'class' => 'btn btn-secondary',
]);

echo $OUTPUT->footer();
