<?php
/**
 * Change password form definition.
 *
 * @package    core
 * @subpackage auth
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class login_change_password_form extends nexoform {

    function definition() {
        global $USER, $CFG;

        $mform = $this->_form;
        $mform->setDisableShortforms(true);

        $mform->addElement('header', 'changepassword', get_string('changepassword', 'core'), '');

        // Visible elements
        $mform->addElement('static', 'username', get_string('username', 'core'), $USER->username);

        // Password policy info
        $policies = [];
        $minlength = get_config('core', 'minpasswordlength') ?: 8;
        $requiredigit = get_config('core', 'passwordrequiredigit');
        $requirelower = get_config('core', 'passwordrequirelower');
        $requireupper = get_config('core', 'passwordrequireupper');

        $policies[] = get_string('minpasswordlength', 'core') . ': ' . $minlength;
        if ($requiredigit) {
            $policies[] = get_string('passwordrequiredigit', 'core');
        }
        if ($requirelower) {
            $policies[] = get_string('passwordrequirelower', 'core');
        }
        if ($requireupper) {
            $policies[] = get_string('passwordrequireupper', 'core');
        }

        if ($policies) {
            $mform->addElement('static', 'passwordpolicyinfo', '', implode('<br />', $policies));
        }

        $mform->addElement('password', 'password', get_string('oldpassword', 'core'));
        $mform->addRule('password', get_string('required', 'core'), 'required', null, 'client');
        $mform->setType('password', PARAM_RAW);

        $mform->addElement('password', 'newpassword1', get_string('newpassword', 'core'));
        $mform->addRule('newpassword1', get_string('required', 'core'), 'required', null, 'client');
        $mform->setType('newpassword1', PARAM_RAW);

        $mform->addElement('password', 'newpassword2', get_string('newpassword', 'core') . ' (' . get_string('again', 'core') . ')');
        $mform->addRule('newpassword2', get_string('required', 'core'), 'required', null, 'client');
        $mform->setType('newpassword2', PARAM_RAW);

        $mform->addElement('checkbox', 'logoutothersessions', get_string('logoutothersessions', 'core'));
        $mform->setDefault('logoutothersessions', 1);

        // Hidden optional params
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // Buttons
        if (get_user_preferences('auth_forcepasswordchange')) {
            $this->add_action_buttons(false);
        } else {
            $this->add_action_buttons(true);
        }
    }

    /**
     * Perform extra password change validation
     */
    function validation($data, $files) {
        global $USER, $DB;

        $errors = parent::validation($data, $files);

        // Verify current password
        $user = $DB->get_record('users', ['id' => $USER->id]);
        if (!$user || !password_verify($data['password'], $user->password)) {
            $errors['password'] = get_string('invalidlogin', 'core');
            return $errors;
        }

        if ($data['newpassword1'] != $data['newpassword2']) {
            $errors['newpassword1'] = get_string('passwordsdiffer', 'core');
            $errors['newpassword2'] = get_string('passwordsdiffer', 'core');
            return $errors;
        }

        if ($data['password'] == $data['newpassword1']) {
            $errors['newpassword1'] = get_string('mustchangepassword', 'core');
            $errors['newpassword2'] = get_string('mustchangepassword', 'core');
            return $errors;
        }

        // Check password policy
        $errmsg = '';
        if (!check_password_policy($data['newpassword1'], $errmsg, $USER)) {
            $errors['newpassword1'] = $errmsg;
            $errors['newpassword2'] = $errmsg;
            return $errors;
        }

        return $errors;
    }
}
