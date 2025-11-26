<?php
/**
 * Purge Caches Page
 *
 * Administration page for purging system caches.
 * Replicates Moodle's admin/purgecaches.php
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

// Ignore component cache during purge operations
define('IGNORE_COMPONENT_CACHE', true);

require_once(dirname(__DIR__) . '/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');

// Require admin login
require_admin();

// Page setup
$PAGE->set_url('/admin/purgecaches.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('purgecaches', 'admin'));
$PAGE->set_heading(get_string('purgecaches', 'admin'));

$PAGE->navbar->add(get_string('administrationsite', 'admin'), new moodle_url('/admin/'));
$PAGE->navbar->add(get_string('development', 'admin'));
$PAGE->navbar->add(get_string('purgecaches', 'admin'));

// Available cache types to purge
$cachetypes = [
    'muc' => [
        'name' => get_string('purgecaches_muc', 'admin'),
        'description' => get_string('purgecaches_muc_desc', 'admin'),
        'default' => true,
    ],
    'theme' => [
        'name' => get_string('purgecaches_theme', 'admin'),
        'description' => get_string('purgecaches_theme_desc', 'admin'),
        'default' => true,
    ],
    'lang' => [
        'name' => get_string('purgecaches_lang', 'admin'),
        'description' => get_string('purgecaches_lang_desc', 'admin'),
        'default' => true,
    ],
    'js' => [
        'name' => get_string('purgecaches_js', 'admin'),
        'description' => get_string('purgecaches_js_desc', 'admin'),
        'default' => true,
    ],
    'template' => [
        'name' => get_string('purgecaches_template', 'admin'),
        'description' => get_string('purgecaches_template_desc', 'admin'),
        'default' => true,
    ],
    'other' => [
        'name' => get_string('purgecaches_other', 'admin'),
        'description' => get_string('purgecaches_other_desc', 'admin'),
        'default' => true,
    ],
];

// Process form submission
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$purgeall = optional_param('purgeall', 0, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

if ($confirm && confirm_sesskey()) {
    $results = [];

    if ($purgeall) {
        // Purge all caches
        $results = purge_all_caches();
        $message = get_string('purgecachesfinished', 'admin');
    } else {
        // Selective purge
        foreach ($cachetypes as $type => $info) {
            if (optional_param($type, 0, PARAM_BOOL)) {
                switch ($type) {
                    case 'muc':
                        \core\cache\cache_helper::purge_all();
                        $results[$type] = ['success' => true, 'message' => $info['name'] . ' purged'];
                        break;

                    case 'theme':
                        theme_reset_all_caches();
                        $results[$type] = ['success' => true, 'message' => $info['name'] . ' purged'];
                        break;

                    case 'lang':
                        if (class_exists('\\core\\string_manager')) {
                            \core\string_manager::clear_cache();
                        }
                        $results[$type] = ['success' => true, 'message' => $info['name'] . ' purged'];
                        break;

                    case 'js':
                        js_reset_all_caches();
                        $results[$type] = ['success' => true, 'message' => $info['name'] . ' purged'];
                        break;

                    case 'template':
                        if (class_exists('\\core\\output\\template_manager')) {
                            \core\output\template_manager::clear_cache();
                        }
                        $results[$type] = ['success' => true, 'message' => $info['name'] . ' purged'];
                        break;

                    case 'other':
                        // Purge OPcache
                        if (function_exists('opcache_reset')) {
                            @opcache_reset();
                        }
                        $results[$type] = ['success' => true, 'message' => $info['name'] . ' purged'];
                        break;
                }
            }
        }
        $message = get_string('purgecachesfinished', 'admin');
    }

    // Log the purge
    debugging('Caches purged by admin: ' . json_encode(array_keys($results)), DEBUG_DEVELOPER);

    // Redirect with success message
    $redirecturl = !empty($returnurl) ? new moodle_url($returnurl) : new moodle_url('/admin/purgecaches.php');
    redirect($redirecturl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

// Output page
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('purgecaches', 'admin'));

echo '<div class="alert alert-info">';
echo '<p>' . get_string('purgecachesinfo', 'admin') . '</p>';
echo '</div>';

// Quick purge all button
echo '<div class="mb-4">';
echo '<form method="post" action="">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
echo '<input type="hidden" name="confirm" value="1">';
echo '<input type="hidden" name="purgeall" value="1">';
if (!empty($returnurl)) {
    echo '<input type="hidden" name="returnurl" value="' . s($returnurl) . '">';
}
echo '<button type="submit" class="btn btn-danger btn-lg">';
echo '<i class="fa fa-trash me-2"></i>';
echo get_string('purgeallcaches', 'admin');
echo '</button>';
echo '</form>';
echo '</div>';

echo '<hr class="my-4">';

// Selective purge form
echo '<h4>' . get_string('purgecachesselective', 'admin') . '</h4>';

echo '<form method="post" action="">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
echo '<input type="hidden" name="confirm" value="1">';
if (!empty($returnurl)) {
    echo '<input type="hidden" name="returnurl" value="' . s($returnurl) . '">';
}

echo '<div class="card mb-4">';
echo '<div class="card-body">';

foreach ($cachetypes as $type => $info) {
    $checked = $info['default'] ? 'checked' : '';
    echo '<div class="form-check mb-3">';
    echo '<input class="form-check-input" type="checkbox" name="' . $type . '" value="1" id="cache_' . $type . '" ' . $checked . '>';
    echo '<label class="form-check-label" for="cache_' . $type . '">';
    echo '<strong>' . s($info['name']) . '</strong>';
    echo '<br><small class="text-muted">' . s($info['description']) . '</small>';
    echo '</label>';
    echo '</div>';
}

echo '</div>';
echo '</div>';

echo '<button type="submit" class="btn btn-primary">';
echo '<i class="fa fa-broom me-2"></i>';
echo get_string('purgeselectedcaches', 'admin');
echo '</button>';

echo '</form>';

// Cache statistics
echo '<hr class="my-4">';
echo '<h4>' . get_string('cachestats', 'admin') . '</h4>';

$stats = \core\cache\cache_helper::get_stats();

echo '<div class="row">';

// Definitions count
echo '<div class="col-md-4">';
echo '<div class="card mb-3">';
echo '<div class="card-body text-center">';
echo '<h5 class="card-title">' . get_string('cachedefinitions', 'admin') . '</h5>';
echo '<p class="card-text display-4">' . ($stats['definitions'] ?? 0) . '</p>';
echo '</div>';
echo '</div>';
echo '</div>';

// Application cache stats
if (isset($stats['stores']['application'])) {
    echo '<div class="col-md-4">';
    echo '<div class="card mb-3">';
    echo '<div class="card-body text-center">';
    echo '<h5 class="card-title">' . get_string('cacheapplication', 'admin') . '</h5>';
    echo '<p class="card-text">';
    echo 'Files: ' . ($stats['stores']['application']['files'] ?? 0) . '<br>';
    echo 'Size: ' . ($stats['stores']['application']['size_formatted'] ?? '0 B');
    echo '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

// Memory stats
if (isset($stats['memory'])) {
    echo '<div class="col-md-4">';
    echo '<div class="card mb-3">';
    echo '<div class="card-body text-center">';
    echo '<h5 class="card-title">' . get_string('memoryusage', 'admin') . '</h5>';
    echo '<p class="card-text">';
    echo 'Current: ' . format_bytes($stats['memory']['current']) . '<br>';
    echo 'Peak: ' . format_bytes($stats['memory']['peak']);
    echo '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

echo '</div>';

// OPcache stats
if (isset($stats['opcache']) && $stats['opcache']['enabled']) {
    echo '<div class="card mb-3">';
    echo '<div class="card-header">OPcache</div>';
    echo '<div class="card-body">';
    echo '<p>Memory Used: ' . format_bytes($stats['opcache']['memory_used']) . '</p>';
    echo '<p>Hit Rate: ' . number_format($stats['opcache']['hit_rate'], 2) . '%</p>';
    echo '</div>';
    echo '</div>';
}

echo $OUTPUT->footer();

/**
 * Format bytes to human readable string
 *
 * @param int $bytes Bytes
 * @return string Formatted string
 */
function format_bytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Reset theme caches
 */
function theme_reset_all_caches(): void {
    global $CFG;

    // Clear theme cache directory
    if (!empty($CFG->cachedir)) {
        $themecachedir = $CFG->cachedir . '/theme';
        if (is_dir($themecachedir)) {
            purge_directory_contents($themecachedir);
        }
    }

    // Increment theme revision
    set_config('themerev', time());
}

/**
 * Reset JavaScript caches
 */
function js_reset_all_caches(): void {
    global $CFG;

    // Clear JS cache directory
    if (!empty($CFG->cachedir)) {
        $jscachedir = $CFG->cachedir . '/js';
        if (is_dir($jscachedir)) {
            purge_directory_contents($jscachedir);
        }
    }

    // Increment JS revision
    set_config('jsrev', time());
}
