<?php

/**
 * ISER - Dependency Resolver
 *
 * Resolves plugin dependencies, detects circular dependencies,
 * and determines installation order using topological sorting.
 *
 * @package    ISER\Core\Plugin
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Week 4 - Plugin System Completion
 */

namespace ISER\Core\Plugin;

use ISER\Core\Database\Database;
use ISER\Core\Utils\Logger;

/**
 * DependencyResolver Class
 *
 * Handles plugin dependency resolution including:
 * - Building dependency trees
 * - Topological sorting for install order
 * - Circular dependency detection
 * - Conflict resolution
 * - Version constraint validation
 */
class DependencyResolver
{
    /**
     * Database instance
     */
    private Database $db;

    /**
     * Cache of plugin manifests
     */
    private array $manifestCache = [];

    /**
     * Dependency graph (adjacency list)
     */
    private array $dependencyGraph = [];

    /**
     * Visited nodes for cycle detection
     */
    private array $visited = [];

    /**
     * Recursion stack for cycle detection
     */
    private array $recursionStack = [];

    /**
     * Constructor
     *
     * @param Database $db Database instance
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Resolve dependencies for a plugin
     *
     * Builds a complete dependency tree and returns all plugins
     * that need to be installed in the correct order.
     *
     * @param string $pluginSlug Target plugin slug
     * @param array $availablePlugins List of available plugins (optional)
     * @return array [
     *   'success' => bool,
     *   'dependencies' => array,  // Ordered list of slugs to install
     *   'errors' => array,         // Error messages
     *   'warnings' => array        // Warning messages
     * ]
     */
    public function resolveDependencies(string $pluginSlug, array $availablePlugins = []): array
    {
        $result = [
            'success' => true,
            'dependencies' => [],
            'errors' => [],
            'warnings' => []
        ];

        try {
            // Reset state
            $this->dependencyGraph = [];
            $this->visited = [];
            $this->recursionStack = [];

            // Build dependency graph
            $this->buildDependencyGraph($pluginSlug, $availablePlugins, $result);

            if (!$result['success']) {
                return $result;
            }

            // Detect circular dependencies
            if ($this->hasCircularDependencies()) {
                $result['success'] = false;
                $result['errors'][] = 'Circular dependency detected';
                Logger::error('Circular dependency detected', [
                    'plugin' => $pluginSlug,
                    'graph' => $this->dependencyGraph
                ]);
                return $result;
            }

            // Get installation order via topological sort
            $result['dependencies'] = $this->topologicalSort();

            Logger::info('Dependencies resolved', [
                'plugin' => $pluginSlug,
                'dependencies' => $result['dependencies']
            ]);

        } catch (\Exception $e) {
            $result['success'] = false;
            $result['errors'][] = 'Failed to resolve dependencies: ' . $e->getMessage();
            Logger::error('Dependency resolution failed', [
                'plugin' => $pluginSlug,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Build dependency graph recursively
     *
     * @param string $pluginSlug Current plugin
     * @param array $availablePlugins Available plugins
     * @param array &$result Result array (modified by reference)
     * @return void
     */
    private function buildDependencyGraph(string $pluginSlug, array $availablePlugins, array &$result): void
    {
        // Skip if already processed
        if (isset($this->dependencyGraph[$pluginSlug])) {
            return;
        }

        // Initialize node
        $this->dependencyGraph[$pluginSlug] = [];

        // Check if plugin is already installed
        $installedPlugin = $this->getInstalledPlugin($pluginSlug);
        if ($installedPlugin) {
            Logger::system('Plugin already installed, skipping', ['slug' => $pluginSlug]);
            return;
        }

        // Get plugin manifest
        $manifest = $this->getPluginManifest($pluginSlug, $availablePlugins);
        if (!$manifest) {
            $result['success'] = false;
            $result['errors'][] = "Plugin not found: {$pluginSlug}";
            return;
        }

        // Process dependencies
        $dependencies = $this->extractDependencies($manifest);

        foreach ($dependencies as $depSlug => $depInfo) {
            // Add edge to graph
            $this->dependencyGraph[$pluginSlug][] = $depSlug;

            // Check if dependency is already installed
            $depInstalled = $this->getInstalledPlugin($depSlug);

            if ($depInstalled) {
                // Check version compatibility
                if (!$this->isVersionCompatible($depInstalled['version'], $depInfo['version'])) {
                    $result['success'] = false;
                    $result['errors'][] = sprintf(
                        'Incompatible version: %s requires %s %s, but %s is installed',
                        $pluginSlug,
                        $depSlug,
                        $depInfo['version'],
                        $depInstalled['version']
                    );
                }
                continue;
            }

            // Recursively resolve dependency
            $this->buildDependencyGraph($depSlug, $availablePlugins, $result);

            if (!$result['success']) {
                return;
            }
        }

        // Check conflicts
        $conflicts = $this->checkConflicts($pluginSlug, $manifest);
        if (!empty($conflicts)) {
            $result['success'] = false;
            $result['errors'] = array_merge($result['errors'], $conflicts);
        }

        // Check recommendations
        $recommendations = $this->extractRecommendations($manifest);
        if (!empty($recommendations)) {
            foreach ($recommendations as $recSlug) {
                $recInstalled = $this->getInstalledPlugin($recSlug);
                if (!$recInstalled) {
                    $result['warnings'][] = "Recommended plugin not installed: {$recSlug}";
                }
            }
        }
    }

    /**
     * Perform topological sort on dependency graph
     *
     * Uses Kahn's algorithm for topological sorting.
     *
     * @return array Ordered list of plugin slugs
     */
    private function topologicalSort(): array
    {
        $sorted = [];
        $inDegree = [];

        // Calculate in-degree for each node
        foreach ($this->dependencyGraph as $node => $edges) {
            if (!isset($inDegree[$node])) {
                $inDegree[$node] = 0;
            }
            foreach ($edges as $dep) {
                if (!isset($inDegree[$dep])) {
                    $inDegree[$dep] = 0;
                }
                $inDegree[$dep]++;
            }
        }

        // Find all nodes with in-degree 0
        $queue = [];
        foreach ($inDegree as $node => $degree) {
            if ($degree === 0) {
                $queue[] = $node;
            }
        }

        // Process queue
        while (!empty($queue)) {
            $node = array_shift($queue);
            $sorted[] = $node;

            // Reduce in-degree for neighbors
            if (isset($this->dependencyGraph[$node])) {
                foreach ($this->dependencyGraph[$node] as $neighbor) {
                    $inDegree[$neighbor]--;
                    if ($inDegree[$neighbor] === 0) {
                        $queue[] = $neighbor;
                    }
                }
            }
        }

        // Reverse to get install order (dependencies first)
        return array_reverse($sorted);
    }

    /**
     * Detect circular dependencies using DFS
     *
     * @return bool True if circular dependency exists
     */
    private function hasCircularDependencies(): bool
    {
        $this->visited = [];
        $this->recursionStack = [];

        foreach ($this->dependencyGraph as $node => $edges) {
            if (!isset($this->visited[$node])) {
                if ($this->detectCycleDFS($node)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * DFS for cycle detection
     *
     * @param string $node Current node
     * @return bool True if cycle detected
     */
    private function detectCycleDFS(string $node): bool
    {
        $this->visited[$node] = true;
        $this->recursionStack[$node] = true;

        if (isset($this->dependencyGraph[$node])) {
            foreach ($this->dependencyGraph[$node] as $neighbor) {
                // Skip if neighbor not in graph (already installed)
                if (!isset($this->dependencyGraph[$neighbor])) {
                    continue;
                }

                if (!isset($this->visited[$neighbor])) {
                    if ($this->detectCycleDFS($neighbor)) {
                        return true;
                    }
                } elseif (isset($this->recursionStack[$neighbor]) && $this->recursionStack[$neighbor]) {
                    // Back edge found - cycle detected
                    return true;
                }
            }
        }

        $this->recursionStack[$node] = false;
        return false;
    }

    /**
     * Check for conflicts with installed plugins
     *
     * @param string $pluginSlug Plugin to check
     * @param array $manifest Plugin manifest
     * @return array List of conflict error messages
     */
    private function checkConflicts(string $pluginSlug, array $manifest): array
    {
        $conflicts = [];

        if (!empty($manifest['conflicts_with'])) {
            foreach ($manifest['conflicts_with'] as $conflictSlug) {
                $conflictPlugin = $this->getInstalledPlugin($conflictSlug);
                if ($conflictPlugin) {
                    $conflicts[] = sprintf(
                        'Plugin %s conflicts with installed plugin %s',
                        $pluginSlug,
                        $conflictSlug
                    );
                }
            }
        }

        return $conflicts;
    }

    /**
     * Extract dependencies from manifest
     *
     * @param array $manifest Plugin manifest
     * @return array Dependencies [slug => ['version' => constraint]]
     */
    private function extractDependencies(array $manifest): array
    {
        $dependencies = [];

        // Old format: depends_on (array of slugs)
        if (!empty($manifest['depends_on'])) {
            foreach ($manifest['depends_on'] as $depSlug) {
                $dependencies[$depSlug] = ['version' => '*'];
            }
        }

        // New format: requires.plugins (array of objects)
        if (!empty($manifest['requires']['plugins'])) {
            foreach ($manifest['requires']['plugins'] as $plugin) {
                $slug = $plugin['slug'] ?? null;
                $version = $plugin['version'] ?? '*';

                if ($slug) {
                    $dependencies[$slug] = ['version' => $version];
                }
            }
        }

        return $dependencies;
    }

    /**
     * Extract recommendations from manifest
     *
     * @param array $manifest Plugin manifest
     * @return array List of recommended plugin slugs
     */
    private function extractRecommendations(array $manifest): array
    {
        return $manifest['recommends'] ?? [];
    }

    /**
     * Get plugin manifest from database or available plugins
     *
     * @param string $pluginSlug Plugin slug
     * @param array $availablePlugins Available plugins
     * @return array|null Manifest or null if not found
     */
    private function getPluginManifest(string $pluginSlug, array $availablePlugins): ?array
    {
        // Check cache
        if (isset($this->manifestCache[$pluginSlug])) {
            return $this->manifestCache[$pluginSlug];
        }

        // Try to get from database (if installed)
        try {
            $plugin = $this->db->selectOne('plugins', ['slug' => $pluginSlug]);
            if ($plugin && !empty($plugin['manifest'])) {
                $manifest = json_decode($plugin['manifest'], true);
                if ($manifest) {
                    $this->manifestCache[$pluginSlug] = $manifest;
                    return $manifest;
                }
            }
        } catch (\Exception $e) {
            Logger::warning('Failed to get plugin from database', [
                'slug' => $pluginSlug,
                'error' => $e->getMessage()
            ]);
        }

        // Try to get from available plugins array
        foreach ($availablePlugins as $plugin) {
            if ($plugin['slug'] === $pluginSlug) {
                $this->manifestCache[$pluginSlug] = $plugin;
                return $plugin;
            }
        }

        return null;
    }

    /**
     * Get installed plugin
     *
     * @param string $pluginSlug Plugin slug
     * @return array|null Plugin data or null if not installed
     */
    private function getInstalledPlugin(string $pluginSlug): ?array
    {
        try {
            $plugin = $this->db->selectOne('plugins', ['slug' => $pluginSlug]);
            return $plugin ?: null;
        } catch (\Exception $e) {
            Logger::warning('Failed to check if plugin is installed', [
                'slug' => $pluginSlug,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if a version satisfies a version constraint
     *
     * Supports:
     * - "1.0.0" (exact version)
     * - ">=1.0.0" (greater than or equal)
     * - ">1.0.0" (greater than)
     * - "<=1.0.0" (less than or equal)
     * - "<1.0.0" (less than)
     * - "*" (any version)
     *
     * @param string $version Installed version
     * @param string $constraint Version constraint
     * @return bool True if version satisfies constraint
     */
    private function isVersionCompatible(string $version, string $constraint): bool
    {
        // Any version
        if ($constraint === '*') {
            return true;
        }

        // Exact version
        if (strpos($constraint, '>') === false && strpos($constraint, '<') === false) {
            return version_compare($version, $constraint, '==');
        }

        // Greater than or equal
        if (strpos($constraint, '>=') === 0) {
            $requiredVersion = substr($constraint, 2);
            return version_compare($version, $requiredVersion, '>=');
        }

        // Greater than
        if (strpos($constraint, '>') === 0) {
            $requiredVersion = substr($constraint, 1);
            return version_compare($version, $requiredVersion, '>');
        }

        // Less than or equal
        if (strpos($constraint, '<=') === 0) {
            $requiredVersion = substr($constraint, 2);
            return version_compare($version, $requiredVersion, '<=');
        }

        // Less than
        if (strpos($constraint, '<') === 0) {
            $requiredVersion = substr($constraint, 1);
            return version_compare($version, $requiredVersion, '<');
        }

        // Default: exact match
        return version_compare($version, $constraint, '==');
    }

    /**
     * Get detailed dependency information for a plugin
     *
     * Returns comprehensive information about dependencies,
     * conflicts, and recommendations.
     *
     * @param string $pluginSlug Plugin slug
     * @return array Dependency information
     */
    public function getDependencyInfo(string $pluginSlug): array
    {
        $info = [
            'direct_dependencies' => [],
            'all_dependencies' => [],
            'conflicts' => [],
            'recommendations' => [],
            'dependents' => []
        ];

        try {
            // Get plugin manifest
            $manifest = $this->getPluginManifest($pluginSlug, []);
            if (!$manifest) {
                return $info;
            }

            // Direct dependencies
            $info['direct_dependencies'] = $this->extractDependencies($manifest);

            // All dependencies (resolved)
            $resolved = $this->resolveDependencies($pluginSlug);
            if ($resolved['success']) {
                $info['all_dependencies'] = $resolved['dependencies'];
            }

            // Conflicts
            if (!empty($manifest['conflicts_with'])) {
                $info['conflicts'] = $manifest['conflicts_with'];
            }

            // Recommendations
            $info['recommendations'] = $this->extractRecommendations($manifest);

            // Dependents (plugins that depend on this one)
            $info['dependents'] = $this->getPluginDependents($pluginSlug);

        } catch (\Exception $e) {
            Logger::error('Failed to get dependency info', [
                'plugin' => $pluginSlug,
                'error' => $e->getMessage()
            ]);
        }

        return $info;
    }

    /**
     * Get plugins that depend on the specified plugin
     *
     * @param string $pluginSlug Plugin slug
     * @return array List of dependent plugin slugs
     */
    private function getPluginDependents(string $pluginSlug): array
    {
        $dependents = [];

        try {
            $allPlugins = $this->db->select('plugins', []);

            foreach ($allPlugins as $plugin) {
                if (empty($plugin['manifest'])) {
                    continue;
                }

                $manifest = json_decode($plugin['manifest'], true);
                if (!$manifest) {
                    continue;
                }

                $deps = $this->extractDependencies($manifest);
                if (isset($deps[$pluginSlug])) {
                    $dependents[] = $plugin['slug'];
                }
            }
        } catch (\Exception $e) {
            Logger::error('Failed to get dependents', [
                'plugin' => $pluginSlug,
                'error' => $e->getMessage()
            ]);
        }

        return $dependents;
    }
}
