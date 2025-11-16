<?php
/**
 * NexoSupport - MFA Administration Interface
 *
 * @package    tool_mfa
 * @copyright  2024 ISER
 * @license    Proprietary
 */

require_once __DIR__ . '/../../../lib/setup.php';
require_once __DIR__ . '/../../../lib/accesslib.php';

defined('NEXOSUPPORT_INTERNAL') || die();

// Require capability
require_capability('tool/mfa:manage');

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'ISER\\Tools\\MFA\\';
    $base_dir = __DIR__ . '/classes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use ISER\Tools\MFA\MFAManager;
use ISER\Core\Database\Database;

// Get database connection
$db_instance = Database::getInstance();
$pdo = $db_instance->getConnection();

// Initialize MFA Manager
$mfa = new MFAManager($pdo);

// Handle POST actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_ip_range':
            $result = $mfa->get_iprange_factor()->add_range(
                $_POST['range_cidr'],
                $_POST['type'],
                $_POST['description'] ?? '',
                get_current_user_id()
            );
            if ($result['success']) {
                $message = 'IP range added successfully';
            } else {
                $error = $result['error'];
            }
            break;

        case 'remove_ip_range':
            $result = $mfa->get_iprange_factor()->remove_range(
                (int)$_POST['range_id'],
                get_current_user_id()
            );
            if ($result['success']) {
                $message = 'IP range removed successfully';
            } else {
                $error = $result['error'];
            }
            break;

        case 'toggle_ip_range':
            $result = $mfa->get_iprange_factor()->toggle_range(
                (int)$_POST['range_id'],
                (bool)$_POST['enabled']
            );
            if ($result['success']) {
                $message = 'IP range updated successfully';
            }
            break;

        case 'cleanup':
            $results = $mfa->cleanup();
            $message = sprintf(
                'Cleanup complete: %d expired codes, %d old logs, %d old IP logs deleted',
                $results['expired_codes'],
                $results['old_logs'],
                $results['old_ip_logs']
            );
            break;
    }
}

// Get data for display
$stats = $mfa->get_stats();
$ip_ranges = $mfa->get_iprange_factor()->get_ranges();
$recent_blocks = $mfa->get_iprange_factor()->get_recent_blocks(20);
$audit_log = $mfa->get_audit_log(50);

