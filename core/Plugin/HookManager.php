<?php

/**
 * ISER - Hook Manager for Plugin System
 *
 * Singleton class that manages plugin hooks/events.
 * Allows plugins to register and fire events throughout the system.
 *
 * @package    ISER\Core\Plugin
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 2
 */

namespace ISER\Core\Plugin;

use ISER\Core\Utils\Logger;

/**
 * HookManager Class
 *
 * Manages plugin hooks and events using the Observer/Pub-Sub pattern.
 * Supports priority-based callback execution.
 */
class HookManager
{
    /**
     * Singleton instance
     */
    private static ?HookManager $instance = null;

    /**
     * Registered hooks with their callbacks
     * Structure: ['hook_name' => [['callback' => callable, 'priority' => int], ...]]
     */
    private array $hooks = [];

    /**
     * Hook execution counts (for debugging)
     */
    private array $executionCounts = [];

    /**
     * Private constructor - use getInstance() instead
     */
    private function __construct()
    {
    }

    /**
     * Get singleton instance
     *
     * @return HookManager
     */
    public static function getInstance(): HookManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a callback for a hook
     *
     * @param string $hookName Hook identifier
     * @param callable $callback Function to execute
     * @param int $priority Priority order (1-100, lower = earlier execution)
     * @return void
     *
     * @throws \InvalidArgumentException If callback is not callable
     */
    public function register(string $hookName, callable $callback, int $priority = 10): void
    {
        if (!is_callable($callback)) {
            Logger::error('Invalid callback for hook', [
                'hook' => $hookName,
                'type' => gettype($callback)
            ]);
            throw new \InvalidArgumentException("Callback for hook '{$hookName}' must be callable");
        }

        // Validate priority range
        if ($priority < 1 || $priority > 100) {
            Logger::warning('Hook priority out of range, using default', [
                'hook' => $hookName,
                'priority' => $priority
            ]);
            $priority = 10;
        }

        // Initialize hook array if doesn't exist
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }

        // Add callback with priority
        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority
        ];

        // Sort by priority (lower values execute first)
        usort($this->hooks[$hookName], function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        Logger::system('Hook registered', [
            'hook' => $hookName,
            'priority' => $priority,
            'callback_count' => count($this->hooks[$hookName])
        ]);
    }

    /**
     * Fire a hook and execute all registered callbacks
     *
     * @param string $hookName Hook identifier
     * @param mixed ...$args Arguments to pass to callbacks
     * @return array Results from all callbacks
     *
     * @throws \RuntimeException If hook execution fails
     */
    public function fire(string $hookName, ...$args): array
    {
        if (!$this->has($hookName)) {
            Logger::system('Hook fired with no listeners', ['hook' => $hookName]);
            return [];
        }

        $results = [];

        // Initialize execution count if needed
        if (!isset($this->executionCounts[$hookName])) {
            $this->executionCounts[$hookName] = 0;
        }

        try {
            foreach ($this->hooks[$hookName] as $index => $hookData) {
                try {
                    $result = call_user_func_array(
                        $hookData['callback'],
                        $args
                    );

                    $results[$index] = $result;
                    $this->executionCounts[$hookName]++;

                } catch (\Exception $e) {
                    Logger::error('Hook callback failed', [
                        'hook' => $hookName,
                        'index' => $index,
                        'error' => $e->getMessage()
                    ]);

                    $results[$index] = null;
                }
            }

            Logger::system('Hook fired successfully', [
                'hook' => $hookName,
                'callbacks_executed' => count($results)
            ]);

        } catch (\Throwable $e) {
            Logger::error('Hook execution failed', [
                'hook' => $hookName,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to execute hook '{$hookName}': {$e->getMessage()}"
            );
        }

        return $results;
    }

    /**
     * Check if a hook has registered callbacks
     *
     * @param string $hookName Hook identifier
     * @return bool True if hook exists and has callbacks
     */
    public function has(string $hookName): bool
    {
        return isset($this->hooks[$hookName]) && count($this->hooks[$hookName]) > 0;
    }

    /**
     * Get all registered hooks
     *
     * @return array All hooks with their callbacks
     */
    public function getHooks(): array
    {
        return $this->hooks;
    }

    /**
     * Get callbacks for a specific hook
     *
     * @param string $hookName Hook identifier
     * @return array Callbacks for the hook
     */
    public function getCallbacks(string $hookName): array
    {
        return $this->hooks[$hookName] ?? [];
    }

    /**
     * Remove a specific callback from a hook
     *
     * @param string $hookName Hook identifier
     * @param callable $callback Callback to remove
     * @return bool True if callback was removed
     */
    public function unregister(string $hookName, callable $callback): bool
    {
        if (!$this->has($hookName)) {
            return false;
        }

        $originalCount = count($this->hooks[$hookName]);

        $this->hooks[$hookName] = array_filter(
            $this->hooks[$hookName],
            function ($hookData) use ($callback) {
                return $hookData['callback'] !== $callback;
            }
        );

        // Remove hook if no callbacks left
        if (empty($this->hooks[$hookName])) {
            unset($this->hooks[$hookName]);
        }

        $removed = count($this->hooks[$hookName]) < $originalCount;

        if ($removed) {
            Logger::system('Hook callback unregistered', [
                'hook' => $hookName
            ]);
        }

        return $removed;
    }

    /**
     * Remove all callbacks from a hook
     *
     * @param string $hookName Hook identifier
     * @return int Number of callbacks removed
     */
    public function removeAll(string $hookName): int
    {
        if (!isset($this->hooks[$hookName])) {
            return 0;
        }

        $count = count($this->hooks[$hookName]);
        unset($this->hooks[$hookName]);

        Logger::system('All hook callbacks removed', [
            'hook' => $hookName,
            'count' => $count
        ]);

        return $count;
    }

    /**
     * Get execution statistics for debugging
     *
     * @return array Execution statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_hooks' => count($this->hooks),
            'total_callbacks' => array_reduce(
                $this->hooks,
                fn ($carry, $callbacks) => $carry + count($callbacks),
                0
            ),
            'execution_counts' => $this->executionCounts
        ];
    }

    /**
     * Clear all hooks (useful for testing)
     *
     * @return void
     */
    public function clear(): void
    {
        $this->hooks = [];
        $this->executionCounts = [];
        Logger::system('All hooks cleared');
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton');
    }
}
