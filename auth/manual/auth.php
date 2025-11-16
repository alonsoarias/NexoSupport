<?php
/**
 * Authentication plugin: Manual accounts
 *
 * @package    auth_manual
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

/**
 * Manual authentication plugin.
 *
 * Esta clase maneja la autenticación manual de usuarios,
 * donde las credenciales se almacenan en la base de datos local.
 */
class auth_plugin_manual extends auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'manual';
        $this->config = get_config('auth_manual');
    }

    /**
     * Autentica un usuario con nombre de usuario y contraseña.
     *
     * @param string $username El nombre de usuario
     * @param string $password La contraseña en texto plano
     * @return bool true si la autenticación fue exitosa, false en caso contrario
     */
    public function user_login($username, $password) {
        global $DB;

        $user = $DB->get_record('users', ['username' => $username]);

        if (!$user) {
            return false;
        }

        // Verificar si la cuenta está suspendida
        if (!empty($user->suspended)) {
            return false;
        }

        // Verificar la contraseña usando password_verify
        if (!password_verify($password, $user->password)) {
            return false;
        }

        // Autenticación exitosa
        return true;
    }

    /**
     * Actualiza la contraseña de un usuario en la base de datos.
     *
     * @param stdClass $user El objeto de usuario
     * @param string $newpassword La nueva contraseña en texto plano
     * @return bool true si el cambio fue exitoso, false en caso contrario
     */
    public function user_update_password($user, $newpassword) {
        global $DB;

        $hash = password_hash($newpassword, PASSWORD_BCRYPT);

        return $DB->update_record('users', [
            'id' => $user->id,
            'password' => $hash,
            'password_updated_at' => time(),
        ]);
    }

    /**
     * Indica si este plugin de autenticación permite cambiar contraseñas.
     *
     * @return bool true si se permite cambiar contraseñas
     */
    public function can_change_password() {
        return true;
    }

    /**
     * Indica si este plugin de autenticación puede editar usuarios.
     *
     * @return bool true si se permite editar usuarios
     */
    public function can_edit_profile() {
        return true;
    }

    /**
     * Indica si este plugin de autenticación es interno.
     *
     * Los plugins internos usan la tabla de usuarios de NexoSupport.
     *
     * @return bool true si es interno
     */
    public function is_internal() {
        return true;
    }

    /**
     * Indica si se pueden crear nuevos usuarios manualmente.
     *
     * @return bool true si se permite la creación manual
     */
    public function can_signup() {
        return false;
    }

    /**
     * Indica si este plugin puede confirmar usuarios.
     *
     * @return bool false ya que la confirmación es manual
     */
    public function can_confirm() {
        return false;
    }

    /**
     * Indica si este plugin puede resetear contraseñas.
     *
     * @return bool true si se permite resetear contraseñas
     */
    public function can_reset_password() {
        return true;
    }

    /**
     * Indica si se debe mostrar el formulario de login.
     *
     * @return bool true para mostrar el formulario
     */
    public function loginpage_hook() {
        return true;
    }

    /**
     * Hook de logout.
     */
    public function logoutpage_hook() {
        // No se requiere acción especial en logout
    }

    /**
     * Sincroniza usuarios desde una fuente externa.
     *
     * Para autenticación manual, no hay sincronización externa.
     *
     * @param bool $do_updates true para aplicar actualizaciones
     * @return bool true si la sincronización fue exitosa
     */
    public function sync_users($do_updates = false) {
        // No hay sincronización para autenticación manual
        return true;
    }

    /**
     * Obtiene la configuración de usuarios desde una fuente externa.
     *
     * @param string $username El nombre de usuario
     * @return array|bool Array con los datos del usuario o false si no existe
     */
    public function get_userinfo($username) {
        // Los datos están en la base de datos local, no en fuente externa
        return false;
    }

    /**
     * Obtiene la URL de cambio de contraseña.
     *
     * @return moodle_url|null URL de cambio de contraseña
     */
    public function change_password_url() {
        global $CFG;
        return new moodle_url($CFG->wwwroot . '/login/change_password.php');
    }

    /**
     * Hook pre-login para validaciones adicionales.
     *
     * @param string $username El nombre de usuario
     * @param string $password La contraseña
     * @param int $type Tipo de autenticación
     * @return void
     */
    public function pre_loginpage_hook() {
        // No se requieren acciones previas
    }

    /**
     * Hook post-login para acciones adicionales.
     *
     * @param stdClass $user El objeto de usuario autenticado
     * @param string $username El nombre de usuario
     * @param string $password La contraseña
     */
    public function user_authenticated_hook(&$user, $username, $password) {
        // Registrar último login
        global $DB;
        $DB->update_record('users', [
            'id' => $user->id,
            'last_login' => time(),
        ]);
    }
}
