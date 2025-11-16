<?php
/**
 * NexoSupport - Data Privacy Administration
 *
 * @package    tool_dataprivacy
 * @copyright  2024 ISER
 * @license    Proprietary
 */

require_once __DIR__ . '/../../../lib/setup.php';
require_once __DIR__ . '/../../../lib/accesslib.php';

defined('NEXOSUPPORT_INTERNAL') || die();

require_capability('tool/dataprivacy:manage');

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'ISER\\Tools\\DataPrivacy\\';
    $base_dir = __DIR__ . '/classes/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use ISER\Tools\DataPrivacy\PrivacyManager;
use ISER\Tools\DataPrivacy\DataExporter;
use ISER\Tools\DataPrivacy\DataEraser;
use ISER\Core\Database\Database;

$db = Database::getInstance()->getConnection();
$privacy = new PrivacyManager($db);
$exporter = new DataExporter($db);
$eraser = new DataEraser($db);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'approve_request':
            $result = $privacy->approve_request((int)$_POST['request_id'], $_SESSION['user_id'] ?? 1);
            $message = $result['success'] ? $result['message'] : null;
            $error = !$result['success'] ? $result['error'] : null;
            break;

        case 'reject_request':
            $result = $privacy->reject_request((int)$_POST['request_id'], $_SESSION['user_id'] ?? 1, $_POST['reason'] ?? '');
            $message = $result['success'] ? $result['message'] : null;
            $error = !$result['success'] ? $result['error'] : null;
            break;

        case 'process_export':
            $request = $privacy->get_request((int)$_POST['request_id']);
            $result = $exporter->export_user_data($request['user_id'], $request['export_format'] ?? 'json');
            if ($result['success']) {
                $privacy->complete_request($request['id'], $result['filename']);
                $message = "Export completed: " . $result['filename'];
            }
            break;

        case 'process_delete':
            $request = $privacy->get_request((int)$_POST['request_id']);
            $result = $eraser->delete_user_data($request['user_id'], $_SESSION['user_id'] ?? 1, 'anonymize');
            if ($result['success']) {
                $privacy->complete_request($request['id']);
                $message = $result['message'];
            }
            break;
    }
}

$pending_exports = $privacy->get_pending_requests('export');
$pending_deletes = $privacy->get_pending_requests('delete');
$policies = $privacy->get_retention_policies();
$report = $privacy->get_compliance_report();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Privacy & GDPR Compliance - NexoSupport</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { margin-bottom: 30px; color: #333; }
        .message { padding: 12px 20px; margin-bottom: 20px; border-radius: 4px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .section { background: white; padding: 25px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .section h2 { margin-bottom: 20px; color: #333; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        .badge { padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: 600; }
        .badge.pending { background: #fff3cd; color: #856404; }
        .badge.approved { background: #d4edda; color: #155724; }
        .badge.completed { background: #d1ecf1; color: #0c5460; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .btn-primary { background: #1e3a8a; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        .btn:hover { opacity: 0.9; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-card .value { font-size: 28px; font-weight: bold; color: #1e3a8a; }
        .stat-card .label { font-size: 14px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚖️ Data Privacy & GDPR Compliance</h1>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="value"><?= count($pending_exports) ?></div>
                <div class="label">Pending Export Requests</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= count($pending_deletes) ?></div>
                <div class="label">Pending Delete Requests</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= count($policies) ?></div>
                <div class="label">Retention Policies</div>
            </div>
        </div>

        <div class="section">
            <h2>📤 Export Requests</h2>
            <table>
                <tr><th>User ID</th><th>Format</th><th>Requested</th><th>Status</th><th>Actions</th></tr>
                <?php foreach ($pending_exports as $req): ?>
                    <tr>
                        <td><?= $req['user_id'] ?></td>
                        <td><?= strtoupper($req['export_format'] ?? 'JSON') ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($req['requested_at'])) ?></td>
                        <td><span class="badge pending"><?= ucfirst($req['status']) ?></span></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="approve_request">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <button type="submit" class="btn btn-primary">Approve</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="process_export">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <button type="submit" class="btn btn-primary">Process</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pending_exports)): ?>
                    <tr><td colspan="5" style="text-align:center;color:#999;">No pending export requests</td></tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="section">
            <h2>🗑️ Delete Requests</h2>
            <table>
                <tr><th>User ID</th><th>Requested</th><th>Notes</th><th>Status</th><th>Actions</th></tr>
                <?php foreach ($pending_deletes as $req): ?>
                    <tr>
                        <td><?= $req['user_id'] ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($req['requested_at'])) ?></td>
                        <td><?= htmlspecialchars($req['notes'] ?? '-') ?></td>
                        <td><span class="badge pending"><?= ucfirst($req['status']) ?></span></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="process_delete">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Delete user data?')">Process</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pending_deletes)): ?>
                    <tr><td colspan="5" style="text-align:center;color:#999;">No pending delete requests</td></tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="section">
            <h2>📅 Retention Policies</h2>
            <table>
                <tr><th>Category</th><th>Retention Period</th><th>Description</th></tr>
                <?php foreach ($policies as $policy): ?>
                    <tr>
                        <td><?= htmlspecialchars($policy['category']) ?></td>
                        <td><?= $policy['retention_days'] ?> days</td>
                        <td><?= htmlspecialchars($policy['description'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
