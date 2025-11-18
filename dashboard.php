<?php
/**
 * Dashboard / Home Page
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();

global $USER, $DB;

// Get stats
$totalusers = $DB->count_records('users', ['deleted' => 0]);
$activeusers = $DB->count_records('users', ['deleted' => 0, 'suspended' => 0]);
$totalroles = $DB->count_records('roles');
$activesessions = \core\session\manager::count_active_sessions();

// Get recent logins
$recentlogins = $DB->get_records_sql('SELECT * FROM {users} WHERE deleted = 0 AND lastlogin > 0 ORDER BY lastlogin DESC LIMIT 5');

?>
<!DOCTYPE html>
<html lang="<?php echo \core\string_manager::get_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_string('dashboard'); ?> - <?php echo get_string('sitename'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .nav {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .nav a {
            margin-right: 20px;
            color: #667eea;
            text-decoration: none;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .welcome-msg {
            color: #666;
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .action-card h3 {
            color: #667eea;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .action-card p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .recent-activity {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .recent-activity h2 {
            color: #333;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-user {
            font-weight: 600;
            color: #333;
        }

        .activity-time {
            color: #999;
            font-size: 13px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/"><?php echo get_string('home'); ?></a>
        <?php if (has_capability('nexosupport/admin:viewdashboard')): ?>
            <a href="/admin"><?php echo get_string('administration'); ?></a>
        <?php endif; ?>
        <a href="/user/profile"><?php echo get_string('profile'); ?></a>
        <a href="/logout"><?php echo get_string('logout'); ?></a>
    </div>

    <h1><?php echo get_string('dashboard'); ?></h1>
    <p class="welcome-msg"><?php echo get_string('welcomeback', 'core', $USER->firstname . ' ' . $USER->lastname); ?></p>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $totalusers; ?></div>
            <div class="stat-label"><?php echo get_string('totalusers'); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-value"><?php echo $activeusers; ?></div>
            <div class="stat-label"><?php echo get_string('activeusers'); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-value"><?php echo $totalroles; ?></div>
            <div class="stat-label"><?php echo get_string('totalroles'); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-value"><?php echo $activesessions; ?></div>
            <div class="stat-label"><?php echo get_string('activesessions'); ?></div>
        </div>
    </div>

    <?php if (has_capability('nexosupport/admin:viewdashboard')): ?>
        <h2 style="margin-bottom: 20px;"><?php echo get_string('quickactions'); ?></h2>
        <div class="actions-grid">
            <?php if (has_capability('nexosupport/admin:manageusers')): ?>
                <a href="/admin/users" class="action-card">
                    <h3><?php echo get_string('manageusers'); ?></h3>
                    <p><?php echo get_string('manageusers_desc'); ?></p>
                </a>
            <?php endif; ?>

            <?php if (has_capability('nexosupport/admin:manageroles')): ?>
                <a href="/admin/roles" class="action-card">
                    <h3><?php echo get_string('manageroles'); ?></h3>
                    <p><?php echo get_string('manageroles_desc'); ?></p>
                </a>
            <?php endif; ?>

            <?php if (has_capability('nexosupport/admin:manageconfig')): ?>
                <a href="/admin/settings" class="action-card">
                    <h3><?php echo get_string('settings'); ?></h3>
                    <p><?php echo get_string('managesettings_desc'); ?></p>
                </a>
            <?php endif; ?>

            <a href="/user/profile" class="action-card">
                <h3><?php echo get_string('profile'); ?></h3>
                <p><?php echo get_string('myprofile_desc'); ?></p>
            </a>
        </div>
    <?php endif; ?>

    <div class="recent-activity">
        <h2><?php echo get_string('recentactivity'); ?></h2>

        <?php if (empty($recentlogins)): ?>
            <div class="empty-state">
                <p><?php echo get_string('norecentactivity'); ?></p>
            </div>
        <?php else: ?>
            <ul class="activity-list">
                <?php foreach ($recentlogins as $login): ?>
                    <li class="activity-item">
                        <div>
                            <span class="activity-user"><?php echo htmlspecialchars($login->firstname . ' ' . $login->lastname); ?></span>
                            <span style="color: #999;"> (<?php echo htmlspecialchars($login->username); ?>)</span>
                        </div>
                        <span class="activity-time">
                            <?php echo get_string('lastlogin'); ?>: <?php echo date('d/m/Y H:i', $login->lastlogin); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
