<?php

/**
 * ISER Authentication System - Bootstrap
 *
 * Main system initialization and bootstrap class.
 *
 * @package    ISER\Core
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

namespace ISER\Core;

use ISER\Core\Config\ConfigManager;
use ISER\Core\Config\Environment;
use ISER\Core\Database\PDOConnection;
use ISER\Core\Database\Database;
use ISER\Core\Router\Router;
use ISER\Core\Session\JWTSession;
use ISER\Core\Utils\Logger;
use RuntimeException;

/**
 * Bootstrap Class
 *
 * Initializes the system in the correct order and manages core components.
 */
class Bootstrap
{
    /**
     * System version
     */
    public const VERSION = '1.0.0';

    /**
     * Base directory path
     */
    private string $baseDir;

    /**
     * Configuration manager
     */
    private ConfigManager $config;

    /**
     * Environment manager
     */
    private Environment $environment;

    /**
     * Database instance
     */
    private ?Database $database = null;

    /**
     * Router instance
     */
    private ?Router $router = null;

    /**
     * JWT session instance
     */
    private ?JWTSession $jwtSession = null;

    /**
     * Autoloader instance
     */
    private ?Autoloader $autoloader = null;

    /**
     * Registered modules
     */
    private array $modules = [];

    /**
     * System initialization status
     */
    private bool $initialized = false;

