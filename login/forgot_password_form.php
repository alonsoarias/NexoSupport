<?php
/**
 * Forgot password form definition.
 *
 * @package    core
 * @subpackage auth
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/lib.php');

/**
 * Reset forgotten password form definition.
 */
class login_forgot_password_form extends nexoform {

    /**
     * Define the forgot password form.
     */
    function definition() {
        global $USER;

        $mform = $this->_form;
        $mform->setDisableShortforms(true);

        $mform->addElement('header', 'searchbyusername', get_string('searchbyusername', 'core'), '');

        $mform->addElement('text', 'username', get_string('username', 'core'), 'size="20"');
        $mform->setType('username', PARAM_RAW);

        $submitlabel = get_string('search', 'core');
        $mform->addElement('submit', 'submitbuttonusername', $submitlabel);

        $mform->addElement('header', 'searchbyemail', get_string('searchbyemail', 'core'), '');

        $mform->addElement('text', 'email', get_string('email', 'core'), 'maxlength="100" size="30"');
        $mform->setType('email', PARAM_RAW_TRIMMED);

        $submitlabel = get_string('search', 'core');
        $mform->addElement('submit', 'submitbuttonemail', $submitlabel);
    }

    /**
     * Validate user input from the forgot password form.
     *
     * @param array $data array of submitted form fields.
     * @param array $files submitted with the form.
     * @return array errors occuring during validation.
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $errors += core_login_validate_forgot_password_data($data);
        return $errors;
    }
}
