<?php
/**
 * NexoSupport - Log Viewer Tool
 *
 * Frankenstyle admin tool for viewing system logs
 *
 * @package    tool_logviewer
 * @copyright  2024 ISER
 * @license    Proprietary
 */

// Require system bootstrap
require_once __DIR__ . '/../../../bootstrap.php';

// Define as internal access
if (!defined('NEXOSUPPORT_INTERNAL')) {
    define('NEXOSUPPORT_INTERNAL', true);
}

// Require login and admin capability
require_login();
require_capability('tool/logviewer:view');

// Load tool library
require_once __DIR__ . '/lib.php';

use ISER\Core\Database\Database;
use tool_logviewer\log_reader;

$database = Database::getInstance();
$logReader = new log_reader($database);

// Get query parameters
$logType = $_GET['type'] ?? 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Get filter parameters
$filters = [];
if (!empty($_GET['level'])) {
    $filters['level'] = $_GET['level'];
}
if (!empty($_GET['user_id'])) {
    $filters['user_id'] = (int)$_GET['user_id'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get logs from database
try {
    $logs = $logReader->get_logs($logType, $perPage, $offset, $filters);
    $totalLogs = $logReader->count_logs($logType, $filters);
} catch (Exception $e) {
    $logs = [];
    $totalLogs = 0;
    $error = 'Error loading logs: ' . $e->getMessage();
}

$totalPages = (int)ceil($totalLogs / $perPage);

// Get log statistics
$stats = $logReader->get_statistics();

// Render page
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo tool_logviewer_get_title(); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #667eea;
        }
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .filter-group select,
        .filter-group input {
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        .filter-actions {
            margin-top: 15px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-left: 10px;
        }
        .log-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .log-table th,
        .log-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .log-table th {
            background: #f8f9fa;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        .log-table tbody tr:hover {
            background: #f8f9fa;
        }
        .log-level {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .level-error { background: #f8d7da; color: #721c24; }
        .level-warning { background: #fff3cd; color: #856404; }
        .level-info { background: #d1ecf1; color: #0c5460; }
        .level-debug { background: #e2e3e5; color: #383d41; }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .pagination a:hover {
            background: #f8f9fa;
        }
        .pagination .active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .log-message {
            max-width: 500px;
            word-wrap: break-word;
        }
        .timestamp {
            white-space: nowrap;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/admin" class="back-link">← Back to Admin</a>

        <h1><?php echo tool_logviewer_get_title(); ?></h1>
        <p class="subtitle">View and filter system logs</p>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Logs</h3>
                <div class="value"><?php echo number_format($stats['total'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Errors (24h)</h3>
                <div class="value"><?php echo number_format($stats['errors_24h'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Warnings (24h)</h3>
                <div class="value"><?php echo number_format($stats['warnings_24h'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Today's Activity</h3>
                <div class="value"><?php echo number_format($stats['today'] ?? 0); ?></div>
            </div>
        </div>

        <form method="GET" class="filters">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Log Level:</label>
                    <select name="level">
                        <option value="">All Levels</option>
                        <option value="error" <?php echo ($filters['level'] ?? '') === 'error' ? 'selected' : ''; ?>>Error</option>
                        <option value="warning" <?php echo ($filters['level'] ?? '') === 'warning' ? 'selected' : ''; ?>>Warning</option>
                        <option value="info" <?php echo ($filters['level'] ?? '') === 'info' ? 'selected' : ''; ?>>Info</option>
                        <option value="debug" <?php echo ($filters['level'] ?? '') === 'debug' ? 'selected' : ''; ?>>Debug</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Search:</label>
                    <input type="text" name="search" placeholder="Search in messages..." value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="?" class="btn btn-secondary">Clear Filters</a>
            </div>
        </form>

        <?php if (isset($error)): ?>
            <div style="padding: 15px; background: #f8d7da; border-radius: 4px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <table class="log-table">
            <thead>
                <tr>
                    <th>Level</th>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Message</th>
                    <th>Context</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                            No logs found
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <span class="log-level level-<?php echo htmlspecialchars($log['level'] ?? 'info'); ?>">
                                    <?php echo strtoupper($log['level'] ?? 'INFO'); ?>
                                </span>
                            </td>
                            <td class="timestamp">
                                <?php echo date('Y-m-d H:i:s', $log['created_at'] ?? time()); ?>
                            </td>
                            <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                            <td class="log-message"><?php echo htmlspecialchars($log['message'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($log['context'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&type=<?php echo urlencode($logType); ?>&level=<?php echo urlencode($filters['level'] ?? ''); ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>">« Previous</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&type=<?php echo urlencode($logType); ?>&level=<?php echo urlencode($filters['level'] ?? ''); ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>"
                       class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&type=<?php echo urlencode($logType); ?>&level=<?php echo urlencode($filters['level'] ?? ''); ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>">Next »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
            <p style="margin-bottom: 10px;"><strong>Log Information:</strong></p>
            <ul style="margin-left: 20px; color: #666;">
                <li>Showing page <?php echo $page; ?> of <?php echo $totalPages; ?></li>
                <li>Total logs: <?php echo number_format($totalLogs); ?></li>
                <li>Logs per page: <?php echo $perPage; ?></li>
            </ul>
        </div>
    </div>
</body>
</html>
