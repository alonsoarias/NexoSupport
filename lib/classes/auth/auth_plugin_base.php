<?php
namespace core\auth;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Base class for authentication plugins
 *
 * Similar to Moodle's auth_plugin_base class.
 * All authentication plugins must extend this class.
 *
 * @package core\auth
 */
abstract class auth_plugin_base {

    /**
     * The configuration details for the plugin.
     * @var object
     */
    public $config;

    /**
     * Authentication plugin type - the same as the directory name
     * @var string
     */
    public $authtype;

    /**
     * The fields we can lock and unlock from external authentication providers.
     * @var array
     */
    public $userfields = ['firstname', 'lastname', 'email'];

    /**
     * Constructor
     */
    public function __construct() {
        $this->authtype = str_replace('auth\\', '', str_replace('\\auth', '', get_class($this)));
        $this->config = get_config('auth_' . $this->authtype);
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    abstract public function user_login($username, $password);

    /**
     * Updates the user's password.
     *
     * Called when the user password is updated.
     *
     * @param object $user User table object
     * @param string $newpassword Plaintext password
     * @return bool True if password was successfully updated
     */
    public function user_update_password($user, $newpassword) {
        return update_internal_user_password($user, $newpassword);
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password() {
        return false;
    }

    /**
     * Returns the URL for changing the user's pw, or false if the default
     * internal password form should be used.
     *
     * @return string|false URL or false
     */
    public function change_password_url() {
        return false;
    }

    /**
     * Returns true if this authentication plugin can edit the users'
     * profile.
     *
     * @return bool
     */
    public function can_edit_profile() {
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
     * This function automatically returns the opposite of is_internal() for backwards
     * compatibility. Plugins that extend this class can override this function to
     * change the behavior.
     *
     * @return bool True if password hashes should be stored in user table
     */
    public function prevent_local_passwords() {
        return !$this->is_internal();
    }

    /**
     * Indicates if external account password is required on login.
     *
     * @return bool
     */
    public function is_synchronised_with_external() {
        return false;
    }

    /**
     * Returns true if this authentication plugin can manually set password.
     *
     * @return bool
     */
    public function can_be_manually_set() {
        return false;
    }

    /**
     * Returns true if plugin allows signup.
     *
     * @return bool
     */
    public function can_signup() {
        return false;
    }

    /**
     * Returns true if plugin allows confirming of new users.
     *
     * @return bool
     */
    public function can_confirm() {
        return false;
    }

    /**
     * Returns true if plugin allows resetting of password.
     *
     * @return bool
     */
    public function can_reset_password() {
        return false;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    public function can_be_manually_assigned() {
        return true;
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
        // Override if needed.
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     *
     * @param object $config Config object
     * @return bool True if configuration was processed successfully
     */
    public function process_config($config) {
        return true;
    }

    /**
     * Hook for overriding behavior before going to the login page.
     */
    public function pre_loginpage_hook() {
        // Override if needed.
    }

    /**
     * Hook for overriding behavior after login page has been submitted.
     */
    public function post_loginpage_hook() {
        // Override if needed.
    }

    /**
     * Returns plugin title
     *
     * @return string
     */
    public function get_title() {
        return get_string('pluginname', 'auth_' . $this->authtype);
    }

    /**
     * Returns plugin description
     *
     * @return string
     */
    public function get_description() {
        return get_string('auth_' . $this->authtype . 'description', 'auth_' . $this->authtype);
    }

    /**
     * Returns whether the plugin should be tested during login.
     * This is used by is_enabled() to determine if the plugin should be used.
     *
     * @return bool
     */
    public function is_configured() {
        return true;
    }

    /**
     * Pre user_login hook.
     *
     * This method is called before the user_login() method and can be used to
     * perform any necessary actions before the actual authentication takes place.
     */
    public function pre_user_login_hook() {
        // Override if needed.
    }

    /**
     * Post authentication hook.
     *
     * This method is called after a successful authentication and is used
     * to provide plugins a way to perform actions when users are logged in.
     *
     * @param object $user User object
     * @param string $username Username
     * @param string $password Password
     */
    public function user_authenticated_hook(&$user, $username, $password) {
        // Override if needed.
    }

    /**
     * Sync roles for this user - usually creator
     *
     * @param object $user User object
     */
    public function sync_roles($user) {
        // Override if needed.
    }

    /**
     * Read user information from external database and returns it as array().
     *
     * @param string $username Username
     * @return array User information array or false on error
     */
    public function get_userinfo($username) {
        return false;
    }

    /**
     * Update user record with fresh data from the external source.
     *
     * @param mixed $user User object or username
     * @return bool
     */
    public function update_user_record($user) {
        return true;
    }

    /**
     * Validates the password policy for this authentication method.
     *
     * @param string $password Password to validate
     * @param string &$error Error message (output parameter)
     * @return bool True if password meets policy requirements
     */
    public function validate_password_policy($password, &$error) {
        global $CFG;

        // Get configuration for this auth plugin
        $config = $this->config;

        // Minimum length
        $minlength = $config->minpasswordlength ?? 8;
        if (strlen($password) < $minlength) {
            $error = get_string('passwordminlength', 'auth_' . $this->authtype, $minlength);
            return false;
        }

        // Require uppercase
        if (!empty($config->requireuppercase)) {
            if (!preg_match('/[A-Z]/', $password)) {
                $error = get_string('passwordrequireuppercase', 'auth_' . $this->authtype);
                return false;
            }
        }

        // Require lowercase
        if (!empty($config->requirelowercase)) {
            if (!preg_match('/[a-z]/', $password)) {
                $error = get_string('passwordrequirelowercase', 'auth_' . $this->authtype);
                return false;
            }
        }

        // Require numbers
        if (!empty($config->requirenumbers)) {
            if (!preg_match('/[0-9]/', $password)) {
                $error = get_string('passwordrequirenumbers', 'auth_' . $this->authtype);
                return false;
            }
        }

        // Require special characters
        if (!empty($config->requirespecialchars)) {
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $error = get_string('passwordrequirespecialchars', 'auth_' . $this->authtype);
                return false;
            }
        }

        return true;
    }
}
