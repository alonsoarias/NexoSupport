<?php
/**
 * Environment Checking Library
 *
 * Functions for validating system requirements and environment.
 * Similar to Moodle's lib/environmentlib.php
 *
 * This library provides:
 * - System requirements validation
 * - PHP version and extension checking
 * - Database compatibility checking
 * - Environment XML parsing
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// Environment check result levels
define('ENV_SELECT_NEWER', 0);
define('ENV_SELECT_RELEASE', 1);
define('ENV_SELECT_BRANCH', 2);

// Result status constants
define('ENVIRONMENT_PASS', 0);
define('ENVIRONMENT_WARN', 1);
define('ENVIRONMENT_FAIL', 2);

/**
 * Check NexoSupport environment meets requirements
 *
 * Main function to check if the server environment meets
 * the requirements for running NexoSupport.
 *
 * @param string $version Version to check requirements for
 * @param int $env_select How to select environment requirements
 * @return array [status (bool), results (array)]
 */
function check_nexosupport_environment($version, $env_select = ENV_SELECT_RELEASE) {
    global $CFG;

    $results = [];
    $status = true;

    // Load requirements from XML
    $requirements = environment_get_requirements($version);

    // PHP Version check
    $phpcheck = environment_check_php($requirements);
    $results[] = $phpcheck;
    if ($phpcheck['status'] === ENVIRONMENT_FAIL) {
        $status = false;
    }

    // PHP Extensions check
    $extchecks = environment_check_php_extensions($requirements);
    foreach ($extchecks as $check) {
        $results[] = $check;
        if ($check['status'] === ENVIRONMENT_FAIL) {
            $status = false;
        }
    }

    // PHP Settings check
    $settingchecks = environment_check_php_settings($requirements);
    foreach ($settingchecks as $check) {
        $results[] = $check;
        if ($check['status'] === ENVIRONMENT_FAIL) {
            $status = false;
        }
    }

    // Database check
    if (!empty($CFG->dbtype)) {
        $dbcheck = environment_check_database($requirements);
        $results[] = $dbcheck;
        if ($dbcheck['status'] === ENVIRONMENT_FAIL) {
            $status = false;
        }
    }

    // Directory permissions check
    $dircheck = environment_check_directories();
    $results[] = $dircheck;
    if ($dircheck['status'] === ENVIRONMENT_FAIL) {
        $status = false;
    }

    return [$status, $results];
}

/**
 * Get requirements from environment.xml
 *
 * Parses the admin/environment.xml file to get requirements
 * for a specific version.
 *
 * @param string $version Version to get requirements for
 * @return array Requirements array
 */
function environment_get_requirements($version) {
    global $CFG;

    $requirements = [
        'php' => [
            'version' => '8.1.0',
            'level' => 'required'
        ],
        'php_extensions' => [],
        'php_settings' => [],
        'databases' => []
    ];

    // Try to load from XML
    $xmlfile = $CFG->dirroot . '/admin/environment.xml';
    if (file_exists($xmlfile)) {
        $xml = simplexml_load_file($xmlfile);
        if ($xml !== false) {
            $requirements = environment_parse_xml($xml, $version);
        }
    }

    // Add default required extensions if not in XML
    $default_extensions = [
        'pdo' => ['level' => 'required', 'name' => 'PDO'],
        'json' => ['level' => 'required', 'name' => 'JSON'],
        'mbstring' => ['level' => 'required', 'name' => 'Multibyte String'],
        'session' => ['level' => 'required', 'name' => 'Session'],
        'ctype' => ['level' => 'required', 'name' => 'Ctype'],
        'fileinfo' => ['level' => 'required', 'name' => 'Fileinfo'],
        'openssl' => ['level' => 'required', 'name' => 'OpenSSL'],
        'curl' => ['level' => 'required', 'name' => 'cURL'],
        'dom' => ['level' => 'required', 'name' => 'DOM'],
        'simplexml' => ['level' => 'required', 'name' => 'SimpleXML'],
        'xml' => ['level' => 'required', 'name' => 'XML'],
        'intl' => ['level' => 'optional', 'name' => 'Internationalization'],
        'gd' => ['level' => 'optional', 'name' => 'GD Graphics'],
        'zip' => ['level' => 'optional', 'name' => 'Zip'],
        'soap' => ['level' => 'optional', 'name' => 'SOAP'],
    ];

    foreach ($default_extensions as $ext => $info) {
        if (!isset($requirements['php_extensions'][$ext])) {
            $requirements['php_extensions'][$ext] = $info;
        }
    }

    // Add default PHP settings
    $default_settings = [
        'memory_limit' => ['value' => '128M', 'level' => 'required'],
        'post_max_size' => ['value' => '64M', 'level' => 'optional'],
        'upload_max_filesize' => ['value' => '64M', 'level' => 'optional'],
        'max_execution_time' => ['value' => '120', 'level' => 'optional'],
    ];

    foreach ($default_settings as $setting => $info) {
        if (!isset($requirements['php_settings'][$setting])) {
            $requirements['php_settings'][$setting] = $info;
        }
    }

    return $requirements;
}