    /**
     * Constructor
     *
     * @param string|null $baseDir Base directory path
     */
    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = $baseDir ?? dirname(__DIR__);
    }

    /**
     * Initialize the system
     *
     * @return self
     */
    public function init(): self
    {
        if ($this->initialized) {
            return $this;
        }

        try {
            // Step 1: Load configuration
            $this->loadConfiguration();

            // Step 2: Setup environment
            $this->setupEnvironment();

            // Step 3: Initialize logging
            $this->initializeLogging();

            // Step 4: Setup autoloader
            $this->setupAutoloader();

            // Step 5: Initialize database
            $this->initializeDatabase();

            // Step 6: Initialize session (JWT)
            $this->initializeSession();

            // Step 7: Initialize i18n and locale detection
            $this->initializeI18n();

            // Step 8: Initialize plugin system (FASE 2)
            $this->initializePluginSystem();

            // Step 9: Initialize router
            $this->initializeRouter();

            // Step 10: Discover and register modules
            $this->discoverModules();

            $this->initialized = true;

            Logger::info('System initialized successfully', [
                'version' => self::VERSION,
                'environment' => $this->environment->getEnvironment(),
            ]);

        } catch (\Exception $e) {
            $this->handleInitializationError($e);
        }

        return $this;
    }

    /**
     * Load configuration
     *
     * @return void
     */
    private function loadConfiguration(): void
    {
        $this->config = ConfigManager::getInstance($this->baseDir);
    }

    /**
     * Setup environment
     *
     * @return void
     */
    private function setupEnvironment(): void
    {
        $this->environment = new Environment(
            $this->config->get('APP_ENV', 'production'),
            filter_var($this->config->get('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN)
        );

        // Configure PHP settings
        $this->environment->configurePhpSettings();

        // Validate system requirements
        $this->environment->validateRequirements();
    }

    /**
     * Initialize logging system
     *
     * @return void
     */
    private function initializeLogging(): void
    {
        $logConfig = $this->config->getLogConfig();

        Logger::init(
            $logConfig['path'],
            $logConfig['level'],
            $logConfig['max_files']
        );

        Logger::info('Logging system initialized');
    }

    /**
     * Setup autoloader
     *
     * @return void
     */
    private function setupAutoloader(): void
    {
        $this->autoloader = new Autoloader($this->baseDir);
        $this->autoloader->register();

        Logger::debug('Autoloader registered');
    }

    /**
     * Initialize database connection
     *
     * @return void
     */
    private function initializeDatabase(): void
    {
        try {
            $dbConfig = $this->config->getDatabaseConfig();

            $connection = PDOConnection::getInstance($dbConfig);

            if (!$connection->testConnection()) {
                throw new RuntimeException('Database connection test failed');
            }

            $this->database = new Database($connection);

            Logger::database('Database connection established', [
                'database' => $dbConfig['database'],
                'host' => $dbConfig['host'],
            ]);

        } catch (\Exception $e) {
            Logger::error('Database initialization failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Initialize JWT session management
     *
     * @return void
     */
    private function initializeSession(): void
    {
        $jwtConfig = $this->config->getJwtConfig();
        $this->jwtSession = new JWTSession($jwtConfig);

        Logger::debug('JWT session initialized');
    }

    /**
     * Initialize i18n and locale detection
     *
     * @return void
     */
    private function initializeI18n(): void
    {
        // Obtener instancia del Translator
        $translator = \ISER\Core\I18n\Translator::getInstance();

        // Crear LocaleDetector
        $localeDetector = new \ISER\Core\I18n\LocaleDetector($translator, $this->database);

        // Detectar y aplicar locale automáticamente
        $localeDetector->apply();

        Logger::debug('I18n initialized', [
            'locale' => $translator->getLocale(),
            'available_locales' => $translator->getAvailableLocales()
        ]);
    }

    /**
     * Initialize plugin system (FASE 2)
     *
     * @return void
     */
    private function initializePluginSystem(): void
    {
        // Initialize HookManager singleton
        $hookManager = \ISER\Core\Plugin\HookManager::getInstance();

        // Initialize PluginLoader
        $pluginLoader = new \ISER\Plugin\PluginLoader($this->database, $hookManager);

        // Load all enabled plugins
        try {
            $pluginLoader->loadAll();
            $loadedPlugins = $pluginLoader->getLoadedPlugins();

            Logger::info('Plugin system initialized', [
                'loaded_plugins' => count($loadedPlugins),
                'plugins' => array_column($loadedPlugins, 'slug')
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to load plugins', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Initialize router
     *
     * @return void
     */
    private function initializeRouter(): void
    {
        $this->router = new Router();

        // Set default handlers
        $this->router->setNotFoundHandler(function ($path) {
            Logger::warning('404 Not Found', ['path' => $path]);
            http_response_code(404);
            return $this->renderError(404, 'Page not found');
        });

        $this->router->setErrorHandler(function (\Throwable $e) {
            Logger::exception($e);
            http_response_code(500);
            return $this->renderError(500, 'Internal server error');
        });

        Logger::debug('Router initialized');
    }

    /**
     * Discover and register modules
     *
     * @return void
     */
    private function discoverModules(): void
    {
        if ($this->autoloader === null) {
            return;
        }

        $discoveredModules = $this->autoloader->discoverModules();

        foreach ($discoveredModules as $moduleInfo) {
            $this->modules[] = $moduleInfo;
        }

        Logger::info('Modules discovered', [
            'count' => count($this->modules),
            'modules' => array_column($this->modules, 'name'),
        ]);
    }

    /**
     * Handle initialization errors
     *
     * @param \Exception $e Exception
     * @return void
     */
    private function handleInitializationError(\Exception $e): void
    {
        // Try to log if logger is initialized
        if (Logger::isInitialized()) {
            Logger::critical('System initialization failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        // Display error based on environment
        if ($this->environment?->isDevelopment() ?? true) {
            echo '<h1>System Initialization Failed</h1>';
            echo '<pre>' . $e->getMessage() . '</pre>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
        } else {
            echo '<h1>System Error</h1>';
            echo '<p>The system encountered an error during initialization.</p>';
        }

        exit(1);
    }

    /**
     * Render error page
     *
     * @param int $code HTTP status code
     * @param string $message Error message
     * @return string HTML output
     */
    private function renderError(int $code, string $message): string
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error {$code}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; text-align: center; }
        h1 { color: #d32f2f; }
        p { color: #666; }
    </style>
</head>
<body>
    <h1>Error {$code}</h1>
    <p>{$message}</p>
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Get configuration manager
     *
     * @return ConfigManager
     */
    public function getConfig(): ConfigManager
    {
        return $this->config;
    }

    /**
     * Get environment manager
     *
     * @return Environment
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Get database instance
     *
     * @return Database|null
     */
    public function getDatabase(): ?Database
    {
        return $this->database;
    }

    /**
     * Get router instance
     *
     * @return Router|null
     */
    public function getRouter(): ?Router
    {
        return $this->router;
    }

    /**
     * Get JWT session instance
     *
     * @return JWTSession|null
     */
    public function getJWTSession(): ?JWTSession
    {
        return $this->jwtSession;
    }

    /**
     * Get autoloader instance
     *
     * @return Autoloader|null
     */
    public function getAutoloader(): ?Autoloader
    {
        return $this->autoloader;
    }

    /**
     * Get registered modules
     *
     * @return array
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Check if system is initialized
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Get system version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Get base directory
     *
     * @return string
     */
    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    /**
     * Run the application
     *
     * @return void
     */
    public function run(): void
    {
        if (!$this->initialized) {
            $this->init();
        }

        if ($this->router === null) {
            throw new RuntimeException('Router not initialized');
        }

        // Dispatch router
        $output = $this->router->dispatch();

        // Output result
        if (is_string($output)) {
            echo $output;
        } elseif (is_array($output) || is_object($output)) {
            header('Content-Type: application/json');
            echo json_encode($output);
        }
    }

    /**
     * Get system information
     *
     * @return array System information
     */
    public function getSystemInfo(): array
    {
        return [
            'version' => self::VERSION,
            'initialized' => $this->initialized,
            'environment' => $this->environment?->getEnvironment(),
            'debug_mode' => $this->environment?->isDebugMode(),
            'modules_count' => count($this->modules),
            'php_version' => PHP_VERSION,
        ];
    }
}
