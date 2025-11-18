<?php
/**
 * Set password form definition.
 *
 * @package    core
 * @subpackage auth
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');

/**
 * Set forgotten password form definition.
 */
class login_set_password_form extends nexoform {

    /**
     * Define the set password form.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $mform->setDisableShortforms(true);
        $mform->addElement('header', 'setpassword', get_string('setpassword', 'core'), '');

        // Include the username in the form so browsers will recognise that a password is being set
        $mform->addElement('text', 'username', '', 'style="display: none;"');
        $mform->setType('username', PARAM_RAW);

        // Token gives authority to change password
        $mform->addElement('hidden', 'token', '');
        $mform->setType('token', PARAM_ALPHANUM);

        // Visible elements
        $mform->addElement('static', 'username2', get_string('username', 'core'));

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

        $mform->addElement('password', 'password', get_string('newpassword', 'core'));
        $mform->addRule('password', get_string('required', 'core'), 'required', null, 'client');
        $mform->setType('password', PARAM_RAW);

        $strpasswordagain = get_string('newpassword', 'core') . ' (' . get_string('again', 'core') . ')';
        $mform->addElement('password', 'password2', $strpasswordagain);
        $mform->addRule('password2', get_string('required', 'core'), 'required', null, 'client');
        $mform->setType('password2', PARAM_RAW);

        $mform->addElement('checkbox', 'logoutothersessions', get_string('logoutothersessions', 'core'));
        $mform->setDefault('logoutothersessions', 1);

        $this->add_action_buttons(true);
    }

    /**
     * Perform extra password change validation.
     *
     * @param array $data submitted form fields.
     * @param array $files submitted with the form.
     * @return array errors occuring during validation.
     */
    public function validation($data, $files) {
        $user = $this->_customdata;
        $errors = parent::validation($data, $files);

        if ($data['password'] !== $data['password2']) {
            $errors['password'] = get_string('passwordsdiffer', 'core');
            $errors['password2'] = get_string('passwordsdiffer', 'core');
            return $errors;
        }

        $errmsg = '';
        if (!check_password_policy($data['password'], $errmsg, $user)) {
            $errors['password'] = $errmsg;
            $errors['password2'] = $errmsg;
            return $errors;
        }

        return $errors;
    }
}
