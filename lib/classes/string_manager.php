<?php
namespace core;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * String Manager
 *
 * Gestiona la internacionalización del sistema.
 * Similar a Moodle's string_manager.
 *
 * @package core
 */
class string_manager {

    /** @var array Cache of loaded strings */
    private static array $cache = [];

    /** @var string Current language */
    private static string $language = 'es';

    /** @var string Fallback language */
    private static string $fallback = 'en';

    /** @var array Available languages */
    private static array $available_languages = [
        'es' => 'Español',
        'en' => 'English'
    ];

    /**
     * Set current language
     *
     * @param string $lang Language code
     * @return void
     */
    public static function set_language(string $lang): void {
        if (isset(self::$available_languages[$lang])) {
            self::$language = $lang;
        }
    }

    /**
     * Get current language
     *
     * @return string Language code
     */
    public static function get_language(): string {
        return self::$language;
    }

    /**
     * Get available languages
     *
     * @return array [code => name]
     */
    public static function get_available_languages(): array {
        return self::$available_languages;
    }

    /**
     * Get a string from the language file
     *
     * @param string $identifier String identifier
     * @param string $component Component (e.g., 'core', 'mod_forum')
     * @param mixed $a Optional parameter for string substitution
     * @return string Translated string or [[identifier]] if not found
     */
    public static function get_string(string $identifier, string $component = 'core', mixed $a = null): string {
        $lang = self::$language;

        // Load strings for this component if not cached
        if (!isset(self::$cache[$lang][$component])) {
            self::load_component_strings($component, $lang);
        }

        // Try to get string in current language
        if (isset(self::$cache[$lang][$component][$identifier])) {
            $string = self::$cache[$lang][$component][$identifier];
        } else {
            // Try fallback language
            if ($lang !== self::$fallback) {
                if (!isset(self::$cache[self::$fallback][$component])) {
                    self::load_component_strings($component, self::$fallback);
                }

                if (isset(self::$cache[self::$fallback][$component][$identifier])) {
                    $string = self::$cache[self::$fallback][$component][$identifier];
                } else {
                    // String not found
                    return "[[{$identifier}]]";
                }
            } else {
                // String not found
                return "[[{$identifier}]]";
            }
        }

        // Process parameter substitution
        if ($a !== null) {
            $string = self::process_string_substitution($string, $a);
        }

        return $string;
    }

    /**
     * Load strings for a component
     *
     * @param string $component Component name
     * @param string $lang Language code
     * @return void
     */
    private static function load_component_strings(string $component, string $lang): void {
        global $CFG;

        // Initialize cache for this language and component
        if (!isset(self::$cache[$lang])) {
            self::$cache[$lang] = [];
        }

        self::$cache[$lang][$component] = [];

        // Determine file path based on component
        if ($component === 'core') {
            $filepath = BASE_DIR . "/lang/{$lang}/core.php";
        } else {
            // For plugins: lang/es/mod_forum.php, lang/es/block_navigation.php, etc.
            $filepath = BASE_DIR . "/lang/{$lang}/{$component}.php";
        }

        // Load strings from file
        if (file_exists($filepath)) {
            $string = [];
            include($filepath);

            if (is_array($string)) {
                self::$cache[$lang][$component] = $string;
            }
        }
    }

    /**
     * Process string substitution
     *
     * Replaces {$a} or {$a->property} with values
     *
     * @param string $string String template
     * @param mixed $a Substitution parameter
     * @return string Processed string
     */
    private static function process_string_substitution(string $string, mixed $a): string {
        if (is_scalar($a)) {
            // Simple substitution: {$a}
            return str_replace('{$a}', (string)$a, $string);
        } elseif (is_object($a) || is_array($a)) {
            // Object/array substitution: {$a->property} or {$a['key']}
            $a = (object)$a;

            // Find all placeholders
            if (preg_match_all('/\{\$a->([a-zA-Z0-9_]+)\}/', $string, $matches)) {
                foreach ($matches[1] as $property) {
                    if (isset($a->$property)) {
                        $string = str_replace('{$a->' . $property . '}', (string)$a->$property, $string);
                    }
                }
            }
        }

        return $string;
    }

    /**
     * Check if a string exists
     *
     * @param string $identifier String identifier
     * @param string $component Component
     * @return bool True if string exists
     */
    public static function string_exists(string $identifier, string $component = 'core'): bool {
        $lang = self::$language;

        if (!isset(self::$cache[$lang][$component])) {
            self::load_component_strings($component, $lang);
        }

        return isset(self::$cache[$lang][$component][$identifier]);
    }

    /**
     * Clear string cache
     *
     * @return void
     */
    public static function clear_cache(): void {
        self::$cache = [];
    }

    /**
     * Get all strings for a component
     *
     * @param string $component Component name
     * @param string|null $lang Language (null = current)
     * @return array Array of strings
     */
    public static function get_component_strings(string $component, ?string $lang = null): array {
        $lang = $lang ?? self::$language;

        if (!isset(self::$cache[$lang][$component])) {
            self::load_component_strings($component, $lang);
        }

        return self::$cache[$lang][$component] ?? [];
    }
}
