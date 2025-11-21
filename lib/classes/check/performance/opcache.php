<?php
/**
 * Check for OPcache configuration.
 *
 * @package    core
 * @subpackage check
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\check\performance;

use core\check\check;
use core\check\result;

defined('INTERNAL_ACCESS') || die();

/**
 * Checks if PHP OPcache is enabled and properly configured.
 *
 * OPcache significantly improves PHP performance.
 */
class opcache extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'PHP OPcache';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        // Check if OPcache extension is loaded
        if (!extension_loaded('Zend OPcache')) {
            return new result(
                result::WARNING,
                'OPcache extension not loaded',
                'PHP OPcache is not available. Install and enable OPcache for significant ' .
                'performance improvements by caching compiled PHP code.'
            );
        }

        // Check if OPcache is enabled
        $opcacheEnabled = ini_get('opcache.enable');
        if (!$opcacheEnabled || $opcacheEnabled === '0') {
            return new result(
                result::WARNING,
                'OPcache is disabled',
                'OPcache extension is loaded but disabled. Enable it in php.ini with ' .
                '<code>opcache.enable=1</code>'
            );
        }

        // Check memory size
        $memorySize = (int)ini_get('opcache.memory_consumption');
        $issues = [];

        if ($memorySize < 128) {
            $issues[] = 'OPcache memory is only ' . $memorySize . 'MB (recommended: 128MB+)';
        }

        // Check interned strings buffer
        $internedStrings = (int)ini_get('opcache.interned_strings_buffer');
        if ($internedStrings < 8) {
            $issues[] = 'Interned strings buffer is only ' . $internedStrings . 'MB (recommended: 8MB+)';
        }

        // Check max accelerated files
        $maxFiles = (int)ini_get('opcache.max_accelerated_files');
        if ($maxFiles < 10000) {
            $issues[] = 'Max accelerated files is ' . $maxFiles . ' (recommended: 10000+)';
        }

        // Check revalidate frequency
        $revalidateFreq = (int)ini_get('opcache.revalidate_freq');
        if ($revalidateFreq < 2) {
            $issues[] = 'Revalidate frequency is ' . $revalidateFreq . 's (recommended: 2+ for production)';
        }

        if (!empty($issues)) {
            return new result(
                result::INFO,
                'OPcache enabled but could be optimized',
                'OPcache is running but the following optimizations are recommended:<ul><li>' .
                implode('</li><li>', $issues) . '</li></ul>'
            );
        }

        return new result(
            result::OK,
            'OPcache is enabled and well-configured',
            'OPcache is running with ' . $memorySize . 'MB memory and can cache up to ' .
            $maxFiles . ' files.'
        );
    }
}
