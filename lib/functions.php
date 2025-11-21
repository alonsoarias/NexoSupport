<?php
/**
 * Funciones globales de NexoSupport
 *
 * Funciones helper globales disponibles en todo el sistema.
 * Similar a Moodle lib/moodlelib.php
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Obtener string de idioma
 *
 * @param string $identifier Identificador del string
 * @param string $component Componente (ej: 'core', 'auth_manual')
 * @param mixed $a Datos para interpolación
 * @return string
 */
function get_string(string $identifier, string $component = 'core', mixed $a = null): string {
    return \core\string_manager::get_string($identifier, $component, $a);
}

/**
 * Verificar si un string existe
 *
 * @param string $identifier
 * @param string $component
 * @return bool
 */
function string_exists(string $identifier, string $component = 'core'): bool {
    return \core\string_manager::string_exists($identifier, $component);
}

/**
 * Cargar strings de idioma de un componente
 *
 * @param string $component
 * @param string $lang
 * @return array
 */
function load_language_strings(string $component, string $lang = 'es'): array {
    global $CFG;

    $strings = [];

    // Determinar ruta del archivo de idioma
    if ($component === 'core') {
        $langfile = $CFG->dirroot . '/lib/lang/' . $lang . '/core.php';
    } else {
        // Plugin: determinar directorio
        $parts = explode('_', $component, 2);
        if (count($parts) === 2) {
            $type = $parts[0];
            $name = $parts[1];

            $components = \core\plugin\manager::load_components();

            if (isset($components['plugintypes'][$type])) {
                $typedir = $components['plugintypes'][$type];
                $langfile = $CFG->dirroot . '/' . $typedir . '/' . $name . '/lang/' . $lang . '/' . $component . '.php';
            }
        }
    }

    if (isset($langfile) && file_exists($langfile)) {
        $string = []; // Variable esperada por los archivos de idioma
        include($langfile);
        $strings = $string;
    }

    return $strings;
}

/**
 * Obtener configuración
 *
 * @param string $component Componente (o nombre de config si component es core)
 * @param string|null $name Nombre de la config (null para obtener todas)
 * @return mixed
 */
function get_config(string $component, ?string $name = null): mixed {
    global $DB, $CFG;

    // Si solo se pasa un parámetro, es get_config('core', 'nombre')
    if ($name === null && $component !== 'core') {
        $name = $component;
        $component = 'core';
    }

    static $cache = [];

    // Cargar toda la config del componente si no está en cache
    if (!isset($cache[$component])) {
        $cache[$component] = [];

        if (isset($DB)) {
            try {
                $records = $DB->get_records('config', ['component' => $component]);

                foreach ($records as $record) {
                    $value = $record->value;

                    // Deserializar si es necesario
                    if ($value === 'true') {
                        $value = true;
                    } elseif ($value === 'false') {
                        $value = false;
                    } elseif (is_numeric($value)) {
                        $value = $value + 0; // Convertir a int o float
                    }

                    $cache[$component][$record->name] = $value;
                }
            } catch (\Exception $e) {
                // BD no disponible aún
            }
        }
    }

    if ($name === null) {
        return $cache[$component];
    }

    return $cache[$component][$name] ?? null;
}

/**
 * Establecer configuración
 *
 * @param string $name Nombre de la config
 * @param mixed $value Valor
 * @param string $component Componente
 * @return bool
 */
function set_config(string $name, mixed $value, string $component = 'core'): bool {
    global $DB;

    // Serializar valor
    if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
    } elseif (is_array($value) || is_object($value)) {
        $value = json_encode($value);
    } else {
        $value = (string)$value;
    }

    // Verificar si existe
    $existing = $DB->get_record('config', [
        'component' => $component,
        'name' => $name
    ]);

    if ($existing) {
        return $DB->update_record('config', [
            'id' => $existing->id,
            'value' => $value
        ]);
    } else {
        $DB->insert_record('config', [
            'component' => $component,
            'name' => $name,
            'value' => $value
        ]);
        return true;
    }
}

/**
 * Unset configuración
 *
 * @param string $name
 * @param string $component
 * @return bool
 */
function unset_config(string $name, string $component = 'core'): bool {
    global $DB;

    return $DB->delete_records('config', [
        'component' => $component,
        'name' => $name
    ]);
}

/**
 * Verificar capability
 *
 * FASE 2: Implementación completa de RBAC
 *
 * @param string $capability
 * @param int|null $userid
 * @param \core\rbac\context|null $context
 * @return bool
 */
function has_capability(string $capability, ?int $userid = null, ?\core\rbac\context $context = null): bool {
    return \core\rbac\access::has_capability($capability, $userid, $context);
}

