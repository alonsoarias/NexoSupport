<?php
/**
 * Environment Checker
 *
 * Similar a Moodle, esta clase verifica el estado del sistema para determinar:
 * - Si está instalado o no
 * - Si necesita actualización
 * - Si hay errores de configuración
 *
 * Patrón Moodle:
 * 1. Verificar config.php existe (en nuestro caso .env)
 * 2. Verificar conectividad a BD
 * 3. Verificar tabla config existe
 * 4. Verificar versión en BD
 * 5. Comparar con versión en código
 *
 * @package core\install
 */

namespace core\install;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Environment Checker Class
 *
 * Verifica el estado del sistema y determina qué acción tomar.
 */
class environment_checker {

    /** @var array Estado del sistema */
    private array $state = [];

    /** @var array Configuración de BD parseada desde .env */
    private array $dbconfig = [];

    /** @var \PDO|null Conexión de prueba a BD */
    private ?\PDO $testpdo = null;

    /**
     * Constructor
     *
     * Ejecuta todas las verificaciones al instanciar.
     */
    public function __construct() {
        $this->check_config_file();

        if ($this->state['config_exists']) {
            $this->parse_config_file();
            $this->check_database_connection();

            if ($this->state['db_connected']) {
                $this->check_tables_exist();

                if ($this->state['tables_exist']) {
                    $this->check_version();
                }
            }
        }

        // Cerrar conexión de prueba
        if ($this->testpdo !== null) {
            $this->testpdo = null;
        }
    }

    /**
     * ¿Existe el archivo de configuración?
     *
     * Similar a como Moodle verifica config.php,
     * nosotros verificamos .env
     *
     * @return void
     */
    private function check_config_file(): void {
        $envfile = BASE_DIR . '/.env';
        $this->state['config_exists'] = file_exists($envfile);
    }

    /**
     * Parsear archivo de configuración
     *
     * Lee .env y extrae configuración de BD
     *
     * @return void
     */
    private function parse_config_file(): void {
        $envfile = BASE_DIR . '/.env';
        $envContent = file_get_contents($envfile);

        $this->dbconfig = [
            'host' => '',
            'database' => '',
            'username' => '',
            'password' => '',
            'prefix' => '',
            'driver' => 'mysql'
        ];

        // Parsear líneas
        if (preg_match('/DB_HOST=(.+)/', $envContent, $matches)) {
            $this->dbconfig['host'] = trim($matches[1]);
        }
        if (preg_match('/DB_DATABASE=(.+)/', $envContent, $matches)) {
            $this->dbconfig['database'] = trim($matches[1]);
        }
        if (preg_match('/DB_USERNAME=(.+)/', $envContent, $matches)) {
            $this->dbconfig['username'] = trim($matches[1]);
        }
        if (preg_match('/DB_PASSWORD=(.+)/', $envContent, $matches)) {
            $this->dbconfig['password'] = trim($matches[1]);
        }
        if (preg_match('/DB_PREFIX=(.+)/', $envContent, $matches)) {
            $this->dbconfig['prefix'] = trim($matches[1]);
        }
        if (preg_match('/DB_DRIVER=(.+)/', $envContent, $matches)) {
            $this->dbconfig['driver'] = trim($matches[1]);
        }

        // Verificar que la configuración sea válida
        $this->state['config_valid'] = (
            !empty($this->dbconfig['host']) &&
            !empty($this->dbconfig['database']) &&
            !empty($this->dbconfig['username']) &&
            !empty($this->dbconfig['prefix'])
        );
    }

    /**
     * Verificar conexión a base de datos
     *
     * Intenta conectar con la configuración del .env
     *
     * @return void
     */
    private function check_database_connection(): void {
        if (!$this->state['config_valid']) {
            $this->state['db_connected'] = false;
            return;
        }

        try {
            $driver = $this->dbconfig['driver'] ?? 'mysql';
            $host = $this->dbconfig['host'];
            $database = $this->dbconfig['database'];
            $username = $this->dbconfig['username'];
            $password = $this->dbconfig['password'] ?? '';

            // Construir DSN
            switch ($driver) {
                case 'mysql':
                    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
                    break;
                case 'pgsql':
                    $dsn = "pgsql:host={$host};dbname={$database}";
                    break;
                default:
                    throw new \Exception("Unsupported database driver: {$driver}");
            }

            // Intentar conectar
            $this->testpdo = new \PDO($dsn, $username, $password);
            $this->testpdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $this->state['db_connected'] = true;
            $this->state['db_error'] = null;

        } catch (\PDOException $e) {
            $this->state['db_connected'] = false;
            $this->state['db_error'] = $e->getMessage();
        }
    }

    /**
     * Verificar si existen las tablas del sistema
     *
     * Similar a Moodle, verifica si existe la tabla 'config'
     * que es la tabla fundamental del sistema.
     *
     * @return void
     */
    private function check_tables_exist(): void {
        if (!$this->state['db_connected'] || $this->testpdo === null) {
            $this->state['tables_exist'] = false;
            return;
        }

        try {
            $prefix = $this->dbconfig['prefix'];
            $driver = $this->dbconfig['driver'] ?? 'mysql';

            // Verificar tabla config (compatible MySQL y PostgreSQL)
            if ($driver === 'mysql') {
                $stmt = $this->testpdo->query("SHOW TABLES LIKE '{$prefix}config'");
                $configExists = ($stmt->rowCount() > 0);
            } else {
                // PostgreSQL
                $stmt = $this->testpdo->prepare("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = ?)");
                $stmt->execute(["{$prefix}config"]);
                $configExists = (bool)$stmt->fetchColumn();
            }

            // Verificar tabla users (tabla crítica)
            if ($driver === 'mysql') {
                $stmt = $this->testpdo->query("SHOW TABLES LIKE '{$prefix}users'");
                $usersExists = ($stmt->rowCount() > 0);
            } else {
                // PostgreSQL
                $stmt = $this->testpdo->prepare("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = ?)");
                $stmt->execute(["{$prefix}users"]);
                $usersExists = (bool)$stmt->fetchColumn();
            }

            // Sistema instalado si ambas tablas existen
            $this->state['tables_exist'] = $configExists && $usersExists;

        } catch (\PDOException $e) {
            $this->state['tables_exist'] = false;
            $this->state['tables_error'] = $e->getMessage();
        }
    }

