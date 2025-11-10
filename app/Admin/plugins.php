<?php
/**
 * ISER - Admin Plugins Controller
 *
 * @package    ISER
 * @copyright  2024 ISER
 * @license    Proprietary
 */

// Include security check
require_once __DIR__ . '/security-check.php';

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
use ISER\Modules\Admin\AdminPlugins;

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
$adminPlugins = new AdminPlugins($db);

// Handle form submission
$alerts = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $plugin = $_POST['plugin'] ?? '';

    if ($action === 'enable') {
        if ($adminPlugins->enablePlugin($plugin)) {
            $alerts[] = [
                'type' => 'success',
                'message' => "Plugin '{$plugin}' habilitado correctamente"
            ];

            $adminManager->logAction(
                $currentUser['id'],
                'plugin_enable',
                'admin_plugins',
                null,
                null,
                ['plugin' => $plugin]
            );
        } else {
            $alerts[] = [
                'type' => 'danger',
                'message' => "Error al habilitar el plugin '{$plugin}'"
            ];
        }
    } elseif ($action === 'disable') {
        if ($adminPlugins->disablePlugin($plugin)) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Plugin '{$plugin}' deshabilitado correctamente"
            ];

            $adminManager->logAction(
                $currentUser['id'],
                'plugin_disable',
                'admin_plugins',
                null,
                null,
                ['plugin' => $plugin]
            );
        } else {
            $alerts[] = [
                'type' => 'danger',
                'message' => "Error al deshabilitar el plugin '{$plugin}'. Los plugins core no pueden deshabilitarse."
            ];
        }
    }
}

// Get all plugins
$plugins = $adminPlugins->getPlugins();
$corePlugins = ['auth_manual', 'user', 'roles', 'admin'];

// Prepare plugins for template
$preparedPlugins = [];
foreach ($plugins as $plugin) {
    $plugin['is_core'] = in_array($plugin['plugin'], $corePlugins);
    $plugin['enabled'] = (bool)$plugin['enabled'];
    $preparedPlugins[] = $plugin;
}

// Prepare template data
$templateData = [
    'page_title' => 'Plugins',
    'page_description' => 'Gestión de plugins y módulos del sistema',
    'admin_username' => $currentUser['username'],
    'primary_color' => $settings->get('primary_color', 'theme_iser', '#667eea'),
    'secondary_color' => $settings->get('secondary_color', 'theme_iser', '#764ba2'),
    'sections' => prepareNavigation($adminManager, 'plugins'),
    'alerts' => $alerts,
    'plugins' => $preparedPlugins,
];

// Render plugins template
$mustache = new Mustache_Engine([
    'loader' => new Mustache_Loader_FilesystemLoader(ISER_BASE_DIR . '/modules/Admin/templates'),
]);

$pluginsContent = $mustache->render('admin_plugins', $templateData);
$templateData['content'] = $pluginsContent;

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
