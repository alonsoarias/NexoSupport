<?php
namespace core\user;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * User Manager
 *
 * Gestiona operaciones CRUD de usuarios.
 * Similar a Moodle's user API.
 *
 * @package core\user
 */
class manager {

    /**
     * Crear usuario
     *
     * @param object|array $user
     * @return int User ID
     */
    public static function create_user(object|array $user): int {
        global $DB;

        $user = (object)$user;

        // Validaciones
        self::validate_user($user, true);

        // Hash password si está presente
        if (isset($user->password)) {
            $user->password = password_hash($user->password, PASSWORD_DEFAULT);
        }

        // Campos por defecto
        if (!isset($user->auth)) {
            $user->auth = 'manual';
        }
        if (!isset($user->suspended)) {
            $user->suspended = 0;
        }
        if (!isset($user->deleted)) {
            $user->deleted = 0;
        }

        $user->timecreated = time();
        $user->timemodified = time();

        $userid = $DB->insert_record('users', $user);

        return $userid;
    }

    /**
     * Actualizar usuario
     *
     * @param object|array $user Must have 'id'
     * @return bool
     */
    public static function update_user(object|array $user): bool {
        global $DB;

        $user = (object)$user;

        if (!isset($user->id)) {
            throw new \coding_exception('User ID is required for update');
        }

        // Validaciones
        self::validate_user($user, false);

        // Hash password si está presente y ha cambiado
        if (isset($user->password) && !empty($user->password)) {
            $user->password = password_hash($user->password, PASSWORD_DEFAULT);
        } else {
            unset($user->password); // No actualizar si está vacío
        }

        $user->timemodified = time();

        return $DB->update_record('users', $user);
    }

    /**
     * Eliminar usuario (soft delete)
     *
     * @param int $userid
     * @return bool
     */
    public static function delete_user(int $userid): bool {
        global $DB;

        $user = self::get_user($userid);

        if (!$user) {
            return false;
        }

        // Soft delete
        $user->deleted = 1;
        $user->timemodified = time();

        // Terminar sesiones del usuario
        \core\session\manager::terminate_user_sessions($userid);

        return $DB->update_record('users', $user);
    }

    /**
     * Obtener usuario por ID
     *
     * @param int $userid
     * @param bool $includeDeleted
     * @return object|null
     */
    public static function get_user(int $userid, bool $includeDeleted = false): ?object {
        global $DB;

        $conditions = ['id' => $userid];

        if (!$includeDeleted) {
            $conditions['deleted'] = 0;
        }

        return $DB->get_record('users', $conditions);
    }

    /**
     * Obtener usuario por username
     *
     * @param string $username
     * @return object|null
     */
    public static function get_user_by_username(string $username): ?object {
        global $DB;

        return $DB->get_record('users', [
            'username' => $username,
            'deleted' => 0
        ]);
    }

    /**
     * Obtener usuario por email
     *
     * @param string $email
     * @return object|null
     */
    public static function get_user_by_email(string $email): ?object {
        global $DB;

        return $DB->get_record('users', [
            'email' => $email,
            'deleted' => 0
        ]);
    }

    /**
     * Obtener todos los usuarios
     *
     * @param bool $includeDeleted
     * @param int $limitfrom
     * @param int $limitnum
     * @return array
     */
    public static function get_all_users(bool $includeDeleted = false, int $limitfrom = 0, int $limitnum = 0): array {
        global $DB;

        $conditions = $includeDeleted ? [] : ['deleted' => 0];

        return $DB->get_records('users', $conditions, 'lastname ASC, firstname ASC', '*', $limitfrom, $limitnum);
    }

    /**
     * Contar usuarios
     *
     * @param bool $includeDeleted
     * @return int
     */
    public static function count_users(bool $includeDeleted = false): int {
        global $DB;

        $conditions = $includeDeleted ? [] : ['deleted' => 0];

        return $DB->count_records('users', $conditions);
    }

