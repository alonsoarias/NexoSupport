<?php
/**
 * User Management Interface
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_login();
require_capability('nexosupport/admin:manageusers');

global $USER;

// Obtener usuarios
$search = optional_param('search', '', 'text');
$page = optional_param('page', 0, 'int');
$perpage = 25;

$users = $search
    ? \core\user\manager::search_users($search, $page * $perpage, $perpage)
    : \core\user\manager::get_all_users(false, $page * $perpage, $perpage);

$totalusers = \core\user\manager::count_users();

?>
<!DOCTYPE html>
<html lang="<?php echo \core\string_manager::get_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_string('usermanagement'); ?> - <?php echo get_string('sitename'); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }

        .search-box {
            display: flex;
            gap: 10px;
        }

        .search-box input {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            min-width: 300px;
        }

        .btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-sm {
            padding: 4px 12px;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        thead {
            background: #f8f9fa;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/"><?php echo get_string('home'); ?></a>
        <a href="/admin"><?php echo get_string('administration'); ?></a>
        <a href="/admin/users"><?php echo get_string('users'); ?></a>
        <a href="/logout"><?php echo get_string('logout'); ?></a>
    </div>

    <h1><?php echo get_string('usermanagement'); ?></h1>
    <p><?php echo get_string('totalusers'); ?>: <?php echo $totalusers; ?></p>

    <div class="actions-bar">
        <form method="GET" class="search-box">
            <input type="text" name="search" placeholder="<?php echo get_string('searchbyname'); ?>" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn"><?php echo get_string('search'); ?></button>
            <?php if ($search): ?>
                <a href="/admin/users" class="btn btn-secondary"><?php echo get_string('cancel'); ?></a>
            <?php endif; ?>
        </form>

        <a href="/admin/user/edit?id=0" class="btn">+ <?php echo get_string('newuser'); ?></a>
    </div>

    <?php if (empty($users)): ?>
        <div class="empty-state">
            <h3><?php echo get_string('nousersfound'); ?></h3>
            <p><?php echo get_string('pleaseselectcriteria'); ?></p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo get_string('username'); ?></th>
                    <th><?php echo get_string('name'); ?></th>
                    <th><?php echo get_string('email'); ?></th>
                    <th><?php echo get_string('status'); ?></th>
                    <th><?php echo get_string('lastlogin'); ?></th>
                    <th><?php echo get_string('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user->id; ?></td>
                        <td><?php echo htmlspecialchars($user->username); ?></td>
                        <td><?php echo htmlspecialchars($user->firstname . ' ' . $user->lastname); ?></td>
                        <td><?php echo htmlspecialchars($user->email); ?></td>
                        <td>
                            <?php if ($user->suspended): ?>
                                <span class="badge badge-warning"><?php echo get_string('suspended'); ?></span>
                            <?php else: ?>
                                <span class="badge badge-success"><?php echo get_string('active'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            if ($user->lastlogin) {
                                echo date('d/m/Y H:i', $user->lastlogin);
                            } else {
                                echo get_string('never');
                            }
                            ?>
                        </td>
                        <td>
                            <a href="/admin/user/edit?id=<?php echo $user->id; ?>" class="btn btn-sm"><?php echo get_string('edit'); ?></a>
                            <a href="/admin/roles/assign?userid=<?php echo $user->id; ?>" class="btn btn-sm btn-secondary"><?php echo get_string('roles'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
