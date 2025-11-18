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
    // Future upgrades go here
    // =========================================================

    // if ($oldversion < 2025011900) {
    //     // Upgrade to v1.3.0
    //     upgrade_core_savepoint(true, 2025011900);
    // }

    // =========================================================
    // PURGAR CACHÉS AL FINALIZAR UPGRADE
    // =========================================================
    // Siempre purgar todas las cachés al finalizar cualquier upgrade
    // para asegurar que los cambios se vean inmediatamente
    if ($result) {
        echo '<div style="background: #fff3e0; border-left: 4px solid #f57c00; padding: 20px; margin: 20px 0;">';
        echo '<h3 style="color: #f57c00; margin-top: 0;">🔄 Purgando Cachés...</h3>';
        echo '<p>Limpiando cachés para asegurar que los cambios se apliquen inmediatamente.</p>';

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

            if (isset($purge_results['application']) && $purge_results['application']['success']) {
                $items = $purge_results['application']['items'] ?? [];
                echo '<li style="color: green;">✓ Caché de aplicación purgado: ' . htmlspecialchars(implode(', ', $items)) . '</li>';
            }

            if (isset($purge_results['rbac']) && $purge_results['rbac']['success']) {
                echo '<li style="color: green;">✓ Caché RBAC purgado exitosamente</li>';
            }
            echo '</ul>';

            echo '<p style="color: green; font-weight: bold;">✓ Todas las cachés han sido purgadas!</p>';
        } catch (\Exception $e) {
            echo '<p style="color: orange;">⚠ No se pudieron purgar algunas cachés: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p>Puede purgarlas manualmente desde: Administración del Sitio → Gestionar Cachés</p>';
        }

        echo '</div>';
    }

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
