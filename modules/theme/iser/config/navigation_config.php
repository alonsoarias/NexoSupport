<?php
/**
 * Configuración de navegación del tema ISER
 * @package theme_iser
 */

return [
    // Menú principal
    'main_menu' => [
        'dashboard' => [
            'title' => 'Dashboard',
            'url' => '/dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'permission' => 'user',
            'order' => 10,
            'badge' => null
        ],
        'courses' => [
            'title' => 'Cursos',
            'url' => '/courses',
            'icon' => 'fas fa-book',
            'permission' => 'user',
            'order' => 20,
            'subsections' => [
                [
                    'title' => 'Mis Cursos',
                    'url' => '/courses/mine'
                ],
                [
                    'title' => 'Explorar',
                    'url' => '/courses/browse'
                ],
                [
                    'title' => 'Categorías',
                    'url' => '/courses/categories'
                ]
            ]
        ],
        'calendar' => [
            'title' => 'Calendario',
            'url' => '/calendar',
            'icon' => 'fas fa-calendar',
            'permission' => 'user',
            'order' => 30
        ],
        'messages' => [
            'title' => 'Mensajes',
            'url' => '/messages',
            'icon' => 'fas fa-envelope',
            'permission' => 'user',
            'order' => 40,
            'badge_field' => 'unread_messages'
        ],
        'grades' => [
            'title' => 'Calificaciones',
            'url' => '/grades',
            'icon' => 'fas fa-chart-line',
            'permission' => 'user',
            'order' => 50
        ]
    ],

    // Menú de administración
    'admin_menu' => [
        'dashboard' => [
            'title' => 'Dashboard',
            'url' => '/admin',
            'icon' => 'fas fa-tachometer-alt',
            'permission' => 'admin',
            'order' => 10
        ],
        'users' => [
            'title' => 'Usuarios',
            'url' => '/admin/user',
            'icon' => 'fas fa-users',
            'permission' => 'admin',
            'order' => 20,
            'badge_field' => 'total_users'
        ],
        'roles' => [
            'title' => 'Roles y Permisos',
            'url' => '/admin/roles/manage',
            'icon' => 'fas fa-user-shield',
            'permission' => 'admin',
            'order' => 30
        ],
        'settings' => [
            'title' => 'Configuración',
            'url' => '/admin/settings.php',
            'icon' => 'fas fa-cog',
            'permission' => 'admin',
            'order' => 40,
            'subsections' => [
                ['title' => 'General', 'url' => '/admin/settings.php?section=general'],
                ['title' => 'Autenticación', 'url' => '/admin/settings.php?section=manageauths'],
                ['title' => 'Correo', 'url' => '/admin/settings.php?section=outgoingmailconfig'],
                ['title' => 'MFA', 'url' => '/admin/settings.php?section=mfa'],
                ['title' => 'Políticas', 'url' => '/admin/settings.php?section=policies'],
                ['title' => 'Tema', 'url' => '/admin/settings.php?section=theme']
            ]
        ],
        'plugins' => [
            'title' => 'Plugins',
            'url' => '/admin/plugins.php',
            'icon' => 'fas fa-puzzle-piece',
            'permission' => 'admin',
            'order' => 50
        ],
        'tools' => [
            'title' => 'Herramientas',
            'url' => '/admin/tools.php',
            'icon' => 'fas fa-tools',
            'permission' => 'admin',
            'order' => 60,
            'subsections' => [
                ['title' => 'Subir Usuarios', 'url' => '/admin/tool/uploaduser/index.php'],
                ['title' => 'Instalar Addon', 'url' => '/admin/tool/installaddon/index.php']
            ]
        ],
        'reports' => [
            'title' => 'Reportes',
            'url' => '/admin/reports.php',
            'icon' => 'fas fa-chart-bar',
            'permission' => 'admin',
            'order' => 70
        ]
    ],

    // Menú de usuario
    'user_menu' => [
        'profile' => [
            'title' => 'Mi Perfil',
            'url' => '/profile',
            'icon' => 'fas fa-user',
            'order' => 10
        ],
        'preferences' => [
            'title' => 'Preferencias',
            'url' => '/user/preferences',
            'icon' => 'fas fa-cog',
            'order' => 20
        ],
        'logout' => [
            'title' => 'Cerrar Sesión',
            'url' => '/logout',
            'icon' => 'fas fa-sign-out-alt',
            'order' => 100
        ]
    ],

    // Configuración de breadcrumbs
    'breadcrumbs' => [
        'max_depth' => 5,
        'show_home' => true,
        'home_title' => 'Inicio',
        'separator' => '/'
    ]
];