/**
 * Require login
 *
 * CRITICAL: This function MUST prevent access to pages without authentication.
 * If user is not logged in (id=0 or not set), redirect to login page.
 *
 * @return void
 */
function require_login(): void {
    global $USER;

    // Check if user is logged in
    // User is NOT logged in if:
    // - $USER->id is not set
    // - $USER->id is 0 (guest)
    // - $USER->id is empty
    if (!isset($USER->id) || empty($USER->id)) {
        // User not logged in, redirect to login page
        redirect('/login');
        exit; // Ensure script stops
    }

    // Additional security: verify user still exists and is active
    if (isset($USER->deleted) && $USER->deleted) {
        // User is deleted, force logout
        unset($_SESSION['USER']);
        redirect('/login');
        exit;
    }

    if (isset($USER->suspended) && $USER->suspended) {
        // User is suspended, force logout
        unset($_SESSION['USER']);
        redirect('/login?error=suspended');
        exit;
    }

    // Hook for MFA (Multi-Factor Authentication)
    // This must be called after basic auth checks but before page access
    if (function_exists('tool_mfa_after_require_login')) {
        tool_mfa_after_require_login();
    }
}

/**
 * Require capability
 *
 * @param string $capability
 * @return void
 */
function require_capability(string $capability): void {
    if (!has_capability($capability)) {
        throw new \nexo_exception('nopermissions', 'error', '', $capability);
    }
}

/**
 * Redirect
 *
 * @param string $url
 * @param string|null $message
 * @param int $delay
 * @return void
 */
function redirect(string $url, ?string $message = null, int $delay = 0): void {
    if ($message !== null) {
        $_SESSION['redirect_message'] = $message;
    }

    if ($delay > 0) {
        sleep($delay);
    }

    header('Location: ' . $url);
    exit;
}

/**
 * Print error and terminate execution
 *
 * Similar to Moodle's print_error() function.
 * Displays an error message and stops execution.
 *
 * @param string $errorcode Error string identifier
 * @param string $module Module name (default 'core')
 * @param string $link Optional link to redirect to
 * @param mixed $a Optional additional data for string
 * @return void
 */
function print_error(string $errorcode, string $module = 'core', string $link = '', $a = null): void {
    global $CFG;

    $message = get_string($errorcode, $module, $a);

    // If debug mode, show more details
    if ($CFG->debug) {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $caller = isset($backtrace[1]) ? $backtrace[1] : [];
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 0;

        echo '<div style="background: #ffebee; border: 2px solid #c62828; padding: 20px; margin: 20px; font-family: monospace;">';
        echo '<h1 style="color: #c62828; margin: 0 0 10px 0;">Error</h1>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($message) . '</p>';
        echo '<p><strong>Error code:</strong> ' . htmlspecialchars($errorcode) . '</p>';
        echo '<p><strong>Module:</strong> ' . htmlspecialchars($module) . '</p>';
        echo '<p><strong>Called from:</strong> ' . htmlspecialchars($file) . ':' . $line . '</p>';

        if (!empty($link)) {
            echo '<p><a href="' . htmlspecialchars($link) . '">Continue</a></p>';
        } else {
            echo '<p><a href="/">Return to home</a></p>';
        }

        echo '</div>';
    } else {
        // Production mode - show minimal error
        echo '<div style="background: #ffebee; border: 2px solid #c62828; padding: 20px; margin: 20px; text-align: center;">';
        echo '<h1 style="color: #c62828;">Error</h1>';
        echo '<p>' . htmlspecialchars($message) . '</p>';

        if (!empty($link)) {
            echo '<p><a href="' . htmlspecialchars($link) . '">Continue</a></p>';
        } else {
            echo '<p><a href="/">Return to home</a></p>';
        }

        echo '</div>';
    }

    exit;
}

/**
 * Required param from request
 *
 * @param string $name
 * @param string $type
 * @return mixed
 */
function required_param(string $name, string $type = 'raw'): mixed {
    $value = optional_param($name, null, $type);

    if ($value === null) {
        throw new \coding_exception("Required parameter '$name' was not provided");
    }

    return $value;
}

/**
 * Optional param from request
 *
 * @param string $name
 * @param mixed $default
 * @param string $type
 * @return mixed
 */
function optional_param(string $name, mixed $default = null, string $type = 'raw'): mixed {
    $value = $_POST[$name] ?? $_GET[$name] ?? $default;

    if ($value === null || $value === $default) {
        return $default;
    }

    return clean_param($value, $type);
}

/**
 * Clean parameter
 *
 * @param mixed $value
 * @param string $type
 * @return mixed
 */
