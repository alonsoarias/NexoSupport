<?php
/**
 * NexoSupport - Admin Routes Configuration
 *
 * This file contains administrative routes (require admin authentication)
 *
 * @package    NexoSupport
 * @copyright  2024 ISER
 */

use ISER\Controllers\AdminController;
use ISER\Controllers\AdminSettingsController;
use ISER\Controllers\UserManagementController;
use ISER\Controllers\RoleController;
use ISER\Controllers\PermissionController;
use ISER\Controllers\AppearanceController;
use ISER\Controllers\ThemePreviewController;
use ISER\Controllers\LogViewerController;
use ISER\Controllers\AuditLogController;
use ISER\Controllers\AdminBackupController;
use ISER\Controllers\AdminEmailQueueController;
use ISER\Admin\AdminPlugins;

// ===== ADMIN ROUTES GROUP =====

$router->group('/admin', function ($router) use ($database) {

    // Admin Dashboard
    $router->get('', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->index($request);
    }, 'admin.index');

    // Settings
    $router->get('/settings', function ($request) use ($database) {
        $controller = new AdminSettingsController($database);
        return $controller->index($request);
    }, 'admin.settings');

    $router->post('/settings', function ($request) use ($database) {
        $controller = new AdminSettingsController($database);
        return $controller->update($request);
    }, 'admin.settings.update');

    $router->post('/settings/reset', function ($request) use ($database) {
        $controller = new AdminSettingsController($database);
        return $controller->reset($request);
    }, 'admin.settings.reset');

    // Reports
    $router->get('/reports', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->reports($request);
    }, 'admin.reports');

    // Security
    $router->get('/security', function ($request) use ($database) {
        $controller = new AdminController($database);
        return $controller->security($request);
    }, 'admin.security');

    // ===== USER MANAGEMENT =====
    $router->get('/users', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->index($request);
    }, 'admin.users');

    $router->get('/users/create', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->create($request);
    }, 'admin.users.create');

    $router->post('/users/store', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->store($request);
    }, 'admin.users.store');

    $router->post('/users/edit', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->edit($request);
    }, 'admin.users.edit');

    $router->post('/users/update', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->update($request);
    }, 'admin.users.update');

    $router->post('/users/delete', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->delete($request);
    }, 'admin.users.delete');

    $router->post('/users/restore', function ($request) use ($database) {
        $controller = new UserManagementController($database);
        return $controller->restore($request);
    }, 'admin.users.restore');

    // ===== ROLE MANAGEMENT =====
    $router->get('/roles', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->index($request);
    }, 'admin.roles');

    $router->get('/roles/create', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->create($request);
    }, 'admin.roles.create');

    $router->post('/roles/store', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->store($request);
    }, 'admin.roles.store');

    $router->post('/roles/edit', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->edit($request);
    }, 'admin.roles.edit');

    $router->post('/roles/update', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->update($request);
    }, 'admin.roles.update');

    $router->post('/roles/delete', function ($request) use ($database) {
        $controller = new RoleController($database);
        return $controller->delete($request);
    }, 'admin.roles.delete');

    // ===== PERMISSION MANAGEMENT =====
    $router->get('/permissions', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->index($request);
    }, 'admin.permissions');

    $router->get('/permissions/create', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->create($request);
    }, 'admin.permissions.create');

    $router->post('/permissions/store', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->store($request);
    }, 'admin.permissions.store');

    $router->post('/permissions/edit', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->edit($request);
    }, 'admin.permissions.edit');

    $router->post('/permissions/update', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->update($request);
    }, 'admin.permissions.update');

    $router->post('/permissions/delete', function ($request) use ($database) {
        $controller = new PermissionController($database);
        return $controller->delete($request);
    }, 'admin.permissions.delete');

    // ===== APPEARANCE/THEME =====
    $router->get('/appearance', function ($request) use ($database) {
        $controller = new AppearanceController($database);
        return $controller->index($request);
    }, 'admin.appearance');

    $router->post('/appearance/save', function ($request) use ($database) {
        $controller = new AppearanceController($database);
        return $controller->save($request);
    }, 'admin.appearance.save');

    $router->post('/appearance/reset', function ($request) use ($database) {
        $controller = new AppearanceController($database);
        return $controller->reset($request);
    }, 'admin.appearance.reset');

    $router->get('/appearance/preview', function ($request) use ($database) {
        $controller = new ThemePreviewController($database);
        return $controller->preview($request);
    }, 'admin.appearance.preview');

    // ===== LOGS =====
    $router->get('/logs', function ($request) use ($database) {
        $controller = new LogViewerController($database);
        return $controller->index($request);
    }, 'admin.logs');

    $router->get('/logs/view/{id}', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $id = end($parts);
        $_GET['id'] = $id;
        $controller = new LogViewerController($database);
        return $controller->view($request);
    }, 'admin.logs.view');

    $router->post('/logs/clear', function ($request) use ($database) {
        $controller = new LogViewerController($database);
        return $controller->clear($request);
    }, 'admin.logs.clear');

    // ===== AUDIT LOG =====
    $router->get('/audit', function ($request) use ($database) {
        $controller = new AuditLogController($database);
        return $controller->index($request);
    }, 'admin.audit');

    $router->get('/audit/export', function ($request) use ($database) {
        $controller = new AuditLogController($database);
        return $controller->export($request);
    }, 'admin.audit.export');

    // ===== BACKUP =====
    $router->get('/backup', function ($request) use ($database) {
        $controller = new AdminBackupController($database);
        return $controller->index($request);
    }, 'admin.backup');

    $router->post('/backup/create', function ($request) use ($database) {
        $controller = new AdminBackupController($database);
        return $controller->create($request);
    }, 'admin.backup.create');

    $router->post('/backup/restore', function ($request) use ($database) {
        $controller = new AdminBackupController($database);
        return $controller->restore($request);
    }, 'admin.backup.restore');

    $router->post('/backup/download', function ($request) use ($database) {
        $controller = new AdminBackupController($database);
        return $controller->download($request);
    }, 'admin.backup.download');

    $router->post('/backup/delete', function ($request) use ($database) {
        $controller = new AdminBackupController($database);
        return $controller->delete($request);
    }, 'admin.backup.delete');

    // ===== EMAIL QUEUE =====
    $router->get('/email-queue', function ($request) use ($database) {
        $controller = new AdminEmailQueueController($database);
        return $controller->index($request);
    }, 'admin.email-queue');

    $router->post('/email-queue/retry/{id}', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $id = end($parts);
        $_GET['id'] = $id;
        $controller = new AdminEmailQueueController($database);
        return $controller->retry($request);
    }, 'admin.email-queue.retry');

    $router->post('/email-queue/delete/{id}', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $id = end($parts);
        $_GET['id'] = $id;
        $controller = new AdminEmailQueueController($database);
        return $controller->delete($request);
    }, 'admin.email-queue.delete');

    // ===== PLUGINS =====
    $router->get('/plugins', function ($request) use ($database) {
        $controller = new AdminPlugins($database);
        return $controller->index($request);
    }, 'admin.plugins');

    $router->post('/plugins/install', function ($request) use ($database) {
        $controller = new AdminPlugins($database);
        return $controller->install($request);
    }, 'admin.plugins.install');

    $router->post('/plugins/uninstall', function ($request) use ($database) {
        $controller = new AdminPlugins($database);
        return $controller->uninstall($request);
    }, 'admin.plugins.uninstall');

    $router->post('/plugins/enable', function ($request) use ($database) {
        $controller = new AdminPlugins($database);
        return $controller->enable($request);
    }, 'admin.plugins.enable');

    $router->post('/plugins/disable', function ($request) use ($database) {
        $controller = new AdminPlugins($database);
        return $controller->disable($request);
    }, 'admin.plugins.disable');

    $router->get('/plugins/settings/{plugin}', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $plugin = end($parts);
        $_GET['plugin'] = $plugin;
        $controller = new AdminPlugins($database);
        return $controller->settings($request);
    }, 'admin.plugins.settings');

    $router->post('/plugins/settings/{plugin}', function ($request) use ($database) {
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $plugin = end($parts);
        $_GET['plugin'] = $plugin;
        $controller = new AdminPlugins($database);
        return $controller->saveSettings($request);
    }, 'admin.plugins.settings.save');
});
