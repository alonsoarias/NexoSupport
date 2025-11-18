<?php
/**
 * Login library file of login/password related NexoSupport functions.
 *
 * Based on Moodle's login/lib.php
 *
 * @package    core
 * @subpackage lib
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

define('PWRESET_STATUS_NOEMAILSENT', 1);
define('PWRESET_STATUS_TOKENSENT', 2);
define('PWRESET_STATUS_OTHEREMAILSENT', 3);
define('PWRESET_STATUS_ALREADYSENT', 4);

/**
 * Processes a user's request to set a new password in the event they forgot the old one.
 */
function core_login_process_password_reset_request() {
    global $OUTPUT, $PAGE;

    require_once(__DIR__ . '/forgot_password_form.php');
    $mform = new login_forgot_password_form();

    if ($mform->is_cancelled()) {
        redirect(get_login_url());
    } else if ($data = $mform->get_data()) {
        $username = $email = '';
        if (!empty($data->username)) {
            $username = $data->username;
        } else {
            $email = $data->email;
        }
        list($status, $notice, $url) = core_login_process_password_reset($username, $email);

        echo $OUTPUT->header();
        notice($notice, $url);
        die;
    }

    // Display form
    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('passwordforgotteninstructions2', 'core'), 'generalbox boxwidthnormal boxaligncenter');
    $mform->display();
    echo $OUTPUT->footer();
}

/**
 * Process the password reset for the given user (via username or email).
 *
 * @param string $username the user name
 * @param string $email the user email
 * @return array array containing fields indicating the reset status, a info notice and redirect URL.
 */
function core_login_process_password_reset($username, $email) {
    global $CFG, $DB;

    if (empty($username) && empty($email)) {
        throw new \moodle_exception('cannotmailconfirm');
    }

    // Find the user account
    if (!empty($username)) {
        $username = \core_text::strtolower($username);
        $user = $DB->get_record('users', ['username' => $username, 'deleted' => 0, 'suspended' => 0]);
    } else {
        // Find by email
        $user = $DB->get_record('users', ['email' => $email, 'deleted' => 0, 'suspended' => 0]);
    }

    $pwresetstatus = PWRESET_STATUS_NOEMAILSENT;

    if ($user && !empty($user->confirmed)) {
        $systemcontext = \core\context\system::instance();

        // Check if user can reset password
        if (!has_capability('nexosupport/user:changeownpassword', $systemcontext, $user->id)) {
            if (send_password_change_info($user)) {
                $pwresetstatus = PWRESET_STATUS_OTHEREMAILSENT;
            } else {
                throw new \moodle_exception('cannotmailconfirm');
            }
        } else {
            // Check for existing reset request
            $resetinprogress = $DB->get_record('user_password_resets', ['userid' => $user->id]);
            $pwresettime = isset($CFG->pwresettime) ? $CFG->pwresettime : 1800;

            if (empty($resetinprogress)) {
                // New reset request
                $resetrecord = core_login_generate_password_reset($user);
                $sendemail = true;
            } else if ($resetinprogress->timerequested < (time() - $pwresettime)) {
                // Expired request - delete old and create new
                $DB->delete_records('user_password_resets', ['id' => $resetinprogress->id]);
                $resetrecord = core_login_generate_password_reset($user);
                $sendemail = true;
            } else if (empty($resetinprogress->timererequested)) {
                // First re-request
                $resetinprogress->timererequested = time();
                $DB->update_record('user_password_resets', $resetinprogress);
                $resetrecord = $resetinprogress;
                $sendemail = true;
            } else {
                // Already re-requested
                $pwresetstatus = PWRESET_STATUS_ALREADYSENT;
                $sendemail = false;
            }

            if ($sendemail) {
                $sendresult = send_password_change_confirmation_email($user, $resetrecord);
                if ($sendresult) {
                    $pwresetstatus = PWRESET_STATUS_TOKENSENT;
                } else {
                    throw new \moodle_exception('cannotmailconfirm');
                }
            }
        }
    }

    $url = $CFG->wwwroot . '/index.php';

    if (!empty($CFG->protectusernames)) {
        $status = 'emailpasswordconfirmmaybesent';
        $notice = get_string($status, 'core');
    } else if (empty($user)) {
        $status = 'emailpasswordconfirmnotsent';
        $notice = get_string($status, 'core');
        $url = $CFG->wwwroot . '/login/forgot_password.php';
    } else if (empty($user->email)) {
        $status = 'emailpasswordconfirmnoemail';
        $notice = get_string($status, 'core');
    } else if ($pwresetstatus == PWRESET_STATUS_ALREADYSENT) {
        $status = 'emailalreadysent';
        $notice = get_string($status, 'core');
    } else if ($pwresetstatus == PWRESET_STATUS_NOEMAILSENT) {
        $protectedemail = preg_replace('/([^@]*)@(.*)/', '******@$2', $user->email);
        $status = 'emailpasswordconfirmsent';
        $notice = get_string($status, 'core', $protectedemail);
    } else {
        $protectedemail = preg_replace('/([^@]*)@(.*)/', '******@$2', $user->email);
        $status = 'emailresetconfirmsent';
        $notice = get_string($status, 'core', $protectedemail);
    }

    return [$status, $notice, $url];
}

