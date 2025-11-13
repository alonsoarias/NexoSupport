<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\Controllers\BaseController;
use ISER\Core\Http\Response;
use ISER\Core\Database\Database;
use ISER\Core\Database\BackupManager;
use ISER\User\UserManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Exception;

/**
 * Admin Backup Controller (REFACTORIZADO con BaseController)
 *
 * Manages database backups and restore operations.
 * Only accessible to admin users.
 *
 * Extiende BaseController para reducir código duplicado.
 */
class AdminBackupController extends BaseController
{
    private UserManager $userManager;
    private BackupManager $backupManager;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->userManager = new UserManager($db);

        // Initialize backup manager
        $this->backupManager = new BackupManager(
            $db->getConnection(),
            $db
        );
    }

    /**
     * Check if user is admin
     */
    private function isAdmin(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return false;
        }

        $roles = $this->userManager->getUserRoles((int)$userId);
        foreach ($roles as $role) {
            if ($role['slug'] === 'admin') {
                return true;
            }
        }

        return false;
    }

    /**
     * Show backup list
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication and permissions
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if (!$this->isAdmin()) {
            $_SESSION['error'] = $this->translator->translate('admin.messages.permission_denied');
            return $this->redirect('/dashboard');
        }

        try {
            $backups = $this->backupManager->listBackups();
            $totalBackupSize = $this->backupManager->getTotalBackupSize();
            $backupDir = $this->backupManager->getBackupDir();
            $isWritable = $this->backupManager->isBackupDirWritable();

            // Get current user
            $currentUser = $this->userManager->getUserById((int)$_SESSION['user_id']);

            $data = [
                'page_title' => $this->translator->translate('backup.page_title'),
                'header_title' => $this->translator->translate('backup.title'),
                'backups' => $backups,
                'has_backups' => count($backups) > 0,
                'no_backups' => count($backups) === 0,
                'backup_dir' => $backupDir,
                'is_writable' => $isWritable,
                'total_backup_size' => $this->formatBytes($totalBackupSize),
                'current_user' => [
                    'full_name' => trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')),
                    'email' => $currentUser['email'] ?? '',
                ],
                'translations' => [
                    'create_backup' => $this->translator->translate('backup.create_backup'),
                    'backup_list' => $this->translator->translate('backup.backup_list'),
                    'filename' => $this->translator->translate('backup.filename'),
                    'size' => $this->translator->translate('backup.size'),
                    'date' => $this->translator->translate('backup.date'),
                    'actions' => $this->translator->translate('admin.table.actions'),
                    'download' => $this->translator->translate('backup.download'),
                    'delete' => $this->translator->translate('backup.delete'),
                    'no_backups_yet' => $this->translator->translate('backup.no_backups_yet'),
                    'warning_restore' => $this->translator->translate('backup.warning_restore'),
                    'restore_instructions' => $this->translator->translate('backup.restore_instructions'),
                    'total_backup_size' => $this->translator->translate('backup.total_backup_size'),
                    'backup_dir_warning' => $this->translator->translate('backup.backup_dir_warning'),
                    'creating_backup' => $this->translator->translate('backup.creating_backup'),
                    'backup_created' => $this->translator->translate('backup.backup_created'),
                    'error_creating_backup' => $this->translator->translate('backup.error_creating_backup'),
                ],
            ];

            return $this->renderWithLayout('admin/backup/index', $data);
        } catch (Exception $e) {
            error_log('Backup list error: ' . $e->getMessage());
            $_SESSION['error'] = $this->translator->translate('backup.error_listing_backups');
            return $this->redirect('/admin');
        }
    }

    /**
     * Create new backup
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication and permissions
        if (!$this->isAuthenticated()) {
            return $this->jsonError('Unauthorized', [], 401);
        }

        if (!$this->isAdmin()) {
            return $this->jsonError('Permission denied', [], 403);
        }

        try {
            // Create backup
            $backup = $this->backupManager->createBackup();

            // Log audit event
            $this->logAudit(
                'create',
                'database_backup',
                'backup_' . date('Y-m-d_His', time()),
                null,
                ['filename' => $backup['filename'], 'size' => $backup['size']]
            );

            return $this->jsonSuccess(
                $this->translator->translate('backup.backup_created_success'),
                [
                    'backup' => [
                        'filename' => $backup['filename'],
                        'size' => $backup['size_human'],
                        'created_at' => $backup['created_at_formatted'],
                    ],
                ]
            );
        } catch (Exception $e) {
            error_log('Backup creation error: ' . $e->getMessage());

            // Log audit event (failure)
            $this->logAudit(
                'create',
                'database_backup',
                'backup_error',
                null,
                ['error' => $e->getMessage()]
            );

            return $this->jsonError(
                $this->translator->translate('backup.backup_creation_failed') . ': ' . $e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * Download backup file
     */
    public function download(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication and permissions
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if (!$this->isAdmin()) {
            return Response::error(403, 'Permission denied');
        }

        try {
            // Get filename from URL
            $uri = $request->getUri()->getPath();
            $parts = explode('/', trim($uri, '/'));
            $filename = $parts[3] ?? '';

            if (empty($filename)) {
                return Response::error(400, 'Invalid filename');
            }

            // Download backup
            $backup = $this->backupManager->downloadBackup($filename);

            // Log audit event
            $this->logAudit(
                'download',
                'database_backup',
                $filename,
                null,
                ['size' => $backup['size']]
            );

            // Return file download response using streaming
            // Create temporary file for download
            $tempFile = tempnam(sys_get_temp_dir(), 'backup_');
            file_put_contents($tempFile, $backup['content']);

            $response = Response::download($tempFile, $filename);

            // Schedule file deletion after response is sent
            register_shutdown_function(function() use ($tempFile) {
                @unlink($tempFile);
            });

            return $response;
        } catch (Exception $e) {
            error_log('Backup download error: ' . $e->getMessage());

            // Log audit event (failure)
            $this->logAudit(
                'download',
                'database_backup',
                'error',
                null,
                ['error' => $e->getMessage()]
            );

            return Response::error(500, 'Failed to download backup');
        }
    }

    /**
     * Delete backup file
     */
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication and permissions
        if (!$this->isAuthenticated()) {
            return $this->jsonError('Unauthorized', [], 401);
        }

        if (!$this->isAdmin()) {
            return $this->jsonError('Permission denied', [], 403);
        }

        try {
            // Get filename from URL
            $uri = $request->getUri()->getPath();
            $parts = explode('/', trim($uri, '/'));
            $filename = $parts[3] ?? '';

            if (empty($filename)) {
                return $this->jsonError('Invalid filename', [], 400);
            }

            // Delete backup
            $this->backupManager->deleteBackup($filename);

            // Log audit event
            $this->logAudit(
                'delete',
                'database_backup',
                $filename,
                ['filename' => $filename],
                null
            );

            return $this->jsonSuccess(
                $this->translator->translate('backup.backup_deleted_success')
            );
        } catch (Exception $e) {
            error_log('Backup deletion error: ' . $e->getMessage());

            // Log audit event (failure)
            $this->logAudit(
                'delete',
                'database_backup',
                'error',
                null,
                ['error' => $e->getMessage()]
            );

            return $this->jsonError(
                $this->translator->translate('backup.backup_deletion_failed'),
                [],
                500
            );
        }
    }

    /**
     * Format bytes to human readable format
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
