<?php
/**
 * Authentication Library
 *
 * Global authentication functions similar to Moodle's authlib.php and moodlelib.php
 *
 * @package core
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get an authentication plugin instance
 *
 * Similar to Moodle's get_auth_plugin() function.
 * Loads the authentication plugin and returns an instance.
 *
 * @param string $auth Authentication method (e.g., 'manual', 'ldap', 'oauth2')
 * @return \core\auth\auth_plugin_base|false Authentication plugin instance or false if not found
 */
function get_auth_plugin($auth) {
    // Validate plugin name
    if (empty($auth) || !preg_match('/^[a-z][a-z0-9_]*$/', $auth)) {
        debugging("Invalid authentication plugin name: '$auth'");
        return false;
    }

    // Check if plugin directory exists
    $plugindir = BASE_DIR . "/auth/$auth";
    if (!is_dir($plugindir)) {
        debugging("Authentication plugin directory not found: $plugindir");
        return false;
    }

    // Load the plugin class file if it exists
    // This ensures compatibility even if autoloader fails
    $authfile = $plugindir . '/classes/auth.php';
    if (file_exists($authfile)) {
        require_once($authfile);
    }

    // Build the class name following NexoSupport convention
    // Pattern: auth_{pluginname}\auth
    // Example: auth_manual\auth
    $classname = "auth_{$auth}\\auth";

    // Check if class exists
    if (!class_exists($classname)) {
        debugging("Authentication plugin class not found: $classname");
        return false;
    }

    // Verify that the class extends the base auth plugin class
    $reflection = new ReflectionClass($classname);
    if (!$reflection->isSubclassOf('core\\auth\\auth_plugin_base')) {
        debugging("Authentication plugin '$auth' must extend \\core\\auth\\auth_plugin_base");
        return false;
    }

    // Instantiate and return
    try {
        $plugin = new $classname();
        return $plugin;
    } catch (Exception $e) {
        debugging("Error instantiating authentication plugin '$auth': " . $e->getMessage());
        return false;
    }
}

/**
 * Verify user password against internal password hash
 *
 * Similar to Moodle's validate_internal_user_password()
 *
 * @param object $user User object with 'password' field containing hash
 * @param string $password Plain text password to verify
 * @return bool True if password matches, false otherwise
 */
function validate_internal_user_password($user, $password) {
    if (!isset($user->password)) {
        return false;
    }

    // Use PHP's password_verify for bcrypt hashes
    return password_verify($password, $user->password);
}

/**
 * Update user's password hash in database
 *
 * Similar to Moodle's update_internal_user_password()
 *
 * @param object $user User object or stdClass with at least 'id' property
 * @param string $password Plain text password
 * @param bool $fasthash If true, use fast hashing (for testing). Default false.
 * @return bool True if password was updated successfully
 */
