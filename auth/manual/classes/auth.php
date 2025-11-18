<?php
namespace auth_manual;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Manual authentication plugin
 *
 * Similar to Moodle's auth_plugin_manual class.
 * Authenticates users against internal password database.
 *
 * @package auth_manual
 */
class auth extends \core\auth\auth_plugin_base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->authtype = 'manual';
        $this->config = get_config('auth_manual');
    }

    /**
     * Returns true if the username and password work against the internal database.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        global $DB;

        // Get user record
        $user = $DB->get_record('users', ['username' => $username, 'deleted' => 0]);

        if (!$user) {
            return false;
        }

        // Verify password
        return validate_internal_user_password($user, $password);
    }

    /**
     * Updates the user's password.
     *
     * @param object $user User table object
     * @param string $newpassword Plaintext password
     * @return bool True if password was successfully updated
     */
    public function user_update_password($user, $newpassword) {
        // Validate against password policy
        $error = '';
        if (!$this->validate_password_policy($newpassword, $error)) {
            debugging('Password does not meet policy requirements: ' . $error);
            return false;
        }

        return update_internal_user_password($user, $newpassword);
    }

    /**
     * Returns true if this authentication plugin can change the user's password.
     *
     * @return bool
     */
    public function can_change_password() {
        return true;
    }

    /**
     * Returns true if this authentication plugin is "internal".
     *
     * Internal plugins use password hashes from NexoSupport user table for authentication.
     *
     * @return bool
     */
    public function is_internal() {
        return true;
    }

    /**
     * Indicates if password hashes should be stored in local user table.
     *
     * @return bool True means password hash stored in user table, false means flag only.
     */
    public function prevent_local_passwords() {
        return false; // We DO store local passwords
    }

    /**
     * Returns true if this authentication plugin can manually set password.
     *
     * @return bool
     */
    public function can_be_manually_set() {
        return true;
    }

    /**
     * No external data sync for manual authentication.
     *
     * @return bool
     */
    public function is_synchronised_with_external() {
        return false;
    }

    /**
     * Manual accounts can be manually assigned.
     *
     * @return bool
     */
    public function can_be_manually_assigned() {
        return true;
    }

    /**
     * No signup for manual authentication - users are created by admins.
     *
     * @return bool
     */
    public function can_signup() {
        return false;
    }

    /**
     * No confirmation for manual authentication.
     *
     * @return bool
     */
    public function can_confirm() {
        return false;
    }

    /**
     * Manual authentication does not support password reset by default.
     * This can be enabled via separate password reset functionality.
     *
     * @return bool
     */
    public function can_reset_password() {
        return false;
    }

    /**
     * Returns plugin title
     *
     * @return string
     */
    public function get_title() {
        return get_string('pluginname', 'auth_manual');
    }

    /**
     * Returns plugin description
     *
     * @return string
     */
    public function get_description() {
        return get_string('auth_manualdescription', 'auth_manual');
    }

    /**
     * Post authentication hook.
     *
     * This method is called after a successful authentication.
     *
     * @param object $user User object
     * @param string $username Username
     * @param string $password Password
     */
    public function user_authenticated_hook(&$user, $username, $password) {
        global $DB;

        // Update last login time
        $user->lastlogin = time();
        $user->lastip = $_SERVER['REMOTE_ADDR'] ?? '';

        $DB->update_record('users', $user);
    }

    /**
     * Get user info from internal database.
     *
     * @param string $username Username
     * @return array|false User information array or false on error
     */
    public function get_userinfo($username) {
        global $DB;

        $user = $DB->get_record('users', ['username' => $username, 'deleted' => 0]);

        if (!$user) {
            return false;
        }

        return [
            'username' => $user->username,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
        ];
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param array $config An object containing all the data for this page.
     * @param string $error
     * @param array $user_fields
     */
    public function config_form($config, $error, $user_fields) {
        // Configuration is handled through settings.php
        // This method can be used if we need a custom config form
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     *
     * @param object $config Config object
     * @return bool True if configuration was processed successfully
     */
    public function process_config($config) {
        global $DB;

        // Process password policy settings
        $settings = [
            'minpasswordlength',
            'requireuppercase',
            'requirelowercase',
            'requirenumbers',
            'requirespecialchars',
        ];

        foreach ($settings as $setting) {
            if (isset($config->$setting)) {
                set_config($setting, $config->$setting, 'auth_manual');
            }
        }

        return true;
    }
}
