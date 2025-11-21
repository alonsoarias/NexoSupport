<?php
/**
 * Result class for system checks.
 *
 * @package    core
 * @subpackage check
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace core\check;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Represents the result of a system check.
 *
 * Contains status, summary, and detailed information about a check's outcome.
 */
class result {

    /** @var string Check does not apply to this system */
    const NA = 'na';

    /** @var string Check passed successfully */
    const OK = 'ok';

    /** @var string Informational status */
    const INFO = 'info';

    /** @var string Status could not be determined */
    const UNKNOWN = 'unknown';

    /** @var string Check found a potential issue */
    const WARNING = 'warning';

    /** @var string Check found an error */
    const ERROR = 'error';

    /** @var string Check found a critical issue */
    const CRITICAL = 'critical';

    /** @var string The status of the check */
    protected string $status;

    /** @var string Brief summary of the result */
    protected string $summary;

    /** @var string Detailed information about the result */
    protected string $details;

    /**
     * Create a new result.
     *
     * @param string $status One of the status constants
     * @param string $summary Brief summary of the result
     * @param string $details Detailed information (optional)
     */
    public function __construct(string $status, string $summary, string $details = '') {
        $this->status = $status;
        $this->summary = $summary;
        $this->details = $details;
    }

    /**
     * Get the status of this result.
     *
     * @return string The status constant
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Get the summary of this result.
     *
     * @return string The brief summary
     */
    public function get_summary(): string {
        return $this->summary;
    }

    /**
     * Get the detailed information.
     *
     * @return string The details or empty string
     */
    public function get_details(): string {
        return $this->details;
    }

    /**
     * Get the CSS class for this status.
     *
     * @return string CSS class name for styling
     */
    public function get_status_class(): string {
        $classes = [
            self::NA => 'secondary',
            self::OK => 'success',
            self::INFO => 'info',
            self::UNKNOWN => 'secondary',
            self::WARNING => 'warning',
            self::ERROR => 'danger',
            self::CRITICAL => 'danger',
        ];

        return $classes[$this->status] ?? 'secondary';
    }

    /**
     * Get a human-readable label for this status.
     *
     * @return string The status label
     */
    public function get_status_label(): string {
        $labels = [
            self::NA => 'N/A',
            self::OK => 'OK',
            self::INFO => 'Info',
            self::UNKNOWN => 'Unknown',
            self::WARNING => 'Warning',
            self::ERROR => 'Error',
            self::CRITICAL => 'Critical',
        ];

        return $labels[$this->status] ?? 'Unknown';
    }

    /**
     * Get the icon name for this status.
     *
     * @return string Icon identifier
     */
    public function get_status_icon(): string {
        $icons = [
            self::NA => 'minus-circle',
            self::OK => 'check-circle',
            self::INFO => 'info-circle',
            self::UNKNOWN => 'question-circle',
            self::WARNING => 'exclamation-triangle',
            self::ERROR => 'times-circle',
            self::CRITICAL => 'exclamation-circle',
        ];

        return $icons[$this->status] ?? 'question-circle';
    }

    /**
     * Check if this result indicates a problem.
     *
     * @return bool True if warning, error, or critical
     */
    public function is_problem(): bool {
        return in_array($this->status, [self::WARNING, self::ERROR, self::CRITICAL]);
    }

    /**
     * Get the severity level for sorting.
     *
     * @return int Higher number = more severe
     */
    public function get_severity(): int {
        $severity = [
            self::NA => 0,
            self::OK => 1,
            self::INFO => 2,
            self::UNKNOWN => 3,
            self::WARNING => 4,
            self::ERROR => 5,
            self::CRITICAL => 6,
        ];

        return $severity[$this->status] ?? 0;
    }
}
