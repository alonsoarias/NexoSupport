<?php
/**
 * Development Settings
 *
 * Developer options following Moodle's debugging and development patterns.
 * Includes theme designer mode, cache settings, and performance tools.
 *
 * @package NexoSupport
 * @version 1.1.30
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageconfig');

global $USER, $CFG, $DB, $PAGE, $OUTPUT;

$success = null;
$errors = [];
$warnings = [];

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    // Theme and CSS settings
    $themedesignermode = optional_param('themedesignermode', 0, PARAM_INT);
    $cachejs = optional_param('cachejs', 1, PARAM_INT);
    $cachetemplates = optional_param('cachetemplates', 1, PARAM_INT);
    $langstringcache = optional_param('langstringcache', 1, PARAM_INT);

    // Development tools
    $perfdebug = optional_param('perfdebug', 0, PARAM_INT);
    $debugpageinfo = optional_param('debugpageinfo', 0, PARAM_INT);
    $debugsqltrace = optional_param('debugsqltrace', 0, PARAM_INT);
    $debugvalidators = optional_param('debugvalidators', 0, PARAM_INT);
    $debugsmtp = optional_param('debugsmtp', 0, PARAM_INT);

    // YUI and AMD settings
    $yuiloglevel = optional_param('yuiloglevel', 'debug', PARAM_ALPHA);
    $yuicomboloading = optional_param('yuicomboloading', 1, PARAM_INT);

    // Save settings
    set_config('themedesignermode', $themedesignermode);
    set_config('cachejs', $cachejs);
    set_config('cachetemplates', $cachetemplates);
    set_config('langstringcache', $langstringcache);
    set_config('perfdebug', $perfdebug);
    set_config('debugpageinfo', $debugpageinfo);
    set_config('debugsqltrace', $debugsqltrace);
    set_config('debugvalidators', $debugvalidators);
    set_config('debugsmtp', $debugsmtp);
    set_config('yuiloglevel', $yuiloglevel);
    set_config('yuicomboloading', $yuicomboloading);

    // Purge caches if theme designer mode changed
    if ($themedesignermode && function_exists('purge_all_caches')) {
        purge_all_caches();
        $warnings[] = get_string('themedesignermodewarning', 'admin');
    }

    $success = get_string('changessaved', 'admin');
}

// Get current settings
$themedesignermode = (int)(get_config('core', 'themedesignermode') ?? 0);
$cachejs = (int)(get_config('core', 'cachejs') ?? 1);
$cachetemplates = (int)(get_config('core', 'cachetemplates') ?? 1);
$langstringcache = (int)(get_config('core', 'langstringcache') ?? 1);
$perfdebug = (int)(get_config('core', 'perfdebug') ?? 0);
$debugpageinfo = (int)(get_config('core', 'debugpageinfo') ?? 0);
$debugsqltrace = (int)(get_config('core', 'debugsqltrace') ?? 0);
$debugvalidators = (int)(get_config('core', 'debugvalidators') ?? 0);
$debugsmtp = (int)(get_config('core', 'debugsmtp') ?? 0);
$yuiloglevel = get_config('core', 'yuiloglevel') ?? 'debug';
$yuicomboloading = (int)(get_config('core', 'yuicomboloading') ?? 1);

// Check for enabled developer features
$developer_features_enabled = [];
if ($themedesignermode) {
    $developer_features_enabled[] = get_string('themedesignermode', 'admin');
}
if (!$cachejs) {
    $developer_features_enabled[] = get_string('cachedisabled', 'admin') . ' (JS)';
}
if (!$cachetemplates) {
    $developer_features_enabled[] = get_string('cachedisabled', 'admin') . ' (Templates)';
}
if (!$langstringcache) {
    $developer_features_enabled[] = get_string('cachedisabled', 'admin') . ' (Lang)';
}
if ($perfdebug) {
    $developer_features_enabled[] = get_string('perfdebug', 'admin');
}

// System info
$system_info = [];

// PHP OPcache status
$opcache_enabled = function_exists('opcache_get_status');
if ($opcache_enabled) {
    $opcache = @opcache_get_status(false);
    $system_info['opcache'] = [
        'enabled' => $opcache !== false && ($opcache['opcache_enabled'] ?? false),
        'memory_usage' => isset($opcache['memory_usage']) ? round($opcache['memory_usage']['used_memory'] / 1024 / 1024, 1) : 0,
        'hit_rate' => isset($opcache['opcache_statistics']) ? round($opcache['opcache_statistics']['opcache_hit_rate'], 1) : 0,
    ];
}

// APCu status
$apcu_enabled = extension_loaded('apcu') && ini_get('apc.enabled');
if ($apcu_enabled && function_exists('apcu_cache_info')) {
    $apcu = @apcu_cache_info(true);
    $system_info['apcu'] = [
        'enabled' => $apcu !== false,
        'memory_usage' => isset($apcu['mem_size']) ? round($apcu['mem_size'] / 1024 / 1024, 1) : 0,
        'entries' => $apcu['num_entries'] ?? 0,
    ];
}

// Redis status
$redis_available = class_exists('Redis');

// Cache directory info
$cachedir = $CFG->dataroot . '/cache';
$cache_size = 0;
if (is_dir($cachedir)) {
    // Approximate size calculation
    $cache_size = disk_free_space($cachedir) !== false ? round(disk_total_space($cachedir) - disk_free_space($cachedir)) : 0;
}

// YUI log levels
$yui_log_levels = [
    ['value' => 'debug', 'label' => 'Debug', 'selected' => $yuiloglevel === 'debug'],
    ['value' => 'info', 'label' => 'Info', 'selected' => $yuiloglevel === 'info'],
    ['value' => 'warn', 'label' => 'Warn', 'selected' => $yuiloglevel === 'warn'],
    ['value' => 'error', 'label' => 'Error', 'selected' => $yuiloglevel === 'error'],
];

// Prepare context
$context = [
    'pagetitle' => get_string('development', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),

    // Form fields - Theme/CSS
    'themedesignermode' => $themedesignermode,
    'themedesignermode_checked' => $themedesignermode == 1,
    'cachejs' => $cachejs,
    'cachejs_checked' => $cachejs == 1,
    'cachetemplates' => $cachetemplates,
    'cachetemplates_checked' => $cachetemplates == 1,
    'langstringcache' => $langstringcache,
    'langstringcache_checked' => $langstringcache == 1,

    // Development tools
    'perfdebug' => $perfdebug,
    'perfdebug_checked' => $perfdebug == 1,
    'debugpageinfo' => $debugpageinfo,
    'debugpageinfo_checked' => $debugpageinfo == 1,
    'debugsqltrace' => $debugsqltrace,
    'debugsqltrace_checked' => $debugsqltrace == 1,
    'debugvalidators' => $debugvalidators,
    'debugvalidators_checked' => $debugvalidators == 1,
    'debugsmtp' => $debugsmtp,
    'debugsmtp_checked' => $debugsmtp == 1,

    // YUI/AMD settings
    'yui_log_levels' => $yui_log_levels,
    'yuicomboloading' => $yuicomboloading,
    'yuicomboloading_checked' => $yuicomboloading == 1,

    // Developer features status
    'developer_features_enabled' => $developer_features_enabled,
    'has_developer_features' => !empty($developer_features_enabled),

    // System info
    'opcache_available' => $opcache_enabled,
    'opcache_enabled' => $system_info['opcache']['enabled'] ?? false,
    'opcache_memory' => $system_info['opcache']['memory_usage'] ?? 0,
    'opcache_hitrate' => $system_info['opcache']['hit_rate'] ?? 0,
    'apcu_available' => $apcu_enabled,
    'apcu_enabled' => $system_info['apcu']['enabled'] ?? false,
    'apcu_memory' => $system_info['apcu']['memory_usage'] ?? 0,
    'apcu_entries' => $system_info['apcu']['entries'] ?? 0,
    'redis_available' => $redis_available,

    // Links
    'purgecaches_url' => $CFG->wwwroot . '/admin/purgecaches.php',
    'cacheadmin_url' => $CFG->wwwroot . '/cache/admin.php',
    'debugging_url' => $CFG->wwwroot . '/admin/settings/debugging.php',
    'phpinfo_url' => $CFG->wwwroot . '/admin/phpinfo.php',

    // Messages
    'success' => $success,
    'errors' => $errors,
    'warnings' => $warnings,
    'haserrors' => !empty($errors),
    'haswarnings' => !empty($warnings),
    'sesskey' => sesskey(),
];

echo render_template('admin/settings_development', $context);
