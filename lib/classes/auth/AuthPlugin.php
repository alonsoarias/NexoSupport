<?php
/**
 * Base Authentication Plugin Class
 *
 * Provides common functionality for all authentication plugins.
 *
 * @package    ISER\Core\Auth
 * @copyright  2025 ISER
 * @license    Proprietary
 */

namespace ISER\Core\Auth;

use ISER\Core\Database\Database;
use ISER\Core\Config\Config;

/**
 * Abstract base class for authentication plugins
 */
abstract class AuthPlugin implements AuthInterface
{
    /**
     * @var string Authentication type identifier
     */
    protected string $authtype;

    /**
     * @var Database Database instance
     */
    protected Database $db;

    /**
     * @var Config Configuration instance
     */
    protected Config $config;

    /**
     * @var array Plugin configuration
     */
    protected array $pluginConfig;

    /**
     * Constructor.
     *
     * @param Database $db Database instance
     * @param Config $config Configuration instance
     */
    public function __construct(Database $db, Config $config)
    {
        $this->db = $db;
        $this->config = $config;
        $this->pluginConfig = $this->load_config();
    }

    /**
     * Load plugin configuration.
     *
     * @return array Plugin configuration
     */
    protected function load_config(): array
    {
        // Default implementation - can be overridden by specific plugins
        $configKey = 'auth_' . $this->authtype;
        return $this->config->get($configKey, []);
    }

    /**
     * Get the authentication type identifier.
     *
     * @return string
     */
    public function get_authtype(): string
    {
        return $this->authtype;
    }

    /**
     * Get plugin configuration value.
     *
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed Configuration value
     */
    protected function get_config(string $key, mixed $default = null): mixed
    {
        return $this->pluginConfig[$key] ?? $default;
    }

    /**
     * Set plugin configuration value.
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return void
     */
    protected function set_config(string $key, mixed $value): void
    {
        $this->pluginConfig[$key] = $value;
    }

    /**
     * Default implementation: cannot change password.
     *
     * @return bool
     */
    public function can_change_password(): bool
    {
        return false;
    }

    /**
     * Default implementation: cannot edit profile.
     *
     * @return bool
     */
    public function can_edit_profile(): bool
    {
        return false;
    }

    /**
     * Default implementation: not internal.
     *
     * @return bool
     */
    public function is_internal(): bool
    {
        return false;
    }

    /**
     * Default implementation: cannot signup.
     *
     * @return bool
     */
    public function can_signup(): bool
    {
        return false;
    }

    /**
     * Default implementation: cannot confirm.
     *
     * @return bool
     */
    public function can_confirm(): bool
    {
        return false;
    }

    /**
     * Default implementation: cannot reset password.
     *
     * @return bool
     */
    public function can_reset_password(): bool
    {
        return false;
    }

    /**
     * Default implementation: pre-login hook does nothing.
     *
     * @return bool
     */
    public function pre_loginpage_hook(): bool
    {
        return true;
    }

    /**
     * Default implementation: post-authentication hook does nothing.
     *
     * @param object $user
     * @param string $username
     * @param string $password
     * @return void
     */
    public function user_authenticated_hook(object &$user, string $username, string $password): void
    {
        // Default: do nothing
    }

    /**
     * Default implementation: logout hook does nothing.
     *
     * @return void
     */
    public function logoutpage_hook(): void
    {
        // Default: do nothing
    }

    /**
     * Default implementation: no synchronization.
     *
     * @param bool $do_updates
     * @return bool
     */
    public function sync_users(bool $do_updates = false): bool
    {
        return true;
    }

    /**
     * Default implementation: no external user info.
     *
     * @param string $username
     * @return array|false
     */
    public function get_userinfo(string $username): array|false
    {
        return false;
    }

    /**
     * Default implementation: use default password change URL.
     *
     * @return string|null
     */
    public function change_password_url(): ?string
    {
        $wwwroot = $this->config->get('wwwroot', '');
        return $wwwroot ? $wwwroot . '/login/change_password.php' : null;
    }
}
