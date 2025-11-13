<?php

declare(strict_types=1);

namespace ISER\Controllers;

use ISER\Core\Controllers\BaseController;
use ISER\Core\Database\Database;
use ISER\Core\Http\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * I18n API Controller (REFACTORIZADO con BaseController)
 *
 * Proporciona endpoints para obtener traducciones en formato JSON
 * para uso en JavaScript
 *
 * Extiende BaseController para reducir código duplicado.
 *
 * @package ISER\Controllers
 * @author ISER Desarrollo
 */
class I18nApiController extends BaseController
{
    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /**
     * Obtener todas las traducciones de un locale
     *
     * Endpoint: GET /api/i18n/{locale}
     * Ejemplo: /api/i18n/es
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getTranslations(ServerRequestInterface $request): ResponseInterface
    {
        // Obtener locale desde la URI
        $uri = $request->getUri()->getPath();
        $parts = explode('/', trim($uri, '/'));
        $locale = $parts[2] ?? 'es'; // Default a español

        // Validar locale
        $availableLocales = $this->translator->getAvailableLocales();
        if (!in_array($locale, $availableLocales)) {
            return $this->jsonResponse([
                'error' => 'Invalid locale',
                'available_locales' => $availableLocales
            ], 400);
        }

        // Verificar si se solicita un namespace específico
        $namespace = $parts[3] ?? null;

        try {
            if ($namespace) {
                // Retornar solo un namespace específico
                $translations = $this->translator->getAll($namespace, $locale);

                if (empty($translations)) {
                    return $this->jsonResponse([
                        'error' => 'Namespace not found',
                        'namespace' => $namespace
                    ], 404);
                }

                $data = [
                    'locale' => $locale,
                    'namespace' => $namespace,
                    'translations' => $translations
                ];
            } else {
                // Retornar todas las traducciones
                $data = [
                    'locale' => $locale,
                    'fallback_locale' => 'es',
                    'translations' => $this->getAllTranslations($locale)
                ];
            }

            // Retornar con cache headers
            return $this->jsonResponse($data, 200, [
                'Cache-Control' => 'public, max-age=3600',
                'ETag' => md5(json_encode($data))
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'error' => 'Error loading translations',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener el locale actual del usuario
     *
     * Endpoint: GET /api/i18n/current
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getCurrentLocale(ServerRequestInterface $request): ResponseInterface
    {
        return $this->jsonResponse([
            'locale' => $this->translator->getLocale(),
            'available_locales' => $this->translator->getAvailableLocales()
        ]);
    }

    /**
     * Actualizar preferencia de idioma del usuario
     *
     * Endpoint: POST /api/i18n/locale
     * Body: {"locale": "es"}
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function setLocale(ServerRequestInterface $request): ResponseInterface
    {
        // Obtener datos del body
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);

        if (!isset($data['locale'])) {
            return $this->jsonResponse([
                'error' => 'Missing locale parameter'
            ], 400);
        }

        $locale = $data['locale'];

        // Validar locale
        $availableLocales = $this->translator->getAvailableLocales();
        if (!in_array($locale, $availableLocales)) {
            return $this->jsonResponse([
                'error' => 'Invalid locale',
                'available_locales' => $availableLocales
            ], 400);
        }

        // Establecer locale
        $this->translator->setLocale($locale);

        // Guardar en sesión
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['locale'] = $locale;
        }

        // Si el usuario está autenticado, guardar en BD
        if (isset($_SESSION['user_id'])) {
            try {
                // Save locale preference to user_preferences table
                $preferencesManager = new \ISER\User\PreferencesManager($this->db);
                $preferencesManager->set($_SESSION['user_id'], 'locale', $locale, 'string');
            } catch (\Exception $e) {
                error_log('Failed to save locale preference: ' . $e->getMessage());
                // Continue execution even if save fails
            }
        }

        return $this->jsonResponse([
            'success' => true,
            'locale' => $locale,
            'message' => 'Locale updated successfully'
        ]);
    }

    /**
     * Obtener todas las traducciones de un locale
     *
     * @param string $locale
     * @return array
     */
    private function getAllTranslations(string $locale): array
    {
        $translations = [];

        // Lista de todos los archivos de idioma
        $files = [
            'auth',
            'common',
            'installer',
            'admin',
            'users',
            'roles',
            'permissions',
            'settings',
            'dashboard',
            'profile',
            'errors',
            'validation',
            'reports',
            'logs',
            'audit'
        ];

        foreach ($files as $file) {
            $translations[$file] = $this->translator->getAll($file, $locale);
        }

        return $translations;
    }

    /**
     * Crear respuesta JSON
     *
     * @param array $data
     * @param int $statusCode
     * @param array $headers
     * @return ResponseInterface
     */
    private function jsonResponse(array $data, int $statusCode = 200, array $headers = []): ResponseInterface
    {
        $defaultHeaders = [
            'Content-Type' => 'application/json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff'
        ];

        $headers = array_merge($defaultHeaders, $headers);

        return Response::json($data, $statusCode, $headers);
    }
}
