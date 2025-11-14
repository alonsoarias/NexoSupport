<?php

/**
 * ISER - Config Form Generator
 *
 * Generates HTML forms and JavaScript validation from plugin config_schema.
 * Supports various field types and validation rules.
 *
 * @package    ISER\Core\Plugin
 * @category   Core
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Week 4 - Plugin System Completion
 */

namespace ISER\Core\Plugin;

use ISER\Core\Utils\Logger;

/**
 * ConfigFormGenerator Class
 *
 * Generates configuration forms from schema including:
 * - HTML form fields based on type
 * - Validation attributes
 * - Labels and descriptions
 * - JavaScript validation code
 */
class ConfigFormGenerator
{
    /**
     * Supported field types
     */
    private const SUPPORTED_TYPES = [
        'string', 'text', 'textarea', 'email', 'url', 'password',
        'int', 'integer', 'number',
        'bool', 'boolean', 'checkbox',
        'select', 'radio'
    ];

    /**
     * Generate complete form HTML
     *
     * Generates a complete form with all fields from schema.
     *
     * @param array $schema Configuration schema
     * @param array $currentValues Current configuration values
     * @param string $formId Form HTML ID
     * @return string Complete form HTML
     */
    public function generateForm(array $schema, array $currentValues = [], string $formId = 'plugin-config-form'): string
    {
        if (empty($schema)) {
            return '<p class="text-muted">This plugin has no configuration options.</p>';
        }

        $html = '<form id="' . htmlspecialchars($formId) . '" class="plugin-config-form">';
        $html .= '<div class="form-fields">';

        foreach ($schema as $key => $fieldSchema) {
            $html .= $this->generateField($key, $fieldSchema, $currentValues[$key] ?? null);
        }

        $html .= '</div>';
        $html .= '<div class="form-actions">';
        $html .= '<button type="submit" class="btn btn-primary">Save Configuration</button>';
        $html .= '<button type="button" class="btn btn-secondary" id="reset-config">Reset to Defaults</button>';
        $html .= '</div>';
        $html .= '</form>';

        return $html;
    }

    /**
     * Generate single form field
     *
     * @param string $key Field key
     * @param array $schema Field schema
     * @param mixed $value Current value
     * @return string Field HTML
     */
    public function generateField(string $key, array $schema, $value = null): string
    {
        $type = $schema['type'] ?? 'string';
        $label = $schema['label'] ?? $this->formatLabel($key);
        $description = $schema['description'] ?? '';
        $required = !empty($schema['required']);
        $placeholder = $schema['placeholder'] ?? '';

        // Get default value if no value provided
        if ($value === null && isset($schema['default'])) {
            $value = $schema['default'];
        }

        $html = '<div class="form-group" data-field="' . htmlspecialchars($key) . '">';

        // Label
        $html .= '<label for="config_' . htmlspecialchars($key) . '">';
        $html .= htmlspecialchars($label);
        if ($required) {
            $html .= ' <span class="text-danger">*</span>';
        }
        $html .= '</label>';

        // Field based on type
        $html .= $this->generateFieldInput($key, $type, $schema, $value);

        // Description
        if ($description) {
            $html .= '<small class="form-text text-muted">' . htmlspecialchars($description) . '</small>';
        }

        // Error placeholder
        $html .= '<div class="invalid-feedback"></div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate field input based on type
     *
     * @param string $key Field key
     * @param string $type Field type
     * @param array $schema Field schema
     * @param mixed $value Current value
     * @return string Field input HTML
     */
    private function generateFieldInput(string $key, string $type, array $schema, $value): string
    {
        $inputId = 'config_' . htmlspecialchars($key);
        $inputName = htmlspecialchars($key);
        $required = !empty($schema['required']);
        $placeholder = $schema['placeholder'] ?? '';

        // Build common attributes
        $attrs = [
            'id' => $inputId,
            'name' => $inputName,
            'class' => 'form-control'
        ];

        if ($required) {
            $attrs['required'] = 'required';
        }

        switch ($type) {
            case 'textarea':
                return $this->generateTextarea($attrs, $value, $schema);

            case 'bool':
            case 'boolean':
            case 'checkbox':
                return $this->generateCheckbox($attrs, $value, $schema);

            case 'select':
                return $this->generateSelect($attrs, $value, $schema);

            case 'radio':
                return $this->generateRadio($key, $value, $schema);

            case 'int':
            case 'integer':
            case 'number':
                return $this->generateNumber($attrs, $value, $schema);

            case 'email':
                $attrs['type'] = 'email';
                if ($placeholder) $attrs['placeholder'] = $placeholder;
                return $this->generateInput($attrs, $value, $schema);

            case 'url':
                $attrs['type'] = 'url';
                if ($placeholder) $attrs['placeholder'] = $placeholder;
                return $this->generateInput($attrs, $value, $schema);

            case 'password':
                $attrs['type'] = 'password';
                if ($placeholder) $attrs['placeholder'] = $placeholder;
                return $this->generateInput($attrs, $value, $schema);

            case 'string':
            case 'text':
            default:
                $attrs['type'] = 'text';
                if ($placeholder) $attrs['placeholder'] = $placeholder;
                return $this->generateInput($attrs, $value, $schema);
        }
    }

    /**
     * Generate text input
     *
     * @param array $attrs HTML attributes
     * @param mixed $value Current value
     * @param array $schema Field schema
     * @return string Input HTML
     */
    private function generateInput(array $attrs, $value, array $schema): string
    {
        // Add validation attributes
        if (isset($schema['min'])) {
            $attrs['minlength'] = $schema['min'];
        }
        if (isset($schema['max'])) {
            $attrs['maxlength'] = $schema['max'];
        }
        if (isset($schema['pattern'])) {
            $attrs['pattern'] = $schema['pattern'];
        }

        if ($value !== null) {
            $attrs['value'] = htmlspecialchars((string)$value);
        }

        return '<input ' . $this->buildAttrsString($attrs) . '>';
    }

    /**
     * Generate textarea
     *
     * @param array $attrs HTML attributes
     * @param mixed $value Current value
     * @param array $schema Field schema
     * @return string Textarea HTML
     */
    private function generateTextarea(array $attrs, $value, array $schema): string
    {
        // Add validation attributes
        if (isset($schema['min'])) {
            $attrs['minlength'] = $schema['min'];
        }
        if (isset($schema['max'])) {
            $attrs['maxlength'] = $schema['max'];
        }

        $attrs['rows'] = $schema['rows'] ?? 4;

        $content = $value !== null ? htmlspecialchars((string)$value) : '';

        return '<textarea ' . $this->buildAttrsString($attrs) . '>' . $content . '</textarea>';
    }

    /**
     * Generate checkbox
     *
     * @param array $attrs HTML attributes
     * @param mixed $value Current value
     * @param array $schema Field schema
     * @return string Checkbox HTML
     */
    private function generateCheckbox(array $attrs, $value, array $schema): string
    {
        $attrs['type'] = 'checkbox';
        $attrs['class'] = 'form-check-input';
        $attrs['value'] = '1';

        // Check if value is truthy
        if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
            $attrs['checked'] = 'checked';
        }

        unset($attrs['required']); // Checkboxes can't be required in HTML5 the same way

        return '<div class="form-check"><input ' . $this->buildAttrsString($attrs) . '></div>';
    }

