<?php
/**
 * Confirm self registered user.
 *
 * @package    core
 * @subpackage auth
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/lib.php');

$data = optional_param('data', '', PARAM_RAW);  // Formatted as:  secret/username
$p = optional_param('p', '', PARAM_ALPHANUM);   // Old parameter:  secret
$s = optional_param('s', '', PARAM_RAW);        // Old parameter:  username
$redirect = optional_param('redirect', '', PARAM_LOCALURL);    // Where to redirect after confirmation

$PAGE->set_url('/login/confirm.php');
$PAGE->set_context(\core\context\system::instance());

if (!empty($data) || (!empty($p) && !empty($s))) {
    if (!empty($data)) {
        $dataelements = explode('/', $data, 2);
        $usersecret = $dataelements[0];
        $username = $dataelements[1];
    } else {
        $usersecret = $p;
        $username = $s;
    }

    $confirmed = confirm_user($username, $usersecret);

    if ($confirmed == AUTH_CONFIRM_ALREADY) {
        $user = $DB->get_record('users', ['username' => $username]);

        $PAGE->navbar->add(get_string("alreadyconfirmed", 'core'));
        $PAGE->set_title(get_string("alreadyconfirmed", 'core'));
        $PAGE->set_heading(get_config('core', 'sitename'));
        echo $OUTPUT->header();
        echo $OUTPUT->box_start('generalbox centerpara boxwidthnormal boxaligncenter');
        echo "<p>" . get_string("alreadyconfirmed", 'core') . "</p>\n";
        echo $OUTPUT->single_button(core_login_get_return_url(), get_string('continue', 'core'));
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;
    } else if ($confirmed == AUTH_CONFIRM_OK) {
        // The user has confirmed successfully, let's log them in
        if (!$user = $DB->get_record('users', ['username' => $username])) {
            throw new \nexo_exception('cannotfinduser', '', '', s($username));
        }

        if (!$user->suspended) {
            complete_user_login($user);
            \core\session\manager::apply_concurrent_login_limit($user->id, session_id());

            // Check where to go
            if (!empty($redirect)) {
                if (!empty($SESSION->wantsurl)) {
                    unset($SESSION->wantsurl);
                }
                redirect($redirect);
            }
        }

        $PAGE->navbar->add(get_string("confirmed", 'core'));
        $PAGE->set_title(get_string("confirmed", 'core'));
        $PAGE->set_heading(get_config('core', 'sitename'));
        echo $OUTPUT->header();
        echo $OUTPUT->box_start('generalbox centerpara boxwidthnormal boxaligncenter');
        echo "<h3>" . get_string("thanks", 'core') . ", " . fullname($USER) . "</h3>\n";
        echo "<p>" . get_string("confirmed", 'core') . "</p>\n";
        echo $OUTPUT->single_button(core_login_get_return_url(), get_string('continue', 'core'));
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;
    } else {
        throw new \nexo_exception('invalidconfirmdata', 'core');
    }
} else {
    throw new \nexo_exception("errorwhenconfirming", 'core');
}

redirect("$CFG->wwwroot/");
