<?php
/**
 * Configuración de layouts del tema ISER
 * @package theme_iser
 */

return [
    // Configuración de sidebar
    'sidebar' => [
        'width' => 250,
        'collapsed_width' => 60,
        'breakpoint' => 768,
        'fixed' => true,
        'collapsible' => true
    ],

    // Configuración de header
    'header' => [
        'height' => 70,
        'fixed' => true,
        'transparent' => false,
        'show_search' => true,
        'show_notifications' => true,
        'show_user_menu' => true
    ],

    // Configuración de footer
    'footer' => [
        'fixed' => false,
        'show_social_links' => true,
        'show_copyright' => true,
        'show_version' => true
    ],

    // Configuración de navegación
    'navbar' => [
        'style' => 'horizontal', // horizontal, vertical
        'fixed' => true,
        'collapsible' => true,
        'breakpoint' => 992,
        'show_icons' => true,
        'show_badges' => true
    ],

    // Configuración de breadcrumbs
    'breadcrumbs' => [
        'enabled' => true,
        'show_home' => true,
        'separator' => '/',
        'max_items' => 5
    ],

    // Configuración de contenedores
    'containers' => [
        'default' => 'container',
        'admin' => 'container-fluid',
        'login' => 'container-sm',
        'dashboard' => 'container-fluid'
    ],

    // Grid system
    'grid' => [
        'columns' => 12,
        'gutter_width' => 30,
        'row_columns' => 6
    ],

    // Configuración de espaciado
    'spacing' => [
        'section_padding' => 4, // rem
        'card_margin' => 3, // rem
        'component_gap' => 1.5 // rem
    ]
];
