<?php
/**
 * Página de gestión de temas
 * @package core
 */

require_once(__DIR__ . '/../../core/bootstrap.php');

use ISER\Core\Database\Database;
use ISER\Core\Config\SettingsManager;
use ISER\Core\Config\ConfigManager;
use ISER\Modules\Theme\Iser\ThemeIser;
use ISER\Modules\Auth\Middleware\AuthMiddleware;
use ISER\Modules\Admin\Middleware\AdminMiddleware;
use Monolog\Logger;

// Cargar configuración
$config = ConfigManager::getInstance();
$db = Database::getInstance(
    $config->get('DB_HOST'),
    $config->get('DB_NAME'),
    $config->get('DB_USER'),
    $config->get('DB_PASS')
);

$logger = new Logger('theme');
$settings = new SettingsManager($db, $logger);

// Middleware de autenticación y admin
$authMiddleware = new AuthMiddleware($db, $logger);
$adminMiddleware = new AdminMiddleware($db, $logger);

// Verificar que el usuario es admin
$adminMiddleware->requireAdmin();
$userId = $_SESSION['user_id'] ?? null;

// Inicializar tema
$theme = new ThemeIser($db, $settings, $logger, $userId);
$theme->init();

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'preview':
        // Vista previa del tema
        handlePreview($theme);
        break;

    case 'customize':
        // Personalización del tema
        handleCustomize($theme);
        break;

    case 'settings':
        // Configuración del tema
        handleSettings($theme);
        break;

    default:
        // Lista de temas disponibles
        handleList($theme);
}

/**
 * Manejar listado de temas
 */
function handleList($theme) {
    $data = [
        'page_title' => 'Temas',
        'themes' => [
            [
                'name' => 'ISER',
                'version' => '1.0.0',
                'author' => 'ISER Desarrollo',
                'description' => 'Tema oficial del ISER basado en Bootstrap 5',
                'active' => true,
                'preview_url' => '/theme/index.php?action=preview&theme=iser',
                'customize_url' => '/theme/index.php?action=customize&theme=iser'
            ]
        ]
    ];

    echo $theme->renderLayout('admin', $data);
}

/**
 * Manejar vista previa
 */
function handlePreview($theme) {
    $themeName = $_GET['theme'] ?? 'iser';

    $data = [
        'page_title' => 'Vista Previa del Tema',
        'content' => '<div class="alert alert-info">Vista previa del tema ' . htmlspecialchars($themeName) . '</div>'
    ];

    echo $theme->renderLayout('fullwidth', $data);
}

/**
 * Manejar personalización
 */
function handleCustomize($theme) {
    $settings = $theme->getThemeSettings();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Guardar cambios
        $newSettings = [
            'colors' => [
                'primary' => $_POST['primary_color'] ?? $settings['colors']['primary'],
                'secondary' => $_POST['secondary_color'] ?? $settings['colors']['secondary']
            ]
        ];

        if ($theme->updateThemeSettings($newSettings)) {
            header('Location: /theme/index.php?action=customize&success=1');
            exit;
        }
    }

    $data = [
        'page_title' => 'Personalizar Tema',
        'settings' => $settings,
        'content' => renderCustomizeForm($settings)
    ];

    echo $theme->renderLayout('admin', $data);
}

/**
 * Renderizar formulario de personalización
 */
function renderCustomizeForm($settings) {
    ob_start();
    ?>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Colores del Tema</h5>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="primary_color" class="form-label">Color Primario</label>
                        <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" value="<?= htmlspecialchars($settings['colors']['primary']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="secondary_color" class="form-label">Color Secundario</label>
                        <input type="color" class="form-control form-control-color" id="secondary_color" name="secondary_color" value="<?= htmlspecialchars($settings['colors']['secondary']) ?>" required>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar Cambios
                    </button>
                    <a href="/theme/index.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Manejar configuración
 */
function handleSettings($theme) {
    $data = [
        'page_title' => 'Configuración del Tema',
        'content' => '<div class="alert alert-info">Configuración del tema próximamente</div>'
    ];

    echo $theme->renderLayout('admin', $data);
}
