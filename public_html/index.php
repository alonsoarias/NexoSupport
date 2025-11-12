<?php

declare(strict_types=1);

/**
 * ISER Authentication System - Front Controller
 * Single entry point for all requests (PSR-7 compliant)
 *
 * @package Core
 * @author ISER Desarrollo
 * @license Propietario
 */

// Define base directory
define('BASE_DIR', dirname(__DIR__));
define('ENV_FILE', BASE_DIR . '/.env');

/**
 * Verificación de instalación (al estilo Moodle/WordPress)
 *
 * Sistema de 3 niveles:
 * 1. Verificar que existe .env
 * 2. Verificar que .env contiene INSTALLED=true
 * 3. Verificar que la BD está accesible
 */
function checkInstallation(): bool {
    // Nivel 1: Verificar que existe .env
    if (!file_exists(ENV_FILE)) {
        return false;
    }

    // Nivel 2: Verificar que .env contiene INSTALLED=true
    $envContent = file_get_contents(ENV_FILE);
    if ($envContent === false) {
        return false;
    }

    // Parsear .env y buscar INSTALLED=true
    $lines = explode("\n", $envContent);
    $installed = false;
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        if (strpos($line, 'INSTALLED=') === 0) {
            $value = trim(str_replace('INSTALLED=', '', $line));
            $installed = ($value === 'true');
            break;
        }
    }

    if (!$installed) {
        return false;
    }

    // Nivel 3: Verificar acceso a BD (básico)
    // No verificamos aquí la BD para no ralentizar cada request
    // Si .env existe y tiene INSTALLED=true, asumimos instalación completa

    return true;
}

// Verificar instalación
if (!checkInstallation()) {
    // No instalado: redirigir al instalador wrapper
    // El instalador está en public_html/install.php (accesible vía web)
    header('Location: /install.php');
    exit;
}

// Cargar autoloader
if (!file_exists(BASE_DIR . '/vendor/autoload.php')) {
    http_response_code(500);
    die('<h1>Dependency Error</h1><p>Composer dependencies not installed. Run: composer install</p>');
}

require_once BASE_DIR . '/vendor/autoload.php';

// Iniciar sesión
session_start();

use ISER\Core\Bootstrap;
use ISER\Core\Routing\Router;
use ISER\Core\Routing\RouteNotFoundException;
use ISER\Core\Http\Request;
use ISER\Core\Http\Response;
use ISER\Controllers\HomeController;
use ISER\Controllers\AuthController;
use ISER\Controllers\PasswordResetController;
use ISER\Controllers\AdminController;
use ISER\Controllers\UserManagementController;
use ISER\Controllers\RoleController;
use ISER\Controllers\PermissionController;
use ISER\Controllers\I18nApiController;
use ISER\Controllers\AppearanceController;
use ISER\Controllers\ThemePreviewController;
use ISER\Controllers\UserProfileController;
use ISER\Controllers\UserPreferencesController;
use ISER\Controllers\LoginHistoryController;
use ISER\Controllers\AuditLogController;
use ISER\Controllers\LogViewerController;
use ISER\Controllers\AdminBackupController;
use ISER\Controllers\AdminEmailQueueController;
use ISER\Controllers\AdminSettingsController;
use ISER\Controllers\SearchController;
use ISER\Admin\AdminPlugins;

// Inicializar la aplicación
try {
    $app = new Bootstrap(BASE_DIR);
    $app->init();
} catch (Exception $e) {
    error_log('Bootstrap Error: ' . $e->getMessage());
    error_log('Bootstrap Stack Trace: ' . $e->getTraceAsString());
    http_response_code(500);

    // Mostrar error detallado temporalmente para debugging
    die('<h1>System Error</h1><p>Failed to initialize the application.</p><pre style="text-align:left;background:#f5f5f5;padding:15px;margin:20px;border:1px solid #ddd;overflow:auto;max-width:800px;margin-left:auto;margin-right:auto;">'
        . '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . "\n\n"
        . '<strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . "\n\n"
        . '<strong>Stack Trace:</strong>' . "\n" . htmlspecialchars($e->getTraceAsString())
        . '</pre>');
}

// Obtener instancia de Database para inyección de dependencias
$database = $app->getDatabase();

// Crear router
$router = new Router();

// ===== RUTAS PÚBLICAS =====
$router->get('/', function ($request) use ($database) {
    $controller = new HomeController($database);
    return $controller->index($request);
}, 'home');

$router->get('/login', function ($request) use ($database) {
    $controller = new AuthController($database);
    return $controller->showLogin($request);
}, 'login');

