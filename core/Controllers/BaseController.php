<?php

declare(strict_types=1);

namespace ISER\Core\Controllers;

use ISER\Controllers\Traits\NavigationTrait;
use ISER\Core\Database\Database;
use ISER\Core\Http\Response;
use ISER\Core\View\MustacheRenderer;
use ISER\Core\I18n\Translator;
use Psr\Http\Message\ResponseInterface;

/**
 * BaseController - Abstract base class for all controllers
 *
 * Provides common functionality to reduce code duplication across controllers:
 * - Rendering with layouts
 * - Navigation integration
 * - Response helpers (HTML, JSON, redirect)
 * - Session management
 * - Flash messages
 * - Audit logging
 * - User data access
 *
 * USAGE:
 * ```php
 * class UserController extends BaseController
 * {
 *     public function index(ServerRequestInterface $request): ResponseInterface
 *     {
 *         $users = $this->userManager->getAll();
 *
 *         return $this->render('users/index', [
 *             'users' => $users,
 *             'title' => 'Users List'
 *         ], '/admin/users');
 *     }
 * }
 * ```
 *
 * @package ISER\Core\Controllers
 * @author ISER Development
 */
abstract class BaseController
{
    use NavigationTrait;

    /**
     * Database instance
     * @var Database
     */
    protected Database $db;

    /**
     * Mustache renderer instance
     * @var MustacheRenderer
     */
    protected MustacheRenderer $renderer;

    /**
     * Translator instance
     * @var Translator
     */
    protected Translator $translator;

    /**
     * Constructor
     *
     * @param Database $db Database instance
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->renderer = MustacheRenderer::getInstance();
        $this->translator = Translator::getInstance();
    }

    /**
     * Render a view with layout and automatic navigation enrichment
     *
     * @param string $view View template path (e.g., 'users/index')
     * @param array $data Data to pass to the view
     * @param string $activeRoute Current active route for navigation highlighting (e.g., '/admin/users')
     * @param string $layout Layout template (default: 'layouts/app')
     * @param array|null $customBreadcrumbs Custom breadcrumbs (optional)
     * @return ResponseInterface PSR-7 Response
     */
    protected function render(
        string $view,
        array $data = [],
        string $activeRoute = '/',
        string $layout = 'layouts/app',
        ?array $customBreadcrumbs = null
    ): ResponseInterface {
        // Enrich data with navigation (user, breadcrumbs, active states)
        $data = $this->enrichWithNavigation($data, $activeRoute, $customBreadcrumbs);

        // Add flash messages if any
        $data = $this->addFlashMessages($data);

        // Add common i18n data
        $data['locale'] = $this->translator->getLocale();

        // Render with layout
        $html = $this->renderer->render($view, $data, $layout);

        return Response::html($html);
    }

    /**
     * Render a view with layout WITHOUT navigation enrichment
     * Useful for public pages, login, error pages, etc.
     *
     * @param string $view View template path
     * @param array $data Data to pass to the view
     * @param string $layout Layout template
     * @return ResponseInterface PSR-7 Response
     */
    protected function renderWithLayout(
        string $view,
        array $data = [],
        string $layout = 'layouts/app'
    ): ResponseInterface {
        // Add flash messages
        $data = $this->addFlashMessages($data);

        // Add locale
        $data['locale'] = $this->translator->getLocale();

        $html = $this->renderer->render($view, $data, $layout);
        return Response::html($html);
    }

    /**
     * Return a JSON response
     *
     * @param array $data Data to encode as JSON
     * @param int $statusCode HTTP status code (default: 200)
     * @return ResponseInterface PSR-7 Response
     */
    protected function json(array $data, int $statusCode = 200): ResponseInterface
    {
        return Response::json($data, $statusCode);
    }

