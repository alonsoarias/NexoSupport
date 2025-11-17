<?php
namespace core\plugininfo;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Clase base para plugins de autenticación
 *
 * Todos los plugins auth_* DEBEN extender esta clase
 * e implementar todos los métodos abstractos.
 *
 * @package core\plugininfo
 */
abstract class auth extends base {

    /**
     * Autenticar usuario con credenciales
     *
     * @param string $username Nombre de usuario
     * @param string $password Contraseña
     * @return bool|object False si falla, objeto usuario si éxito
     */
    abstract public function authenticate(string $username, string $password): bool|object;

    /**
     * ¿El plugin permite cambiar contraseña?
     *
     * @return bool
     */
    abstract public function can_change_password(): bool;

    /**
     * ¿El plugin permite recuperar contraseña?
     *
     * @return bool
     */
    abstract public function can_reset_password(): bool;

    /**
     * Hook: después de login exitoso
     *
     * Los plugins pueden sobrescribir este método para realizar
     * acciones adicionales después del login.
     *
     * @param object $user Usuario que acaba de loguear
     * @return void
     */
    public function post_login_hook(object $user): void {
        // Implementación por defecto (vacía)
        // Los plugins pueden sobrescribir si necesitan
    }

    /**
     * Hook: antes de logout
     *
     * @param object $user Usuario que va a cerrar sesión
     * @return void
     */
    public function pre_logout_hook(object $user): void {
        // Implementación por defecto (vacía)
    }

    /**
     * Cambiar contraseña del usuario
     *
     * @param object $user Usuario
     * @param string $newpassword Nueva contraseña
     * @return bool
     */
    public function change_password(object $user, string $newpassword): bool {
        global $DB;

        if (!$this->can_change_password()) {
            return false;
        }

        $hashedpassword = password_hash($newpassword, PASSWORD_DEFAULT);

        return $DB->update_record('users', [
            'id' => $user->id,
            'password' => $hashedpassword,
            'timemodified' => time()
        ]);
    }

    /**
     * Obtener configuración del plugin
     *
     * @return array
     */
    public function get_config(): array {
        return get_config($this->component);
    }

    /**
     * ¿El plugin soporta signup (auto-registro)?
     *
     * @return bool
     */
    public function can_signup(): bool {
        return false;
    }

    /**
     * ¿El plugin sincroniza usuarios desde sistema externo?
     *
     * @return bool
     */
    public function is_synchronised(): bool {
        return false;
    }
}