$router->post('/login', function ($request) use ($database) {
    $controller = new AuthController($database);
    return $controller->processLogin($request);
}, 'login.process');

$router->get('/logout', function ($request) use ($database) {
    $controller = new AuthController($database);
    return $controller->logout($request);
}, 'logout');

// ===== PASSWORD RESET ROUTES =====
$router->get('/forgot-password', function ($request) use ($database) {
    $controller = new PasswordResetController($database);
    return $controller->showForgotForm($request);
}, 'forgot-password');

$router->post('/forgot-password', function ($request) use ($database) {
    $controller = new PasswordResetController($database);
    return $controller->sendResetLink($request);
}, 'forgot-password.send');

$router->get('/reset-password', function ($request) use ($database) {
    $controller = new PasswordResetController($database);
    return $controller->showResetForm($request);
}, 'reset-password');

$router->post('/reset-password', function ($request) use ($database) {
    $controller = new PasswordResetController($database);
    return $controller->resetPassword($request);
}, 'reset-password.process');

// ===== RUTAS PROTEGIDAS =====
$router->get('/dashboard', function ($request) use ($database) {
    $controller = new HomeController($database);
    return $controller->dashboard($request);
}, 'dashboard');

// ===== USER PROFILE ROUTES (require authentication) =====
$router->get('/profile', function ($request) use ($database) {
    $controller = new UserProfileController($database);
    return $controller->index($request);
}, 'profile.index');

$router->get('/profile/edit', function ($request) use ($database) {
    $controller = new UserProfileController($database);
    return $controller->edit($request);
}, 'profile.edit');

$router->post('/profile/edit', function ($request) use ($database) {
    $controller = new UserProfileController($database);
    return $controller->update($request);
}, 'profile.update');

// View any user's profile (admin only)
$router->get('/profile/view/{id}', function ($request) use ($database) {
    $uri = $request->getUri()->getPath();
    $parts = explode('/', trim($uri, '/'));
    $userId = (int)($parts[2] ?? 0);
    $controller = new UserProfileController($database);
    return $controller->viewProfile($request, $userId);
}, 'profile.view');

// ===== USER PREFERENCES ROUTES (require authentication) =====
$router->get('/preferences', function ($request) use ($database) {
    $controller = new UserPreferencesController($database);
    return $controller->index($request);
}, 'preferences.index');

$router->post('/preferences', function ($request) use ($database) {
    $controller = new UserPreferencesController($database);
    return $controller->update($request);
}, 'preferences.update');

// ===== USER LOGIN HISTORY ROUTES (require authentication) =====
$router->get('/login-history', function ($request) use ($database) {
    $controller = new LoginHistoryController($database);
    return $controller->index($request);
}, 'login-history.index');

$router->post('/login-history/terminate/{id}', function ($request) use ($database) {
    $uri = $request->getUri()->getPath();
    $parts = explode('/', trim($uri, '/'));
    $loginId = (int)($parts[2] ?? 0);
    $request = $request->withAttribute('login_id', $loginId);
    $controller = new LoginHistoryController($database);
    return $controller->terminate($request);
}, 'login-history.terminate');

