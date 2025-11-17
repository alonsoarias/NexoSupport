<?php
namespace core\plugininfo;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Clase base para factores MFA
 *
 * Todos los plugins factor_* DEBEN extender esta clase
 * e implementar los métodos abstractos.
 *
 * @package core\plugininfo
 */
abstract class factor extends base {

    /** Estados de factor */
    const STATE_PASS = 'pass';
    const STATE_FAIL = 'fail';
    const STATE_NEUTRAL = 'neutral';
    const STATE_UNKNOWN = 'unknown';

    /**
     * Obtener estado del factor para el usuario
     *
     * @param int $userid ID del usuario
     * @return string Una de las constantes STATE_*
     */
    abstract public function get_state(int $userid): string;

    /**
     * ¿El factor requiere input del usuario?
     *
     * @return bool
     */
    abstract public function has_input(): bool;

    /**
     * Verificar el factor
     *
     * @param int $userid ID del usuario
     * @param array $data Datos del formulario
     * @return bool True si la verificación es exitosa
     */
    abstract public function verify(int $userid, array $data): bool;

    /**
     * Obtener peso del factor
     *
     * El peso determina la importancia del factor en la autenticación.
     * Un peso de 100 significa que el factor por sí solo es suficiente.
     *
     * @return int
     */
    public function get_weight(): int {
        $weight = get_config($this->component, 'weight');
        return $weight !== null ? (int)$weight : 100;
    }

    /**
     * Establecer peso del factor
     *
     * @param int $weight
     * @return void
     */
    public function set_weight(int $weight): void {
        set_config('weight', $weight, $this->component);
    }

    /**
     * ¿El factor está activo para el usuario?
     *
     * @param int $userid
     * @return bool
     */
    public function is_active_for_user(int $userid): bool {
        $state = $this->get_state($userid);
        return $state !== self::STATE_NEUTRAL;
    }

    /**
     * Obtener configuración del factor
     *
     * @return array
     */
    public function get_config(): array {
        return get_config($this->component);
    }

    /**
     * Setup del factor para un usuario
     *
     * @param int $userid
     * @return bool
     */
    public function setup(int $userid): bool {
        // Implementación por defecto
        return true;
    }

    /**
     * Obtener orden de presentación del factor
     *
     * @return int
     */
    public function get_display_order(): int {
        $order = get_config($this->component, 'display_order');
        return $order !== null ? (int)$order : 0;
    }
}
