<?php
/**
 * Authentication Library
 *
 * Global authentication functions similar to Moodle's authlib.php and moodlelib.php
 *
 * @package core
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// ============================================
// AUTHENTICATION CONSTANTS (Moodle compatible)
// ============================================

/** Login successful */
define('AUTH_LOGIN_OK', 0);

/** User does not exist */
define('AUTH_LOGIN_NOUSER', 1);

/** User account suspended */
define('AUTH_LOGIN_SUSPENDED', 2);

/** Invalid credentials */
define('AUTH_LOGIN_FAILED', 3);

/** Account is locked out */
define('AUTH_LOGIN_LOCKOUT', 4);

/** User not authorized */
define('AUTH_LOGIN_UNAUTHORISED', 5);

/** reCAPTCHA validation failed */
define('AUTH_LOGIN_FAILED_RECAPTCHA', 6);

/** Password not stored locally (external auth) */
define('AUTH_PASSWORD_NOT_CACHED', 'not cached');

// ============================================
// AUTHENTICATION FUNCTIONS
// ============================================

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

// ============================================
// LOGIN COMPLETION FUNCTIONS
// ============================================

/**
 * Complete the user login process
 *
 * Similar to Moodle's complete_user_login() - called after successful authentication
 * to set up the session and trigger events.
 *
 * @param object $user User object from database
 * @param array $extrauserinfo Additional info for the login event
 * @return object The logged in user object
 */
function complete_user_login($user, array $extrauserinfo = []) {
    global $CFG, $USER, $SESSION, $DB;

    // 1. Set up session
    \core\session\manager::login_user($user);

    // 2. Update global USER object
    $USER = $user;
    $_SESSION['USER'] = $user;

    // 3. Update login times
    $now = time();
    $user->lastlogin = $now;
    $user->lastip = $_SERVER['REMOTE_ADDR'] ?? '';

    // Update in database
    $DB->update_record('users', (object)[
        'id' => $user->id,
        'lastlogin' => $user->lastlogin,
        'lastip' => $user->lastip
    ]);

    // 4. Reset failed login attempts
    login_attempt_valid($user);

    // 5. Trigger login event
    try {
        $event = \core\event\user_loggedin::create([
            'objectid' => $user->id,
            'userid' => $user->id,
            'relateduserid' => $user->id,
            'other' => $extrauserinfo
        ]);
        $event->trigger();
    } catch (Exception $e) {
        debugging("Event trigger failed: " . $e->getMessage(), DEBUG_DEVELOPER);
    }

    // 6. Check if password change is required
    if (get_user_preferences('auth_forcepasswordchange', false, $user->id)) {
        if (!isset($SESSION)) {
            $SESSION = new stdClass();
        }
        $SESSION->forcepasswordchange = true;
    }

    return $USER;
}

// ============================================
// ACCOUNT LOCKOUT FUNCTIONS
// ============================================

/**
 * Check if a user account is locked out
 *
 * Similar to Moodle's login_is_lockedout() - checks if account is locked
 * due to too many failed login attempts.
 *
 * @param object $user User object
 * @return bool True if account is locked out
 */
function login_is_lockedout($user) {
    global $CFG;

    // Get lockout settings from config
    $lockoutthreshold = get_config('core', 'lockoutthreshold') ?: 0;

    // If threshold is 0, lockout is disabled
    if (empty($lockoutthreshold)) {
        return false;
    }

    // Check if user can ignore lockout (admin privilege)
    if (get_user_preferences('login_lockout_ignored', 0, $user->id)) {
        return false;
    }

    // Check if account is currently locked
    $locked = get_user_preferences('login_lockout', 0, $user->id);

    if (!$locked) {
        return false;
    }

    // Get lockout duration
    $lockoutduration = get_config('core', 'lockoutduration') ?: 0;

    // If no duration set, lock is permanent until admin unlocks
    if (empty($lockoutduration)) {
        return true;
    }

    // Check if lock has expired
    if (time() - $locked < $lockoutduration) {
        return true; // Still locked
    }

    // Lock has expired, automatically unlock
    login_unlock_account($user);
    return false;
}

/**
 * Lock a user account
 *
 * Similar to Moodle's login_lock_account() - locks account after too many failed attempts.
 *
 * @param object $user User object
 */
function login_lock_account($user) {
    global $CFG;

    // Check if already locked
    $alreadylocked = get_user_preferences('login_lockout', 0, $user->id);

    // Set lock timestamp
    set_user_preference('login_lockout', time(), $user->id);

    // If first time locking, generate unlock secret and send email
    if (!$alreadylocked) {
        $secret = random_string(15);
        set_user_preference('login_lockout_secret', $secret, $user->id);

        // Send notification email
        $sitename = get_config('core', 'sitename') ?: 'NexoSupport';

        $data = new stdClass();
        $data->firstname = $user->firstname;
        $data->username = $user->username;
        $data->sitename = $sitename;
        $data->link = $CFG->wwwroot . '/login/unlock_account.php?u=' . $user->id . '&s=' . $secret;

        $subject = get_string('accountlocked', 'admin');
        $message = get_string('accountlockednotification', 'core', $data);

        email_to_user($user, get_admin(), $subject, $message);
    }
}

