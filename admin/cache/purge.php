<?php
/**
 * Cache Purge Administration
 *
 * Allows administrators to purge various caches
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('nexosupport/admin:managesettings');

global $USER;

$success = null;
$errors = [];
$purge_results = [];

// Process purge request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $purge_type = optional_param('purge_type', '', 'text');

    try {
        switch ($purge_type) {
            case 'all':
                $purge_results = \core\cache\cache_manager::purge_all();
                $success = get_string('cachepurgedall', 'core');
                break;

            case 'opcache':
                $purge_results['opcache'] = \core\cache\cache_manager::purge_opcache();
                $success = get_string('cachepurgedopcache', 'core');
                break;

            case 'mustache':
                $purge_results['mustache'] = \core\cache\cache_manager::purge_mustache_cache();
                $success = get_string('cachepurgedmustache', 'core');
                break;

            case 'application':
                $purge_results['application'] = \core\cache\cache_manager::purge_application_cache();
                $success = get_string('cachepurgedapp', 'core');
                break;

            case 'rbac':
                $purge_results['rbac'] = \core\cache\cache_manager::purge_rbac_cache();
                $success = get_string('cachepurgedrbac', 'core');
                break;

            default:
                $errors[] = get_string('invalidpurgetype', 'core');
        }
    } catch (\Exception $e) {
        $errors[] = get_string('error') . ': ' . $e->getMessage();
    }
}

// Get cache status
$cache_status = \core\cache\cache_manager::get_status();

// Prepare context for template
$context = [
    'user' => $USER,
    'showadmin' => true,
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'purge_results' => $purge_results,
    'has_purge_results' => !empty($purge_results),
    'cache_status' => $cache_status,
    'sesskey' => sesskey(),
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
];

// Format cache status for display
if (isset($cache_status['opcache']) && $cache_status['opcache']['enabled']) {
    $context['opcache_enabled'] = true;
    $context['opcache_memory_used'] = \core\cache\cache_manager::format_bytes($cache_status['opcache']['memory_used']);
    $context['opcache_memory_free'] = \core\cache\cache_manager::format_bytes($cache_status['opcache']['memory_free']);
    $context['opcache_memory_wasted'] = \core\cache\cache_manager::format_bytes($cache_status['opcache']['memory_wasted']);
    $context['opcache_scripts'] = $cache_status['opcache']['num_cached_scripts'];
    $context['opcache_hits'] = number_format($cache_status['opcache']['hits']);
    $context['opcache_misses'] = number_format($cache_status['opcache']['misses']);

    if ($cache_status['opcache']['hits'] + $cache_status['opcache']['misses'] > 0) {
        $hit_rate = ($cache_status['opcache']['hits'] / ($cache_status['opcache']['hits'] + $cache_status['opcache']['misses'])) * 100;
        $context['opcache_hit_rate'] = number_format($hit_rate, 2) . '%';
    }
} else {
    $context['opcache_enabled'] = false;
}

// Format Mustache cache status
if (isset($cache_status['mustache'])) {
    $context['mustache_enabled'] = true;
    $context['mustache_templates'] = $cache_status['mustache']['num_cached_templates'];
    $context['mustache_cache_size'] = \core\cache\cache_manager::format_bytes($cache_status['mustache']['cache_size']);
    $context['mustache_cache_dir'] = $cache_status['mustache']['cache_dir'];
} else {
    $context['mustache_enabled'] = false;
}

// Render and output
echo render_template('admin/cache_purge', $context);
