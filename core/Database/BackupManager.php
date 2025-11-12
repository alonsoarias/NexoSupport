<?php

/**
 * ISER Authentication System - Database Backup Manager
 *
 * Manages database backup and restore operations.
 *
 * @package    ISER\Core\Database
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 8
 */

namespace ISER\Core\Database;

use RuntimeException;
use Exception;
use PDOException;

/**
 * BackupManager Class
 *
 * Handles database backup creation, listing, and deletion.
 */
class BackupManager
{
    /**
     * Backup directory path
     */
    private string $backupDir;

    /**
     * PDOConnection instance
     */
    private PDOConnection $connection;

    /**
     * Database instance
     */
    private Database $database;

    /**
     * Database driver (mysql, pgsql, etc.)
     */
    private string $driver;

    /**
     * Constructor
     *
     * @param PDOConnection $connection PDO connection instance
     * @param Database $database Database instance
     * @param string|null $backupDir Custom backup directory (optional)
     * @throws RuntimeException If backup directory is not writable
     */
    public function __construct(PDOConnection $connection, Database $database, ?string $backupDir = null)
    {
        $this->connection = $connection;
        $this->database = $database;

        // Set backup directory
        if ($backupDir === null) {
            $backupDir = '/var/backups';
        }

        // Ensure backup directory exists and is writable
        $this->backupDir = rtrim($backupDir, '/');

        if (!$this->ensureBackupDirectory()) {
            throw new RuntimeException(
                "Backup directory '{$this->backupDir}' does not exist or is not writable"
            );
        }

        // Detect database driver
        $this->driver = $this->connection->getDriverName();
    }

    /**
     * Ensure backup directory exists and is writable
     *
     * @return bool True if directory is writable
     */
    private function ensureBackupDirectory(): bool
    {
        // Check if directory exists
        if (!file_exists($this->backupDir)) {
            // Try to create directory
            if (!mkdir($this->backupDir, 0755, true)) {
                return false;
            }
        }

        // Check if directory is writable
        return is_dir($this->backupDir) && is_writable($this->backupDir);
    }

    /**
     * Create a database backup
     *
     * @return array Array with backup file info (filename, size, path, created_at)
     * @throws RuntimeException If backup creation fails
     */
    public function createBackup(): array
    {
        try {
            // Generate filename with timestamp
            $timestamp = date('Y-m-d_His', time());
            $filename = "backup_{$timestamp}.sql";
            $filepath = $this->backupDir . '/' . $filename;

            // Execute mysqldump command (if MySQL/MariaDB)
            if ($this->driver === 'mysql') {
                return $this->createMysqlBackup($filepath, $filename);
            } elseif ($this->driver === 'pgsql') {
                return $this->createPostgresBackup($filepath, $filename);
            } else {
                throw new RuntimeException("Unsupported database driver: {$this->driver}");
            }
        } catch (Exception $e) {
            throw new RuntimeException("Backup creation failed: " . $e->getMessage());
        }
    }

    /**
     * Create MySQL/MariaDB backup using mysqldump
     *
     * @param string $filepath Path to save backup file
     * @param string $filename Backup filename
     * @return array Backup file info
     * @throws RuntimeException If mysqldump fails
     */
    private function createMysqlBackup(string $filepath, string $filename): array
    {
        // Get connection details
        $pdo = $this->connection->getConnection();
        $dsn = $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS);

        // Parse DSN to get database name
        $config = $this->connection->getConfig();
        $database = $config['database'] ?? '';

        if (empty($database)) {
            throw new RuntimeException('Unable to determine database name');
        }

        // Build mysqldump command
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 3306;
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        // Escape command parameters
        $host = escapeshellarg($host);
        $database = escapeshellarg($database);
        $username = escapeshellarg($username);
        $filepath = escapeshellarg($filepath);

        // Build command
        $command = "mysqldump --host={$host} --port={$port} --user={$username}";

        if (!empty($password)) {
            $password = escapeshellarg($password);
            $command .= " --password={$password}";
        }

        $command .= " --single-transaction --lock-tables=false {$database} > {$filepath}";

        // Execute command
        $output = null;
        $returnVar = null;
        exec($command . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            @unlink($filepath); // Clean up failed backup
            throw new RuntimeException(
                'mysqldump command failed: ' . implode("\n", $output)
            );
        }

        // Verify file was created
        if (!file_exists($filepath)) {
            throw new RuntimeException('Backup file was not created');
        }

        $fileSize = filesize($filepath);

        if ($fileSize === 0) {
            @unlink($filepath);
            throw new RuntimeException('Backup file is empty');
        }

