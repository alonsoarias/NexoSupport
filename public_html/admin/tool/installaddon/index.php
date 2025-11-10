<?php
/**
 * ISER - Install Addon Tool Controller
 *
 * @package    ISER
 * @copyright  2024 ISER
 * @license    Proprietary
 */

define('ISER_BASE_DIR', dirname(__DIR__, 4));

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
use ISER\Modules\Admin\Tool\InstallAddon\InstallAddon;

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
$installAddon = new InstallAddon($db, $adminPlugins, ISER_BASE_DIR . '/modules');

// Handle actions
$action = $_POST['action'] ?? '';
$alerts = [];
$installResult = null;

if ($action === 'install' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle addon installation
    if (!isset($_FILES['addon_file']) || $_FILES['addon_file']['error'] !== UPLOAD_ERR_OK) {
        $alerts[] = [
            'type' => 'danger',
            'message' => 'Error al subir el archivo'
        ];
    } else {
        $filePath = $_FILES['addon_file']['tmp_name'];

        // Install package
        $result = $installAddon->installPackage($filePath);

        if ($result['success']) {
            $installResult = [
                'success' => true,
                'plugin' => $result['plugin']
            ];

            // Log the action
            $adminManager->logAction(
                $currentUser['id'],
                'plugin_install',
                'admin_tool_installaddon',
                null,
                null,
                ['plugin' => $result['plugin']['plugin']]
            );

            $alerts[] = [
                'type' => 'success',
                'message' => "Plugin '{$result['plugin']['name']}' instalado correctamente"
            ];
        } else {
            $installResult = [
                'success' => false,
                'errors' => $result['errors']
            ];

            $alerts[] = [
                'type' => 'danger',
                'message' => 'Error al instalar el addon'
            ];
        }

        // Clean up uploaded file
        @unlink($filePath);
    }
}

if ($action === 'uninstall' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle addon uninstallation
    $pluginName = $_POST['plugin'] ?? '';

    if (empty($pluginName)) {
        $alerts[] = [
            'type' => 'danger',
            'message' => 'Plugin no especificado'
        ];
    } else {
        $result = $installAddon->uninstallPackage($pluginName);

        if ($result['success']) {
            // Log the action
            $adminManager->logAction(
                $currentUser['id'],
                'plugin_uninstall',
                'admin_tool_installaddon',
                null,
                null,
                ['plugin' => $pluginName]
            );

            $alerts[] = [
                'type' => 'success',
                'message' => "Plugin '{$pluginName}' desinstalado correctamente"
            ];
        } else {
            $alerts[] = [
                'type' => 'danger',
                'message' => 'Error: ' . implode(', ', $result['errors'])
            ];
        }
    }
}

// Get installed plugins
$installedPlugins = $adminPlugins->getPlugins();
$corePlugins = ['auth_manual', 'user', 'roles', 'admin', 'tool_mfa'];

foreach ($installedPlugins as &$plugin) {
    $plugin['is_core'] = in_array($plugin['plugin'], $corePlugins);
    $plugin['enabled'] = (bool)$plugin['enabled'];
}

// Prepare template data
$templateData = [
    'page_title' => 'Instalar Addon',
    'page_description' => 'Instalar nuevos plugins y módulos',
    'admin_username' => $currentUser['username'],
    'primary_color' => $settings->get('primary_color', 'theme_iser', '#667eea'),
    'secondary_color' => $settings->get('secondary_color', 'theme_iser', '#764ba2'),
    'sections' => prepareNavigation($adminManager, 'tools'),
    'alerts' => $alerts,
    'install_result' => $installResult,
    'installed_plugins' => $installedPlugins,
];

// Render template
$mustache = new Mustache_Engine([
    'loader' => new Mustache_Loader_FilesystemLoader(ISER_BASE_DIR . '/modules/Admin/Tool/InstallAddon/templates'),
]);

$content = $mustache->render('install_addon', $templateData);
$templateData['content'] = $content;

// Use admin layout
$adminLayoutLoader = new Mustache_Loader_FilesystemLoader(ISER_BASE_DIR . '/modules/Admin/templates');
$mustache->setLoader($adminLayoutLoader);

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
