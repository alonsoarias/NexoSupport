<?php
/**
 * Admin Settings Page
 *
 * Displays and processes admin settings using the admin_setting classes.
 * Supports dynamic settings tree with categories and pages.
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:manageconfig');

global $USER, $CFG;

// Get page parameter
$page = optional_param('page', 'generalsettings', PARAM_TEXT);

// Get settings tree
$settingspage = admin_find_page($page);

if (!$settingspage) {
    redirect('/admin', get_string('pagenotfound', 'core'));
}

// Check access to this page
if (!$settingspage->check_access()) {
    redirect('/admin', get_string('nopermission', 'core'));
}

$errors = [];
$success = null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $errors = $settingspage->save_settings($_POST);

    if (empty($errors)) {
        $success = get_string('configsaved', 'core');
    }
}

// Get all categories for navigation
$categories = admin_get_categories();

// Prepare navigation data
$nav_categories = [];
foreach ($categories as $category) {
    $cat_data = [];
    $cat_data['name'] = $category->name;
    $cat_data['visiblename'] = $category->visiblename;
    $cat_data['pages'] = [];

    foreach ($category->get_pages() as $pg) {
        $cat_data['pages'][] = [
            'name' => $pg->name,
            'visiblename' => $pg->visiblename,
            'url' => '/admin/settings?page=' . urlencode($pg->name),
            'active' => ($pg->name === $page),
        ];
    }

    $nav_categories[] = $cat_data;
}

// Get settings from current page
$settings_html = [];
foreach ($settingspage->settings as $setting) {
    $settings_html[] = [
        'name' => $setting->name,
        'visiblename' => $setting->visiblename,
        'description' => $setting->description,
        'html' => $setting->output_html($setting->get_setting()),
        'is_heading' => $setting instanceof \core\admin\admin_setting_heading,
    ];
}

// Prepare template context
$context = [
    'lang' => \core\string_manager::get_language(),
    'page_title' => $settingspage->visiblename,
    'page_name' => $settingspage->name,
    'success' => $success ? htmlspecialchars($success) : null,
    'errors' => array_map('htmlspecialchars', $errors),
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
    'settings' => $settings_html,
    'categories' => $nav_categories,
    'systemversion' => get_config('core', 'version') ?? get_string('unknown', 'core'),
    'phpversion' => phpversion(),
    'dbtype' => $CFG->dbtype,
    'dbprefix' => $CFG->dbprefix,
];

// Render template
echo render_template('admin/settings_page', $context);