// ===== RUTAS DE ADMINISTRACIÓN =====
$router->group('/admin', function (Router $router) use ($database) {
    // Panel principal de administración
    $router->get('', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->index($request);
    }, 'admin');

    // ===== CONFIGURACIÓN DEL SISTEMA (FASE 8) =====
    // Vista de configuración del sistema
    $router->get('/settings', function ($request) use ($database) {
        $controller = new AdminSettingsController($database);
        return $controller->index($request);
    }, 'admin.settings.index');

    // Actualizar configuración
    $router->post('/settings', function ($request) use ($database) {
        $controller = new AdminSettingsController($database);
        return $controller->update($request);
    }, 'admin.settings.update');

    // Resetear configuración a valores predeterminados
    $router->post('/settings/reset', function ($request) use ($database) {
        $controller = new AdminSettingsController($database);
        return $controller->reset($request);
    }, 'admin.settings.reset');

    // Reportes del sistema
    $router->get('/reports', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->reports($request);
    }, 'admin.reports');

    // Seguridad del sistema
    $router->get('/security', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->security($request);
    }, 'admin.security');

    // ===== GESTIÓN DE USUARIOS =====
    // Lista de usuarios
    $router->get('/users', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->index($request);
    }, 'admin.users.index');

    // Formulario de creación
    $router->get('/users/create', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->create($request);
    }, 'admin.users.create');

    // Procesar creación
    $router->post('/users/store', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->store($request);
    }, 'admin.users.store');

    // Formulario de edición (POST para no exponer ID en URL)
    $router->post('/users/edit', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->edit($request);
    }, 'admin.users.edit');

    // Procesar actualización (ID viene en el body del formulario)
    $router->post('/users/update', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->update($request);
    }, 'admin.users.update');

    // Eliminar usuario (soft delete) (ID viene en el body)
    $router->post('/users/delete', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->delete($request);
    }, 'admin.users.delete');

    // Restaurar usuario eliminado (ID viene en el body)
    $router->post('/users/restore', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->restore($request);
    }, 'admin.users.restore');

    // ===== GESTIÓN DE ROLES =====
    // Lista de roles
    $router->get('/roles', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->index($request);
    }, 'admin.roles.index');

    // Formulario de creación
    $router->get('/roles/create', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->create($request);
    }, 'admin.roles.create');

    // Procesar creación
    $router->post('/roles/store', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->store($request);
    }, 'admin.roles.store');

    // Formulario de edición (POST para no exponer ID en URL)
    $router->post('/roles/edit', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->edit($request);
    }, 'admin.roles.edit');

    // Procesar actualización (ID viene en el body del formulario)
    $router->post('/roles/update', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->update($request);
    }, 'admin.roles.update');

    // Eliminar rol (ID viene en el body)
    $router->post('/roles/delete', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->delete($request);
    }, 'admin.roles.delete');

    // ===== GESTIÓN DE PERMISOS =====
    // Lista de permisos
    $router->get('/permissions', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->index($request);
    }, 'admin.permissions.index');

    // Formulario de creación
    $router->get('/permissions/create', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->create($request);
    }, 'admin.permissions.create');

    // Procesar creación
    $router->post('/permissions/store', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->store($request);
    }, 'admin.permissions.store');

    // Formulario de edición (POST para no exponer ID en URL)
    $router->post('/permissions/edit', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->edit($request);
    }, 'admin.permissions.edit');

    // Procesar actualización (ID viene en el body del formulario)
    $router->post('/permissions/update', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->update($request);
    }, 'admin.permissions.update');

    // Eliminar permiso (ID viene en el body)
    $router->post('/permissions/delete', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->delete($request);
    }, 'admin.permissions.delete');

    // ===== CONFIGURACIÓN DE APARIENCIA (FASE 4) =====
    // Página de configuración de apariencia
    $router->get('/appearance', function ($request) use ($database) {
        $controller = new AppearanceController($database);
        return $controller->index($request);
    }, 'admin.appearance.index');

    // Guardar configuración de apariencia
    $router->post('/appearance/save', function ($request) use ($database) {
        $controller = new AppearanceController($database);
        return $controller->save($request);
    }, 'admin.appearance.save');

    // Restaurar configuración por defecto
    $router->post('/appearance/reset', function ($request) use ($database) {
        $controller = new AppearanceController($database);
        return $controller->reset($request);
    }, 'admin.appearance.reset');

    // ===== THEME PREVIEW SYSTEM (FASE 8) =====
    // Display theme preview page with side-by-side comparison
    $router->get('/theme/preview', function ($request) use ($database) {
        $controller = new ThemePreviewController($database);
        return $controller->preview($request);
    }, 'admin.theme.preview');

    // Switch to different theme for preview (session-based)
    $router->post('/theme/switch', function ($request) use ($database) {
        $controller = new ThemePreviewController($database);
        return $controller->switch($request);
    }, 'admin.theme.switch');

    // Apply selected preview theme to account
    $router->post('/theme/apply', function ($request) use ($database) {
        $controller = new ThemePreviewController($database);
        return $controller->apply($request);
    }, 'admin.theme.apply');

    // Reset preview to original theme
    $router->post('/theme/reset-preview', function ($request) use ($database) {
        $controller = new ThemePreviewController($database);
        return $controller->resetPreview($request);
    }, 'admin.theme.reset-preview');

    // ===== LOGIN HISTORY (Admin View) =====
    // Admin view of all logins
    $router->get('/login-history', function ($request) use ($database) {
        $controller = new LoginHistoryController($database);
        return $controller->adminIndex($request);
    }, 'admin.login-history.index');

    // ===== SYSTEM LOGS VIEWER =====
    // View logs page with filters and pagination
    $router->get('/logs', function ($request) use ($database) {
        $controller = new LogViewerController($database);
        return $controller->index($request);
    }, 'admin.logs.index');

    // View single log entry details
    $router->get('/logs/view/{id}', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $logId = (int)($parts[2] ?? 0);
        $controller = new LogViewerController($database);
        return $controller->view($request, $logId);
    }, 'admin.logs.view');

    // Clear old logs (admin action)
    $router->post('/logs/clear', function ($request) use ($database) {
        $controller = new LogViewerController($database);
        return $controller->clear($request);
    }, 'admin.logs.clear');

    // Download logs as CSV or JSON
    $router->get('/logs/download', function ($request) use ($database) {
        $controller = new LogViewerController($database);
        return $controller->download($request);
    }, 'admin.logs.download');

    // ===== GESTIÓN DE PLUGINS (FASE 2) =====
    // Listar plugins
    $router->get('/plugins', function ($request) use ($database) {
        $controller = new AdminPlugins($database);
        $queryParams = $request->getQueryParams();
        return $controller->index($queryParams);
    }, 'admin.plugins.index');

    // Descubrir plugins
    $router->post('/plugins/discover', function ($request) use ($database) {
        $controller = new AdminPlugins($database);
        return $controller->discover();
    }, 'admin.plugins.discover');

    // Habilitar plugin
    $router->post('/plugins/{slug}/enable', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $slug = $parts[2] ?? '';
        $controller = new AdminPlugins($database);
        return $controller->enable($slug);
    }, 'admin.plugins.enable');

    // Deshabilitar plugin
    $router->post('/plugins/{slug}/disable', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $slug = $parts[2] ?? '';
        $controller = new AdminPlugins($database);
        return $controller->disable($slug);
    }, 'admin.plugins.disable');

    // Desinstalar plugin
    $router->post('/plugins/{slug}/uninstall', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $slug = $parts[2] ?? '';
        $controller = new AdminPlugins($database);
        return $controller->uninstall($slug);
    }, 'admin.plugins.uninstall');

    // Ver detalles de plugin
    $router->get('/plugins/{slug}', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $slug = $parts[2] ?? '';
        $controller = new AdminPlugins($database);
        return $controller->show($slug);
    }, 'admin.plugins.show');

    // ===== AUDIT LOG VIEWER =====
    // Lista de logs de auditoría
    $router->get('/audit', function ($request) use ($database) {
        $controller = new AuditLogController($database);
        return $controller->index($request);
    }, 'admin.audit.index');

    // Ver detalles de entrada de auditoría
    $router->get('/audit/view/{id}', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $id = (int)($parts[3] ?? 0);
        $controller = new AuditLogController($database);
        return $controller->view($request, $id);
    }, 'admin.audit.view');

    // Exportar logs de auditoría a CSV
    $router->get('/audit/export', function ($request) use ($database) {
        $controller = new AuditLogController($database);
        return $controller->export($request);
    }, 'admin.audit.export');

    // ===== EMAIL QUEUE MANAGER (FASE 8) =====
    // List queued emails
    $router->get('/email-queue', function ($request) use ($database) {
        $controller = new AdminEmailQueueController($database);
        return $controller->index($request);
    }, 'admin.email-queue.index');

    // View email details
    $router->get('/email-queue/view/{id}', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $id = (int)($parts[3] ?? 0);
        $controller = new AdminEmailQueueController($database);
        return $controller->view($request, $id);
    }, 'admin.email-queue.view');

    // Retry failed email
    $router->post('/email-queue/retry/{id}', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $id = (int)($parts[3] ?? 0);
        $controller = new AdminEmailQueueController($database);
        return $controller->retry($request, $id);
    }, 'admin.email-queue.retry');

    // Delete email from queue
    $router->post('/email-queue/delete/{id}', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $id = (int)($parts[3] ?? 0);
        $controller = new AdminEmailQueueController($database);
        return $controller->delete($request, $id);
    }, 'admin.email-queue.delete');

    // Clear old emails
    $router->post('/email-queue/clear', function ($request) use ($database) {
        $controller = new AdminEmailQueueController($database);
        return $controller->clear($request);
    }, 'admin.email-queue.clear');

    // ===== DATABASE BACKUP MANAGER (FASE 8) =====
    // Show backup list
    $router->get('/backup', function ($request) use ($database) {
        $controller = new AdminBackupController($database);
        return $controller->index($request);
    }, 'admin.backup.index');

    // Create new backup
    $router->post('/backup/create', function ($request) use ($database) {
        $controller = new AdminBackupController($database);
        return $controller->create($request);
    }, 'admin.backup.create');

    // Download backup file
    $router->get('/backup/download/{filename}', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $filename = $parts[3] ?? '';
        $request = $request->withAttribute('filename', $filename);
        $controller = new AdminBackupController($database);
        return $controller->download($request);
    }, 'admin.backup.download');

    // Delete backup file
    $router->post('/backup/delete/{filename}', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $filename = $parts[3] ?? '';
        $request = $request->withAttribute('filename', $filename);
        $controller = new AdminBackupController($database);
        return $controller->delete($request);
    }, 'admin.backup.delete');
});

