<?php
namespace core\log;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Log Store Interface
 *
 * Defines the contract for log stores.
 *
 * @package    core\log
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
interface store_interface {

    /**
     * Write a log record
     *
     * @param array $record Log record data
     * @return bool Success
     */
    public function write(array $record): bool;

    /**
     * Get store name
     *
     * @return string
     */
    public function get_name(): string;

    /**
     * Get store description
     *
     * @return string
     */
    public function get_description(): string;

    /**
     * Check if store is available
     *
     * @return bool
     */
    public function is_available(): bool;
}