/**
 * Unlock a user account
 *
 * Similar to Moodle's login_unlock_account() - removes lockout from user account.
 *
 * @param object $user User object
 * @param bool $notify Show notification message
 */
function login_unlock_account($user, bool $notify = false) {
    global $SESSION;

    // Clear all lockout preferences
    unset_user_preference('login_lockout', $user->id);
    unset_user_preference('login_failed_count', $user->id);
    unset_user_preference('login_failed_last', $user->id);
    unset_user_preference('login_lockout_secret', $user->id);

    // Show notification if requested
    if ($notify && isset($SESSION)) {
        $SESSION->logininfomsg = get_string('accountunlocked', 'admin');
    }
}

/**
 * Record a failed login attempt
 *
 * Similar to Moodle's login_attempt_failed() - increments failed login counter
 * and locks account if threshold is reached.
 *
 * @param object $user User object
 */
function login_attempt_failed($user) {
    global $CFG;

    // Get lockout settings
    $lockoutthreshold = get_config('core', 'lockoutthreshold') ?: 0;
    $lockoutwindow = get_config('core', 'lockoutwindow') ?: 0;

    // Get current counts
    $count = get_user_preferences('login_failed_count', 0, $user->id);
    $last = get_user_preferences('login_failed_last', 0, $user->id);

    // If lockout disabled, just record and return
    if (empty($lockoutthreshold)) {
        set_user_preference('login_failed_count', $count + 1, $user->id);
        set_user_preference('login_failed_last', time(), $user->id);
        return;
    }

    // Reset count if outside lockout window
    if (!empty($lockoutwindow) && (time() - $last) > $lockoutwindow) {
        $count = 0;
    }

    // Increment counter
    $count++;

    // Save preferences
    set_user_preference('login_failed_count', $count, $user->id);
    set_user_preference('login_failed_last', time(), $user->id);

    // Lock account if threshold reached
    if ($count >= $lockoutthreshold) {
        login_lock_account($user);
    }
}

/**
 * Record a successful login attempt
 *
 * Similar to Moodle's login_attempt_valid() - resets failed login counter.
 *
 * @param object $user User object
 */
function login_attempt_valid($user) {
    // Reset failed login counter
    unset_user_preference('login_failed_count', $user->id);
    unset_user_preference('login_failed_last', $user->id);
}

// ============================================
// USER HELPER FUNCTIONS
// ============================================

/**
 * Check if user is a guest user
 *
 * Similar to Moodle's isguestuser() - checks if user is the guest account.
 *
 * @param object|int|null $user User object or ID (null = current user)
 * @return bool True if user is guest
 */
function isguestuser($user = null) {
    global $USER, $CFG;

    if ($user === null) {
        $user = $USER;
    }

    if (is_numeric($user)) {
        $userid = $user;
    } else {
        $userid = $user->id ?? 0;
    }

    // Guest user has ID = 1 by convention, or check config
    $guestid = get_config('core', 'siteguest') ?: 1;

    return ($userid == $guestid);
}

/**
 * Check if user profile is complete
 *
 * Similar to Moodle's user_not_fully_set_up() - checks if required fields are filled.
 *
 * @param object $user User object
 * @param bool $strict Check all required fields strictly
 * @return bool True if user profile is NOT fully set up
 */
function user_not_fully_set_up($user, bool $strict = false) {
    // Required fields
    $required = ['firstname', 'lastname', 'email'];

    foreach ($required as $field) {
        if (empty($user->$field)) {
            return true;
        }
    }

    // Check email is valid
    if (!validate_email($user->email)) {
        return true;
    }

    // Check if force password change is set
    if (get_user_preferences('auth_forcepasswordchange', false, $user->id)) {
        return true;
    }

    return false;
}

/**
 * Add password to user's password history
 *
 * Similar to Moodle's user_add_password_history() - stores password hash
 * to prevent reuse.
 *
 * @param int $userid User ID
 * @param string $password Plain text password
 * @return bool True on success
 */
