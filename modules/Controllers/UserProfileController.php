<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\View\MustacheRenderer;
use ISER\Core\I18n\Translator;
use ISER\Core\Http\Response;
use ISER\Core\Database\Database;
use ISER\User\UserManager;
use ISER\User\UserProfile;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * User Profile Controller
 * Manages user profile viewing and editing
 */
class UserProfileController
{
    private MustacheRenderer $renderer;
    private Translator $translator;
    private Database $db;
    private UserManager $userManager;
    private UserProfile $profileManager;

    public function __construct(Database $db)
    {
        $this->renderer = MustacheRenderer::getInstance();
        $this->translator = Translator::getInstance();
        $this->db = $db;
        $this->userManager = new UserManager($db);
        $this->profileManager = new UserProfile($db);
    }

    /**
     * Show current user's profile
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication
        if (!$this->isAuthenticated()) {
            return Response::redirect('/login');
        }

        $userId = $_SESSION['user_id'];

        // Get user data
        $user = $this->userManager->getUserById($userId);
        if (!$user) {
            $_SESSION['error'] = $this->translator->translate('errors.user_not_found');
            return Response::redirect('/dashboard');
        }

        // Get profile data
        $profile = $this->profileManager->getProfile($userId);

        // Get user roles
        $roles = $this->userManager->getUserRoles($userId);

        // Format dates
        $user['created_at_formatted'] = date('d/m/Y H:i', $user['created_at']);
        $user['updated_at_formatted'] = isset($user['updated_at'])
            ? date('d/m/Y H:i', $user['updated_at'])
            : 'N/A';

        if ($profile && isset($profile['timemodified'])) {
            $profile['timemodified_formatted'] = date('d/m/Y H:i', $profile['timemodified']);
        }

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('profile.title'),
            'app_name' => 'NexoSupport',
            'user' => $user,
            'profile' => $profile ?: [],
            'roles' => $roles,
            'success' => $_SESSION['success'] ?? null,
            'error' => $_SESSION['error'] ?? null,
            'content' => $this->renderer->render('user/profile/index', [
                'user' => $user,
                'profile' => $profile ?: [],
                'roles' => $roles,
                'trans' => [
                    'personal_info' => $this->translator->translate('profile.personal_info'),
                    'account_info' => $this->translator->translate('profile.account_info'),
                    'edit_profile' => $this->translator->translate('profile.edit_title'),
                    'username' => $this->translator->translate('profile.username'),
                    'email' => $this->translator->translate('profile.email'),
                    'first_name' => $this->translator->translate('profile.first_name'),
                    'last_name' => $this->translator->translate('profile.last_name'),
                    'phone' => $this->translator->translate('profile.phone'),
                    'address' => $this->translator->translate('profile.address'),
                    'city' => $this->translator->translate('profile.city'),
                    'country' => $this->translator->translate('profile.country'),
                    'postal_code' => $this->translator->translate('profile.postal_code'),
                    'bio' => $this->translator->translate('profile.bio'),
                    'member_since' => $this->translator->translate('profile.member_since'),
                    'last_updated' => $this->translator->translate('profile.last_updated'),
                    'status' => $this->translator->translate('profile.account_status'),
                    'roles' => $this->translator->translate('profile.roles', 'Roles'),
                ],
            ]),
        ];

        unset($_SESSION['success']);
        unset($_SESSION['error']);

        $html = $this->renderer->render('layouts/app', $data);
        return Response::html($html);
    }

    /**
     * Show edit form for current user
     */
    public function edit(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication
        if (!$this->isAuthenticated()) {
            return Response::redirect('/login');
        }

        $userId = $_SESSION['user_id'];

        // Get user data
        $user = $this->userManager->getUserById($userId);
        if (!$user) {
            $_SESSION['error'] = $this->translator->translate('errors.user_not_found');
            return Response::redirect('/dashboard');
        }

        // Get profile data
        $profile = $this->profileManager->getProfile($userId);

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('profile.edit_title'),
            'app_name' => 'NexoSupport',
            'user' => $user,
            'profile' => $profile ?: [],
            'errors' => $_SESSION['errors'] ?? [],
            'content' => $this->renderer->render('user/profile/edit', [
                'user' => $user,
                'profile' => $profile ?: [],
                'errors' => $_SESSION['errors'] ?? [],
                'trans' => [
                    'edit_profile' => $this->translator->translate('profile.edit_title'),
                    'personal_info' => $this->translator->translate('profile.personal_info'),
                    'phone' => $this->translator->translate('profile.phone'),
                    'mobile' => $this->translator->translate('profile.mobile', 'Móvil'),
                    'address' => $this->translator->translate('profile.address'),
                    'city' => $this->translator->translate('profile.city'),
                    'state' => $this->translator->translate('profile.state', 'Estado/Provincia'),
                    'country' => $this->translator->translate('profile.country'),
                    'postal_code' => $this->translator->translate('profile.postal_code'),
                    'bio' => $this->translator->translate('profile.bio'),
                    'avatar_url' => $this->translator->translate('profile.avatar_url', 'URL del Avatar'),
                    'website' => $this->translator->translate('profile.website'),
                    'linkedin' => $this->translator->translate('profile.linkedin', 'LinkedIn'),
                    'twitter' => $this->translator->translate('profile.twitter', 'Twitter'),
                    'save_profile' => $this->translator->translate('profile.update_profile'),
                    'cancel' => $this->translator->translate('profile.cancel'),
                ],
            ]),
        ];

