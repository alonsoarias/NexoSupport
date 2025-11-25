<?php
/**
 * NexoSupport Core Routes
 *
 * This file defines all core system routes.
 * Routes are separated from the front controller for maintainability.
 *
 * Plugins can define their own routes in their routes.php file
 * or by implementing register_routes() in their plugin class.
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

use core\routing\route_collection;

/**
 * Returns a closure that registers all core routes
 *
 * @return callable
 */
return function(route_collection $routes) {

    // ============================================
    // STATIC FILES
    // ============================================
    $routes->get('/favicon.ico', function() {
        http_response_code(204);
    })->name('favicon');

    // ============================================
    // MAIN ROUTES
    // ============================================
    $routes->get('/', function() {
        require(BASE_DIR . '/dashboard.php');
    })->name('home');

    // ============================================
    // INSTALLATION ROUTES
    // ============================================
    $routes->group(['prefix' => '/install'], function($routes) {
        $routes->any('', function() {
            require(BASE_DIR . '/install/index.php');
        })->name('install');

        $routes->any('/{stage}', function() {
            require(BASE_DIR . '/install/index.php');
        })->name('install_stage');
    });

    // ============================================
    // AUTHENTICATION ROUTES
    // ============================================
    $routes->group(['prefix' => '/login'], function($routes) {
        $routes->match('', function() {
            require(BASE_DIR . '/login/index.php');
        });
        $routes->get('', function() {
            require(BASE_DIR . '/login/index.php');
        })->name('login');
        $routes->post('', function() {
            require(BASE_DIR . '/login/index.php');
        })->name('login_submit');

        $routes->match('/change_password', function() {
            require(BASE_DIR . '/login/change_password.php');
        });
        $routes->get('/change_password', function() {
            require(BASE_DIR . '/login/change_password.php');
        })->name('change_password');

        $routes->match('/forgot_password', function() {
            require(BASE_DIR . '/login/forgot_password.php');
        });
        $routes->get('/forgot_password', function() {
            require(BASE_DIR . '/login/forgot_password.php');
        })->name('forgot_password');

        $routes->get('/confirm', function() {
            require(BASE_DIR . '/login/confirm.php');
        })->name('confirm');
    });

    $routes->get('/logout', function() {
        require(BASE_DIR . '/login/logout.php');
    })->name('logout');

    $routes->get('/login/logout.php', function() {
        require(BASE_DIR . '/login/logout.php');
    });

    // ============================================
    // ADMIN ROUTES
    // ============================================
    $routes->group(['prefix' => '/admin'], function($routes) {

        // Dashboard
        $routes->get('', function() {
            require(BASE_DIR . '/admin/index.php');
        })->name('admin');

        // Upgrade
        $routes->match('/upgrade', function() {
            require(BASE_DIR . '/admin/upgrade.php');
        });
        $routes->match('/upgrade.php', function() {
            require(BASE_DIR . '/admin/upgrade.php');
        });

        // User Management
        $routes->group(['prefix' => '/user'], function($routes) {
            $routes->get('', function() {
                require(BASE_DIR . '/admin/user/index.php');
            })->name('admin_users');

            $routes->match('/edit', function() {
                require(BASE_DIR . '/admin/user/edit.php');
            })->name('admin_user_edit');

            $routes->match('/edit.php', function() {
                require(BASE_DIR . '/admin/user/edit.php');
            });
        });

        // Alternative user routes
        $routes->get('/users', function() {
            require(BASE_DIR . '/admin/user/index.php');
        });

        // Role Management
        $routes->group(['prefix' => '/roles'], function($routes) {
            $routes->get('', function() {
                require(BASE_DIR . '/admin/roles/index.php');
            })->name('admin_roles');

            $routes->match('/edit', function() {
                require(BASE_DIR . '/admin/roles/edit.php');
            })->name('admin_role_edit');

            $routes->match('/edit.php', function() {
                require(BASE_DIR . '/admin/roles/edit.php');
            });

            $routes->match('/define', function() {
                require(BASE_DIR . '/admin/roles/define.php');
            })->name('admin_role_define');

            $routes->match('/define.php', function() {
                require(BASE_DIR . '/admin/roles/define.php');
            });

            $routes->match('/assign', function() {
                require(BASE_DIR . '/admin/roles/assign.php');
            })->name('admin_role_assign');

            $routes->match('/assign.php', function() {
                require(BASE_DIR . '/admin/roles/assign.php');
            });
        });

        // Settings
        $routes->group(['prefix' => '/settings'], function($routes) {
            $routes->match('', function() {
                require(BASE_DIR . '/admin/settings/index.php');
            })->name('admin_settings');

            // Individual settings pages
            $settingsPages = [
                'debugging', 'systempaths', 'sessionhandling', 'http',
                'maintenancemode', 'plugins', 'security', 'server',
                'development', 'general'
            ];

            foreach ($settingsPages as $page) {
                $routes->match("/{$page}", function() use ($page) {
                    require(BASE_DIR . "/admin/settings/{$page}.php");
                })->name("admin_settings_{$page}");
            }
        });

        // Plugins management
        $routes->get('/plugins', function() {
            require(BASE_DIR . '/admin/settings/plugins.php');
        })->name('admin_plugins');

        // Environment & System Info
        $routes->get('/environment', function() {
            require(BASE_DIR . '/admin/environment.php');
        })->name('admin_environment');

        $routes->get('/phpinfo', function() {
            require(BASE_DIR . '/admin/phpinfo.php');
        })->name('admin_phpinfo');

        // Cache
        $routes->match('/cache/purge', function() {
            require(BASE_DIR . '/admin/cache/purge.php');
        })->name('admin_cache_purge');

        $routes->match('/cache/purge.php', function() {
            require(BASE_DIR . '/admin/cache/purge.php');
        });

        // Report redirects
        $routes->get('/reports/logs', function() {
            header('Location: /report/log');
            exit;
        });

        $routes->get('/reports/livelogs', function() {
            header('Location: /report/loglive');
            exit;
        });

        $routes->get('/reports/security', function() {
            header('Location: /report/security');
            exit;
        });

        $routes->get('/reports/performance', function() {
            header('Location: /report/performance');
            exit;
        });

        // MFA Tool
        $routes->group(['prefix' => '/tool/mfa'], function($routes) {
            $routes->match('', function() {
                require(BASE_DIR . '/admin/tool/mfa/settings.php');
            })->name('admin_mfa');

            $routes->match('/auth', function() {
                require(BASE_DIR . '/admin/tool/mfa/auth.php');
            })->name('admin_mfa_auth');

            $routes->match('/auth.php', function() {
                require(BASE_DIR . '/admin/tool/mfa/auth.php');
            });
        });

    });

    // ============================================
    // REPORT ROUTES
    // ============================================
    $routes->group(['prefix' => '/report'], function($routes) {
        $routes->get('/log', function() {
            require(BASE_DIR . '/report/log/index.php');
        })->name('report_log');

        $routes->get('/log/index.php', function() {
            require(BASE_DIR . '/report/log/index.php');
        });

        $routes->get('/loglive', function() {
            require(BASE_DIR . '/report/loglive/index.php');
        })->name('report_loglive');

        $routes->get('/loglive/index.php', function() {
            require(BASE_DIR . '/report/loglive/index.php');
        });

        $routes->get('/loglive/loglive_ajax.php', function() {
            require(BASE_DIR . '/report/loglive/loglive_ajax.php');
        });

        $routes->get('/security', function() {
            require(BASE_DIR . '/report/security/index.php');
        })->name('report_security');

        $routes->get('/security/index.php', function() {
            require(BASE_DIR . '/report/security/index.php');
        });

        $routes->get('/performance', function() {
            require(BASE_DIR . '/report/performance/index.php');
        })->name('report_performance');

        $routes->get('/performance/index.php', function() {
            require(BASE_DIR . '/report/performance/index.php');
        });
    });

    // ============================================
    // USER PROFILE ROUTES
    // ============================================
    $routes->group(['prefix' => '/user'], function($routes) {
        $routes->get('/profile', function() {
            require(BASE_DIR . '/user/profile.php');
        })->name('user_profile');

        $routes->get('/profile.php', function() {
            require(BASE_DIR . '/user/profile.php');
        });

        $routes->match('/edit', function() {
            require(BASE_DIR . '/user/edit.php');
        })->name('user_edit');

        $routes->match('/edit.php', function() {
            require(BASE_DIR . '/user/edit.php');
        });

        $routes->match('/preferences/notification', function() {
            require(BASE_DIR . '/user/preferences/notification.php');
        })->name('user_notification_prefs');

        $routes->match('/preferences/notification.php', function() {
            require(BASE_DIR . '/user/preferences/notification.php');
        });
    });

};
