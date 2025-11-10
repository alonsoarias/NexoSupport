<?php
/**
 * ISER - User Manager
 * @package ISER\Modules\User
 */

namespace ISER\Modules\User;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Helpers;

class UserManager
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int|false
    {
        $required = ['username', 'email', 'password', 'firstname', 'lastname'];
        foreach ($required as $field) {
            if (empty($data[$field])) return false;
        }

        if (!Helpers::validateEmail($data['email'])) return false;

        $now = time();
        return $this->db->insert('users', [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Helpers::hashPassword($data['password']),
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'status' => $data['status'] ?? 1,
            'failed_attempts' => 0,
            'locked_until' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    public function getUserById(int $id): array|false
    {
        return $this->db->selectOne('users', ['id' => $id]);
    }

    public function getUserByUsername(string $username): array|false
    {
        return $this->db->selectOne('users', ['username' => $username]);
    }

    public function getUserByEmail(string $email): array|false
    {
        return $this->db->selectOne('users', ['email' => $email]);
    }

    public function update(int $id, array $data): bool
    {
        $data['timemodified'] = time();
        if (isset($data['password'])) {
            $data['password'] = Helpers::hashPassword($data['password']);
        }
        return $this->db->update('users', $data, ['id' => $id]) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->delete('users', ['id' => $id]) > 0;
    }

    public function recordLoginAttempt(string $username, bool $success, string $ip): void
    {
        $this->db->insert('login_attempts', [
            'username' => $username,
            'ip_address' => $ip,
            'user_agent' => Helpers::getUserAgent(),
            'success' => $success ? 1 : 0,
            'attempted_at' => time(),
        ]);
    }

    public function getFailedAttempts(string $username, int $timeWindow = 900): int
    {
        $since = time() - $timeWindow;
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table('login_attempts')}
                WHERE username = :username AND success = 0 AND attempted_at > :since";
        $result = $this->db->getConnection()->fetchOne($sql, [
            ':username' => $username,
            ':since' => $since
        ]);
        return (int)($result['count'] ?? 0);
    }

    public function lockAccount(string $username, int $duration = 900): bool
    {
        $user = $this->getUserByUsername($username);
        if (!$user) return false;

        return $this->update($user['id'], [
            'locked_until' => time() + $duration,
            'failed_attempts' => $this->getFailedAttempts($username)
        ]);
    }

    public function isAccountLocked(string $username): bool
    {
        $user = $this->getUserByUsername($username);
        if (!$user) return false;
        return $user['locked_until'] > time();
    }

    public function resetFailedAttempts(string $username): bool
    {
        $user = $this->getUserByUsername($username);
        if (!$user) return false;
        return $this->update($user['id'], ['failed_attempts' => 0, 'locked_until' => 0]);
    }
}
