<?php
/**
 * Top-level admin settings categories
 *
 * This file creates all the top-level categories in the admin settings tree.
 * It MUST be loaded FIRST before any other settings files.
 *
 * Following Moodle's pattern: /admin/settings/top.php
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

// $ADMIN is the admin_root instance passed from adminlib.php

// ========================================================
// SITE ADMINISTRATION (root visible container)
// ========================================================

// General settings category
$ADMIN->add('root', new \core\admin\admin_category(
    'general',
    get_string('general', 'core')
));

// Users management category
$ADMIN->add('root', new \core\admin\admin_category(
    'users',
    get_string('users', 'core')
));

// Roles and permissions category (inside users)
$ADMIN->add('users', new \core\admin\admin_category(
    'roles',
    get_string('roles', 'core')
));

// Security category
$ADMIN->add('root', new \core\admin\admin_category(
    'security',
    get_string('security', 'core')
));

// Server category
$ADMIN->add('root', new \core\admin\admin_category(
    'server',
    get_string('server', 'admin')
));

// Development category
$ADMIN->add('root', new \core\admin\admin_category(
    'development',
    get_string('developmentsettings', 'core')
));

// Plugins category
$ADMIN->add('root', new \core\admin\admin_category(
    'plugins',
    get_string('plugins', 'core')
));

// Local plugins subcategory
$ADMIN->add('plugins', new \core\admin\admin_category(
    'localplugins',
    get_string('localplugins', 'admin')
));

// Authentication plugins subcategory
$ADMIN->add('plugins', new \core\admin\admin_category(
    'authsettings',
    get_string('authentication', 'core')
));