        unset($_SESSION['errors']);

        $html = $this->renderer->render('layouts/app', $data);
        return Response::html($html);
    }

    /**
     * Update current user's profile
     */
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication
        if (!$this->isAuthenticated()) {
            return Response::redirect('/login');
        }

        $userId = $_SESSION['user_id'];
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            $_SESSION['error'] = $this->translator->translate('errors.invalid_data');
            return Response::redirect('/profile/edit');
        }

        // Prepare profile data
        $profileData = [];

        // Add fields that are allowed to be updated
        $allowedFields = [
            'phone',
            'address',
            'city',
            'country',
            'postalcode',
            'bio',
            'website',
            'linkedin',
            'twitter',
            'institution',
            'department',
            'position',
        ];

        foreach ($allowedFields as $field) {
            if (isset($body[$field])) {
                $profileData[$field] = trim($body[$field]);
            }
        }

        // Validate profile data
        $errors = $this->validateProfileData($profileData);

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            return Response::redirect('/profile/edit');
        }

        // Update profile
        try {
            $success = $this->profileManager->updateProfile($userId, $profileData);

            if ($success) {
                $_SESSION['success'] = $this->translator->translate('profile.updated_message');
                return Response::redirect('/profile');
            } else {
                $_SESSION['error'] = $this->translator->translate('errors.update_failed');
                return Response::redirect('/profile/edit');
            }
        } catch (\Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            $_SESSION['error'] = $this->translator->translate('errors.system_error');
            return Response::redirect('/profile/edit');
        }
    }

    /**
     * View any user's profile (admin only)
     */
    public function viewProfile(ServerRequestInterface $request, int $userId): ResponseInterface
    {
        // Check authentication
        if (!$this->isAuthenticated()) {
            return Response::redirect('/login');
        }

        // Check if user is admin
        $currentUserId = $_SESSION['user_id'];
        if (!$this->isAdmin($currentUserId)) {
            $_SESSION['error'] = $this->translator->translate('errors.access_denied');
            return Response::redirect('/dashboard');
        }

        // Get user data
        $user = $this->userManager->getUserById($userId);
        if (!$user) {
            $_SESSION['error'] = $this->translator->translate('errors.user_not_found');
            return Response::redirect('/admin/users');
        }

        // Get profile data
        $profile = $this->profileManager->getProfile($userId);

        // Get user roles
        $roles = $this->userManager->getUserRoles($userId);

        // Format dates
        $user['created_at_formatted'] = date('d/m/Y H:i', $user['created_at']);
        $user['updated_at_formatted'] = isset($user['updated_at'])
            ? date('d/m/Y H:i', $user['updated_at'])
            : 'N/A';

        if ($profile && isset($profile['timemodified'])) {
            $profile['timemodified_formatted'] = date('d/m/Y H:i', $profile['timemodified']);
        }

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('profile.view_title'),
            'app_name' => 'NexoSupport',
            'user' => $user,
            'profile' => $profile ?: [],
            'roles' => $roles,
            'is_admin_view' => true,
            'content' => $this->renderer->render('user/profile/index', [
                'user' => $user,
                'profile' => $profile ?: [],
                'roles' => $roles,
                'is_admin_view' => true,
                'trans' => [
                    'personal_info' => $this->translator->translate('profile.personal_info'),
                    'account_info' => $this->translator->translate('profile.account_info'),
                    'username' => $this->translator->translate('profile.username'),
                    'email' => $this->translator->translate('profile.email'),
                    'first_name' => $this->translator->translate('profile.first_name'),
                    'last_name' => $this->translator->translate('profile.last_name'),
                    'phone' => $this->translator->translate('profile.phone'),
                    'address' => $this->translator->translate('profile.address'),
                    'city' => $this->translator->translate('profile.city'),
                    'country' => $this->translator->translate('profile.country'),
                    'postal_code' => $this->translator->translate('profile.postal_code'),
                    'bio' => $this->translator->translate('profile.bio'),
                    'member_since' => $this->translator->translate('profile.member_since'),
                    'last_updated' => $this->translator->translate('profile.last_updated'),
                    'status' => $this->translator->translate('profile.account_status'),
                    'roles' => $this->translator->translate('profile.roles', 'Roles'),
                ],
            ]),
        ];

        $html = $this->renderer->render('layouts/app', $data);
        return Response::html($html);
    }

    /**
     * Check if user is authenticated
     */
    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id'])
            && isset($_SESSION['authenticated'])
            && $_SESSION['authenticated'] === true;
    }

    /**
     * Check if user is admin
     */
    private function isAdmin(int $userId): bool
    {
        return $this->userManager->hasRole($userId, 'admin')
            || $this->userManager->hasRole($userId, 'administrator');
    }

    /**
     * Validate profile data
     */
    private function validateProfileData(array $data): array
    {
        $errors = [];

        // Validate phone (if provided)
        if (!empty($data['phone'])) {
            if (strlen($data['phone']) > 20) {
                $errors['phone'] = $this->translator->translate('validation.phone_too_long',
                    'El teléfono no debe exceder 20 caracteres');
            }
            // Basic phone validation (numbers, spaces, dashes, parentheses, plus)
            if (!preg_match('/^[0-9\s\-\+\(\)]+$/', $data['phone'])) {
                $errors['phone'] = $this->translator->translate('validation.phone_invalid',
                    'El formato del teléfono no es válido');
            }
        }

        // Validate postal code (if provided)
        if (!empty($data['postalcode']) && strlen($data['postalcode']) > 20) {
            $errors['postalcode'] = $this->translator->translate('validation.postalcode_too_long',
                'El código postal no debe exceder 20 caracteres');
        }

        // Validate city (if provided)
        if (!empty($data['city']) && strlen($data['city']) > 100) {
            $errors['city'] = $this->translator->translate('validation.city_too_long',
                'La ciudad no debe exceder 100 caracteres');
        }

        // Validate country (if provided)
        if (!empty($data['country']) && strlen($data['country']) > 100) {
            $errors['country'] = $this->translator->translate('validation.country_too_long',
                'El país no debe exceder 100 caracteres');
        }

        // Validate website URL (if provided)
        if (!empty($data['website'])) {
            if (filter_var($data['website'], FILTER_VALIDATE_URL) === false) {
                $errors['website'] = $this->translator->translate('validation.website_invalid',
                    'El sitio web debe ser una URL válida');
            }
        }

        // Validate LinkedIn URL (if provided)
        if (!empty($data['linkedin'])) {
            if (filter_var($data['linkedin'], FILTER_VALIDATE_URL) === false) {
                $errors['linkedin'] = $this->translator->translate('validation.linkedin_invalid',
                    'LinkedIn debe ser una URL válida');
            }
        }

        // Validate bio length (if provided)
        if (!empty($data['bio']) && strlen($data['bio']) > 1000) {
            $errors['bio'] = $this->translator->translate('validation.bio_too_long',
                'La biografía no debe exceder 1000 caracteres');
        }

        return $errors;
    }
}
