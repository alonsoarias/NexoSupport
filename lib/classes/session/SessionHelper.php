<?php
/**
 * NexoSupport - Session Helper Class
 *
 * Provides helper methods for session and authentication operations
 * Manages user session state and login verification
 *
 * @package    ISER\Core\Session
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Core\Session;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Session Helper - Session and authentication operations
 *
 * Provides convenient methods for session management
 */
class SessionHelper
{
    /** @var SessionHelper|null Singleton instance */
    private static ?SessionHelper $instance = null;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Get singleton instance
     *
     * @return SessionHelper
     */
    public static function getInstance(): SessionHelper
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if user is logged in
     *
     * @return bool True if user is logged in
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
    }

    /**
     * Get current user ID from session
     *
     * @return int Current user ID (0 if not logged in)
     */
    public function getCurrentUserId(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    /**
     * Require user to be logged in
     * Throws exception if user is not logged in
     *
     * @throws \Exception If user is not logged in
     */
    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            throw new \Exception("Login required. Please authenticate.");
        }
    }

    /**
     * Set current user ID in session
     *
     * @param int $userId User ID
     */
    public function setUserId(int $userId): void
    {
        $_SESSION['user_id'] = $userId;
    }

    /**
     * Get current user data from session
     *
     * @return array|null User data array or null if not logged in
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $_SESSION['user_data'] ?? null;
    }

    /**
     * Set current user data in session
     *
     * @param array $userData User data array
     */
    public function setCurrentUser(array $userData): void
    {
        $_SESSION['user_data'] = $userData;
        if (isset($userData['id'])) {
            $_SESSION['user_id'] = $userData['id'];
        }
    }

    /**
     * Clear session (logout)
     */
    public function clearSession(): void
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_data']);

        // Clear all session data
        $_SESSION = [];

        // Destroy session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy session
        session_destroy();
    }

    /**
     * Regenerate session ID for security
     *
     * @param bool $deleteOldSession Whether to delete old session
     */
    public function regenerateId(bool $deleteOldSession = true): void
    {
        session_regenerate_id($deleteOldSession);
    }

    /**
     * Get session value
     *
     * @param string $key Session key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Session value or default
     */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set session value
     *
     * @param string $key Session key
     * @param mixed $value Value to set
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Check if session has key
     *
     * @param string $key Session key
     * @return bool True if key exists
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session value
     *
     * @param string $key Session key
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Get flash message (one-time message)
     *
     * @param string $key Flash key
     * @param mixed $default Default value
     * @return mixed Flash value or default
     */
    public function getFlash(string $key, $default = null)
    {
        $value = $_SESSION["_flash_{$key}"] ?? $default;
        unset($_SESSION["_flash_{$key}"]);
        return $value;
    }

    /**
     * Set flash message (one-time message)
     *
     * @param string $key Flash key
     * @param mixed $value Value to set
     */
    public function setFlash(string $key, $value): void
    {
        $_SESSION["_flash_{$key}"] = $value;
    }

    /**
     * Get all session data
     *
     * @return array Session data
     */
    public function all(): array
    {
        return $_SESSION;
    }
}
