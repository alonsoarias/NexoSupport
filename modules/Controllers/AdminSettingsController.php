<?php

declare(strict_types=1);

/**
 * ISER - Admin Settings Controller
 *
 * Handles comprehensive system settings management through a tabbed interface
 * Manages configuration for general, email, security, appearance, and advanced settings
 *
 * @package ISER\Controllers
 * @author ISER Development Team
 * @copyright 2024 ISER
 * @license Proprietary
 * @version 8.0.0
 * @since Phase 8
 */

namespace ISER\Controllers;

use ISER\Controllers\Traits\NavigationTrait;
use ISER\Core\Database\Database;
use ISER\Core\View\MustacheRenderer;
use ISER\Core\Http\Response;
use ISER\Core\Config\SettingsManager;
use ISER\Core\I18n\Translator;
use ISER\Core\Utils\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * AdminSettingsController Class
 *
 * Provides comprehensive system settings management with:
 * - Tabbed interface by category
 * - Input validation
 * - Audit logging
 * - Default restoration
 */
class AdminSettingsController
{
    use NavigationTrait;

    private Database $db;
    private MustacheRenderer $renderer;
    private SettingsManager $settings;
    private Translator $translator;

    /**
     * Default values for all settings
     */
    private const DEFAULTS = [
        // General
        'site_name' => 'NexoSupport',
        'site_description' => 'Sistema de Soporte y Autenticación ISER',
        'timezone' => 'America/Mexico_City',
        'locale' => 'es',
        'date_format' => 'Y-m-d',

        // Email
        'from_name' => 'NexoSupport',
        'from_address' => 'noreply@nexosupport.local',
        'reply_to' => 'support@nexosupport.local',
        'mail_driver' => 'smtp',

        // Security
        'session_lifetime' => 120,
        'password_min_length' => 8,
        'require_email_verification' => true,
        'login_max_attempts' => 5,
        'lockout_duration' => 15,

        // Appearance
        'theme' => 'iser',
        'items_per_page' => 20,
        'default_language' => 'es',

        // Advanced
        'cache_driver' => 'file',
        'log_level' => 'info',
        'debug_mode' => false,
        'maintenance_mode' => false,
    ];

    /**
     * Constructor
     *
     * @param Database $db Database instance
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->renderer = MustacheRenderer::getInstance();
        $this->settings = new SettingsManager($db);
        $this->translator = Translator::getInstance();
    }

    /**
     * Display settings page with tabbed interface
     *
     * GET /admin/settings
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Verify authentication
        if (!$this->isAuthenticated()) {
            return Response::redirect('/login');
        }

        // Verify admin permissions
        if (!$this->hasAdminPermission()) {
            return Response::json(['error' => 'Unauthorized access'], 403);
        }

        try {
            // Get all current settings
            $currentSettings = $this->getAllSettings();

            // Check for success message in session
            $successMessage = $_SESSION['settings_success'] ?? null;
            unset($_SESSION['settings_success']);

            // Prepare data for view
            $data = [
                'page_title' => $this->translator->translate('settings.title'),
                'header_title' => $this->translator->translate('settings.title'),
                'locale' => $this->translator->getLocale(),

                // Settings by category
                'general_settings' => $this->getGeneralSettings($currentSettings),
                'email_settings' => $this->getEmailSettings($currentSettings),
                'security_settings' => $this->getSecuritySettings($currentSettings),
                'appearance_settings' => $this->getAppearanceSettings($currentSettings),
                'advanced_settings' => $this->getAdvancedSettings($currentSettings),

                // Options for selects
                'timezones' => $this->getTimezoneOptions($currentSettings['timezone']),
                'locales' => $this->getLocaleOptions($currentSettings['locale']),
                'mail_drivers' => $this->getMailDriverOptions($currentSettings['mail_driver']),
                'cache_drivers' => $this->getCacheDriverOptions($currentSettings['cache_driver']),
                'log_levels' => $this->getLogLevelOptions($currentSettings['log_level']),
                'date_formats' => $this->getDateFormatOptions($currentSettings['date_format']),
                'themes' => $this->getThemeOptions($currentSettings['theme']),

                // Messages
                'success_message' => $successMessage,
                'error_message' => null,
            ];

            // Enrich with navigation
            $data = $this->enrichWithNavigation($data, '/admin/settings');

            // Render with layout
            $html = $this->renderer->render('admin/settings/index', $data, 'layouts/app');
            return Response::html($html);

        } catch (\Exception $e) {
            error_log("Error in AdminSettingsController::index: " . $e->getMessage());
            return Response::json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Update multiple settings
     *
     * POST /admin/settings
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response
     */
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        // Verify authentication
        if (!$this->isAuthenticated()) {
            return Response::redirect('/login');
        }

