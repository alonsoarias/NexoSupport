<?php
/**
 * Fix siteadmins configuration
 *
 * This script ensures that siteadmins configuration is set properly
 * Run this from your browser: http://localhost/admin/fix_siteadmins.php
 */

require_once(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/setup.php');

require_login();

global $DB, $USER;

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fix Siteadmins Configuration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        h1 { color: #333; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-left: 4px solid green; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-left: 4px solid red; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #e3f2fd; border-left: 4px solid blue; margin: 10px 0; }
        .warning { color: orange; padding: 10px; background: #fff3e0; border-left: 4px solid orange; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
    </style>
</head>
<body>';

echo '<h1>🔧 Fix Siteadmins Configuration</h1>';

try {
    // Check current siteadmins config
    echo '<h2>1. Current Configuration</h2>';
    $sql = "SELECT * FROM {config} WHERE name = 'siteadmins'";
    $existing = $DB->get_record_sql($sql);

    if ($existing) {
        echo '<div class="info">✓ Siteadmins config exists:<br>';
        echo '<pre>';
        echo 'Component: ' . ($existing->component ?? 'N/A') . "\n";
        echo 'Name: ' . $existing->name . "\n";
        echo 'Value: ' . $existing->value . "\n";
        echo '</pre></div>';

        $current_siteadmins = array_map('intval', explode(',', $existing->value));
    } else {
        echo '<div class="warning">⚠ Siteadmins config NOT FOUND</div>';
        $current_siteadmins = [];
    }

    // Check role assignments
    echo '<h2>2. Administrator Role Assignments</h2>';
    $syscontext = \core\rbac\context::system();

    $sql = "SELECT DISTINCT ra.userid, u.username, u.firstname, u.lastname
            FROM {role_assignments} ra
            JOIN {roles} r ON r.id = ra.roleid
            JOIN {users} u ON u.id = ra.userid
            WHERE ra.contextid = :contextid
            AND r.shortname = 'administrator'
            AND u.deleted = 0
            ORDER BY ra.userid ASC";

    $adminusers = $DB->get_records_sql($sql, ['contextid' => $syscontext->id]);

    if (!empty($adminusers)) {
        echo '<div class="info">✓ Found ' . count($adminusers) . ' administrator(s):</div>';
        echo '<table>';
        echo '<tr><th>User ID</th><th>Username</th><th>Name</th><th>In Siteadmins?</th></tr>';

        foreach ($adminusers as $admin) {
            $in_siteadmins = in_array($admin->userid, $current_siteadmins) ? '✓ Yes' : '✗ No';
            echo "<tr><td>{$admin->userid}</td><td>{$admin->username}</td><td>{$admin->firstname} {$admin->lastname}</td><td>{$in_siteadmins}</td></tr>";
        }
        echo '</table>';

        $admin_userids = array_keys($adminusers);
    } else {
        echo '<div class="warning">⚠ No administrator role assignments found</div>';
        $admin_userids = [];
    }

    // Check first user as fallback
    if (empty($admin_userids)) {
        echo '<h2>3. Fallback: First User</h2>';
        $firstuser = $DB->get_record_sql('SELECT * FROM {users} WHERE deleted = 0 ORDER BY id ASC LIMIT 1');

        if ($firstuser) {
            echo '<div class="warning">⚠ Using first user as fallback siteadmin:</div>';
            echo '<table>';
            echo '<tr><th>User ID</th><th>Username</th><th>Name</th></tr>';
            echo "<tr><td>{$firstuser->id}</td><td>{$firstuser->username}</td><td>{$firstuser->firstname} {$firstuser->lastname}</td></tr>";
            echo '</table>';

            $admin_userids = [$firstuser->id];
        } else {
            echo '<div class="error">✗ ERROR: No users found in database!</div>';
            echo '</body></html>';
            exit;
        }
    }

    // Fix siteadmins configuration
    echo '<h2>4. Fix Siteadmins Configuration</h2>';

    $new_siteadmins_value = implode(',', $admin_userids);

    if ($existing) {
        // Update existing
        if ($existing->value !== $new_siteadmins_value) {
            $existing->value = $new_siteadmins_value;
            $DB->update_record('config', $existing);
            echo '<div class="success">✓ UPDATED siteadmins configuration from "' . htmlspecialchars($current_siteadmins ? implode(',', $current_siteadmins) : '(empty)') . '" to "' . htmlspecialchars($new_siteadmins_value) . '"</div>';
        } else {
            echo '<div class="info">✓ Siteadmins configuration is already correct</div>';
        }
    } else {
        // Insert new
        $record = new \stdClass();
        $record->component = 'core';
        $record->name = 'siteadmins';
        $record->value = $new_siteadmins_value;
        $DB->insert_record('config', $record);
        echo '<div class="success">✓ CREATED siteadmins configuration: "' . htmlspecialchars($new_siteadmins_value) . '"</div>';
    }

    // Verify
    echo '<h2>5. Verification</h2>';
    $sql = "SELECT * FROM {config} WHERE name = 'siteadmins'";
    $verified = $DB->get_record_sql($sql);

    if ($verified && !empty($verified->value)) {
        echo '<div class="success">✓ Siteadmins config is now set correctly:<br>';
        echo '<pre>';
        echo 'Component: ' . ($verified->component ?? 'N/A') . "\n";
        echo 'Name: ' . $verified->name . "\n";
        echo 'Value: ' . $verified->value . "\n";
        echo '</pre></div>';

        // Test is_siteadmin()
        echo '<h2>6. Test is_siteadmin() Function</h2>';
        echo '<table>';
        echo '<tr><th>User ID</th><th>is_siteadmin()</th></tr>';

        foreach ($admin_userids as $uid) {
            $is_admin = is_siteadmin($uid);
            $status = $is_admin ? '<span style="color: green;">✓ TRUE</span>' : '<span style="color: red;">✗ FALSE</span>';
            echo "<tr><td>{$uid}</td><td>{$status}</td></tr>";
        }
        echo '</table>';

        echo '<div class="success">✓✓✓ FIX COMPLETE! You can now <a href="/admin">go back to admin dashboard</a></div>';

    } else {
        echo '<div class="error">✗ ERROR: Siteadmins config still not found after fix!</div>';
    }

} catch (Exception $e) {
    echo '<div class="error">✗ ERROR: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

echo '</body></html>';
