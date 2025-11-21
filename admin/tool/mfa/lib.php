<?php
/**
 * MFA plugin library functions.
 *
 * @package    tool_mfa
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Hook called after require_login() completes.
 *
 * This is the main integration point for MFA with the authentication system.
 * It intercepts page loads and redirects to MFA if needed.
 *
 * @param mixed $courseorid Course or course ID
 * @param bool $autologinguest Auto login as guest
 * @param mixed $cm Course module
 * @param bool $setwantsurltome Set wants URL to me
 * @param bool $preventredirect Prevent redirect
 * @return void
 */
function tool_mfa_after_require_login($courseorid = null, $autologinguest = null,
        $cm = null, $setwantsurltome = null, $preventredirect = null): void {
    global $SESSION;

    if (empty($SESSION->tool_mfa_authenticated)) {
        \tool_mfa\manager::require_auth($courseorid, $autologinguest,
            $cm, $setwantsurltome, $preventredirect);
    }
}

/**
 * Extend navigation for MFA management.
 *
 * @param \core\navigation\views\secondary $navigation Secondary navigation
 * @return void
 */
function tool_mfa_extend_navigation_user($navigation): void {
    global $USER, $CFG;

    // Only show if MFA is enabled
    if (!get_config('tool_mfa', 'enabled')) {
        return;
    }

    // Add link to MFA settings
    $url = new \nexo_url('/admin/tool/mfa/user.php');
    $navigation->add(
        get_string('pluginname', 'tool_mfa'),
        $url,
        \navigation_node::TYPE_SETTING
    );
}

/**
 * Get the MFA status for the current session.
 *
 * @return bool True if user has completed MFA
 */
function tool_mfa_is_authenticated(): bool {
    global $SESSION;
    return !empty($SESSION->tool_mfa_authenticated);
}

/**
 * Clear MFA authentication status (for testing or admin purposes).
 *
 * @return void
 */
function tool_mfa_clear_authentication(): void {
    global $SESSION;
    unset($SESSION->tool_mfa_authenticated);
    unset($SESSION->mfa_factor_states);
    unset($SESSION->mfa_pending);
}

/**
 * Check if MFA is required for the current page.
 *
 * @return bool True if MFA should be enforced
 */
function tool_mfa_is_required(): bool {
    return \tool_mfa\manager::is_ready();
}

/**
 * Get human-readable MFA status.
 *
 * @return string Status description
 */
function tool_mfa_get_status_string(): string {
    if (!get_config('tool_mfa', 'enabled')) {
        return get_string('status:mfadisabled', 'tool_mfa');
    }

    $factors = \tool_mfa\plugininfo\factor::get_enabled_factors();
    $count = count($factors);

    if ($count == 0) {
        return get_string('status:mfadisabled', 'tool_mfa');
    }

    return get_string('status:mfaenabled', 'tool_mfa') . ' - ' .
           get_string('status:factorsenabled', 'tool_mfa', $count);
}

/**
 * Check if a user has any configured factors.
 *
 * @param int $userid User ID
 * @return bool True if user has factors
 */
function tool_mfa_user_has_factors(int $userid): bool {
    global $DB;

    return $DB->record_exists('tool_mfa', [
        'userid' => $userid,
        'revoked' => 0,
    ]);
}

/**
 * Get all factors configured for a user.
 *
 * @param int $userid User ID
 * @return array Array of factor records
 */
function tool_mfa_get_user_factors(int $userid): array {
    return \tool_mfa\manager::get_user_factors($userid);
}

/**
 * Revoke all MFA factors for a user.
 *
 * @param int $userid User ID
 * @return bool Success
 */
function tool_mfa_revoke_all_factors(int $userid): bool {
    global $DB;

    return $DB->set_field('tool_mfa', 'revoked', 1, ['userid' => $userid]);
}
