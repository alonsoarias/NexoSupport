<?php

declare(strict_types=1);

namespace ISER\Core\Database;

/**
 * BaseRepository - Abstract base class for all repositories/managers
 *
 * Provides common CRUD operations to reduce code duplication across
 * manager classes. Child classes must define $table property.
 *
 * FEATURES:
 * - Standard CRUD operations (create, findById, update, delete)
 * - Pagination support (getAll with limit/offset)
 * - Filtering support
 * - Soft delete support (optional)
 * - Automatic timestamps (created_at, updated_at)
 * - Bulk operations
 *
 * USAGE:
 * ```php
 * class UserManager extends BaseRepository
 * {
 *     protected string $table = 'users';
 *     protected bool $useSoftDeletes = true;
 *
 *     // Only implement user-specific methods here
 *     public function getUserByEmail(string $email): ?array
 *     {
 *         return $this->findByField('email', $email);
 *     }
 * }
 * ```
 *
 * @package ISER\Core\Database
 * @author ISER Development
 */
abstract class BaseRepository
{
    protected Database $db;

    /**
     * Table name (must be set by child class)
     * @var string
     */
    protected string $table;

    /**
     * Primary key column name
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Enable soft deletes (deleted_at column)
     * @var bool
     */
    protected bool $useSoftDeletes = false;

    /**
     * Enable automatic timestamps (created_at, updated_at)
     * @var bool
     */
    protected bool $useTimestamps = true;

    /**
     * Default ORDER BY clause for getAll()
     * @var string
     */
    protected string $defaultOrderBy = 'id DESC';

    /**
     * Constructor
     *
     * @param Database $db Database instance
     */
    public function __construct(Database $db)
    {
        $this->db = $db;

        // Validate that child class set table name
        if (!isset($this->table)) {
            throw new \RuntimeException(
                'Property $table must be defined in ' . static::class
            );
        }
    }

    /**
     * Find a record by ID
     *
     * @param int $id Record ID
     * @return array|null Record data or null if not found
     */
    public function findById(int $id): ?array
    {
        $result = $this->db->selectOne($this->table, [$this->primaryKey => $id]);
        return $result !== false ? $result : null;
    }

    /**
     * Find a record by a specific field
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @return array|null Record data or null if not found
     */
    public function findByField(string $field, $value): ?array
    {
        $result = $this->db->selectOne($this->table, [$field => $value]);
        return $result !== false ? $result : null;
    }

    /**
     * Get all records with pagination and filtering
     *
     * @param int $limit Maximum number of records
     * @param int $offset Offset for pagination
     * @param array $filters Associative array of filters
     * @return array Array of records
     */
    public function getAll(int $limit = 100, int $offset = 0, array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->db->table($this->table)} WHERE 1=1";
        $params = [];

        // Apply soft delete filter
        if ($this->useSoftDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }

        // Apply custom filters
        $sql = $this->applyFilters($sql, $filters, $params);

        // Add ordering and pagination
        $sql .= " ORDER BY {$this->defaultOrderBy} LIMIT :limit OFFSET :offset";

        $stmt = $this->db->getConnection()->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Count records with optional filtering
     *
     * @param array $filters Associative array of filters
     * @return int Total count
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->db->table($this->table)} WHERE 1=1";
        $params = [];

        // Apply soft delete filter
        if ($this->useSoftDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }

        // Apply custom filters
        $sql = $this->applyFilters($sql, $filters, $params);

        $result = $this->db->getConnection()->fetchOne($sql, $params);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Create a new record
     *
     * @param array $data Record data
     * @return int|false Inserted ID or false on failure
     */
    public function create(array $data): int|false
    {
        // Add timestamps if enabled
        if ($this->useTimestamps) {
            $data = $this->addTimestamps($data, false);
        }

        return $this->db->insert($this->table, $data);
    }

    /**
     * Update a record
     *
     * @param int $id Record ID
     * @param array $data Data to update
     * @return bool True if updated, false otherwise
     */
    public function update(int $id, array $data): bool
    {
        // Add updated_at timestamp if enabled
        if ($this->useTimestamps) {
            $data = $this->addTimestamps($data, true);
        }

        $rowsAffected = $this->db->update(
            $this->table,
            $data,
            [$this->primaryKey => $id]
        );

        return $rowsAffected > 0;
    }

    /**
     * Delete a record (hard delete or soft delete)
     *
     * @param int $id Record ID
     * @return bool True if deleted, false otherwise
     */
    public function delete(int $id): bool
    {
        if ($this->useSoftDeletes) {
            return $this->softDelete($id);
        }

        $rowsAffected = $this->db->delete($this->table, [$this->primaryKey => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Soft delete a record (mark as deleted)
     *
     * @param int $id Record ID
     * @return bool True if soft deleted, false otherwise
     */
    public function softDelete(int $id): bool
    {
        if (!$this->useSoftDeletes) {
            throw new \RuntimeException('Soft deletes are not enabled for ' . static::class);
        }

        return $this->update($id, ['deleted_at' => time()]);
    }

    /**
     * Restore a soft-deleted record
     *
     * @param int $id Record ID
     * @return bool True if restored, false otherwise
     */
    public function restore(int $id): bool
    {
        if (!$this->useSoftDeletes) {
            throw new \RuntimeException('Soft deletes are not enabled for ' . static::class);
        }

        return $this->update($id, ['deleted_at' => null]);
    }

    /**
     * Check if a record exists
     *
     * @param int $id Record ID
     * @return bool True if exists, false otherwise
     */
    public function exists(int $id): bool
    {
        return $this->findById($id) !== null;
    }

    /**
     * Bulk create records
     *
     * @param array $records Array of record data arrays
     * @return int Number of records created
     */
    public function bulkCreate(array $records): int
    {
        $count = 0;
        foreach ($records as $record) {
            if ($this->create($record) !== false) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Bulk update records
     *
     * @param array $ids Array of record IDs
     * @param array $data Data to update
     * @return int Number of records updated
     */
    public function bulkUpdate(array $ids, array $data): int
    {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->update((int)$id, $data)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Bulk delete records
     *
     * @param array $ids Array of record IDs
     * @return int Number of records deleted
     */
    public function bulkDelete(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->delete((int)$id)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Apply filters to SQL query
     *
     * Override this method in child classes to add custom filtering logic
     *
     * @param string $sql Base SQL query
     * @param array $filters Filters to apply
     * @param array $params Parameters array (passed by reference)
     * @return string Modified SQL query
     */
    protected function applyFilters(string $sql, array $filters, array &$params): string
    {
        // Default implementation: simple equality filters
        foreach ($filters as $field => $value) {
            $sql .= " AND {$field} = :{$field}";
            $params[":{$field}"] = $value;
        }

        return $sql;
    }

    /**
     * Add timestamps to data array
     *
     * @param array $data Data array
     * @param bool $isUpdate True if updating, false if creating
     * @return array Data array with timestamps
     */
    protected function addTimestamps(array $data, bool $isUpdate = false): array
    {
        $now = time();

        if (!$isUpdate && !isset($data['created_at'])) {
            $data['created_at'] = $now;
        }

        if (!isset($data['updated_at'])) {
            $data['updated_at'] = $now;
        }

        return $data;
    }

    /**
     * Get the table name
     *
     * @return string Table name
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the Database instance
     *
     * @return Database Database instance
     */
    protected function getDb(): Database
    {
        return $this->db;
    }
}