// ===== RUTAS DE REPORTES =====
$router->get('/report', function ($request) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
        return Response::redirect('/login');
    }
    ob_start();
    require BASE_DIR . '/app/Report/index.php';
    $content = ob_get_clean();
    return Response::html($content);
}, 'report');

// ===== RUTAS DE THEME =====
$router->get('/theme', function ($request) {
    ob_start();
    require BASE_DIR . '/app/Theme/index.php';
    $content = ob_get_clean();
    return Response::html($content);
}, 'theme');

// ===== SEARCH ROUTES (FASE 8) =====
// Advanced search form
$router->get('/search', function ($request) use ($database) {
    $controller = new SearchController($database);
    return $controller->index($request);
}, 'search.index');

// Search results
$router->get('/search/results', function ($request) use ($database) {
    $controller = new SearchController($database);
    return $controller->results($request);
}, 'search.results');

// Search suggestions API
$router->get('/api/search/suggestions', function ($request) use ($database) {
    $controller = new SearchController($database);
    return $controller->suggestions($request);
}, 'api.search.suggestions');

// ===== RUTAS API =====
$router->group('/api', function (Router $router) {
    // API de estado del sistema
    $router->get('/status', function ($request) {
        return Response::json(['status' => 'ok', 'timestamp' => time()]);
    }, 'api.status');

    // ===== API de Internacionalización =====
    // Obtener locale actual
    $router->get('/i18n/current', function ($request) {
        $controller = new I18nApiController();
        return $controller->getCurrentLocale($request);
    }, 'api.i18n.current');

    // Establecer locale del usuario
    $router->post('/i18n/locale', function ($request) {
        $controller = new I18nApiController();
        return $controller->setLocale($request);
    }, 'api.i18n.setLocale');

    // Obtener todas las traducciones de un locale
    $router->get('/i18n/{locale}', function ($request) {
        $controller = new I18nApiController();
        return $controller->getTranslations($request);
    }, 'api.i18n.translations');

    // Obtener traducciones de un namespace específico
    $router->get('/i18n/{locale}/{namespace}', function ($request) {
        $controller = new I18nApiController();
        return $controller->getTranslations($request);
    }, 'api.i18n.namespace');
});