        // Verify admin permissions
        if (!$this->hasAdminPermission()) {
            return Response::json(['error' => 'Unauthorized access'], 403);
        }

        try {
            // Get POST data
            $postData = $request->getParsedBody();

            // Validate and filter settings
            $validatedSettings = $this->validateSettings($postData);

            if (isset($validatedSettings['errors']) && !empty($validatedSettings['errors'])) {
                $_SESSION['settings_error'] = implode(', ', $validatedSettings['errors']);
                return Response::redirect('/admin/settings');
            }

            // Save each setting
            $savedCount = 0;
            $errors = [];

            foreach ($validatedSettings as $key => $value) {
                if ($this->settings->set($key, $value, 'core')) {
                    $savedCount++;
                } else {
                    $errors[] = "Failed to save: {$key}";
                }
            }

            // Log the change
            Logger::auth('Settings updated', [
                'user_id' => $_SESSION['user_id'] ?? 0,
                'count' => $savedCount,
                'settings' => array_keys($validatedSettings)
            ]);

            // Set success message
            $_SESSION['settings_success'] = $this->translator->translate('settings.saved_message') . " ({$savedCount} " . $this->translator->translate('settings.items_updated') . ")";

            return Response::redirect('/admin/settings');

        } catch (\Exception $e) {
            error_log("Error in AdminSettingsController::update: " . $e->getMessage());
            $_SESSION['settings_error'] = 'Failed to save settings';
            return Response::redirect('/admin/settings');
        }
    }

    /**
     * Reset settings to defaults
     *
     * POST /admin/settings/reset
     *
     * @param ServerRequestInterface $request HTTP request
     * @return ResponseInterface HTTP response
     */
    public function reset(ServerRequestInterface $request): ResponseInterface
    {
        // Verify authentication
        if (!$this->isAuthenticated()) {
            return Response::json(['error' => 'Unauthorized access'], 401);
        }

        // Verify admin permissions
        if (!$this->hasAdminPermission()) {
            return Response::json(['error' => 'Insufficient permissions'], 403);
        }

        try {
            // Reset all settings to defaults
            $count = 0;
            foreach (self::DEFAULTS as $key => $value) {
                if ($this->settings->set($key, $value, 'core')) {
                    $count++;
                }
            }

            // Log the reset
            Logger::auth('Settings reset to defaults', [
                'user_id' => $_SESSION['user_id'] ?? 0,
                'count' => $count
            ]);

            return Response::json([
                'success' => true,
                'message' => $this->translator->translate('settings.restored_message'),
                'count' => $count
            ], 200);

        } catch (\Exception $e) {
            error_log("Error in AdminSettingsController::reset: " . $e->getMessage());
            return Response::json([
                'error' => 'Failed to reset settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all current settings with defaults
     *
     * @return array Current settings
     */
    private function getAllSettings(): array
    {
        $settings = [];

        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = $this->settings->get($key, 'core', $default);
        }

        return $settings;
    }

    /**
     * Get general settings for view
     *
     * @param array $current Current settings
     * @return array General settings
     */
    private function getGeneralSettings(array $current): array
    {
        return [
            'site_name' => $current['site_name'],
            'site_description' => $current['site_description'],
            'timezone' => $current['timezone'],
            'locale' => $current['locale'],
            'date_format' => $current['date_format'],
        ];
    }

    /**
     * Get email settings for view
     *
     * @param array $current Current settings
     * @return array Email settings
     */
    private function getEmailSettings(array $current): array
    {
        return [
            'from_name' => $current['from_name'],
            'from_address' => $current['from_address'],
            'reply_to' => $current['reply_to'],
            'mail_driver' => $current['mail_driver'],
        ];
    }

    /**
     * Get security settings for view
     *
     * @param array $current Current settings
     * @return array Security settings
     */
    private function getSecuritySettings(array $current): array
    {
        return [
            'session_lifetime' => $current['session_lifetime'],
            'password_min_length' => $current['password_min_length'],
            'require_email_verification' => (bool)$current['require_email_verification'],
            'login_max_attempts' => $current['login_max_attempts'],
            'lockout_duration' => $current['lockout_duration'],
        ];
    }

    /**
     * Get appearance settings for view
     *
     * @param array $current Current settings
     * @return array Appearance settings
     */
    private function getAppearanceSettings(array $current): array
    {
        return [
            'theme' => $current['theme'],
            'items_per_page' => $current['items_per_page'],
            'default_language' => $current['default_language'],
        ];
    }

    /**
     * Get advanced settings for view
     *
     * @param array $current Current settings
     * @return array Advanced settings
     */
    private function getAdvancedSettings(array $current): array
    {
        return [
            'cache_driver' => $current['cache_driver'],
            'log_level' => $current['log_level'],
            'debug_mode' => (bool)$current['debug_mode'],
            'maintenance_mode' => (bool)$current['maintenance_mode'],
        ];
    }

    /**
     * Validate settings before saving
     *
     * @param array $data POST data
     * @return array Validated settings or errors
     */
    private function validateSettings(array $data): array
    {
        $validated = [];
        $errors = [];

        // Validate site_name
        if (isset($data['site_name'])) {
            $validated['site_name'] = trim($data['site_name']);
            if (empty($validated['site_name'])) {
                $errors[] = 'Site name cannot be empty';
            }
        }

        // Validate site_description
        if (isset($data['site_description'])) {
            $validated['site_description'] = trim($data['site_description']);
        }

        // Validate timezone
        if (isset($data['timezone'])) {
            $timezones = timezone_identifiers_list();
            if (in_array($data['timezone'], $timezones)) {
                $validated['timezone'] = $data['timezone'];
            } else {
                $errors[] = 'Invalid timezone';
            }
        }

        // Validate locale
        if (isset($data['locale'])) {
            if (in_array($data['locale'], ['es', 'en', 'pt'])) {
                $validated['locale'] = $data['locale'];
            }
        }

        // Validate date_format
        if (isset($data['date_format'])) {
            $validated['date_format'] = $data['date_format'];
        }

        // Validate email settings
        if (isset($data['from_name'])) {
            $validated['from_name'] = trim($data['from_name']);
        }

        if (isset($data['from_address'])) {
            if (filter_var($data['from_address'], FILTER_VALIDATE_EMAIL)) {
                $validated['from_address'] = $data['from_address'];
            } else {
                $errors[] = 'Invalid from email address';
            }
        }

        if (isset($data['reply_to'])) {
            if (filter_var($data['reply_to'], FILTER_VALIDATE_EMAIL)) {
                $validated['reply_to'] = $data['reply_to'];
            } else {
                $errors[] = 'Invalid reply-to email address';
            }
        }

        if (isset($data['mail_driver'])) {
            $validated['mail_driver'] = $data['mail_driver'];
        }

        // Validate security settings
        if (isset($data['session_lifetime'])) {
            $lifetime = (int)$data['session_lifetime'];
            if ($lifetime >= 5 && $lifetime <= 1440) {
                $validated['session_lifetime'] = $lifetime;
            } else {
                $errors[] = 'Session lifetime must be between 5 and 1440 minutes';
            }
        }

        if (isset($data['password_min_length'])) {
            $length = (int)$data['password_min_length'];
            if ($length >= 6 && $length <= 32) {
                $validated['password_min_length'] = $length;
            } else {
                $errors[] = 'Password minimum length must be between 6 and 32';
            }
        }

        if (isset($data['require_email_verification'])) {
            $validated['require_email_verification'] = $data['require_email_verification'] === 'on' || $data['require_email_verification'] === '1';
        }

        if (isset($data['login_max_attempts'])) {
            $attempts = (int)$data['login_max_attempts'];
            if ($attempts >= 3 && $attempts <= 20) {
                $validated['login_max_attempts'] = $attempts;
            } else {
                $errors[] = 'Max login attempts must be between 3 and 20';
            }
        }

        if (isset($data['lockout_duration'])) {
            $duration = (int)$data['lockout_duration'];
            if ($duration >= 1 && $duration <= 1440) {
                $validated['lockout_duration'] = $duration;
            } else {
                $errors[] = 'Lockout duration must be between 1 and 1440 minutes';
            }
        }

        // Validate appearance settings
        if (isset($data['theme'])) {
            $validated['theme'] = $data['theme'];
        }

        if (isset($data['items_per_page'])) {
            $items = (int)$data['items_per_page'];
            if ($items >= 10 && $items <= 100) {
                $validated['items_per_page'] = $items;
            } else {
                $errors[] = 'Items per page must be between 10 and 100';
            }
        }

        if (isset($data['default_language'])) {
            if (in_array($data['default_language'], ['es', 'en', 'pt'])) {
                $validated['default_language'] = $data['default_language'];
            }
        }

        // Validate advanced settings
        if (isset($data['cache_driver'])) {
            $validated['cache_driver'] = $data['cache_driver'];
        }

        if (isset($data['log_level'])) {
            $validated['log_level'] = $data['log_level'];
        }

        if (isset($data['debug_mode'])) {
            $validated['debug_mode'] = $data['debug_mode'] === 'on' || $data['debug_mode'] === '1';
        }

        if (isset($data['maintenance_mode'])) {
            $validated['maintenance_mode'] = $data['maintenance_mode'] === 'on' || $data['maintenance_mode'] === '1';
        }

        // Return errors if any
        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        return $validated;
    }

    /**
     * Get timezone options for select
     *
     * @param string $current Current timezone
     * @return array Timezone options
     */
    private function getTimezoneOptions(string $current): array
    {
        $timezones = timezone_identifiers_list();
        $options = [];

        foreach ($timezones as $tz) {
            $options[] = [
                'value' => $tz,
                'label' => $tz,
                'selected' => $tz === $current
            ];
        }

        return $options;
    }

    /**
     * Get locale options for select
     *
     * @param string $current Current locale
     * @return array Locale options
     */
    private function getLocaleOptions(string $current): array
    {
        return [
            ['value' => 'es', 'label' => 'Español', 'selected' => $current === 'es'],
            ['value' => 'en', 'label' => 'English', 'selected' => $current === 'en'],
            ['value' => 'pt', 'label' => 'Português', 'selected' => $current === 'pt'],
        ];
    }

    /**
     * Get mail driver options for select
     *
     * @param string $current Current driver
     * @return array Mail driver options
     */
    private function getMailDriverOptions(string $current): array
    {
        return [
            ['value' => 'smtp', 'label' => 'SMTP', 'selected' => $current === 'smtp'],
            ['value' => 'sendmail', 'label' => 'Sendmail', 'selected' => $current === 'sendmail'],
            ['value' => 'mail', 'label' => 'PHP Mail', 'selected' => $current === 'mail'],
        ];
    }

    /**
     * Get cache driver options for select
     *
     * @param string $current Current driver
     * @return array Cache driver options
     */
    private function getCacheDriverOptions(string $current): array
    {
        return [
            ['value' => 'file', 'label' => 'File', 'selected' => $current === 'file'],
            ['value' => 'redis', 'label' => 'Redis', 'selected' => $current === 'redis'],
            ['value' => 'memcached', 'label' => 'Memcached', 'selected' => $current === 'memcached'],
        ];
    }

    /**
     * Get log level options for select
     *
     * @param string $current Current level
     * @return array Log level options
     */
    private function getLogLevelOptions(string $current): array
    {
        return [
            ['value' => 'debug', 'label' => 'Debug', 'selected' => $current === 'debug'],
            ['value' => 'info', 'label' => 'Info', 'selected' => $current === 'info'],
            ['value' => 'warning', 'label' => 'Warning', 'selected' => $current === 'warning'],
            ['value' => 'error', 'label' => 'Error', 'selected' => $current === 'error'],
        ];
    }

    /**
     * Get date format options for select
     *
     * @param string $current Current format
     * @return array Date format options
     */
    private function getDateFormatOptions(string $current): array
    {
        return [
            ['value' => 'Y-m-d', 'label' => 'YYYY-MM-DD (2024-12-31)', 'selected' => $current === 'Y-m-d'],
            ['value' => 'd/m/Y', 'label' => 'DD/MM/YYYY (31/12/2024)', 'selected' => $current === 'd/m/Y'],
            ['value' => 'm/d/Y', 'label' => 'MM/DD/YYYY (12/31/2024)', 'selected' => $current === 'm/d/Y'],
            ['value' => 'Y-m-d H:i:s', 'label' => 'YYYY-MM-DD HH:MM:SS', 'selected' => $current === 'Y-m-d H:i:s'],
        ];
    }

    /**
     * Get theme options for select
     *
     * @param string $current Current theme
     * @return array Theme options
     */
    private function getThemeOptions(string $current): array
    {
        return [
            ['value' => 'iser', 'label' => 'ISER Corporate', 'selected' => $current === 'iser'],
            ['value' => 'default', 'label' => 'Default', 'selected' => $current === 'default'],
        ];
    }

    /**
     * Check if user is authenticated
     *
     * @return bool True if authenticated
     */
    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id'])
            && isset($_SESSION['authenticated'])
            && $_SESSION['authenticated'] === true;
    }

    /**
     * Check if user has admin permission
     *
     * @return bool True if user has admin permission
     */
    private function hasAdminPermission(): bool
    {
        // Check if user has admin or moderator role
        $roles = $_SESSION['roles'] ?? [];

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if (isset($role['slug']) && in_array($role['slug'], ['admin', 'moderator'])) {
                    return true;
                }
            }
        }

        // Fallback: check role_name
        $roleName = $_SESSION['role_name'] ?? '';
        return in_array($roleName, ['admin', 'moderator', 'administrator']);
    }
}
