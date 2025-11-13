<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\Controllers\BaseController;
use ISER\Core\Database\Database;
use ISER\User\UserManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Admin Email Queue Controller (REFACTORIZADO con BaseController) - FASE 8
 * Manages email queue viewing, filtering, and operations
 *
 * Extiende BaseController para reducir código duplicado.
 */
class AdminEmailQueueController extends BaseController
{
    private UserManager $userManager;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->userManager = new UserManager($db);
    }

    /**
     * Check if user has admin role
     */
    private function isAdmin(): bool
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $userId = $_SESSION['user_id'];
        $user = $this->userManager->getUserById($userId);
        if (!$user) {
            return false;
        }

        $roles = $this->userManager->getUserRoles($userId);
        foreach ($roles as $role) {
            if ($role['slug'] === 'admin') {
                return true;
            }
        }

        return false;
    }

    /**
     * List queued emails with filters
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication and permissions
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if (!$this->isAdmin()) {
            $_SESSION['error'] = $this->translator->translate('errors.permission_denied');
            return $this->redirect('/dashboard');
        }

        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $status = $request->getQueryParams()['status'] ?? null;
        $limit = 50;
        $offset = ($page - 1) * $limit;

        // Build query
        $query = 'SELECT * FROM ' . $this->db->table('email_queue') . ' WHERE 1=1';
        $params = [];

        if ($status && in_array($status, ['pending', 'sent', 'failed'])) {
            $query .= ' AND status = :status';
            $params[':status'] = $status;
        }

        // Get total count for pagination
        $countQuery = 'SELECT COUNT(*) as count FROM ' . $this->db->table('email_queue') . ' WHERE 1=1';
        if ($status && in_array($status, ['pending', 'sent', 'failed'])) {
            $countQuery .= ' AND status = :status';
        }
        $countResult = $this->db->getConnection()->fetchOne($countQuery, $params);
        $total = (int)($countResult['count'] ?? 0);
        $totalPages = (int)ceil($total / $limit);

        // Get paginated results
        $query .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $emails = $this->db->getConnection()->fetchAll($query, $params);

        // Format emails for template
        $formattedEmails = array_map(function ($email) {
            return [
                'id' => $email['id'],
                'to_email' => $email['to_email'],
                'subject' => $email['subject'],
                'status' => $email['status'],
                'status_label' => $this->getStatusLabel($email['status']),
                'status_badge_class' => $this->getStatusBadgeClass($email['status']),
                'attempts' => $email['attempts'],
                'created_at' => date('Y-m-d H:i', $email['created_at'] ?? time()),
                'last_attempt_at' => $email['last_attempt_at'] ? date('Y-m-d H:i', $email['last_attempt_at']) : '-',
                'has_error' => !empty($email['error_message']),
                'error_preview' => strlen($email['error_message'] ?? '') > 50
                    ? substr($email['error_message'], 0, 47) . '...'
                    : ($email['error_message'] ?? ''),
            ];
        }, $emails);

        // Get statistics
        $statsPending = $this->db->getConnection()->fetchOne(
            'SELECT COUNT(*) as count FROM ' . $this->db->table('email_queue') . ' WHERE status = :status',
            [':status' => 'pending']
        );
        $statsSent = $this->db->getConnection()->fetchOne(
            'SELECT COUNT(*) as count FROM ' . $this->db->table('email_queue') . ' WHERE status = :status',
            [':status' => 'sent']
        );
        $statsFailed = $this->db->getConnection()->fetchOne(
            'SELECT COUNT(*) as count FROM ' . $this->db->table('email_queue') . ' WHERE status = :status',
            [':status' => 'failed']
        );

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('email_queue.title'),
            'header_title' => $this->translator->translate('email_queue.title'),
            'emails' => $formattedEmails,
            'pagination' => [
                'current' => $page,
                'total' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages,
                'prev_page' => $page - 1,
                'next_page' => $page + 1,
                'showing_from' => $offset + 1,
                'showing_to' => min($offset + $limit, $total),
                'total' => $total,
            ],
            'stats' => [
                'pending' => (int)($statsPending['count'] ?? 0),
                'sent' => (int)($statsSent['count'] ?? 0),
                'failed' => (int)($statsFailed['count'] ?? 0),
                'total' => $total,
            ],
            'filters' => [
                'status' => $status,
                'status_label' => $status ? $this->getStatusLabel($status) : '',
            ],
            'has_filters' => !empty($status),
            'trans' => [
                'title' => $this->translator->translate('email_queue.title'),
                'no_emails' => $this->translator->translate('email_queue.no_emails'),
                'back' => $this->translator->translate('common.back'),
                'status' => $this->translator->translate('email_queue.status'),
                'to_email' => $this->translator->translate('email_queue.to_email'),
                'subject' => $this->translator->translate('email_queue.subject'),
                'attempts' => $this->translator->translate('email_queue.attempts'),
                'created_at' => $this->translator->translate('email_queue.created_at'),
                'actions' => $this->translator->translate('common.actions'),
                'view' => $this->translator->translate('common.view'),
                'retry' => $this->translator->translate('email_queue.retry'),
                'delete' => $this->translator->translate('common.delete'),
                'clear' => $this->translator->translate('email_queue.clear'),
                'filter' => $this->translator->translate('email_queue.filter'),
                'clear_filters' => $this->translator->translate('email_queue.clear_filters'),
                'pending' => $this->translator->translate('email_queue.status_pending'),
                'sent' => $this->translator->translate('email_queue.status_sent'),
                'failed' => $this->translator->translate('email_queue.status_failed'),
            ],
        ];

        return $this->renderWithLayout('admin/email-queue/index', $data);
    }

    /**
     * View email details
     */
    public function view(ServerRequestInterface $request, int $id): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if (!$this->isAdmin()) {
            $_SESSION['error'] = $this->translator->translate('errors.permission_denied');
            return $this->redirect('/dashboard');
        }

        $email = $this->db->getConnection()->fetchOne(
            'SELECT * FROM ' . $this->db->table('email_queue') . ' WHERE id = :id',
            [':id' => $id]
        );

        if (!$email) {
            $_SESSION['error'] = $this->translator->translate('email_queue.not_found');
            return $this->redirect('/admin/email-queue');
        }

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('email_queue.view_title'),
            'header_title' => $this->translator->translate('email_queue.view_title'),
            'email' => [
                'id' => $email['id'],
                'to_email' => $email['to_email'],
                'subject' => $email['subject'],
                'body' => $email['body'],
                'status' => $email['status'],
                'status_label' => $this->getStatusLabel($email['status']),
                'status_badge_class' => $this->getStatusBadgeClass($email['status']),
                'attempts' => $email['attempts'],
                'created_at' => date('Y-m-d H:i:s', $email['created_at'] ?? time()),
                'updated_at' => date('Y-m-d H:i:s', $email['updated_at'] ?? time()),
                'last_attempt_at' => $email['last_attempt_at'] ? date('Y-m-d H:i:s', $email['last_attempt_at']) : '-',
                'error_message' => $email['error_message'] ?? '',
                'has_error' => !empty($email['error_message']),
                'can_retry' => $email['status'] === 'failed',
            ],
            'trans' => [
                'title' => $this->translator->translate('email_queue.title'),
                'to_email' => $this->translator->translate('email_queue.to_email'),
                'subject' => $this->translator->translate('email_queue.subject'),
                'body' => $this->translator->translate('email_queue.body'),
                'status' => $this->translator->translate('email_queue.status'),
                'attempts' => $this->translator->translate('email_queue.attempts'),
                'created_at' => $this->translator->translate('email_queue.created_at'),
                'updated_at' => $this->translator->translate('email_queue.updated_at'),
                'last_attempt_at' => $this->translator->translate('email_queue.last_attempt_at'),
                'error_message' => $this->translator->translate('email_queue.error_message'),
                'back' => $this->translator->translate('common.back'),
                'retry' => $this->translator->translate('email_queue.retry'),
                'delete' => $this->translator->translate('common.delete'),
            ],
        ];

        return $this->renderWithLayout('admin/email-queue/view', $data);
    }

    /**
     * Retry sending a failed email
     */
    public function retry(ServerRequestInterface $request, int $id): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if (!$this->isAdmin()) {
            $_SESSION['error'] = $this->translator->translate('errors.permission_denied');
            return $this->redirect('/dashboard');
        }

        $email = $this->db->getConnection()->fetchOne(
            'SELECT * FROM ' . $this->db->table('email_queue') . ' WHERE id = :id',
            [':id' => $id]
        );

        if (!$email) {
            $_SESSION['error'] = $this->translator->translate('email_queue.not_found');
            return $this->redirect('/admin/email-queue');
        }

        // Reset to pending status
        $this->db->getConnection()->update(
            $this->db->table('email_queue'),
            [
                'status' => 'pending',
                'attempts' => 0,
                'error_message' => null,
                'updated_at' => time(),
            ],
            ['id' => $id]
        );

        $_SESSION['success'] = $this->translator->translate('email_queue.retry_success');
        return $this->redirect('/admin/email-queue');
    }

    /**
     * Delete an email from queue
     */
    public function delete(ServerRequestInterface $request, int $id): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if (!$this->isAdmin()) {
            $_SESSION['error'] = $this->translator->translate('errors.permission_denied');
            return $this->redirect('/dashboard');
        }

        $email = $this->db->getConnection()->fetchOne(
            'SELECT * FROM ' . $this->db->table('email_queue') . ' WHERE id = :id',
            [':id' => $id]
        );

        if (!$email) {
            $_SESSION['error'] = $this->translator->translate('email_queue.not_found');
            return $this->redirect('/admin/email-queue');
        }

        $this->db->getConnection()->delete(
            $this->db->table('email_queue'),
            ['id' => $id]
        );

        $_SESSION['success'] = $this->translator->translate('email_queue.delete_success');
        return $this->redirect('/admin/email-queue');
    }

    /**
     * Clear sent/failed emails (older than 30 days)
     */
    public function clear(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if (!$this->isAdmin()) {
            $_SESSION['error'] = $this->translator->translate('errors.permission_denied');
            return $this->redirect('/dashboard');
        }

        $thirtyDaysAgo = time() - (30 * 24 * 60 * 60);

        $deleted = $this->db->getConnection()->delete(
            $this->db->table('email_queue'),
            'status IN (\'sent\', \'failed\') AND created_at < :cutoff_date',
            [':cutoff_date' => $thirtyDaysAgo]
        );

        $_SESSION['success'] = $this->translator->translate('email_queue.clear_success', [
            'count' => $deleted,
        ]);

        return $this->redirect('/admin/email-queue');
    }

    /**
     * Get status label for display
     */
    private function getStatusLabel(string $status): string
    {
        return $this->translator->translate('email_queue.status_' . $status, [], 'email_queue', $status);
    }

    /**
     * Get CSS class for status badge
     */
    private function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'pending' => 'badge-warning',
            'sent' => 'badge-success',
            'failed' => 'badge-danger',
            default => 'badge-secondary',
        };
    }
}
