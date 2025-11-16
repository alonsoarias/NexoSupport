<?php
/**
 * NexoSupport - ZIP Extractor
 *
 * @package    tool_installaddon
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Tools\InstallAddon;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * ZIP Extractor
 *
 * Safely extracts ZIP files
 */
class ZipExtractor
{
    /**
     * Extract ZIP file to temporary directory
     *
     * @param string $zipfile ZIP file path
     * @return array Result with extraction path
     */
    public function extract(string $zipfile): array
    {
        if (!file_exists($zipfile)) {
            return ['success' => false, 'error' => 'ZIP file not found'];
        }

        // Verify ZIP integrity
        if (!$this->verify_zip($zipfile)) {
            return ['success' => false, 'error' => 'ZIP file is corrupted'];
        }

        // Create unique temp directory
        $temp_dir = sys_get_temp_dir() . '/nexosupport_install_' . uniqid();
        if (!mkdir($temp_dir, 0755, true)) {
            return ['success' => false, 'error' => 'Failed to create temp directory'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipfile) !== true) {
            return ['success' => false, 'error' => 'Failed to open ZIP file'];
        }

        // Extract all files with path traversal check
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);

            // Prevent path traversal
            if ($this->check_path_traversal($filename)) {
                $zip->close();
                return ['success' => false, 'error' => 'Path traversal attack detected'];
            }

            // Sanitize filename
            $safe_name = $this->sanitize_filename($filename);
            $zip->extractTo($temp_dir, $filename);
        }

        $zip->close();

        return [
            'success' => true,
            'path' => $temp_dir,
            'files' => $zip->numFiles,
        ];
    }

    /**
     * Verify ZIP integrity
     *
     * @param string $zipfile ZIP file path
     * @return bool Valid or not
     */
    private function verify_zip(string $zipfile): bool
    {
        $zip = new \ZipArchive();
        $result = $zip->open($zipfile, \ZipArchive::CHECKCONS);
        $zip->close();
        return $result === true;
    }

    /**
     * Check for path traversal attempts
     *
     * @param string $path File path
     * @return bool True if path traversal detected
     */
    private function check_path_traversal(string $path): bool
    {
        // Check for .. in path
        if (strpos($path, '..') !== false) {
            return true;
        }

        // Check for absolute paths
        if (substr($path, 0, 1) === '/' || preg_match('/^[a-zA-Z]:/', $path)) {
            return true;
        }

        return false;
    }

    /**
     * Sanitize filename
     *
     * @param string $filename Filename
     * @return string Sanitized filename
     */
    private function sanitize_filename(string $filename): string
    {
        // Remove any null bytes
        $filename = str_replace("\0", '', $filename);

        // Remove leading/trailing dots and slashes
        $filename = trim($filename, './\\');

        return $filename;
    }

    /**
     * Get file list from ZIP
     *
     * @param string $zipfile ZIP file path
     * @return array File list
     */
    public function get_file_list(string $zipfile): array
    {
        $files = [];
        $zip = new \ZipArchive();

        if ($zip->open($zipfile) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $files[] = $zip->getNameIndex($i);
            }
            $zip->close();
        }

        return $files;
    }

    /**
     * Get extraction statistics
     *
     * @param string $zipfile ZIP file path
     * @return array Statistics
     */
    public function get_extraction_stats(string $zipfile): array
    {
        $zip = new \ZipArchive();
        $stats = [
            'files' => 0,
            'size' => 0,
            'compressed_size' => 0,
        ];

        if ($zip->open($zipfile) === true) {
            $stats['files'] = $zip->numFiles;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $file_stats = $zip->statIndex($i);
                $stats['size'] += $file_stats['size'];
                $stats['compressed_size'] += $file_stats['comp_size'];
            }

            $zip->close();
        }

        return $stats;
    }
}
