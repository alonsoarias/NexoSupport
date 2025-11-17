<?php
/**
 * NexoSupport - Core Role Class
 *
 * Represents a role in the RBAC system
 *
 * @package    ISER\Core\Role
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Core\Role;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Role Class
 *
 * Core role entity with permissions
 */
class Role
{
    /** @var int Role ID */
    public int $id;

    /** @var string Role name */
    public string $name;

    /** @var string Role slug */
    public string $slug;

    /** @var string Description */
    public string $description = '';

    /** @var bool Is system role (cannot be deleted) */
    public bool $issystem = false;

    /** @var int Created timestamp */
    public int $createdat;

    /** @var int Updated timestamp */
    public int $updatedat;

    /** @var array|null Permissions (lazy loaded) */
    private ?array $permissions = null;

    /**
     * Constructor
     *
     * @param object|array|null $data Role data from database
     */
    public function __construct($data = null)
    {
        if ($data) {
            $this->load_from_data($data);
        }
    }

    /**
     * Load role data from database record
     *
     * @param object|array $data Role data
     */
    private function load_from_data($data): void
    {
        $data = (object)$data;

        $this->id = (int)$data->id;
        $this->name = $data->name ?? '';
        $this->slug = $data->slug ?? '';
        $this->description = $data->description ?? '';
        $this->issystem = (bool)($data->is_system ?? false);
        $this->createdat = (int)($data->created_at ?? time());
        $this->updatedat = (int)($data->updated_at ?? time());
    }

    /**
     * Check if role can be deleted
     *
     * @return bool True if deletable
     */
    public function can_delete(): bool
    {
        return !$this->issystem;
    }

    /**
     * Convert to array
     *
     * @return array Role data as array
     */
    public function to_array(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'issystem' => $this->issystem,
            'createdat' => $this->createdat,
            'updatedat' => $this->updatedat,
        ];
    }
}