/**
 * This function processes a user's submitted token to validate the request to set a new password.
 *
 * @param string $token the one-use identifier which should verify the password reset request
 * @return void
 */
function core_login_process_password_set($token) {
    global $DB, $CFG, $OUTPUT, $PAGE, $SESSION;
    require_once(__DIR__ . '/set_password_form.php');

    $pwresettime = isset($CFG->pwresettime) ? $CFG->pwresettime : 1800;
    $sql = "SELECT u.*, upr.token, upr.timerequested, upr.id as tokenid
              FROM {users} u
              JOIN {user_password_resets} upr ON upr.userid = u.id
             WHERE upr.token = ?";
    $user = $DB->get_record_sql($sql, [$token]);

    $forgotpasswordurl = "{$CFG->wwwroot}/login/forgot_password.php";

    if (empty($user) || ($user->timerequested < (time() - $pwresettime - DAYSECS))) {
        echo $OUTPUT->header();
        notice(get_string('noresetrecord', 'core'), $forgotpasswordurl);
        die;
    }

    if ($user->timerequested < (time() - $pwresettime)) {
        $pwresetmins = floor($pwresettime / MINSECS);
        echo $OUTPUT->header();
        notice(get_string('resetrecordexpired', 'core', $pwresetmins), $forgotpasswordurl);
        die;
    }

    // Check this isn't guest user
    if (isguestuser($user)) {
        throw new \moodle_exception('cannotresetguestpwd');
    }

    // Token is correct and unexpired
    $mform = new login_set_password_form(null, $user);
    $data = $mform->get_data();

    if (empty($data)) {
        // Display form
        $setdata = new stdClass();
        $setdata->username = $user->username;
        $setdata->username2 = $user->username;
        $setdata->token = $user->token;
        $mform->set_data($setdata);
        echo $OUTPUT->header();
        echo $OUTPUT->box(get_string('setpasswordinstructions', 'core'), 'generalbox boxwidthnormal boxaligncenter');
        $mform->display();
        echo $OUTPUT->footer();
        return;
    } else {
        // User has submitted form - delete token
        $DB->delete_records('user_password_resets', ['id' => $user->tokenid]);

        // Update password
        $user->password = password_hash($data->password, PASSWORD_DEFAULT);
        $DB->update_record('users', $user);

        // User password history
        user_add_password_history($user->id, $data->password);

        // Logout other sessions if requested
        if (!empty($CFG->passwordchangelogout) || !empty($data->logoutothersessions)) {
            \core\session\manager::destroy_user_sessions($user->id, session_id());
        }

        // Reset login lockout
        login_unlock_account($user);

        // Clear force password change
        unset_user_preference('auth_forcepasswordchange', $user);
        unset_user_preference('create_password', $user);

        if (!empty($user->lang)) {
            unset($SESSION->lang);
        }

        complete_user_login($user);
        \core\session\manager::apply_concurrent_login_limit($user->id, session_id());

        $urltogo = core_login_get_return_url();
        unset($SESSION->wantsurl);

        redirect($urltogo, get_string('passwordset', 'core'), 1);
    }
}

/**
 * Create a new record in the database to track a new password set request for user.
 *
 * @param object $user the user record
 * @return object record created
 */
