<?php
/**
 * ISER - Admin Tools Controller
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
use ISER\Modules\Admin\AdminTools;

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
$adminTools = new AdminTools($db);

// Handle form submission
$alerts = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'clearcache') {
        if ($adminTools->clearCache()) {
            $alerts[] = [
                'type' => 'success',
                'message' => 'Caché limpiado correctamente'
            ];

            $adminManager->logAction(
                $currentUser['id'],
                'cache_clear',
                'admin_tools'
            );
        } else {
            $alerts[] = [
                'type' => 'danger',
                'message' => 'Error al limpiar el caché'
            ];
        }
    }
}

// Get tools and stats
$tools = $adminTools->getTools();
$dbSize = $adminTools->getDatabaseSize();
$cacheStats = $adminTools->getCacheStats();

// Prepare template data
$templateData = [
    'page_title' => 'Herramientas',
    'page_description' => 'Herramientas administrativas del sistema',
    'admin_username' => $currentUser['username'],
    'primary_color' => $settings->get('primary_color', 'theme_iser', '#667eea'),
    'secondary_color' => $settings->get('secondary_color', 'theme_iser', '#764ba2'),
    'sections' => prepareNavigation($adminManager, 'tools'),
    'alerts' => $alerts,
    'tools' => array_values($tools),
    'db_size_mb' => $dbSize['size_mb'],
    'db_table_count' => $dbSize['table_count'],
    'cache_total' => $cacheStats['total'],
    'cache_expired' => $cacheStats['expired'],
];

// Render tools template
$mustache = new Mustache_Engine([
    'loader' => new Mustache_Loader_FilesystemLoader(ISER_BASE_DIR . '/modules/Admin/templates'),
]);

$toolsContent = $mustache->render('admin_tools', $templateData);
$templateData['content'] = $toolsContent;

echo $mustache->render('admin_layout', $templateData);

/**
 * Prepare navigation sections
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
