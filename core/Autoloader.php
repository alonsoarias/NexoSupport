<?php

/**
 * ISER Authentication System - Custom Autoloader
 *
 * PSR-4 compliant autoloader for dynamic module loading.
 * Complements Composer's autoloader for modular architecture.
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

/**
 * Autoloader Class
 *
 * Provides dynamic class loading for the modular system.
 */
class Autoloader
{
    /**
     * Registered namespace prefixes and their base directories
     */
    private array $prefixes = [];

    /**
     * Base directory for the system
     */
    private string $baseDir;

    /**
     * Constructor
     *
     * @param string|null $baseDir Base directory path
     */
    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = $baseDir ?? dirname(__DIR__);
        $this->registerDefaultNamespaces();
    }

    /**
     * Register the autoloader
     *
     * @return void
     */
    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Unregister the autoloader
     *
     * @return void
     */
    public function unregister(): void
    {
        spl_autoload_unregister([$this, 'loadClass']);
    }

    /**
     * Register default namespaces
     *
     * @return void
     */
    private function registerDefaultNamespaces(): void
    {
        $this->addNamespace('ISER\\Core', $this->baseDir . '/core');
        $this->addNamespace('ISER\\Modules', $this->baseDir . '/modules');
    }

    /**
     * Add a namespace prefix and base directory
     *
     * @param string $prefix The namespace prefix
     * @param string $baseDir Base directory for classes in this namespace
     * @param bool $prepend Prepend to the stack instead of append
     * @return void
     */
    public function addNamespace(string $prefix, string $baseDir, bool $prepend = false): void
    {
        // Normalize namespace prefix
        $prefix = trim($prefix, '\\') . '\\';

        // Normalize base directory
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';

        // Initialize the namespace prefix array if needed
        if (!isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = [];
        }

        // Add the base directory for the namespace prefix
        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $baseDir);
        } else {
            array_push($this->prefixes[$prefix], $baseDir);
        }
    }

    /**
     * Load the class file for a given class name
     *
     * @param string $class The fully-qualified class name
     * @return bool|string The mapped file name on success, or false on failure
     */
    public function loadClass(string $class): bool|string
    {
        // The current namespace prefix
        $prefix = $class;

        // Work backwards through the namespace names to find a mapped file
        while (false !== $pos = strrpos($prefix, '\\')) {
            // Retain the trailing namespace separator in the prefix
            $prefix = substr($class, 0, $pos + 1);

            // The rest is the relative class name
            $relativeClass = substr($class, $pos + 1);

            // Try to load a mapped file for the prefix and relative class
            $mappedFile = $this->loadMappedFile($prefix, $relativeClass);

            if ($mappedFile !== false) {
                return $mappedFile;
            }

            // Remove the trailing namespace separator for the next iteration
            $prefix = rtrim($prefix, '\\');
        }

        // Never found a mapped file
        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class
     *
     * @param string $prefix The namespace prefix
     * @param string $relativeClass The relative class name
     * @return bool|string The file path if successful, false otherwise
     */
    private function loadMappedFile(string $prefix, string $relativeClass): bool|string
    {
        // Are there any base directories for this namespace prefix?
        if (!isset($this->prefixes[$prefix])) {
            return false;
        }

        // Look through base directories for this namespace prefix
        foreach ($this->prefixes[$prefix] as $baseDir) {
            // Replace namespace separators with directory separators
            // and append with .php
            $file = $baseDir
                . str_replace('\\', '/', $relativeClass)
                . '.php';

            // If the mapped file exists, require it
            if ($this->requireFile($file)) {
                return $file;
            }
        }

        // Never found a file
        return false;
    }

    /**
     * If a file exists, require it from the file system
     *
     * @param string $file The file to require
     * @return bool True if the file exists and was required, false otherwise
     */
    private function requireFile(string $file): bool
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }

        return false;
    }

    /**
     * Get all registered namespaces
     *
     * @return array
     */
    public function getNamespaces(): array
    {
        return $this->prefixes;
    }

    /**
     * Load module classes dynamically
     *
     * @param string $moduleName Module name (e.g., 'auth', 'user')
     * @param string $className Class name within the module
     * @return bool|object Instance of the class if successful, false otherwise
     */
    public function loadModuleClass(string $moduleName, string $className): bool|object
    {
        // Construct the fully qualified class name
        $fqcn = "ISER\\Modules\\{$moduleName}\\{$className}";

        // Check if class exists (will trigger autoload)
        if (class_exists($fqcn)) {
            return new $fqcn();
        }

        return false;
    }

    /**
     * Discover and register all modules
     *
     * @return array List of discovered modules
     */
    public function discoverModules(): array
    {
        $modulesDir = $this->baseDir . '/modules';
        $discoveredModules = [];

        if (!is_dir($modulesDir)) {
            return $discoveredModules;
        }

        $moduleTypes = scandir($modulesDir);

        foreach ($moduleTypes as $moduleType) {
            if ($moduleType === '.' || $moduleType === '..') {
                continue;
            }

            $moduleTypePath = $modulesDir . '/' . $moduleType;

            if (!is_dir($moduleTypePath)) {
                continue;
            }

            // Check for submodules
            $subModules = scandir($moduleTypePath);

            foreach ($subModules as $subModule) {
                if ($subModule === '.' || $subModule === '..') {
                    continue;
                }

                $subModulePath = $moduleTypePath . '/' . $subModule;

                if (is_dir($subModulePath)) {
                    $discoveredModules[] = [
                        'type' => $moduleType,
                        'name' => $subModule,
                        'path' => $subModulePath,
                    ];

                    // Register namespace for this module
                    $namespace = "ISER\\Modules\\" . ucfirst($moduleType) . "\\" . ucfirst($subModule);
                    $this->addNamespace($namespace, $subModulePath);
                }
            }
        }

        return $discoveredModules;
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
     * Check if a class can be loaded
     *
     * @param string $class Fully qualified class name
     * @return bool
     */
    public function canLoadClass(string $class): bool
    {
        return class_exists($class, true);
    }
}

// Register the autoloader if not already done by Composer
if (!class_exists('Composer\Autoload\ClassLoader', false)) {
    $autoloader = new Autoloader();
    $autoloader->register();
}