function clean_param(mixed $value, string $type): mixed {
    // Handle null/empty values
    if ($value === null || $value === '') {
        switch ($type) {
            case 'int':
                return 0;
            case 'float':
                return 0.0;
            case 'bool':
                return false;
            case 'array':
                return [];
            default:
                return $value;
        }
    }

    switch ($type) {
        case 'int':
            return (int)$value;

        case 'float':
            return (float)$value;

        case 'bool':
            return (bool)$value;

        case 'email':
            $email = filter_var($value, FILTER_VALIDATE_EMAIL);
            return $email !== false ? $email : '';

        case 'url':
            $url = filter_var($value, FILTER_VALIDATE_URL);
            return $url !== false ? $url : '';

        case 'alphanumext':
            // Letters, numbers, underscore, hyphen, dot
            return preg_replace('/[^a-zA-Z0-9_.-]/', '', $value);

        case 'alphanum':
            // Only letters and numbers
            return preg_replace('/[^a-zA-Z0-9]/', '', $value);

        case 'alpha':
            // Only letters
            return preg_replace('/[^a-zA-Z]/', '', $value);

        case 'text':
            // Strip tags and encode HTML entities
            return htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');

        case 'notags':
            // Strip all HTML/PHP tags
            return strip_tags($value);

        case 'path':
            // Clean file path (no traversal attempts)
            $value = str_replace('\\', '/', $value);
            $value = preg_replace('#/+#', '/', $value);
            $value = preg_replace('#\.\.+#', '', $value);
            return trim($value, '/');

        case 'file':
            // Clean filename (no path separators)
            return preg_replace('/[^a-zA-Z0-9_.-]/', '', basename($value));

        case 'safedir':
            // Safe directory name (no special chars)
            return preg_replace('/[^a-zA-Z0-9_-]/', '', $value);

        case 'username':
            // Username format (letters, numbers, underscore, hyphen, dot)
            return preg_replace('/[^a-zA-Z0-9_.-]/', '', strtolower($value));

        case 'host':
            // Hostname/domain
            return preg_replace('/[^a-zA-Z0-9.-]/', '', strtolower($value));

        case 'sequence':
            // Comma-separated sequence of integers
            $items = explode(',', $value);
            $items = array_map('intval', $items);
            return implode(',', array_filter($items));

        case 'array':
            // Ensure it's an array
            return is_array($value) ? $value : [$value];

        case 'json':
            // Decode JSON
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                return $decoded !== null ? $decoded : [];
            }
            return $value;

        case 'raw':
        case PARAM_RAW:
        default:
            return $value;
    }
}

/**
 * Debugging output
 *
 * @param string $message
 * @param string $level
 * @return void
 */
function debugging(string $message, string $level = 'notice'): void {
    global $CFG;

    if (isset($CFG->debug) && $CFG->debug) {
        error_log("[$level] $message");
    }
}

/**
 * Require sesskey (CSRF protection)
 *
 * Throws an exception if the sesskey is invalid.
 * Use this in forms and actions that modify data.
 *
 * @return void
 * @throws nexo_exception If sesskey is invalid
 */
function require_sesskey(): void {
    $sesskey = optional_param('sesskey', null, 'raw');

    if ($sesskey !== sesskey()) {
        throw new \nexo_exception('invalidsesskey', 'error');
    }
}

/**
 * Confirm session key (CSRF protection)
 *
 * Similar to require_sesskey() but returns boolean instead of throwing exception.
 * Use this in optional sesskey validation scenarios (e.g., links with confirm dialogs,
 * or when you need to handle invalid sesskey gracefully).
 *
 * Example usage:
 * <code>
 * if ($delete && confirm_sesskey()) {
 *     // Process delete action
 * }
 * </code>
 *
 * @return bool True if sesskey is valid, false otherwise
 */
function confirm_sesskey(): bool {
    $sesskey = optional_param('sesskey', '', 'raw');
    return ($sesskey === sesskey());
}

/**
 * Validate session key (alias for confirm_sesskey)
 *
 * Alias function for backward compatibility.
 * Validates the session key from POST/GET parameters.
 *
 * @return bool True if sesskey is valid, false otherwise
 */
function validate_sesskey(): bool {
    return confirm_sesskey();
}

/**
 * Get session key
 *
 * Returns the current user's session key for CSRF protection.
 * Include this in all forms and links that perform actions.
 *
 * @return string Session key
 */
function sesskey(): string {
    return \core\session\manager::get_sesskey();
}

/**
 * Add notification to session
 *
 * @param string $message
 * @param string $type success|error|warning|info
 * @return void
 */
