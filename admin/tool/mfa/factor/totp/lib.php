<?php
/**
 * NexoSupport - TOTP MFA Factor - Library
 *
 * @package    factor_totp
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get factor name
 *
 * @return string
 */
function factor_totp_get_name(): string
{
    return get_string('pluginname', 'factor_totp');
}

/**
 * Get factor weight (for priority)
 *
 * @return int
 */
function factor_totp_get_weight(): int
{
    return 100; // High priority
}

/**
 * Check if user has this factor configured
 *
 * @param int $userid User ID
 * @return bool
 */
function factor_totp_is_configured(int $userid): bool
{
    global $DB;
    $record = $DB->get_record('mfa_totp_secrets', ['user_id' => $userid]);
    return $record && $record->verified;
}
