<?php
/**
 * Paleta de colores extendida del tema ISER
 * @package theme_iser
 */

return [
    // Colores primarios
    'primary' => [
        'base' => '#2c7be5',
        'light' => '#5a9df5',
        'dark' => '#1a5fcd',
        'contrast' => '#ffffff'
    ],

    'secondary' => [
        'base' => '#6e84a3',
        'light' => '#8ba1bc',
        'dark' => '#566785',
        'contrast' => '#ffffff'
    ],

    // Colores de estado
    'success' => [
        'base' => '#00d97e',
        'light' => '#33e498',
        'dark' => '#00b369',
        'contrast' => '#ffffff'
    ],

    'danger' => [
        'base' => '#e63757',
        'light' => '#eb6179',
        'dark' => '#c92a45',
        'contrast' => '#ffffff'
    ],

    'warning' => [
        'base' => '#f6c343',
        'light' => '#f8cf69',
        'dark' => '#e0ad1e',
        'contrast' => '#000000'
    ],

    'info' => [
        'base' => '#39afd1',
        'light' => '#61bfda',
        'dark' => '#2a8fa9',
        'contrast' => '#ffffff'
    ],

    // Colores neutros
    'light' => [
        'base' => '#f9fafd',
        'dark' => '#e9ecef',
        'contrast' => '#000000'
    ],

    'dark' => [
        'base' => '#0b1727',
        'light' => '#1a2332',
        'contrast' => '#ffffff'
    ],

    // Escala de grises
    'gray' => [
        '100' => '#f8f9fa',
        '200' => '#e9ecef',
        '300' => '#dee2e6',
        '400' => '#ced4da',
        '500' => '#adb5bd',
        '600' => '#6c757d',
        '700' => '#495057',
        '800' => '#343a40',
        '900' => '#212529'
    ],

    // Colores académicos específicos
    'academic' => [
        'courses' => '#2c7be5',
        'assignments' => '#f6c343',
        'grades' => '#00d97e',
        'calendar' => '#39afd1',
        'messages' => '#e63757'
    ],

    // Colores de usuario
    'user_roles' => [
        'admin' => '#e63757',
        'teacher' => '#2c7be5',
        'student' => '#00d97e',
        'guest' => '#6e84a3'
    ],

    // Degradados
    'gradients' => [
        'primary' => 'linear-gradient(135deg, #2c7be5 0%, #5a9df5 100%)',
        'secondary' => 'linear-gradient(135deg, #6e84a3 0%, #8ba1bc 100%)',
        'success' => 'linear-gradient(135deg, #00d97e 0%, #33e498 100%)',
        'danger' => 'linear-gradient(135deg, #e63757 0%, #eb6179 100%)',
        'warning' => 'linear-gradient(135deg, #f6c343 0%, #f8cf69 100%)',
        'info' => 'linear-gradient(135deg, #39afd1 0%, #61bfda 100%)',
        'dark' => 'linear-gradient(135deg, #0b1727 0%, #1a2332 100%)'
    ]
];
