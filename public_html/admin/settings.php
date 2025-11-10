<?php
/**
 * ISER - Admin Settings Controller
 *
 * @package    ISER
 * @copyright  2024 ISER
 * @license    Proprietary
 */

define('ISER_BASE_DIR', dirname(__DIR__, 2));

require_once ISER_BASE_DIR . '/vendor/autoload.php';

use ISER\Core\Config\ConfigManager;
use ISER\Core\Config\SettingsManager;
use ISER\Core\Database\Database;
use ISER\Core\Session\JWTSession;
use ISER\Core\Middleware\AdminMiddleware;
use ISER\Modules\User\UserManager;
use ISER\Modules\Roles\PermissionManager;
use ISER\Modules\Roles\RoleAssignment;
use ISER\Modules\Admin\AdminManager;
use ISER\Modules\Admin\AdminSettings;
use ISER\Core\Utils\Logger;

// Initialize core services
$config = ConfigManager::getInstance(ISER_BASE_DIR);
$db = Database::getInstance($config->getDatabaseConfig());
$jwt = new JWTSession($config->getJwtConfig(), $db);
$settings = new SettingsManager($db);
$userManager = new UserManager($db);
$permissionManager = new PermissionManager($db);
$roleAssignment = new RoleAssignment($db);

// Check admin authentication
$adminMiddleware = new AdminMiddleware($jwt, $userManager, $permissionManager, $roleAssignment);
$adminMiddleware->requireAdmin();

// Get current admin user
$currentUser = $adminMiddleware->getUser();

// Initialize managers
$adminManager = new AdminManager($db, $settings);
$adminSettings = new AdminSettings($settings);

// Get current section
$currentSection = $_GET['section'] ?? 'general';

// Handle form submission
$alerts = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = [];

    foreach ($_POST as $key => $value) {
        if ($key !== 'submit') {
            // Handle checkboxes (not sent if unchecked)
            $values[$key] = $value;
        }
    }

    // Save settings
    if ($adminSettings->saveSectionSettings($currentSection, $values)) {
        $alerts[] = [
            'type' => 'success',
            'message' => 'Configuración guardada correctamente'
        ];

        // Log the action
        $adminManager->logAction(
            $currentUser['id'],
            'settings_update',
            'admin_settings',
            null,
            null,
            ['section' => $currentSection]
        );
    } else {
        $alerts[] = [
            'type' => 'danger',
            'message' => 'Error al guardar la configuración'
        ];
    }
}

// Get section settings
$sectionSettings = $adminSettings->getSectionSettings($currentSection);
$sections = $adminSettings->getSections();

// Prepare settings for template
$preparedSettings = [];
foreach ($sectionSettings as $name => $setting) {
    $type = $setting['type'];
    $setting['name'] = $name;

    // Set type flags for Mustache
    $setting['type_' . $type] = true;

    // Handle select options
    if ($type === 'select' && isset($setting['options'])) {
        $options = [];
        foreach ($setting['options'] as $optKey => $optLabel) {
            $options[] = [
                'key' => $optKey,
                'label' => is_array($optLabel) ? $optLabel : $optLabel,
                'is_selected' => ($optKey == $setting['value'])
            ];
        }
        $setting['options'] = $options;
    }

    // Set default rows for textarea
    if ($type === 'textarea' && !isset($setting['rows'])) {
        $setting['rows'] = 5;
    }

    $preparedSettings[] = $setting;
}

// Prepare section navigation
$preparedSections = [];
foreach ($sections as $key => $name) {
    $preparedSections[] = [
        'key' => $key,
        'name' => $name,
        'is_active' => ($key === $currentSection)
    ];
}

// Prepare template data
$templateData = [
    'page_title' => 'Configuración',
    'page_description' => $sections[$currentSection] ?? 'Configuración del Sistema',
    'admin_username' => $currentUser['username'],
    'primary_color' => $settings->get('primary_color', 'theme_iser', '#667eea'),
    'secondary_color' => $settings->get('secondary_color', 'theme_iser', '#764ba2'),
    'sections' => prepareNavigation($adminManager, 'settings'),
    'alerts' => $alerts,
    'has_sections' => true,
    'sections' => $preparedSections,
    'current_section' => $currentSection,
    'section_title' => $sections[$currentSection] ?? 'Configuración',
    'settings' => $preparedSettings,
    'show_test_email' => ($currentSection === 'outgoingmailconfig'),
];

// Render settings template
$mustache = new Mustache_Engine([
    'loader' => new Mustache_Loader_FilesystemLoader(ISER_BASE_DIR . '/modules/Admin/templates'),
]);

$settingsContent = $mustache->render('admin_settings', $templateData);
$templateData['content'] = $settingsContent;

echo $mustache->render('admin_layout', $templateData);

/**
 * Prepare navigation sections
 *
 * @param AdminManager $adminManager
 * @param string $currentSection
 * @return array
 */
function prepareNavigation(AdminManager $adminManager, string $currentSection): array
{
    $sections = $adminManager->getAdminSections();
    $prepared = [];

    foreach ($sections as $key => $section) {
        $section['is_active'] = ($key === $currentSection);
        $section['has_subsections'] = !empty($section['subsections']);
        $prepared[] = $section;
    }

    return $prepared;
}
