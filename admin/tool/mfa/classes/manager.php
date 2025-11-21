<?php
/**
 * MFA Manager class - Core orchestration of MFA system.
 *
 * @package    tool_mfa
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_mfa;

defined('NEXOSUPPORT_INTERNAL') || die();

use tool_mfa\plugininfo\factor;

/**
 * MFA Manager - handles all MFA logic and flow control.
 */
class manager {

    /** @var int Redirect to MFA auth page */
    const REDIRECT = 1;

    /** @var int Do not redirect */
    const NO_REDIRECT = 0;

    /** @var int Redirect exception (loop detected) */
    const REDIRECT_EXCEPTION = -1;

    /** @var int Threshold for detecting redirect loops */
    const REDIR_LOOP_THRESHOLD = 5;

    /**
     * Main entry point - require MFA authentication.
     *
     * Called from require_login() hook to enforce MFA.
     *
     * @param mixed $courseorid Course or course ID
     * @param bool $autologinguest Auto login as guest
     * @param mixed $cm Course module
     * @param bool $setwantsurltome Set wants URL to me
     * @param bool $preventredirect Prevent redirect
     * @return void
     */
    public static function require_auth($courseorid = null, $autologinguest = null,
            $cm = null, $setwantsurltome = null, $preventredirect = null): void {
        global $SESSION, $USER;

        // Guest users are not subject to MFA
        if (!isset($USER->id) || $USER->id == 0) {
            return;
        }

        // Check if MFA is ready to be used
        if (!self::is_ready()) {
            $SESSION->tool_mfa_authenticated = true;
            return;
        }

        // Already authenticated with MFA
        if (!empty($SESSION->tool_mfa_authenticated)) {
            return;
        }

        // Get current URL for exclusion check
        $cleanurl = self::get_clean_url();

        // Determine if we should redirect to MFA
        $redir = self::should_require_mfa($cleanurl, $preventredirect);

        if ($redir == self::REDIRECT) {
            self::resolve_mfa_status(true);
        } else if ($redir == self::REDIRECT_EXCEPTION) {
            throw new \nexo_exception('error:mloopdetected', 'tool_mfa');
        }
    }

    /**
     * Check if MFA system is ready to be used.
     *
     * @return bool True if MFA should be enforced
     */
    public static function is_ready(): bool {
        global $CFG, $USER;

        // Not during upgrades
        if (!empty($CFG->upgraderunning)) {
            return false;
        }

        // Check if plugin is enabled
        $pluginenabled = get_config('tool_mfa', 'enabled');
        if (empty($pluginenabled)) {
            return false;
        }

        // Check if user is logged in
        if (!isset($USER->id) || $USER->id == 0) {
            return false;
        }

        // Check capability (all users have this by default)
        // In a full implementation, this would check context
        // For now, we check if user is not admin (admins can be exempt)
        $exemptadmins = get_config('tool_mfa', 'exemptadmins');
        if ($exemptadmins && is_siteadmin($USER->id)) {
            return false;
        }

        // At least one factor must be enabled
        $enabledfactors = factor::get_enabled_factors();
        return count($enabledfactors) > 0;
    }

    /**
     * Get the current MFA status for the user.
     *
     * @return string State constant (STATE_PASS, STATE_FAIL, STATE_NEUTRAL)
     */
    public static function get_status(): string {
        $dominated_weight = 0;

        // Get all active factors for user
        $factors = factor::get_active_user_factor_types();

        foreach ($factors as $factor) {
            $state = $factor->get_state();

            // If any factor failed, return FAIL
            if ($state == factor::STATE_FAIL) {
                return factor::STATE_FAIL;
            }

            // If factor is locked, return LOCKED
            if ($state == factor::STATE_LOCKED) {
                return factor::STATE_LOCKED;
            }

            // Accumulate weight of passed factors
            if ($state == factor::STATE_PASS) {
                $dominated_weight += $factor->get_weight();
            }
        }

        // If weight >= 100, user passes MFA
        if ($dominated_weight >= 100) {
            return factor::STATE_PASS;
        }

        return factor::STATE_NEUTRAL;
    }

    /**
     * Set the pass state - user has completed MFA.
     *
     * @return void
     */
    public static function set_pass_state(): void {
        global $SESSION, $USER, $DB;

        if (empty($SESSION->tool_mfa_authenticated)) {
            // Mark as authenticated
            $SESSION->tool_mfa_authenticated = true;

            // Update last verification time
            self::update_pass_time();

            // Reset lock counters for all user's factors
            $DB->set_field('tool_mfa', 'lockcounter', 0, ['userid' => $USER->id]);

            // Clean up any temporary codes
            $factors = factor::get_active_user_factor_types();
            foreach ($factors as $factor) {
                $factor->post_pass_state();
            }
        }
    }

    /**
     * Update the last pass time for the user.
     *
     * @return void
     */
    private static function update_pass_time(): void {
        global $DB, $USER;

        $record = $DB->get_record('tool_mfa_auth', ['userid' => $USER->id]);

        if ($record) {
            $record->lastverified = time();
            $DB->update_record('tool_mfa_auth', $record);
        } else {
            $DB->insert_record('tool_mfa_auth', [
                'userid' => $USER->id,
                'lastverified' => time(),
            ]);
        }
    }

