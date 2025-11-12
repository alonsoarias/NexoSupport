<?php

declare(strict_types=1);

/**
 * Search Manager - Advanced Search Functionality (FASE 8)
 *
 * Gestiona búsquedas globales y filtradas en el sistema
 * Soporta: usuarios, tickets, knowledge base, archivos
 * Incluye: relevancia, paginación, filtros avanzados
 *
 * @package ISER\Core\Search
 * @author ISER Development Team
 * @since FASE 8
 */

namespace ISER\Core\Search;

use ISER\Core\Database\Database;

class SearchManager
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * Minimum query length
     */
    private const MIN_QUERY_LENGTH = 3;

    /**
     * Results per page
     */
    private const PER_PAGE = 20;

    /**
     * Constructor
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Unified global search across all entities
     *
     * @param string $query Search query
     * @param array $filters Search filters (entity_type, date_from, date_to, status)
     * @param int $page Current page (1-based)
     * @return array Search results with metadata
     */
    public function search(string $query, array $filters = [], int $page = 1): array
    {
        // Validate query length
        if (strlen(trim($query)) < self::MIN_QUERY_LENGTH) {
            return [
                'success' => false,
                'message' => 'Query too short. Minimum 3 characters required.',
                'results' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => self::PER_PAGE,
                'pages' => 0,
            ];
        }

        $query = trim($query);
        $offset = ($page - 1) * self::PER_PAGE;
        $results = [];
        $total = 0;

        // Determine which entities to search
        $entityType = $filters['entity_type'] ?? 'all';

        try {
            if ($entityType === 'all' || $entityType === 'users') {
                $userResults = $this->searchUsers($query, $filters);
                $results = array_merge($results, $userResults);
            }

            if ($entityType === 'all' || $entityType === 'tickets') {
                $ticketResults = $this->searchTickets($query, $filters);
                $results = array_merge($results, $ticketResults);
            }

            if ($entityType === 'all' || $entityType === 'knowledge_base') {
                $kbResults = $this->searchKnowledgeBase($query, $filters);
                $results = array_merge($results, $kbResults);
            }

            if ($entityType === 'all' || $entityType === 'files') {
                $fileResults = $this->searchFiles($query, $filters);
                $results = array_merge($results, $fileResults);
            }

            // Sort by relevance score (descending)
            usort($results, function($a, $b) {
                return $b['relevance_score'] <=> $a['relevance_score'];
            });

            $total = count($results);

            // Apply pagination
            $paginatedResults = array_slice($results, $offset, self::PER_PAGE);

            return [
                'success' => true,
                'query' => $query,
                'results' => $paginatedResults,
                'total' => $total,
                'page' => $page,
                'per_page' => self::PER_PAGE,
                'pages' => (int)ceil($total / self::PER_PAGE),
                'grouped' => $this->groupResultsByType($paginatedResults),
            ];
        } catch (\Exception $e) {
            error_log('Search Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Search error occurred: ' . $e->getMessage(),
                'results' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => self::PER_PAGE,
                'pages' => 0,
            ];
        }
    }

    /**
     * Search users by username, email, first_name, or last_name
     *
     * @param string $query Search query
     * @param array $filters Additional filters
     * @return array Search results
     */
    public function searchUsers(string $query, array $filters = []): array
    {
        try {
            $searchTerm = '%' . $this->escapeSearchTerm($query) . '%';

            $sql = "SELECT
                        id,
                        username,
                        email,
                        first_name,
                        last_name,
                        status,
                        created_at,
                        'users' as entity_type,
                        CASE
                            WHEN username LIKE :exact_username THEN 100
                            WHEN email LIKE :exact_email THEN 90
                            WHEN CONCAT(first_name, ' ', last_name) LIKE :exact_fullname THEN 80
                            WHEN first_name LIKE :search THEN 70
                            WHEN last_name LIKE :search THEN 70
                            WHEN email LIKE :search THEN 60
                            WHEN username LIKE :search THEN 50
                            ELSE 10
                        END as relevance_score
                    FROM {$this->db->table('users')}
                    WHERE deleted_at IS NULL
                    AND (
                        username LIKE :search
                        OR email LIKE :search
                        OR first_name LIKE :search
                        OR last_name LIKE :search
                    )";

            $params = [
                ':search' => $searchTerm,
                ':exact_username' => $query,
                ':exact_email' => $query,
                ':exact_fullname' => $query . '%',
            ];

            // Apply status filter if provided
            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            $sql .= " ORDER BY relevance_score DESC LIMIT 100";

            $results = $this->db->getConnection()->fetchAll($sql, $params);

            return array_map(function($user) {
                return [
                    'id' => $user['id'],
                    'entity_type' => 'users',
                    'title' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['username'],
                    'subtitle' => $user['email'],
                    'description' => $user['username'],
                    'status' => $user['status'] ?? 'active',
                    'created_at' => $user['created_at'],
                    'url' => '/profile/view/' . $user['id'],
                    'icon' => 'person',
                    'badge' => ucfirst($user['status'] ?? 'active'),
                    'relevance_score' => (int)$user['relevance_score'],
                ];
            }, $results);
        } catch (\Exception $e) {
            error_log('User Search Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search tickets
     *
     * @param string $query Search query
     * @param array $filters Additional filters
     * @return array Search results
     */
    public function searchTickets(string $query, array $filters = []): array
    {
        try {
            $searchTerm = '%' . $this->escapeSearchTerm($query) . '%';

            $sql = "SELECT
                        id,
                        title,
                        description,
                        ticket_number,
                        status,
                        priority,
                        created_at,
                        'tickets' as entity_type,
                        CASE
                            WHEN title LIKE :exact_title THEN 100
                            WHEN ticket_number = :exact_number THEN 95
                            WHEN title LIKE :search THEN 80
                            WHEN description LIKE :search THEN 60
                            WHEN ticket_number LIKE :search THEN 50
                            ELSE 10
                        END as relevance_score
                    FROM {$this->db->table('tickets')}
                    WHERE (
                        title LIKE :search
                        OR description LIKE :search
                        OR ticket_number LIKE :search
                    )";

            $params = [
                ':search' => $searchTerm,
                ':exact_title' => $query,
                ':exact_number' => $query,
            ];

            // Apply status filter if provided
            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            // Apply date range filter if provided
            if (isset($filters['date_from'])) {
                $sql .= " AND created_at >= :date_from";
                $params[':date_from'] = strtotime($filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $sql .= " AND created_at <= :date_to";
                $params[':date_to'] = strtotime($filters['date_to'] . ' 23:59:59');
            }

            $sql .= " ORDER BY relevance_score DESC LIMIT 100";

            $results = $this->db->getConnection()->fetchAll($sql, $params);

            return array_map(function($ticket) {
                return [
                    'id' => $ticket['id'],
                    'entity_type' => 'tickets',
                    'title' => $ticket['title'],
                    'subtitle' => '#' . $ticket['ticket_number'],
                    'description' => substr($ticket['description'] ?? '', 0, 100) . '...',
                    'status' => $ticket['status'] ?? 'open',
                    'priority' => $ticket['priority'] ?? 'normal',
                    'created_at' => $ticket['created_at'],
                    'url' => '/tickets/' . $ticket['id'],
                    'icon' => 'ticket',
                    'badge' => ucfirst($ticket['priority'] ?? 'normal'),
                    'relevance_score' => (int)$ticket['relevance_score'],
                ];
            }, $results);
        } catch (\Exception $e) {
            error_log('Ticket Search Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search in knowledge base
     *
     * @param string $query Search query
     * @param array $filters Additional filters
     * @return array Search results
     */
    public function searchKnowledgeBase(string $query, array $filters = []): array
    {
        try {
            $searchTerm = '%' . $this->escapeSearchTerm($query) . '%';

            $sql = "SELECT
                        id,
                        title,
                        content,
                        category,
                        views,
                        created_at,
                        'knowledge_base' as entity_type,
                        CASE
                            WHEN title LIKE :exact_title THEN 100
                            WHEN title LIKE :search THEN 90
                            WHEN content LIKE :search THEN 70
                            WHEN category LIKE :search THEN 50
                            ELSE 10
                        END as relevance_score
                    FROM {$this->db->table('knowledge_base')}
                    WHERE (
                        title LIKE :search
                        OR content LIKE :search
                        OR category LIKE :search
                    )";

            $params = [
                ':search' => $searchTerm,
                ':exact_title' => $query,
            ];

            // Apply category filter if provided
            if (isset($filters['category'])) {
                $sql .= " AND category = :category";
                $params[':category'] = $filters['category'];
            }

            // Apply date range filter if provided
            if (isset($filters['date_from'])) {
                $sql .= " AND created_at >= :date_from";
                $params[':date_from'] = strtotime($filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $sql .= " AND created_at <= :date_to";
                $params[':date_to'] = strtotime($filters['date_to'] . ' 23:59:59');
            }

            $sql .= " ORDER BY relevance_score DESC, views DESC LIMIT 100";

            $results = $this->db->getConnection()->fetchAll($sql, $params);

            return array_map(function($article) {
                return [
                    'id' => $article['id'],
                    'entity_type' => 'knowledge_base',
                    'title' => $article['title'],
                    'subtitle' => $article['category'],
                    'description' => substr($article['content'] ?? '', 0, 100) . '...',
                    'views' => $article['views'] ?? 0,
                    'created_at' => $article['created_at'],
                    'url' => '/knowledge-base/' . $article['id'],
                    'icon' => 'book',
                    'badge' => $article['views'] . ' views',
                    'relevance_score' => (int)$article['relevance_score'],
                ];
            }, $results);
        } catch (\Exception $e) {
            error_log('Knowledge Base Search Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search files
     *
     * @param string $query Search query
     * @param array $filters Additional filters
     * @return array Search results
     */
    public function searchFiles(string $query, array $filters = []): array
    {
        try {
            $searchTerm = '%' . $this->escapeSearchTerm($query) . '%';

            $sql = "SELECT
                        id,
                        filename,
                        original_filename,
                        mime_type,
                        size,
                        uploaded_by,
                        created_at,
                        'files' as entity_type,
                        CASE
                            WHEN filename LIKE :exact_filename THEN 100
                            WHEN original_filename LIKE :exact_original THEN 95
                            WHEN filename LIKE :search THEN 80
                            WHEN original_filename LIKE :search THEN 75
                            ELSE 10
                        END as relevance_score
                    FROM {$this->db->table('files')}
                    WHERE (
                        filename LIKE :search
                        OR original_filename LIKE :search
                    )";

            $params = [
                ':search' => $searchTerm,
                ':exact_filename' => $query,
                ':exact_original' => $query,
            ];

            // Apply mime type filter if provided
            if (isset($filters['mime_type'])) {
                $sql .= " AND mime_type = :mime_type";
                $params[':mime_type'] = $filters['mime_type'];
            }

            // Apply date range filter if provided
            if (isset($filters['date_from'])) {
                $sql .= " AND created_at >= :date_from";
                $params[':date_from'] = strtotime($filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $sql .= " AND created_at <= :date_to";
                $params[':date_to'] = strtotime($filters['date_to'] . ' 23:59:59');
            }

            $sql .= " ORDER BY relevance_score DESC LIMIT 100";

            $results = $this->db->getConnection()->fetchAll($sql, $params);

            return array_map(function($file) {
                return [
                    'id' => $file['id'],
                    'entity_type' => 'files',
                    'title' => $file['original_filename'],
                    'subtitle' => $this->formatFileSize((int)$file['size']),
                    'description' => $file['mime_type'],
                    'size' => $file['size'],
                    'mime_type' => $file['mime_type'],
                    'created_at' => $file['created_at'],
                    'url' => '/files/' . $file['id'],
                    'icon' => $this->getMimeTypeIcon($file['mime_type']),
                    'badge' => $this->getFileTypeLabel($file['mime_type']),
                    'relevance_score' => (int)$file['relevance_score'],
                ];
            }, $results);
        } catch (\Exception $e) {
            error_log('File Search Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Group search results by entity type
     *
     * @param array $results Search results
     * @return array Grouped results
     */
    private function groupResultsByType(array $results): array
    {
        $grouped = [];

        foreach ($results as $result) {
            $type = $result['entity_type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $result;
        }

        return $grouped;
    }

    /**
     * Escape search term for SQL LIKE queries
     *
     * @param string $term Search term
     * @return string Escaped term
     */
    private function escapeSearchTerm(string $term): string
    {
        // Remove SQL special characters to prevent injection
        return addslashes(preg_replace('/[%_\\]/', '', $term));
    }

    /**
     * Format file size for display
     *
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get MIME type icon
     *
     * @param string $mimeType MIME type
     * @return string Icon class
     */
    private function getMimeTypeIcon(string $mimeType): string
    {
        return match(true) {
            strpos($mimeType, 'image') !== false => 'image',
            strpos($mimeType, 'video') !== false => 'film',
            strpos($mimeType, 'audio') !== false => 'music',
            strpos($mimeType, 'pdf') !== false => 'file-pdf',
            strpos($mimeType, 'word') !== false => 'file-word',
            strpos($mimeType, 'sheet') !== false => 'file-excel',
            strpos($mimeType, 'presentation') !== false => 'file-powerpoint',
            strpos($mimeType, 'zip') !== false => 'file-archive',
            strpos($mimeType, 'text') !== false => 'file-text',
            default => 'file',
        };
    }

    /**
     * Get file type label from MIME type
     *
     * @param string $mimeType MIME type
     * @return string File type label
     */
    private function getFileTypeLabel(string $mimeType): string
    {
        return match(true) {
            strpos($mimeType, 'image') !== false => 'Image',
            strpos($mimeType, 'video') !== false => 'Video',
            strpos($mimeType, 'audio') !== false => 'Audio',
            strpos($mimeType, 'pdf') !== false => 'PDF',
            strpos($mimeType, 'word') !== false => 'Document',
            strpos($mimeType, 'sheet') !== false => 'Spreadsheet',
            strpos($mimeType, 'presentation') !== false => 'Presentation',
            strpos($mimeType, 'zip') !== false => 'Archive',
            strpos($mimeType, 'text') !== false => 'Text',
            default => 'File',
        };
    }

    /**
     * Get search statistics
     *
     * @return array Search statistics
     */
    public function getSearchStatistics(): array
    {
        try {
            $userCount = $this->db->getConnection()->fetchOne(
                "SELECT COUNT(*) as count FROM {$this->db->table('users')} WHERE deleted_at IS NULL"
            );
            $ticketCount = $this->db->getConnection()->fetchOne(
                "SELECT COUNT(*) as count FROM {$this->db->table('tickets')}"
            );
            $kbCount = $this->db->getConnection()->fetchOne(
                "SELECT COUNT(*) as count FROM {$this->db->table('knowledge_base')}"
            );
            $fileCount = $this->db->getConnection()->fetchOne(
                "SELECT COUNT(*) as count FROM {$this->db->table('files')}"
            );

            return [
                'users' => (int)($userCount['count'] ?? 0),
                'tickets' => (int)($ticketCount['count'] ?? 0),
                'knowledge_base' => (int)($kbCount['count'] ?? 0),
                'files' => (int)($fileCount['count'] ?? 0),
                'total' => ((int)($userCount['count'] ?? 0)) + ((int)($ticketCount['count'] ?? 0)) +
                           ((int)($kbCount['count'] ?? 0)) + ((int)($fileCount['count'] ?? 0)),
            ];
        } catch (\Exception $e) {
            error_log('Search Statistics Error: ' . $e->getMessage());
            return [
                'users' => 0,
                'tickets' => 0,
                'knowledge_base' => 0,
                'files' => 0,
                'total' => 0,
            ];
        }
    }
}
