<?php
namespace auth_manual;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Plugin de autenticación manual
 *
 * Autentica usuarios contra la base de datos local
 * usando password_hash/password_verify.
 *
 * Sigue el patrón Frankenstyle de NexoSupport,
 * extendiendo la clase base \core\plugininfo\auth.
 *
 * @package auth_manual
 */
class plugin extends \core\plugininfo\auth {

    /**
     * Autenticar usuario con credenciales
     *
     * @param string $username Nombre de usuario
     * @param string $password Contraseña
     * @return bool|object False si falla, objeto usuario si éxito
     */
    public function authenticate(string $username, string $password): bool|object {
        global $DB;

        // Buscar usuario por username
        $user = $DB->get_record('users', ['username' => $username]);

        if (!$user) {
            return false;
        }

        // Verificar que no esté suspendido
        if ($user->suspended) {
            return false;
        }

        // Verificar que no esté eliminado
        if ($user->deleted) {
            return false;
        }

        // Verificar contraseña
        if (!password_verify($password, $user->password)) {
            return false;
        }

        return $user;
    }

    /**
     * ¿El plugin permite cambiar contraseña?
     *
     * @return bool
     */
    public function can_change_password(): bool {
        return true;
    }

    /**
     * ¿El plugin permite recuperar contraseña?
     *
     * @return bool
     */
    public function can_reset_password(): bool {
        return true;
    }

    /**
     * Hook: después de login exitoso
     *
     * Actualiza información de último acceso.
     *
     * @param object $user Usuario que acaba de loguear
     * @return void
     */
    public function post_login_hook(object $user): void {
        $this->update_last_login($user->id);
    }

    /**
     * Actualizar timestamp de último login
     *
     * @param int $userid ID del usuario
     * @return void
     */
    private function update_last_login(int $userid): void {
        global $DB;

        $DB->update_record('users', [
            'id' => $userid,
            'lastlogin' => time(),
            'lastip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'timemodified' => time()
        ]);
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
     * Validar contraseña según política
     *
     * @param string $password
     * @return array Array de errores (vacío si válida)
     */
    public function validate_password(string $password): array {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = get_string('passwordtooshort', 'auth_manual');
        }

        // Agregar más validaciones según política configurada
        $config = $this->get_config();

        if (!empty($config['requireuppercase']) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = get_string('passwordrequireuppercase', 'auth_manual');
        }

        if (!empty($config['requirelowercase']) && !preg_match('/[a-z]/', $password)) {
            $errors[] = get_string('passwordrequirelowercase', 'auth_manual');
        }

        if (!empty($config['requirenumbers']) && !preg_match('/[0-9]/', $password)) {
            $errors[] = get_string('passwordrequirenumbers', 'auth_manual');
        }

        if (!empty($config['requirespecialchars']) && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = get_string('passwordrequirespecialchars', 'auth_manual');
        }

        return $errors;
    }

    /**
     * ¿El plugin soporta signup (auto-registro)?
     *
     * @return bool
     */
    public function can_signup(): bool {
        $config = $this->get_config();
        return !empty($config['allowsignup']);
    }
}
