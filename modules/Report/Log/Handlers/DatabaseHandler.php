<?php
/**
 * Handler de Monolog para escribir logs en base de datos
 * @package Report\Log\Handlers
 * @author ISER Desarrollo
 * @license Propietario
 */

namespace ISER\Modules\Report\Log\Handlers;

use ISER\Core\Database\Database;
use ISER\Modules\Report\Log\ReportLog;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class DatabaseHandler extends AbstractProcessingHandler
{
    private ReportLog $reportLog;
    private string $component;

    public function __construct(ReportLog $reportLog, string $component = 'system', Level $level = Level::Debug)
    {
        parent::__construct($level);
        $this->reportLog = $reportLog;
        $this->component = $component;
    }

    /**
     * Escribir log en base de datos
     */
    protected function write(LogRecord $record): void
    {
        $severity = $this->mapLevelToSeverity($record->level);

        $this->reportLog->log([
            'eventname' => $record->channel . '.' . strtolower($record->level->name),
            'component' => $this->component,
            'action' => $record->context['action'] ?? 'log',
            'crud' => $record->context['crud'] ?? ReportLog::CRUD_READ,
            'userid' => $record->context['userid'] ?? ($_SESSION['user_id'] ?? 0),
            'objecttable' => $record->context['objecttable'] ?? null,
            'objectid' => $record->context['objectid'] ?? null,
            'description' => $record->message,
            'context' => $this->filterContext($record->context),
            'severity' => $severity
        ]);
    }

    /**
     * Mapear nivel de Monolog a severidad de sistema
     */
    private function mapLevelToSeverity(Level $level): int
    {
        return match($level) {
            Level::Debug, Level::Info, Level::Notice => ReportLog::SEVERITY_INFO,
            Level::Warning => ReportLog::SEVERITY_WARNING,
            Level::Error => ReportLog::SEVERITY_ERROR,
            Level::Critical, Level::Alert, Level::Emergency => ReportLog::SEVERITY_CRITICAL,
        };
    }

    /**
     * Filtrar contexto para almacenamiento
     */
    private function filterContext(array $context): array
    {
        // Remover claves que ya se almacenan en columnas separadas
        $filtered = $context;
        unset($filtered['action'], $filtered['crud'], $filtered['userid'],
              $filtered['objecttable'], $filtered['objectid']);

        // Limitar contexto a datos serializables
        array_walk_recursive($filtered, function(&$value) {
            if (is_object($value)) {
                $value = method_exists($value, '__toString')
                    ? (string)$value
                    : get_class($value);
            } elseif (is_resource($value)) {
                $value = 'resource';
            }
        });

        return $filtered;
    }
}
