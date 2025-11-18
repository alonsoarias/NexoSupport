<?php
/**
 * Change password page.
 *
 * @package    core
 * @subpackage auth
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../config.php');
require_once('change_password_form.php');
require_once('lib.php');

$id = optional_param('id', SITEID, PARAM_INT); // Current course
$return = optional_param('return', 0, PARAM_BOOL); // Redirect after password change

$systemcontext = \core\context\system::instance();

$PAGE->set_url('/login/change_password.php', ['id' => $id]);
$PAGE->set_context($systemcontext);

if ($return) {
    // Redirect after successful password change
    if (empty($SESSION->wantsurl)) {
        $returnto = "$CFG->wwwroot/user/profile.php?id={$USER->id}";
    } else {
        $returnto = $SESSION->wantsurl;
    }
    unset($SESSION->wantsurl);
    redirect($returnto);
}

$strparticipants = get_string('participants', 'core');

// Require proper login; guest user cannot change password
if (!isloggedin() || isguestuser()) {
    if (empty($SESSION->wantsurl)) {
        $SESSION->wantsurl = $CFG->wwwroot . '/login/change_password.php';
    }
    redirect(get_login_url());
}

$PAGE->set_context(\core\context\user::instance($USER->id));
$PAGE->set_pagelayout('standard');

// Do not require change own password cap if change forced
if (!get_user_preferences('auth_forcepasswordchange', false)) {
    require_capability('nexosupport/user:changeownpassword', $systemcontext);
}

$mform = new login_change_password_form();
$mform->set_data(['id' => $id]);

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/user/profile.php?id=' . $USER->id);
} else if ($data = $mform->get_data()) {
    // Update password
    $USER->password = password_hash($data->newpassword1, PASSWORD_DEFAULT);
    $DB->update_record('users', $USER);

    user_add_password_history($USER->id, $data->newpassword1);

    // Logout other sessions if requested
    if (!empty($CFG->passwordchangelogout) || !empty($data->logoutothersessions)) {
        \core\session\manager::destroy_user_sessions($USER->id, session_id());
    }

    // Reset login lockout
    login_unlock_account($USER);

    // Clear force password change preference
    unset_user_preference('auth_forcepasswordchange', $USER);
    unset_user_preference('create_password', $USER);

    $strpasswordchanged = get_string('passwordchanged', 'core');

    $PAGE->set_title($strpasswordchanged);
    $PAGE->set_heading(fullname($USER));
    echo $OUTPUT->header();

    notice($strpasswordchanged, new moodle_url($PAGE->url, ['return' => 1]));

    echo $OUTPUT->footer();
    exit;
}

$strchangepassword = get_string('changepassword', 'core');
$fullname = fullname($USER, true);

$PAGE->set_title($strchangepassword);
$PAGE->set_heading($fullname);
echo $OUTPUT->header();

if (get_user_preferences('auth_forcepasswordchange')) {
    echo $OUTPUT->notification(get_string('forcepasswordchangenotice', 'core'));
}

$mform->display();
echo $OUTPUT->footer();