// Helper function to get current user ID (simplified)
function get_current_user_id() {
    return $_SESSION['user_id'] ?? 1;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Factor Authentication - NexoSupport</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .message { padding: 12px 20px; margin-bottom: 20px; border-radius: 4px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-card h3 { font-size: 14px; color: #666; margin-bottom: 10px; text-transform: uppercase; }
        .stat-card .value { font-size: 32px; font-weight: bold; color: #1e3a8a; }
        .stat-card .label { font-size: 12px; color: #999; margin-top: 5px; }
        .section { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .section h2 { color: #333; margin-bottom: 20px; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; color: #555; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: 600; }
        .badge.success { background: #d4edda; color: #155724; }
        .badge.danger { background: #f8d7da; color: #721c24; }
        .badge.info { background: #d1ecf1; color: #0c5460; }
        .badge.warning { background: #fff3cd; color: #856404; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-primary { background: #1e3a8a; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { opacity: 0.9; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .form-inline { display: flex; gap: 10px; align-items: flex-end; }
        .form-inline .form-group { flex: 1; margin-bottom: 0; }
        .empty-state { text-align: center; padding: 40px; color: #999; }
        .factor-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; }
        .factor-item .info { flex: 1; }
        .factor-item .name { font-weight: 600; color: #333; }
        .factor-item .description { font-size: 14px; color: #666; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔒 Multi-Factor Authentication</h1>
        <p class="subtitle">Configure and manage multi-factor authentication settings</p>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <h3>Users with MFA</h3>
                <div class="value"><?= $stats['overall']['users_with_mfa'] ?? 0 ?></div>
                <div class="label">Active users</div>
            </div>

            <div class="stat-card">
                <h3>Email Factor</h3>
                <div class="value"><?= $stats['email']['total_codes'] ?? 0 ?></div>
                <div class="label">Codes sent (30 days)</div>
            </div>

            <div class="stat-card">
                <h3>IP Restrictions</h3>
                <div class="value"><?= count($ip_ranges) ?></div>
                <div class="label">Active ranges</div>
            </div>

            <div class="stat-card">
                <h3>Blocked Access</h3>
                <div class="value"><?= $stats['iprange']['blocked_access'] ?? 0 ?></div>
                <div class="label">Last 30 days</div>
            </div>
        </div>

        <!-- Available Factors -->
        <div class="section">
            <h2>Available MFA Factors</h2>

            <div class="factor-item">
                <div class="info">
                    <div class="name">📧 Email Verification</div>
                    <div class="description">Send 6-digit codes to user's email address</div>
                </div>
                <span class="badge success">Available</span>
            </div>

            <div class="factor-item">
                <div class="info">
                    <div class="name">🌐 IP Range Restriction</div>
                    <div class="description">Restrict access based on IP address ranges (CIDR)</div>
                </div>
                <span class="badge success">Available</span>
            </div>
        </div>

        <!-- IP Range Management -->
        <div class="section">
            <h2>IP Range Configuration</h2>

            <form method="POST" class="form-inline">
                <input type="hidden" name="action" value="add_ip_range">
                <div class="form-group">
                    <label>IP Range (CIDR)</label>
                    <input type="text" name="range_cidr" placeholder="192.168.1.0/24" required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" required>
                        <option value="whitelist">Whitelist</option>
                        <option value="blacklist">Blacklist</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" placeholder="Office network">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Add Range</button>
                </div>
            </form>

            <table style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Range</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ip_ranges)): ?>
                        <tr>
                            <td colspan="5" class="empty-state">No IP ranges configured</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ip_ranges as $range): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($range['range_cidr']) ?></code></td>
                                <td>
                                    <span class="badge <?= $range['type'] === 'whitelist' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($range['type']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($range['description'] ?? '-') ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($range['created_at'])) ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove_ip_range">
                                        <input type="hidden" name="range_id" value="<?= $range['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Remove this IP range?')">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Blocked Access -->
        <?php if (!empty($recent_blocks)): ?>
            <div class="section">
                <h2>Recent Blocked Access Attempts</h2>
                <table>
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Reason</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recent_blocks, 0, 10) as $block): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($block['ip_address']) ?></code></td>
                                <td><?= htmlspecialchars($block['reason']) ?></td>
                                <td><?= date('Y-m-d H:i:s', strtotime($block['timestamp'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Audit Log -->
        <div class="section">
            <h2>Recent Activity</h2>
            <table>
                <thead>
                    <tr>
                        <th>Factor</th>
                        <th>Action</th>
                        <th>Status</th>
                        <th>IP Address</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($audit_log)): ?>
                        <tr>
                            <td colspan="5" class="empty-state">No recent activity</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_slice($audit_log, 0, 15) as $log): ?>
                            <tr>
                                <td><span class="badge info"><?= ucfirst($log['factor']) ?></span></td>
                                <td><?= htmlspecialchars($log['action']) ?></td>
                                <td>
                                    <span class="badge <?= $log['success'] ? 'success' : 'danger' ?>">
                                        <?= $log['success'] ? 'Success' : 'Failed' ?>
                                    </span>
                                </td>
                                <td><code><?= htmlspecialchars($log['ip_address'] ?? '-') ?></code></td>
                                <td><?= date('Y-m-d H:i:s', strtotime($log['timestamp'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Maintenance -->
        <div class="section">
            <h2>Maintenance</h2>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="cleanup">
                <button type="submit" class="btn btn-secondary" onclick="return confirm('Clean up expired codes and old logs?')">
                    🧹 Cleanup Old Data
                </button>
            </form>
            <p style="margin-top: 10px; color: #666; font-size: 14px;">
                Remove expired verification codes and old audit logs (keeps last 90 days)
            </p>
        </div>
    </div>
</body>
</html>
