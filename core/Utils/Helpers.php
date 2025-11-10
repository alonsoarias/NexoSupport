<?php

/**
 * ISER Authentication System - Helper Functions
 *
 * Collection of utility helper functions.
 *
 * @package    ISER\Core\Utils
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

namespace ISER\Core\Utils;

/**
 * Helpers Class
 *
 * Provides various utility helper functions.
 */
class Helpers
{
    /**
     * Sanitize string for HTML output
     *
     * @param string $string String to sanitize
     * @return string Sanitized string
     */
    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Generate a random string
     *
     * @param int $length Length of the string
     * @param string|null $characters Characters to use
     * @return string Random string
     */
    public static function randomString(int $length = 32, ?string $characters = null): string
    {
        if ($characters === null) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * Generate a secure random token
     *
     * @param int $length Token length
     * @return string Random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Hash a password using bcrypt
     *
     * @param string $password Plain password
     * @return string Hashed password
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify a password against a hash
     *
     * @param string $password Plain password
     * @param string $hash Hashed password
     * @return bool True if password matches
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        // Get hash algorithm info for debugging
        $hashInfo = password_get_info($hash);
        error_log("[Helpers::verifyPassword] Hash algorithm: " . ($hashInfo['algoName'] ?? 'unknown'));
        error_log("[Helpers::verifyPassword] Hash (first 30 chars): " . substr($hash, 0, 30));
        error_log("[Helpers::verifyPassword] Password length: " . strlen($password));

        $result = password_verify($password, $hash);
        error_log("[Helpers::verifyPassword] Verification result: " . ($result ? "SUCCESS" : "FAILED"));

        return $result;
    }

    /**
     * Check if password needs rehashing
     *
     * @param string $hash Password hash
     * @return bool True if needs rehashing
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Sanitize email address
     *
     * @param string $email Email address
     * @return string|false Sanitized email or false
     */
    public static function sanitizeEmail(string $email): string|false
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Validate email address
     *
     * @param string $email Email address
     * @return bool True if valid email
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     *
     * @param string $url URL to validate
     * @return bool True if valid URL (http/https only)
     */
    public static function validateUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        // Only allow http and https protocols
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array($scheme, ['http', 'https'], true);
    }

    /**
     * Generate a UUID v4
     *
     * @return string UUID
     */
    public static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Convert array to object
     *
     * @param array $array Array to convert
     * @return object Object
     */
    public static function arrayToObject(array $array): object
    {
        return json_decode(json_encode($array));
    }

    /**
     * Convert object to array
     *
     * @param object $object Object to convert
     * @return array Array
     */
    public static function objectToArray(object $object): array
    {
        return json_decode(json_encode($object), true);
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes Number of bytes
     * @param int $precision Decimal precision
     * @return string Formatted string
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get client IP address
     *
     * @return string Client IP address
     */
    public static function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get user agent string
     *
     * @return string User agent
     */
    public static function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    /**
     * Check if request is AJAX
     *
     * @return bool True if AJAX request
     */
    public static function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check if request is POST
     *
     * @return bool True if POST request
     */
    public static function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Check if request is GET
     *
     * @return bool True if GET request
     */
    public static function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Redirect to URL
     *
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }

    /**
     * Get current URL
     *
     * @return string Current URL
     */
    public static function getCurrentUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return $protocol . '://' . $host . $uri;
    }

    /**
     * Get base URL
     *
     * @return string Base URL
     */
    public static function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $protocol . '://' . $host;
    }

    /**
     * Convert string to slug
     *
     * @param string $string String to convert
     * @param string $separator Separator character
     * @return string Slug
     */
    public static function slug(string $string, string $separator = '-'): string
    {
        $string = mb_strtolower($string, 'UTF-8');
        // First replace non-alphanumeric characters with separator
        $string = preg_replace('/[^a-z0-9]+/', $separator, $string);
        // Clean up multiple separators
        $string = preg_replace('/' . preg_quote($separator, '/') . '+/', $separator, $string);
        return trim($string, $separator);
    }

    /**
     * Truncate string
     *
     * @param string $string String to truncate
     * @param int $length Maximum length
     * @param string $suffix Suffix to append
     * @return string Truncated string
     */
    public static function truncate(string $string, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($string) <= $length) {
            return $string;
        }

        return mb_substr($string, 0, $length - mb_strlen($suffix)) . $suffix;
    }

    /**
     * Parse JSON safely
     *
     * @param string $json JSON string
     * @param bool $assoc Return associative array
     * @return mixed Parsed data or null on error
     */
    public static function parseJson(string $json, bool $assoc = true): mixed
    {
        try {
            return json_decode($json, $assoc, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return null;
        }
    }

    /**
     * Encode to JSON safely
     *
     * @param mixed $data Data to encode
     * @param int $options JSON options
     * @return string|false JSON string or false on error
     */
    public static function toJson(mixed $data, int $options = JSON_UNESCAPED_UNICODE): string|false
    {
        try {
            return json_encode($data, $options | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return false;
        }
    }

    /**
     * Get array value by dot notation
     *
     * @param array $array Source array
     * @param string $key Key in dot notation (e.g., 'user.name')
     * @param mixed $default Default value if not found
     * @return mixed Value or default
     */
    public static function arrayGet(array $array, string $key, mixed $default = null): mixed
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Check if value is empty (custom definition)
     *
     * @param mixed $value Value to check
     * @return bool True if empty
     */
    public static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [] || $value === false;
    }

    /**
     * Get timestamp in milliseconds
     *
     * @return int Timestamp in milliseconds
     */
    public static function timestampMs(): int
    {
        return (int) (microtime(true) * 1000);
    }

    /**
     * Format date for display
     *
     * @param string|int $date Date string or timestamp
     * @param string $format Date format
     * @return string Formatted date
     */
    public static function formatDate(string|int $date, string $format = 'Y-m-d H:i:s'): string
    {
        if (is_numeric($date)) {
            $timestamp = $date;
        } else {
            $timestamp = strtotime($date);
        }

        return date($format, $timestamp);
    }

    /**
     * Calculate time ago from timestamp
     *
     * @param int $timestamp Unix timestamp
     * @return string Time ago string
     */
    public static function timeAgo(int $timestamp): string
    {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return $diff . ' seconds ago';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' minutes ago';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' hours ago';
        } elseif ($diff < 604800) {
            return floor($diff / 86400) . ' days ago';
        } else {
            return date('Y-m-d', $timestamp);
        }
    }
}
