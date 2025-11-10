<?php
/**
 * ISER - Admin Dashboard Controller
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

// Initialize Admin Manager
$adminManager = new AdminManager($db, $settings);

// Get dashboard data
$stats = $adminManager->getDashboardStats();
$systemInfo = $adminManager->getSystemInfo();
$recentActivity = $adminManager->getRecentActivity(10);

// Format recent activity timestamps
foreach ($recentActivity as &$activity) {
    $activity['timecreated_formatted'] = date('d/m/Y H:i', $activity['timecreated']);
}

// Prepare template data
$templateData = [
    'page_title' => 'Dashboard',
    'page_description' => 'Visión general del sistema',
    'admin_username' => $currentUser['username'],
    'primary_color' => $settings->get('primary_color', 'theme_iser', '#667eea'),
    'secondary_color' => $settings->get('secondary_color', 'theme_iser', '#764ba2'),
    'sections' => prepareNavigation($adminManager, 'dashboard'),
    'stats' => $stats,
    'system_info' => $systemInfo,
    'recent_activity' => $recentActivity,
];

// Render dashboard template
$mustache = new Mustache_Engine([
    'loader' => new Mustache_Loader_FilesystemLoader(ISER_BASE_DIR . '/modules/Admin/templates'),
]);

$dashboardContent = $mustache->render('admin_dashboard', $templateData);
$templateData['content'] = $dashboardContent;

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
