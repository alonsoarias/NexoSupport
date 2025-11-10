<?php
/**
 * ISER - Install Addon Tool
 *
 * Allows installation of plugins/addons from ZIP packages.
 * Validates package structure, extracts files, and registers plugins.
 *
 * @package    ISER\Modules\Admin\Tool\InstallAddon
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    6.0.0
 * @since      Phase 6
 */

namespace ISER\Modules\Admin\Tool\InstallAddon;

use ISER\Core\Database\Database;
use ISER\Modules\Admin\AdminPlugins;
use ISER\Core\Utils\Logger;
use ZipArchive;

class InstallAddon
{
    private Database $db;
    private AdminPlugins $adminPlugins;
    private string $modulesPath;

    public function __construct(Database $db, AdminPlugins $adminPlugins, string $modulesPath)
    {
        $this->db = $db;
        $this->adminPlugins = $adminPlugins;
        $this->modulesPath = $modulesPath;
    }

    /**
     * Validate ZIP package
     *
     * @param string $zipPath Path to ZIP file
     * @return array Validation result
     */
    public function validatePackage(string $zipPath): array
    {
        $errors = [];

        // Check file exists
        if (!file_exists($zipPath)) {
            return ['valid' => false, 'errors' => ['Archivo no encontrado']];
        }

        // Check file size (max 50MB)
        if (filesize($zipPath) > 50 * 1024 * 1024) {
            return ['valid' => false, 'errors' => ['Archivo demasiado grande (máx 50MB)']];
        }

        // Check if it's a valid ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['valid' => false, 'errors' => ['No es un archivo ZIP válido']];
        }

        // Look for version.php
        $versionPhpFound = false;
        $pluginName = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Check for version.php
            if (basename($filename) === 'version.php') {
                $versionPhpFound = true;

                // Try to extract plugin name from path
                $pathParts = explode('/', dirname($filename));
                if (!empty($pathParts)) {
                    $pluginName = end($pathParts);
                }
            }
        }

        $zip->close();

        if (!$versionPhpFound) {
            $errors[] = 'No se encontró archivo version.php en el paquete';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'plugin_name' => $pluginName
        ];
    }

    /**
     * Install addon from ZIP package
     *
     * @param string $zipPath Path to ZIP file
     * @return array Installation result
     */
    public function installPackage(string $zipPath): array
    {
        // Validate first
        $validation = $this->validatePackage($zipPath);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }

        $pluginName = $validation['plugin_name'];

        // Check if plugin already exists
        $existingPlugin = $this->adminPlugins->getPlugin($pluginName);
        if ($existingPlugin) {
            return [
                'success' => false,
                'errors' => ["El plugin '{$pluginName}' ya está instalado"]
            ];
        }

        // Extract ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return [
                'success' => false,
                'errors' => ['No se pudo abrir el archivo ZIP']
            ];
        }

        // Extract to modules directory
        $targetPath = $this->modulesPath;
        if (!$zip->extractTo($targetPath)) {
            $zip->close();
            return [
                'success' => false,
                'errors' => ['Error al extraer los archivos']
            ];
        }

        $zip->close();

        // Read version.php to get plugin metadata
        $versionFile = $targetPath . '/' . $pluginName . '/version.php';
        if (!file_exists($versionFile)) {
            return [
                'success' => false,
                'errors' => ['No se encontró el archivo version.php después de la extracción']
            ];
        }

        // Include version.php to get plugin info
        $plugin = [];
        include $versionFile;

        if (empty($plugin)) {
            return [
                'success' => false,
                'errors' => ['El archivo version.php no contiene información válida del plugin']
            ];
        }

        // Register plugin
        $pluginInfo = [
            'plugin' => $plugin['component'] ?? $pluginName,
            'name' => $plugin['name'] ?? ucfirst($pluginName),
            'version' => $plugin['version'] ?? '1.0.0',
            'sortorder' => 999
        ];

        if (!$this->adminPlugins->registerPlugin(
            $pluginInfo['plugin'],
            $pluginInfo['name'],
            $pluginInfo['version'],
            $pluginInfo['sortorder']
        )) {
            return [
                'success' => false,
                'errors' => ['Error al registrar el plugin en la base de datos']
            ];
        }

        // Run install.php if exists
        $installFile = $targetPath . '/' . $pluginName . '/db/install.php';
        if (file_exists($installFile)) {
            try {
                require_once $installFile;

                // Call install function if it exists
                $installFunction = 'install_' . str_replace('-', '_', $pluginName) . '_db';
                if (function_exists($installFunction)) {
                    $installFunction($this->db);
                }
            } catch (\Exception $e) {
                Logger::error('Plugin install.php failed', [
                    'plugin' => $pluginName,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Logger::auth('Plugin installed', [
            'plugin' => $pluginInfo['plugin'],
            'version' => $pluginInfo['version']
        ]);

        return [
            'success' => true,
            'plugin' => $pluginInfo
        ];
    }

    /**
     * Uninstall addon
     *
     * @param string $pluginName Plugin name
     * @return array Uninstallation result
     */
    public function uninstallPackage(string $pluginName): array
    {
        // Check if plugin exists
        $plugin = $this->adminPlugins->getPlugin($pluginName);
        if (!$plugin) {
            return [
                'success' => false,
                'errors' => ["Plugin '{$pluginName}' no encontrado"]
            ];
        }

        // Don't allow uninstalling core plugins
        $corePlugins = ['auth_manual', 'user', 'roles', 'admin', 'tool_mfa'];
        if (in_array($pluginName, $corePlugins)) {
            return [
                'success' => false,
                'errors' => ['No se pueden desinstalar plugins del sistema']
            ];
        }

        // Run uninstall.php if exists
        $uninstallFile = $this->modulesPath . '/' . $pluginName . '/db/uninstall.php';
        if (file_exists($uninstallFile)) {
            try {
                require_once $uninstallFile;

                // Call uninstall function if it exists
                $uninstallFunction = 'uninstall_' . str_replace('-', '_', $pluginName) . '_db';
                if (function_exists($uninstallFunction)) {
                    $uninstallFunction($this->db);
                }
            } catch (\Exception $e) {
                Logger::error('Plugin uninstall.php failed', [
                    'plugin' => $pluginName,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Remove plugin files
        $pluginPath = $this->modulesPath . '/' . $pluginName;
        if (is_dir($pluginPath)) {
            $this->removeDirectory($pluginPath);
        }

        // Remove from database
        $this->db->delete('config_plugins', ['plugin' => $pluginName]);

        Logger::auth('Plugin uninstalled', ['plugin' => $pluginName]);

        return ['success' => true];
    }

    /**
     * Remove directory recursively
     *
     * @param string $dir Directory path
     * @return bool True on success
     */
    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        return rmdir($dir);
    }

    /**
     * Check dependencies
     *
     * @param array $dependencies Required dependencies
     * @return array Check result
     */
    public function checkDependencies(array $dependencies): array
    {
        $missing = [];

        foreach ($dependencies as $dependency) {
            if (!$this->adminPlugins->isEnabled($dependency)) {
                $missing[] = $dependency;
            }
        }

        return [
            'satisfied' => empty($missing),
            'missing' => $missing
        ];
    }
}