        return [
            'filename' => $filename,
            'size' => $fileSize,
            'size_human' => $this->formatBytes($fileSize),
            'path' => $filepath,
            'created_at' => time(),
            'created_at_formatted' => date('Y-m-d H:i:s', time()),
        ];
    }

    /**
     * Create PostgreSQL backup using pg_dump
     *
     * @param string $filepath Path to save backup file
     * @param string $filename Backup filename
     * @return array Backup file info
     * @throws RuntimeException If pg_dump fails
     */
    private function createPostgresBackup(string $filepath, string $filename): array
    {
        // Get connection details
        $config = $this->connection->getConfig();
        $database = $config['database'] ?? '';

        if (empty($database)) {
            throw new RuntimeException('Unable to determine database name');
        }

        // Build pg_dump command
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 5432;
        $username = $config['username'] ?? '';

        // Escape command parameters
        $host = escapeshellarg($host);
        $database = escapeshellarg($database);
        $username = escapeshellarg($username);
        $filepath = escapeshellarg($filepath);

        // Build command
        $command = "pg_dump --host={$host} --port={$port} --username={$username}";
        $command .= " {$database} > {$filepath}";

        // Execute command
        $output = null;
        $returnVar = null;
        putenv("PGPASSWORD=" . escapeshellarg($config['password'] ?? ''));
        exec($command . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            @unlink($filepath); // Clean up failed backup
            throw new RuntimeException(
                'pg_dump command failed: ' . implode("\n", $output)
            );
        }

        // Verify file was created
        if (!file_exists($filepath)) {
            throw new RuntimeException('Backup file was not created');
        }

        $fileSize = filesize($filepath);

        if ($fileSize === 0) {
            @unlink($filepath);
            throw new RuntimeException('Backup file is empty');
        }

        return [
            'filename' => $filename,
            'size' => $fileSize,
            'size_human' => $this->formatBytes($fileSize),
            'path' => $filepath,
            'created_at' => time(),
            'created_at_formatted' => date('Y-m-d H:i:s', time()),
        ];
    }

    /**
     * List all backup files
     *
     * @return array Array of backup files with metadata
     */
    public function listBackups(): array
    {
        $backups = [];

        if (!is_dir($this->backupDir)) {
            return $backups;
        }

        try {
            $files = scandir($this->backupDir, SCANDIR_SORT_DESCENDING);

            if (!$files) {
                return $backups;
            }

            foreach ($files as $file) {
                // Only include .sql files
                if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{6}\.sql$/', $file)) {
                    continue;
                }

                $filepath = $this->backupDir . '/' . $file;

                if (!is_file($filepath)) {
                    continue;
                }

                $fileSize = filesize($filepath);
                $fileTime = filemtime($filepath);

                $backups[] = [
                    'filename' => $file,
                    'size' => $fileSize,
                    'size_human' => $this->formatBytes($fileSize),
                    'created_at' => $fileTime,
                    'created_at_formatted' => date('Y-m-d H:i:s', $fileTime),
                    'created_at_date' => date('Y-m-d', $fileTime),
                    'path' => $filepath,
                    'downloadable' => true,
                    'deletable' => true,
                ];
            }

            return $backups;
        } catch (Exception $e) {
            return $backups;
        }
    }

    /**
     * Get a specific backup file
     *
     * @param string $filename Backup filename
     * @return array|null Backup file info or null if not found
     */
    public function getBackup(string $filename): ?array
    {
        // Validate filename format
        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{6}\.sql$/', $filename)) {
            return null;
        }

        $filepath = $this->backupDir . '/' . $filename;

        // Verify file exists and is readable
        if (!file_exists($filepath) || !is_readable($filepath)) {
            return null;
        }

        $fileSize = filesize($filepath);
        $fileTime = filemtime($filepath);

        return [
            'filename' => $filename,
            'size' => $fileSize,
            'size_human' => $this->formatBytes($fileSize),
            'created_at' => $fileTime,
            'created_at_formatted' => date('Y-m-d H:i:s', $fileTime),
            'path' => $filepath,
        ];
    }

    /**
     * Delete a backup file
     *
     * @param string $filename Backup filename
     * @return bool True if deleted successfully
     * @throws RuntimeException If deletion fails
     */
    public function deleteBackup(string $filename): bool
    {
        // Validate filename format to prevent directory traversal
        if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{6}\.sql$/', $filename)) {
            throw new RuntimeException('Invalid backup filename format');
        }

        $filepath = $this->backupDir . '/' . $filename;

        // Verify file exists
        if (!file_exists($filepath)) {
            throw new RuntimeException("Backup file not found: {$filename}");
        }

        // Delete file
        if (!unlink($filepath)) {
            throw new RuntimeException("Failed to delete backup file: {$filename}");
        }

        return true;
    }

    /**
     * Download a backup file (returns file content)
     *
     * @param string $filename Backup filename
     * @return array Array with 'content' and 'size' keys
     * @throws RuntimeException If file cannot be read
     */
    public function downloadBackup(string $filename): array
    {
        $backup = $this->getBackup($filename);

        if (!$backup) {
            throw new RuntimeException("Backup file not found: {$filename}");
        }

        $filepath = $backup['path'];
        $content = file_get_contents($filepath);

        if ($content === false) {
            throw new RuntimeException("Failed to read backup file: {$filename}");
        }

        return [
            'content' => $content,
            'size' => $backup['size'],
            'filename' => $filename,
        ];
    }

    /**
     * Get backup directory path
     *
     * @return string Backup directory path
     */
    public function getBackupDir(): string
    {
        return $this->backupDir;
    }

    /**
     * Check if backup directory is writable
     *
     * @return bool True if writable
     */
    public function isBackupDirWritable(): bool
    {
        return is_writable($this->backupDir);
    }

    /**
     * Get total backup size
     *
     * @return int Total size in bytes
     */
    public function getTotalBackupSize(): int
    {
        $totalSize = 0;
        $backups = $this->listBackups();

        foreach ($backups as $backup) {
            $totalSize += $backup['size'];
        }

        return $totalSize;
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes Size in bytes
     * @return string Human readable size
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