function core_login_generate_password_reset($user) {
    global $DB;

    $resetrecord = new stdClass();
    $resetrecord->timerequested = time();
    $resetrecord->userid = $user->id;
    $resetrecord->token = random_string(32);
    $resetrecord->id = $DB->insert_record('user_password_resets', $resetrecord);

    return $resetrecord;
}

/**
 * Determine where a user should be redirected after they have been logged in.
 *
 * @return string url the user should be redirected to
 */
function core_login_get_return_url() {
    global $CFG, $SESSION, $USER;

    // Prepare redirection
    if (user_not_fully_set_up($USER, true)) {
        $urltogo = $CFG->wwwroot . '/user/edit.php';
    } else if (isset($SESSION->wantsurl) && (strpos($SESSION->wantsurl, $CFG->wwwroot) === 0)) {
        $urltogo = $SESSION->wantsurl;
        unset($SESSION->wantsurl);
    } else {
        $urltogo = $CFG->wwwroot . '/';
        unset($SESSION->wantsurl);
    }

    return $urltogo;
}

/**
 * Send password change confirmation email.
 *
 * @param object $user User object
 * @param object $resetrecord Password reset record
 * @return bool Success status
 */
function send_password_change_confirmation_email($user, $resetrecord) {
    global $CFG;

    $sitename = get_config('core', 'sitename');
    $reseturl = $CFG->wwwroot . '/login/forgot_password.php?token=' . $resetrecord->token;

    $data = new stdClass();
    $data->firstname = $user->firstname;
    $data->lastname = $user->lastname;
    $data->username = $user->username;
    $data->sitename = $sitename;
    $data->link = $reseturl;
    $data->reseturl = $reseturl;

    $message = get_string('emailresetconfirmation', 'core', $data);
    $subject = get_string('emailresetconfirmationsubject', 'core', $sitename);

    return email_to_user($user, get_admin(), $subject, $message);
}

/**
 * Send password change info email (when user can't reset password themselves).
 *
 * @param object $user User object
 * @return bool Success status
 */
function send_password_change_info($user) {
    global $CFG;

    $sitename = get_config('core', 'sitename');
    $supportemail = $CFG->supportemail ?? get_admin()->email;

    $data = new stdClass();
    $data->firstname = $user->firstname;
    $data->lastname = $user->lastname;
    $data->username = $user->username;
    $data->sitename = $sitename;
    $data->admin = get_string('administrator', 'core');
    $data->supportemail = $supportemail;

    $message = get_string('emailpasswordchangeinfo', 'core', $data);
    $subject = get_string('emailpasswordchangeinfosubject', 'core', $sitename);

    return email_to_user($user, get_admin(), $subject, $message);
}

/**
 * Validates the forgot password form data.
 *
 * @param array $data array containing the data to be validated (email and username)
 * @return array array of errors compatible with mform
 */
function core_login_validate_forgot_password_data($data) {
    global $CFG, $DB;

    $errors = [];

    if ((!empty($data['username']) && !empty($data['email'])) || (empty($data['username']) && empty($data['email']))) {
        $errors['username'] = get_string('usernameoremail', 'core');
        $errors['email'] = get_string('usernameoremail', 'core');
    } else if (!empty($data['email'])) {
        if (!validate_email($data['email'])) {
            $errors['email'] = get_string('invalidemail', 'core');
        } else {
            $user = $DB->get_record('users', ['email' => $data['email'], 'deleted' => 0]);
            if ($user && empty($user->confirmed)) {
                send_confirmation_email($user);
                if (empty($CFG->protectusernames)) {
                    $errors['email'] = get_string('confirmednot', 'core');
                }
            } else if (!$user && empty($CFG->protectusernames)) {
                $errors['email'] = get_string('emailnotfound', 'core');
            }
        }
    } else {
        if ($user = $DB->get_record('users', ['username' => $data['username'], 'deleted' => 0])) {
            if (empty($user->confirmed)) {
                send_confirmation_email($user);
                if (empty($CFG->protectusernames)) {
                    $errors['username'] = get_string('confirmednot', 'core');
                }
            }
        }
        if (!$user && empty($CFG->protectusernames)) {
            $errors['username'] = get_string('usernamenotfound', 'core');
        }
    }

    return $errors;
}