function add_notification(string $message, string $type = 'info'): void {
    if (!isset($_SESSION['notifications'])) {
        $_SESSION['notifications'] = [];
    }

    $_SESSION['notifications'][] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get global OUTPUT renderer
 *
 * @return \core\output\renderer
 */
function get_renderer(): \core\output\renderer {
    global $OUTPUT;

    if (!isset($OUTPUT)) {
        $OUTPUT = new \core\output\renderer();
    }

    return $OUTPUT;
}

/**
 * Get global PAGE object
 *
 * @return \core\output\page
 */
function get_page(): \core\output\page {
    global $PAGE;

    if (!isset($PAGE)) {
        $PAGE = new \core\output\page();
    }

    return $PAGE;
}

/**
 * Render a template
 *
 * @param string $templatename Template name (component/name)
 * @param array|object $context Template context
 * @return string Rendered HTML
 */
function render_template(string $templatename, $context = []): string {
    global $USER, $CFG;

    // Auto-inject global variables into context
    $auto_context = [
        // User information
        'user' => $USER ?? null,
        'currentlang' => \core\string_manager::get_language(),

        // Developer mode variables
        'developer_mode' => is_developer_mode(),
        'show_dev_toolbar' => show_dev_toolbar(),
        'debug_level' => get_debug_level(),

        // System info for developer toolbar
        'version' => $CFG->version ?? '1.1.9',
        'php_version' => PHP_VERSION,
    ];

    // Add performance metrics if in developer mode
    if (is_developer_mode() && show_performance_info()) {
        $auto_context['page_load_time'] = number_format(microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)), 3);
        $auto_context['memory_usage'] = round(memory_get_usage() / 1024 / 1024, 2) . ' MB';

        // Get DB query count if available
        global $DB;
        if (isset($DB) && method_exists($DB, 'get_query_count')) {
            $auto_context['db_queries'] = $DB->get_query_count();
        }
    }

    // Merge with provided context (user context takes precedence)
    $final_context = array_merge($auto_context, $context);

    return \core\output\template_manager::render($templatename, $final_context);
}

/**
 * Coding exception
 */
class coding_exception extends \Exception {
}

/**
 * NexoSupport exception class
 */
class nexo_exception extends \Exception {
    public function __construct($errorcode, $module = '', $link = '', $a = null, $debuginfo = null) {
        $message = get_string($errorcode, $module, $a);
        parent::__construct($message);
    }
}

// Backward compatibility alias
class_alias('nexo_exception', 'moodle_exception');

// Context class aliases for Moodle compatibility
class_alias('core\\rbac\\context_system', 'context_system');
class_alias('core\\rbac\\context_course', 'context_course');
class_alias('core\\rbac\\context_user', 'context_user');

/**
 * Access exception - thrown when user doesn't have permission
 */
class access_exception extends \Exception {
    public function __construct($message = 'Access denied', $code = 403) {
        parent::__construct($message, $code);
    }
}

/**
 * Constantes de tipo de parámetro
 */
// Parameter type constants
define('PARAM_RAW', 'raw');
define('PARAM_INT', 'int');
define('PARAM_FLOAT', 'float');
define('PARAM_BOOL', 'bool');
define('PARAM_EMAIL', 'email');
define('PARAM_URL', 'url');
define('PARAM_ALPHANUMEXT', 'alphanumext');
define('PARAM_ALPHANUM', 'alphanum');
define('PARAM_ALPHA', 'alpha');
define('PARAM_TEXT', 'text');
define('PARAM_NOTAGS', 'notags');
define('PARAM_PATH', 'path');
define('PARAM_FILE', 'file');
define('PARAM_SAFEDIR', 'safedir');
define('PARAM_USERNAME', 'username');
define('PARAM_HOST', 'host');
define('PARAM_SEQUENCE', 'sequence');
define('PARAM_ARRAY', 'array');
define('PARAM_JSON', 'json');

/**
 * Constantes de madurez
 *
 * Define maturity levels for plugins and system components.
 * These are also defined in public_html/index.php for early use,
 * so we check if they're already defined to avoid redefinition warnings.
 */
if (!defined('MATURITY_ALPHA')) {
    define('MATURITY_ALPHA', 50);
    define('MATURITY_BETA', 100);
    define('MATURITY_RC', 150);
    define('MATURITY_STABLE', 200);
}

/**
 * Constantes de SQL params
 */
define('SQL_PARAMS_QM', 0);     // Question mark placeholders (?)
define('SQL_PARAMS_NAMED', 1);  // Named placeholders (:param)

// ============================================
// RBAC Helper Functions (Fase 2)
// ============================================

/**
 * Assign role to user
 *
 * @param int $roleid
 * @param int $userid
 * @param \core\rbac\context|null $context
 * @return int Assignment ID
 */
function role_assign(int $roleid, int $userid, ?\core\rbac\context $context = null): int {
    if ($context === null) {
        $context = \core\rbac\context::system();
    }

    return \core\rbac\access::assign_role($roleid, $userid, $context);
}

