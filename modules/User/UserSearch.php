<?php
/**
 * ISER - User Search and Filtering
 * @package ISER\Modules\User
 */

namespace ISER\User;

use ISER\Core\Database\Database;

class UserSearch
{
    private Database $db;
    private array $filters = [];
    private string $orderBy = 'timecreated';
    private string $orderDirection = 'DESC';
    private int $limit = 50;
    private int $offset = 0;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Set search keyword (searches in username, email, firstname, lastname)
     */
    public function setKeyword(string $keyword): self
    {
        $this->filters['keyword'] = $keyword;
        return $this;
    }

    /**
     * Filter by user status
     */
    public function filterByStatus(int $status): self
    {
        $this->filters['status'] = $status;
        return $this;
    }

    /**
     * Filter by suspended status
     */
    public function filterBySuspended(bool $suspended): self
    {
        $this->filters['suspended'] = $suspended ? 1 : 0;
        return $this;
    }

    /**
     * Filter by deleted status
     */
    public function filterByDeleted(bool $deleted): self
    {
        $this->filters['deleted'] = $deleted ? 1 : 0;
        return $this;
    }

    /**
     * Filter by email domain
     */
    public function filterByEmailDomain(string $domain): self
    {
        $this->filters['email_domain'] = $domain;
        return $this;
    }

    /**
     * Filter by creation date range
     */
    public function filterByCreationDate(int $from, ?int $to = null): self
    {
        $this->filters['created_from'] = $from;
        if ($to !== null) {
            $this->filters['created_to'] = $to;
        }
        return $this;
    }

    /**
     * Filter by last login date range
     */
    public function filterByLastLogin(int $from, ?int $to = null): self
    {
        $this->filters['lastlogin_from'] = $from;
        if ($to !== null) {
            $this->filters['lastlogin_to'] = $to;
        }
        return $this;
    }

    /**
     * Filter by institution (from profile)
     */
    public function filterByInstitution(string $institution): self
    {
        $this->filters['institution'] = $institution;
        return $this;
    }

    /**
     * Set ordering
     */
    public function orderBy(string $field, string $direction = 'DESC'): self
    {
        $allowedFields = ['id', 'username', 'email', 'firstname', 'lastname',
                          'timecreated', 'timemodified', 'lastlogin', 'status'];

        if (in_array($field, $allowedFields)) {
            $this->orderBy = $field;
        }

        $this->orderDirection = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        return $this;
    }

    /**
     * Set pagination
     */
    public function setPagination(int $limit, int $offset = 0): self
    {
        $this->limit = max(1, min($limit, 1000)); // Max 1000 results
        $this->offset = max(0, $offset);
        return $this;
    }

    /**
     * Build the SQL query based on filters
     */
    private function buildQuery(bool $countOnly = false): array
    {
        $params = [];

        if ($countOnly) {
            $sql = "SELECT COUNT(DISTINCT u.id) as count FROM {$this->db->table('users')} u";
        } else {
            $sql = "SELECT DISTINCT u.* FROM {$this->db->table('users')} u";
        }

        // Join with profiles if filtering by institution
        if (isset($this->filters['institution'])) {
            $sql .= " LEFT JOIN {$this->db->table('user_profiles')} p ON u.id = p.userid";
        }

        $sql .= " WHERE 1=1";

        // Apply filters
        if (isset($this->filters['keyword'])) {
            $sql .= " AND (u.username LIKE :keyword OR u.email LIKE :keyword
                     OR u.firstname LIKE :keyword OR u.lastname LIKE :keyword)";
            $params[':keyword'] = '%' . $this->filters['keyword'] . '%';
        }

        if (isset($this->filters['status'])) {
            $sql .= " AND u.status = :status";
            $params[':status'] = $this->filters['status'];
        }

        if (isset($this->filters['suspended'])) {
            $sql .= " AND u.suspended = :suspended";
            $params[':suspended'] = $this->filters['suspended'];
        }

        if (isset($this->filters['deleted'])) {
            $sql .= " AND u.deleted = :deleted";
            $params[':deleted'] = $this->filters['deleted'];
        } else {
            // By default, exclude deleted users
            $sql .= " AND u.deleted = 0";
        }

        if (isset($this->filters['email_domain'])) {
            $sql .= " AND u.email LIKE :email_domain";
            $params[':email_domain'] = '%@' . $this->filters['email_domain'];
        }

        if (isset($this->filters['created_from'])) {
            $sql .= " AND u.timecreated >= :created_from";
            $params[':created_from'] = $this->filters['created_from'];
        }

        if (isset($this->filters['created_to'])) {
            $sql .= " AND u.timecreated <= :created_to";
            $params[':created_to'] = $this->filters['created_to'];
        }

        if (isset($this->filters['lastlogin_from'])) {
            $sql .= " AND u.lastlogin >= :lastlogin_from";
            $params[':lastlogin_from'] = $this->filters['lastlogin_from'];
        }

        if (isset($this->filters['lastlogin_to'])) {
            $sql .= " AND u.lastlogin <= :lastlogin_to";
            $params[':lastlogin_to'] = $this->filters['lastlogin_to'];
        }

        if (isset($this->filters['institution'])) {
            $sql .= " AND p.institution LIKE :institution";
            $params[':institution'] = '%' . $this->filters['institution'] . '%';
        }

        // Add ordering and pagination for non-count queries
        if (!$countOnly) {
            $sql .= " ORDER BY u.{$this->orderBy} {$this->orderDirection}";
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $this->limit;
            $params[':offset'] = $this->offset;
        }

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Execute search and return results
     */
    public function search(): array
    {
        $query = $this->buildQuery();
        return $this->db->getConnection()->fetchAll($query['sql'], $query['params']);
    }

    /**
     * Get total count of matching users
     */
    public function count(): int
    {
        $query = $this->buildQuery(true);
        $result = $this->db->getConnection()->fetchOne($query['sql'], $query['params']);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Search with pagination information
     */
    public function searchWithPagination(): array
    {
        $totalCount = $this->count();
        $results = $this->search();

        return [
            'users' => $results,
            'total' => $totalCount,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'hasMore' => ($this->offset + $this->limit) < $totalCount,
            'currentPage' => floor($this->offset / $this->limit) + 1,
            'totalPages' => ceil($totalCount / $this->limit),
        ];
    }

    /**
     * Reset all filters
     */
    public function resetFilters(): self
    {
        $this->filters = [];
        return $this;
    }

    /**
     * Get active filters
     */
    public function getActiveFilters(): array
    {
        return $this->filters;
    }

    /**
     * Quick search by username or email
     */
    public function quickSearch(string $query, int $limit = 10): array
    {
        $sql = "SELECT id, username, email, firstname, lastname
                FROM {$this->db->table('users')}
                WHERE (username LIKE :query OR email LIKE :query)
                AND deleted = 0
                LIMIT :limit";

        return $this->db->getConnection()->fetchAll($sql, [
            ':query' => '%' . $query . '%',
            ':limit' => $limit
        ]);
    }
}
