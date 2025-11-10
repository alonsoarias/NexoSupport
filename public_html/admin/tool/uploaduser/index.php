<?php
/**
 * ISER - Upload User Tool Controller
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
use ISER\Modules\Admin\Tool\UploadUser\UploadUser;

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
$uploadUser = new UploadUser($db, $userManager);

// Handle actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$alerts = [];
$results = null;

if ($action === 'download_template') {
    // Download CSV template
    $template = $uploadUser->getTemplate();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users_template.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo $template;
    exit;
}

if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle CSV upload
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $alerts[] = [
            'type' => 'danger',
            'message' => 'Error al subir el archivo'
        ];
    } else {
        $filePath = $_FILES['csv_file']['tmp_name'];
        $updateExisting = isset($_POST['update_existing']);
        $sendEmails = isset($_POST['send_emails']);

        // Process CSV
        $result = $uploadUser->processCsv($filePath, $updateExisting, $sendEmails);

        if ($result['success']) {
            $results = [
                'success' => true,
                'stats' => $result['stats']
            ];

            // Log the action
            $adminManager->logAction(
                $currentUser['id'],
                'user_upload_csv',
                'admin_tool_uploaduser',
                null,
                null,
                [
                    'created' => $result['stats']['created'],
                    'updated' => $result['stats']['updated'],
                    'total' => $result['stats']['total']
                ]
            );

            if ($result['stats']['created'] > 0 || $result['stats']['updated'] > 0) {
                $alerts[] = [
                    'type' => 'success',
                    'message' => "Carga exitosa: {$result['stats']['created']} usuarios creados, {$result['stats']['updated']} actualizados"
                ];
            }
        } else {
            $results = [
                'success' => false,
                'stats' => $result['stats'],
                'errors' => $result['errors'] ?? []
            ];

            $alerts[] = [
                'type' => 'danger',
                'message' => 'Error al procesar el archivo CSV'
            ];
        }

        // Clean up uploaded file
        @unlink($filePath);
    }
}

// Get upload statistics
$uploadStats = $uploadUser->getUploadStats();
if ($uploadStats['last_upload'] > 0) {
    $uploadStats['last_upload_formatted'] = date('d/m/Y H:i', $uploadStats['last_upload']);
}

// Prepare template data
$templateData = [
    'page_title' => 'Carga Masiva de Usuarios',
    'page_description' => 'Importar usuarios desde archivo CSV',
    'admin_username' => $currentUser['username'],
    'primary_color' => $settings->get('primary_color', 'theme_iser', '#667eea'),
    'secondary_color' => $settings->get('secondary_color', 'theme_iser', '#764ba2'),
    'sections' => prepareNavigation($adminManager, 'tools'),
    'alerts' => $alerts,
    'upload_stats' => $uploadStats,
    'results' => $results,
];

// Render template
$mustache = new Mustache_Engine([
    'loader' => new Mustache_Loader_FilesystemLoader(ISER_BASE_DIR . '/modules/Admin/Tool/UploadUser/templates'),
]);

$content = $mustache->render('upload_user', $templateData);
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
