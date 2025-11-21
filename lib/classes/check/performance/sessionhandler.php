<?php
/**
 * Check for session handler configuration.
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
 * Checks session handler configuration for performance.
 */
class sessionhandler extends check {

    protected string $component = 'core';

    /**
     * Get the check name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'Session Handler';
    }

    /**
     * Execute the check.
     *
     * @return result
     */
    public function get_result(): result {
        $handler = ini_get('session.save_handler');

        $details = '<p>Current session handler: <strong>' . htmlspecialchars($handler) . '</strong></p>';

        switch ($handler) {
            case 'files':
                $savePath = ini_get('session.save_path');
                $details .= '<p>Sessions are stored in files at: ' . htmlspecialchars($savePath ?: 'default path') . '</p>';
                $details .= '<p>File-based sessions work well for single-server setups. ' .
                    'For multi-server environments or high traffic, consider using Redis or Memcached.</p>';

                return new result(
                    result::INFO,
                    'Using file-based sessions',
                    $details
                );

            case 'redis':
                $details .= '<p>Redis provides fast, scalable session storage ideal for high-traffic sites.</p>';
                return new result(
                    result::OK,
                    'Using Redis for sessions',
                    $details
                );

            case 'memcached':
                $details .= '<p>Memcached provides fast session storage.</p>';
                return new result(
                    result::OK,
                    'Using Memcached for sessions',
                    $details
                );

            case 'database':
                $details .= '<p>Database sessions can be slower than file or memory-based alternatives. ' .
                    'Consider using Redis or Memcached for better performance.</p>';
                return new result(
                    result::INFO,
                    'Using database for sessions',
                    $details
                );

            default:
                return new result(
                    result::INFO,
                    'Session handler: ' . htmlspecialchars($handler),
                    $details
                );
        }
    }

    /**
     * Get action link.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \nexo_url('/admin/settings.php', ['section' => 'sessionhandling']),
            'Session settings'
        );
    }
}
