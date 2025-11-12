<?php

/**
 * Validation message translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    // General validation rules
    'required' => 'The :field field is required',
    'required_if' => 'The :field field is required when :other is :value',
    'required_unless' => 'The :field field is required unless :other is in :values',
    'required_with' => 'The :field field is required when :values is present',
    'required_without' => 'The :field field is required when :values is not present',

    // Data types
    'email' => 'The :field field must be a valid email address',
    'url' => 'The :field field must be a valid URL',
    'numeric' => 'The :field field must be a number',
    'integer' => 'The :field field must be an integer',
    'boolean' => 'The :field field must be true or false',
    'string' => 'The :field field must be a string',
    'array' => 'The :field field must be an array',
    'date' => 'The :field field must be a valid date',
    'ip' => 'The :field field must be a valid IP address',
    'json' => 'The :field field must be valid JSON',
    'regex' => 'The :field field format is invalid',

    // Lengths
    'min' => [
        'string' => 'The :field field must be at least :min characters',
        'numeric' => 'The :field field must be at least :min',
        'array' => 'The :field field must have at least :min items',
    ],
    'max' => [
        'string' => 'The :field field must not exceed :max characters',
        'numeric' => 'The :field field must not be greater than :max',
        'array' => 'The :field field must not have more than :max items',
    ],
    'between' => [
        'string' => 'The :field field must be between :min and :max characters',
        'numeric' => 'The :field field must be between :min and :max',
        'array' => 'The :field field must have between :min and :max items',
    ],
    'size' => [
        'string' => 'The :field field must be exactly :size characters',
        'numeric' => 'The :field field must be :size',
        'array' => 'The :field field must contain :size items',
    ],

    // Comparisons
    'same' => 'The :field field must match :other',
    'different' => 'The :field field must be different from :other',
    'in' => 'The selected :field is invalid',
    'not_in' => 'The selected :field is invalid',
    'confirmed' => 'The :field field confirmation does not match',

    // Uniqueness and existence
    'unique' => 'The :field field is already in use',
    'exists' => 'The selected :field is invalid',
    'distinct' => 'The :field field has a duplicate value',

    // Dates
    'before' => 'The :field field must be a date before :date',
    'after' => 'The :field field must be a date after :date',
    'date_equals' => 'The :field field must be a date equal to :date',
    'date_format' => 'The :field field does not match the format :format',

    // Files
    'file' => 'The :field field must be a file',
    'image' => 'The :field field must be an image',
    'mimes' => 'The :field field must be a file of type: :values',
    'mimetypes' => 'The :field field must be a file of type: :values',
    'uploaded' => 'The :field field could not be uploaded',
    'max_file_size' => 'The :field field must not be greater than :max kilobytes',
    'dimensions' => 'The :field field has invalid image dimensions',

    // Special
    'alpha' => 'The :field field may only contain letters',
    'alpha_dash' => 'The :field field may only contain letters, numbers, hyphens, and underscores',
    'alpha_num' => 'The :field field may only contain letters and numbers',
    'starts_with' => 'The :field field must start with: :values',
    'ends_with' => 'The :field field must end with: :values',
    'timezone' => 'The :field field must be a valid timezone',

    // Passwords
    'password' => [
        'min_length' => 'Password must be at least :min characters',
        'uppercase' => 'Password must contain at least one uppercase letter',
        'lowercase' => 'Password must contain at least one lowercase letter',
        'number' => 'Password must contain at least one number',
        'symbol' => 'Password must contain at least one symbol',
        'common' => 'This password is too common',
        'compromised' => 'This password has been compromised in security breaches',
    ],

    // Common field names
    'attributes' => [
        'username' => 'username',
        'email' => 'email address',
        'password' => 'password',
        'password_confirm' => 'password confirmation',
        'first_name' => 'first name',
        'last_name' => 'last name',
        'phone' => 'phone',
        'address' => 'address',
        'city' => 'city',
        'country' => 'country',
        'role' => 'role',
        'status' => 'status',
        'description' => 'description',
        'title' => 'title',
        'content' => 'content',
        'date' => 'date',
        'time' => 'time',
    ],

    // Custom field-specific validations
    'custom' => [
        'username' => [
            'required' => 'You must enter a username',
            'unique' => 'This username is already registered',
            'min' => 'Username must be at least :min characters',
            'alpha_dash' => 'Username may only contain letters, numbers, hyphens, and underscores',
        ],
        'email' => [
            'required' => 'You must enter an email address',
            'email' => 'You must enter a valid email address',
            'unique' => 'This email address is already registered',
        ],
        'password' => [
            'required' => 'You must enter a password',
            'min' => 'Password must be at least :min characters',
            'confirmed' => 'Passwords do not match',
        ],
    ],
];
