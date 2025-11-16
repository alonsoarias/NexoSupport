<?php
/**
 * NexoSupport - Core User Class
 *
 * Represents a user entity in the system
 *
 * @package    core\user
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace core\user;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * User Class
 *
 * Core user entity with basic properties and methods
 */
class user
{
    /** @var int User ID */
    public int $id;

    /** @var string Username */
    public string $username;

    /** @var string Email */
    public string $email;

    /** @var string First name */
    public string $firstname = '';

    /** @var string Last name */
    public string $lastname = '';

    /** @var string Status (active, inactive, suspended, pending) */
    public string $status = 'active';

    /** @var bool Email verified */
    public bool $emailverified = false;

    /** @var int Created timestamp */
    public int $createdat;

    /** @var int Updated timestamp */
    public int $updatedat;

    /** @var int|null Deleted timestamp (soft delete) */
    public ?int $deletedat = null;

    /**
     * Constructor
     *
     * @param object|array|null $data User data from database
     */
    public function __construct($data = null)
    {
        if ($data) {
            $this->load_from_data($data);
        }
    }

    /**
     * Load user data from database record
     *
     * @param object|array $data User data
     */
    private function load_from_data($data): void
    {
        $data = (object)$data;

        $this->id = (int)$data->id;
        $this->username = $data->username ?? '';
        $this->email = $data->email ?? '';
        $this->firstname = $data->first_name ?? '';
        $this->lastname = $data->last_name ?? '';
        $this->status = $data->status ?? 'active';
        $this->emailverified = (bool)($data->email_verified ?? false);
        $this->createdat = (int)($data->created_at ?? time());
        $this->updatedat = (int)($data->updated_at ?? time());
        $this->deletedat = isset($data->deleted_at) ? (int)$data->deleted_at : null;
    }

    /**
     * Get full name
     *
     * @return string Full name
     */
    public function get_fullname(): string
    {
        $parts = array_filter([$this->firstname, $this->lastname]);
        return implode(' ', $parts) ?: $this->username;
    }

    /**
     * Check if user is active
     *
     * @return bool True if active
     */
    public function is_active(): bool
    {
        return $this->status === 'active' && $this->deletedat === null;
    }

    /**
     * Check if user is deleted
     *
     * @return bool True if deleted
     */
    public function is_deleted(): bool
    {
        return $this->deletedat !== null;
    }

    /**
     * Convert to array
     *
     * @return array User data as array
     */
    public function to_array(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'fullname' => $this->get_fullname(),
            'status' => $this->status,
            'emailverified' => $this->emailverified,
            'createdat' => $this->createdat,
            'updatedat' => $this->updatedat,
            'deletedat' => $this->deletedat,
            'isactive' => $this->is_active(),
        ];
    }
}