/**
 * Parse environment XML file
 *
 * @param SimpleXMLElement $xml XML object
 * @param string $version Target version
 * @return array Parsed requirements
 */
function environment_parse_xml($xml, $version) {
    $requirements = [
        'php' => ['version' => '8.1.0', 'level' => 'required'],
        'php_extensions' => [],
        'php_settings' => [],
        'databases' => []
    ];

    // Find matching version
    foreach ($xml->NEXOSUPPORT as $nexo) {
        $xmlversion = (string)$nexo['version'];

        // Check if this version applies
        if (version_compare($version, $xmlversion, '>=')) {
            // PHP version
            foreach ($nexo->PHP as $php) {
                $requirements['php']['version'] = (string)$php['version'];
                $requirements['php']['level'] = (string)$php['level'] ?: 'required';
            }

            // PHP Extensions
            foreach ($nexo->PHP_EXTENSION as $ext) {
                $name = (string)$ext['name'];
                $requirements['php_extensions'][$name] = [
                    'name' => $name,
                    'level' => (string)$ext['level'] ?: 'required'
                ];
            }

            // PHP Settings
            foreach ($nexo->PHP_SETTING as $setting) {
                $name = (string)$setting['name'];
                $requirements['php_settings'][$name] = [
                    'value' => (string)$setting['value'],
                    'level' => (string)$setting['level'] ?: 'required'
                ];
            }

            // Databases
            foreach ($nexo->DATABASE as $db) {
                $name = (string)$db['name'];
                $requirements['databases'][$name] = [
                    'name' => $name,
                    'version' => (string)$db['version'],
                    'level' => (string)$db['level'] ?: 'required'
                ];
            }
        }
    }

    return $requirements;
}

/**
 * Check PHP version
 *
 * @param array $requirements Requirements array
 * @return array Check result
 */
function environment_check_php($requirements) {
    $required = $requirements['php']['version'] ?? '8.1.0';
    $level = $requirements['php']['level'] ?? 'required';

    $current = PHP_VERSION;
    $pass = version_compare($current, $required, '>=');

    $status = ENVIRONMENT_PASS;
    if (!$pass) {
        $status = ($level === 'required') ? ENVIRONMENT_FAIL : ENVIRONMENT_WARN;
    }

    return [
        'type' => 'php',
        'name' => 'PHP Version',
        'info' => $required,
        'current' => $current,
        'level' => $level,
        'status' => $status,
        'message' => $pass ? 'OK' : "Se requiere PHP $required o superior"
    ];
}

/**
 * Check PHP extensions
 *
 * @param array $requirements Requirements array
 * @return array Array of check results
 */
function environment_check_php_extensions($requirements) {
    $results = [];

    foreach ($requirements['php_extensions'] as $ext => $info) {
        $name = $info['name'] ?? $ext;
        $level = $info['level'] ?? 'required';

        // Special case for database extensions
        if ($ext === 'pdo_mysql' || $ext === 'mysqli') {
            $loaded = extension_loaded('pdo_mysql') || extension_loaded('mysqli');
        } elseif ($ext === 'pdo_pgsql') {
            $loaded = extension_loaded('pdo_pgsql');
        } else {
            $loaded = extension_loaded($ext);
        }

        $status = ENVIRONMENT_PASS;
        if (!$loaded) {
            $status = ($level === 'required') ? ENVIRONMENT_FAIL : ENVIRONMENT_WARN;
        }

        $results[] = [
            'type' => 'php_extension',
            'name' => "Extensión PHP: $name",
            'info' => $level === 'required' ? 'Requerido' : 'Opcional',
            'current' => $loaded ? 'Cargado' : 'No cargado',
            'level' => $level,
            'status' => $status,
            'message' => $loaded ? 'OK' : ($level === 'required' ? "Extensión $ext requerida" : "Extensión $ext recomendada")
        ];
    }

    return $results;
}

/**
 * Check PHP settings
 *
 * @param array $requirements Requirements array
 * @return array Array of check results
 */
