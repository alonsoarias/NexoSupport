<?php
namespace core\rbac;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Role Management System
 *
 * Gestión de roles y capabilities.
 *
 * @package core\rbac
 */
class role {

    /** @var int Role ID */
    public int $id;

    /** @var string Role shortname */
    public string $shortname;

    /** @var string Role name */
    public string $name;

    /** @var string Role description */
    public string $description;

    /** @var int Archetype */
    public int $archetype;

    /** @var int Sort order */
    public int $sortorder;

    /**
     * Constructor
     *
     * @param object $record
     */
    public function __construct(object $record) {
        $this->id = (int)$record->id;
        $this->shortname = $record->shortname;
        $this->name = $record->name;
        $this->description = $record->description ?? '';
        $this->archetype = (int)($record->archetype ?? 0);
        $this->sortorder = (int)($record->sortorder ?? 0);
    }

    /**
     * Get role by ID
     *
     * @param int $id
     * @return role|null
     */
    public static function get_by_id(int $id): ?role {
        global $DB;

        $record = $DB->get_record('roles', ['id' => $id]);

        if (!$record) {
            return null;
        }

        return new self($record);
    }

    /**
     * Get role by shortname
     *
     * @param string $shortname
     * @return role|null
     */
    public static function get_by_shortname(string $shortname): ?role {
        global $DB;

        $record = $DB->get_record('roles', ['shortname' => $shortname]);

        if (!$record) {
            return null;
        }

        return new self($record);
    }

    /**
     * Get all roles
     *
     * @return array
     */
    public static function get_all(): array {
        global $DB;

        $records = $DB->get_records('roles', null, 'sortorder ASC');

        $roles = [];
        foreach ($records as $record) {
            $roles[] = new self($record);
        }

        return $roles;
    }

    /**
     * Create new role
     *
     * @param string $shortname
     * @param string $name
     * @param string $description
     * @param int $archetype
     * @return role
     */
    public static function create(string $shortname, string $name, string $description = '', int $archetype = 0): role {
        global $DB;

        // Validate shortname
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $shortname)) {
            throw new \coding_exception('Invalid role shortname');
        }

        // Check if exists
        if ($DB->record_exists('roles', ['shortname' => $shortname])) {
            throw new \coding_exception('Role already exists: ' . $shortname);
        }

        // Get next sortorder
        $maxsort = $DB->get_field_sql('SELECT MAX(sortorder) FROM {roles}');
        $sortorder = ($maxsort ?? 0) + 1;

        // Create role
        $record = new \stdClass();
        $record->shortname = $shortname;
        $record->name = $name;
        $record->description = $description;
        $record->archetype = $archetype;
        $record->sortorder = $sortorder;

        $id = $DB->insert_record('roles', $record);
        $record->id = $id;

        return new self($record);
    }

    /**
     * Update role
     *
     * @param string|null $name
     * @param string|null $description
     * @return bool
     */
    public function update(?string $name = null, ?string $description = null): bool {
        global $DB;

        $record = new \stdClass();
        $record->id = $this->id;

        if ($name !== null) {
            $record->name = $name;
            $this->name = $name;
        }

        if ($description !== null) {
            $record->description = $description;
            $this->description = $description;
        }

        return $DB->update_record('roles', $record);
    }

    /**
     * Delete role
     *
     * @return bool
     */
    public function delete(): bool {
        global $DB;

        // Delete role assignments
        $DB->delete_records('role_assignments', ['roleid' => $this->id]);

        // Delete role capabilities
        $DB->delete_records('role_capabilities', ['roleid' => $this->id]);

        // Delete role
        return $DB->delete_records('roles', ['id' => $this->id]);
    }

    /**
     * Assign capability to role
     *
     * @param string $capability
     * @param int $permission
     * @param context|null $context
     * @return int
     */
    public function assign_capability(string $capability, int $permission, ?context $context = null): int {
        global $DB;

        if ($context === null) {
            $context = context::system();
        }

        // Check if exists
        $existing = $DB->get_record('role_capabilities', [
            'roleid' => $this->id,
            'capability' => $capability,
            'contextid' => $context->id
        ]);

        if ($existing) {
            // Update
            $existing->permission = $permission;
            $existing->timemodified = time();
            $DB->update_record('role_capabilities', $existing);
            access::clear_all_cache();
            return $existing->id;
        }

        // Create
        $record = new \stdClass();
        $record->roleid = $this->id;
        $record->capability = $capability;
        $record->permission = $permission;
        $record->contextid = $context->id;
        $record->timemodified = time();

        $id = $DB->insert_record('role_capabilities', $record);

        // Clear cache
        access::clear_all_cache();

        return $id;
    }

    /**
     * Remove capability from role
     *
     * @param string $capability
     * @param context|null $context
     * @return bool
     */
    public function remove_capability(string $capability, ?context $context = null): bool {
        global $DB;

        if ($context === null) {
            $context = context::system();
        }

        $result = $DB->delete_records('role_capabilities', [
            'roleid' => $this->id,
            'capability' => $capability,
            'contextid' => $context->id
        ]);

        // Clear cache
        access::clear_all_cache();

        return $result;
    }

    /**
     * Get capabilities for this role
     *
     * @param context|null $context
     * @return array
     */
    public function get_capabilities(?context $context = null): array {
        global $DB;

        if ($context === null) {
            $context = context::system();
        }

        $records = $DB->get_records('role_capabilities', [
            'roleid' => $this->id,
            'contextid' => $context->id
        ]);

        $capabilities = [];
        foreach ($records as $record) {
            $capabilities[$record->capability] = (int)$record->permission;
        }

        return $capabilities;
    }

    /**
     * Get users assigned to this role
     *
     * @param context|null $context
     * @return array
     */
    public function get_users(?context $context = null): array {
        global $DB;

        if ($context === null) {
            $context = context::system();
        }

        $sql = "SELECT u.*
                FROM {users} u
                JOIN {role_assignments} ra ON ra.userid = u.id
                WHERE ra.roleid = ?
                  AND ra.contextid = ?
                ORDER BY u.lastname, u.firstname";

        return $DB->get_records_sql($sql, [$this->id, $context->id]);
    }
}
