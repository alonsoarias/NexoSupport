<?php

declare(strict_types=1);

namespace ISER\Core\I18n;

/**
 * Translator - Sistema de Internacionalización
 *
 * Maneja traducciones y localización del sistema
 * Compatible con archivos PHP que retornan arrays
 *
 * @package ISER\Core\I18n
 * @author ISER Desarrollo
 */
class Translator
{
    private static ?Translator $instance = null;
    private string $locale = 'es';
    private string $fallbackLocale = 'es';
    private array $translations = [];
    private string $translationsPath;

    /**
     * Constructor privado (Singleton)
     */
    private function __construct(string $translationsPath)
    {
        $this->translationsPath = $translationsPath;
    }

    /**
     * Obtener instancia única
     */
    public static function getInstance(string $translationsPath = ''): self
    {
        if (self::$instance === null) {
            if (empty($translationsPath)) {
                $translationsPath = dirname(__DIR__, 3) . '/resources/lang';
            }
            self::$instance = new self($translationsPath);
        }

        return self::$instance;
    }

    /**
     * Establecer locale actual
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
        $this->loadTranslations($locale);
    }

    /**
     * Obtener locale actual
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Establecer locale de respaldo
     */
    public function setFallbackLocale(string $locale): void
    {
        $this->fallbackLocale = $locale;
    }

    /**
     * Cargar traducciones para un locale
     */
    private function loadTranslations(string $locale): void
    {
        if (isset($this->translations[$locale])) {
            return;
        }

        $this->translations[$locale] = [];
        $localePath = $this->translationsPath . '/' . $locale;

        if (!is_dir($localePath)) {
            return;
        }

        // Cargar archivos PHP
        $files = glob($localePath . '/*.php');
        foreach ($files as $file) {
            $key = basename($file, '.php');
            $data = require $file;
            if (is_array($data)) {
                $this->translations[$locale][$key] = $data;
            }
        }
    }

    /**
     * Traducir una clave
     *
     * @param string $key Clave de traducción (ej: "auth.login")
     * @param array $replace Variables a reemplazar
     * @param string|null $locale Locale específico (opcional)
     * @return string Traducción
     */
    public function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;

        // Cargar traducciones si no están cargadas
        if (!isset($this->translations[$locale])) {
            $this->loadTranslations($locale);
        }

        // Buscar traducción
        $translation = $this->findTranslation($key, $locale);

        // Si no se encuentra, intentar con fallback
        if ($translation === null && $locale !== $this->fallbackLocale) {
            if (!isset($this->translations[$this->fallbackLocale])) {
                $this->loadTranslations($this->fallbackLocale);
            }
            $translation = $this->findTranslation($key, $this->fallbackLocale);
        }

        // Si aún no se encuentra, devolver la clave
        if ($translation === null) {
            return $key;
        }

        // Reemplazar variables
        return $this->replaceVariables($translation, $replace);
    }

    /**
     * Buscar traducción en el array
     */
    private function findTranslation(string $key, string $locale): ?string
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset($this->translations[$locale][$file])) {
            return null;
        }

        $translation = $this->translations[$locale][$file];

        foreach ($parts as $part) {
            if (!isset($translation[$part])) {
                return null;
            }
            $translation = $translation[$part];
        }

        return is_string($translation) ? $translation : null;
    }

    /**
     * Reemplazar variables en traducción
     */
    private function replaceVariables(string $translation, array $replace): string
    {
        foreach ($replace as $key => $value) {
            $translation = str_replace(
                [':' . $key, ':' . strtoupper($key), ':' . ucfirst($key)],
                [$value, strtoupper($value), ucfirst($value)],
                $translation
            );
        }

        return $translation;
    }

    /**
     * Verificar si existe una traducción
     */
    public function has(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->locale;

        if (!isset($this->translations[$locale])) {
            $this->loadTranslations($locale);
        }

        return $this->findTranslation($key, $locale) !== null;
    }

    /**
     * Obtener todas las traducciones de un archivo
     */
    public function getAll(string $file, ?string $locale = null): array
    {
        $locale = $locale ?? $this->locale;

        if (!isset($this->translations[$locale])) {
            $this->loadTranslations($locale);
        }

        return $this->translations[$locale][$file] ?? [];
    }

    /**
     * Obtener locales disponibles
     */
    public function getAvailableLocales(): array
    {
        $locales = [];
        $dirs = glob($this->translationsPath . '/*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $locales[] = basename($dir);
        }

        return $locales;
    }
}

/**
 * Helper function para traducción
 */
if (!function_exists('__')) {
    function __(string $key, array $replace = [], ?string $locale = null): string
    {
        return \ISER\Core\I18n\Translator::getInstance()->translate($key, $replace, $locale);
    }
}

/**
 * Helper function para traducción con pluralización
 */
if (!function_exists('trans_choice')) {
    function trans_choice(string $key, int $count, array $replace = [], ?string $locale = null): string
    {
        $translator = \ISER\Core\I18n\Translator::getInstance();
        $translation = $translator->translate($key, $replace, $locale);

        // Soporte básico de pluralización
        $parts = explode('|', $translation);

        if (count($parts) === 1) {
            return str_replace(':count', (string)$count, $translation);
        }

        // {0} There are none | {1} There is one | [2,*] There are some
        foreach ($parts as $part) {
            $part = trim($part);

            // Singular
            if ($count === 1 && strpos($part, '{1}') !== false) {
                return str_replace(['{1}', ':count'], ['', (string)$count], $part);
            }

            // Cero
            if ($count === 0 && strpos($part, '{0}') !== false) {
                return str_replace(['{0}', ':count'], ['', (string)$count], $part);
            }

            // Plural
            if ($count > 1 && (strpos($part, '[2,*]') !== false || strpos($part, '{*}') !== false)) {
                return str_replace(['[2,*]', '{*}', ':count'], ['', '', (string)$count], $part);
            }
        }

        return str_replace(':count', (string)$count, $parts[0]);
    }
}
