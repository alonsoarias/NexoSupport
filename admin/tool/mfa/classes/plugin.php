<?php
namespace tool_mfa;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Plugin de autenticación multi-factor (MFA)
 *
 * Proporciona autenticación de dos factores para NexoSupport.
 * Soporta múltiples factores (email, IP range, etc.) a través
 * del sistema de subplugins.
 *
 * Sigue el patrón Frankenstyle de NexoSupport,
 * extendiendo la clase base \core\plugininfo\tool.
 *
 * @package tool_mfa
 */
class plugin extends \core\plugininfo\tool {

    /**
     * MFA tiene su propio sistema de capabilities
     *
     * @return bool
     */
    public function has_capabilities(): bool {
        return true;
    }

    /**
     * Obtener sección de configuración en admin
     *
     * @return string|null
     */
    public function get_settings_section(): ?string {
        return 'security';
    }

    /**
     * URL del tool MFA
     *
     * @return string
     */
    public function get_url(): string {
        return '/admin/tool/mfa/settings.php';
    }

    /**
     * Capability requerida para configurar MFA
     *
     * @return string
     */
    public function get_required_capability(): string {
        return 'tool/mfa:manage';
    }

    /**
     * ¿MFA está habilitado globalmente?
     *
     * @return bool
     */
    public function is_mfa_enabled(): bool {
        $config = $this->get_config();
        return !empty($config['enabled']);
    }

    /**
     * Obtener todos los factores instalados
     *
     * @return array Array de factores
     */
    public function get_factors(): array {
        return manager::get_factors();
    }

    /**
     * Obtener factores habilitados
     *
     * @return array Array de factores habilitados
     */
    public function get_enabled_factors(): array {
        return manager::get_enabled_factors();
    }

    /**
     * ¿El usuario necesita verificación MFA?
     *
     * @param object $user Usuario a verificar
     * @return bool
     */
    public function user_needs_mfa(object $user): bool {
        if (!$this->is_mfa_enabled()) {
            return false;
        }

        return manager::user_needs_verification($user);
    }

    /**
     * Verificar si el usuario ha pasado MFA
     *
     * @param object $user Usuario
     * @return bool
     */
    public function is_user_verified(object $user): bool {
        return manager::is_verified($user);
    }

    /**
     * Obtener peso total requerido para pasar MFA
     *
     * @return int
     */
    public function get_required_weight(): int {
        $config = $this->get_config();
        return $config['requiredweight'] ?? 100;
    }

    /**
     * Obtener peso actual del usuario
     *
     * @param object $user Usuario
     * @return int
     */
    public function get_user_weight(object $user): int {
        return manager::get_cumulative_weight($user);
    }

    /**
     * Obtener descripción del tool
     *
     * @return string
     */
    public function get_description(): string {
        return get_string('plugindescription', 'tool_mfa');
    }

    /**
     * Obtener configuración del plugin
     *
     * @return array
     */
    public function get_config(): array {
        $config = get_config('tool_mfa');
        return is_array($config) ? $config : (array) $config;
    }

    /**
     * Obtener los subplugins (factores) de este tool
     *
     * @return array
     */
    public function get_subplugins(): array {
        return [
            'factor' => 'admin/tool/mfa/factor',
        ];
    }
}
