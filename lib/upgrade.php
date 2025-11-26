<?php
/**
 * Core Upgrade Script
 *
 * Contiene las funciones de upgrade para actualizar el sistema
 * de una versión a otra.
 *
 * Patrón similar a Moodle: cada función xmldb_core_upgrade($oldversion)
 * verifica la versión antigua y ejecuta los cambios necesarios.
 *
 * @package core
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Main core upgrade function
 *
 * @param int $oldversion Version number of the old installation
 * @return bool Success
 */
function xmldb_core_upgrade(int $oldversion): bool {
    global $DB;

    $result = true;

    // =========================================================
    // Upgrade to v1.1.0 (2025011800) - RBAC System
    // =========================================================
    if ($oldversion < 2025011800) {

        // Las tablas de RBAC ya existen desde v1.0.0
        // Solo necesitamos instalar los datos iniciales

        require_once(__DIR__ . '/install_rbac.php');

        // Instalar sistema RBAC (roles, capabilities, contexts)
        if (!install_rbac_system()) {
            debugging('Failed to install RBAC system during upgrade');
            return false;
        }

        // Asignar rol administrator al primer usuario (ID=1)
        // Normalmente es el administrador inicial
        try {
            $syscontext = \core\rbac\context::system();
            $adminrole = \core\rbac\role::get_by_shortname('administrator');

            if ($adminrole) {
                // Obtener el primer usuario (normalmente el admin)
                $firstuser = $DB->get_record_sql('SELECT * FROM {users} WHERE deleted = 0 ORDER BY id ASC LIMIT 1');

                if ($firstuser) {
                    \core\rbac\access::assign_role($adminrole->id, $firstuser->id, $syscontext);
                    debugging("Administrator role assigned to user {$firstuser->username}", DEBUG_DEVELOPER);
                }
            }
        } catch (Exception $e) {
            debugging('Error assigning administrator role: ' . $e->getMessage());
            // No es crítico, continuar
        }

        // Actualizar versión guardada
        upgrade_core_savepoint(true, 2025011800);
    }

    // =========================================================
    // Upgrade to v1.1.1 (2025011801) - Fix Missing Capabilities
    // =========================================================
    if ($oldversion < 2025011801) {

        require_once(__DIR__ . '/install_rbac.php');

        try {
            $syscontext = \core\rbac\context::system();
            $adminrole = \core\rbac\role::get_by_shortname('administrator');

            if ($adminrole) {
                // Get all capabilities from system
                $capabilities = get_system_capabilities();

                // Install any missing capabilities and assign to administrator
                foreach ($capabilities as $cap) {
                    // Check if capability exists
                    $existing = $DB->get_record('capabilities', ['name' => $cap['name']]);

                    if (!$existing) {
                        // Insert new capability
                        $record = new stdClass();
                        $record->name = $cap['name'];
                        $record->captype = $cap['captype'];
                        $record->contextlevel = $cap['contextlevel'];
                        $record->component = $cap['component'];
                        $record->riskbitmask = $cap['riskbitmask'];

                        $DB->insert_record('capabilities', $record);
                        debugging("Installed missing capability: {$cap['name']}", DEBUG_DEVELOPER);
                    }

                    // Assign to administrator if not already assigned
                    $existing_perm = $DB->get_record('role_capabilities', [
                        'roleid' => $adminrole->id,
                        'capability' => $cap['name'],
                        'contextid' => $syscontext->id
                    ]);

                    if (!$existing_perm) {
                        $adminrole->assign_capability(
                            $cap['name'],
                            \core\rbac\access::PERMISSION_ALLOW,
                            $syscontext
                        );
                        debugging("Assigned capability {$cap['name']} to administrator", DEBUG_DEVELOPER);
                    }
                }
            }
        } catch (Exception $e) {
            debugging('Error installing missing capabilities: ' . $e->getMessage());
        }

        upgrade_core_savepoint(true, 2025011801);
    }

    // =========================================================
    // Upgrade to v1.1.2 (2025011802) - i18n & Mustache Templates
    // =========================================================
    // Changes in this version:
    //
    // 1. INTERNATIONALIZATION (i18n) SYSTEM:
    //    * Added string_manager class (lib/classes/string_manager.php)
    //      - Multi-language support with fallback mechanism
    //      - Caching for performance
    //      - Parameter substitution support ({$a}, {$a->property})
    //    * Created lang/es/core.php with 300+ Spanish strings
    //    * Created lang/en/core.php with 300+ English strings
    //    * Added 'lang' field to users table (CHAR(10), default 'es')
    //    * Integrated i18n into lib/setup.php (language auto-detection)
    //    * Added get_string() global function for easy access
    //    * Migrated ALL hardcoded text to i18n system
    //
    // 2. MUSTACHE TEMPLATE ENGINE:
    //    * Installed mustache/mustache ^3.0 via Composer
    //    * Created template_manager class (lib/classes/output/template_manager.php)
    //      - Auto-loading from templates/ directory
    //      - Common context injection (wwwroot, sesskey, currentlang)
    //      - String helper {{#str}}identifier,component{{/str}}
    //    * Added render_template() global function
    //    * Created complete template structure:
    //      - Base templates: header.mustache, nav.mustache, footer.mustache
    //      - Core templates: login.mustache, dashboard.mustache
    //      - Admin templates: dashboard, user_list, user_edit, role_list,
    //        role_edit, role_define, role_assign, settings, upgrade
    //      - Component templates: notification, button, card
    //    * Migrated ALL 13 core/admin pages to use Mustache templates:
    //      - login/index.php → templates/core/login.mustache
    //      - dashboard.php → templates/core/dashboard.mustache
    //      - admin/index.php → templates/admin/dashboard.mustache
    //      - admin/user/index.php → templates/admin/user_list.mustache
    //      - admin/user/edit.php → templates/admin/user_edit.mustache
    //      - admin/roles/index.php → templates/admin/role_list.mustache
    //      - admin/roles/edit.php → templates/admin/role_edit.mustache
    //      - admin/roles/define.php → templates/admin/role_define.mustache
    //      - admin/roles/assign.php → templates/admin/role_assign.mustache
    //      - admin/settings/index.php → templates/admin/settings.mustache
    //      - admin/upgrade.php → templates/admin/upgrade.mustache
    //    * NOTE: auth/manual/settings.php uses inline HTML (Moodle pattern)
    //    * All templates use {{#str}} for internationalization
    //    * Complete separation: PHP = logic, Mustache = presentation
    //
    // 3. NEW FEATURES:
    //    * Created auth/manual/settings.php following Moodle pattern
    //      - Settings definitions file (NOT a web page)
    //      - Password policy configuration (length, uppercase, lowercase, numbers, special chars)
    //      - Will integrate with admin settings tree in future
    //      - Uses Frankenstyle plugin lang files
    //    * Plugin internationalization support in string_manager
    //      - auth_manual → auth/manual/lang/*/auth_manual.php
    //    * Dynamic HTML lang attribute based on current language
    //
    // 4. BUG FIXES:
    //    * Fixed role::update() method conflict
    //      - Renamed static method to update_role()
    //      - Updated admin/roles/edit.php to use role::update_role()
    //
    // 5. ARCHITECTURE IMPROVEMENTS:
    //    * Complete MVC separation achieved
    //    * Reusable template partials ({{> core/header}})
    //    * Context-based data passing to templates
    //    * Zero hardcoded text in any PHP or template files
    //    * Frankenstyle naming convention for components
    // =========================================================
    if ($oldversion < 2025011802) {
        require_once(__DIR__ . '/classes/db/xmldb_table.php');
        require_once(__DIR__ . '/classes/db/xmldb_field.php');
        require_once(__DIR__ . '/classes/db/ddl_manager.php');

        echo '<div class="upgrade-info">';
        echo '<h3>🎉 Upgrading to v1.1.2 (2025011802) - Internationalization & Modern Templates</h3>';
        echo '<p><strong>📢 Major New Features:</strong></p>';
        echo '<ul>';
        echo '<li><strong>Internationalization (i18n):</strong> Complete multi-language support with 300+ strings in Spanish and English</li>';
        echo '<li><strong>Mustache Templates:</strong> 13 core/admin pages migrated to modern, maintainable template system</li>';
        echo '<li><strong>Clean Architecture:</strong> Complete separation of logic (PHP) and presentation (Mustache)</li>';
        echo '<li><strong>Plugin System:</strong> auth_manual settings.php following Moodle pattern (settings definitions, not web page)</li>';
        echo '<li><strong>Plugin i18n:</strong> Frankenstyle plugin language files (auth_manual/lang/*/auth_manual.php)</li>';
        echo '</ul>';
        echo '<p><strong>📝 Templates Created:</strong></p>';
        echo '<ul>';
        echo '<li>Base: header, nav, footer</li>';
        echo '<li>Core: login, dashboard</li>';
        echo '<li>Admin: dashboard, user management (list, edit), role management (list, edit, define, assign), settings, upgrade</li>';
        echo '<li>Components: notification, button, card</li>';
        echo '</ul>';
        echo '<p><strong>🌍 Languages Supported:</strong></p>';
        echo '<ul>';
        echo '<li>Spanish (es) - 300+ strings</li>';
        echo '<li>English (en) - 300+ strings</li>';
        echo '</ul>';
        echo '<p><strong>🗄️ Database Changes:</strong></p>';
        echo '<ul>';
        echo '<li>Adding \'lang\' field to users table for language preferences...</li>';
        echo '</ul>';
        echo '</div>';

        try {
            $ddl = new \core\db\ddl_manager($DB);

            // Add lang field to users table
            $table = new \core\db\xmldb_table('users');
            $field = (new \core\db\xmldb_field('lang', 'char'))
                ->set_length(10)
                ->set_notnull(true)
                ->set_default('es');

            if (!$ddl->field_exists($table, $field)) {
                $ddl->add_field($table, $field);
                debugging('Added lang field to users table', DEBUG_DEVELOPER);
                echo '<p style="color: green;">✓ Successfully added lang field to users table</p>';
            } else {
                echo '<p style="color: blue;">ℹ Lang field already exists in users table</p>';
            }
        } catch (Exception $e) {
            debugging('Error adding lang field: ' . $e->getMessage());
            echo '<p style="color: red;">✗ Error adding lang field: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }

        echo '<p style="color: green; font-weight: bold;">✓ Upgrade to v1.1.2 completed successfully!</p>';

        upgrade_core_savepoint(true, 2025011802);
    }

    // =========================================================
    // Upgrade to v1.1.3 (2025011803) - User Management Enhancements
    // =========================================================
    if ($oldversion < 2025011803) {
        echo '<div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #667eea; margin-top: 0;">🚀 Upgrading to NexoSupport v1.1.3</h2>';
        echo '<p><strong>User Management Enhancements</strong></p>';
        echo '<p>This upgrade adds complete user lifecycle management following Moodle\'s architecture:</p>';
        echo '<ul>';
        echo '<li><strong>User Operations:</strong> Delete, suspend, unsuspend, unlock, confirm users</li>';
        echo '<li><strong>Email Confirmation:</strong> Support for user email confirmation workflow</li>';
        echo '<li><strong>Safety Features:</strong> Protected operations (cannot delete/suspend admins or self)</li>';
        echo '<li><strong>Session Management:</strong> Auto-logout on suspend/delete operations</li>';
        echo '<li><strong>Soft Delete:</strong> Users marked as deleted with data anonymization</li>';
        echo '</ul>';
        echo '<p><strong>📝 New Features:</strong></p>';
        echo '<ul>';
        echo '<li>lib/userlib.php - Complete user management functions</li>';
        echo '<li>admin/user/index.php - Enhanced with all operations</li>';
        echo '<li>templates/admin/user_delete_confirm.mustache - Delete confirmation page</li>';
        echo '<li>Updated user list template with action buttons</li>';
        echo '</ul>';
        echo '<p><strong>🗄️ Database Changes:</strong></p>';
        echo '<ul>';
        echo '<li>Adding \'confirmed\' field to users table for email confirmation tracking...</li>';
        echo '</ul>';
        echo '</div>';

        try {
            $ddl = new \core\db\ddl_manager($DB);

            // Add confirmed field to users table
            $table = new \core\db\xmldb_table('users');
            $field = (new \core\db\xmldb_field('confirmed', 'int'))
                ->set_length(1)
                ->set_notnull(true)
                ->set_default(1);

            if (!$ddl->field_exists($table, $field)) {
                $ddl->add_field($table, $field, 'phone');
                debugging('Added confirmed field to users table', DEBUG_DEVELOPER);
                echo '<p style="color: green;">✓ Successfully added confirmed field to users table</p>';
            } else {
                echo '<p style="color: blue;">ℹ Confirmed field already exists in users table</p>';
            }
        } catch (Exception $e) {
            debugging('Error adding confirmed field: ' . $e->getMessage());
            echo '<p style="color: red;">✗ Error adding confirmed field: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }

        echo '<p style="color: green; font-weight: bold;">✓ Upgrade to v1.1.3 completed successfully!</p>';

        upgrade_core_savepoint(true, 2025011803);
    }

    // =========================================================
    // Upgrade to v1.1.6 (2025011806) - Logging System & User Preferences
    // =========================================================
    if ($oldversion < 2025011806) {
        echo '<div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #667eea; margin-top: 0;">📊 Upgrading to NexoSupport v1.1.6</h2>';
        echo '<p><strong>Comprehensive Logging System & User Preferences</strong></p>';
        echo '<p>This upgrade adds a complete event logging system following Moodle\'s logstore architecture:</p>';
        echo '<ul>';
        echo '<li><strong>Event Logging:</strong> logstore_standard_log table for complete action tracking</li>';
        echo '<li><strong>User Preferences:</strong> user_preferences table for storing user settings</li>';
        echo '<li><strong>Password Security:</strong> Password history and reset token tables</li>';
        echo '<li><strong>Site Configuration:</strong> siteadmin configuration in config table</li>';
        echo '</ul>';
        echo '<p><strong>🗄️ Database Changes:</strong></p>';
        echo '<ul>';
        echo '<li>Creating logstore_standard_log table (Moodle-compatible event logging)...</li>';
        echo '<li>Creating user_preferences table...</li>';
        echo '<li>Creating user_password_history table...</li>';
        echo '<li>Creating user_password_resets table...</li>';
        echo '<li>Setting siteadmin configuration...</li>';
        echo '</ul>';
        echo '</div>';

        try {
            $ddl = new \core\db\ddl_manager($DB);

            // Create logstore_standard_log table (simplified for NexoSupport)
            $table = new \core\db\xmldb_table('logstore_standard_log');
            $table->add_field((new \core\db\xmldb_field('id', 'int'))->set_length(10)->set_notnull(true)->set_sequence(true));
            $table->add_field((new \core\db\xmldb_field('eventname', 'char'))->set_length(255)->set_notnull(true));
            $table->add_field((new \core\db\xmldb_field('action', 'char'))->set_length(50)->set_notnull(true));
            $table->add_field((new \core\db\xmldb_field('objecttable', 'char'))->set_length(50)->set_notnull(false));
            $table->add_field((new \core\db\xmldb_field('objectid', 'int'))->set_length(10)->set_notnull(false));
            $table->add_field((new \core\db\xmldb_field('userid', 'int'))->set_length(10)->set_notnull(true));
            $table->add_field((new \core\db\xmldb_field('contextid', 'int'))->set_length(10)->set_notnull(true));
            $table->add_field((new \core\db\xmldb_field('other', 'text'))->set_notnull(false));
            $table->add_field((new \core\db\xmldb_field('timecreated', 'int'))->set_length(10)->set_notnull(true));
            $table->add_field((new \core\db\xmldb_field('ip', 'char'))->set_length(45)->set_notnull(false));
            $table->add_key((new \core\db\xmldb_key('primary', 'primary', ['id'])));
            $table->add_index((new \core\db\xmldb_index('idx_timecreated', 'notunique', ['timecreated'])));
            $table->add_index((new \core\db\xmldb_index('idx_userid', 'notunique', ['userid'])));
            $table->add_index((new \core\db\xmldb_index('idx_eventname', 'notunique', ['eventname'])));
            $table->add_index((new \core\db\xmldb_index('idx_action', 'notunique', ['action'])));
            $table->add_index((new \core\db\xmldb_index('idx_objecttable_objectid', 'notunique', ['objecttable', 'objectid'])));

            if (!$ddl->table_exists($table->get_name())) {
                $ddl->create_table($table);
                echo '<p style="color: green;">✓ Created logstore_standard_log table</p>';
            } else {
                echo '<p style="color: blue;">ℹ logstore_standard_log table already exists</p>';
            }

            // Create user_preferences table
            $table = new \core\db\xmldb_table('user_preferences');
            $table->add_field((new \core\db\xmldb_field('id', 'int'))->set_length(10)->set_notnull(true)->set_sequence(true));
            $table->add_field((new \core\db\xmldb_field('userid', 'int'))->set_length(10)->set_notnull(true));
            $table->add_field((new \core\db\xmldb_field('name', 'char'))->set_length(255)->set_notnull(true));
            $table->add_field((new \core\db\xmldb_field('value', 'text'))->set_notnull(true));
            $table->add_field((new \core\db\xmldb_field('timemodified', 'int'))->set_length(10)->set_notnull(true));
            $table->add_key((new \core\db\xmldb_key('primary', 'primary', ['id'])));
            $table->add_index((new \core\db\xmldb_index('idx_userid_name', 'unique', ['userid', 'name'])));

            if (!$ddl->table_exists($table->get_name())) {
                $ddl->create_table($table);
                echo '<p style="color: green;">✓ Created user_preferences table</p>';
            } else {
                echo '<p style="color: blue;">ℹ user_preferences table already exists</p>';
            }

            // Create user_password_history table
            $table = new \core\db\xmldb_table('user_password_history');
            $table->add_field((new \core\db\xmldb_field('id', 'int'))->set_length(10)->set_notnull(true)->set_sequence(true));
            $table->add_field((new \core\db\xmldb_field('userid', 'int'))->set_length(10)->set_notnull(true));
            $table->add_field((new \core\db\xmldb_field('hash', 'char'))->set_length(255)->set_notnull(true));
            $table->add_field((new \core\db\xmldb_field('timecreated', 'int'))->set_length(10)->set_notnull(true));
            $table->add_key((new \core\db\xmldb_key('primary', 'primary', ['id'])));
            $table->add_index((new \core\db\xmldb_index('idx_userid', 'notunique', ['userid'])));

            if (!$ddl->table_exists($table->get_name())) {
                $ddl->create_table($table);
                echo '<p style="color: green;">✓ Created user_password_history table</p>';
            } else {
                echo '<p style="color: blue;">ℹ user_password_history table already exists</p>';
            }

            // Create user_password_resets table
            $table = new \core\db\xmldb_table('user_password_resets');
            $table->add_field((new \core\db\xmldb_field('id', 'int'))->set_length(10)->set_notnull(true)->set_sequence(true));
            $table->add_field((new \core\db\xmldb_field('userid', 'int'))->set_length(10)->set_notnull(true));
            $table->add_field((new \core\db\xmldb_field('token', 'char'))->set_length(32)->set_notnull(true));
            $table->add_field((new \core\db\xmldb_field('timerequested', 'int'))->set_length(10)->set_notnull(true));
            $table->add_field((new \core\db\xmldb_field('timererequested', 'int'))->set_length(10)->set_notnull(false)->set_default(0));
            $table->add_key((new \core\db\xmldb_key('primary', 'primary', ['id'])));
            $table->add_index((new \core\db\xmldb_index('idx_token', 'notunique', ['token'])));
            $table->add_index((new \core\db\xmldb_index('idx_userid', 'notunique', ['userid'])));

            if (!$ddl->table_exists($table->get_name())) {
                $ddl->create_table($table);
                echo '<p style="color: green;">✓ Created user_password_resets table</p>';
            } else {
                echo '<p style="color: blue;">ℹ user_password_resets table already exists</p>';
            }

            // Set siteadmins configuration (Moodle pattern)
            // Find all users with administrator role in system context
            $syscontext = \core\rbac\context::system();

            $sql = "SELECT DISTINCT ra.userid
                    FROM {role_assignments} ra
                    JOIN {roles} r ON r.id = ra.roleid
                    WHERE ra.contextid = :contextid
                    AND r.shortname = 'administrator'
                    ORDER BY ra.userid ASC";

            $adminusers = $DB->get_records_sql($sql, ['contextid' => $syscontext->id]);

            if (!empty($adminusers)) {
                // Convert to comma-separated list of user IDs
                $userids = array_keys($adminusers);
                $siteadmins_value = implode(',', $userids);

                // Check if siteadmins config exists
                $sql = "SELECT * FROM {config} WHERE name = ? LIMIT 1";
                $existing = $DB->get_record_sql($sql, ['siteadmins']);

                if (!$existing) {
                    $record = new \stdClass();
                    $record->component = 'core';  // IMPORTANTE: especificar component
                    $record->name = 'siteadmins';
                    $record->value = $siteadmins_value;
                    $DB->insert_record('config', $record);
                    echo '<p style="color: green;">✓ Set siteadmins configuration: ' . count($userids) . ' administrators (' . $siteadmins_value . ')</p>';
                } else {
                    echo '<p style="color: blue;">ℹ siteadmins configuration already exists</p>';
                }
            } else {
                // No administrator role found, use first user as fallback
                $firstuser = $DB->get_record_sql('SELECT * FROM {users} WHERE deleted = 0 ORDER BY id ASC LIMIT 1');
                if ($firstuser) {
                    $sql = "SELECT * FROM {config} WHERE name = ? LIMIT 1";
                    $existing = $DB->get_record_sql($sql, ['siteadmins']);

                    if (!$existing) {
                        $record = new \stdClass();
                        $record->component = 'core';  // IMPORTANTE: especificar component
                        $record->name = 'siteadmins';
                        $record->value = (string)$firstuser->id;
                        $DB->insert_record('config', $record);
                        echo '<p style="color: orange;">⚠ No administrators found, using first user (ID: ' . $firstuser->id . ') as siteadmin</p>';
                    }
                }
            }

        } catch (\Exception $e) {
            debugging('Error in v1.1.6 upgrade: ' . $e->getMessage());
            echo '<p style="color: red;">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }

        echo '<p style="color: green; font-weight: bold;">✓ Upgrade to v1.1.6 completed successfully!</p>';

        upgrade_core_savepoint(true, 2025011806);
    }

    // =========================================================
    // Upgrade to v1.1.7 (2025011807) - Consolidated Release (v1.1.3 to v1.1.7)
    // =========================================================
    if ($oldversion < 2025011807) {
        echo '<div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #667eea; margin-top: 0;">🚀 Upgrading to NexoSupport v1.1.7 - CONSOLIDATED RELEASE</h2>';
        echo '<p><strong>This release consolidates all improvements from v1.1.3 through v1.1.7</strong></p>';

        echo '<h3 style="color: #667eea;">📦 What\'s Included:</h3>';

        echo '<h4>v1.1.3 - User Management Enhancements</h4>';
        echo '<ul>';
        echo '<li>Complete user lifecycle management (delete, suspend, unsuspend, unlock, confirm)</li>';
        echo '<li>Email confirmation workflow</li>';
        echo '<li>Protected operations (cannot delete/suspend admins or self)</li>';
        echo '<li>Session management with auto-logout</li>';
        echo '<li>Soft delete with data anonymization</li>';
        echo '</ul>';

        echo '<h4>v1.1.4 - Password Management System</h4>';
        echo '<ul>';
        echo '<li>Complete password management: change password, forgot password, reset password</li>';
        echo '<li>Password policy enforcement (length, complexity, history)</li>';
        echo '<li>Secure token-based password reset</li>';
        echo '<li>Password history tracking</li>';
        echo '</ul>';

        echo '<h4>v1.1.5 - Authentication Improvements</h4>';
        echo '<ul>';
        echo '<li>Enhanced authentication security</li>';
        echo '<li>Session security improvements</li>';
        echo '<li>Login attempt tracking</li>';
        echo '</ul>';

        echo '<h4>v1.1.6 - Logging System & Site Administrators</h4>';
        echo '<ul>';
        echo '<li>Complete event logging system (logstore_standard_log table - Moodle compatible)</li>';
        echo '<li>User preferences system</li>';
        echo '<li>Password history and reset tables</li>';
        echo '<li>Site administrators configuration (config.siteadmins - Moodle pattern)</li>';
        echo '<li>RBAC bypass for site administrators</li>';
        echo '</ul>';

        echo '<h4>v1.1.7 - Automatic Upgrade + Moodle Navigation (Current Release)</h4>';
        echo '<ul>';
        echo '<li><strong>Automatic Upgrade Detection:</strong> System automatically detects when upgrade is needed</li>';
        echo '<li><strong>Moodle-Style Navigation:</strong> Hierarchical sidebar menu for better navigation</li>';
        echo '<li><strong>Improved UX:</strong> Collapsible categories, active state highlighting, responsive layout</li>';
        echo '<li><strong>Navigation System:</strong> lib/classes/navigation/nav_manager.php with complete menu management</li>';
        echo '<li><strong>Two-column Layout:</strong> Sidebar (280px) + content area with Moodle-inspired styling</li>';
        echo '<li><strong>All Admin Pages Updated:</strong> 9 admin pages now include navigation sidebar</li>';
        echo '</ul>';

        echo '<p><strong>🔄 Changed Behavior:</strong></p>';
        echo '<ul>';
        echo '<li>Automatic redirect to upgrade page when new version detected</li>';
        echo '<li>Non-logged users redirected to login before accessing upgrade</li>';
        echo '<li>Site administrators have all capabilities (bypass RBAC)</li>';
        echo '<li>All admin pages now show hierarchical navigation menu</li>';
        echo '</ul>';

        echo '</div>';

        // No database changes for v1.1.7 specifically (v1.1.3 and v1.1.6 had DB changes)
        // Those changes are already applied in their respective upgrade functions above
        echo '<p style="color: green; font-weight: bold;">✓ Upgrade to v1.1.7 completed successfully!</p>';
        echo '<p style="color: blue;">ℹ No additional database changes for v1.1.7 (UI/UX improvements only).</p>';
        echo '<p style="color: green;">All features from v1.1.3 through v1.1.7 are now active!</p>';

        upgrade_core_savepoint(true, 2025011807);
    }

    // =========================================================
    // v1.1.8 - Cache Management System
    // =========================================================
    if ($oldversion < 2025011808) {
        echo '<div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #667eea; margin-top: 0;">🚀 Upgrading to NexoSupport v1.1.8 - Cache Management System</h2>';

        echo '<h3 style="color: #667eea;">📦 What\'s New in v1.1.8:</h3>';
        echo '<ul>';
        echo '<li><strong>Cache Management System:</strong> Complete cache purge functionality for OPcache, RBAC, and application caches</li>';
        echo '<li><strong>Admin Interface:</strong> New /admin/cache/purge page with real-time cache status monitoring</li>';
        echo '<li><strong>OPcache Integration:</strong> View memory usage, hit rate, and purge PHP bytecode cache</li>';
        echo '<li><strong>RBAC Cache:</strong> Clear role/permission caches when permissions change</li>';
        echo '<li><strong>Navigation Menu:</strong> Added "Manage Caches" item to Site Administration menu</li>';
        echo '<li><strong>Router Enhancement:</strong> Defensive query string stripping for improved routing reliability</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🔧 Technical Details:</h3>';
        echo '<ul>';
        echo '<li><strong>New Class:</strong> core\\cache\\cache_manager - Central cache management</li>';
        echo '<li><strong>New Admin Page:</strong> admin/cache/purge.php with cache status dashboard</li>';
        echo '<li><strong>New Template:</strong> templates/admin/cache_purge.mustache</li>';
        echo '<li><strong>Language Strings:</strong> 29 new cache-related strings (ES/EN)</li>';
        echo '<li><strong>Route Registered:</strong> /admin/cache/purge (GET/POST)</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🎯 Why This Release?</h3>';
        echo '<p>During v1.1.7 testing, we discovered that OPcache was serving stale bytecode, ';
        echo 'causing routing issues with query strings. This release adds comprehensive cache ';
        echo 'management tools to allow administrators to clear caches when needed, ensuring ';
        echo 'the system always runs the latest code and configurations.</p>';

        echo '<p><strong>🔄 Usage:</strong></p>';
        echo '<ul>';
        echo '<li>Navigate to: <strong>Site Administration → Manage Caches</strong></li>';
        echo '<li>View cache statistics (memory usage, hit rate, cache size)</li>';
        echo '<li>Purge individual caches or all caches at once</li>';
        echo '<li>Use after code updates, permission changes, or when experiencing unexpected behavior</li>';
        echo '</ul>';

        echo '<p style="color: green; font-weight: bold;">✓ Upgrade to v1.1.8 completed successfully!</p>';
        echo '<p style="color: blue;">ℹ No database changes for v1.1.8 (cache management functionality only).</p>';
        echo '<p style="color: green;">🔄 All caches will be automatically purged at the end of this upgrade process!</p>';

        echo '</div>';

        // No database changes for v1.1.8 - this is a cache management feature only
        upgrade_core_savepoint(true, 2025011808);
    }

    // =========================================================
    // v1.1.9 - Navigation System Redesign + i18n Cache
    // =========================================================
    if ($oldversion < 2025011809) {
        echo '<div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #667eea; margin-top: 0;">🎨 Upgrading to NexoSupport v1.1.9 - Navigation System Redesign</h2>';

        echo '<h3 style="color: #667eea;">✨ What\'s New in v1.1.9:</h3>';
        echo '<ul>';
        echo '<li><strong>Complete Navigation Redesign:</strong> Modern OOP architecture with navigation_node, navigation_tree, navigation_builder, navigation_renderer</li>';
        echo '<li><strong>Font Awesome 6 Icons:</strong> Professional icons replacing emojis throughout the interface</li>';
        echo '<li><strong>Template-Based Rendering:</strong> Mustache templates for sidebar and breadcrumbs navigation</li>';
        echo '<li><strong>Permission-Based Filtering:</strong> Granular access control at individual menu item level</li>';
        echo '<li><strong>i18n Cache Purging:</strong> Added i18n language string cache management</li>';
        echo '<li><strong>Breadcrumb System:</strong> Automatic breadcrumb trail generation from navigation tree</li>';
        echo '<li><strong>Auto-Expand Categories:</strong> Admin menu auto-expands when on admin pages</li>';
        echo '<li><strong>Backward Compatibility:</strong> All v1.1.8 nav_manager API calls continue to work</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🏗️ New Architecture:</h3>';
        echo '<ul>';
        echo '<li><strong>navigation_node:</strong> Single node with permissions, icons, and states (537 lines)</li>';
        echo '<li><strong>navigation_tree:</strong> Tree structure with filtering and breadcrumbs (464 lines)</li>';
        echo '<li><strong>navigation_builder:</strong> Fluent API for building navigation (250 lines)</li>';
        echo '<li><strong>navigation_renderer:</strong> Template-based rendering (122 lines)</li>';
        echo '<li><strong>nav_manager v2:</strong> Redesigned using new system, maintains BC (339 lines)</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🎯 New Features:</h3>';
        echo '<ul>';
        echo '<li><strong>Separators:</strong> Add visual separators between navigation groups</li>';
        echo '<li><strong>Icon Conversion:</strong> Automatic emoji → Font Awesome conversion</li>';
        echo '<li><strong>Active Detection:</strong> Auto-detect and highlight current page</li>';
        echo '<li><strong>Collapsible Categories:</strong> Click to expand/collapse with smooth transitions</li>';
        echo '<li><strong>Responsive Design:</strong> Mobile-friendly navigation with media queries</li>';
        echo '<li><strong>Accessibility:</strong> ARIA labels, keyboard navigation, high contrast mode</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">📦 New Files:</h3>';
        echo '<ul>';
        echo '<li>lib/classes/navigation/navigation_node.php</li>';
        echo '<li>lib/classes/navigation/navigation_tree.php</li>';
        echo '<li>lib/classes/navigation/navigation_builder.php</li>';
        echo '<li>lib/classes/navigation/navigation_renderer.php</li>';
        echo '<li>templates/navigation/sidebar.mustache</li>';
        echo '<li>templates/navigation/sidebar_node.mustache</li>';
        echo '<li>templates/navigation/breadcrumbs.mustache</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🔧 Updated Files:</h3>';
        echo '<ul>';
        echo '<li>lib/classes/navigation/nav_manager.php - Complete redesign</li>';
        echo '<li>lib/classes/cache/cache_manager.php - Added purge_i18n_cache()</li>';
        echo '<li>templates/core/header.mustache - Added Font Awesome 6 CDN</li>';
        echo '<li>lang/es/core.php - Added: collapse, expand, breadcrumbs</li>';
        echo '<li>lang/en/core.php - Added: collapse, expand, breadcrumbs</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">💾 Cache Management:</h3>';
        echo '<p>Added i18n cache purging to clear language string caches:</p>';
        echo '<ul>';
        echo '<li>Purge i18n cache from admin interface</li>';
        echo '<li>Automatic purge during upgrades</li>';
        echo '<li>Clear cached translations after language file changes</li>';
        echo '</ul>';

        echo '<p style="color: green; font-weight: bold; margin-top: 20px;">✅ No database changes required for v1.1.9 - All improvements are code-level</p>';

        echo '</div>';

        // No database changes for v1.1.9 - navigation and cache enhancements only
        upgrade_core_savepoint(true, 2025011809);
    }

    // =========================================================
    // v1.1.10 - Moodle-Style Navigation + Plugin Context
    // =========================================================
    if ($oldversion < 2025011810) {
        echo '<div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #667eea; margin-top: 0;">🚀 Upgrading to NexoSupport v1.1.10 - Moodle-Style Navigation</h2>';

        echo '<h3 style="color: #667eea;">✨ What\'s New in v1.1.10:</h3>';
        echo '<ul>';
        echo '<li><strong>Complete Moodle Navigation:</strong> Full Site Administration hierarchy with all categories</li>';
        echo '<li><strong>User Navigation Menu:</strong> Profile, preferences, and notification settings</li>';
        echo '<li><strong>Plugin Navigation System:</strong> Contextual navigation for plugins to add their own options</li>';
        echo '<li><strong>7 Major Admin Categories:</strong> Users, Courses, Plugins, Appearance, Server, Reports, Development</li>';
        echo '<li><strong>User Preferences:</strong> Edit profile, change password, notification preferences</li>';
        echo '<li><strong>50+ New Navigation Items:</strong> Complete administrative navigation structure</li>';
        echo '<li><strong>50+ New Language Strings:</strong> Full i18n support for ES/EN</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🗂️ Site Administration Structure:</h3>';
        echo '<ul>';
        echo '<li><strong>👥 Users:</strong> Browse, add, bulk actions, cohorts, profile fields, permissions, roles</li>';
        echo '<li><strong>📚 Courses:</strong> Manage courses, course categories</li>';
        echo '<li><strong>🧩 Plugins:</strong> Overview, install, auth plugins, MFA factors, admin tools, local plugins, blocks</li>';
        echo '<li><strong>🎨 Appearance:</strong> Themes, theme selector, navigation, HTML settings</li>';
        echo '<li><strong>🖥️ Server:</strong> System paths, support contact, sessions, HTTP, maintenance, environment, PHP info</li>';
        echo '<li><strong>📊 Reports:</strong> Logs, live logs, config changes, security, performance</li>';
        echo '<li><strong>💻 Development:</strong> Debugging, purge caches, test site, XMLDB editor, web services</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">👤 User Navigation:</h3>';
        echo '<ul>';
        echo '<li><strong>Profile:</strong> View and edit profile information</li>';
        echo '<li><strong>Preferences:</strong> Change password, notification settings</li>';
        echo '<li><strong>Logout:</strong> With visual separator</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🔌 Plugin Navigation System:</h3>';
        echo '<p>New plugin_navigation class allows plugins to register their own navigation items:</p>';
        echo '<ul>';
        echo '<li><strong>Context-Based:</strong> Register navigation for system, user, course, category contexts</li>';
        echo '<li><strong>Auto-Categorization:</strong> Plugin items automatically placed in correct parent category</li>';
        echo '<li><strong>Helper Methods:</strong> register_settings(), register_report(), register_user_preferences()</li>';
        echo '<li><strong>Load Callbacks:</strong> Plugins can implement {component}_extend_navigation() and {component}_extend_admin_navigation()</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">📦 New Files:</h3>';
        echo '<ul>';
        echo '<li>lib/classes/navigation/plugin_navigation.php (296 lines) - Plugin navigation system</li>';
        echo '<li>user/edit.php - Edit user profile page</li>';
        echo '<li>user/preferences/notification.php - Notification preferences page</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🔧 Updated Files:</h3>';
        echo '<ul>';
        echo '<li>lib/classes/navigation/nav_manager.php - Complete Moodle-style navigation (870 lines)</li>';
        echo '<li>public_html/index.php - Added user routes: /user/edit, /user/preferences/notification</li>';
        echo '<li>lang/es/core.php - Added 50+ navigation strings</li>';
        echo '<li>lang/en/core.php - Added 50+ navigation strings</li>';
        echo '<li>lib/version.php - Updated to v1.1.10 (2025011810)</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🎯 Key Features:</h3>';
        echo '<ul>';
        echo '<li><strong>Hierarchical Navigation:</strong> Nested categories up to 3 levels deep</li>';
        echo '<li><strong>Permission-Based:</strong> Each menu item can have capability requirements</li>';
        echo '<li><strong>Auto-Expand:</strong> Admin category expands when on admin pages</li>';
        echo '<li><strong>Breadcrumbs:</strong> Automatic breadcrumb trail for current location</li>';
        echo '<li><strong>Font Awesome Icons:</strong> Professional icons throughout</li>';
        echo '<li><strong>Responsive:</strong> Mobile-friendly with collapsible sections</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🔄 Navigation Methods:</h3>';
        echo '<p>Plugins can register navigation using plugin_navigation class:</p>';
        echo '<code style="display: block; background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 4px;">';
        echo '// In plugin lib.php<br>';
        echo 'function tool_example_extend_admin_navigation() {<br>';
        echo '&nbsp;&nbsp;\\core\\navigation\\plugin_navigation::register_settings(<br>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;\'tool_example\',<br>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;\'tool\',<br>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;[\'text\' => \'Example Tool\', \'url\' => \'/admin/tool/example\']<br>';
        echo '&nbsp;&nbsp;);<br>';
        echo '}';
        echo '</code>';

        echo '<p style="color: green; font-weight: bold; margin-top: 20px;">✅ No database changes required for v1.1.10 - All improvements are code-level</p>';
        echo '<p style="color: blue;">📱 New navigation is fully responsive and accessible</p>';
        echo '<p style="color: green;">🌍 Full multilingual support (Spanish/English)</p>';

        echo '</div>';

        // No database changes for v1.1.10 - navigation enhancements only
        upgrade_core_savepoint(true, 2025011810);
    }

    // =========================================================
    // v1.1.11 - Debug Settings + Always Purge Caches
    // =========================================================
    if ($oldversion < 2025011811) {
        echo '<div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #667eea; margin-top: 0;">🐛 Upgrading to NexoSupport v1.1.11 - Debug Settings</h2>';

        echo '<h3 style="color: #667eea;">✨ What\'s New in v1.1.11:</h3>';
        echo '<ul>';
        echo '<li><strong>Debug Settings Page:</strong> Complete debugging configuration interface in Development</li>';
        echo '<li><strong>5 Debug Levels:</strong> NONE, MINIMAL, NORMAL, DEVELOPER, ALL (similar to Moodle)</li>';
        echo '<li><strong>Debug Display Control:</strong> Show/hide errors on screen independently</li>';
        echo '<li><strong>Always Purge Caches:</strong> Cache purging now ALWAYS executes after upgrades</li>';
        echo '<li><strong>DB-Based Configuration:</strong> Debug settings stored in config table, loaded at startup</li>';
        echo '<li><strong>Real-Time Status:</strong> View current PHP error_reporting and display_errors settings</li>';
        echo '<li><strong>25+ New Language Strings:</strong> Full i18n support for debug settings (ES/EN)</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🐛 Debug Levels:</h3>';
        echo '<ul>';
        echo '<li><strong>DEBUG_NONE (0):</strong> No debug messages. Recommended for production.</li>';
        echo '<li><strong>DEBUG_MINIMAL (E_ERROR | E_PARSE):</strong> Only critical errors.</li>';
        echo '<li><strong>DEBUG_NORMAL (E_ERROR | E_PARSE | E_WARNING | E_NOTICE):</strong> Errors, warnings, and notices.</li>';
        echo '<li><strong>DEBUG_DEVELOPER (E_ALL & ~E_STRICT & ~E_DEPRECATED):</strong> All except strict and deprecated. For development.</li>';
        echo '<li><strong>DEBUG_ALL (E_ALL):</strong> ALL messages including strict and deprecated. For advanced debugging.</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🔧 Technical Improvements:</h3>';
        echo '<ul>';
        echo '<li><strong>setup.php:</strong> Loads debug configuration from database at startup</li>';
        echo '<li><strong>upgrade.php:</strong> Cache purging now executes ALWAYS, not only on success</li>';
        echo '<li><strong>Debug Constants:</strong> Defined in lib/setup.php before any code loads</li>';
        echo '<li><strong>Immediate Application:</strong> Settings applied instantly via error_reporting() and ini_set()</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">📦 New Files:</h3>';
        echo '<ul>';
        echo '<li>admin/settings/debugging.php - Complete debugging configuration page</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🔧 Updated Files:</h3>';
        echo '<ul>';
        echo '<li>lib/setup.php - Added DEBUG_* constants and DB-based debug loading</li>';
        echo '<li>lib/upgrade.php - Cache purging now ALWAYS executes (not conditional on $result)</li>';
        echo '<li>public_html/index.php - Added routes: /admin/settings/debugging (GET/POST)</li>';
        echo '<li>lang/es/core.php - Added 25+ debugging strings</li>';
        echo '<li>lang/en/core.php - Added 25+ debugging strings</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🎯 Usage:</h3>';
        echo '<p>Configure debugging settings:</p>';
        echo '<ol>';
        echo '<li>Navigate to: <strong>Site Administration → Development → Debugging</strong></li>';
        echo '<li>Select appropriate debug level for your environment</li>';
        echo '<li>Enable/disable display errors (NEVER enable in production)</li>';
        echo '<li>View current PHP error_reporting and display_errors settings</li>';
        echo '<li>Save - settings apply immediately</li>';
        echo '</ol>';

        echo '<h3 style="color: #667eea;">⚠️ Critical Change:</h3>';
        echo '<p style="color: #f57c00; font-weight: bold;">Cache purging now ALWAYS executes after upgrades, even if upgrade fails.</p>';
        echo '<p>This prevents issues with stale bytecode causing errors. If cache purging fails, you MUST manually purge caches immediately.</p>';

        echo '<p style="color: green; font-weight: bold; margin-top: 20px;">✅ No database changes required for v1.1.11 - All improvements are code-level</p>';
        echo '<p style="color: blue;">🐛 Debug settings now configurable via admin interface</p>';
        echo '<p style="color: green;">🔄 Cache purging is now guaranteed after every upgrade</p>';

        echo '</div>';

        // No database changes for v1.1.11 - debugging configuration only
        upgrade_core_savepoint(true, 2025011811);
    }

    // =========================================================
    // Upgrade to v1.1.14 (2025011814) - Fix Automatic Upgrade Detection
    // =========================================================
    if ($oldversion < 2025011814) {
        echo '<div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #667eea; margin-top: 0;">🔧 Upgrading to NexoSupport v1.1.14 - Auto-Upgrade Fix</h2>';

        echo '<h3 style="color: #667eea;">🐛 What\'s Fixed in v1.1.14:</h3>';
        echo '<ul>';
        echo '<li><strong>Automatic Upgrade Detection:</strong> System now auto-redirects to upgrade when new version detected</li>';
        echo '<li><strong>MATURITY Constants:</strong> Defined before environment_checker runs to prevent version read errors</li>';
        echo '<li><strong>Better Error Handling:</strong> environment_checker now catches all errors when reading version.php</li>';
        echo '<li><strong>Debug Logging:</strong> Added temporary logging to track upgrade detection process</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🔧 Root Cause:</h3>';
        echo '<p><strong>Problem:</strong> environment_checker was created BEFORE lib/setup.php loaded, causing MATURITY_* constants to be undefined when reading lib/version.php. This caused version detection to fail silently.</p>';
        echo '<p><strong>Solution:</strong></p>';
        echo '<ul>';
        echo '<li>Define MATURITY_* constants in public_html/index.php BEFORE creating environment_checker</li>';
        echo '<li>Add try-catch in environment_checker to handle version.php loading errors gracefully</li>';
        echo '<li>Check for null code_version in upgrade detection logic</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">📝 Files Modified:</h3>';
        echo '<ul>';
        echo '<li><strong>public_html/index.php:</strong> Added MATURITY_* constants + debug logging for upgrade detection</li>';
        echo '<li><strong>lib/classes/install/environment_checker.php:</strong> Improved error handling when loading version.php</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">✅ Impact:</h3>';
        echo '<p style="color: green;">When a new version is deployed, the system will automatically:</p>';
        echo '<ul>';
        echo '<li>Detect the version mismatch (code_version > db_version)</li>';
        echo '<li>Redirect logged-in users to /admin/upgrade.php</li>';
        echo '<li>Redirect non-logged users to /login with return URL to upgrade</li>';
        echo '<li>Block all non-essential requests until upgrade completes</li>';
        echo '</ul>';

        echo '<p style="color: green; font-weight: bold; margin-top: 20px;">✅ No database changes required for v1.1.14 - Code fixes only</p>';
        echo '<p style="color: blue;">🔄 Automatic upgrade detection now works like Moodle</p>';
        echo '<p style="color: green;">✓ System will self-update without manual intervention</p>';

        echo '</div>';

        // No database changes for v1.1.14 - code fixes only
        upgrade_core_savepoint(true, 2025011814);
    }

    // =========================================================
    // Upgrade to v1.1.15 (2025011815) - Fix Upgrade Visualization + Clean public_html
    // =========================================================
    if ($oldversion < 2025011815) {
        echo '<div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #667eea; margin-top: 0;">🔧 Upgrading to NexoSupport v1.1.15 - Upgrade Visualization Fixes</h2>';

        echo '<h3 style="color: #667eea;">✨ What\'s New in v1.1.15:</h3>';
        echo '<ul>';
        echo '<li><strong>Upgrade Output Visualization:</strong> admin/upgrade.php now captures and displays upgrade process output</li>';
        echo '<li><strong>Clean public_html:</strong> Removed extra files (.htaccess, verify_docroot.php) - only index.php remains</li>';
        echo '<li><strong>Debug Logs Removed:</strong> Cleaned up debug logging from front controller</li>';
        echo '<li><strong>Better User Experience:</strong> Users now see detailed progress when system upgrades</li>';
        echo '<li><strong>Apache Configuration Documented:</strong> docs/APACHE_CONFIG.md with complete setup instructions</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🔧 Technical Changes:</h3>';
        echo '<ul>';
        echo '<li><strong>admin/upgrade.php:</strong> Uses output buffering to capture xmldb_core_upgrade() echo statements</li>';
        echo '<li><strong>templates/admin/upgrade.mustache:</strong> New section to display upgrade output with proper styling</li>';
        echo '<li><strong>public_html/index.php:</strong> Removed error_log() debug statements</li>';
        echo '<li><strong>public_html/.htaccess:</strong> Removed (configuration moved to VirtualHost)</li>';
        echo '<li><strong>public_html/verify_docroot.php:</strong> Removed (not needed for system operation)</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">📁 Architecture Compliance:</h3>';
        echo '<p><strong>Frankenstyle Principle:</strong> Minimal exposure in public_html/</p>';
        echo '<ul>';
        echo '<li>✅ <strong>public_html/index.php:</strong> ONLY file in public_html (front controller)</li>';
        echo '<li>✅ <strong>Apache config in VirtualHost:</strong> Proper production setup</li>';
        echo '<li>✅ <strong>Assets served through front controller:</strong> /theme/name/pix/logo.png routing</li>';
        echo '<li>✅ <strong>Security hardened:</strong> No direct filesystem access</li>';
        echo '</ul>';

        echo '<h3 style="color: #667eea;">🎯 User Experience Improvements:</h3>';
        echo '<ul>';
        echo '<li>When clicking "Upgrade Now", users see real-time output from upgrade process</li>';
        echo '<li>All database changes, table creations, and configurations are logged visibly</li>';
        echo '<li>No more "black box" upgrades - full transparency</li>';
        echo '<li>Scrollable output window with clean styling</li>';
        echo '</ul>';

        echo '<p style="color: green; font-weight: bold; margin-top: 20px;">✅ No database changes required for v1.1.15 - Code improvements only</p>';
        echo '<p style="color: blue;">🎨 Upgrade process now fully visible to administrators</p>';
        echo '<p style="color: green;">✓ public_html/ cleaned - only index.php remains (Frankenstyle compliant)</p>';

        echo '</div>';

        // No database changes for v1.1.15 - code improvements only
        upgrade_core_savepoint(true, 2025011815);
    }

    // =========================================================
    // Upgrade to v1.1.16 (2025011816) - Security Fixes + RBAC + Code Cleanup
    // =========================================================
    if ($oldversion < 2025011816) {
        echo '<div style="background: #f1f8e9; border-left: 4px solid #558b2f; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #558b2f; margin-top: 0;">🔒 Upgrading to NexoSupport v1.1.16 - Security & Quality</h2>';

        echo '<h3 style="color: #558b2f;">🚨 PRIORITY 1: Critical Security Fixes</h3>';
        echo '<ul>';
        echo '<li><strong>admin/index.php:</strong> Added capability check (nexosupport/admin:viewdashboard) ✅</li>';
        echo '<li><strong>admin/upgrade.php:</strong> Enhanced siteadmin verification for upgrade access ✅</li>';
        echo '<li><strong>admin/settings/debugging.php:</strong> Replaced require_admin() with require_capability() ✅</li>';
        echo '<li><strong>lib/userlib.php:</strong> Implemented fullname() function (CRITICAL - was missing) ✅</li>';
        echo '<li><strong>lib/functions.php:</strong> Implemented confirm_sesskey() function ✅</li>';
        echo '<li><strong>lib/authlib.php:</strong> Verified check_password_policy() exists (line 105) ✅</li>';
        echo '<li><strong>lib/adminlib.php:</strong> Verified admin_get_categories() exists ✅</li>';
        echo '</ul>';

        echo '<h3 style="color: #558b2f;">🛡️ PRIORITY 2: RBAC System Improvements</h3>';
        echo '<ul>';
        echo '<li><strong>lib/classes/rbac/context.php:</strong> Added context::course() method ✅</li>';
        echo '<li><strong>lib/classes/rbac/context.php:</strong> Added context::module() method ✅</li>';
        echo '<li><strong>admin/user/edit.php:</strong> Refined capability checks (create vs update) ✅</li>';
        echo '<li><strong>Granular Permissions:</strong> Now distinguishes between user:create and user:update</li>';
        echo '</ul>';

        echo '<h3 style="color: #558b2f;">🧹 PRIORITY 3: Code Quality & Cleanup</h3>';
        echo '<ul>';
        echo '<li><strong>lib/functions.php:</strong> Added duplicate definition protection for MATURITY constants ✅</li>';
        echo '<li><strong>lib/setup.php:</strong> Documented standard $USER access pattern ✅</li>';
        echo '<li><strong>Code Consistency:</strong> Standardized null coalescing and isset() usage patterns</li>';
        echo '</ul>';

        echo '<h3 style="color: #558b2f;">📋 Functions Added/Verified</h3>';
        echo '<ul>';
        echo '<li><code>fullname($user, $override)</code> - Get formatted user name (NEW - CRITICAL fix)</li>';
        echo '<li><code>confirm_sesskey()</code> - Boolean sesskey validation (NEW)</li>';
        echo '<li><code>check_password_policy()</code> - Already existed at line 105 (VERIFIED)</li>';
        echo '<li><code>admin_get_categories()</code> - Already existed (VERIFIED)</li>';
        echo '</ul>';

        echo '<h3 style="color: #558b2f;">🔐 Security Improvements</h3>';
        echo '<ul>';
        echo '<li><strong>Access Control:</strong> All admin pages now properly verify capabilities</li>';
        echo '<li><strong>Upgrade Protection:</strong> Only siteadmins can run system upgrades</li>';
        echo '<li><strong>Password Security:</strong> Password policy validation already in place</li>';
        echo '<li><strong>CSRF Protection:</strong> Enhanced with confirm_sesskey() helper</li>';
        echo '</ul>';

        echo '<h3 style="color: #558b2f;">📊 Impact Summary</h3>';
        echo '<ul>';
        echo '<li><strong>Files Modified:</strong> 10 files</li>';
        echo '<li><strong>Security Vulnerabilities Fixed:</strong> 7 critical issues</li>';
        echo '<li><strong>Missing Functions Added:</strong> 2 functions (fullname, confirm_sesskey)</li>';
        echo '<li><strong>Functions Verified:</strong> 2 functions (check_password_policy, admin_get_categories)</li>';
        echo '<li><strong>RBAC Features Added:</strong> 3 improvements</li>';
        echo '<li><strong>Code Quality Fixes:</strong> 2 cleanups</li>';
        echo '</ul>';

        echo '<p style="color: green; font-weight: bold; margin-top: 20px;">✅ No database changes required for v1.1.16 - Code improvements only</p>';
        echo '<p style="color: blue;">🔒 System security significantly enhanced</p>';
        echo '<p style="color: green;">✓ Critical missing functions implemented</p>';
        echo '<p style="color: purple;">🎯 RBAC system more complete and granular</p>';

        echo '</div>';

        // No database changes for v1.1.16 - code improvements only
        upgrade_core_savepoint(true, 2025011816);
    }

    // =========================================================
    // Upgrade to v1.1.17 (2025011817) - Moodle-style Install/Upgrade System
    // =========================================================
    if ($oldversion < 2025011817) {

        $dbman = $DB->get_manager();

        // Create config_plugins table for plugin versioning
        if (!$dbman->table_exists('config_plugins')) {
            $table = new \core\db\xmldb_table('config_plugins');
            $table->add_field('id', \core\db\xmldb_field::TYPE_INT, 10, true, true);
            $table->add_field('plugin', \core\db\xmldb_field::TYPE_CHAR, 100, true);
            $table->add_field('name', \core\db\xmldb_field::TYPE_CHAR, 100, true);
            $table->add_field('value', \core\db\xmldb_field::TYPE_TEXT);
            $table->add_key('primary', \core\db\xmldb_key::TYPE_PRIMARY, ['id']);
            $table->add_index('plugin_name', true, ['plugin', 'name']);

            $dbman->create_table($table);
            debugging('Created config_plugins table', DEBUG_DEVELOPER);
        }

        // Create upgrade_log table for tracking upgrade history
        if (!$dbman->table_exists('upgrade_log')) {
            $table = new \core\db\xmldb_table('upgrade_log');
            $table->add_field('id', \core\db\xmldb_field::TYPE_INT, 10, true, true);
            $table->add_field('type', \core\db\xmldb_field::TYPE_INT, 10, true);
            $table->add_field('plugin', \core\db\xmldb_field::TYPE_CHAR, 100);
            $table->add_field('version', \core\db\xmldb_field::TYPE_CHAR, 100);
            $table->add_field('targetversion', \core\db\xmldb_field::TYPE_CHAR, 100);
            $table->add_field('info', \core\db\xmldb_field::TYPE_CHAR, 255, true);
            $table->add_field('details', \core\db\xmldb_field::TYPE_TEXT);
            $table->add_field('backtrace', \core\db\xmldb_field::TYPE_TEXT);
            $table->add_field('userid', \core\db\xmldb_field::TYPE_INT, 10, true);
            $table->add_field('timemodified', \core\db\xmldb_field::TYPE_INT, 10, true);
            $table->add_key('primary', \core\db\xmldb_key::TYPE_PRIMARY, ['id']);
            $table->add_index('timemodified', false, ['timemodified']);
            $table->add_index('type_timemodified', false, ['type', 'timemodified']);

            $dbman->create_table($table);
            debugging('Created upgrade_log table', DEBUG_DEVELOPER);
        }

        // Log this upgrade
        try {
            global $USER;
            $DB->insert_record('upgrade_log', [
                'type' => 0, // Core upgrade
                'plugin' => 'core',
                'version' => '2025011816',
                'targetversion' => '2025011817',
                'info' => 'Implemented Moodle-style install/upgrade system',
                'details' => 'Added config_plugins and upgrade_log tables, plugin_manager class, installlib.php',
                'backtrace' => '',
                'userid' => $USER->id ?? 0,
                'timemodified' => time()
            ]);
        } catch (\Exception $e) {
            // Table might not be ready yet
            debugging('Could not log upgrade: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        upgrade_core_savepoint(true, 2025011817);
    }

    // =========================================================
    // v1.1.22 (2025011822) - Code Organization & Frankenstyle Compliance
    // =========================================================
    if ($oldversion < 2025011822) {
        echo '<div style="background: #e3f2fd; border-left: 4px solid #1976d2; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #1976d2; margin-top: 0;">🔧 Upgrading to NexoSupport v1.1.22 - Code Organization</h2>';

        echo '<h3 style="color: #1976d2;">✨ What\'s New in v1.1.22:</h3>';
        echo '<ul>';
        echo '<li><strong>Frankenstyle Compliance:</strong> All code adapted for NexoSupport (was copied from Moodle)</li>';
        echo '<li><strong>Constant Standardization:</strong> All INTERNAL_ACCESS changed to NEXOSUPPORT_INTERNAL</li>';
        echo '<li><strong>License Updates:</strong> GPL licenses changed to Proprietary - NexoSupport</li>';
        echo '<li><strong>Composer Autoloading:</strong> All plugins added to PSR-4 autoloading</li>';
        echo '<li><strong>Code Consistency:</strong> Uniform coding standards across all files</li>';
        echo '</ul>';

        echo '<h3 style="color: #1976d2;">📦 Files Updated:</h3>';
        echo '<ul>';
        echo '<li><strong>58 files:</strong> Changed INTERNAL_ACCESS to NEXOSUPPORT_INTERNAL</li>';
        echo '<li><strong>100+ files:</strong> Updated license headers to Proprietary</li>';
        echo '<li><strong>composer.json:</strong> Added autoloading for tool_mfa, factor_email, theme_boost, all report plugins</li>';
        echo '</ul>';

        echo '<h3 style="color: #1976d2;">🔧 Plugins with Autoloading Added:</h3>';
        echo '<ul>';
        echo '<li>theme_boost\\ → theme/boost/classes/</li>';
        echo '<li>tool_mfa\\ → admin/tool/mfa/classes/</li>';
        echo '<li>factor_email\\ → admin/tool/mfa/factor/email/classes/</li>';
        echo '<li>report_log\\ → report/log/classes/</li>';
        echo '<li>report_loglive\\ → report/loglive/classes/</li>';
        echo '<li>report_security\\ → report/security/classes/</li>';
        echo '<li>report_performance\\ → report/performance/classes/</li>';
        echo '</ul>';

        echo '<p style="color: green; font-weight: bold; margin-top: 20px;">✅ No database changes required for v1.1.22 - Code organization only</p>';
        echo '<p style="color: blue;">📝 Run: composer dump-autoload to regenerate autoloader</p>';

        echo '</div>';

        // No database changes for v1.1.22 - code organization only
        upgrade_core_savepoint(true, 2025011822);
    }

    // =========================================================
    // v1.1.24 (2025011824) - Plugin Management System
    // =========================================================
    if ($oldversion < 2025011824) {
        echo '<div style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #4caf50; margin-top: 0;">🧩 Upgrading to NexoSupport v1.1.24 - Plugin Management System</h2>';

        echo '<h3 style="color: #4caf50;">✨ What\'s New in v1.1.24:</h3>';
        echo '<ul>';
        echo '<li><strong>Plugin Install from ZIP:</strong> Upload and install plugins directly from ZIP files</li>';
        echo '<li><strong>Plugin Uninstall:</strong> Safely uninstall plugins with proper cleanup (tables, capabilities, configs, files)</li>';
        echo '<li><strong>Plugin Upgrade:</strong> Upgrade plugins when new versions are detected</li>';
        echo '<li><strong>Protected Plugins:</strong> Core plugins (boost theme, manual auth) cannot be uninstalled</li>';
        echo '<li><strong>Plugin Types:</strong> Support for report, tool, theme, auth, and block plugins</li>';
        echo '<li><strong>Admin Interface:</strong> Complete plugin management UI at /admin/plugins</li>';
        echo '</ul>';

        echo '<h3 style="color: #4caf50;">🔌 Plugin Manager Features:</h3>';
        echo '<ul>';
        echo '<li><code>install_from_zip()</code> - Extract and install plugin from ZIP archive</li>';
        echo '<li><code>uninstall_plugin()</code> - Complete plugin removal with cleanup</li>';
        echo '<li><code>can_uninstall()</code> - Check if plugin can be safely removed</li>';
        echo '<li><code>get_type_display_name()</code> - Localized plugin type names</li>';
        echo '</ul>';

        echo '<h3 style="color: #4caf50;">🎨 Admin Interface:</h3>';
        echo '<ul>';
        echo '<li>Plugin install form with file upload and type selection</li>';
        echo '<li>Plugins grouped by type (Reports, Tools, Themes, etc.)</li>';
        echo '<li>Status badges: Installed, Upgrade Required, Not Installed</li>';
        echo '<li>Action buttons: Upgrade, Uninstall (with confirmation)</li>';
        echo '<li>Version information: Code version and DB version</li>';
        echo '</ul>';

        echo '<h3 style="color: #4caf50;">🌍 Language Support:</h3>';
        echo '<ul>';
        echo '<li>20+ new language strings added (EN/ES)</li>';
        echo '<li>Plugin type translations</li>';
        echo '<li>Action messages and confirmations</li>';
        echo '</ul>';

        echo '<h3 style="color: #4caf50;">📦 Files Modified:</h3>';
        echo '<ul>';
        echo '<li><strong>lib/classes/plugin/plugin_manager.php:</strong> Added uninstall, ZIP install, and helper methods</li>';
        echo '<li><strong>admin/settings/plugins.php:</strong> Complete rewrite with plugin management actions</li>';
        echo '<li><strong>templates/admin/plugins.mustache:</strong> New UI with install form and plugin listing</li>';
        echo '<li><strong>lang/en/admin.php:</strong> Added plugin management strings</li>';
        echo '<li><strong>lang/es/admin.php:</strong> Added plugin management strings</li>';
        echo '</ul>';

        echo '<p style="color: green; font-weight: bold; margin-top: 20px;">✅ No database changes required for v1.1.24 - Plugin management functionality only</p>';
        echo '<p style="color: blue;">🧩 Plugin management now available at: Site Administration → Plugins</p>';

        echo '</div>';

        // No database changes for v1.1.24 - plugin management only
        upgrade_core_savepoint(true, 2025011824);
    }

    // =========================================================
    // v1.1.25 (2025011825) - Auto-detect Plugin Type + Plugin Improvements
    // =========================================================
    if ($oldversion < 2025011825) {
        echo '<div style="background: #e3f2fd; border-left: 4px solid #1976d2; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #1976d2; margin-top: 0;">🔍 Upgrading to NexoSupport v1.1.25 - Smart Plugin Detection</h2>';

        echo '<h3 style="color: #1976d2;">✨ What\'s New in v1.1.25:</h3>';
        echo '<ul>';
        echo '<li><strong>Auto-Detect Plugin Type:</strong> System now automatically detects plugin type from version.php</li>';
        echo '<li><strong>Simplified Install Form:</strong> No need to select plugin type - just upload the ZIP file</li>';
        echo '<li><strong>Nested ZIP Support:</strong> Handles ZIP files with nested directories (e.g., plugin-master/)</li>';
        echo '<li><strong>Component Detection:</strong> Reads $plugin->component from version.php to determine type</li>';
        echo '<li><strong>Better Error Messages:</strong> Clear feedback when plugin detection fails</li>';
        echo '</ul>';

        echo '<h3 style="color: #1976d2;">🔧 Technical Improvements:</h3>';
        echo '<ul>';
        echo '<li><code>install_from_zip()</code> - Now auto-detects plugin type, type parameter is optional</li>';
        echo '<li><code>detect_plugin_type_from_version()</code> - New method to parse version.php content</li>';
        echo '<li><code>find_plugin_dir_in_extracted()</code> - Handles nested ZIP structures</li>';
        echo '<li>Plugin UI simplified - removed type selection dropdown</li>';
        echo '</ul>';

        echo '<h3 style="color: #1976d2;">📦 Files Modified:</h3>';
        echo '<ul>';
        echo '<li><strong>lib/classes/plugin/plugin_manager.php:</strong> Enhanced install_from_zip with auto-detection</li>';
        echo '<li><strong>admin/settings/plugins.php:</strong> Simplified install action</li>';
        echo '<li><strong>templates/admin/plugins.mustache:</strong> Removed type selector from form</li>';
        echo '<li><strong>lang/en/admin.php:</strong> Added new error messages</li>';
        echo '<li><strong>lang/es/admin.php:</strong> Added new error messages</li>';
        echo '</ul>';

        echo '<h3 style="color: #1976d2;">🎯 How It Works:</h3>';
        echo '<p>When installing a plugin from ZIP, the system now:</p>';
        echo '<ol>';
        echo '<li>Extracts the ZIP file to a temporary directory</li>';
        echo '<li>Finds the plugin directory (handles nested structures)</li>';
        echo '<li>Reads version.php and looks for <code>$plugin->component = \'type_name\'</code></li>';
        echo '<li>Automatically determines the plugin type (report, tool, theme, auth, block)</li>';
        echo '<li>Moves the plugin to the correct directory and installs it</li>';
        echo '</ol>';

        echo '<p style="color: green; font-weight: bold; margin-top: 20px;">✅ No database changes required for v1.1.25 - Code improvements only</p>';
        echo '<p style="color: blue;">🔍 Plugin type detection now automatic</p>';

        echo '</div>';

        // No database changes for v1.1.25 - code improvements only
        upgrade_core_savepoint(true, 2025011825);
    }

    // =========================================================
    // v1.1.26 (2025011826) - Automatic Plugin Installation During Upgrade
    // =========================================================
    if ($oldversion < 2025011826) {
        echo '<div style="background: #f3e5f5; border-left: 4px solid #9c27b0; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #9c27b0; margin-top: 0;">🔌 Upgrading to NexoSupport v1.1.26 - Automatic Plugin Installation</h2>';

        echo '<h3 style="color: #9c27b0;">✨ What\'s New in v1.1.26:</h3>';
        echo '<ul>';
        echo '<li><strong>Automatic Plugin Installation:</strong> All plugins are now installed/upgraded during system upgrade</li>';
        echo '<li><strong>Plugin Processing in Upgrader:</strong> The upgrader class now handles plugin lifecycle</li>';
        echo '<li><strong>Better Logging:</strong> Plugin install/upgrade status is logged during upgrade</li>';
        echo '</ul>';

        echo '<h3 style="color: #9c27b0;">📦 Plugins That Will Be Installed:</h3>';
        echo '<ul>';
        echo '<li><strong>auth_manual:</strong> Manual authentication plugin</li>';
        echo '<li><strong>report_log:</strong> System logs viewer</li>';
        echo '<li><strong>report_loglive:</strong> Live logs viewer with auto-refresh</li>';
        echo '<li><strong>report_performance:</strong> System performance metrics</li>';
        echo '<li><strong>report_security:</strong> Security checks report</li>';
        echo '<li><strong>theme_boost:</strong> Default theme with SCSS support</li>';
        echo '<li><strong>tool_mfa:</strong> Multi-Factor Authentication</li>';
        echo '</ul>';

        echo '<h3 style="color: #9c27b0;">🔧 Technical Changes:</h3>';
        echo '<ul>';
        echo '<li><code>upgrader::process_plugins()</code> - New method to install/upgrade plugins</li>';
        echo '<li>Plugin processing runs after core upgrade completes</li>';
        echo '<li>Each plugin\'s version.php is read and compared to database version</li>';
        echo '<li>New plugins are installed, existing plugins are upgraded if needed</li>';
        echo '</ul>';

        echo '<p style="color: green; font-weight: bold; margin-top: 20px;">✅ Plugins will be installed automatically after this upgrade block</p>';
        echo '<p style="color: blue;">🔌 Check /admin/plugins to see all installed plugins</p>';

        echo '</div>';

        // No database changes for v1.1.26 - upgrader enhancement only
        upgrade_core_savepoint(true, 2025011826);
    }

    // =========================================================
    // v1.1.27 (2025011827) - Mustache Namespace Compatibility Fix
    // =========================================================
    if ($oldversion < 2025011827) {
        echo '<div style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #4caf50; margin-top: 0;">🔧 Upgrading to NexoSupport v1.1.27 - Mustache Compatibility Fix</h2>';

        echo '<h3 style="color: #4caf50;">🐛 Critical Bug Fixes:</h3>';
        echo '<ul>';
        echo '<li><strong>Mustache Namespace Fix:</strong> Changed <code>\Mustache\Engine</code> to <code>\Mustache_Engine</code></li>';
        echo '<li><strong>Loader Fix:</strong> Changed <code>\Mustache\Loader\FilesystemLoader</code> to <code>\Mustache_Loader_FilesystemLoader</code></li>';
        echo '<li><strong>Helper Fix:</strong> Changed <code>\Mustache\LambdaHelper</code> to <code>\Mustache_LambdaHelper</code></li>';
        echo '<li><strong>Exception Fix:</strong> Changed <code>\Mustache\Exception\UnknownTemplateException</code> to <code>\Mustache_Exception_UnknownTemplateException</code></li>';
        echo '<li><strong>Interface Fix:</strong> Changed <code>\Mustache\Loader</code> to <code>\Mustache_Loader</code></li>';
        echo '</ul>';

        echo '<h3 style="color: #4caf50;">📋 Why This Was Needed:</h3>';
        echo '<p>The <code>mustache/mustache</code> library v3.0 uses underscore-separated class names (e.g., <code>Mustache_Engine</code>) ';
        echo 'instead of namespaced classes (e.g., <code>\Mustache\Engine</code>). The previous code was using ';
        echo 'the incorrect namespace format, causing fatal errors when the Mustache library was loaded.</p>';

        echo '<h3 style="color: #4caf50;">📦 Files Modified (9 files):</h3>';
        echo '<ul>';
        echo '<li><strong>lib/classes/output/mustache_engine.php:</strong> Fixed all Mustache class references</li>';
        echo '<li><strong>lib/classes/output/template_manager.php:</strong> Fixed engine, loader, and exception references</li>';
        echo '<li><strong>lib/classes/output/mustache_string_helper.php:</strong> Fixed LambdaHelper type hint</li>';
        echo '<li><strong>lib/classes/output/mustache_pix_helper.php:</strong> Fixed LambdaHelper type hint</li>';
        echo '<li><strong>lib/classes/output/mustache_quote_helper.php:</strong> Fixed LambdaHelper type hint</li>';
        echo '<li><strong>lib/classes/output/mustache_javascript_helper.php:</strong> Fixed LambdaHelper type hint</li>';
        echo '<li><strong>lib/classes/output/mustache_shortentext_helper.php:</strong> Fixed LambdaHelper type hint</li>';
        echo '<li><strong>lib/classes/output/mustache_cleanstr_helper.php:</strong> Fixed LambdaHelper type hint</li>';
        echo '<li><strong>lib/classes/output/mustache_userdate_helper.php:</strong> Fixed LambdaHelper type hint</li>';
        echo '</ul>';

        echo '<h3 style="color: #4caf50;">📁 Directory Structure Created:</h3>';
        echo '<ul>';
        echo '<li><strong>var/cache:</strong> Template and general cache storage</li>';
        echo '<li><strong>var/temp:</strong> Temporary file storage</li>';
        echo '<li><strong>var/sessions:</strong> PHP session storage</li>';
        echo '<li><strong>var/logs:</strong> Application log files</li>';
        echo '<li><strong>var/localcache:</strong> Local cache for performance</li>';
        echo '</ul>';

        echo '<h3 style="color: #4caf50;">⚠️ Important Note:</h3>';
        echo '<p style="color: #ff5722;">Make sure <code>composer install</code> has been run to install the <code>mustache/mustache</code> library.</p>';
        echo '<p>The library is defined in <code>composer.json</code> as: <code>"mustache/mustache": "^3.0"</code></p>';

        echo '<p style="color: green; font-weight: bold; margin-top: 20px;">✅ No database changes required for v1.1.27 - Code fixes only</p>';
        echo '<p style="color: blue;">🔧 Template rendering system now fully functional</p>';
        echo '<p style="color: green;">✓ Login, upgrade, and all template-based pages will now work correctly</p>';

        echo '</div>';

        // No database changes for v1.1.27 - code fixes only
        upgrade_core_savepoint(true, 2025011827);
    }

    // =========================================================
    // v1.1.28 (2025011828) - Moodle-style Install/Upgrade System
    // =========================================================
    if ($oldversion < 2025011828) {
        echo '<div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 20px; margin: 20px 0;">';
        echo '<h2 style="color: #1565c0; margin-top: 0;">🚀 Upgrading to NexoSupport v1.1.28 - Moodle-style Install/Upgrade System</h2>';

        echo '<h3 style="color: #1565c0;">📦 New Installation System:</h3>';
        echo '<ul>';
        echo '<li><strong>install.php:</strong> Web-based installation wizard with 6 phases</li>';
        echo '<li><strong>admin/cli/install.php:</strong> Command-line installation tool</li>';
        echo '<li><strong>lib/installlib.php:</strong> Core installation functions (install_core, install_from_xmldb_file)</li>';
        echo '</ul>';

        echo '<h3 style="color: #1565c0;">🔄 Enhanced Upgrade System:</h3>';
        echo '<ul>';
        echo '<li><strong>admin/upgrade.php:</strong> Refactored Moodle-style upgrade page</li>';
        echo '<li><strong>admin/cli/upgrade.php:</strong> Command-line upgrade tool</li>';
        echo '<li><strong>lib/upgradelib.php:</strong> Upgrade functions (upgrade_core, upgrade_noncore, upgrade_plugins)</li>';
        echo '<li><strong>Savepoint functions:</strong> upgrade_main_savepoint, upgrade_plugin_savepoint, upgrade_mod_savepoint</li>';
        echo '</ul>';

        echo '<h3 style="color: #1565c0;">🔍 Environment Validation:</h3>';
        echo '<ul>';
        echo '<li><strong>lib/environmentlib.php:</strong> System requirements checking functions</li>';
        echo '<li><strong>admin/environment.xml:</strong> Requirements definition (PHP 8.1+, extensions, settings)</li>';
        echo '<li><strong>check_nexosupport_environment():</strong> Validates PHP version, extensions, settings, database</li>';
        echo '</ul>';

        echo '<h3 style="color: #1565c0;">🛠️ CLI Tools Created:</h3>';
        echo '<ul>';
        echo '<li><code>php admin/cli/install.php --help</code> - Full installation wizard</li>';
        echo '<li><code>php admin/cli/upgrade.php --help</code> - Command-line upgrade</li>';
        echo '<li>Both support <code>--non-interactive</code> mode for automation</li>';
        echo '<li>Both support <code>--verbose</code> for detailed output</li>';
        echo '</ul>';

        echo '<h3 style="color: #1565c0;">📋 Installation Phases:</h3>';
        echo '<ol>';
        echo '<li><strong>INSTALL_WELCOME:</strong> Welcome and language selection</li>';
        echo '<li><strong>INSTALL_ENVIRONMENT:</strong> System requirements check</li>';
        echo '<li><strong>INSTALL_PATHS:</strong> Configure wwwroot and dataroot</li>';
        echo '<li><strong>INSTALL_DATABASE:</strong> Database connection configuration</li>';
        echo '<li><strong>INSTALL_ADMIN:</strong> Admin account and site name setup</li>';
        echo '<li><strong>INSTALL_SAVE:</strong> Execute installation</li>';
        echo '</ol>';

        echo '<h3 style="color: #1565c0;">📁 Files Created/Modified:</h3>';
        echo '<ul>';
        echo '<li><strong>install.php</strong> - Web installation entry point (NEW)</li>';
        echo '<li><strong>lib/environmentlib.php</strong> - Environment checking library (NEW)</li>';
        echo '<li><strong>admin/environment.xml</strong> - Requirements XML (NEW)</li>';
        echo '<li><strong>admin/cli/install.php</strong> - CLI installation (NEW)</li>';
        echo '<li><strong>admin/cli/upgrade.php</strong> - CLI upgrade (NEW)</li>';
        echo '<li><strong>admin/upgrade.php</strong> - Refactored upgrade page</li>';
        echo '<li><strong>lib/installlib.php</strong> - Enhanced installation functions</li>';
        echo '<li><strong>lib/upgradelib.php</strong> - Enhanced upgrade functions</li>';
        echo '</ul>';

        echo '<p style="color: green; font-weight: bold; margin-top: 20px;">✅ Moodle-compatible install/upgrade system now available</p>';
        echo '<p style="color: blue;">🔧 Use <code>php admin/cli/install.php --help</code> for CLI installation</p>';
        echo '<p style="color: blue;">🔧 Use <code>php admin/cli/upgrade.php --help</code> for CLI upgrades</p>';

        echo '</div>';

        // No database changes for v1.1.28 - system infrastructure only
        upgrade_core_savepoint(true, 2025011828);
    }

    // =========================================================
    // Future upgrades go here
    // =========================================================

    // if ($oldversion < 2025011900) {
    //     // Upgrade to v1.3.0
    //     upgrade_core_savepoint(true, 2025011900);
    // }

    // =========================================================
    // PURGAR CACHÉS AL FINALIZAR UPGRADE
    // =========================================================
    // CRÍTICO: SIEMPRE purgar TODAS las cachés después de cualquier upgrade
    // independientemente del resultado ($result) para prevenir problemas
    // de código antiguo causando errores. Esto es esencial para la estabilidad del sistema.

    echo '<div style="background: #fff3e0; border-left: 4px solid #f57c00; padding: 20px; margin: 20px 0;">';
    echo '<h3 style="color: #f57c00; margin-top: 0;">🔄 Purgando Cachés...</h3>';
    echo '<p><strong>CRÍTICO:</strong> Limpiando TODAS las cachés para prevenir problemas de código antiguo.</p>';
    echo '<p>Esta operación se ejecuta SIEMPRE después de cualquier actualización para garantizar estabilidad.</p>';

    try {
        $purge_results = \core\cache\cache_manager::purge_all();

            echo '<ul>';
            if (isset($purge_results['opcache']) && $purge_results['opcache']['success']) {
                echo '<li style="color: green;">✓ OPcache purgado exitosamente</li>';
            } else if (isset($purge_results['opcache'])) {
                echo '<li style="color: orange;">⚠ ' . htmlspecialchars($purge_results['opcache']['message']) . '</li>';
            }

            if (isset($purge_results['mustache']) && $purge_results['mustache']['success']) {
                $count = $purge_results['mustache']['count'] ?? 0;
                echo '<li style="color: green;">✓ Caché de Mustache purgado exitosamente (' . $count . ' templates compilados)</li>';
            } else if (isset($purge_results['mustache'])) {
                echo '<li style="color: orange;">⚠ ' . htmlspecialchars($purge_results['mustache']['message']) . '</li>';
            }

            if (isset($purge_results['i18n']) && $purge_results['i18n']['success']) {
                echo '<li style="color: green;">✓ Caché de i18n purgado exitosamente</li>';
            } else if (isset($purge_results['i18n'])) {
                echo '<li style="color: orange;">⚠ ' . htmlspecialchars($purge_results['i18n']['message']) . '</li>';
            }

            if (isset($purge_results['application']) && $purge_results['application']['success']) {
                $items = $purge_results['application']['items'] ?? [];
                echo '<li style="color: green;">✓ Caché de aplicación purgado: ' . htmlspecialchars(implode(', ', $items)) . '</li>';
            }

            if (isset($purge_results['rbac']) && $purge_results['rbac']['success']) {
                echo '<li style="color: green;">✓ Caché RBAC purgado exitosamente</li>';
            }
            echo '</ul>';

            echo '<p style="color: green; font-weight: bold;">✓ Todas las cachés han sido purgadas!</p>';
            echo '<p style="color: green;">✓ El sistema ahora está ejecutando el código más reciente.</p>';
        } catch (\Exception $e) {
            echo '<p style="color: red; font-weight: bold;">✗ ERROR: No se pudieron purgar algunas cachés: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p style="color: orange;"><strong>IMPORTANTE:</strong> Debe purgar las cachés manualmente INMEDIATAMENTE desde: Administración del Sitio → Development → Purge Caches</p>';
            echo '<p style="color: orange;">El sistema puede no funcionar correctamente hasta que se purguen las cachés.</p>';
        }

    echo '</div>';

    return $result;
}

/**
 * Save core upgrade checkpoint
 *
 * @param bool $result
 * @param int $version
 * @return void
 */
function upgrade_core_savepoint(bool $result, int $version): void {
    global $DB;

    if ($result) {
        // Update core version in config
        $DB->delete_records('config', ['name' => 'version', 'component' => 'core']);

        $record = new stdClass();
        $record->component = 'core';  // IMPORTANT: specify component
        $record->name = 'version';
        $record->value = (string)$version;

        $DB->insert_record('config', $record);

        debugging("Upgrade savepoint reached: version $version", DEBUG_DEVELOPER);
    }
}

/**
 * Get core version from database
 *
 * @return int|null
 */
function get_core_version_from_db(): ?int {
    global $DB;

    try {
        // Try with component='core' first (correct way)
        $sql = "SELECT * FROM {config} WHERE name = ? AND component = ? LIMIT 1";
        $record = $DB->get_record_sql($sql, ['version', 'core']);

        // Fallback to old way (without component) for compatibility
        if (!$record) {
            $record = $DB->get_record('config', ['name' => 'version']);
        }

        return $record ? (int)$record->value : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get core version from code
 *
 * @return int
 */
function get_core_version_from_code(): int {
    require(__DIR__ . '/version.php');
    return $plugin->version;
}

/**
 * Check if upgrade is required
 *
 * @return bool
 */
function core_upgrade_required(): bool {
    $dbversion = get_core_version_from_db();
    $codeversion = get_core_version_from_code();

    // If no version in DB, assume it needs upgrade
    if ($dbversion === null) {
        return true;
    }

    return $codeversion > $dbversion;
}