// Ejecutar el router con PSR-7
try {
    $request = Request::createFromGlobals();
    $response = $router->dispatch($request);
    $response->send();
} catch (RouteNotFoundException $e) {
    // Página 404
    error_log('Route not found: ' . $e->getMessage());
    $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada</title>
    <link rel="stylesheet" href="/assets/css/iser-theme.css">
</head>
<body>
    <div class="container" style="text-align: center; padding: 100px 20px;">
        <h1 style="color: var(--iser-red); font-size: 4rem;">404</h1>
        <h2>Página no encontrada</h2>
        <p style="color: var(--text-secondary); margin: 20px 0;">La página que buscas no existe.</p>
        <a href="/" class="btn btn-primary">Volver al inicio</a>
    </div>
</body>
</html>';
    Response::html($html, 404)->send();
} catch (Exception $e) {
    // Error 500
    error_log('Server Error: ' . $e->getMessage());
    error_log('Server Error Stack Trace: ' . $e->getTraceAsString());

    // Mostrar error detallado temporalmente para debugging
    $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Error del Servidor</title>
    <link rel="stylesheet" href="/assets/css/iser-theme.css">
</head>
<body>
    <div class="container" style="text-align: center; padding: 100px 20px;">
        <h1 style="color: var(--iser-red); font-size: 4rem;">500</h1>
        <h2>Error del Servidor</h2>
        <p style="color: var(--text-secondary); margin: 20px 0;">Ocurrió un error inesperado.</p>
        <div style="text-align: left; background: #f5f5f5; padding: 15px; margin: 20px auto; border: 1px solid #ddd; max-width: 800px; overflow: auto;">
            <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '<br><br>
            <strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '<br><br>
            <strong>Stack Trace:</strong><br><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>
        </div>
        <a href="/" class="btn btn-primary">Volver al inicio</a>
    </div>
</body>
</html>';
    Response::html($html, 500)->send();
}
