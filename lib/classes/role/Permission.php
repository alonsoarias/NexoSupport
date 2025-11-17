<?php
/**
 * NexoSupport - Core Permission Class
 *
 * Represents a permission/capability in the RBAC system
 *
 * @package    ISER\Core\Role
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Core\Role;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Permission Class
 *
 * Core permission/capability entity
 */
class Permission
{
    /** @var int Permission ID */
    public int $id;

    /** @var string Permission name */
    public string $name;

    /** @var string Permission slug (e.g., 'users.view', 'roles.create') */
    public string $slug;

    /** @var string Description */
    public string $description = '';

    /** @var string Module this permission belongs to */
    public string $module = '';

    /** @var int Created timestamp */
    public int $createdat;

    /** @var int Updated timestamp */
    public int $updatedat;

    /**
     * Constructor
     *
     * @param object|array|null $data Permission data from database
     */
    public function __construct($data = null)
    {
        if ($data) {
            $this->load_from_data($data);
        }
    }

    /**
     * Load permission data from database record
     *
     * @param object|array $data Permission data
     */
    private function load_from_data($data): void
    {
        $data = (object)$data;

        $this->id = (int)$data->id;
        $this->name = $data->name ?? '';
        $this->slug = $data->slug ?? '';
        $this->description = $data->description ?? '';
        $this->module = $data->module ?? '';
        $this->createdat = (int)($data->created_at ?? time());
        $this->updatedat = (int)($data->updated_at ?? time());
    }

    /**
     * Convert to array
     *
     * @return array Permission data as array
     */
    public function to_array(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'module' => $this->module,
            'createdat' => $this->createdat,
            'updatedat' => $this->updatedat,
        ];
    }
}
