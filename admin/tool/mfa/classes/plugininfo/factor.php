<?php
/**
 * MFA factor plugininfo - manages MFA factor plugins.
 *
 * @package    tool_mfa
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace tool_mfa\plugininfo;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Factor plugin info and factory class.
 */
class factor {

    /** @var string Factor state: Unknown/not verified yet */
    const STATE_UNKNOWN = 'unknown';

    /** @var string Factor state: Verification passed */
    const STATE_PASS = 'pass';

    /** @var string Factor state: Verification failed */
    const STATE_FAIL = 'fail';

    /** @var string Factor state: Neutral/not applicable */
    const STATE_NEUTRAL = 'neutral';

    /** @var string Factor state: Locked due to failed attempts */
    const STATE_LOCKED = 'locked';

    /**
     * Get all available factors.
     *
     * @return array Array of factor objects
     */
    public static function get_factors(): array {
        global $CFG;

        $factors = [];
        $factordir = $CFG->dirroot . '/admin/tool/mfa/factor';

        if (!is_dir($factordir)) {
            return $factors;
        }

        $dirs = scandir($factordir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $classpath = $factordir . '/' . $dir . '/classes/factor.php';
            if (file_exists($classpath)) {
                $classname = '\\factor_' . $dir . '\\factor';

                // Include the file
                require_once($classpath);

                if (class_exists($classname)) {
                    $factors[] = new $classname($dir);
                }
            }
        }

        return self::sort_factors_by_order($factors);
    }

    /**
     * Get all enabled factors.
     *
     * @return array Array of enabled factor objects
     */
    public static function get_enabled_factors(): array {
        $factors = self::get_factors();
        $enabled = [];

        foreach ($factors as $factor) {
            if ($factor->is_enabled()) {
                $enabled[] = $factor;
            }
        }

        return $enabled;
    }

    /**
     * Get active factors for the current user.
     *
     * @return array Array of active factor objects
     */
    public static function get_active_user_factor_types(): array {
        $factors = self::get_enabled_factors();
        $active = [];

        foreach ($factors as $factor) {
            if ($factor->is_active()) {
                $active[] = $factor;
            }
        }

        return $active;
    }

    /**
     * Get the next factor requiring user input.
     *
     * @return \tool_mfa\local\factor\object_factor|null
     */
    public static function get_next_user_login_factor() {
        $factors = self::get_active_user_factor_types();

        foreach ($factors as $factor) {
            // Skip factors without user input
            if (!$factor->has_input()) {
                continue;
            }

            // Return first factor not yet verified
            if ($factor->get_state() == self::STATE_UNKNOWN) {
                return $factor;
            }
        }

        // Return fallback factor if no input factors available
        return new \tool_mfa\local\factor\fallback();
    }

    /**
     * Get a specific factor by name.
     *
     * @param string $name Factor name
     * @return \tool_mfa\local\factor\object_factor|null
     */
    public static function get_factor(string $name) {
        $factors = self::get_factors();

        foreach ($factors as $factor) {
            if ($factor->get_name() === $name) {
                return $factor;
            }
        }

        return null;
    }

    /**
     * Sort factors by configured order.
     *
     * @param array $factors Array of factors
     * @return array Sorted array
     */
    private static function sort_factors_by_order(array $factors): array {
        usort($factors, function($a, $b) {
            $ordera = get_config('factor_' . $a->get_name(), 'order') ?: 100;
            $orderb = get_config('factor_' . $b->get_name(), 'order') ?: 100;
            return $ordera - $orderb;
        });

        return $factors;
    }

    /**
     * Check if any factor has input (requires user action).
     *
     * @return bool True if at least one factor requires input
     */
    public static function has_input_factors(): bool {
        $factors = self::get_enabled_factors();

        foreach ($factors as $factor) {
            if ($factor->has_input()) {
                return true;
            }
        }

        return false;
    }
}
