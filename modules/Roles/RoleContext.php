<?php
/**
 * ISER Roles System - Context Manager
 *
 * Manages context hierarchies for granular permissions.
 * Contexts allow permissions to be scoped at different levels
 * (system, user, module, block, etc.)
 *
 * @package    ISER\Modules\Roles
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Modules\Roles;

use ISER\Core\Database\Database;

// Context level constants
if (!defined('CONTEXT_SYSTEM')) define('CONTEXT_SYSTEM', 10);
if (!defined('CONTEXT_USER')) define('CONTEXT_USER', 30);
if (!defined('CONTEXT_MODULE')) define('CONTEXT_MODULE', 70);
if (!defined('CONTEXT_BLOCK')) define('CONTEXT_BLOCK', 80);

class RoleContext
{
    private Database $db;
    private array $contextCache = [];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get system context (root context)
     *
     * @return array|false System context or false on failure
     */
    public function getSystemContext(): array|false
    {
        return $this->db->selectOne('context', ['contextlevel' => CONTEXT_SYSTEM]);
    }

    /**
     * Get context by ID
     *
     * @param int $contextId Context ID
     * @return array|false Context data or false if not found
     */
    public function getContext(int $contextId): array|false
    {
        // Check cache
        if (isset($this->contextCache[$contextId])) {
            return $this->contextCache[$contextId];
        }

        $context = $this->db->selectOne('context', ['id' => $contextId]);

        if ($context) {
            $this->contextCache[$contextId] = $context;
        }

        return $context;
    }

    /**
     * Get or create context
     *
     * @param int $contextLevel Context level (CONTEXT_SYSTEM, CONTEXT_USER, etc.)
     * @param int $instanceId Instance ID (user ID, module ID, etc.)
     * @param int|null $parentContextId Parent context ID (null for system context)
     * @return int|false Context ID or false on failure
     */
    public function getOrCreateContext(
        int $contextLevel,
        int $instanceId = 0,
        ?int $parentContextId = null
    ): int|false {
        // Check if context exists
        $existing = $this->db->selectOne('context', [
            'contextlevel' => $contextLevel,
            'instanceid' => $instanceId
        ]);

        if ($existing) {
            return (int)$existing['id'];
        }

        // Create new context
        $path = $this->buildContextPath($contextLevel, $parentContextId);
        $depth = substr_count($path, '/');

        $result = $this->db->insert('context', [
            'contextlevel' => $contextLevel,
            'instanceid' => $instanceId,
            'path' => $path,
            'depth' => $depth,
            'timecreated' => time()
        ]);

        return $result !== false ? (int)$this->db->getConnection()->lastInsertId() : false;
    }

    /**
     * Get context for user
     *
     * @param int $userId User ID
     * @return int|false Context ID or false on failure
     */
    public function getUserContext(int $userId): int|false
    {
        $context = $this->db->selectOne('context', [
            'contextlevel' => CONTEXT_USER,
            'instanceid' => $userId
        ]);

        if ($context) {
            return (int)$context['id'];
        }

        // Create user context
        $systemContext = $this->getSystemContext();
        if (!$systemContext) {
            return false;
        }

        return $this->getOrCreateContext(CONTEXT_USER, $userId, (int)$systemContext['id']);
    }

    /**
     * Get context for module
     *
     * @param int $moduleId Module ID
     * @return int|false Context ID or false on failure
     */
    public function getModuleContext(int $moduleId): int|false
    {
        $context = $this->db->selectOne('context', [
            'contextlevel' => CONTEXT_MODULE,
            'instanceid' => $moduleId
        ]);

        if ($context) {
            return (int)$context['id'];
        }

        // Create module context
        $systemContext = $this->getSystemContext();
        if (!$systemContext) {
            return false;
        }

        return $this->getOrCreateContext(CONTEXT_MODULE, $moduleId, (int)$systemContext['id']);
    }

    /**
     * Get parent context
     *
     * @param int $contextId Context ID
     * @return array|false Parent context or false if none
     */
    public function getParentContext(int $contextId): array|false
    {
        $context = $this->getContext($contextId);
        if (!$context) {
            return false;
        }

        // Parse path to get parent
        $path = $context['path'];
        $pathParts = array_filter(explode('/', $path));

        if (count($pathParts) <= 1) {
            return false; // System context has no parent
        }

        // Get parent ID from path
        $parentId = (int)$pathParts[count($pathParts) - 2];
        return $this->getContext($parentId);
    }

    /**
     * Get context path (all contexts from root to current)
     *
     * @param int $contextId Context ID
     * @return array Array of context IDs from root to current
     */
    public function getContextPath(int $contextId): array
    {
        $context = $this->getContext($contextId);
        if (!$context) {
            return [];
        }

        $path = $context['path'];
        $pathParts = array_filter(explode('/', $path));

        return array_map('intval', $pathParts);
    }

    /**
     * Get all child contexts
     *
     * @param int $contextId Parent context ID
     * @param bool $recursive Include all descendants (default: false)
     * @return array Array of child contexts
     */
    public function getChildContexts(int $contextId, bool $recursive = false): array
    {
        $context = $this->getContext($contextId);
        if (!$context) {
            return [];
        }

        if ($recursive) {
            // Get all descendants
            $sql = "SELECT * FROM {$this->db->table('context')}
                    WHERE path LIKE :path
                    AND id != :contextid
                    ORDER BY depth ASC";

            return $this->db->getConnection()->fetchAll($sql, [
                ':path' => $context['path'] . '%',
                ':contextid' => $contextId
            ]);
        } else {
            // Get direct children only
            $sql = "SELECT * FROM {$this->db->table('context')}
                    WHERE path LIKE :path
                    AND depth = :depth
                    ORDER BY id ASC";

            return $this->db->getConnection()->fetchAll($sql, [
                ':path' => $context['path'] . '/%',
                ':depth' => $context['depth'] + 1
            ]);
        }
    }

    /**
     * Check if context is ancestor of another context
     *
     * @param int $ancestorId Potential ancestor context ID
     * @param int $descendantId Potential descendant context ID
     * @return bool True if ancestor
     */
    public function isAncestor(int $ancestorId, int $descendantId): bool
    {
        $descendant = $this->getContext($descendantId);
        if (!$descendant) {
            return false;
        }

        return str_contains($descendant['path'], "/{$ancestorId}/");
    }

    /**
     * Get context level name
     *
     * @param int $contextLevel Context level constant
     * @return string Context level name
     */
    public function getContextLevelName(int $contextLevel): string
    {
        return match ($contextLevel) {
            CONTEXT_SYSTEM => 'System',
            CONTEXT_USER => 'User',
            CONTEXT_MODULE => 'Module',
            CONTEXT_BLOCK => 'Block',
            default => 'Unknown'
        };
    }

    /**
     * Delete context and all descendants
     *
     * @param int $contextId Context ID
     * @return bool True on success
     */
    public function deleteContext(int $contextId): bool
    {
        $context = $this->getContext($contextId);
        if (!$context) {
            return false;
        }

        // Don't allow deleting system context
        if ($context['contextlevel'] == CONTEXT_SYSTEM) {
            return false;
        }

        // Delete all descendants
        $sql = "DELETE FROM {$this->db->table('context')}
                WHERE path LIKE :path OR id = :contextid";

        $result = $this->db->getConnection()->execute($sql, [
            ':path' => $context['path'] . '/%',
            ':contextid' => $contextId
        ]);

        // Clear cache
        unset($this->contextCache[$contextId]);

        return $result > 0;
    }

    /**
     * Build context path
     *
     * @param int $contextLevel Context level
     * @param int|null $parentContextId Parent context ID
     * @return string Context path
     */
    private function buildContextPath(int $contextLevel, ?int $parentContextId = null): string
    {
        if ($contextLevel === CONTEXT_SYSTEM || $parentContextId === null) {
            // System context is root
            return '/1';
        }

        $parent = $this->getContext($parentContextId);
        if (!$parent) {
            return '/1'; // Fallback to system context
        }

        // This will be updated after insert with actual ID
        return $parent['path'] . '/0';
    }

    /**
     * Update context path after creation
     *
     * @param int $contextId Context ID
     * @param int $parentContextId Parent context ID
     * @return bool True on success
     */
    public function updateContextPath(int $contextId, int $parentContextId): bool
    {
        $parent = $this->getContext($parentContextId);
        if (!$parent) {
            return false;
        }

        $newPath = $parent['path'] . '/' . $contextId;
        $newDepth = $parent['depth'] + 1;

        return $this->db->update('context', [
            'path' => $newPath,
            'depth' => $newDepth
        ], ['id' => $contextId]) > 0;
    }

    /**
     * Get context statistics
     *
     * @return array Context statistics by level
     */
    public function getContextStats(): array
    {
        $sql = "SELECT contextlevel, COUNT(*) as count
                FROM {$this->db->table('context')}
                GROUP BY contextlevel
                ORDER BY contextlevel ASC";

        $stats = $this->db->getConnection()->fetchAll($sql);

        $result = [];
        foreach ($stats as $stat) {
            $levelName = $this->getContextLevelName((int)$stat['contextlevel']);
            $result[$levelName] = (int)$stat['count'];
        }

        return $result;
    }

    /**
     * Clear context cache
     */
    public function clearCache(): void
    {
        $this->contextCache = [];
    }
}