/**
 * Unassign role from user
 *
 * @param int $roleid
 * @param int $userid
 * @param \core\rbac\context|null $context
 * @return bool
 */
function role_unassign(int $roleid, int $userid, ?\core\rbac\context $context = null): bool {
    if ($context === null) {
        $context = \core\rbac\context::system();
    }

    return \core\rbac\access::unassign_role($roleid, $userid, $context);
}

/**
 * Get user roles
 *
 * @param int $userid
 * @param \core\rbac\context|null $context
 * @return array
 */
function get_user_roles(int $userid, ?\core\rbac\context $context = null): array {
    global $DB;

    if ($context === null) {
        $context = \core\rbac\context::system();
    }

    $sql = "SELECT r.*
            FROM {roles} r
            JOIN {role_assignments} ra ON ra.roleid = r.id
            WHERE ra.userid = ?
              AND ra.contextid = ?";

    return $DB->get_records_sql($sql, [$userid, $context->id]);
}

/**
 * Check if user is a site administrator
 *
 * Similar to Moodle's is_siteadmin() - checks if user is in the siteadmins
 * config setting. This is the PRIMARY way to verify site administrators.
 *
 * Site administrators are super users with unrestricted access to everything.
 * Their user IDs are stored in the config table as 'siteadmins' (comma-separated).
 *
 * @param int|null $userid User ID to check (null = current user)
 * @return bool True if user is site administrator
 */
function is_siteadmin(?int $userid = null): bool {
    global $USER, $DB;

    if ($userid === null) {
        $userid = $USER->id ?? 0;
    }

    if ($userid == 0) {
        return false;
    }

    // Get siteadmins from config table
    // This is the Moodle way: config.siteadmins contains comma-separated user IDs
    static $siteadmins = null;

    if ($siteadmins === null) {
        try {
            // Query config table - component defaults to 'core' in database
            // But we'll query by name only since config might not have component field
            $sql = "SELECT * FROM {config} WHERE name = ? LIMIT 1";
            $config = $DB->get_record_sql($sql, ['siteadmins']);

            if ($config && !empty($config->value)) {
                // Convert comma-separated string to array of integers
                $siteadmins = array_map('intval', explode(',', $config->value));
                debugging("Loaded siteadmins from config: " . implode(',', $siteadmins), DEBUG_DEVELOPER);
            } else {
                // Config not found or empty
                debugging("WARNING: config.siteadmins not found or empty in database", DEBUG_DEVELOPER);
                $siteadmins = [];
            }
        } catch (Exception $e) {
            // If config table doesn't exist or query fails, fallback to empty
            debugging("ERROR loading siteadmins from config: " . $e->getMessage(), DEBUG_DEVELOPER);
            $siteadmins = [];
        }
    }

    // Check if user ID is in siteadmins list
    $result = in_array($userid, $siteadmins, true);
    debugging("is_siteadmin($userid) = " . ($result ? 'true' : 'false'), DEBUG_DEVELOPER);

    return $result;
}

/**
 * Get list of site administrator user IDs
 *
 * Returns an array of user IDs who are site administrators.
 * Similar to Moodle's get_admins() function.
 *
 * @return array Array of user IDs
 */
function get_siteadmins(): array {
    global $DB;

    try {
        $config = $DB->get_record('config', ['name' => 'siteadmins']);
        if ($config && !empty($config->value)) {
            return array_map('intval', explode(',', $config->value));
        }
    } catch (Exception $e) {
        debugging('Error retrieving siteadmins: ' . $e->getMessage());
    }

    return [];
}

/**
 * Add a user to site administrators
 *
 * Adds the given user ID to the siteadmins config setting.
 * Similar to Moodle pattern for managing site admins.
 *
 * @param int $userid User ID to add
 * @return bool True on success, false on failure
 */
function add_siteadmin(int $userid): bool {
    global $DB;

    if ($userid <= 0) {
        return false;
    }

    // Verify user exists
    if (!$DB->record_exists('users', ['id' => $userid])) {
        return false;
    }

    // Get current siteadmins
    $siteadmins = get_siteadmins();

    // Check if already a siteadmin
    if (in_array($userid, $siteadmins, true)) {
        return true; // Already a siteadmin
    }

    // Add to list
    $siteadmins[] = $userid;

    // Save to config
    return set_siteadmins($siteadmins);
}

/**
 * Remove a user from site administrators
 *
 * Removes the given user ID from the siteadmins config setting.
 * Similar to Moodle pattern for managing site admins.
 *
 * @param int $userid User ID to remove
 * @return bool True on success, false on failure
 */
