# Caching System - Quick Reference Guide

## File Locations

```
Core Implementation:
  /home/user/NexoSupport/lib/classes/cache/Cache.php

Configuration:
  /home/user/NexoSupport/config/cache.php

Updated Components:
  /home/user/NexoSupport/core/View/ViewRenderer.php
  /home/user/NexoSupport/lib/classes/component/ComponentHelper.php

Cache Storage:
  /home/user/NexoSupport/var/cache/templates/
  /home/user/NexoSupport/var/cache/components/
  /home/user/NexoSupport/var/cache/[custom-namespace]/

Documentation:
  /home/user/NexoSupport/docs/CACHING_OPTIMIZATION.md
  /home/user/NexoSupport/PERFORMANCE_OPTIMIZATION_SUMMARY.md
```

---

## Quick Start

### Basic Cache Usage

```php
use ISER\Core\Cache\Cache;

// Create a cache instance (namespace, TTL)
$cache = new Cache('my_cache', 3600);

// Store a value
$cache->set('user_1', ['name' => 'John', 'email' => 'john@example.com']);

// Retrieve a value
$user = $cache->get('user_1');

// Check if exists
if ($cache->has('user_1')) {
    echo "User cached";
}

// Delete a value
$cache->delete('user_1');

// Clear all cache in this namespace
$cache->clear();
```

### Remember Pattern (Get or Set)

```php
// Get cached value, or generate and cache if missing
$users = $cache->remember('all_users', function() {
    // This callback only executes if cache miss
    return db_query('SELECT * FROM users LIMIT 100');
}, 3600); // 1 hour TTL
```

### Template Caching (Automatic)

```php
use ISER\Core\View\ViewRenderer;

$renderer = ViewRenderer::getInstance();

// Render template (caching is automatic)
$html = $renderer->render('report_log/index', [
    'title' => 'Report',
    'data' => $data,
]);

// Check caching status
if ($renderer->isCachingEnabled()) {
    echo "Caching is enabled";
}

// Disable caching (development mode)
$renderer->setCachingEnabled(false);

// Clear template cache
$renderer->clearCache();

// Get cache statistics
$stats = $renderer->getCacheStats();
echo "Cached templates: " . $stats['entries'];
echo "Cache size: " . $stats['total_size'] . " bytes";
```

### Component Caching (Automatic)

```php
use ISER\Core\Component\ComponentHelper;

$helper = ComponentHelper::getInstance();

// Get component path (caching is automatic)
$path = $helper->getPath('auth_manual');

// Cache is automatically invalidated when components.json changes

// Get cache statistics
$stats = $helper->getCacheStats();
echo "Cached paths: " . $stats['entries'];

// Manually clear cache if needed
$helper->clearCache();
```

---

## Common Patterns

### Cache with Default Value

```php
$cache = new Cache('defaults');

// Returns null if not cached
$value = $cache->get('key');

// Returns default value if not cached
$value = $cache->get('key', 'default_value');

// Returns cached value or default
if ($cache->has('key')) {
    $value = $cache->get('key');
} else {
    $value = 'default_value';
}
```

### Cache with Callback

```php
// Get cached value or generate from callback
$config = $cache->remember('config_key', function() {
    return [
        'app_name' => 'MyApp',
        'version' => '1.0.0',
        'env' => 'production',
    ];
}, 86400); // 24 hours
```

### Cache with Dynamic TTL

```php
// Short-lived cache (5 minutes)
$cache->set('session_temp', $data, 300);

// Medium-lived cache (1 hour)
$cache->set('user_data', $data, 3600);

// Long-lived cache (24 hours)
$cache->set('config_data', $data, 86400);

// Permanent cache (7 days)
$cache->set('static_data', $data, 604800);
```

### Namespace Organization

```php
// Create separate caches for different data types
$userCache = new Cache('users', 1800);       // 30 mins
$postCache = new Cache('posts', 3600);       // 1 hour
$configCache = new Cache('config', 86400);   // 24 hours

// Each namespace has its own cache directory and can be cleared independently
$userCache->clear();  // Only clears user cache
$postCache->clear();  // Only clears post cache
```

---

## Configuration

### Template Cache Settings

```php
// In config/cache.php
'templates' => [
    'enabled' => true,
    'namespace' => 'templates',
    'ttl' => 86400,                    // 24 hours
    'invalidate_on_file_change' => true,
    'cache_dir' => 'var/cache/templates',
],
```

### Component Cache Settings

```php
// In config/cache.php
'components' => [
    'enabled' => true,
    'namespace' => 'components',
    'ttl' => 3600,                     // 1 hour
    'invalidate_on_json_change' => true,
    'cache_key_prefix' => 'component_path_',
],
```

### Custom Namespace Settings

```php
// In config/cache.php
'namespaces' => [
    'my_custom_cache' => [
        'ttl' => 3600,
        'description' => 'My custom cache data',
    ],
],
```

---

## Cache Invalidation

### Automatic Invalidation

**Templates:** Automatically invalidated when source file changes
```php
// If you modify a template file, cache is automatically refreshed
// Cache key includes file modification time
```

**Components:** Automatically invalidated when components.json changes
```php
// If you install/uninstall a component, cache is automatically cleared
// System checks file modification time on each getPath() call
```

### Manual Invalidation

```php
// Clear template cache
ViewRenderer::getInstance()->clearCache();

// Clear component path cache
ComponentHelper::getInstance()->clearCache();

// Clear specific namespace
$cache = new Cache('my_namespace');
$cache->clear();
```

