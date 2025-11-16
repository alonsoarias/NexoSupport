<?php
/**
 * NexoSupport - SMS MFA Factor - Library
 *
 * @package    factor_sms
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get factor name
 *
 * @return string
 */
function factor_sms_get_name(): string
{
    return get_string('pluginname', 'factor_sms');
}

/**
 * Get factor weight (for priority)
 *
 * @return int
 */
function factor_sms_get_weight(): int
{
    return 75; // Medium-high priority
}

/**
 * Check if user has this factor configured
 *
 * @param int $userid User ID
 * @return bool
 */
function factor_sms_is_configured(int $userid): bool
{
    global $DB;
    $user = $DB->get_record('users', ['id' => $userid]);
    return !empty($user->phone);
}