    /**
     * Verificar versión instalada vs versión del código
     *
     * Lee el registro 'version' de la tabla config
     * y lo compara con lib/version.php
     *
     * @return void
     */
    private function check_version(): void {
        if (!$this->state['tables_exist'] || $this->testpdo === null) {
            $this->state['version_ok'] = false;
            return;
        }

        try {
            $prefix = $this->dbconfig['prefix'];

            // Obtener versión de BD
            $stmt = $this->testpdo->prepare("SELECT value FROM {$prefix}config WHERE name = 'version' LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result) {
                $this->state['db_version'] = (int)$result['value'];
            } else {
                // No existe registro de versión (instalación incompleta)
                $this->state['db_version'] = null;
            }

            // Obtener versión del código
            $versionfile = BASE_DIR . '/lib/version.php';
            if (file_exists($versionfile)) {
                $plugin = new \stdClass();
                try {
                    include($versionfile);
                    $this->state['code_version'] = $plugin->version ?? null;
                    $this->state['release'] = $plugin->release ?? 'Unknown';
                } catch (\Throwable $e) {
                    // Error al cargar version.php (ej: constantes no definidas)
                    $this->state['code_version'] = null;
                    $this->state['version_error'] = 'Error loading version.php: ' . $e->getMessage();
                }
            } else {
                $this->state['code_version'] = null;
            }

            // Determinar si necesita upgrade
            if ($this->state['db_version'] === null) {
                // No hay versión en BD: instalación incompleta
                $this->state['version_ok'] = false;
                $this->state['needs_upgrade'] = false;
                $this->state['needs_install'] = true;
            } else if ($this->state['code_version'] === null) {
                // No se pudo leer versión del código
                $this->state['version_ok'] = false;
                $this->state['needs_upgrade'] = false;
                $this->state['needs_install'] = false;
            } else if ($this->state['code_version'] > $this->state['db_version']) {
                // Versión de código es mayor: necesita upgrade
                $this->state['version_ok'] = false;
                $this->state['needs_upgrade'] = true;
                $this->state['needs_install'] = false;
            } else {
                // Versiones coinciden o BD es mayor (downgrade no soportado)
                $this->state['version_ok'] = true;
                $this->state['needs_upgrade'] = false;
                $this->state['needs_install'] = false;
            }

        } catch (\PDOException $e) {
            $this->state['version_ok'] = false;
            $this->state['version_error'] = $e->getMessage();
        } catch (\Throwable $e) {
            // Catch all other errors
            $this->state['version_ok'] = false;
            $this->state['version_error'] = 'Unexpected error in check_version: ' . $e->getMessage();
        }
    }

    /**
     * ¿Está instalado el sistema?
     *
     * @return bool
     */
    public function is_installed(): bool {
        return (
            $this->state['config_exists'] ?? false &&
            $this->state['config_valid'] ?? false &&
            $this->state['db_connected'] ?? false &&
            $this->state['tables_exist'] ?? false &&
            isset($this->state['db_version'])
        );
    }

    /**
     * ¿Necesita actualización?
     *
     * @return bool
     */
    public function needs_upgrade(): bool {
        return $this->state['needs_upgrade'] ?? false;
    }

    /**
     * ¿Necesita instalación?
     *
     * Puede ser instalación completa o completar instalación incompleta.
     *
     * @return bool
     */
    public function needs_install(): bool {
        return !$this->is_installed();
    }

    /**
     * Obtener estado completo del sistema
     *
     * Útil para debugging
     *
     * @return array
     */
    public function get_state(): array {
        return $this->state;
    }

    /**
     * Obtener configuración de BD parseada
     *
     * @return array
     */
    public function get_db_config(): array {
        return $this->dbconfig;
    }

    /**
     * Obtener versión de BD
     *
     * @return int|null
     */
    public function get_db_version(): ?int {
        return $this->state['db_version'] ?? null;
    }

    /**
     * Obtener versión del código
     *
     * @return int|null
     */
    public function get_code_version(): ?int {
        return $this->state['code_version'] ?? null;
    }

    /**
     * Obtener release string
     *
     * @return string
     */
    public function get_release(): string {
        return $this->state['release'] ?? 'Unknown';
    }

    /**
     * ¿Hay errores?
     *
     * @return bool
     */
    public function has_errors(): bool {
        return (
            isset($this->state['db_error']) ||
            isset($this->state['tables_error']) ||
            isset($this->state['version_error'])
        );
    }

    /**
     * Obtener mensajes de error
     *
     * @return array
     */
    public function get_errors(): array {
        $errors = [];

        if (isset($this->state['db_error'])) {
            $errors[] = 'Database connection error: ' . $this->state['db_error'];
        }

        if (isset($this->state['tables_error'])) {
            $errors[] = 'Database tables error: ' . $this->state['tables_error'];
        }

        if (isset($this->state['version_error'])) {
            $errors[] = 'Version check error: ' . $this->state['version_error'];
        }

        return $errors;
    }
}
