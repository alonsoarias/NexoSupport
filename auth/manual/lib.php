<?php
/**
 * NexoSupport - Manual Authentication Plugin - Library
 *
 * @package    auth_manual
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get plugin name
 *
 * @return string
 */
function auth_manual_get_name(): string
{
    return get_string('pluginname', 'auth_manual');
}

/**
 * Authenticate user
 *
 * @param string $username Username
 * @param string $password Password
 * @return array|false User data or false on failure
 */
function auth_manual_authenticate(string $username, string $password)
{
    global $DB;

    $user = $DB->get_record('users', ['username' => $username]);

    if (!$user) {
        return false;
    }

    if (!password_verify($password, $user->password)) {
        return false;
    }

    return (array) $user;
}

/**
 * Can change password
 *
 * @return bool
 */
function auth_manual_can_change_password(): bool
{
    return true;
}

/**
 * Change user password
 *
 * @param int $userid User ID
 * @param string $newpassword New password
 * @return bool Success
 */
function auth_manual_change_password(int $userid, string $newpassword): bool
{
    global $DB;

    $hash = password_hash($newpassword, PASSWORD_BCRYPT);

    return $DB->update_record('users', [
        'id' => $userid,
        'password' => $hash,
        'password_updated_at' => time(),
    ]);
}
