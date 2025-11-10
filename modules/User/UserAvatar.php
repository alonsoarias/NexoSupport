<?php
/**
 * ISER - User Avatar Manager
 * @package ISER\Modules\User
 */

namespace ISER\User;

use ISER\Core\Database\Database;
use ISER\Core\Utils\FileManager;

class UserAvatar
{
    private Database $db;
    private FileManager $fileManager;
    private string $avatarDir;
    private int $maxAvatarSize;
    private array $allowedTypes;

    /**
     * Constructor
     *
     * @param Database $db Database instance
     * @param string $avatarDir Avatar storage directory
     * @param int $maxAvatarSize Maximum avatar size in bytes (default: 2MB)
     */
    public function __construct(
        Database $db,
        string $avatarDir,
        int $maxAvatarSize = 2097152
    ) {
        $this->db = $db;
        $this->avatarDir = rtrim($avatarDir, '/');
        $this->maxAvatarSize = $maxAvatarSize;
        $this->allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        // Initialize FileManager
        $this->fileManager = new FileManager(
            $this->avatarDir,
            $this->maxAvatarSize,
            $this->allowedTypes
        );

        // Create avatar directory if it doesn't exist
        if (!is_dir($this->avatarDir)) {
            mkdir($this->avatarDir, 0755, true);
        }
    }

    /**
     * Get user avatar information
     */
    public function getAvatar(int $userId): array|false
    {
        return $this->db->selectOne('user_avatars', ['userid' => $userId]);
    }

    /**
     * Upload and set user avatar
     *
     * @param int $userId User ID
     * @param array $file $_FILES array element
     * @param bool $resize Whether to resize the avatar (default: true)
     * @return array Result with 'success' and either avatar data or 'error'
     */
    public function uploadAvatar(int $userId, array $file, bool $resize = true): array
    {
        // Validate upload
        $validation = $this->fileManager->validateUpload($file);
        if (!$validation['success']) {
            return $validation;
        }

        // Delete existing avatar if present
        $existingAvatar = $this->getAvatar($userId);
        if ($existingAvatar && !$existingAvatar['is_default']) {
            $this->deleteAvatar($userId);
        }

        // Upload file with custom name
        $customName = 'avatar_' . $userId;
        $upload = $this->fileManager->upload($file, $customName);

        if (!$upload['success']) {
            return $upload;
        }

        // Resize avatar if requested
        if ($resize) {
            $this->fileManager->resizeImage($upload['filename'], 200, 200);

            // Update file size after resize
            $fileInfo = $this->fileManager->getFileInfo($upload['filename']);
            if ($fileInfo) {
                $upload['size'] = $fileInfo['size'];
            }
        }

        // Save avatar info to database
        $now = time();
        $avatarData = [
            'userid' => $userId,
            'filename' => $upload['filename'],
            'filesize' => $upload['size'],
            'mimetype' => $upload['mime'],
            'filepath' => $upload['path'],
            'is_default' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        // Check if avatar record exists
        if ($existingAvatar) {
            // Update existing record
            $this->db->update('user_avatars', $avatarData, ['userid' => $userId]);
        } else {
            // Insert new record
            $this->db->insert('user_avatars', $avatarData);
        }

        return [
            'success' => true,
            'avatar' => $avatarData
        ];
    }

    /**
     * Delete user avatar
     */
    public function deleteAvatar(int $userId): bool
    {
        $avatar = $this->getAvatar($userId);

        if (!$avatar) {
            return false;
        }

        // Don't delete default avatars from filesystem
        if (!$avatar['is_default']) {
            $this->fileManager->delete($avatar['filename']);
        }

        // Remove from database
        return $this->db->delete('user_avatars', ['userid' => $userId]) > 0;
    }

    /**
     * Get avatar URL for a user
     *
     * @param int $userId User ID
     * @param bool $absolute Whether to return absolute URL
     * @return string Avatar URL or default avatar URL
     */
    public function getAvatarUrl(int $userId, bool $absolute = false): string
    {
        $avatar = $this->getAvatar($userId);

        if ($avatar && file_exists($avatar['filepath'])) {
            $baseUrl = $absolute ? $this->getBaseUrl() : '';
            $relativePath = str_replace(ISER_BASE_DIR, '', $avatar['filepath']);
            return $baseUrl . $relativePath;
        }

        // Return default avatar
        return $this->getDefaultAvatarUrl($absolute);
    }

    /**
     * Get default avatar URL
     */
    public function getDefaultAvatarUrl(bool $absolute = false): string
    {
        $baseUrl = $absolute ? $this->getBaseUrl() : '';
        return $baseUrl . '/public_html/assets/images/default-avatar.png';
    }

    /**
     * Set default avatar for user
     */
    public function setDefaultAvatar(int $userId): bool
    {
        $existing = $this->getAvatar($userId);

        if ($existing && !$existing['is_default']) {
            $this->fileManager->delete($existing['filename']);
        }

        $now = time();
        $avatarData = [
            'userid' => $userId,
            'filename' => 'default-avatar.png',
            'filesize' => 0,
            'mimetype' => 'image/png',
            'filepath' => ISER_BASE_DIR . '/public_html/assets/images/default-avatar.png',
            'is_default' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        if ($existing) {
            return $this->db->update('user_avatars', $avatarData, ['userid' => $userId]) > 0;
        } else {
            return $this->db->insert('user_avatars', $avatarData) !== false;
        }
    }

    /**
     * Get avatar file path
     */
    public function getAvatarPath(int $userId): string|false
    {
        $avatar = $this->getAvatar($userId);

        if ($avatar && file_exists($avatar['filepath'])) {
            return $avatar['filepath'];
        }

        return false;
    }

    /**
     * Check if user has custom avatar
     */
    public function hasCustomAvatar(int $userId): bool
    {
        $avatar = $this->getAvatar($userId);
        return $avatar && !$avatar['is_default'] && file_exists($avatar['filepath']);
    }

    /**
     * Get avatar thumbnail URL (for now, same as regular avatar)
     */
    public function getThumbnailUrl(int $userId, bool $absolute = false): string
    {
        // Could implement separate thumbnail logic in the future
        return $this->getAvatarUrl($userId, $absolute);
    }

    /**
     * Get base URL for absolute URLs
     */
    private function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    /**
     * Get avatar size statistics
     */
    public function getAvatarStats(int $userId): array
    {
        $avatar = $this->getAvatar($userId);

        if (!$avatar) {
            return [
                'exists' => false,
                'is_default' => true,
                'size' => 0,
                'formatted_size' => '0 B',
            ];
        }

        return [
            'exists' => true,
            'is_default' => (bool)$avatar['is_default'],
            'size' => $avatar['filesize'],
            'formatted_size' => $this->formatBytes($avatar['filesize']),
            'mime_type' => $avatar['mimetype'],
            'uploaded' => $avatar['timecreated'],
        ];
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Bulk delete avatars for multiple users
     */
    public function bulkDeleteAvatars(array $userIds): int
    {
        $deleted = 0;

        foreach ($userIds as $userId) {
            if ($this->deleteAvatar($userId)) {
                $deleted++;
            }
        }

        return $deleted;
    }
}
