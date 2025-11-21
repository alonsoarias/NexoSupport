<?php
/**
 * MFA plugin database installation.
 *
 * @package    tool_mfa
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Install the MFA plugin tables.
 *
 * @return bool
 */
function xmldb_tool_mfa_install(): bool {
    global $DB;

    // Create tool_mfa table - stores user factors
    $sql = "CREATE TABLE IF NOT EXISTS {tool_mfa} (
        id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        userid BIGINT(10) UNSIGNED NOT NULL,
        factor VARCHAR(100) NOT NULL DEFAULT '',
        secret VARCHAR(1333) DEFAULT NULL,
        label VARCHAR(1333) DEFAULT NULL,
        timecreated BIGINT(15) UNSIGNED NOT NULL DEFAULT 0,
        createdfromip VARCHAR(100) DEFAULT NULL,
        timemodified BIGINT(15) UNSIGNED NOT NULL DEFAULT 0,
        lastverified BIGINT(15) UNSIGNED DEFAULT 0,
        revoked TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
        lockcounter INT(5) UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY userid (userid),
        KEY factor (factor),
        KEY userid_factor_lockcounter (userid, factor, lockcounter)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $DB->execute($sql);

    // Create tool_mfa_secrets table - temporary tokens
    $sql = "CREATE TABLE IF NOT EXISTS {tool_mfa_secrets} (
        id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        userid BIGINT(10) UNSIGNED NOT NULL,
        factor VARCHAR(100) NOT NULL DEFAULT '',
        secret VARCHAR(1333) DEFAULT NULL,
        timecreated BIGINT(15) UNSIGNED NOT NULL DEFAULT 0,
        expiry BIGINT(15) UNSIGNED NOT NULL DEFAULT 0,
        revoked TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
        sessionid VARCHAR(100) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY userid (userid),
        KEY factor (factor)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $DB->execute($sql);

    // Create tool_mfa_auth table - last authentication records
    $sql = "CREATE TABLE IF NOT EXISTS {tool_mfa_auth} (
        id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        userid BIGINT(10) UNSIGNED NOT NULL,
        lastverified BIGINT(15) UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY userid (userid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $DB->execute($sql);

    return true;
}
