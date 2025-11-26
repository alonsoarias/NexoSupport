<?php
/**
 * Cache Administration Page
 *
 * Provides an interface to configure and manage MUC (Moodle Universal Cache).
 * Replicates Moodle's cache/admin.php
 *
 * @package    core_cache
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

require_once(dirname(__DIR__) . '/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');

// Require admin login
require_admin();

// Page setup
$PAGE->set_url('/cache/admin.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('cacheadmin', 'admin'));
$PAGE->set_heading(get_string('cacheadmin', 'admin'));

$PAGE->navbar->add(get_string('administrationsite', 'admin'), new moodle_url('/admin/'));
$PAGE->navbar->add(get_string('plugins', 'admin'));
$PAGE->navbar->add(get_string('caching', 'admin'));

// Actions
$action = optional_param('action', '', PARAM_ALPHA);
$store = optional_param('store', '', PARAM_ALPHANUMEXT);
$definition = optional_param('definition', '', PARAM_ALPHANUMEXT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Process actions
if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'purgestore':
            if ($store) {
                \core\cache\cache_helper::purge_stores(\core\cache\cache::MODE_APPLICATION);
                redirect(new moodle_url('/cache/admin.php'),
                    get_string('cachestorepurged', 'admin'),
                    null, \core\output\notification::NOTIFY_SUCCESS);
            }
            break;

        case 'purgedefinition':
            if ($definition) {
                $parts = explode('/', $definition, 2);
                if (count($parts) === 2) {
                    \core\cache\cache_helper::purge_by_definition($parts[0], $parts[1]);
                    redirect(new moodle_url('/cache/admin.php'),
                        get_string('cachedefinitionpurged', 'admin'),
                        null, \core\output\notification::NOTIFY_SUCCESS);
                }
            }
            break;

        case 'purgeall':
            \core\cache\cache_helper::purge_all();
            redirect(new moodle_url('/cache/admin.php'),
                get_string('purgecachesfinished', 'admin'),
                null, \core\output\notification::NOTIFY_SUCCESS);
            break;
    }
}

// Output page
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('cacheadmin', 'admin'));

// Purge all button
echo '<div class="mb-4">';
echo '<a href="' . (new moodle_url('/cache/admin.php', ['action' => 'purgeall', 'sesskey' => sesskey()]))->out() . '" ';
echo 'class="btn btn-danger">';
echo '<i class="fa fa-trash me-2"></i>' . get_string('purgeallcaches', 'admin');
echo '</a>';
echo ' ';
echo '<a href="' . (new moodle_url('/admin/purgecaches.php'))->out() . '" class="btn btn-secondary">';
echo '<i class="fa fa-cog me-2"></i>' . get_string('purgecachesselective', 'admin');
echo '</a>';
echo '</div>';

// Tabs
$tabs = [];
$tabs[] = ['id' => 'stores', 'name' => get_string('cachestores', 'admin'), 'active' => true];
$tabs[] = ['id' => 'definitions', 'name' => get_string('cachedefinitions', 'admin'), 'active' => false];
$tabs[] = ['id' => 'stats', 'name' => get_string('cachestats', 'admin'), 'active' => false];

echo '<ul class="nav nav-tabs mb-4" id="cacheTabs" role="tablist">';
foreach ($tabs as $tab) {
    $active = $tab['active'] ? ' active' : '';
    $selected = $tab['active'] ? 'true' : 'false';
    echo '<li class="nav-item" role="presentation">';
    echo '<button class="nav-link' . $active . '" id="' . $tab['id'] . '-tab" data-bs-toggle="tab" ';
    echo 'data-bs-target="#' . $tab['id'] . '" type="button" role="tab" aria-controls="' . $tab['id'] . '" ';
    echo 'aria-selected="' . $selected . '">' . $tab['name'] . '</button>';
    echo '</li>';
}
echo '</ul>';

echo '<div class="tab-content" id="cacheTabsContent">';

// ==========================================
// STORES TAB
// ==========================================
echo '<div class="tab-pane fade show active" id="stores" role="tabpanel" aria-labelledby="stores-tab">';

// Get available stores
$stores = [
    'file' => [
        'name' => 'File Store',
        'plugin' => 'file',
        'description' => get_string('cachestore_file_desc', 'admin'),
        'supported_modes' => [\core\cache\cache::MODE_APPLICATION],
        'available' => \core\cache\stores\cachestore_file::is_available(),
        'default' => true,
    ],
    'session' => [
        'name' => 'Session Store',
        'plugin' => 'session',
        'description' => get_string('cachestore_session_desc', 'admin'),
        'supported_modes' => [\core\cache\cache::MODE_SESSION],
        'available' => true,
        'default' => true,
    ],
    'static' => [
        'name' => 'Static Store',
        'plugin' => 'static',
        'description' => get_string('cachestore_static_desc', 'admin'),
        'supported_modes' => [\core\cache\cache::MODE_REQUEST],
        'available' => true,
        'default' => true,
    ],
];

// Check for Redis
if (extension_loaded('redis')) {
    $stores['redis'] = [
        'name' => 'Redis Store',
        'plugin' => 'redis',
        'description' => get_string('cachestore_redis_desc', 'admin'),
        'supported_modes' => [\core\cache\cache::MODE_APPLICATION, \core\cache\cache::MODE_SESSION],
        'available' => class_exists('\\core\\cache\\stores\\cachestore_redis') &&
                       \core\cache\stores\cachestore_redis::is_available(),
        'default' => false,
    ];
}

// Check for APCu
if (extension_loaded('apcu')) {
    $stores['apcu'] = [
        'name' => 'APCu Store',
        'plugin' => 'apcu',
        'description' => get_string('cachestore_apcu_desc', 'admin'),
        'supported_modes' => [\core\cache\cache::MODE_APPLICATION],
        'available' => class_exists('\\core\\cache\\stores\\cachestore_apcu') &&
                       \core\cache\stores\cachestore_apcu::is_available(),
        'default' => false,
    ];
}

echo '<div class="card mb-4">';
echo '<div class="card-header"><h5 class="mb-0">' . get_string('installedstores', 'admin') . '</h5></div>';
echo '<div class="card-body">';
echo '<table class="table table-striped">';
echo '<thead><tr>';
echo '<th>' . get_string('storename', 'admin') . '</th>';
echo '<th>' . get_string('storeplugin', 'admin') . '</th>';
echo '<th>' . get_string('supportedmodes', 'admin') . '</th>';
echo '<th>' . get_string('status', 'admin') . '</th>';
echo '<th>' . get_string('actions', 'admin') . '</th>';
echo '</tr></thead>';
echo '<tbody>';

foreach ($stores as $id => $store) {
    echo '<tr>';
    echo '<td><strong>' . s($store['name']) . '</strong><br><small class="text-muted">' . s($store['description']) . '</small></td>';
    echo '<td><code>' . s($store['plugin']) . '</code></td>';

    // Modes
    echo '<td>';
    $modes = [];
    foreach ($store['supported_modes'] as $mode) {
        switch ($mode) {
            case \core\cache\cache::MODE_APPLICATION:
                $modes[] = '<span class="badge bg-primary">Application</span>';
                break;
            case \core\cache\cache::MODE_SESSION:
                $modes[] = '<span class="badge bg-info">Session</span>';
                break;
            case \core\cache\cache::MODE_REQUEST:
                $modes[] = '<span class="badge bg-secondary">Request</span>';
                break;
        }
    }
    echo implode(' ', $modes);
    echo '</td>';

    // Status
    echo '<td>';
    if ($store['available']) {
        echo '<span class="badge bg-success">' . get_string('available', 'admin') . '</span>';
        if ($store['default']) {
            echo ' <span class="badge bg-warning text-dark">' . get_string('default', 'admin') . '</span>';
        }
    } else {
        echo '<span class="badge bg-danger">' . get_string('unavailable', 'admin') . '</span>';
    }
    echo '</td>';

    // Actions
    echo '<td>';
    if ($store['available']) {
        $purgeurl = new moodle_url('/cache/admin.php', [
            'action' => 'purgestore',
            'store' => $id,
            'sesskey' => sesskey()
        ]);
        echo '<a href="' . $purgeurl->out() . '" class="btn btn-sm btn-outline-danger" title="' . get_string('purge', 'admin') . '">';
        echo '<i class="fa fa-trash"></i>';
        echo '</a>';
    }
    echo '</td>';
    echo '</tr>';
}

echo '</tbody></table>';
echo '</div>';
echo '</div>';

echo '</div>'; // End stores tab

// ==========================================
// DEFINITIONS TAB
// ==========================================
echo '<div class="tab-pane fade" id="definitions" role="tabpanel" aria-labelledby="definitions-tab">';

// Get definitions
$definitions = \core\cache\cache_definition::get_all_definitions();

echo '<div class="card mb-4">';
echo '<div class="card-header"><h5 class="mb-0">' . get_string('cachedefinitions', 'admin') . ' (' . count($definitions) . ')</h5></div>';
echo '<div class="card-body">';

if (empty($definitions)) {
    echo '<p class="text-muted">' . get_string('nodefinitions', 'admin') . '</p>';
} else {
    echo '<table class="table table-striped table-sm">';
    echo '<thead><tr>';
    echo '<th>' . get_string('definition', 'admin') . '</th>';
    echo '<th>' . get_string('mode', 'admin') . '</th>';
    echo '<th>' . get_string('options', 'admin') . '</th>';
    echo '<th>' . get_string('actions', 'admin') . '</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($definitions as $key => $def) {
        echo '<tr>';

        // Definition name
        echo '<td><code>' . s($key) . '</code></td>';

        // Mode
        echo '<td>';
        $mode = $def['mode'] ?? \core\cache\cache::MODE_APPLICATION;
        switch ($mode) {
            case \core\cache\cache::MODE_APPLICATION:
                echo '<span class="badge bg-primary">Application</span>';
                break;
            case \core\cache\cache::MODE_SESSION:
                echo '<span class="badge bg-info">Session</span>';
                break;
            case \core\cache\cache::MODE_REQUEST:
                echo '<span class="badge bg-secondary">Request</span>';
                break;
        }
        echo '</td>';

        // Options
        echo '<td>';
        $options = [];
        if (!empty($def['simplekeys'])) {
            $options[] = '<small class="badge bg-light text-dark">simplekeys</small>';
        }
        if (!empty($def['simpledata'])) {
            $options[] = '<small class="badge bg-light text-dark">simpledata</small>';
        }
        if (!empty($def['staticacceleration'])) {
            $options[] = '<small class="badge bg-light text-dark">static</small>';
        }
        if (!empty($def['ttl'])) {
            $options[] = '<small class="badge bg-light text-dark">TTL:' . $def['ttl'] . '</small>';
        }
        echo implode(' ', $options);
        echo '</td>';

        // Actions
        echo '<td>';
        $purgeurl = new moodle_url('/cache/admin.php', [
            'action' => 'purgedefinition',
            'definition' => $key,
            'sesskey' => sesskey()
        ]);
        echo '<a href="' . $purgeurl->out() . '" class="btn btn-sm btn-outline-danger" title="' . get_string('purge', 'admin') . '">';
        echo '<i class="fa fa-trash"></i>';
        echo '</a>';
        echo '</td>';

        echo '</tr>';
    }

    echo '</tbody></table>';
}

echo '</div>';
echo '</div>';

echo '</div>'; // End definitions tab

// ==========================================
// STATS TAB
// ==========================================
echo '<div class="tab-pane fade" id="stats" role="tabpanel" aria-labelledby="stats-tab">';

$stats = \core\cache\cache_helper::get_stats();

echo '<div class="row">';

// Summary cards
echo '<div class="col-md-4 mb-4">';
echo '<div class="card h-100">';
echo '<div class="card-body text-center">';
echo '<h5 class="card-title">' . get_string('cachedefinitions', 'admin') . '</h5>';
echo '<p class="display-4">' . ($stats['definitions'] ?? 0) . '</p>';
echo '</div>';
echo '</div>';
echo '</div>';

// Application store
if (isset($stats['stores']['application'])) {
    $appstats = $stats['stores']['application'];
    echo '<div class="col-md-4 mb-4">';
    echo '<div class="card h-100">';
    echo '<div class="card-body text-center">';
    echo '<h5 class="card-title">' . get_string('applicationcache', 'admin') . '</h5>';
    if ($appstats['exists']) {
        echo '<p class="card-text">';
        echo '<strong>' . $appstats['files'] . '</strong> ' . get_string('files', 'admin') . '<br>';
        echo '<strong>' . $appstats['size_formatted'] . '</strong>';
        echo '</p>';
    } else {
        echo '<p class="text-muted">' . get_string('notconfigured', 'admin') . '</p>';
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

// Memory
if (isset($stats['memory'])) {
    echo '<div class="col-md-4 mb-4">';
    echo '<div class="card h-100">';
    echo '<div class="card-body text-center">';
    echo '<h5 class="card-title">' . get_string('memoryusage', 'admin') . '</h5>';
    echo '<p class="card-text">';
    echo get_string('current', 'admin') . ': <strong>' . format_bytes($stats['memory']['current']) . '</strong><br>';
    echo get_string('peak', 'admin') . ': <strong>' . format_bytes($stats['memory']['peak']) . '</strong>';
    echo '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

echo '</div>'; // End row

// OPcache
if (isset($stats['opcache'])) {
    echo '<div class="card mb-4">';
    echo '<div class="card-header"><h5 class="mb-0">OPcache</h5></div>';
    echo '<div class="card-body">';
    if ($stats['opcache']['enabled']) {
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<p><strong>' . get_string('memoryused', 'admin') . ':</strong> ' . format_bytes($stats['opcache']['memory_used']) . '</p>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<p><strong>' . get_string('hitrate', 'admin') . ':</strong> ' . number_format($stats['opcache']['hit_rate'], 2) . '%</p>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<p class="text-warning">' . get_string('opcachedisabled', 'admin') . '</p>';
    }
    echo '</div>';
    echo '</div>';
}

echo '</div>'; // End stats tab

echo '</div>'; // End tab-content

// JavaScript for tabs
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Handle tab clicks
    var tabs = document.querySelectorAll("#cacheTabs button");
    tabs.forEach(function(tab) {
        tab.addEventListener("click", function(e) {
            e.preventDefault();
            // Remove active from all tabs and panes
            document.querySelectorAll("#cacheTabs button").forEach(function(t) {
                t.classList.remove("active");
                t.setAttribute("aria-selected", "false");
            });
            document.querySelectorAll(".tab-pane").forEach(function(p) {
                p.classList.remove("show", "active");
            });
            // Activate clicked tab and its pane
            this.classList.add("active");
            this.setAttribute("aria-selected", "true");
            var target = this.getAttribute("data-bs-target");
            var pane = document.querySelector(target);
            if (pane) {
                pane.classList.add("show", "active");
            }
        });
    });
});
</script>';

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
