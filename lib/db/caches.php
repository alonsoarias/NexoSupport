<?php
/**
 * Core Cache Definitions
 *
 * This file contains the core cache definitions for NexoSupport.
 * Plugins can define their own caches in their db/caches.php files.
 *
 * Cache definition structure:
 * - mode: Cache mode (MODE_APPLICATION, MODE_SESSION, MODE_REQUEST)
 * - simplekeys: True if keys are simple strings without special characters
 * - simpledata: True if data is scalar or simple array (allows serialization optimization)
 * - staticacceleration: True to keep a static copy in memory
 * - staticaccelerationsize: Max entries in static cache
 * - ttl: Time to live in seconds (0 = no expiry)
 * - invalidationevents: Array of events that trigger cache purge
 * - canuselocalstore: True if can use local store instead of shared
 * - sharingoptions: Sharing options bitmask
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$definitions = [

    // Configuration cache - stores site configuration
    'config' => [
        'mode' => \core\cache\cache::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 100,
    ],

    // Plugin configuration cache
    'plugin_config' => [
        'mode' => \core\cache\cache::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
    ],

    // String cache - stores language strings
    'string' => [
        'mode' => \core\cache\cache::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 30,
        'canuselocalstore' => true,
    ],

    // Language menu cache
    'langmenu' => [
        'mode' => \core\cache\cache::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'canuselocalstore' => true,
    ],

    // Template cache - stores compiled templates
    'templates' => [
        'mode' => \core\cache\cache::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => false,
        'staticacceleration' => true,
        'staticaccelerationsize' => 50,
        'invalidationevents' => ['theme_changed'],
    ],

    // RBAC/Capabilities cache
    'capabilities' => [
        'mode' => \core\cache\cache::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'invalidationevents' => ['role_updated', 'capability_changed'],
    ],

    // Role definitions cache
    'roledefs' => [
        'mode' => \core\cache\cache::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'invalidationevents' => ['role_updated'],
    ],

    // User access cache - stores user permissions
    'useraccess' => [
        'mode' => \core\cache\cache::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => true,
        'invalidationevents' => ['user_updated', 'role_assigned'],
    ],

    // Context cache
    'contexts' => [
        'mode' => \core\cache\cache::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 100,
    ],

    // Plugin list cache
    'plugin_list' => [
        'mode' => \core\cache\cache::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
    ],

    // Plugin types cache
    'plugin_types' => [
        'mode' => \core\cache\cache::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
    ],

    // Navigation cache
    'navigation' => [
        'mode' => \core\cache\cache::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => false,
        'invalidationevents' => ['user_updated'],
    ],

    // Session user cache - stores user object for current session
    'session_user' => [
        'mode' => \core\cache\cache::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => false,
    ],

    // Request cache - general purpose request-scoped cache
    'request' => [
        'mode' => \core\cache\cache::MODE_REQUEST,
        'simplekeys' => true,
        'simpledata' => false,
    ],

    // Database metadata cache
    'databasemeta' => [
        'mode' => \core\cache\cache::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'canuselocalstore' => true,
    ],

    // Event observers cache
    'observers' => [
        'mode' => \core\cache\cache::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => false,
        'staticacceleration' => true,
        'invalidationevents' => ['plugin_installed', 'plugin_updated'],
    ],

    // Admin tree cache
    'admin_tree' => [
        'mode' => \core\cache\cache::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => false,
        'staticacceleration' => true,
        'invalidationevents' => ['admin_tree_changed'],
    ],

    // File info cache
    'fileinfo' => [
        'mode' => \core\cache\cache::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 3600, // 1 hour
    ],

    // User preferences cache
    'user_preferences' => [
        'mode' => \core\cache\cache::MODE_SESSION,
        'simplekeys' => true,
        'simpledata' => true,
        'invalidationevents' => ['user_updated'],
    ],

];
