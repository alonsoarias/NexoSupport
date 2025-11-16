<?php
/**
 * NexoSupport - API Routes Configuration
 *
 * This file contains API routes
 *
 * @package    NexoSupport
 * @copyright  2024 ISER
 */

use ISER\Controllers\I18nApiController;
use ISER\Core\Http\Response;

// ===== API ROUTES GROUP =====

$router->group('/api', function ($router) use ($database) {

    // System Status API
    $router->get('/status', function ($request) {
        return Response::json([
            'status' => 'ok',
            'timestamp' => time(),
            'version' => NEXOSUPPORT_VERSION ?? '1.0.0'
        ]);
    }, 'api.status');

    // ===== I18N API =====

    // Get current locale
    $router->get('/i18n/current', function ($request) {
        $controller = new I18nApiController();
        return $controller->getCurrentLocale($request);
    }, 'api.i18n.current');

    // Set user locale
    $router->post('/i18n/locale', function ($request) {
        $controller = new I18nApiController();
        return $controller->setLocale($request);
    }, 'api.i18n.setLocale');

    // Get all translations for a locale
    $router->get('/i18n/{locale}', function ($request) {
        $controller = new I18nApiController();
        return $controller->getTranslations($request);
    }, 'api.i18n.translations');

    // Get translations for specific namespace
    $router->get('/i18n/{locale}/{namespace}', function ($request) {
        $controller = new I18nApiController();
        return $controller->getTranslations($request);
    }, 'api.i18n.namespace');
});