function remove_siteadmin(int $userid): bool {
    global $DB;

    if ($userid <= 0) {
        return false;
    }

    // Get current siteadmins
    $siteadmins = get_siteadmins();

    // Check if user is a siteadmin
    $key = array_search($userid, $siteadmins, true);
    if ($key === false) {
        return true; // Not a siteadmin, nothing to do
    }

    // Prevent removing the last siteadmin
    if (count($siteadmins) === 1) {
        debugging('Cannot remove the last site administrator');
        return false;
    }

    // Remove from list
    unset($siteadmins[$key]);
    $siteadmins = array_values($siteadmins); // Re-index

    // Save to config
    return set_siteadmins($siteadmins);
}

/**
 * Set site administrators list
 *
 * Sets the complete list of site administrator user IDs.
 * Internal function used by add_siteadmin() and remove_siteadmin().
 *
 * @param array $userids Array of user IDs
 * @return bool True on success, false on failure
 */
function set_siteadmins(array $userids): bool {
    global $DB;

    // Ensure all values are integers
    $userids = array_map('intval', $userids);

    // Remove duplicates and zeros
    $userids = array_unique(array_filter($userids));

    // Convert to comma-separated string
    $value = implode(',', $userids);

    try {
        // Check if config exists
        $config = $DB->get_record('config', ['name' => 'siteadmins']);

        if ($config) {
            // Update existing record
            $config->value = $value;
            $DB->update_record('config', $config);
        } else {
            // Create new record
            $record = new stdClass();
            $record->name = 'siteadmins';
            $record->value = $value;
            $DB->insert_record('config', $record);
        }

        // Clear static cache in is_siteadmin()
        // This is done by using a hacky approach, but it works
        // We'll call the function with invalid userid to reset cache
        // Actually, we can't do this cleanly with static variables
        // The cache will be cleared on next request automatically

        return true;

    } catch (Exception $e) {
        debugging('Error setting siteadmins: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get a user preference value
 *
 * Similar to Moodle's get_user_preferences() - retrieves a user preference
 * from the database or returns default value if not set.
 *
 * @param string $name Preference name
 * @param mixed $default Default value if preference doesn't exist
 * @param int|null $userid User ID (null = current user)
 * @return mixed Preference value or default
 */
function get_user_preferences($name, $default = null, $userid = null) {
    global $USER, $DB;

    if ($userid === null) {
        $userid = $USER->id ?? 0;
    }

    if ($userid == 0) {
        return $default;
    }

    // Check if user_preferences table exists
    try {
        $record = $DB->get_record('user_preferences', [
            'userid' => $userid,
            'name' => $name
        ]);

        if ($record) {
            return $record->value;
        }
    } catch (Exception $e) {
        // Table doesn't exist yet or other error
        return $default;
    }

    return $default;
}

/**
 * Set a user preference value
 *
 * Similar to Moodle's set_user_preference() - stores a user preference
 * in the database.
 *
 * @param string $name Preference name
 * @param mixed $value Preference value
 * @param int|null $userid User ID (null = current user)
 * @return bool True on success
 */
function set_user_preference($name, $value, $userid = null) {
    global $USER, $DB;

    if ($userid === null) {
        $userid = $USER->id ?? 0;
    }

    if ($userid == 0) {
        return false;
    }

    // Check if user_preferences table exists
    try {
        $existing = $DB->get_record('user_preferences', [
            'userid' => $userid,
            'name' => $name
        ]);

        if ($existing) {
            // Update existing preference
            $existing->value = $value;
            $existing->timemodified = time();
            return $DB->update_record('user_preferences', $existing);
        } else {
            // Insert new preference
            $record = new stdClass();
            $record->userid = $userid;
            $record->name = $name;
            $record->value = $value;
            $record->timemodified = time();
            return $DB->insert_record('user_preferences', $record);
        }
    } catch (Exception $e) {
        // Table doesn't exist yet or other error
        return false;
    }
}

/**
 * Unset a user preference
 *
 * Similar to Moodle's unset_user_preference() - removes a user preference
 * from the database.
 *
 * @param string $name Preference name
 * @param int|null $userid User ID (null = current user)
 * @return bool True on success
 */
function unset_user_preference($name, $userid = null) {
    global $USER, $DB;

    if ($userid === null) {
        $userid = $USER->id ?? 0;
    }

    if ($userid == 0) {
        return false;
    }

    try {
        return $DB->delete_records('user_preferences', [
            'userid' => $userid,
            'name' => $name
        ]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generate a random string
 *
 * @param int $length Length of string to generate
 * @return string Random string
 */
function random_string($length = 15) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $count = strlen($chars);
    $random = '';
    for ($i = 0; $i < $length; $i++) {
        $random .= $chars[random_int(0, $count - 1)];
    }
    return $random;
}

/**
 * Strip query string from URL
 *
 * @param string $url URL to strip
 * @return string URL without query string
 */
function strip_querystring($url) {
    if ($pos = strpos($url, '?')) {
        return substr($url, 0, $pos);
    }
    return $url;
}

/**
 * Get current URL
 *
 * @return string Current URL
 */
function qualified_me() {
    global $CFG;

    $url = $_SERVER['REQUEST_URI'] ?? '/';

    // Remove CFG->wwwroot from beginning if present
    if (isset($CFG->wwwroot)) {
        $parsed = parse_url($CFG->wwwroot);
        if (isset($parsed['path'])) {
            $basepath = rtrim($parsed['path'], '/');
            if (strpos($url, $basepath) === 0) {
                $url = substr($url, strlen($basepath));
            }
        }
    }

    return $CFG->wwwroot . $url;
}

/**
 * Escape HTML special characters
 *
 * Similar to Moodle's s() function - shortcut for htmlspecialchars
 *
 * @param string|mixed $var Variable to escape
 * @return string Escaped string
 */
function s($var) {
    if ($var === null || $var === false) {
        return '';
    }
    if (is_object($var) || is_array($var)) {
        return '';
    }
    return htmlspecialchars((string)$var, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Get navigation HTML
 *
 * Renders the Moodle-style navigation sidebar for the current page.
 * This function initializes and renders the navigation manager.
 *
 * @return string Navigation HTML
 */
function get_navigation_html(): string {
    \core\navigation\nav_manager::init();
    return \core\navigation\nav_manager::render();
}

// ============================================
// DEVELOPER MODE & .ENV HELPER FUNCTIONS
// ============================================

/**
 * Get environment variable with default fallback
 *
 * @param string $key Variable name
 * @param mixed $default Default value if not set
 * @return mixed Variable value or default
 */
function env(string $key, $default = null) {
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    // Convert string booleans to actual booleans
    if ($value === 'true' || $value === '(true)') {
        return true;
    }

    if ($value === 'false' || $value === '(false)') {
        return false;
    }

    // Convert string null to actual null
    if ($value === 'null' || $value === '(null)') {
        return null;
    }

    return $value;
}

/**
 * Check if developer mode is enabled
 *
 * @return bool True if developer mode is enabled
 */
function is_developer_mode(): bool {
    global $CFG;

    // Check .env setting first
    if (env('DEVELOPER_MODE') === true) {
        return true;
    }

    // Check database config setting
    $devmode = get_config('core', 'developer_mode');
    if ($devmode !== null && $devmode) {
        return true;
    }

    // Check APP_ENV
    if (env('APP_ENV') === 'development') {
        return true;
    }

    return false;
}

/**
 * Check if SQL queries should be logged/shown
 *
 * @return bool True if SQL queries should be shown
 */
function show_sql_queries(): bool {
    if (!is_developer_mode()) {
        return false;
    }

    return env('SHOW_SQL_QUERIES', false) === true ||
           get_config('core', 'show_sql_queries');
}

/**
 * Check if performance info should be shown
 *
 * @return bool True if performance info should be shown
 */
function show_performance_info(): bool {
    if (!is_developer_mode()) {
        return false;
    }

    return env('SHOW_PERFORMANCE_INFO', false) === true ||
           get_config('core', 'show_performance_info');
}

/**
 * Check if developer toolbar should be shown
 *
 * @return bool True if dev toolbar should be shown
 */
function show_dev_toolbar(): bool {
    if (!is_developer_mode()) {
        return false;
    }

    return env('SHOW_DEV_TOOLBAR', false) === true ||
           get_config('core', 'show_dev_toolbar');
}

/**
 * Check if template hints should be shown
 *
 * @return bool True if template hints should be shown
 */
function show_template_hints(): bool {
    if (!is_developer_mode()) {
        return false;
    }

    return env('SHOW_TEMPLATE_HINTS', false) === true ||
           get_config('core', 'show_template_hints');
}

/**
 * Get debug level from environment or database
 *
 * @return int Debug level (0-4)
 */
function get_debug_level(): int {
    global $CFG;

    // Check CFG first (loaded from database in setup.php)
    if (isset($CFG->debug)) {
        return (int)$CFG->debug;
    }

    // Fallback to .env
    $level = env('DEBUG_LEVEL', 0);
    return (int)$level;
}

/**
 * Check if debug display is enabled
 *
 * @return bool True if errors should be displayed
 */
function is_debug_display(): bool {
    global $CFG;

    if (isset($CFG->debugdisplay)) {
        return (bool)$CFG->debugdisplay;
    }

    return env('DEBUG_DISPLAY', false) === true;
}

/**
 * Get all developer settings
 *
 * Returns an associative array with all developer settings
 * from both .env and database.
 *
 * @return array Developer settings
 */
function get_developer_settings(): array {
    return [
        'developer_mode' => is_developer_mode(),
        'debug_level' => get_debug_level(),
        'debug_display' => is_debug_display(),
        'show_sql_queries' => show_sql_queries(),
        'show_performance_info' => show_performance_info(),
        'show_dev_toolbar' => show_dev_toolbar(),
        'show_template_hints' => show_template_hints(),
        'cache_enabled' => env('CACHE_ENABLED_DEV', true) === true,
        'template_cache_enabled' => env('TEMPLATE_CACHE_ENABLED', true) === true,
        'string_cache_enabled' => env('STRING_CACHE_ENABLED', true) === true,
    ];
}

// ============================================
// CONSTANTS
// ============================================

/**
 * Site ID constant - represents the site-level course
 */
if (!defined('SITEID')) {
    define('SITEID', 1);
}

/**
 * Risk bitmask constants for capabilities
 */
if (!defined('RISK_CONFIG')) {
    define('RISK_CONFIG', 1);
    define('RISK_PERSONAL', 2);
    define('RISK_SPAM', 4);
    define('RISK_XSS', 8);
    define('RISK_MANAGETRUST', 16);
    define('RISK_DATALOSS', 32);
}

/**
 * Capability permission constants
 */
if (!defined('CAP_ALLOW')) {
    define('CAP_ALLOW', 1);
    define('CAP_PREVENT', -1);
    define('CAP_PROHIBIT', -1000);
    define('CAP_INHERIT', 0);
}

/**
 * Context level constants
 */
if (!defined('CONTEXT_SYSTEM')) {
    define('CONTEXT_SYSTEM', 10);
    define('CONTEXT_USER', 30);
    define('CONTEXT_COURSE', 50);
    define('CONTEXT_MODULE', 70);
}

// ============================================
// ADDITIONAL HELPER FUNCTIONS
// ============================================

/**
 * Format date/time using userdate format
 *
 * Similar to Moodle's userdate() function.
 *
 * @param int $timestamp Unix timestamp
 * @param string $format strftime format string
 * @param int|string $timezone Timezone (optional)
 * @param bool $fixday Remove leading zero from day
 * @return string Formatted date string
 */
function userdate(int $timestamp, string $format = '%d %B %Y, %H:%M', $timezone = 99, bool $fixday = true): string {
    global $CFG;

    if ($timestamp === 0) {
        return '';
    }

    // Default format if not provided
    if (empty($format)) {
        $format = '%d %B %Y, %H:%M';
    }

    // Handle timezone
    if ($timezone == 99 || $timezone === 'server') {
        $timezone = $CFG->timezone ?? date_default_timezone_get();
    }

    // Create DateTime object
    $date = new DateTime();
    $date->setTimestamp($timestamp);

    if (is_string($timezone)) {
        try {
            $date->setTimezone(new DateTimeZone($timezone));
        } catch (Exception $e) {
            // Use default timezone
        }
    }

    // Convert strftime format to DateTime format
    $dateFormat = strtr($format, [
        '%d' => 'd',
        '%e' => 'j',
        '%m' => 'm',
        '%n' => 'n',
        '%Y' => 'Y',
        '%y' => 'y',
        '%H' => 'H',
        '%I' => 'h',
        '%M' => 'i',
        '%S' => 's',
        '%p' => 'A',
        '%P' => 'a',
        '%A' => 'l',
        '%a' => 'D',
        '%B' => 'F',
        '%b' => 'M',
        '%j' => 'z',
        '%W' => 'W',
        '%U' => 'W',
        '%%' => '%',
    ]);

    return $date->format($dateFormat);
}

/**
 * Get full name of a user
 *
 * Similar to Moodle's fullname() function.
 *
 * @param object|array $user User object or array with firstname/lastname
 * @param bool $override Whether to override name display settings
 * @return string Full name
 */
function fullname($user, bool $override = false): string {
    if (is_array($user)) {
        $user = (object)$user;
    }

    $firstname = $user->firstname ?? '';
    $lastname = $user->lastname ?? '';

    $name = trim($firstname . ' ' . $lastname);

    if (empty($name) && isset($user->username)) {
        return $user->username;
    }

    if (empty($name) && isset($user->id)) {
        return 'User ' . $user->id;
    }

    return $name ?: 'Unknown';
}

/**
 * Get context by name
 *
 * Helper function for context access.
 *
 * @param string $contextname Context name (system, user, course, module)
 * @return object Context object
 */
function context_system() {
    return \core\rbac\context::system();
}

function context_user(int $userid) {
    return \core\rbac\context::user($userid);
}

function context_course(int $courseid) {
    return \core\rbac\context::course($courseid);
}