### Disable Caching (Development)

```php
// In development, disable caching to see changes immediately
ViewRenderer::getInstance()->setCachingEnabled(false);

// For custom caches, don't set values or clear before use
$cache = new Cache('dev_data');
$cache->clear();
```

---

## Monitoring & Debugging

### Cache Statistics

```php
// Get template cache stats
$stats = ViewRenderer::getInstance()->getCacheStats();
echo json_encode($stats);

// Output:
// {
//   "namespace": "templates",
//   "cache_dir": "/path/to/var/cache/templates",
//   "entries": 42,
//   "total_size": 2048576,
//   "expired_entries": 3
// }
```

### Monitor Cache Hit Rate

```php
// Track cache hits over time
$stats = $cache->getStats();
if ($stats['entries'] === 0) {
    error_log("WARNING: Cache is empty, check disk space and permissions");
}

// Monitor expired entries
if ($stats['expired_entries'] > 0) {
    error_log("INFO: Found {$stats['expired_entries']} expired cache entries");
}
```

### Debug Cache Operations

```php
$cache = new Cache('debug', 3600);

// Test set
$success = $cache->set('test_key', 'test_value');
if (!$success) {
    error_log("ERROR: Failed to write to cache");
}

// Test get
$value = $cache->get('test_key');
if ($value === null) {
    error_log("ERROR: Failed to read from cache");
}

// Check cache directory
$stats = $cache->getStats();
echo "Cache dir: " . $stats['cache_dir'];
echo "Is writable: " . is_writable($stats['cache_dir']);
```

---

## Performance Tips

### 1. Use Appropriate TTL Values

```php
// Don't cache everything forever
$cache->set('user_session', $session, 1800);   // 30 min for sessions

// Cache static data longer
$cache->set('site_config', $config, 604800);   // 7 days for config

// Cache API responses reasonably
$cache->set('api_data', $data, 3600);          // 1 hour for API data
```

### 2. Use Remember Pattern

```php
// Good: Clean and efficient
$data = $cache->remember('expensive_data',
    fn() => expensive_operation(),
    3600
);

// Avoid: Redundant checks
if (!$cache->has('key')) {
    $data = expensive_operation();
    $cache->set('key', $data);
} else {
    $data = $cache->get('key');
}
```

### 3. Clear Cache on Important Changes

```php
// After updating components
ComponentHelper::getInstance()->clearCache();

// After updating templates
ViewRenderer::getInstance()->clearCache();

// After installing a plugin
$cache->clear();
```

### 4. Monitor Cache Effectiveness

```php
// Check cache hit rate regularly
$stats = $cache->getStats();
if ($stats['entries'] === 0) {
    // Cache is empty, might be too aggressive with TTL
}

if ($stats['expired_entries'] > $stats['entries'] / 2) {
    // Too many expired entries, increase TTL
}
```

---

## Troubleshooting

### Cache Not Working

```php
// 1. Check if directory is writable
if (!is_writable('/home/user/NexoSupport/var/cache')) {
    error_log("Cache directory is not writable");
}

// 2. Test basic caching
$cache = new Cache('test');
if ($cache->set('key', 'value')) {
    echo "Cache is working";
} else {
    echo "Cache write failed";
}

// 3. Check if caching is enabled
if (!ViewRenderer::getInstance()->isCachingEnabled()) {
    echo "Template caching is disabled";
}
```

### Stale Cache Issues

```php
// Clear all caches
ViewRenderer::getInstance()->clearCache();
ComponentHelper::getInstance()->clearCache();

// Or delete cache files manually
// rm -rf /home/user/NexoSupport/var/cache/*
```

### Performance Not Improving

```php
// Verify cache is being used
$stats = ViewRenderer::getInstance()->getCacheStats();
if ($stats['entries'] === 0) {
    error_log("Cache is empty, caching not working");
}

// Check TTL values
$config = require '/home/user/NexoSupport/config/cache.php';
echo "Template TTL: " . $config['templates']['ttl'] . " seconds";
```

---

## Integration Checklist

- [ ] Cache directories created (`var/cache/templates/`, `var/cache/components/`)
- [ ] Cache class imported in your code
- [ ] Configuration loaded from `config/cache.php`
- [ ] Template caching working in ViewRenderer
- [ ] Component path caching working in ComponentHelper
- [ ] Cache statistics monitored
- [ ] Caching enabled in production
- [ ] Caching disabled in development (if needed)
- [ ] Cache cleared after deployments

---

## API Reference

### Cache Class Methods

```php
class Cache {
    public function __construct(string $namespace, int $defaultTtl = 3600)
    public function get(string $key, mixed $default = null): mixed
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    public function has(string $key): bool
    public function delete(string $key): bool
    public function clear(): bool
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    public function getStats(): array
    public function getNamespace(): string
    public function getDefaultTtl(): int
}
```

### ViewRenderer Additional Methods

```php
class ViewRenderer {
    public function setCachingEnabled(bool $enabled): void
    public function isCachingEnabled(): bool
    public function clearCache(): bool
    public function getCacheStats(): array
}
```

### ComponentHelper Additional Methods

```php
class ComponentHelper {
    public function getCacheStats(): array
    // clearCache() and reload() also clear persistent cache
}
```

---

## Further Reading

For comprehensive documentation, see:
- `/home/user/NexoSupport/docs/CACHING_OPTIMIZATION.md` - Complete guide
- `/home/user/NexoSupport/PERFORMANCE_OPTIMIZATION_SUMMARY.md` - Implementation details
