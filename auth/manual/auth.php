<?php
/**
 * Authentication plugin: Manual accounts
 *
 * @package    auth_manual
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

namespace ISER\Auth\Manual;

use ISER\Core\Auth\AuthPlugin;
use ISER\Core\Database\Database;
use ISER\Core\Config\Config;

/**
 * Manual authentication plugin.
 *
 * Esta clase maneja la autenticación manual de usuarios,
 * donde las credenciales se almacenan en la base de datos local.
 */
class auth_plugin_manual extends AuthPlugin
{
    /**
     * Constructor.
     *
     * @param Database $db Database instance
     * @param Config $config Configuration instance
     */
    public function __construct(Database $db, Config $config)
    {
        $this->authtype = 'manual';
        parent::__construct($db, $config);
    }

    /**
     * Autentica un usuario con nombre de usuario y contraseña.
     *
     * @param string $username El nombre de usuario
     * @param string $password La contraseña en texto plano
     * @return bool true si la autenticación fue exitosa, false en caso contrario
     */
    public function user_login(string $username, string $password): bool
    {
        $user = $this->db->get_record('users', ['username' => $username]);

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
     * @param object $user El objeto de usuario
     * @param string $newpassword La nueva contraseña en texto plano
     * @return bool true si el cambio fue exitoso, false en caso contrario
     */
    public function user_update_password(object $user, string $newpassword): bool
    {
        $hash = password_hash($newpassword, PASSWORD_BCRYPT);

        return $this->db->update_record('users', [
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
    public function can_change_password(): bool
    {
        return true;
    }

    /**
     * Indica si este plugin de autenticación puede editar usuarios.
     *
     * @return bool true si se permite editar usuarios
     */
    public function can_edit_profile(): bool
    {
        return true;
    }

    /**
     * Indica si este plugin de autenticación es interno.
     *
     * Los plugins internos usan la tabla de usuarios de NexoSupport.
     *
     * @return bool true si es interno
     */
    public function is_internal(): bool
    {
        return true;
    }

    /**
     * Indica si se pueden crear nuevos usuarios manualmente.
     *
     * @return bool true si se permite la creación manual
     */
    public function can_signup(): bool
    {
        return false;
    }

    /**
     * Indica si este plugin puede confirmar usuarios.
     *
     * @return bool false ya que la confirmación es manual
     */
    public function can_confirm(): bool
    {
        return false;
    }

    /**
     * Indica si este plugin puede resetear contraseñas.
     *
     * @return bool true si se permite resetear contraseñas
     */
    public function can_reset_password(): bool
    {
        return true;
    }

    /**
     * Hook pre-login para validaciones adicionales.
     *
     * @return bool true to continue with login
     */
    public function pre_loginpage_hook(): bool
    {
        // No se requieren acciones previas
        return true;
    }

    /**
     * Hook de logout.
     *
     * @return void
     */
    public function logoutpage_hook(): void
    {
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
    public function sync_users(bool $do_updates = false): bool
    {
        // No hay sincronización para autenticación manual
        return true;
    }

    /**
     * Obtiene la configuración de usuarios desde una fuente externa.
     *
     * @param string $username El nombre de usuario
     * @return array|false Array con los datos del usuario o false si no existe
     */
    public function get_userinfo(string $username): array|false
    {
        // Los datos están en la base de datos local, no en fuente externa
        return false;
    }

    /**
     * Obtiene la URL de cambio de contraseña.
     *
     * @return string|null URL de cambio de contraseña
     */
    public function change_password_url(): ?string
    {
        $wwwroot = $this->config->get('wwwroot', '');
        return $wwwroot ? $wwwroot . '/login/change_password.php' : null;
    }

    /**
     * Hook post-login para acciones adicionales.
     *
     * @param object $user El objeto de usuario autenticado
     * @param string $username El nombre de usuario
     * @param string $password La contraseña
     * @return void
     */
    public function user_authenticated_hook(object &$user, string $username, string $password): void
    {
        // Registrar último login
        $this->db->update_record('users', [
            'id' => $user->id,
            'last_login' => time(),
        ]);
    }
}