    /**
     * Generate select dropdown
     *
     * @param array $attrs HTML attributes
     * @param mixed $value Current value
     * @param array $schema Field schema
     * @return string Select HTML
     */
    private function generateSelect(array $attrs, $value, array $schema): string
    {
        $options = $schema['options'] ?? [];

        $html = '<select ' . $this->buildAttrsString($attrs) . '>';

        // Add empty option if not required
        if (empty($schema['required'])) {
            $html .= '<option value="">-- Select --</option>';
        }

        foreach ($options as $optionValue) {
            $selected = ($value !== null && $value == $optionValue) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($optionValue) . '"' . $selected . '>';
            $html .= htmlspecialchars($optionValue);
            $html .= '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * Generate radio buttons
     *
     * @param string $key Field key
     * @param mixed $value Current value
     * @param array $schema Field schema
     * @return string Radio buttons HTML
     */
    private function generateRadio(string $key, $value, array $schema): string
    {
        $options = $schema['options'] ?? [];
        $required = !empty($schema['required']);

        $html = '<div class="form-radio-group">';

        foreach ($options as $optionValue) {
            $inputId = 'config_' . htmlspecialchars($key) . '_' . htmlspecialchars($optionValue);
            $checked = ($value !== null && $value == $optionValue) ? ' checked' : '';

            $html .= '<div class="form-check">';
            $html .= '<input type="radio" class="form-check-input" ';
            $html .= 'id="' . $inputId . '" ';
            $html .= 'name="' . htmlspecialchars($key) . '" ';
            $html .= 'value="' . htmlspecialchars($optionValue) . '"';
            if ($required) {
                $html .= ' required';
            }
            $html .= $checked . '>';
            $html .= '<label class="form-check-label" for="' . $inputId . '">';
            $html .= htmlspecialchars($optionValue);
            $html .= '</label>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate number input
     *
     * @param array $attrs HTML attributes
     * @param mixed $value Current value
     * @param array $schema Field schema
     * @return string Number input HTML
     */
    private function generateNumber(array $attrs, $value, array $schema): string
    {
        $attrs['type'] = 'number';

        if (isset($schema['min'])) {
            $attrs['min'] = $schema['min'];
        }
        if (isset($schema['max'])) {
            $attrs['max'] = $schema['max'];
        }
        if (isset($schema['step'])) {
            $attrs['step'] = $schema['step'];
        }

        if ($value !== null) {
            $attrs['value'] = htmlspecialchars((string)$value);
        }

        return '<input ' . $this->buildAttrsString($attrs) . '>';
    }

    /**
     * Generate JavaScript validation code
     *
     * @param array $schema Configuration schema
     * @param string $formId Form HTML ID
     * @return string JavaScript code
     */
    public function generateValidationJS(array $schema, string $formId = 'plugin-config-form'): string
    {
        $rules = [];

        foreach ($schema as $key => $fieldSchema) {
            $fieldRules = [];

            if (!empty($fieldSchema['required'])) {
                $fieldRules[] = 'required: true';
            }

            $type = $fieldSchema['type'] ?? 'string';

            if ($type === 'email') {
                $fieldRules[] = 'email: true';
            } elseif ($type === 'url') {
                $fieldRules[] = 'url: true';
            } elseif ($type === 'int' || $type === 'integer' || $type === 'number') {
                $fieldRules[] = 'number: true';

                if (isset($fieldSchema['min'])) {
                    $fieldRules[] = 'min: ' . $fieldSchema['min'];
                }
                if (isset($fieldSchema['max'])) {
                    $fieldRules[] = 'max: ' . $fieldSchema['max'];
                }
            } elseif ($type === 'string' || $type === 'text' || $type === 'textarea') {
                if (isset($fieldSchema['min'])) {
                    $fieldRules[] = 'minlength: ' . $fieldSchema['min'];
                }
                if (isset($fieldSchema['max'])) {
                    $fieldRules[] = 'maxlength: ' . $fieldSchema['max'];
                }
            }

            if (!empty($fieldRules)) {
                $rules[$key] = '{ ' . implode(', ', $fieldRules) . ' }';
            }
        }

        $js = <<<JS
(function() {
    const form = document.getElementById('{$formId}');
    if (!form) return;

    // Validation rules
    const rules = {

JS;

        foreach ($rules as $key => $rule) {
            $js .= "        '{$key}': {$rule},\n";
        }

        $js .= <<<JS
    };

    // Form validation on submit
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const errors = {};

        // Clear previous errors
        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
        });

        // Validate each field
        for (const [fieldName, fieldRules] of Object.entries(rules)) {
            const field = form.querySelector('[name="' + fieldName + '"]');
            if (!field) continue;

            const value = field.type === 'checkbox' ? field.checked : field.value;

            // Required validation
            if (fieldRules.required && !value) {
                isValid = false;
                errors[fieldName] = 'This field is required';
                continue;
            }

            // Skip other validations if field is empty and not required
            if (!value && !fieldRules.required) {
                continue;
            }

            // Email validation
            if (fieldRules.email && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errors[fieldName] = 'Please enter a valid email address';
                    continue;
                }
            }

            // URL validation
            if (fieldRules.url && value) {
                try {
                    new URL(value);
                } catch {
                    isValid = false;
                    errors[fieldName] = 'Please enter a valid URL';
                    continue;
                }
            }

            // Number validation
            if (fieldRules.number && value && isNaN(value)) {
                isValid = false;
                errors[fieldName] = 'Please enter a valid number';
                continue;
            }

            // Min/max for numbers
            if (fieldRules.min !== undefined && value < fieldRules.min) {
                isValid = false;
                errors[fieldName] = 'Value must be at least ' + fieldRules.min;
                continue;
            }
            if (fieldRules.max !== undefined && value > fieldRules.max) {
                isValid = false;
                errors[fieldName] = 'Value must be at most ' + fieldRules.max;
                continue;
            }

            // Min/max length for strings
            if (fieldRules.minlength !== undefined && value.length < fieldRules.minlength) {
                isValid = false;
                errors[fieldName] = 'Must be at least ' + fieldRules.minlength + ' characters';
                continue;
            }
            if (fieldRules.maxlength !== undefined && value.length > fieldRules.maxlength) {
                isValid = false;
                errors[fieldName] = 'Must be at most ' + fieldRules.maxlength + ' characters';
                continue;
            }
        }

        // Display errors
        for (const [fieldName, errorMsg] of Object.entries(errors)) {
            const field = form.querySelector('[name="' + fieldName + '"]');
            if (field) {
                field.classList.add('is-invalid');
                const feedbackEl = field.closest('.form-group')?.querySelector('.invalid-feedback');
                if (feedbackEl) {
                    feedbackEl.textContent = errorMsg;
                }
            }
        }

        if (!isValid) {
            e.preventDefault();
            return false;
        }
    });
})();
JS;

        return $js;
    }

    /**
     * Build HTML attributes string
     *
     * @param array $attrs Attributes array
     * @return string Attributes string
     */
    private function buildAttrsString(array $attrs): string
    {
        $parts = [];

        foreach ($attrs as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                if ($value) {
                    $parts[] = htmlspecialchars($key);
                }
            } else {
                $parts[] = htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Format label from field key
     *
     * @param string $key Field key
     * @return string Formatted label
     */
    private function formatLabel(string $key): string
    {
        // Convert snake_case to Title Case
        $label = str_replace('_', ' ', $key);
        return ucwords($label);
    }
}
