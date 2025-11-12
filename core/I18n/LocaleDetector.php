<?php

declare(strict_types=1);

namespace ISER\Core\I18n;

use ISER\Core\Database\Database;

/**
 * LocaleDetector - Detección Automática de Idioma
 *
 * Detecta el idioma preferido del usuario usando múltiples fuentes
 * en orden de prioridad
 *
 * @package ISER\Core\I18n
 * @author ISER Desarrollo
 */
class LocaleDetector
{
    private Translator $translator;
    private ?Database $db;
    private array $availableLocales = ['es', 'en', 'pt'];
    private string $defaultLocale = 'es';

    /**
     * Constructor
     *
     * @param Translator $translator
     * @param Database|null $db Base de datos (opcional)
     */
    public function __construct(Translator $translator, ?Database $db = null)
    {
        $this->translator = $translator;
        $this->db = $db;

        // Obtener locales disponibles del Translator
        $available = $translator->getAvailableLocales();
        if (!empty($available)) {
            $this->availableLocales = $available;
        }

        // Obtener locale por defecto desde configuración
        if (isset($_ENV['DEFAULT_LOCALE'])) {
            $this->defaultLocale = $_ENV['DEFAULT_LOCALE'];
        }
    }

    /**
     * Detectar locale usando múltiples fuentes en orden de prioridad
     *
     * Orden de prioridad:
     * 1. Parámetro GET (?lang=es)
     * 2. Sesión ($_SESSION['locale'])
     * 3. Preferencia de usuario en BD (user_preferences.locale)
     * 4. Header HTTP Accept-Language
     * 5. Configuración por defecto del sistema (DEFAULT_LOCALE)
     *
     * @return string Locale detectado
     */
    public function detect(): string
    {
        // Fuente 1: Parámetro GET
        $locale = $this->detectFromQueryParam();
        if ($locale !== null) {
            return $locale;
        }

        // Fuente 2: Sesión
        $locale = $this->detectFromSession();
        if ($locale !== null) {
            return $locale;
        }

        // Fuente 3: Preferencia de usuario en BD
        $locale = $this->detectFromUserPreference();
        if ($locale !== null) {
            return $locale;
        }

        // Fuente 4: Header Accept-Language
        $locale = $this->detectFromAcceptLanguage();
        if ($locale !== null) {
            return $locale;
        }

        // Fuente 5: Configuración por defecto
        return $this->defaultLocale;
    }

    /**
     * Aplicar locale detectado
     *
     * Detecta el locale y lo establece en el Translator
     */
    public function apply(): void
    {
        $locale = $this->detect();
        $this->translator->setLocale($locale);

        // Guardar en sesión para persistencia
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['locale'] = $locale;
        }
    }

    /**
     * Detectar locale desde parámetro GET (?lang=es)
     *
     * @return string|null
     */
    private function detectFromQueryParam(): ?string
    {
        $param = $_GET['lang'] ?? null;

        if ($param && $this->isValidLocale($param)) {
            return $param;
        }

        return null;
    }

    /**
     * Detectar locale desde sesión
     *
     * @return string|null
     */
    private function detectFromSession(): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $locale = $_SESSION['locale'] ?? null;

        if ($locale && $this->isValidLocale($locale)) {
            return $locale;
        }

        return null;
    }

    /**
     * Detectar locale desde preferencias de usuario en BD
     *
     * @return string|null
     */
    private function detectFromUserPreference(): ?string
    {
        // Verificar que hay usuario autenticado y base de datos disponible
        if (!isset($_SESSION['user_id']) || $this->db === null) {
            return null;
        }

        try {
            $userId = (int)$_SESSION['user_id'];

            // Intentar obtener desde user_profiles.locale (existente)
            $query = "SELECT locale FROM user_profiles WHERE user_id = :user_id LIMIT 1";
            $result = $this->db->query($query, ['user_id' => $userId]);

            if (!empty($result) && isset($result[0]['locale'])) {
                $locale = $result[0]['locale'];
                if ($this->isValidLocale($locale)) {
                    return $locale;
                }
            }

            // TODO: Cuando exista user_preferences, buscar ahí también
            // $query = "SELECT value FROM user_preferences
            //           WHERE user_id = :user_id AND key = 'locale' LIMIT 1";

        } catch (\Exception $e) {
            // Si falla la consulta, continuar con otras fuentes
            error_log('LocaleDetector: Error al obtener preferencia de usuario: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Detectar locale desde header Accept-Language
     *
     * @return string|null
     */
    private function detectFromAcceptLanguage(): ?string
    {
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;

        if (!$acceptLanguage) {
            return null;
        }

        // Parsear header Accept-Language
        // Ejemplo: "es-MX,es;q=0.9,en;q=0.8,pt;q=0.7"
        $languages = [];
        $parts = explode(',', $acceptLanguage);

        foreach ($parts as $part) {
            $part = trim($part);

            // Separar idioma y calidad (q)
            if (strpos($part, ';q=') !== false) {
                [$lang, $quality] = explode(';q=', $part);
                $quality = (float)$quality;
            } else {
                $lang = $part;
                $quality = 1.0;
            }

            // Normalizar locale (es-MX → es)
            $lang = strtolower($lang);
            if (strpos($lang, '-') !== false) {
                $lang = explode('-', $lang)[0];
            }

            $languages[$lang] = $quality;
        }

        // Ordenar por calidad (mayor primero)
        arsort($languages);

        // Buscar el primer locale soportado
        foreach (array_keys($languages) as $lang) {
            if ($this->isValidLocale($lang)) {
                return $lang;
            }
        }

        return null;
    }

    /**
     * Verificar si un locale es válido (soportado)
     *
     * @param string $locale
     * @return bool
     */
    private function isValidLocale(string $locale): bool
    {
        return in_array($locale, $this->availableLocales, true);
    }

    /**
     * Obtener locales disponibles
     *
     * @return array
     */
    public function getAvailableLocales(): array
    {
        return $this->availableLocales;
    }

    /**
     * Establecer locales disponibles
     *
     * @param array $locales
     */
    public function setAvailableLocales(array $locales): void
    {
        $this->availableLocales = $locales;
    }

    /**
     * Obtener locale por defecto
     *
     * @return string
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * Establecer locale por defecto
     *
     * @param string $locale
     */
    public function setDefaultLocale(string $locale): void
    {
        if ($this->isValidLocale($locale)) {
            $this->defaultLocale = $locale;
        }
    }
}
