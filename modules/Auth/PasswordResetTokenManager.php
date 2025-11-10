<?php
/**
 * Password Reset Token Manager
 *
 * Maneja la nueva tabla normalizada password_reset_tokens
 * separada de la tabla users para mejor seguridad y trazabilidad.
 *
 * @package ISER\Auth
 * @author ISER Desarrollo
 * @license Propietario
 */

namespace ISER\Auth;

use PDO;

class PasswordResetTokenManager
{
    private PDO $pdo;
    private string $prefix;

    public function __construct(PDO $pdo, string $prefix = 'iser_')
    {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    /**
     * Crear un nuevo token de reseteo de contraseña
     *
     * @param int $userId ID del usuario
     * @param int $expiresIn Tiempo de expiración en segundos (default: 1 hora)
     * @return string Token generado
     */
    public function createToken(int $userId, int $expiresIn = 3600): string
    {
        // Invalidar tokens previos del usuario
        $this->invalidateUserTokens($userId);

        // Generar token único
        $token = bin2hex(random_bytes(32));
        $now = time();
        $expiresAt = $now + $expiresIn;

        $sql = "INSERT INTO `{$this->prefix}password_reset_tokens`
                (user_id, token, expires_at, created_at)
                VALUES (:user_id, :token, :expires_at, :created_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':token' => $token,
            ':expires_at' => $expiresAt,
            ':created_at' => $now
        ]);

        return $token;
    }

    /**
     * Validar un token de reseteo
     *
     * @param string $token Token a validar
     * @return array|null Datos del token si es válido, null si es inválido o expiró
     */
    public function validateToken(string $token): ?array
    {
        $sql = "SELECT * FROM `{$this->prefix}password_reset_tokens`
                WHERE token = :token
                  AND expires_at > :now
                  AND used_at IS NULL";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':token' => $token,
            ':now' => time()
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Marcar un token como usado
     *
     * @param string $token Token a marcar como usado
     * @return bool True si se marcó correctamente
     */
    public function markAsUsed(string $token): bool
    {
        $sql = "UPDATE `{$this->prefix}password_reset_tokens`
                SET used_at = :used_at
                WHERE token = :token";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':used_at' => time(),
            ':token' => $token
        ]);
    }

    /**
     * Invalidar todos los tokens activos de un usuario
     *
     * @param int $userId ID del usuario
     * @return bool True si se invalidaron tokens
     */
    public function invalidateUserTokens(int $userId): bool
    {
        $sql = "UPDATE `{$this->prefix}password_reset_tokens`
                SET used_at = :used_at
                WHERE user_id = :user_id
                  AND used_at IS NULL
                  AND expires_at > :now";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':used_at' => time(),
            ':user_id' => $userId,
            ':now' => time()
        ]);
    }

    /**
     * Obtener historial de tokens de reseteo de un usuario
     *
     * @param int $userId ID del usuario
     * @param int $limit Límite de registros
     * @return array Historial de tokens
     */
    public function getUserTokenHistory(int $userId, int $limit = 10): array
    {
        $sql = "SELECT * FROM `{$this->prefix}password_reset_tokens`
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Limpiar tokens expirados (mantenimiento)
     *
     * @param int $olderThan Eliminar tokens más antiguos que X segundos (default: 24 horas)
     * @return int Cantidad de tokens eliminados
     */
    public function cleanupExpiredTokens(int $olderThan = 86400): int
    {
        $cutoff = time() - $olderThan;

        $sql = "DELETE FROM `{$this->prefix}password_reset_tokens`
                WHERE expires_at < :cutoff";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':cutoff' => $cutoff]);

        return $stmt->rowCount();
    }

    /**
     * Verificar si un usuario tiene tokens activos
     *
     * @param int $userId ID del usuario
     * @return bool True si tiene tokens activos
     */
    public function hasActiveTokens(int $userId): bool
    {
        $sql = "SELECT COUNT(*) as count
                FROM `{$this->prefix}password_reset_tokens`
                WHERE user_id = :user_id
                  AND expires_at > :now
                  AND used_at IS NULL";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':now' => time()
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Obtener información del usuario asociado a un token
     *
     * @param string $token Token de reseteo
     * @return array|null Información del usuario si el token es válido
     */
    public function getUserByToken(string $token): ?array
    {
        $sql = "SELECT u.*
                FROM `{$this->prefix}users` u
                INNER JOIN `{$this->prefix}password_reset_tokens` t ON u.id = t.user_id
                WHERE t.token = :token
                  AND t.expires_at > :now
                  AND t.used_at IS NULL";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':token' => $token,
            ':now' => time()
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtener estadísticas de tokens de reseteo
     *
     * @return array Estadísticas
     */
    public function getStatistics(): array
    {
        $sql = "SELECT
                    COUNT(*) as total_tokens,
                    SUM(CASE WHEN used_at IS NOT NULL THEN 1 ELSE 0 END) as used_tokens,
                    SUM(CASE WHEN expires_at > UNIX_TIMESTAMP() AND used_at IS NULL THEN 1 ELSE 0 END) as active_tokens,
                    SUM(CASE WHEN expires_at <= UNIX_TIMESTAMP() AND used_at IS NULL THEN 1 ELSE 0 END) as expired_tokens
                FROM `{$this->prefix}password_reset_tokens`";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Revocar un token específico
     *
     * @param string $token Token a revocar
     * @return bool True si se revocó correctamente
     */
    public function revokeToken(string $token): bool
    {
        return $this->markAsUsed($token);
    }

    /**
     * Verificar límite de intentos de reseteo
     * Previene abuso del sistema de reseteo de contraseñas
     *
     * @param int $userId ID del usuario
     * @param int $timeWindow Ventana de tiempo en segundos (default: 1 hora)
     * @param int $maxAttempts Máximo de intentos permitidos (default: 3)
     * @return bool True si está dentro del límite
     */
    public function checkResetRateLimit(int $userId, int $timeWindow = 3600, int $maxAttempts = 3): bool
    {
        $cutoff = time() - $timeWindow;

        $sql = "SELECT COUNT(*) as count
                FROM `{$this->prefix}password_reset_tokens`
                WHERE user_id = :user_id
                  AND created_at > :cutoff";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':cutoff' => $cutoff
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['count'] ?? 0;

        return $count < $maxAttempts;
    }
}
