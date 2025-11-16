<?php
/**
 * NexoSupport - Addon Installer
 *
 * @package    tool_installaddon
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Tools\InstallAddon;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Addon Installer
 *
 * Installs plugins from ZIP files
 */
class AddonInstaller
{
    /** @var string Temporary directory for extraction */
    private $temp_dir;

    /** @var array Installation log */
    private $log = [];

    /**
     * Install plugin from ZIP file
     *
     * @param string $zip_path Path to ZIP file
     * @return array Result
     */
    public function install_from_zip(string $zip_path): array
    {
        $this->log[] = "Starting installation from: $zip_path";

        // Step 1: Extract to temp
        $extractor = new ZipExtractor();
        $extract_result = $extractor->extract($zip_path);

        if (!$extract_result['success']) {
            return ['success' => false, 'error' => $extract_result['error']];
        }

        $this->temp_dir = $extract_result['path'];
        $this->log[] = "Extracted to: {$this->temp_dir}";

        // Step 2: Validate structure
        $validator = new AddonValidator();
        $valid_result = $validator->validate_structure($this->temp_dir);

        if (!$valid_result['success']) {
            $this->cleanup_temp($this->temp_dir);
            return ['success' => false, 'error' => $valid_result['error']];
        }

        $this->log[] = "Validation passed";

        // Step 3: Detect plugin type
        $type = $this->detect_plugin_type($this->temp_dir);
        $component = $valid_result['component'];

        $this->log[] = "Detected: $component (type: $type)";

        // Step 4: Copy to destination
        $copy_result = $this->copy_to_destination($this->temp_dir, $type, $component);

        if (!$copy_result['success']) {
            $this->cleanup_temp($this->temp_dir);
            return ['success' => false, 'error' => $copy_result['error']];
        }

        $this->log[] = "Copied to destination: {$copy_result['path']}";

        // Step 5: Cleanup temp
        $this->cleanup_temp($this->temp_dir);

        return [
            'success' => true,
            'component' => $component,
            'type' => $type,
            'path' => $copy_result['path'],
            'log' => $this->log,
        ];
    }

    /**
     * Detect plugin type from component name
     *
     * @param string $dir Directory path
     * @return string Plugin type
     */
    private function detect_plugin_type(string $dir): string
    {
        if (file_exists($dir . '/version.php')) {
            $content = file_get_contents($dir . '/version.php');
            if (preg_match('/\$plugin->component\s*=\s*[\'"]([^_]+)_/', $content, $matches)) {
                return $matches[1]; // tool, auth, theme, etc.
            }
        }
        return 'unknown';
    }

    /**
     * Copy plugin to destination directory
     *
     * @param string $source Source directory
     * @param string $type Plugin type
     * @param string $component Component name
     * @return array Result
     */
    private function copy_to_destination(string $source, string $type, string $component): array
    {
        // Determine destination based on type
        $base = __DIR__ . '/../../../../';
        $destinations = [
            'tool' => 'admin/tool/',
            'auth' => 'modules/Auth/',
            'theme' => 'theme/',
            'report' => 'modules/Report/',
        ];

        if (!isset($destinations[$type])) {
            return ['success' => false, 'error' => "Unknown plugin type: $type"];
        }

        // Extract name from component (e.g., tool_uploaduser -> uploaduser)
        $name = substr($component, strlen($type) + 1);
        $dest_path = $base . $destinations[$type] . $name;

        // Check if already exists
        if (file_exists($dest_path)) {
            return ['success' => false, 'error' => "Plugin already exists: $dest_path"];
        }

        // Create destination and copy files
        if (!$this->recursive_copy($source, $dest_path)) {
            return ['success' => false, 'error' => "Failed to copy files"];
        }

        return ['success' => true, 'path' => $dest_path];
    }

    /**
     * Recursive copy
     *
     * @param string $src Source
     * @param string $dst Destination
     * @return bool Success
     */
    private function recursive_copy(string $src, string $dst): bool
    {
        if (!file_exists($src)) return false;

        if (is_dir($src)) {
            if (!mkdir($dst, 0755, true) && !is_dir($dst)) {
                return false;
            }

            $files = scandir($src);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $this->recursive_copy("$src/$file", "$dst/$file");
                }
            }
        } else {
            copy($src, $dst);
        }

        return true;
    }

    /**
     * Cleanup temporary directory
     *
     * @param string $dir Directory to remove
     * @return void
     */
    private function cleanup_temp(string $dir): void
    {
        if (file_exists($dir)) {
            $this->recursive_delete($dir);
        }
    }

    /**
     * Recursive delete
     *
     * @param string $dir Directory
     * @return void
     */
    private function recursive_delete(string $dir): void
    {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $path = "$dir/$file";
                    is_dir($path) ? $this->recursive_delete($path) : unlink($path);
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Rollback installation
     *
     * @param string $component Component name
     * @return bool Success
     */
    public function rollback_installation(string $component): bool
    {
        $this->log[] = "Rolling back installation of: $component";
        // Implementation would remove installed files
        return true;
    }
}