function environment_check_php_settings($requirements) {
    $results = [];

    foreach ($requirements['php_settings'] as $setting => $info) {
        $required = $info['value'];
        $level = $info['level'] ?? 'optional';

        $current = ini_get($setting);
        $pass = environment_compare_setting($current, $required, $setting);

        $status = ENVIRONMENT_PASS;
        if (!$pass) {
            $status = ($level === 'required') ? ENVIRONMENT_FAIL : ENVIRONMENT_WARN;
        }

        $results[] = [
            'type' => 'php_setting',
            'name' => "PHP Setting: $setting",
            'info' => $required,
            'current' => $current ?: 'no establecido',
            'level' => $level,
            'status' => $status,
            'message' => $pass ? 'OK' : "Se recomienda $setting >= $required"
        ];
    }

    return $results;
}

/**
 * Compare PHP setting value
 *
 * @param string $current Current value
 * @param string $required Required value
 * @param string $setting Setting name
 * @return bool True if current meets requirement
 */
function environment_compare_setting($current, $required, $setting) {
    // Memory-related settings
    if (in_array($setting, ['memory_limit', 'post_max_size', 'upload_max_filesize'])) {
        $currentbytes = environment_convert_to_bytes($current);
        $requiredbytes = environment_convert_to_bytes($required);

        // -1 means unlimited
        if ($currentbytes === -1) {
            return true;
        }

        return $currentbytes >= $requiredbytes;
    }

    // Numeric comparison
    return (int)$current >= (int)$required;
}

/**
 * Convert memory string to bytes
 *
 * @param string $val Memory value (e.g., '128M')
 * @return int Bytes (-1 for unlimited)
 */