    /**
     * Return a success JSON response
     *
     * @param string $message Success message
     * @param array $data Additional data (optional)
     * @param int $statusCode HTTP status code (default: 200)
     * @return ResponseInterface PSR-7 Response
     */
    protected function jsonSuccess(string $message, array $data = [], int $statusCode = 200): ResponseInterface
    {
        return $this->json(array_merge([
            'success' => true,
            'message' => $message
        ], $data), $statusCode);
    }

    /**
     * Return an error JSON response
     *
     * @param string $message Error message
     * @param array $errors Validation errors (optional)
     * @param int $statusCode HTTP status code (default: 400)
     * @return ResponseInterface PSR-7 Response
     */
    protected function jsonError(string $message, array $errors = [], int $statusCode = 400): ResponseInterface
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return $this->json($response, $statusCode);
    }

    /**
     * Redirect to a URL
     *
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code (default: 302)
     * @return ResponseInterface PSR-7 Response
     */
    protected function redirect(string $url, int $statusCode = 302): ResponseInterface
    {
        return Response::redirect($url, $statusCode);
    }

    /**
     * Get current user data from session
     *
     * @return array|null User data or null if not authenticated
     */
    protected function getCurrentUser(): ?array
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? 'Usuario',
            'email' => $_SESSION['email'] ?? '',
            'name' => $_SESSION['name'] ?? $_SESSION['username'] ?? 'Usuario',
            'role_name' => $_SESSION['role_name'] ?? 'Usuario',
            'avatar' => $_SESSION['avatar'] ?? null,
        ];
    }

    /**
     * Get current user ID from session
     *
     * @return int|null User ID or null if not authenticated
     */
    protected function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if user is authenticated
     *
     * @return bool True if authenticated
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['authenticated']);
    }

    /**
     * Add flash message to session
     *
     * Flash messages are displayed once and then removed
     *
     * @param string $type Message type: 'success', 'error', 'warning', 'info'
     * @param string $message Message content
     * @return void
     */
    protected function flash(string $type, string $message): void
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }

        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Add flash messages to view data and clear them from session
     *
     * @param array $data View data
     * @return array View data with flash messages
     */
    protected function addFlashMessages(array $data): array
    {
        if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])) {
            $data['flash_messages'] = $_SESSION['flash_messages'];
            unset($_SESSION['flash_messages']);
        }

        return $data;
    }

    /**
     * Log an audit event
     *
     * @param string $action Action performed (e.g., 'create', 'update', 'delete')
     * @param string $entityType Entity type (e.g., 'user', 'role', 'permission')
     * @param int|null $entityId Entity ID
     * @param array|null $oldValues Old values before change (for update/delete)
     * @param array|null $newValues New values after change (for create/update)
     * @return void
     */
    protected function logAudit(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $sql = "INSERT INTO {$this->db->table('audit_log')}
                    (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at)
                    VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent, :created_at)";

            $this->db->getConnection()->execute($sql, [
                ':user_id' => $userId,
                ':action' => $action,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':old_values' => $oldValues ? json_encode($oldValues) : null,
                ':new_values' => $newValues ? json_encode($newValues) : null,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent,
                ':created_at' => time(),
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            error_log("Failed to log audit: " . $e->getMessage());
        }
    }

    /**
     * Get database instance
     *
     * @return Database Database instance
     */
    protected function getDb(): Database
    {
        return $this->db;
    }

    /**
     * Get renderer instance
     *
     * @return MustacheRenderer Renderer instance
     */
    protected function getRenderer(): MustacheRenderer
    {
        return $this->renderer;
    }

    /**
     * Get translator instance
     *
     * @return Translator Translator instance
     */
    protected function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * Validate CSRF token from request
     *
     * @param array $data Request data (POST, etc.)
     * @return bool True if valid, false otherwise
     */
    protected function validateCsrfToken(array $data): bool
    {
        $token = $data['csrf_token'] ?? null;
        $sessionToken = $_SESSION['csrf_token'] ?? null;

        return $token && $sessionToken && hash_equals($sessionToken, $token);
    }

    /**
     * Generate a CSRF token and store in session
     *
     * @return string CSRF token
     */
    protected function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}
