<?php
/**
 * NexoSupport - Main Routes Configuration
 *
 * This file contains public routes (no authentication required)
 *
 * @package    NexoSupport
 * @copyright  2024 ISER
 */

use ISER\Controllers\HomeController;
use ISER\Controllers\AuthController;
use ISER\Controllers\PasswordResetController;
use ISER\Controllers\UserProfileController;
use ISER\Controllers\UserPreferencesController;
use ISER\Controllers\LoginHistoryController;
use ISER\Controllers\SearchController;

// ===== PUBLIC ROUTES (No Authentication Required) =====

$router->get('/', function ($request) use ($database) {
    $controller = new HomeController($database);
    return $controller->index($request);
}, 'home');

// ===== AUTHENTICATION ROUTES =====

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

// ===== PROTECTED ROUTES (Authentication Required) =====

$router->get('/dashboard', function ($request) use ($database) {
    $controller = new HomeController($database);
    return $controller->dashboard($request);
}, 'dashboard');

// ===== USER PROFILE ROUTES =====

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

$router->get('/profile/view/{id}', function ($request) use ($database) {
    $uri = $request->getUri()->getPath();
    $parts = explode('/', trim($uri, '/'));
    $id = end($parts);
    $_GET['id'] = $id;
    $controller = new UserProfileController($database);
    return $controller->view($request);
}, 'profile.view');

// ===== USER PREFERENCES ROUTES =====

$router->get('/preferences', function ($request) use ($database) {
    $controller = new UserPreferencesController($database);
    return $controller->index($request);
}, 'preferences.index');

$router->post('/preferences', function ($request) use ($database) {
    $controller = new UserPreferencesController($database);
    return $controller->update($request);
}, 'preferences.update');

// ===== LOGIN HISTORY ROUTES =====

$router->get('/login-history', function ($request) use ($database) {
    $controller = new LoginHistoryController($database);
    return $controller->index($request);
}, 'login-history.index');

$router->post('/login-history/terminate/{id}', function ($request) use ($database) {
    $uri = $request->getUri()->getPath();
    $parts = explode('/', trim($uri, '/'));
    $id = end($parts);
    $_GET['id'] = $id;
    $controller = new LoginHistoryController($database);
    return $controller->terminate($request);
}, 'login-history.terminate');

// ===== SEARCH ROUTES =====

$router->get('/search/results', function ($request) use ($database) {
    $controller = new SearchController($database);
    return $controller->results($request);
}, 'search.results');

$router->get('/api/search/suggestions', function ($request) use ($database) {
    $controller = new SearchController($database);
    return $controller->suggestions($request);
}, 'api.search.suggestions');