function user_add_password_history($userid, $password) {
    global $DB, $CFG;

    // Check if password history is enabled
    $historycount = get_config('core', 'passwordreuselimit') ?: 0;

    if (empty($historycount)) {
        return true; // History disabled
    }

    // Hash the password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Check if table exists
    try {
        // Add new entry
        $record = new stdClass();
        $record->userid = $userid;
        $record->hash = $hash;
        $record->timecreated = time();

        $DB->insert_record('user_password_history', $record);

        // Clean up old entries if we have more than the limit
        $sql = "SELECT id FROM {user_password_history}
                WHERE userid = ?
                ORDER BY timecreated DESC";
        $records = $DB->get_records_sql($sql, [$userid]);

        $count = 0;
        foreach ($records as $rec) {
            $count++;
            if ($count > $historycount) {
                $DB->delete_records('user_password_history', ['id' => $rec->id]);
            }
        }

        return true;
    } catch (Exception $e) {
        // Table might not exist yet
        debugging('Password history table not available: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if password was used before
 *
 * @param int $userid User ID
 * @param string $password Plain text password
 * @return bool True if password was used before
 */
function user_is_previously_used_password($userid, $password) {
    global $DB;

    $historycount = get_config('core', 'passwordreuselimit') ?: 0;

    if (empty($historycount)) {
        return false;
    }

    try {
        $sql = "SELECT * FROM {user_password_history}
                WHERE userid = ?
                ORDER BY timecreated DESC";
        $records = $DB->get_records_sql($sql, [$userid], 0, $historycount);

        foreach ($records as $record) {
            if (password_verify($password, $record->hash)) {
                return true;
            }
        }
    } catch (Exception $e) {
        // Table might not exist
        return false;
    }

    return false;
}

// ============================================
// EMAIL FUNCTIONS
// ============================================

/**
 * Validate an email address
 *
 * @param string $email Email address to validate
 * @return bool True if valid
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get the primary admin user
 *
 * Similar to Moodle's get_admin() - returns the first site administrator.
 *
 * @return object|false Admin user object or false if not found
 */
function get_admin() {
    global $DB;

    $siteadmins = get_siteadmins();

    if (empty($siteadmins)) {
        return false;
    }

    // Get first admin
    $adminid = reset($siteadmins);

    return $DB->get_record('users', ['id' => $adminid]);
}

/**
 * Send an email to a user
 *
 * Similar to Moodle's email_to_user() - sends email using configured mailer.
 *
 * @param object $user Recipient user object
 * @param object $from Sender user object or support user
 * @param string $subject Email subject
 * @param string $messagetext Plain text message
 * @param string $messagehtml HTML message (optional)
 * @param string $attachment Attachment path (optional)
 * @param string $attachname Attachment filename (optional)
 * @return bool True on success
 */
function email_to_user($user, $from, $subject, $messagetext, $messagehtml = '', $attachment = '', $attachname = '') {
    global $CFG;

    // Validate recipient email
    if (empty($user->email) || !validate_email($user->email)) {
        debugging('Invalid recipient email: ' . ($user->email ?? 'empty'));
        return false;
    }

    // Get sender email
    if (is_object($from)) {
        $fromemail = $from->email ?? ($CFG->supportemail ?? 'noreply@localhost');
        $fromname = trim(($from->firstname ?? '') . ' ' . ($from->lastname ?? '')) ?: 'Support';
    } else {
        $fromemail = $CFG->supportemail ?? 'noreply@localhost';
        $fromname = 'Support';
    }

    // Build headers
    $headers = [
        'From' => "$fromname <$fromemail>",
        'Reply-To' => $fromemail,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'X-Mailer' => 'NexoSupport'
    ];

    // Use HTML if provided
    if (!empty($messagehtml)) {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        $body = $messagehtml;
    } else {
        $body = $messagetext;
    }

    // Build header string
    $headerstring = '';
    foreach ($headers as $key => $value) {
        $headerstring .= "$key: $value\r\n";
    }

    // Send email
    $result = @mail($user->email, $subject, $body, $headerstring);

    if (!$result) {
        debugging('Failed to send email to: ' . $user->email);
    }

    return $result;
}

/**
 * Send account confirmation email
 *
 * Similar to Moodle's send_confirmation_email()
 *
 * @param object $user User object
 * @param string $confirmationurl Custom confirmation URL (optional)
 * @return bool True on success
 */
function send_confirmation_email($user, $confirmationurl = '') {
    global $CFG;

    $sitename = get_config('core', 'sitename') ?: 'NexoSupport';

    // Generate confirmation token if not set
    if (empty($user->secret)) {
        $user->secret = random_string(15);
        global $DB;
        $DB->set_field('users', 'secret', $user->secret, ['id' => $user->id]);
    }

    // Build confirmation URL
    if (empty($confirmationurl)) {
        $confirmationurl = $CFG->wwwroot . '/login/confirm.php?data=' . $user->secret . '/' . urlencode($user->username);
    }

    $data = new stdClass();
    $data->firstname = $user->firstname;
    $data->lastname = $user->lastname;
    $data->username = $user->username;
    $data->sitename = $sitename;
    $data->link = $confirmationurl;
    $data->admin = get_string('administrator', 'core');

    $subject = get_string('emailconfirmationsubject', 'core', $sitename);
    $message = get_string('emailconfirmation', 'core', $data);

    return email_to_user($user, get_admin(), $subject, $message);
}

/**
 * Get login URL
 *
 * @return string Login page URL
 */
function get_login_url() {
    global $CFG;
    return $CFG->wwwroot . '/login';
}

/**
 * Check if authentication method is enabled
 *
 * @param string $auth Auth method name
 * @return bool True if enabled
 */
function is_enabled_auth($auth) {
    $enabled = get_enabled_auth_plugins();
    return in_array($auth, $enabled);
}