function environment_convert_to_bytes($val) {
    $val = trim($val);

    if ($val === '-1') {
        return -1;
    }

    $last = strtolower($val[strlen($val) - 1]);
    $val = (int)$val;

    switch ($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}

/**
 * Check database compatibility
 *
 * @param array $requirements Requirements array
 * @return array Check result
 */
function environment_check_database($requirements) {
    global $CFG, $DB;

    $dbtype = $CFG->dbtype ?? '';

    // Map dbtype to generic name
    $dbmap = [
        'mysqli' => 'mysql',
        'mariadb' => 'mysql',
        'pgsql' => 'postgres'
    ];

    $generictype = $dbmap[$dbtype] ?? $dbtype;

    // Get required version
    $required = '';
    $level = 'required';

    if (isset($requirements['databases'][$generictype])) {
        $required = $requirements['databases'][$generictype]['version'];
        $level = $requirements['databases'][$generictype]['level'];
    } elseif (isset($requirements['databases']['mysql']) && ($dbtype === 'mysqli' || $dbtype === 'mariadb')) {
        $required = $requirements['databases']['mysql']['version'];
        $level = $requirements['databases']['mysql']['level'];
    }

    // Get current version
    $current = 'Desconocido';
    try {
        if (isset($DB)) {
            $current = $DB->get_server_info();
        }
    } catch (Exception $e) {
        // Can't get version
    }

    // Compare versions
    $pass = true;
    if (!empty($required) && $current !== 'Desconocido') {
        // Extract version number
        preg_match('/[\d.]+/', $current, $matches);
        $currentversion = $matches[0] ?? '0';
        $pass = version_compare($currentversion, $required, '>=');
    }

    $status = ENVIRONMENT_PASS;
    if (!$pass) {
        $status = ($level === 'required') ? ENVIRONMENT_FAIL : ENVIRONMENT_WARN;
    }

    return [
        'type' => 'database',
        'name' => 'Base de Datos (' . ucfirst($dbtype) . ')',
        'info' => $required ?: 'N/A',
        'current' => $current,
        'level' => $level,
        'status' => $status,
        'message' => $pass ? 'OK' : "Se requiere versión $required o superior"
    ];
}

/**
 * Check directory permissions
 *
 * @return array Check result
 */
function environment_check_directories() {
    global $CFG;

    $issues = [];

    // Check dataroot
    if (!empty($CFG->dataroot)) {
        if (!file_exists($CFG->dataroot)) {
            $issues[] = 'dataroot no existe';
        } elseif (!is_writable($CFG->dataroot)) {
            $issues[] = 'dataroot no es escribible';
        }
    }

    // Check var directory
    $vardir = $CFG->dirroot . '/var';
    if (file_exists($vardir) && !is_writable($vardir)) {
        $issues[] = 'var/ no es escribible';
    }

    $pass = empty($issues);

    return [
        'type' => 'directories',
        'name' => 'Permisos de Directorios',
        'info' => 'Escribibles',
        'current' => $pass ? 'OK' : implode(', ', $issues),
        'level' => 'required',
        'status' => $pass ? ENVIRONMENT_PASS : ENVIRONMENT_FAIL,
        'message' => $pass ? 'OK' : 'Algunos directorios no tienen permisos correctos'
    ];
}

/**
 * Check if core tables exist
 *
 * @return bool True if core tables exist
 */
function core_tables_exist() {
    global $DB;

    try {
        $dbman = $DB->get_manager();

        // Check for essential tables
        $tables = ['config', 'users', 'contexts', 'roles'];

        foreach ($tables as $table) {
            if (!$dbman->table_exists($table)) {
                return false;
            }
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Normalize version string
 *
 * @param string $release Release string
 * @return string Normalized version
 */
function normalize_version($release) {
    // Extract version number from release string
    // e.g., "1.1.27 (Build: 2025011827)" -> "1.1.27"
    if (preg_match('/^([\d.]+)/', $release, $matches)) {
        return $matches[1];
    }
    return $release;
}

/**
 * Get environment results as HTML
 *
 * @param array $results Results from check_nexosupport_environment
 * @return string HTML output
 */
function environment_results_html($results) {
    $html = '<table class="environment-results" style="width: 100%; border-collapse: collapse;">';
    $html .= '<thead><tr>';
    $html .= '<th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">Requisito</th>';
    $html .= '<th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">Requerido</th>';
    $html .= '<th style="text-align: left; padding: 10px; border-bottom: 2px solid #ddd;">Actual</th>';
    $html .= '<th style="text-align: center; padding: 10px; border-bottom: 2px solid #ddd;">Estado</th>';
    $html .= '</tr></thead><tbody>';

    foreach ($results as $result) {
        $rowclass = '';
        $statusicon = '✓';
        $statuscolor = '#4caf50';

        if ($result['status'] === ENVIRONMENT_FAIL) {
            $rowclass = 'background: #ffebee;';
            $statusicon = '✗';
            $statuscolor = '#f44336';
        } elseif ($result['status'] === ENVIRONMENT_WARN) {
            $rowclass = 'background: #fff8e1;';
            $statusicon = '⚠';
            $statuscolor = '#ff9800';
        }

        $html .= '<tr style="' . $rowclass . '">';
        $html .= '<td style="padding: 10px; border-bottom: 1px solid #eee;">' . htmlspecialchars($result['name']) . '</td>';
        $html .= '<td style="padding: 10px; border-bottom: 1px solid #eee;">' . htmlspecialchars($result['info']) . '</td>';
        $html .= '<td style="padding: 10px; border-bottom: 1px solid #eee;">' . htmlspecialchars($result['current']) . '</td>';
        $html .= '<td style="padding: 10px; border-bottom: 1px solid #eee; text-align: center; color: ' . $statuscolor . '; font-weight: bold;">' . $statusicon . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    return $html;
}

/**
 * Check if all plugins are OK
 *
 * Similar to Moodle's function to check if all plugins
 * meet their requirements before upgrade.
 *
 * @param float $version Target core version
 * @param array &$failed Array to receive failed plugins
 * @param string $branch Version branch
 * @return bool True if all plugins OK
 */
function all_plugins_ok($version, &$failed = [], $branch = '') {
    $pluginman = \core\plugin\plugin_manager::instance();

    $allok = true;
    $failed = [];

    $plugins = $pluginman->get_all_plugins();

    foreach ($plugins as $type => $typeplugins) {
        foreach ($typeplugins as $name => $plugin) {
            // Check dependencies
            if (!empty($plugin->dependencies)) {
                foreach ($plugin->dependencies as $dep => $depversion) {
                    // Check if dependency is met
                    $depmet = $pluginman->check_dependency($dep, $depversion);
                    if (!$depmet) {
                        $failed[] = [
                            'plugin' => "{$type}_{$name}",
                            'dependency' => $dep,
                            'required' => $depversion
                        ];
                        $allok = false;
                    }
                }
            }

            // Check core version requirement
            if (!empty($plugin->requires)) {
                if ($plugin->requires > $version) {
                    $failed[] = [
                        'plugin' => "{$type}_{$name}",
                        'dependency' => 'core',
                        'required' => $plugin->requires
                    ];
                    $allok = false;
                }
            }
        }
    }

    return $allok;
}

/**
 * Print environment check table (Moodle style)
 *
 * @param array $results Results array
 * @param bool $status Overall status
 * @return void
 */
function print_environment_table($results, $status) {
    echo '<h3>Verificación del Ambiente</h3>';

    if ($status) {
        echo '<div class="alert alert-success">';
        echo '<strong>✓ Todos los requisitos del sistema han sido verificados.</strong>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-danger">';
        echo '<strong>✗ Algunos requisitos del sistema no se cumplen.</strong>';
        echo '</div>';
    }

    echo environment_results_html($results);
}
