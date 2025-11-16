<?php
/**
 * NexoSupport - IP Range MFA Factor - Library
 *
 * @package    factor_iprange
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get factor name
 *
 * @return string
 */
function factor_iprange_get_name(): string
{
    return get_string('pluginname', 'factor_iprange');
}

/**
 * Get factor weight (for priority)
 *
 * @return int
 */
function factor_iprange_get_weight(): int
{
    return 100; // High priority (passive check)
}

/**
 * Check if user has this factor configured
 *
 * @param int $userid User ID
 * @return bool
 */
function factor_iprange_is_configured(int $userid): bool
{
    global $DB;
    return $DB->record_exists('mfa_iprange_config', ['user_id' => $userid]);
}
