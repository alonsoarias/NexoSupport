<?php
/**
 * Upgrader Class
 *
 * Centraliza toda la lógica del proceso de actualización.
 * Separa completamente la lógica de negocio de la presentación.
 *
 * @package core\install
 */

namespace core\install;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Main Upgrader Class
 */
class upgrader {

    /** @var int Versión en base de datos */
    private ?int $db_version = null;

    /** @var int Versión en código */
    private ?int $code_version = null;

    /** @var string Release string */
    private string $release = '';

    /** @var array Log de operaciones */
    private array $log = [];

    /** @var array Errores */
    private array $errors = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->detect_versions();
    }

    /**
     * Detectar versiones actuales
     *
     * @return void
     */
    private function detect_versions(): void {
        global $DB;

        // Obtener versión de BD
        try {
            $sql = "SELECT value FROM {config} WHERE name = ? AND component = ? LIMIT 1";
            $result = $DB->get_record_sql($sql, ['version', 'core']);

            if ($result) {
                $this->db_version = (int)$result->value;
            } else {
                // Intentar sin component (backward compatibility)
                $result = $DB->get_record('config', ['name' => 'version']);
                $this->db_version = $result ? (int)$result->value : null;
            }
        } catch (\Exception $e) {
            $this->db_version = null;
            $this->add_error(get_string('upgrader_no_db', 'install') . ': ' . $e->getMessage());
        }

        // Obtener versión de código
        try {
            $versionfile = BASE_DIR . '/lib/version.php';
            if (file_exists($versionfile)) {
                $plugin = new \stdClass();
                include($versionfile);
                $this->code_version = $plugin->version ?? null;
                $this->release = $plugin->release ?? get_string('unknown', 'core');
            }
        } catch (\Exception $e) {
            $this->code_version = null;
            $this->add_error($e->getMessage());
        }
    }

    /**
     * ¿Necesita actualización?
     *
     * @return bool
     */
    public function needs_upgrade(): bool {
        if ($this->db_version === null || $this->code_version === null) {
            return false;
        }

        return $this->code_version > $this->db_version;
    }

    /**
     * Obtener versión de BD
     *
     * @return int|null
     */
    public function get_db_version(): ?int {
        return $this->db_version;
    }

    /**
     * Obtener versión de código
     *
     * @return int|null
     */
    public function get_code_version(): ?int {
        return $this->code_version;
    }

    /**
     * Obtener release string
     *
     * @return string
     */
    public function get_release(): string {
        return $this->release;
    }

    /**
     * Ejecutar upgrade
     *
     * @return array [bool success, array log, array errors]
     */
    public function execute(): array {
        $this->log = [];
        $this->errors = [];

        try {
            if (!$this->needs_upgrade()) {
                $this->add_log(get_string('upgrader_no_upgrade_needed', 'install'));
                return ['success' => true, 'log' => $this->log, 'errors' => $this->errors];
            }

            $this->add_log(get_string('upgrader_log_start', 'install', $this->db_version));
            $this->add_log(get_string('upgrader_log_requirements', 'install'));
            $this->add_log(get_string('upgrader_log_backup', 'install'));

            // Cargar función de upgrade
            require_once(BASE_DIR . '/lib/upgrade.php');

            $this->add_log(get_string('upgrader_log_executing', 'install'));

            // Ejecutar upgrade con buffer de salida
            ob_start();
            $success = xmldb_core_upgrade($this->db_version ?? 0);
            $output = ob_get_clean();

            if ($output) {
                $this->add_log("Output:\n" . $output);
            }

            if ($success) {
                $this->add_log(get_string('upgrader_log_complete', 'install'));

                // Process plugins (install/upgrade)
                $this->add_log(get_string('upgrader_log_plugins', 'install'));
                $this->process_plugins();

                // Limpiar cache
                $this->add_log(get_string('upgrader_log_purging', 'install'));
                $this->purge_caches();

                $this->add_log(get_string('upgrader_log_version_updated', 'install', $this->code_version));

                return ['success' => true, 'log' => $this->log, 'errors' => $this->errors];
            } else {
                $this->add_error(get_string('upgrader_failed', 'install', 'upgrade function returned false'));
                return ['success' => false, 'log' => $this->log, 'errors' => $this->errors];
            }

        } catch (\Exception $e) {
            $this->add_error(get_string('upgrader_failed', 'install', $e->getMessage()));
            $this->add_error('Stack trace: ' . $e->getTraceAsString());

            return ['success' => false, 'log' => $this->log, 'errors' => $this->errors];
        }
    }

    /**
     * Process all plugins (install new, upgrade existing)
     *
     * @return void
     */
    private function process_plugins(): void {
        try {
            $pluginman = \core\plugin\plugin_manager::instance();
            $updates = $pluginman->get_plugins_to_update();

            if (empty($updates)) {
                $this->add_log('  - ' . get_string('upgrader_plugins_uptodate', 'install'));
                return;
            }

            foreach ($updates as $info) {
                $component = $info->component ?? "{$info->type}_{$info->name}";
                $status = $info->status ?? 'new';

                if ($status === \core\plugin\plugin_manager::STATUS_NEW) {
                    $this->add_log("  - " . get_string('upgrader_plugin_installing', 'install', $component));
                    $result = $pluginman->install_plugin($info->type, $info->name);
                } else {
                    $this->add_log("  - " . get_string('upgrader_plugin_upgrading', 'install', $component));
                    $result = $pluginman->upgrade_plugin($info->type, $info->name);
                }

                if ($result) {
                    $this->add_log("    ✓ " . get_string('success', 'core'));
                } else {
                    $this->add_log("    ✗ " . get_string('failed', 'core'));
                    $this->add_error(get_string('upgrader_plugin_failed', 'install', $component));
                }
            }

            // Reset plugin caches
            \core\plugin\plugin_manager::reset_caches();

        } catch (\Exception $e) {
            $this->add_error(get_string('upgrader_plugins_error', 'install') . ': ' . $e->getMessage());
        }
    }

    /**
     * Limpiar cachés del sistema
     *
     * @return void
     */
    private function purge_caches(): void {
        $cache_dirs = [
            BASE_DIR . '/var/cache',
        ];

        foreach ($cache_dirs as $dir) {
            if (is_dir($dir)) {
                $this->clear_directory($dir);
            }
        }
    }

    /**
     * Limpiar directorio recursivamente
     *
     * @param string $dir
     * @return void
     */
    private function clear_directory(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->clear_directory($path);
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
    }

    /**
     * Agregar entrada al log
     *
     * @param string $message
     * @return void
     */
    private function add_log(string $message): void {
        $this->log[] = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    }

    /**
     * Agregar error
     *
     * @param string $error
     * @return void
     */
    private function add_error(string $error): void {
        $this->errors[] = $error;
        $this->log[] = '[ERROR] ' . $error;
    }

    /**
     * Obtener log completo
     *
     * @return array
     */
    public function get_log(): array {
        return $this->log;
    }

    /**
     * Obtener errores
     *
     * @return array
     */
    public function get_errors(): array {
        return $this->errors;
    }

    /**
     * ¿Tiene errores?
     *
     * @return bool
     */
    public function has_errors(): bool {
        return !empty($this->errors);
    }

    /**
     * Verificar requisitos para upgrade
     *
     * @return array [bool success, array issues]
     */
    public function check_requirements(): array {
        $issues = [];

        // Verificar PHP
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            $issues[] = 'PHP 8.1.0 o superior es requerido. Versión actual: ' . PHP_VERSION;
        }

        // Verificar extensiones
        $required_extensions = ['pdo', 'mbstring', 'json'];
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $issues[] = "Extensión requerida no encontrada: $ext";
            }
        }

        // Verificar permisos de escritura
        $writable_dirs = [BASE_DIR . '/var'];
        foreach ($writable_dirs as $dir) {
            if (!is_writable($dir)) {
                $issues[] = "Directorio no escribible: $dir";
            }
        }

        // Verificar conexión a BD
        global $DB;
        if (!$DB || !$DB->get_pdo()) {
            $issues[] = 'No hay conexión a la base de datos';
        }

        return ['success' => empty($issues), 'issues' => $issues];
    }

    /**
     * Obtener información de la actualización
     *
     * @return array
     */
    public function get_upgrade_info(): array {
        $info = [
            'db_version' => $this->db_version,
            'code_version' => $this->code_version,
            'release' => $this->release,
            'needs_upgrade' => $this->needs_upgrade(),
            'version_diff' => $this->code_version - $this->db_version
        ];

        // Obtener lista de upgrades que se ejecutarán
        if ($this->needs_upgrade()) {
            $info['upgrades_to_execute'] = $this->get_upgrades_list();
        }

        return $info;
    }

    /**
     * Obtener lista de upgrades que se ejecutarán
     *
     * @return array
     */
    private function get_upgrades_list(): array {
        // Esta función analiza lib/upgrade.php y extrae los bloques de upgrade
        $upgrades = [];

        $upgrade_file = BASE_DIR . '/lib/upgrade.php';
        if (!file_exists($upgrade_file)) {
            return $upgrades;
        }

        $content = file_get_contents($upgrade_file);

        // Buscar bloques de upgrade con regex
        preg_match_all('/if\s*\(\s*\$oldversion\s*<\s*(\d+)\s*\)\s*\{/', $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $version) {
                $version_num = (int)$version;
                if ($version_num > $this->db_version && $version_num <= $this->code_version) {
                    $upgrades[] = [
                        'version' => $version_num,
                        'will_execute' => true
                    ];
                }
            }
        }

        return $upgrades;
    }
}