    /**
     * Determine if MFA should be required.
     *
     * @param string $url Current URL
     * @param bool $preventredirect Prevent redirect flag
     * @return int REDIRECT, NO_REDIRECT, or REDIRECT_EXCEPTION
     */
    public static function should_require_mfa(string $url, bool $preventredirect = false): int {
        global $SESSION, $CFG;

        // URLs that are always excluded from MFA
        $excludedurls = [
            '/admin/tool/mfa/auth.php',
            '/login/logout.php',
            '/login/index.php',
            '/admin/upgrade.php',
            '/admin/index.php',  // Allow initial admin setup
        ];

        // Check if current URL is excluded
        foreach ($excludedurls as $excluded) {
            if (strpos($url, $excluded) !== false) {
                return self::NO_REDIRECT;
            }
        }

        // Check custom exclusions from config
        $customexclusions = get_config('tool_mfa', 'redir_exclusions');
        if (!empty($customexclusions)) {
            $lines = explode("\n", $customexclusions);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line) && strpos($url, $line) !== false) {
                    return self::NO_REDIRECT;
                }
            }
        }

        // Check for AJAX requests - should throw exception
        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            return self::REDIRECT_EXCEPTION;
        }

        // Check for redirect loops
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (!empty($SESSION->mfa_redir_referer) && $SESSION->mfa_redir_referer == $referer) {
            if (!isset($SESSION->mfa_redir_count)) {
                $SESSION->mfa_redir_count = 1;
            } else {
                $SESSION->mfa_redir_count++;
            }

            if ($SESSION->mfa_redir_count > self::REDIR_LOOP_THRESHOLD) {
                return self::REDIRECT_EXCEPTION;
            }
        } else {
            $SESSION->mfa_redir_referer = $referer;
            $SESSION->mfa_redir_count = 1;
        }

        // Prevent redirect if requested
        if ($preventredirect) {
            return self::REDIRECT_EXCEPTION;
        }

        return self::REDIRECT;
    }

    /**
     * Resolve MFA status and redirect if needed.
     *
     * @param bool $shouldredirect Whether to redirect to auth page
     * @return void
     */
    public static function resolve_mfa_status(bool $shouldredirect = false): void {
        global $SESSION, $CFG;

        $status = self::get_status();

        switch ($status) {
            case factor::STATE_PASS:
                self::set_pass_state();
                return;

            case factor::STATE_FAIL:
            case factor::STATE_LOCKED:
                // Log out user on complete failure
                require_logout();
                throw new \nexo_exception('error:mfafailed', 'tool_mfa');

            case factor::STATE_NEUTRAL:
            default:
                // Need to complete MFA
                if ($shouldredirect) {
                    // Save where user wanted to go
                    if (empty($SESSION->wantsurl)) {
                        $SESSION->wantsurl = qualified_me();
                        $SESSION->tool_mfa_setwantsurl = true;
                    }

                    // Mark as pending
                    $SESSION->mfa_pending = true;

                    // Redirect to MFA auth page
                    redirect($CFG->wwwroot . '/admin/tool/mfa/auth.php');
                }
                break;
        }
    }

    /**
     * Get clean current URL without query string.
     *
     * @return string Clean URL path
     */
    private static function get_clean_url(): string {
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        $parts = parse_url($url);
        return $parts['path'] ?? '/';
    }

    /**
     * Get the next factor that needs user input.
     *
     * @return \tool_mfa\local\factor\object_factor|null Next factor or null
     */
    public static function get_next_user_login_factor() {
        return factor::get_next_user_login_factor();
    }

    /**
     * Check if user has completed MFA setup.
     *
     * @return bool True if user has at least one factor configured
     */
    public static function user_has_setup_factors(): bool {
        global $DB, $USER;

        return $DB->record_exists('tool_mfa', [
            'userid' => $USER->id,
            'revoked' => 0,
        ]);
    }

    /**
     * Get all factors configured for a user.
     *
     * @param int $userid User ID
     * @return array Array of factor records
     */
    public static function get_user_factors(int $userid): array {
        global $DB;

        return $DB->get_records('tool_mfa', [
            'userid' => $userid,
            'revoked' => 0,
        ]);
    }

    /**
     * Revoke a specific factor for a user.
     *
     * @param int $factorid Factor record ID
     * @return bool Success
     */
    public static function revoke_factor(int $factorid): bool {
        global $DB;

        return $DB->set_field('tool_mfa', 'revoked', 1, ['id' => $factorid]);
    }

    /**
     * Increment lock counter for a factor.
     *
     * @param int $factorid Factor record ID
     * @return int New counter value
     */
    public static function increment_lock_counter(int $factorid): int {
        global $DB;

        $record = $DB->get_record('tool_mfa', ['id' => $factorid]);
        if ($record) {
            $record->lockcounter++;
            $DB->update_record('tool_mfa', $record);
            return $record->lockcounter;
        }
        return 0;
    }

    /**
     * Check if a factor is locked.
     *
     * @param int $factorid Factor record ID
     * @return bool True if locked
     */
    public static function is_factor_locked(int $factorid): bool {
        global $DB;

        $lockthreshold = get_config('tool_mfa', 'lockout') ?: 10;
        $record = $DB->get_record('tool_mfa', ['id' => $factorid]);

        return $record && $record->lockcounter >= $lockthreshold;
    }
}
