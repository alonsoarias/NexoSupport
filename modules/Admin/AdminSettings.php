<?php
/**
 * ISER - Admin Settings Manager
 *
 * Manages system settings with validation and organization.
 * Provides structured access to settings grouped by category.
 *
 * @package    ISER\Modules\Admin
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    6.0.0
 * @since      Phase 6
 */

namespace ISER\Admin;

use ISER\Core\Config\SettingsManager;
use ISER\Core\Utils\Logger;

class AdminSettings
{
    private SettingsManager $settings;

    // Setting categories/sections
    private const SECTIONS = [
        'general' => 'Configuración General',
        'manageauths' => 'Métodos de Autenticación',
        'outgoingmailconfig' => 'Correo Saliente',
        'mfa' => 'Autenticación Multi-Factor',
        'sitepolicies' => 'Políticas del Sitio',
        'themesettingiser' => 'Tema ISER',
    ];

    public function __construct(SettingsManager $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get all available sections
     *
     * @return array Sections
     */
    public function getSections(): array
    {
        return self::SECTIONS;
    }

    /**
     * Get settings for a specific section
     *
     * @param string $section Section name
     * @return array Settings configuration
     */
    public function getSectionSettings(string $section): array
    {
        return match ($section) {
            'general' => $this->getGeneralSettings(),
            'manageauths' => $this->getAuthSettings(),
            'outgoingmailconfig' => $this->getMailSettings(),
            'mfa' => $this->getMfaSettings(),
            'sitepolicies' => $this->getSitePolicySettings(),
            'themesettingiser' => $this->getThemeSettings(),
            default => [],
        };
    }

    /**
     * Get general settings configuration
     *
     * @return array General settings
     */
    private function getGeneralSettings(): array
    {
        return [
            'sitename' => [
                'value' => $this->settings->get('sitename', 'core', 'ISER'),
                'type' => 'text',
                'label' => 'Nombre del Sitio',
                'description' => 'El nombre que aparecerá en todo el sistema',
            ],
            'sitedescription' => [
                'value' => $this->settings->get('sitedescription', 'core', ''),
                'type' => 'textarea',
                'label' => 'Descripción del Sitio',
                'description' => 'Breve descripción del sitio',
            ],
            'defaultlanguage' => [
                'value' => $this->settings->get('defaultlanguage', 'core', 'es'),
                'type' => 'select',
                'label' => 'Idioma Predeterminado',
                'options' => ['es' => 'Español', 'en' => 'English'],
            ],
            'timezone' => [
                'value' => $this->settings->get('timezone', 'core', 'America/Mexico_City'),
                'type' => 'select',
                'label' => 'Zona Horaria',
                'options' => timezone_identifiers_list(),
            ],
        ];
    }

    /**
     * Get authentication settings configuration
     *
     * @return array Auth settings
     */
    private function getAuthSettings(): array
    {
        return [
            'enabled' => [
                'value' => $this->settings->getBool('enabled', 'auth_manual', true),
                'type' => 'checkbox',
                'label' => 'Autenticación Manual Habilitada',
            ],
            'recaptcha_enabled' => [
                'value' => $this->settings->getBool('recaptcha_enabled', 'auth_manual', false),
                'type' => 'checkbox',
                'label' => 'Habilitar reCAPTCHA en Login',
            ],
            'recaptcha_sitekey' => [
                'value' => $this->settings->get('recaptcha_sitekey', 'auth_manual', ''),
                'type' => 'text',
                'label' => 'reCAPTCHA Site Key',
                'description' => 'Clave del sitio de Google reCAPTCHA v2',
            ],
            'recaptcha_secret' => [
                'value' => $this->settings->get('recaptcha_secret', 'auth_manual', ''),
                'type' => 'password',
                'label' => 'reCAPTCHA Secret Key',
                'description' => 'Clave secreta de Google reCAPTCHA v2',
            ],
        ];
    }

    /**
     * Get mail settings configuration
     *
     * @return array Mail settings
     */
    private function getMailSettings(): array
    {
        return [
            'smtphosts' => [
                'value' => $this->settings->get('smtphosts', 'core', ''),
                'type' => 'text',
                'label' => 'Servidor SMTP',
                'description' => 'Dirección del servidor SMTP (ej: smtp.gmail.com)',
            ],
            'smtpport' => [
                'value' => $this->settings->getInt('smtpport', 'core', 587),
                'type' => 'number',
                'label' => 'Puerto SMTP',
                'description' => 'Puerto del servidor SMTP (587 para TLS, 465 para SSL)',
            ],
            'smtpuser' => [
                'value' => $this->settings->get('smtpuser', 'core', ''),
                'type' => 'text',
                'label' => 'Usuario SMTP',
                'description' => 'Usuario para autenticación SMTP',
            ],
            'smtppass' => [
                'value' => $this->settings->get('smtppass', 'core', ''),
                'type' => 'password',
                'label' => 'Contraseña SMTP',
                'description' => 'Contraseña para autenticación SMTP',
            ],
            'smtpsecure' => [
                'value' => $this->settings->get('smtpsecure', 'core', 'tls'),
                'type' => 'select',
                'label' => 'Encriptación SMTP',
                'options' => ['tls' => 'TLS', 'ssl' => 'SSL', '' => 'Ninguna'],
            ],
            'noreplyaddress' => [
                'value' => $this->settings->get('noreplyaddress', 'core', 'noreply@localhost'),
                'type' => 'email',
                'label' => 'Dirección No-Reply',
                'description' => 'Dirección de correo para mensajes automáticos',
            ],
            'emailfromname' => [
                'value' => $this->settings->get('emailfromname', 'core', 'ISER System'),
                'type' => 'text',
                'label' => 'Nombre del Remitente',
                'description' => 'Nombre que aparecerá como remitente',
            ],
        ];
    }

    /**
     * Get MFA settings configuration
     *
     * @return array MFA settings
     */
    private function getMfaSettings(): array
    {
        return [
            'enabled' => [
                'value' => $this->settings->getBool('enabled', 'tool_mfa', false),
                'type' => 'checkbox',
                'label' => 'MFA Habilitado',
            ],
            'required_for_admin' => [
                'value' => $this->settings->getBool('required_for_admin', 'tool_mfa', false),
                'type' => 'checkbox',
                'label' => 'MFA Requerido para Administradores',
            ],
            'grace_period' => [
                'value' => $this->settings->getInt('grace_period', 'tool_mfa', 7),
                'type' => 'number',
                'label' => 'Período de Gracia (días)',
                'description' => 'Días que el usuario tiene para configurar MFA',
            ],
            'totp_enabled' => [
                'value' => $this->settings->getBool('totp_enabled', 'tool_mfa', true),
                'type' => 'checkbox',
                'label' => 'Habilitar TOTP (Google Authenticator)',
            ],
            'email_enabled' => [
                'value' => $this->settings->getBool('email_enabled', 'tool_mfa', true),
                'type' => 'checkbox',
                'label' => 'Habilitar Códigos por Email',
            ],
            'backup_enabled' => [
                'value' => $this->settings->getBool('backup_enabled', 'tool_mfa', true),
                'type' => 'checkbox',
                'label' => 'Habilitar Códigos de Respaldo',
            ],
        ];
    }

    /**
     * Get site policy settings configuration
     *
     * @return array Site policy settings
     */
    private function getSitePolicySettings(): array
    {
        return [
            'privacy_policy' => [
                'value' => $this->settings->get('privacy_policy', 'core', ''),
                'type' => 'textarea',
                'label' => 'Política de Privacidad',
                'description' => 'Texto de la política de privacidad',
                'rows' => 10,
            ],
            'terms_of_service' => [
                'value' => $this->settings->get('terms_of_service', 'core', ''),
                'type' => 'textarea',
                'label' => 'Términos de Servicio',
                'description' => 'Texto de los términos de servicio',
                'rows' => 10,
            ],
            'minimum_age' => [
                'value' => $this->settings->getInt('minimum_age', 'core', 13),
                'type' => 'number',
                'label' => 'Edad Mínima',
                'description' => 'Edad mínima requerida para registrarse',
            ],
        ];
    }

    /**
     * Get theme settings configuration
     *
     * @return array Theme settings
     */
    private function getThemeSettings(): array
    {
        return [
            'logo' => [
                'value' => $this->settings->get('logo', 'theme_iser', ''),
                'type' => 'file',
                'label' => 'Logo del Sitio',
                'description' => 'Logo que aparecerá en el header',
            ],
            'primary_color' => [
                'value' => $this->settings->get('primary_color', 'theme_iser', '#667eea'),
                'type' => 'color',
                'label' => 'Color Primario',
            ],
            'secondary_color' => [
                'value' => $this->settings->get('secondary_color', 'theme_iser', '#764ba2'),
                'type' => 'color',
                'label' => 'Color Secundario',
            ],
            'footer_text' => [
                'value' => $this->settings->get('footer_text', 'theme_iser', 'ISER Authentication System'),
                'type' => 'text',
                'label' => 'Texto del Footer',
            ],
        ];
    }

    /**
     * Save settings for a section
     *
     * @param string $section Section name
     * @param array $values Setting values
     * @return bool True on success
     */
    public function saveSectionSettings(string $section, array $values): bool
    {
        $plugin = $this->getPluginForSection($section);
        $success = true;

        foreach ($values as $name => $value) {
            if (!$this->settings->set($name, $value, $plugin)) {
                $success = false;
                Logger::error('Failed to save setting', [
                    'section' => $section,
                    'name' => $name,
                ]);
            }
        }

        if ($success) {
            Logger::auth('Settings updated', [
                'section' => $section,
                'count' => count($values),
            ]);
        }

        return $success;
    }

    /**
     * Get plugin name for section
     *
     * @param string $section Section name
     * @return string Plugin name
     */
    private function getPluginForSection(string $section): string
    {
        return match ($section) {
            'manageauths' => 'auth_manual',
            'mfa' => 'tool_mfa',
            'themesettingiser' => 'theme_iser',
            default => 'core',
        };
    }

    /**
     * Validate setting value
     *
     * @param string $name Setting name
     * @param mixed $value Setting value
     * @param array $config Setting configuration
     * @return bool True if valid
     */
    public function validateSetting(string $name, mixed $value, array $config): bool
    {
        $type = $config['type'] ?? 'text';

        return match ($type) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'number' => is_numeric($value),
            'checkbox' => is_bool($value) || in_array($value, ['0', '1', 0, 1, true, false]),
            default => true,
        };
    }
}
