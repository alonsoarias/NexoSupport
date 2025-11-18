<?php
namespace core\session;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Session Manager
 *
 * Gestiona sesiones de usuario con almacenamiento en base de datos.
 * Similar a Moodle's session manager.
 *
 * @package core\session
 */
class manager {

    /** @var bool Si la sesión ha sido iniciada */
    private static bool $started = false;

    /** @var int Session timeout en segundos (2 horas) */
    const SESSION_TIMEOUT = 7200;

    /**
     * Iniciar sesión
     *
     * @return void
     */
    public static function start(): void {
        if (self::$started) {
            return;
        }

        // Configurar handler de sesión
        session_set_save_handler(
            [__CLASS__, 'open'],
            [__CLASS__, 'close'],
            [__CLASS__, 'read'],
            [__CLASS__, 'write'],
            [__CLASS__, 'destroy'],
            [__CLASS__, 'gc']
        );

        // Configurar opciones de sesión
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', self::SESSION_TIMEOUT);

        // Nombre de la sesión
        session_name('NEXOSUPPORT_SESSION');

        // Iniciar sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        self::$started = true;

        // Regenerar ID de sesión periódicamente
        self::regenerate_id_if_needed();
    }

    /**
     * Abrir sesión (handler)
     *
     * @param string $path
     * @param string $name
     * @return bool
     */
    public static function open(string $path, string $name): bool {
        return true;
    }

    /**
     * Cerrar sesión (handler)
     *
     * @return bool
     */
    public static function close(): bool {
        return true;
    }

    /**
     * Leer datos de sesión (handler)
     *
     * @param string $id
     * @return string
     */
    public static function read(string $id): string {
        global $DB;

        try {
            $record = $DB->get_record('sessions', ['id' => $id]);

            if ($record) {
                // Verificar timeout
                if ($record->timemodified < (time() - self::SESSION_TIMEOUT)) {
                    // Sesión expirada
                    self::destroy($id);
                    return '';
                }

                return $record->data ?? '';
            }
        } catch (\Exception $e) {
            debugging('Error reading session: ' . $e->getMessage());
        }

        return '';
    }

    /**
     * Escribir datos de sesión (handler)
     *
     * @param string $id
     * @param string $data
     * @return bool
     */
    public static function write(string $id, string $data): bool {
        global $DB, $USER;

        try {
            $userid = $USER->id ?? 0;
            $now = time();

            $record = $DB->get_record('sessions', ['id' => $id]);

            if ($record) {
                // Actualizar existente
                $DB->update_record('sessions', [
                    'id' => $id,
                    'userid' => $userid,
                    'data' => $data,
                    'timemodified' => $now
                ]);
            } else {
                // Crear nuevo
                $DB->insert_record('sessions', [
                    'id' => $id,
                    'userid' => $userid,
                    'data' => $data,
                    'timecreated' => $now,
                    'timemodified' => $now
                ]);
            }

            return true;
        } catch (\Exception $e) {
            debugging('Error writing session: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Destruir sesión (handler)
     *
     * @param string $id
     * @return bool
     */
    public static function destroy(string $id): bool {
        global $DB;

        try {
            $DB->delete_records('sessions', ['id' => $id]);
            return true;
        } catch (\Exception $e) {
            debugging('Error destroying session: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Garbage collection (handler)
     *
     * @param int $maxlifetime
     * @return bool
     */
    public static function gc(int $maxlifetime): bool {
        global $DB;

        try {
            $cutoff = time() - $maxlifetime;

            $DB->delete_records_select('sessions', 'timemodified < ?', [$cutoff]);

            return true;
        } catch (\Exception $e) {
            debugging('Error in session GC: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Regenerar ID de sesión si es necesario
     *
     * @return void
     */
    private static function regenerate_id_if_needed(): void {
        // Regenerar cada 30 minutos
        if (!isset($_SESSION['last_regenerate'])) {
            $_SESSION['last_regenerate'] = time();
        } elseif (time() - $_SESSION['last_regenerate'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regenerate'] = time();
        }
    }

    /**
     * Terminar sesión actual
     *
     * @return void
     */
    public static function terminate(): void {
        global $USER;

        $sessionid = session_id();

        if ($sessionid) {
            self::destroy($sessionid);
        }

        // Limpiar variables
        $_SESSION = [];

        // Destruir cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destruir sesión
        session_destroy();

        // Resetear USER
        $USER = new \stdClass();
    }

    /**
     * Obtener sesskey (para protección CSRF)
     *
     * @return string
     */
    public static function get_sesskey(): string {
        if (!isset($_SESSION['sesskey'])) {
            $_SESSION['sesskey'] = bin2hex(random_bytes(16));
        }

        return $_SESSION['sesskey'];
    }

    /**
     * Verificar sesskey
     *
     * @param string $sesskey
     * @return bool
     */
    public static function verify_sesskey(string $sesskey): bool {
        return hash_equals(self::get_sesskey(), $sesskey);
    }

    /**
     * Obtener todas las sesiones activas de un usuario
     *
     * @param int $userid
     * @return array
     */
    public static function get_user_sessions(int $userid): array {
        global $DB;

        return $DB->get_records('sessions', ['userid' => $userid]);
    }

    /**
     * Terminar todas las sesiones de un usuario
     *
     * @param int $userid
     * @return void
     */
    public static function terminate_user_sessions(int $userid): void {
        global $DB;

        $sessions = self::get_user_sessions($userid);

        foreach ($sessions as $session) {
            self::destroy($session->id);
        }
    }

    /**
     * Contar sesiones activas
     *
     * @return int
     */
    public static function count_active_sessions(): int {
        global $DB;

        $cutoff = time() - self::SESSION_TIMEOUT;

        return $DB->count_records_select('sessions', 'timemodified > ?', [$cutoff]);
    }
}
