<?php
/**
 * NexoSupport - Backup Codes MFA Factor - Library
 *
 * @package    factor_backupcodes
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get factor name
 *
 * @return string
 */
function factor_backupcodes_get_name(): string
{
    return get_string('pluginname', 'factor_backupcodes');
}

/**
 * Get factor weight (for priority)
 *
 * @return int
 */
function factor_backupcodes_get_weight(): int
{
    return 25; // Low priority (fallback)
}

/**
 * Check if user has this factor configured
 *
 * @param int $userid User ID
 * @return bool
 */
function factor_backupcodes_is_configured(int $userid): bool
{
    global $DB;
    return $DB->record_exists('mfa_backup_codes', [
        'user_id' => $userid,
        'used' => 0,
    ]);
}
