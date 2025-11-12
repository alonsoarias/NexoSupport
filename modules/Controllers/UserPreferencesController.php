<?php

declare(strict_types=1);

/**
 * ISER - User Preferences Controller
 *
 * Handles user preference management through HTTP requests
 * Manages preference page display and settings updates
 *
 * @package ISER\Controllers
 * @author ISER Development Team
 * @copyright 2024 ISER
 * @license Proprietary
 * @version 1.0.0
 */

namespace ISER\Controllers;

use ISER\Controllers\Traits\NavigationTrait;
use ISER\Core\Database\Database;
use ISER\Core\View\MustacheRenderer;
use ISER\Core\I18n\Translator;
use ISER\Core\Http\Response;
use ISER\User\PreferencesManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * UserPreferencesController Class
 *
 * Handles user preferences management
 * Provides endpoints for viewing and updating user preferences
 */
class UserPreferencesController
{
    use NavigationTrait;

    /**
     * Database instance
     */
    private Database $db;

    /**
     * Mustache renderer instance
     */
    private MustacheRenderer $renderer;

    /**
     * Translator instance
     */
    private Translator $translator;

    /**
     * Preferences manager instance
     */
    private PreferencesManager $preferencesManager;

    /**
     * Valid timezone identifiers
     */
    private const VALID_TIMEZONES = [
        'America/Bogota',
        'America/New_York',
        'America/Los_Angeles',
        'America/Chicago',
        'America/Denver',
        'America/Mexico_City',
        'America/Sao_Paulo',
        'America/Argentina/Buenos_Aires',
        'Europe/London',
        'Europe/Paris',
        'Europe/Madrid',
        'Asia/Tokyo',
        'Asia/Shanghai',
        'Australia/Sydney',
        'UTC'
    ];

    /**
     * Valid locale identifiers
     */
    private const VALID_LOCALES = ['es', 'en', 'pt'];

    /**
     * Valid theme options
     */
    private const VALID_THEMES = ['light', 'dark'];

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
        $this->preferencesManager = new PreferencesManager($db);
    }

    /**
     * Display user preferences page
     *
     * GET /preferences
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

        $userId = $_SESSION['user_id'];

        try {
            // Get all user preferences
            $preferences = $this->preferencesManager->getAll($userId);

            // Get available locales from translator
            $availableLocales = $this->translator->getAvailableLocales();

            // Prepare timezone options
            $timezones = array_map(function($tz) use ($preferences) {
                return [
                    'value' => $tz,
                    'label' => $tz,
                    'selected' => ($preferences['timezone'] ?? 'America/Bogota') === $tz
                ];
            }, self::VALID_TIMEZONES);

            // Prepare locale options
            $locales = array_map(function($locale) use ($preferences) {
                $labels = [
                    'es' => 'Español',
                    'en' => 'English',
                    'pt' => 'Português'
                ];
                return [
                    'value' => $locale,
                    'label' => $labels[$locale] ?? $locale,
                    'selected' => ($preferences['locale'] ?? 'es') === $locale
                ];
            }, $availableLocales);

            // Prepare theme options
            $themeValue = $preferences['theme'] ?? 'light';
            $themes = [
                [
                    'value' => 'light',
                    'label' => $this->translator->translate('profile.theme_light'),
                    'selected' => $themeValue === 'light'
                ],
                [
                    'value' => 'dark',
                    'label' => $this->translator->translate('profile.theme_dark'),
                    'selected' => $themeValue === 'dark'
                ]
            ];

            // Get notification preferences
            $notificationsEmail = $preferences['notifications_email'] ?? true;
            $notificationsBrowser = $preferences['notifications_browser'] ?? true;

            // Check for success/error messages
            $successMessage = $_SESSION['preferences_success'] ?? null;
            $errorMessage = $_SESSION['preferences_error'] ?? null;
            unset($_SESSION['preferences_success'], $_SESSION['preferences_error']);

            // Prepare data for view
            $data = [
                'page_title' => $this->translator->translate('profile.preferences_title'),
                'header_title' => $this->translator->translate('profile.preferences_title'),
                'locale' => $this->translator->getLocale(),

                // Preferences sections
                'timezones' => $timezones,
                'locales' => $locales,
                'themes' => $themes,

                // Current values
                'current_timezone' => $preferences['timezone'] ?? 'America/Bogota',
                'current_locale' => $preferences['locale'] ?? 'es',
                'current_theme' => $themeValue,

                // Notification settings
                'notifications_email_checked' => $notificationsEmail,
                'notifications_browser_checked' => $notificationsBrowser,

                // Messages
                'success_message' => $successMessage,
                'error_message' => $errorMessage,
            ];

            // Enrich with navigation
            $data = $this->enrichWithNavigation($data, '/preferences');

            // Render with layout
            $html = $this->renderer->render('user/preferences', $data, 'layouts/app');
            return Response::html($html);

        } catch (\Exception $e) {
            error_log("Error in UserPreferencesController::index: " . $e->getMessage());
            return Response::json(
                ['error' => 'Internal server error'],
                500
            );
        }
    }

    /**
     * Update user preferences
     *
     * POST /preferences
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

        $userId = $_SESSION['user_id'];

        try {
            // Get form data
            $body = $request->getParsedBody();

            if (!is_array($body)) {
                $_SESSION['preferences_error'] = $this->translator->translate('common.error_occurred');
                return Response::redirect('/preferences');
            }

            // Extract and validate preferences
            $timezone = $body['timezone'] ?? null;
            $locale = $body['locale'] ?? null;
            $theme = $body['theme'] ?? null;
            $notificationsEmail = isset($body['notifications_email']);
            $notificationsBrowser = isset($body['notifications_browser']);

            // Validation
            $errors = [];

            if ($timezone && !in_array($timezone, self::VALID_TIMEZONES)) {
                $errors[] = 'Invalid timezone';
            }

            if ($locale && !in_array($locale, self::VALID_LOCALES)) {
                $errors[] = 'Invalid locale';
            }

            if ($theme && !in_array($theme, self::VALID_THEMES)) {
                $errors[] = 'Invalid theme';
            }

            if (!empty($errors)) {
                $_SESSION['preferences_error'] = implode(', ', $errors);
                return Response::redirect('/preferences');
            }

            // Save preferences
            $savedCount = 0;

            if ($timezone) {
                if ($this->preferencesManager->set($userId, 'timezone', $timezone, 'string')) {
                    $savedCount++;
                }
            }

            if ($locale) {
                if ($this->preferencesManager->set($userId, 'locale', $locale, 'string')) {
                    $savedCount++;
                    // Update session locale
                    $_SESSION['locale'] = $locale;
                    $this->translator->setLocale($locale);
                }
            }

            if ($theme) {
                if ($this->preferencesManager->set($userId, 'theme', $theme, 'string')) {
                    $savedCount++;
                }
            }

            // Save notification preferences (always save these)
            if ($this->preferencesManager->set($userId, 'notifications_email', $notificationsEmail, 'bool')) {
                $savedCount++;
            }

            if ($this->preferencesManager->set($userId, 'notifications_browser', $notificationsBrowser, 'bool')) {
                $savedCount++;
            }

            // Set success message
            $_SESSION['preferences_success'] = $this->translator->translate('profile.preferences_updated');

            return Response::redirect('/preferences');

        } catch (\Exception $e) {
            error_log("Error in UserPreferencesController::update: " . $e->getMessage());
            $_SESSION['preferences_error'] = $this->translator->translate('common.error_occurred');
            return Response::redirect('/preferences');
        }
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
}
