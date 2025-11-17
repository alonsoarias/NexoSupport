<?php

declare(strict_types=1);

namespace ISER\Core\Utils;

/**
 * Validator - Centralized validation utility
 *
 * Provides common validation rules to eliminate duplicate validation logic
 * across controllers and managers.
 *
 * USAGE:
 * ```php
 * use ISER\Core\Utils\Validator;
 *
 * $errors = Validator::validate($_POST, [
 *     'username' => ['required', 'minLength:3', 'maxLength:50'],
 *     'email' => ['required', 'email'],
 *     'password' => ['required', 'minLength:8'],
 *     'website' => ['url'],  // optional field
 * ]);
 *
 * if (!empty($errors)) {
 *     // Handle errors
 * }
 * ```
 *
 * @package ISER\Core\Utils
 * @author ISER Development
 */
class Validator
{
    /**
     * Validate data against rules
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules [field => [rules...]]
     * @param array $messages Custom error messages (optional)
     * @return array Errors [field => error message] or empty array if valid
     */
    public static function validate(array $data, array $rules, array $messages = []): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $error = self::applyRule($field, $value, $rule, $data);

                if ($error !== null) {
                    // Use custom message if provided
                    $errors[$field] = $messages[$field][$rule] ?? $error;
                    break; // Stop at first error for this field
                }
            }
        }

        return $errors;
    }

    /**
     * Check if a single field is valid
     *
     * @param mixed $value Value to validate
     * @param string|array $rules Rules to apply
     * @return bool True if valid
     */
    public static function check($value, $rules): bool
    {
        $rules = is_array($rules) ? $rules : [$rules];

        foreach ($rules as $rule) {
            $error = self::applyRule('field', $value, $rule, []);
            if ($error !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply a single validation rule
     *
     * @param string $field Field name
     * @param mixed $value Value to validate
     * @param string $rule Rule to apply
     * @param array $allData All data (for comparison rules)
     * @return string|null Error message or null if valid
     */
    private static function applyRule(string $field, $value, string $rule, array $allData): ?string
    {
        // Parse rule and parameters (e.g., "minLength:8")
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $ruleParam = $parts[1] ?? null;

        switch ($ruleName) {
            case 'required':
                return self::validateRequired($field, $value);

            case 'email':
                return self::validateEmail($field, $value);

            case 'url':
                return self::validateUrl($field, $value);

            case 'minLength':
                return self::validateMinLength($field, $value, (int)$ruleParam);

            case 'maxLength':
                return self::validateMaxLength($field, $value, (int)$ruleParam);

            case 'min':
                return self::validateMin($field, $value, (float)$ruleParam);

            case 'max':
                return self::validateMax($field, $value, (float)$ruleParam);

            case 'numeric':
                return self::validateNumeric($field, $value);

            case 'integer':
                return self::validateInteger($field, $value);

            case 'alpha':
                return self::validateAlpha($field, $value);

            case 'alphaNumeric':
                return self::validateAlphaNumeric($field, $value);

            case 'slug':
                return self::validateSlug($field, $value);

            case 'matches':
                return self::validateMatches($field, $value, $ruleParam, $allData);

            case 'in':
                $options = explode(',', $ruleParam);
                return self::validateIn($field, $value, $options);

            case 'regex':
                return self::validateRegex($field, $value, $ruleParam);

            case 'date':
                return self::validateDate($field, $value);

            case 'boolean':
                return self::validateBoolean($field, $value);

            default:
                return get_string('unknown_rule', 'validation', ['rule' => $ruleName]);
        }
    }

    /**
     * Validate required field
     */
    private static function validateRequired(string $field, $value): ?string
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return get_string('required', 'validation', ['field' => ucfirst($field)]);
        }
        return null;
    }

    /**
     * Validate email format
     */
    private static function validateEmail(string $field, $value): ?string
    {
        // Skip if empty (use 'required' rule for required fields)
        if (self::isEmpty($value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return get_string('email', 'validation', ['field' => ucfirst($field)]);
        }
        return null;
    }

    /**
     * Validate URL format
     */
    private static function validateUrl(string $field, $value): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return get_string('url', 'validation', ['field' => ucfirst($field)]);
        }
        return null;
    }

    /**
     * Validate minimum length
     */
    private static function validateMinLength(string $field, $value, int $min): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (strlen((string)$value) < $min) {
            return get_string('min', 'validation', ['field' => ucfirst($field), 'min' => $min])['string'];
        }
        return null;
    }

    /**
     * Validate maximum length
     */
    private static function validateMaxLength(string $field, $value, int $max): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (strlen((string)$value) > $max) {
            return get_string('max', 'validation', ['field' => ucfirst($field), 'max' => $max])['string'];
        }
        return null;
    }

    /**
     * Validate minimum value (numeric)
     */
    private static function validateMin(string $field, $value, float $min): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (!is_numeric($value) || (float)$value < $min) {
            return get_string('min', 'validation', ['field' => ucfirst($field), 'min' => $min])['numeric'];
        }
        return null;
    }

    /**
     * Validate maximum value (numeric)
     */
    private static function validateMax(string $field, $value, float $max): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (!is_numeric($value) || (float)$value > $max) {
            return get_string('max', 'validation', ['field' => ucfirst($field), 'max' => $max])['numeric'];
        }
        return null;
    }

    /**
     * Validate numeric value
     */
    private static function validateNumeric(string $field, $value): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (!is_numeric($value)) {
            return get_string('numeric', 'validation', ['field' => ucfirst($field)]);
        }
        return null;
    }

    /**
     * Validate integer value
     */
    private static function validateInteger(string $field, $value): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return get_string('integer', 'validation', ['field' => ucfirst($field)]);
        }
        return null;
    }

    /**
     * Validate alpha characters only
     */
    private static function validateAlpha(string $field, $value): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (!ctype_alpha(str_replace(' ', '', (string)$value))) {
            return get_string('alpha', 'validation', ['field' => ucfirst($field)]);
        }
        return null;
    }

    /**
     * Validate alphanumeric characters
     */
    private static function validateAlphaNumeric(string $field, $value): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (!ctype_alnum(str_replace(' ', '', (string)$value))) {
            return get_string('alpha_num', 'validation', ['field' => ucfirst($field)]);
        }
        return null;
    }

    /**
     * Validate slug format (lowercase, alphanumeric, hyphens, underscores)
     */
    private static function validateSlug(string $field, $value): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string)$value)) {
            return get_string('slug', 'validation', ['field' => ucfirst($field)]);
        }
        return null;
    }

    /**
     * Validate that field matches another field
     */
    private static function validateMatches(string $field, $value, string $otherField, array $allData): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        $otherValue = $allData[$otherField] ?? null;

        if ($value !== $otherValue) {
            return get_string('matches', 'validation', ['field' => ucfirst($field), 'other' => ucfirst($otherField)]);
        }
        return null;
    }

    /**
     * Validate that value is in allowed list
     */
    private static function validateIn(string $field, $value, array $allowed): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (!in_array($value, $allowed, true)) {
            return get_string('in_list', 'validation', ['field' => ucfirst($field), 'values' => implode(', ', $allowed)]);
        }
        return null;
    }

    /**
     * Validate against regex pattern
     */
    private static function validateRegex(string $field, $value, string $pattern): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (!preg_match($pattern, (string)$value)) {
            return get_string('regex', 'validation', ['field' => ucfirst($field)]);
        }
        return null;
    }

    /**
     * Validate date format
     */
    private static function validateDate(string $field, $value): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        $date = \DateTime::createFromFormat('Y-m-d', (string)$value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            return get_string('date', 'validation', ['field' => ucfirst($field)]);
        }
        return null;
    }

    /**
     * Validate boolean value
     */
    private static function validateBoolean(string $field, $value): ?string
    {
        if (self::isEmpty($value)) {
            return null;
        }

        if (!in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true)) {
            return get_string('boolean', 'validation', ['field' => ucfirst($field)]);
        }
        return null;
    }

    /**
     * Check if value is empty (for optional field validation)
     */
    private static function isEmpty($value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * Sanitize input data
     *
     * @param array $data Data to sanitize
     * @param array $fields Fields to sanitize (empty = all)
     * @return array Sanitized data
     */
    public static function sanitize(array $data, array $fields = []): array
    {
        $fieldsToSanitize = empty($fields) ? array_keys($data) : $fields;
        $sanitized = $data;

        foreach ($fieldsToSanitize as $field) {
            if (isset($sanitized[$field]) && is_string($sanitized[$field])) {
                $sanitized[$field] = trim($sanitized[$field]);
            }
        }

        return $sanitized;
    }

    /**
     * Quick validation helpers for common cases
     */

    public static function isRequired($value): bool
    {
        return self::validateRequired('field', $value) === null;
    }

    public static function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public static function isSlug(string $slug): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
    }

    public static function isAlphaNumeric(string $value): bool
    {
        return ctype_alnum(str_replace(' ', '', $value));
    }

    public static function isNumeric($value): bool
    {
        return is_numeric($value);
    }

    public static function isInteger($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
}