function update_internal_user_password($user, $password, $fasthash = false) {
    global $DB;

    // Hash the password using bcrypt (PASSWORD_DEFAULT)
    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($hash === false) {
        debugging('Failed to hash password');
        return false;
    }

    // Update user record
    $record = new stdClass();
    $record->id = $user->id;
    $record->password = $hash;

    try {
        $DB->update_record('users', $record);
        return true;
    } catch (Exception $e) {
        debugging('Failed to update user password: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if a password meets the requirements of the authentication plugin
 *
 * This function supports two signatures for Moodle compatibility:
 * 1. check_password_policy($password, $authmethod, &$error)  - Old style
 * 2. check_password_policy($password, &$error, $user)        - Moodle 3.x+ style
 *
 * @param string $password Plain text password
 * @param string|object &$errmsg_or_user Error message (output) OR user object
 * @param object|string|null $user_or_null User object OR null
 * @return bool True if password is valid, false otherwise
 */
function check_password_policy($password, &$errmsg_or_user = '', $user_or_null = null) {
    global $CFG;

    // Determine which signature is being used
    $error = '';
    $user = null;

    if (is_object($errmsg_or_user)) {
        // Moodle 3.x+ style: check_password_policy($password, &$error, $user)
        $user = $errmsg_or_user;
        $errmsg_or_user = '';
    } else if (is_object($user_or_null)) {
        // Moodle 3.x+ style: check_password_policy($password, &$error, $user)
        $user = $user_or_null;
    }

    // Get password policy settings
    $minlength = get_config('core', 'minpasswordlength') ?: 8;
    $requiredigit = get_config('core', 'passwordrequiredigit');
    $requirelower = get_config('core', 'passwordrequirelower');
    $requireupper = get_config('core', 'passwordrequireupper');

    // Check minimum length
    if (strlen($password) < $minlength) {
        $errmsg_or_user = get_string('policy_too_short', 'core', $minlength);
        return false;
    }

    // Check digit requirement
    if ($requiredigit && !preg_match('/\d/', $password)) {
        $errmsg_or_user = get_string('policy_missing_digit', 'core');
        return false;
    }

    // Check lowercase requirement
    if ($requirelower && !preg_match('/[a-z]/', $password)) {
        $errmsg_or_user = get_string('policy_missing_lower', 'core');
        return false;
    }

    // Check uppercase requirement
    if ($requireupper && !preg_match('/[A-Z]/', $password)) {
        $errmsg_or_user = get_string('policy_missing_upper', 'core');
        return false;
    }

    return true;
}

/**
 * Authenticate user with username and password
 *
 * Similar to Moodle's authenticate_user_login()
 *
 * @param string $username Username
 * @param string $password Plain text password
 * @param string $authmethod Authentication method. If null, tries all enabled methods.
 * @return object|false User object if authentication succeeds, false otherwise
 */
function authenticate_user_login($username, $password, $authmethod = null) {
    global $DB;

    // Validate inputs
    if (empty($username) || empty($password)) {
        return false;
    }

    // Get user record
    $user = $DB->get_record('users', ['username' => $username, 'deleted' => 0]);

    if (!$user) {
        debugging("User '$username' not found", DEBUG_DEVELOPER);
        return false;
    }

    // If auth method specified, use that
    if ($authmethod !== null) {
        $authplugin = get_auth_plugin($authmethod);

        if (!$authplugin) {
            debugging("Auth plugin '$authmethod' not found", DEBUG_DEVELOPER);
            return false;
        }

        // Attempt authentication
        if ($authplugin->user_login($username, $password)) {
            // Call post-authentication hook
            $authplugin->user_authenticated_hook($user, $username, $password);
            return $user;
        }

        return false;
    }

    // No auth method specified - try user's auth method or default to manual
    $userauth = $user->auth ?? 'manual';
    $authplugin = get_auth_plugin($userauth);

    if (!$authplugin) {
        debugging("User auth plugin '$userauth' not found, falling back to manual", DEBUG_DEVELOPER);
        $authplugin = get_auth_plugin('manual');
    }

    if (!$authplugin) {
        debugging("Manual auth plugin not found - cannot authenticate", DEBUG_DEVELOPER);
        return false;
    }

    // Attempt authentication
    if ($authplugin->user_login($username, $password)) {
        // Call post-authentication hook
        $authplugin->user_authenticated_hook($user, $username, $password);
        return $user;
    }

    return false;
}

/**
 * Get list of available authentication plugins
 *
 * @return array Array of authentication plugin names
 */
function get_available_auth_plugins() {
    $authdir = BASE_DIR . '/auth';

    if (!is_dir($authdir)) {
        return [];
    }

    $plugins = [];
    $items = scandir($authdir);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $plugindir = $authdir . '/' . $item;

        // Check if it's a directory and has auth.php class
        if (is_dir($plugindir)) {
            $authfile = $plugindir . '/classes/auth.php';
            if (file_exists($authfile)) {
                $plugins[] = $item;
            }
        }
    }

    return $plugins;
}

/**
 * Get enabled authentication plugins
 *
 * @return array Array of enabled authentication plugin names
 */
function get_enabled_auth_plugins() {
    global $CFG;

    // For now, return all available plugins
    // In the future, this could check a config setting
    $available = get_available_auth_plugins();

    // Filter only enabled ones (future enhancement)
    // $enabled = explode(',', $CFG->auth ?? 'manual');
    // return array_intersect($enabled, $available);

    return $available;
}

/**
 * Check if an authentication plugin is enabled
 *
 * @param string $auth Authentication plugin name
 * @return bool True if enabled, false otherwise
 */
function is_auth_plugin_enabled($auth) {
    $enabled = get_enabled_auth_plugins();
    return in_array($auth, $enabled);
}

/**
 * Create a new user with specified authentication method
 *
 * @param object $user User data object
 * @param string $password Plain text password (optional)
 * @param string $auth Authentication method (default: 'manual')
 * @return object|false Created user object or false on failure
 */
function create_user_with_auth($user, $password = null, $auth = 'manual') {
    global $DB;

    // Set auth method
    $user->auth = $auth;

    // Validate password if provided
    if ($password !== null) {
        $error = '';
        if (!check_password_policy($password, $auth, $error)) {
            debugging('Password does not meet policy: ' . $error);
            return false;
        }

        // Hash password
        $user->password = password_hash($password, PASSWORD_DEFAULT);
    }

    // Set default values
    $user->timecreated = time();
    $user->timemodified = time();
    $user->deleted = 0;

    // Set default language if not specified
    if (!isset($user->lang)) {
        $user->lang = 'es';
    }

    try {
        $user->id = $DB->insert_record('users', $user);
        return $user;
    } catch (Exception $e) {
        debugging('Failed to create user: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update user password with policy validation
 *
 * @param object $user User object
 * @param string $newpassword New plain text password
 * @return bool True on success, false on failure
 */
function update_user_password($user, $newpassword) {
    // Get user's auth method
    $authmethod = $user->auth ?? 'manual';

    // Get auth plugin
    $authplugin = get_auth_plugin($authmethod);

    if (!$authplugin) {
        debugging("Auth plugin '$authmethod' not found", DEBUG_DEVELOPER);
        return false;
    }

    // Check if plugin allows password changes
    if (!$authplugin->can_change_password()) {
        debugging("Auth plugin '$authmethod' does not allow password changes", DEBUG_DEVELOPER);
        return false;
    }

    // Use plugin's password update method (includes policy validation)
    return $authplugin->user_update_password($user, $newpassword);
}

/**
 * Get password policy description for display
 *
 * @param string $authmethod Authentication method (default: 'manual')
 * @return string HTML description of password policy
 */
function get_password_policy_description($authmethod = 'manual') {
    $authplugin = get_auth_plugin($authmethod);

    if (!$authplugin) {
        return '';
    }

    $config = $authplugin->config;
    $requirements = [];

    // Minimum length
    $minlength = $config->minpasswordlength ?? 8;
    $requirements[] = get_string('passwordminlength', 'auth_' . $authmethod, $minlength);

    // Uppercase
    if (!empty($config->requireuppercase)) {
        $requirements[] = get_string('passwordrequireuppercase', 'auth_' . $authmethod);
    }

    // Lowercase
    if (!empty($config->requirelowercase)) {
        $requirements[] = get_string('passwordrequirelowercase', 'auth_' . $authmethod);
    }

    // Numbers
    if (!empty($config->requirenumbers)) {
        $requirements[] = get_string('passwordrequirenumbers', 'auth_' . $authmethod);
    }

    // Special characters
    if (!empty($config->requirespecialchars)) {
        $requirements[] = get_string('passwordrequirespecialchars', 'auth_' . $authmethod);
    }

    if (empty($requirements)) {
        return '';
    }

    $html = '<div class="password-policy">';
    $html .= '<strong>' . get_string('passwordpolicy', 'auth_' . $authmethod) . '</strong>';
    $html .= '<ul>';
    foreach ($requirements as $req) {
        $html .= '<li>' . $req . '</li>';
    }
    $html .= '</ul>';
    $html .= '</div>';

    return $html;
}