    /**
     * Buscar usuarios
     *
     * @param string $search
     * @param int $limitfrom
     * @param int $limitnum
     * @return array
     */
    public static function search_users(string $search, int $limitfrom = 0, int $limitnum = 25): array {
        global $DB;

        $search = '%' . $search . '%';

        $sql = "SELECT * FROM {users}
                WHERE deleted = 0
                AND (username LIKE ? OR firstname LIKE ? OR lastname LIKE ? OR email LIKE ?)
                ORDER BY lastname ASC, firstname ASC";

        if ($limitnum > 0) {
            $sql .= " LIMIT $limitnum OFFSET $limitfrom";
        }

        return $DB->get_records_sql($sql, [$search, $search, $search, $search]);
    }

    /**
     * Verificar si username existe
     *
     * @param string $username
     * @param int|null $excludeUserid
     * @return bool
     */
    public static function username_exists(string $username, ?int $excludeUserid = null): bool {
        global $DB;

        $sql = "SELECT id FROM {users} WHERE username = ? AND deleted = 0";
        $params = [$username];

        if ($excludeUserid !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeUserid;
        }

        $sql .= " LIMIT 1";

        return $DB->get_record_sql($sql, $params) !== null;
    }

    /**
     * Verificar si email existe
     *
     * @param string $email
     * @param int|null $excludeUserid
     * @return bool
     */
    public static function email_exists(string $email, ?int $excludeUserid = null): bool {
        global $DB;

        $sql = "SELECT id FROM {users} WHERE email = ? AND deleted = 0";
        $params = [$email];

        if ($excludeUserid !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeUserid;
        }

        $sql .= " LIMIT 1";

        return $DB->get_record_sql($sql, $params) !== null;
    }

    /**
     * Validar datos de usuario
     *
     * @param object $user
     * @param bool $isNew
     * @return void
     * @throws \coding_exception
     */
    private static function validate_user(object $user, bool $isNew): void {
        if ($isNew) {
            // Campos requeridos para nuevo usuario
            if (empty($user->username)) {
                throw new \coding_exception('Username is required');
            }
            if (empty($user->email)) {
                throw new \coding_exception('Email is required');
            }
            if (empty($user->firstname)) {
                throw new \coding_exception('Firstname is required');
            }
            if (empty($user->lastname)) {
                throw new \coding_exception('Lastname is required');
            }
        }

        // Validar username
        if (isset($user->username)) {
            if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $user->username)) {
                throw new \coding_exception('Username can only contain letters, numbers, dots, hyphens and underscores');
            }

            if (strlen($user->username) < 3) {
                throw new \coding_exception('Username must be at least 3 characters');
            }

            // Verificar duplicados
            $excludeId = isset($user->id) ? $user->id : null;
            if (self::username_exists($user->username, $excludeId)) {
                throw new \coding_exception('Username already exists');
            }
        }

        // Validar email
        if (isset($user->email)) {
            if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                throw new \coding_exception('Invalid email format');
            }

            // Verificar duplicados
            $excludeId = isset($user->id) ? $user->id : null;
            if (self::email_exists($user->email, $excludeId)) {
                throw new \coding_exception('Email already exists');
            }
        }

        // Validar password
        if (isset($user->password) && !empty($user->password)) {
            if (strlen($user->password) < 8) {
                throw new \coding_exception('Password must be at least 8 characters');
            }
        }
    }

    /**
     * Suspender usuario
     *
     * @param int $userid
     * @return bool
     */
    public static function suspend_user(int $userid): bool {
        global $DB;

        $user = self::get_user($userid);

        if (!$user) {
            return false;
        }

        $user->suspended = 1;
        $user->timemodified = time();

        // Terminar sesiones
        \core\session\manager::terminate_user_sessions($userid);

        return $DB->update_record('users', $user);
    }

    /**
     * Reactivar usuario
     *
     * @param int $userid
     * @return bool
     */
    public static function unsuspend_user(int $userid): bool {
        global $DB;

        $user = self::get_user($userid);

        if (!$user) {
            return false;
        }

        $user->suspended = 0;
        $user->timemodified = time();

        return $DB->update_record('users', $user);
    }

    /**
     * Actualizar último login
     *
     * @param int $userid
     * @return void
     */
    public static function update_last_login(int $userid): void {
        global $DB;

        $DB->update_record('users', [
            'id' => $userid,
            'lastlogin' => time(),
            'lastip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    }
}
