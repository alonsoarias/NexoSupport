<?php
/**
 * Installer Class
 *
 * Centraliza toda la lógica del proceso de instalación.
 * Separa completamente la lógica de negocio de la presentación.
 *
 * @package core\install
 */

namespace core\install;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Main Installer Class
 */
class installer {

    /** @var array Estado actual de la instalación */
    private array $state = [];

    /** @var array Configuración de la instalación */
    private array $config = [];

    /** @var array Errores acumulados */
    private array $errors = [];

    /** @var environment_checker Verificador de entorno */
    private environment_checker $env_checker;

    /**
     * Constructor
     */
    public function __construct() {
        $this->env_checker = new environment_checker();
        $this->init_session();
        $this->load_state();
    }

    /**
     * Inicializar sesión de instalación
     *
     * @return void
     */
    private function init_session(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['nexosupport_install'])) {
            $_SESSION['nexosupport_install'] = [
                'started' => time(),
                'stage' => 'welcome',
                'data' => []
            ];
        }
    }

    /**
     * Cargar estado actual desde la sesión
     *
     * @return void
     */
    private function load_state(): void {
        $this->state = $_SESSION['nexosupport_install'] ?? [];
    }

    /**
     * Guardar estado en la sesión
     *
     * @return void
     */
    private function save_state(): void {
        $_SESSION['nexosupport_install'] = $this->state;
    }

    /**
     * Obtener stage actual
     *
     * @return string
     */
    public function get_current_stage(): string {
        return $this->state['stage'] ?? 'welcome';
    }

    /**
     * Establecer stage actual
     *
     * @param string $stage
     * @return void
     */
    public function set_stage(string $stage): void {
        $valid_stages = ['welcome', 'requirements', 'database', 'install_db', 'admin', 'finish'];

        if (!in_array($stage, $valid_stages, true)) {
            $stage = 'welcome';
        }

        $this->state['stage'] = $stage;
        $this->save_state();
    }

    /**
     * Verificar requisitos del sistema
     *
     * @return array [bool success, array requirements]
     */
    public function check_requirements(): array {
        $requirements = [
            'php_version' => [
                'name' => 'Versión de PHP',
                'required' => '8.1.0',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.1.0', '>='),
                'critical' => true
            ],
            'pdo' => [
                'name' => 'PDO Extension',
                'required' => 'Requerido',
                'current' => extension_loaded('pdo') ? 'Instalado' : 'No instalado',
                'status' => extension_loaded('pdo'),
                'critical' => true
            ],
            'pdo_mysql' => [
                'name' => 'PDO MySQL Driver',
                'required' => 'Requerido',
                'current' => extension_loaded('pdo_mysql') ? 'Instalado' : 'No instalado',
                'status' => extension_loaded('pdo_mysql'),
                'critical' => false
            ],
            'mbstring' => [
                'name' => 'Mbstring Extension',
                'required' => 'Requerido',
                'current' => extension_loaded('mbstring') ? 'Instalado' : 'No instalado',
                'status' => extension_loaded('mbstring'),
                'critical' => true
            ],
            'json' => [
                'name' => 'JSON Extension',
                'required' => 'Requerido',
                'current' => extension_loaded('json') ? 'Instalado' : 'No instalado',
                'status' => extension_loaded('json'),
                'critical' => true
            ],
            'writable_root' => [
                'name' => 'Directorio raíz escribible',
                'required' => 'Escribible',
                'current' => is_writable(BASE_DIR) ? 'Escribible' : 'No escribible',
                'status' => is_writable(BASE_DIR),
                'critical' => true
            ],
            'writable_var' => [
                'name' => 'Directorio var/ escribible',
                'required' => 'Escribible',
                'current' => is_writable(BASE_DIR . '/var') ? 'Escribible' : 'No escribible',
                'status' => is_writable(BASE_DIR . '/var'),
                'critical' => true
            ]
        ];

        $all_ok = true;
        foreach ($requirements as $req) {
            if ($req['critical'] && !$req['status']) {
                $all_ok = false;
                break;
            }
        }

        return ['success' => $all_ok, 'requirements' => $requirements];
    }

    /**
     * Validar configuración de base de datos
     *
     * @param array $dbconfig
     * @return array [bool success, ?string error]
     */
    public function validate_database_config(array $dbconfig): array {
        // Validar driver
        if (!in_array($dbconfig['driver'] ?? '', ['mysql', 'pgsql'], true)) {
            return ['success' => false, 'error' => 'Driver de base de datos no válido'];
        }

        // Validar nombre de BD (seguridad)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbconfig['database'] ?? '')) {
            return ['success' => false, 'error' => 'Nombre de base de datos inválido (solo letras, números y guiones bajos)'];
        }

        // Validar prefijo (seguridad)
        if (!preg_match('/^[a-zA-Z0-9_]*$/', $dbconfig['prefix'] ?? '')) {
            return ['success' => false, 'error' => 'Prefijo de tablas inválido (solo letras, números y guiones bajos)'];
        }

        // Validar campos requeridos
        if (empty($dbconfig['host']) || empty($dbconfig['database']) || empty($dbconfig['username'])) {
            return ['success' => false, 'error' => 'Campos requeridos faltantes'];
        }

        return ['success' => true, 'error' => null];
    }

    /**
     * Probar conexión a base de datos
     *
     * @param array $dbconfig
     * @return array [bool success, ?string error, ?\PDO pdo]
     */
    public function test_database_connection(array $dbconfig): array {
        try {
            $dsn = $dbconfig['driver'] === 'mysql'
                ? "mysql:host={$dbconfig['host']};charset=utf8mb4"
                : "pgsql:host={$dbconfig['host']}";

            $pdo = new \PDO($dsn, $dbconfig['username'], $dbconfig['password'] ?? '');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Crear BD si no existe (solo MySQL)
            if ($dbconfig['driver'] === 'mysql') {
                $dbname = $dbconfig['database'];
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$dbname`");
            }

            return ['success' => true, 'error' => null, 'pdo' => $pdo];

        } catch (\PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'pdo' => null];
        }
    }

    /**
     * Guardar configuración de BD y crear .env
     *
     * @param array $dbconfig
     * @return array [bool success, ?string error]
     */
    public function save_database_config(array $dbconfig): array {
        try {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $app_url = $protocol . '://' . $host;

            $envContent = "# NexoSupport Environment Configuration\n";
            $envContent .= "# Generated on " . date('Y-m-d H:i:s') . "\n\n";
            $envContent .= "# Application Settings\n";
            $envContent .= "APP_NAME=NexoSupport\n";
            $envContent .= "APP_ENV=production\n";
            $envContent .= "APP_DEBUG=false\n";
            $envContent .= "APP_URL=$app_url\n";
            $envContent .= "APP_TIMEZONE=America/Bogota\n";
            $envContent .= "\n";
            $envContent .= "# Database Configuration\n";
            $envContent .= "DB_DRIVER={$dbconfig['driver']}\n";
            $envContent .= "DB_HOST={$dbconfig['host']}\n";
            $envContent .= "DB_DATABASE={$dbconfig['database']}\n";
            $envContent .= "DB_USERNAME={$dbconfig['username']}\n";
            $envContent .= "DB_PASSWORD={$dbconfig['password']}\n";
            $envContent .= "DB_PREFIX={$dbconfig['prefix']}\n";
            $envContent .= "\n";
            $envContent .= "# Cache Settings\n";
            $envContent .= "CACHE_DRIVER=file\n";
            $envContent .= "\n";
            $envContent .= "# Session Settings\n";
            $envContent .= "SESSION_LIFETIME=120\n";
            $envContent .= "SESSION_NAME=nexosupport_session\n";

            file_put_contents(BASE_DIR . '/.env', $envContent);

            // Guardar en estado de instalación
            $this->state['data']['database'] = $dbconfig;
            $this->save_state();

            return ['success' => true, 'error' => null];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Instalar esquema de base de datos
     *
     * @return array [bool success, ?string error, array log]
     */
    public function install_database_schema(): array {
        $log = [];

        try {
            $dbconfig = $this->state['data']['database'] ?? null;
            if (!$dbconfig) {
                return ['success' => false, 'error' => 'Configuración de BD no encontrada', 'log' => $log];
            }

            // Conectar
            $dsn = $dbconfig['driver'] === 'mysql'
                ? "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8mb4"
                : "pgsql:host={$dbconfig['host']};dbname={$dbconfig['database']}";

            $pdo = new \PDO($dsn, $dbconfig['username'], $dbconfig['password'] ?? '');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $log[] = "Conexión a BD establecida";

            // Verificar instalación existente
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$dbconfig['prefix']}config");
                $stmt->execute();
                $count = (int)$stmt->fetchColumn();

                if ($count > 0) {
                    return ['success' => false, 'error' => 'Ya existe una instalación en esta base de datos', 'log' => $log];
                }
            } catch (\PDOException $e) {
                // Tabla no existe, continuar
                $log[] = "BD vacía, procediendo con instalación";
            }

            // Cargar clases necesarias
            require_once(BASE_DIR . '/lib/classes/db/xmldb_table.php');
            require_once(BASE_DIR . '/lib/classes/db/xmldb_field.php');
            require_once(BASE_DIR . '/lib/classes/db/xmldb_key.php');
            require_once(BASE_DIR . '/lib/classes/db/xmldb_index.php');
            require_once(BASE_DIR . '/lib/classes/db/database.php');
            require_once(BASE_DIR . '/lib/classes/db/ddl_manager.php');
            require_once(BASE_DIR . '/lib/classes/db/schema_installer.php');

            $DB = new \core\db\database($pdo, $dbconfig['prefix'], $dbconfig['driver']);
            $installer = new \core\db\schema_installer($DB);

            $log[] = "Instalando esquema desde lib/db/install.xml";

            // Instalar schema
            $installer->install_from_xmlfile(BASE_DIR . '/lib/db/install.xml');

            $log[] = "Esquema instalado correctamente";

            // Crear contexto system
            $DB->insert_record('contexts', [
                'contextlevel' => 10,
                'instanceid' => 0,
                'path' => '/1',
                'depth' => 1
            ]);

            $log[] = "Contexto SYSTEM creado";

            return ['success' => true, 'error' => null, 'log' => $log];

        } catch (\Exception $e) {
            $log[] = "ERROR: " . $e->getMessage();
            return ['success' => false, 'error' => $e->getMessage(), 'log' => $log];
        }
    }

    /**
     * Crear usuario administrador
     *
     * @param array $userdata
     * @return array [bool success, ?string error, ?int userid]
     */
    public function create_admin_user(array $userdata): array {
        try {
            // Validar datos
            if (empty($userdata['username']) || empty($userdata['password']) ||
                empty($userdata['email']) || empty($userdata['firstname']) ||
                empty($userdata['lastname'])) {
                return ['success' => false, 'error' => 'Campos requeridos faltantes', 'userid' => null];
            }

            // Validar email
            if (!filter_var($userdata['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'error' => 'Email inválido', 'userid' => null];
            }

            // Validar contraseña
            if (strlen($userdata['password']) < 8) {
                return ['success' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres', 'userid' => null];
            }

            // Conectar a BD
            $dbconfig = $this->state['data']['database'] ?? null;
            if (!$dbconfig) {
                return ['success' => false, 'error' => 'Configuración de BD no encontrada', 'userid' => null];
            }

            $dsn = $dbconfig['driver'] === 'mysql'
                ? "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8mb4"
                : "pgsql:host={$dbconfig['host']};dbname={$dbconfig['database']}";

            $pdo = new \PDO($dsn, $dbconfig['username'], $dbconfig['password'] ?? '');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            require_once(BASE_DIR . '/lib/classes/db/database.php');
            $DB = new \core\db\database($pdo, $dbconfig['prefix'], $dbconfig['driver']);

            // Crear usuario
            $userid = $DB->insert_record('users', [
                'auth' => 'manual',
                'username' => $userdata['username'],
                'password' => password_hash($userdata['password'], PASSWORD_DEFAULT),
                'firstname' => $userdata['firstname'],
                'lastname' => $userdata['lastname'],
                'email' => $userdata['email'],
                'confirmed' => 1,
                'suspended' => 0,
                'deleted' => 0,
                'timecreated' => time(),
                'timemodified' => time()
            ]);

            // Guardar en estado
            $this->state['data']['admin_userid'] = $userid;
            $this->save_state();

            return ['success' => true, 'error' => null, 'userid' => $userid];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'userid' => null];
        }
    }

    /**
     * Finalizar instalación (instalar RBAC, configurar sistema)
     *
     * @return array [bool success, ?string error, array log]
     */
    public function finalize_installation(): array {
        $log = [];

        try {
            $dbconfig = $this->state['data']['database'] ?? null;
            $adminuserid = $this->state['data']['admin_userid'] ?? null;

            if (!$dbconfig || !$adminuserid) {
                return ['success' => false, 'error' => 'Datos de instalación incompletos', 'log' => $log];
            }

            // Conectar
            $dsn = $dbconfig['driver'] === 'mysql'
                ? "mysql:host={$dbconfig['host']};dbname={$dbconfig['database']};charset=utf8mb4"
                : "pgsql:host={$dbconfig['host']};dbname={$dbconfig['database']}";

            $pdo = new \PDO($dsn, $dbconfig['username'], $dbconfig['password'] ?? '');
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            require_once(BASE_DIR . '/lib/classes/db/database.php');
            $GLOBALS['DB'] = new \core\db\database($pdo, $dbconfig['prefix'], $dbconfig['driver']);

            $log[] = "Conexión a BD establecida";

            // Instalar RBAC
            require_once(BASE_DIR . '/lib/install_rbac.php');

            if (install_rbac_system()) {
                $log[] = "Sistema RBAC instalado";

                // Asignar rol administrator
                $syscontext = \core\rbac\context::system();
                $adminrole = \core\rbac\role::get_by_shortname('administrator');

                if ($adminrole) {
                    \core\rbac\access::assign_role($adminrole->id, $adminuserid, $syscontext);
                    $log[] = "Rol administrator asignado al usuario";
                }
            } else {
                return ['success' => false, 'error' => 'Error instalando sistema RBAC', 'log' => $log];
            }

            // Guardar versión del core
            require_once(BASE_DIR . '/lib/version.php');
            $GLOBALS['DB']->insert_record('config', [
                'component' => 'core',
                'name' => 'version',
                'value' => (string)$plugin->version
            ]);

            $log[] = "Versión del core guardada: {$plugin->version}";

            // Guardar siteadmins
            $GLOBALS['DB']->insert_record('config', [
                'component' => 'core',
                'name' => 'siteadmins',
                'value' => (string)$adminuserid
            ]);

            $log[] = "Site administrator configurado";

            // Limpiar sesión de instalación
            unset($_SESSION['nexosupport_install']);

            return ['success' => true, 'error' => null, 'log' => $log];

        } catch (\Exception $e) {
            $log[] = "ERROR: " . $e->getMessage();
            return ['success' => false, 'error' => $e->getMessage(), 'log' => $log];
        }
    }

    /**
     * Obtener datos guardados
     *
     * @param string $key
     * @return mixed
     */
    public function get_data(string $key) {
        return $this->state['data'][$key] ?? null;
    }

    /**
     * Destructor de la sesión de instalación
     *
     * @return void
     */
    public function destroy(): void {
        unset($_SESSION['nexosupport_install']);
    }
}
