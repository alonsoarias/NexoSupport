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
 * @return void
 */
function require_login(): void {
    global $USER;

    if (!isset($USER->id)) {
        redirect('/login');
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
        throw new \moodle_exception('nopermissions', 'error', '', $capability);
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
 * @return void
 */
function require_sesskey(): void {
    $sesskey = optional_param('sesskey', null, 'raw');

    if ($sesskey !== sesskey()) {
        throw new \moodle_exception('invalidsesskey', 'error');
    }
}

/**
 * Get session key
 *
 * @return string
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
    return \core\output\template_manager::render($templatename, $context);
}

/**
 * Coding exception
 */
class coding_exception extends \Exception {
}

/**
 * Moodle exception (compatible)
 */
class moodle_exception extends \Exception {
    public function __construct($errorcode, $module = '', $link = '', $a = null, $debuginfo = null) {
        $message = get_string($errorcode, $module, $a);
        parent::__construct($message);
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
 */
define('MATURITY_ALPHA', 50);
define('MATURITY_BETA', 100);
define('MATURITY_RC', 150);
define('MATURITY_STABLE', 200);

/**
 * Constantes de SQL params
 */
define('SQL_PARAMS_QM', 0);     // Question mark placeholders (?)
define('SQL_PARAMS_NAMED', 1);  // Named placeholders (:param)

/**
 * Constantes de debugging
 */
define('DEBUG_NONE', 0);
define('DEBUG_MINIMAL', 5);
define('DEBUG_NORMAL', 15);
define('DEBUG_ALL', 32767);
define('DEBUG_DEVELOPER', 38911);

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
 * Similar to Moodle's is_siteadmin() - checks if user has 'administrator' role
 * in system context.
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

    // Check if user has administrator role in system context
    $syscontext = \core\rbac\context::system();

    $sql = "SELECT COUNT(*)
            FROM {role_assignments} ra
            JOIN {roles} r ON r.id = ra.roleid
            WHERE ra.userid = ?
            AND ra.contextid = ?
            AND r.shortname = 'administrator'";

    $count = $DB->count_records_sql($sql, [$userid, $syscontext->id]);

    return $count > 0;
}
