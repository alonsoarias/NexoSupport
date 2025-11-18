<?php
/**
 * User Management Interface
 *
 * Similar to Moodle's admin/user.php
 * Allows listing, deleting, suspending, unsuspending, unlocking and confirming users.
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageusers');

global $USER, $DB;

// Get action parameters
$delete       = optional_param('delete', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   // md5 confirmation hash
$confirmuser  = optional_param('confirmuser', 0, PARAM_INT);
$suspend      = optional_param('suspend', 0, PARAM_INT);
$unsuspend    = optional_param('unsuspend', 0, PARAM_INT);
$unlock       = optional_param('unlock', 0, PARAM_INT);
$resendemail  = optional_param('resendemail', 0, PARAM_INT);

$returnurl = '/admin/users';

// Process actions
$error = null;
$success = null;

// Confirm user
if ($confirmuser && confirm_sesskey()) {
    $user = $DB->get_record('users', ['id' => $confirmuser, 'deleted' => 0]);

    if (!$user) {
        $error = get_string('usernotfound', 'core');
    } else {
        if (confirm_user($user)) {
            $success = get_string('userconfirmed', 'core');
        } else {
            $error = get_string('usernotconfirmed', 'core');
        }
    }
}

// Resend confirmation email
else if ($resendemail && confirm_sesskey()) {
    $user = $DB->get_record('users', ['id' => $resendemail, 'deleted' => 0]);

    if (!$user) {
        $error = get_string('usernotfound', 'core');
    } else if ($user->confirmed) {
        $error = get_string('alreadyconfirmed', 'core');
    } else {
        if (send_confirmation_email($user)) {
            $success = get_string('emailconfirmsent', 'core');
        } else {
            $error = get_string('emailconfirmsentfailure', 'core');
        }
    }
}

// Delete user
else if ($delete && confirm_sesskey()) {
    $user = $DB->get_record('users', ['id' => $delete, 'deleted' => 0]);

    if (!$user) {
        $error = get_string('usernotfound', 'core');
    } else if ($user->deleted) {
        $error = get_string('userdeleted', 'core');
    } else if (is_siteadmin($user->id)) {
        $error = get_string('cannotdeleteadmin', 'core');
    } else {
        // Show confirmation if not confirmed yet
        if ($confirm != md5($delete)) {
            $fullname = $user->firstname . ' ' . $user->lastname;

            // Show confirmation page
            $context = [
                'user' => $USER,
                'showadmin' => true,
                'action' => 'deleteconfirm',
                'targetuser' => [
                    'id' => $user->id,
                    'fullname' => htmlspecialchars($fullname),
                    'username' => htmlspecialchars($user->username),
                    'email' => htmlspecialchars($user->email),
                ],
                'confirmhash' => md5($delete),
                'sesskey' => sesskey(),
                'has_navigation' => true,
                'navigation_html' => get_navigation_html(),
            ];

            echo render_template('admin/user_delete_confirm', $context);
            exit;
        } else {
            // Confirmed - delete user
            if (delete_user($user)) {
                \core\session\manager::gc(); // Clean up sessions
                redirect($returnurl, get_string('userdeleted', 'core'));
            } else {
                $error = get_string('userdeletionerror', 'core');
            }
        }
    }
}

// Suspend user
else if ($suspend && confirm_sesskey()) {
    $user = $DB->get_record('users', ['id' => $suspend, 'deleted' => 0]);

    if (!$user) {
        $error = get_string('usernotfound', 'core');
    } else {
        if (suspend_user($user)) {
            $success = get_string('usersuspended', 'core');
        } else {
            $error = get_string('cannotsuspenduser', 'core');
        }
    }
}

// Unsuspend user
else if ($unsuspend && confirm_sesskey()) {
    $user = $DB->get_record('users', ['id' => $unsuspend, 'deleted' => 0]);

    if (!$user) {
        $error = get_string('usernotfound', 'core');
    } else {
        if (unsuspend_user($user)) {
            $success = get_string('userunsuspended', 'core');
        } else {
            $error = get_string('cannotunsuspenduser', 'core');
        }
    }
}

// Unlock user
else if ($unlock && confirm_sesskey()) {
    $user = $DB->get_record('users', ['id' => $unlock, 'deleted' => 0]);

    if (!$user) {
        $error = get_string('usernotfound', 'core');
    } else {
        if (unlock_user($user)) {
            $success = get_string('userunlocked', 'core');
        } else {
            $error = get_string('cannotunlockuser', 'core');
        }
    }
}

// Get user list
$search = optional_param('search', '', 'text');
$page = optional_param('page', 0, 'int');
$perpage = 25;

$users = $search
    ? \core\user\manager::search_users($search, $page * $perpage, $perpage)
    : \core\user\manager::get_all_users(false, $page * $perpage, $perpage);

$totalusers = \core\user\manager::count_users();

// Format users for template
$usersformatted = [];
foreach ($users as $userobj) {
    $issiteadmin = is_siteadmin($userobj->id);
    $isself = ($userobj->id == $USER->id);

    $usersformatted[] = [
        'id' => $userobj->id,
        'username' => htmlspecialchars($userobj->username),
        'fullname' => htmlspecialchars($userobj->firstname . ' ' . $userobj->lastname),
        'email' => htmlspecialchars($userobj->email),
        'issuspended' => (bool)$userobj->suspended,
        'isconfirmed' => (bool)$userobj->confirmed,
        'issiteadmin' => $issiteadmin,
        'isself' => $isself,
        'lastloginformatted' => $userobj->lastlogin ? date('d/m/Y H:i', $userobj->lastlogin) : get_string('never', 'core'),
        'candelete' => !$issiteadmin && !$isself,
        'cansuspend' => !$issiteadmin && !$isself && !$userobj->suspended,
        'canunsuspend' => !$issiteadmin && $userobj->suspended,
        'canconfirm' => !$userobj->confirmed,
        'sesskey' => sesskey(),
    ];
}

// Prepare context for template
$context = [
    'user' => $USER,
    'showadmin' => true,
    'totalusers' => $totalusers,
    'search' => htmlspecialchars($search),
    'users' => $usersformatted,
    'hasusers' => !empty($usersformatted),
    'error' => $error ? htmlspecialchars($error) : null,
    'success' => $success ? htmlspecialchars($success) : null,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
];

// Render and output
echo render_template('admin/user_list', $context);
