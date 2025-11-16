<?php
/**
 * NexoSupport - Addon Validator
 *
 * @package    tool_installaddon
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Tools\InstallAddon;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Addon Validator
 *
 * Validates plugin packages before installation
 */
class AddonValidator
{
    /** @var int Maximum ZIP size (50MB) */
    private $max_size = 52428800;

    /** @var array Dangerous functions */
    private $dangerous_functions = ['eval', 'exec', 'system', 'shell_exec', 'passthru', 'popen'];

    /**
     * Validate ZIP file
     *
     * @param string $filepath ZIP file path
     * @return array Validation result
     */
    public function validate_zip(string $filepath): array
    {
        // Check file size
        if (filesize($filepath) > $this->max_size) {
            return ['success' => false, 'error' => 'ZIP file too large (max 50MB)'];
        }

        // Check extension
        if (!$this->check_file_extension($filepath)) {
            return ['success' => false, 'error' => 'Invalid file extension'];
        }

        // Check if valid ZIP
        $zip = new \ZipArchive();
        if ($zip->open($filepath) !== true) {
            return ['success' => false, 'error' => 'Invalid ZIP file'];
        }
        $zip->close();

        return ['success' => true];
    }

    /**
     * Validate plugin structure
     *
     * @param string $dir Plugin directory
     * @return array Validation result
     */
    public function validate_structure(string $dir): array
    {
        // Must have version.php
        if (!file_exists($dir . '/version.php')) {
            return ['success' => false, 'error' => 'Missing version.php'];
        }

        // Must have lib.php
        if (!file_exists($dir . '/lib.php')) {
            return ['success' => false, 'error' => 'Missing lib.php'];
        }

        // Validate version.php content
        $version_result = $this->validate_version_php($dir . '/version.php');
        if (!$version_result['success']) {
            return $version_result;
        }

        // Validate lib.php content
        $lib_result = $this->validate_lib_php($dir . '/lib.php');
        if (!$lib_result['success']) {
            return $lib_result;
        }

        // Security check
        $security_result = $this->check_security_threats($dir);
        if (!$security_result['success']) {
            return $security_result;
        }

        return [
            'success' => true,
            'component' => $version_result['component'],
        ];
    }

    /**
     * Validate version.php file
     *
     * @param string $filepath File path
     * @return array Validation result
     */
    private function validate_version_php(string $filepath): array
    {
        $content = file_get_contents($filepath);

        // Must define $plugin->component
        if (!preg_match('/\$plugin->component\s*=\s*[\'"]([a-z]+_[a-z0-9]+)[\'"]/', $content, $matches)) {
            return ['success' => false, 'error' => 'Invalid component name in version.php'];
        }

        $component = $matches[1];

        // Validate Frankenstyle naming
        if (!$this->validate_component_name($component)) {
            return ['success' => false, 'error' => "Invalid component name: $component"];
        }

        return ['success' => true, 'component' => $component];
    }

    /**
     * Validate lib.php file
     *
     * @param string $filepath File path
     * @return array Validation result
     */
    private function validate_lib_php(string $filepath): array
    {
        $content = file_get_contents($filepath);

        // Must have get_capabilities function
        if (!preg_match('/function\s+\w+_get_capabilities\s*\(/', $content)) {
            return ['success' => false, 'error' => 'Missing get_capabilities() function in lib.php'];
        }

        return ['success' => true];
    }

    /**
     * Check for security threats
     *
     * @param string $dir Directory to check
     * @return array Check result
     */
    private function check_security_threats(string $dir): array
    {
        $php_files = $this->find_php_files($dir);

        foreach ($php_files as $file) {
            $content = file_get_contents($file);

            // Check for dangerous functions
            foreach ($this->dangerous_functions as $func) {
                if (preg_match('/\b' . $func . '\s*\(/', $content)) {
                    return ['success' => false, 'error' => "Dangerous function detected: $func in " . basename($file)];
                }
            }

            // Check for base64_decode (common in malware)
            if (preg_match('/base64_decode\s*\([^)]*\$/', $content)) {
                return ['success' => false, 'error' => 'Suspicious base64_decode usage detected'];
            }
        }

        return ['success' => true];
    }

    /**
     * Find all PHP files in directory
     *
     * @param string $dir Directory
     * @return array PHP files
     */
    private function find_php_files(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Validate component name (Frankenstyle)
     *
     * @param string $name Component name
     * @return bool Valid or not
     */
    private function validate_component_name(string $name): bool
    {
        // Must be type_name format
        if (!preg_match('/^[a-z]+_[a-z0-9]+$/', $name)) {
            return false;
        }

        // Valid types
        $valid_types = ['tool', 'auth', 'theme', 'report', 'factor'];
        list($type, ) = explode('_', $name, 2);

        return in_array($type, $valid_types);
    }

    /**
     * Check file extension
     *
     * @param string $filename Filename
     * @return bool Valid or not
     */
    private function check_file_extension(string $filename): bool
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'zip';
    }
}
