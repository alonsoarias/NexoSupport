<?php
/**
 * ISER - File Manager
 * @package ISER\Core\Utils
 */

namespace ISER\Core\Utils;

class FileManager
{
    private string $uploadDir;
    private int $maxFileSize;
    private array $allowedMimeTypes;

    /**
     * Constructor
     *
     * @param string $uploadDir Base upload directory
     * @param int $maxFileSize Maximum file size in bytes (default: 5MB)
     * @param array $allowedMimeTypes Allowed MIME types
     */
    public function __construct(
        string $uploadDir,
        int $maxFileSize = 5242880,
        array $allowedMimeTypes = []
    ) {
        $this->uploadDir = rtrim($uploadDir, '/');
        $this->maxFileSize = $maxFileSize;
        $this->allowedMimeTypes = $allowedMimeTypes;

        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload a file
     *
     * @param array $file $_FILES array element
     * @param string|null $customName Custom filename (without extension)
     * @return array Upload result with 'success', 'path', 'filename', 'size', 'mime'
     */
    public function upload(array $file, ?string $customName = null): array
    {
        // Validate file upload
        $validation = $this->validateUpload($file);
        if (!$validation['success']) {
            return $validation;
        }

        // Generate filename
        if ($customName) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $this->sanitizeFilename($customName) . '.' . $extension;
        } else {
            $filename = $this->generateUniqueFilename($file['name']);
        }

        $filePath = $this->uploadDir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'success' => false,
                'error' => 'Failed to move uploaded file'
            ];
        }

        // Set appropriate permissions
        chmod($filePath, 0644);

        return [
            'success' => true,
            'path' => $filePath,
            'filename' => $filename,
            'size' => filesize($filePath),
            'mime' => mime_content_type($filePath)
        ];
    }

    /**
     * Validate file upload
     */
    public function validateUpload(array $file): array
    {
        // Check for upload errors
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'error' => 'Invalid file upload'];
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'error' => 'File exceeds maximum size'];
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'error' => 'No file uploaded'];
            default:
                return ['success' => false, 'error' => 'Unknown upload error'];
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'success' => false,
                'error' => 'File size exceeds maximum allowed (' . $this->formatBytes($this->maxFileSize) . ')'
            ];
        }

        // Check MIME type if restrictions are set
        if (!empty($this->allowedMimeTypes)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, $this->allowedMimeTypes)) {
                return [
                    'success' => false,
                    'error' => 'File type not allowed. Allowed types: ' . implode(', ', $this->allowedMimeTypes)
                ];
            }
        }

        return ['success' => true];
    }

    /**
     * Delete a file
     */
    public function delete(string $filename): bool
    {
        $filePath = $this->uploadDir . '/' . basename($filename);

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Get file information
     */
    public function getFileInfo(string $filename): array|false
    {
        $filePath = $this->uploadDir . '/' . basename($filename);

        if (!file_exists($filePath)) {
            return false;
        }

        return [
            'filename' => basename($filePath),
            'path' => $filePath,
            'size' => filesize($filePath),
            'mime' => mime_content_type($filePath),
            'modified' => filemtime($filePath),
        ];
    }

    /**
     * Resize image
     *
     * @param string $filename Source filename
     * @param int $maxWidth Maximum width
     * @param int $maxHeight Maximum height
     * @param string|null $outputFilename Output filename (null = overwrite original)
     * @return bool Success status
     */
    public function resizeImage(
        string $filename,
        int $maxWidth,
        int $maxHeight,
        ?string $outputFilename = null
    ): bool {
        $sourcePath = $this->uploadDir . '/' . basename($filename);

        if (!file_exists($sourcePath)) {
            return false;
        }

        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        [$width, $height, $type] = $imageInfo;

        // Calculate new dimensions
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        if ($ratio >= 1) {
            return true; // Image is already smaller
        }

        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);

        // Create image resource from source
        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_GIF => imagecreatefromgif($sourcePath),
            default => false
        };

        if (!$source) {
            return false;
        }

        // Create new image
        $destination = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Determine output path
        $outputPath = $outputFilename
            ? $this->uploadDir . '/' . basename($outputFilename)
            : $sourcePath;

        // Save image
        $result = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($destination, $outputPath, 90),
            IMAGETYPE_PNG => imagepng($destination, $outputPath, 9),
            IMAGETYPE_GIF => imagegif($destination, $outputPath),
            default => false
        };

        // Free memory
        imagedestroy($source);
        imagedestroy($destination);

        return $result;
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        $basename = $this->sanitizeFilename($basename);

        $filename = $basename . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;

        // Ensure uniqueness
        while (file_exists($this->uploadDir . '/' . $filename)) {
            $filename = $basename . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        }

        return $filename;
    }

    /**
     * Sanitize filename
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove extension if present
        $filename = pathinfo($filename, PATHINFO_FILENAME);

        // Convert to lowercase
        $filename = strtolower($filename);

        // Replace spaces and special characters
        $filename = preg_replace('/[^a-z0-9_-]/', '_', $filename);

        // Remove consecutive underscores
        $filename = preg_replace('/_+/', '_', $filename);

        // Trim underscores from ends
        $filename = trim($filename, '_');

        return $filename ?: 'file';
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get upload directory
     */
    public function getUploadDir(): string
    {
        return $this->uploadDir;
    }

    /**
     * Set allowed MIME types
     */
    public function setAllowedMimeTypes(array $mimeTypes): void
    {
        $this->allowedMimeTypes = $mimeTypes;
    }

    /**
     * Set maximum file size
     */
    public function setMaxFileSize(int $bytes): void
    {
        $this->maxFileSize = $bytes;
    }
}
