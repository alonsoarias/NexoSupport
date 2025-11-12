<?php

/**
 * Error message translations - English
 *
 * @package ISER\Resources\Lang
 */

return [
    // HTTP errors
    'http' => [
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '403' => 'Forbidden',
        '404' => 'Page Not Found',
        '405' => 'Method Not Allowed',
        '408' => 'Request Timeout',
        '419' => 'Session Expired',
        '429' => 'Too Many Requests',
        '500' => 'Internal Server Error',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
    ],

    // Detailed HTTP messages
    'http_messages' => [
        '400' => 'The request could not be processed due to a client error',
        '401' => 'You must log in to access this resource',
        '403' => 'You do not have permission to access this resource',
        '404' => 'The page you are looking for does not exist or has been moved',
        '405' => 'The HTTP method used is not allowed for this route',
        '408' => 'The request took too long to process',
        '419' => 'Your session has expired. Please log in again',
        '429' => 'You have made too many requests. Please try again later',
        '500' => 'An unexpected error has occurred on the server',
        '502' => 'Communication error with the server',
        '503' => 'The service is temporarily unavailable',
        '504' => 'The server did not respond in time',
    ],

    // Authentication errors
    'auth' => [
        'invalid_credentials' => 'Invalid credentials',
        'user_not_found' => 'User not found',
        'account_suspended' => 'Your account has been suspended',
        'account_locked' => 'Your account has been temporarily locked',
        'account_deleted' => 'This account has been deleted',
        'email_not_verified' => 'You must verify your email address',
        'too_many_attempts' => 'Too many failed attempts. Account locked for :minutes minutes',
        'invalid_token' => 'Invalid or expired token',
        'token_expired' => 'The token has expired',
        'session_expired' => 'Your session has expired. Please log in again',
    ],

    // Authorization errors
    'authorization' => [
        'no_permission' => 'You do not have permission to perform this action',
        'access_denied' => 'Access denied',
        'insufficient_privileges' => 'Insufficient privileges',
        'role_required' => 'The :role role is required to access',
    ],

    // Database errors
    'database' => [
        'connection_failed' => 'Could not connect to the database',
        'query_failed' => 'Error executing the query',
        'record_not_found' => 'Record not found',
        'duplicate_entry' => 'This record already exists',
        'foreign_key_constraint' => 'Cannot delete due to related records',
        'transaction_failed' => 'The transaction has failed',
    ],

    // File errors
    'file' => [
        'not_found' => 'File not found',
        'not_readable' => 'File cannot be read',
        'not_writable' => 'File cannot be written to',
        'upload_failed' => 'Error uploading file',
        'invalid_format' => 'Invalid file format',
        'file_too_large' => 'File is too large (maximum: :max)',
        'extension_not_allowed' => 'File extension not allowed',
    ],

    // General validation errors
    'validation' => [
        'required' => 'This field is required',
        'invalid' => 'The provided value is invalid',
        'too_short' => 'The value is too short',
        'too_long' => 'The value is too long',
        'out_of_range' => 'The value is out of range',
        'not_unique' => 'This value is already in use',
    ],

    // System errors
    'system' => [
        'maintenance' => 'The system is under maintenance. Please try again later',
        'unavailable' => 'The service is temporarily unavailable',
        'configuration_error' => 'System configuration error',
        'dependency_missing' => 'A required dependency is missing',
        'cache_error' => 'Error accessing cache',
        'log_error' => 'Error writing to log',
    ],

    // Suggested actions
    'actions' => [
        'go_home' => 'Go Home',
        'go_back' => 'Go Back',
        'login' => 'Log In',
        'contact_admin' => 'Contact Administrator',
        'try_again' => 'Try Again',
        'reload' => 'Reload Page',
    ],

    // General
    'something_went_wrong' => 'Something went wrong',
    'please_try_again' => 'Please try again',
    'if_problem_persists' => 'If the problem persists, contact the administrator',
];
