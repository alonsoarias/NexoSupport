<?php
/**
 * Configuración del tema ISER
 * @package theme_iser
 * @author ISER Desarrollo
 * @license Propietario
 */

return [
    'name' => 'iser',
    'version' => '1.0.0',
    'author' => 'ISER Desarrollo',
    'description' => 'Tema oficial del ISER basado en Bootstrap 5',
    'license' => 'Propietario',

    // Configuración de layouts
    'layouts' => [
        'base' => [
            'template' => 'layouts/base',
            'regions' => ['header', 'navbar', 'sidebar', 'content', 'footer'],
            'has_sidebar' => true,
            'has_navbar' => true,
            'has_breadcrumbs' => true,
            'container_fluid' => true
        ],
        'admin' => [
            'template' => 'layouts/admin',
            'regions' => ['header', 'navbar', 'sidebar', 'content', 'footer'],
            'has_sidebar' => true,
            'has_navbar' => false,
            'has_breadcrumbs' => true,
            'container_fluid' => true,
            'sidebar_fixed' => true
        ],
        'login' => [
            'template' => 'layouts/login',
            'regions' => ['header', 'content', 'footer'],
            'has_sidebar' => false,
            'has_navbar' => false,
            'has_breadcrumbs' => false,
            'container_fluid' => false,
            'centered' => true
        ],
        'dashboard' => [
            'template' => 'layouts/dashboard',
            'regions' => ['header', 'navbar', 'sidebar', 'content', 'footer'],
            'has_sidebar' => true,
            'has_navbar' => true,
            'has_breadcrumbs' => true,
            'container_fluid' => true,
            'grid_layout' => true
        ],
        'fullwidth' => [
            'template' => 'layouts/fullwidth',
            'regions' => ['header', 'navbar', 'content', 'footer'],
            'has_sidebar' => false,
            'has_navbar' => true,
            'has_breadcrumbs' => false,
            'container_fluid' => true
        ],
        'popup' => [
            'template' => 'layouts/popup',
            'regions' => ['content'],
            'has_sidebar' => false,
            'has_navbar' => false,
            'has_breadcrumbs' => false,
            'has_header' => false,
            'has_footer' => false,
            'minimal' => true
        ]
    ],

    // Paleta de colores ISER
    'colors' => [
        'primary' => '#2c7be5',
        'secondary' => '#6e84a3',
        'success' => '#00d97e',
        'danger' => '#e63757',
        'warning' => '#f6c343',
        'info' => '#39afd1',
        'light' => '#f9fafd',
        'dark' => '#0b1727'
    ],

    // Configuración de assets
    'assets' => [
        'css' => [
            'vendor/bootstrap.min.css',
            'vendor/fontawesome.min.css',
            'theme.css'
        ],
        'js' => [
            'vendor/bootstrap.bundle.min.js',
            'main.js'
        ]
    ],

    // Configuración de navegación
    'navigation' => [
        'main_menu' => [
            'dashboard' => [
                'title' => 'Dashboard',
                'url' => '/dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'permission' => 'user',
                'order' => 10
            ],
            'courses' => [
                'title' => 'Cursos',
                'url' => '/courses',
                'icon' => 'fas fa-book',
                'permission' => 'user',
                'order' => 20
            ],
            'calendar' => [
                'title' => 'Calendario',
                'url' => '/calendar',
                'icon' => 'fas fa-calendar',
                'permission' => 'user',
                'order' => 30
            ],
            'admin' => [
                'title' => 'Administración',
                'url' => '/admin',
                'icon' => 'fas fa-tools',
                'permission' => 'admin',
                'order' => 100
            ]
        ]
    ],

    // Tipografía
    'typography' => [
        'font_heading' => 'Montserrat, sans-serif',
        'font_body' => 'Open Sans, sans-serif',
        'font_mono' => 'Courier New, monospace',
        'base_font_size' => '16px',
        'line_height' => 1.6
    ],

    // Configuración responsive
    'responsive' => [
        'breakpoints' => [
            'xs' => 0,
            'sm' => 576,
            'md' => 768,
            'lg' => 992,
            'xl' => 1200,
            'xxl' => 1400
        ],
        'container_max_widths' => [
            'sm' => 540,
            'md' => 720,
            'lg' => 960,
            'xl' => 1140,
            'xxl' => 1320
        ]
    ],

    // Características del tema
    'features' => [
        'dark_mode' => true,
        'responsive' => true,
        'accessibility' => true,
        'customizable_colors' => true,
        'multiple_layouts' => true,
        'breadcrumbs' => true,
        'sidebar' => true,
        'footer' => true
    ],

    // Configuración de caché
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hora
        'templates' => true,
        'assets' => true
    ]
];
