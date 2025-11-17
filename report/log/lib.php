<?php
/**
 * NexoSupport - Log Report Plugin - Library
 *
 * @package    report_log
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

use ISER\Report\Log\LogRepository;
use ISER\Core\Database\Database;
use ISER\Core\I18n\Translator;

/**
 * Get plugin name
 *
 * @return string
 * @deprecated Use Translator::get_string('pluginname', 'report_log') instead
 */
function report_log_get_name(): string
{
    $translator = Translator::getInstance();
    return $translator->get_string('pluginname', 'report_log');
}

/**
 * Get log entries
 *
 * @param array $filters Filters
 * @param int $page Page number
 * @param int $perpage Items per page
 * @return array Log entries
 * @deprecated Use LogRepository::get_entries() instead
 */
function report_log_get_entries(array $filters = [], int $page = 0, int $perpage = 50): array
{
    $db = Database::getInstance();
    $repository = new LogRepository($db);
    return $repository->get_entries($filters, $page, $perpage);
}

/**
 * Count log entries matching filters
 *
 * @param array $filters Filters
 * @return int Total count
 * @deprecated Use LogRepository::count_entries() instead
 */
function report_log_count_entries(array $filters = []): int
{
    $db = Database::getInstance();
    $repository = new LogRepository($db);
    return $repository->count_entries($filters);
}

/**
 * Export logs to CSV
 *
 * @param array $filters Filters
 * @return string CSV content
 * @deprecated Use LogController::export() instead
 */
function report_log_export_csv(array $filters = []): string
{
    $db = Database::getInstance();
    $repository = new LogRepository($db);
    $entries = $repository->export_entries($filters);

    $csv = "ID,Usuario,Acción,IP,Fecha\n";

    foreach ($entries as $entry) {
        $csv .= sprintf(
            "%d,%s,%s,%s,%s\n",
            $entry->id,
            $entry->username ?? 'N/A',
            $entry->action,
            $entry->ip_address ?? 'N/A',
            date('Y-m-d H:i:s', $entry->created_at)
        );
    }

    return $csv;
}
