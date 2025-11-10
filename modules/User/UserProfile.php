<?php
/**
 * ISER - User Profile Manager
 * @package ISER\Modules\User
 */

namespace ISER\Modules\User;

use ISER\Core\Database\Database;

class UserProfile
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get user profile by user ID
     */
    public function getProfile(int $userId): array|false
    {
        $profile = $this->db->selectOne('user_profiles', ['userid' => $userId]);

        // If profile doesn't exist, create a basic one
        if (!$profile) {
            $this->createProfile($userId);
            $profile = $this->db->selectOne('user_profiles', ['userid' => $userId]);
        }

        return $profile;
    }

    /**
     * Create a new profile for a user
     */
    public function createProfile(int $userId, array $data = []): int|false
    {
        $now = time();

        return $this->db->insert('user_profiles', array_merge([
            'userid' => $userId,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'postalcode' => $data['postalcode'] ?? null,
            'institution' => $data['institution'] ?? null,
            'department' => $data['department'] ?? null,
            'position' => $data['position'] ?? null,
            'bio' => $data['bio'] ?? null,
            'website' => $data['website'] ?? null,
            'linkedin' => $data['linkedin'] ?? null,
            'twitter' => $data['twitter'] ?? null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]));
    }

    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): bool
    {
        $profile = $this->getProfile($userId);

        if (!$profile) {
            // Create profile if it doesn't exist
            return $this->createProfile($userId, $data) !== false;
        }

        $data['timemodified'] = time();

        // Remove userid from data if present (shouldn't be updated)
        unset($data['userid']);
        unset($data['id']);

        return $this->db->update('user_profiles', $data, ['userid' => $userId]) > 0;
    }

    /**
     * Delete user profile
     */
    public function deleteProfile(int $userId): bool
    {
        return $this->db->delete('user_profiles', ['userid' => $userId]) > 0;
    }

    /**
     * Get profile field value
     */
    public function getField(int $userId, string $field): mixed
    {
        $profile = $this->getProfile($userId);
        return $profile[$field] ?? null;
    }

    /**
     * Set profile field value
     */
    public function setField(int $userId, string $field, mixed $value): bool
    {
        return $this->updateProfile($userId, [$field => $value]);
    }

    /**
     * Get all available profile fields
     */
    public function getProfileFields(): array
    {
        return [
            'phone' => [
                'type' => 'text',
                'label' => 'Teléfono',
                'maxlength' => 20,
            ],
            'address' => [
                'type' => 'textarea',
                'label' => 'Dirección',
            ],
            'city' => [
                'type' => 'text',
                'label' => 'Ciudad',
                'maxlength' => 100,
            ],
            'country' => [
                'type' => 'text',
                'label' => 'País',
                'maxlength' => 100,
            ],
            'postalcode' => [
                'type' => 'text',
                'label' => 'Código Postal',
                'maxlength' => 20,
            ],
            'institution' => [
                'type' => 'text',
                'label' => 'Institución',
                'maxlength' => 255,
            ],
            'department' => [
                'type' => 'text',
                'label' => 'Departamento',
                'maxlength' => 255,
            ],
            'position' => [
                'type' => 'text',
                'label' => 'Cargo',
                'maxlength' => 255,
            ],
            'bio' => [
                'type' => 'textarea',
                'label' => 'Biografía',
                'rows' => 5,
            ],
            'website' => [
                'type' => 'url',
                'label' => 'Sitio Web',
                'maxlength' => 255,
            ],
            'linkedin' => [
                'type' => 'url',
                'label' => 'LinkedIn',
                'maxlength' => 255,
            ],
            'twitter' => [
                'type' => 'text',
                'label' => 'Twitter',
                'maxlength' => 255,
            ],
        ];
    }

    /**
     * Validate profile data
     */
    public function validateProfileData(array $data): array
    {
        $errors = [];
        $fields = $this->getProfileFields();

        foreach ($data as $field => $value) {
            if (!isset($fields[$field])) {
                continue; // Skip unknown fields
            }

            $fieldConfig = $fields[$field];

            // Check length for text fields
            if (isset($fieldConfig['maxlength']) && strlen($value) > $fieldConfig['maxlength']) {
                $errors[$field] = "El campo {$fieldConfig['label']} excede la longitud máxima de {$fieldConfig['maxlength']} caracteres";
            }

            // Validate URL fields
            if ($fieldConfig['type'] === 'url' && !empty($value)) {
                if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                    $errors[$field] = "El campo {$fieldConfig['label']} debe ser una URL válida";
                }
            }
        }

        return $errors;
    }

    /**
     * Get complete user data with profile
     */
    public function getCompleteUserData(int $userId): array|false
    {
        $user = $this->db->selectOne('users', ['id' => $userId]);
        if (!$user) return false;

        $profile = $this->getProfile($userId);

        // Merge user and profile data
        return array_merge($user, ['profile' => $profile ?: []]);
    }

    /**
     * Search profiles by field
     */
    public function searchProfiles(string $field, string $value): array
    {
        $sql = "SELECT p.*, u.username, u.email, u.firstname, u.lastname
                FROM {$this->db->table('user_profiles')} p
                JOIN {$this->db->table('users')} u ON p.userid = u.id
                WHERE p.{$field} LIKE :value
                AND u.deleted = 0";

        return $this->db->getConnection()->fetchAll($sql, [
            ':value' => '%' . $value . '%'
        ]);
    }
}
