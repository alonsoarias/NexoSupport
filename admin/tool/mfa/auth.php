<?php
defined('NEXOSUPPORT_INTERNAL') || die();
global $PAGE, $OUTPUT, $CFG, $USER;
require_once(__DIR__ . '/../../../config.php');

use tool_mfa\manager;
use tool_mfa\plugininfo\factor;

if (!isset($USER->id) || $USER->id == 0) {
    redirect($CFG->wwwroot . '/login/index.php');
}

if (!empty($SESSION->tool_mfa_authenticated)) {
    $wantsurl = $SESSION->wantsurl ?? $CFG->wwwroot;
    unset($SESSION->wantsurl);
    redirect($wantsurl);
}

$factor = manager::get_next_user_login_factor();
$status = manager::get_status();

if ($status === factor::STATE_PASS) {
    manager::set_pass_state();
    $wantsurl = $SESSION->wantsurl ?? $CFG->wwwroot;
    unset($SESSION->wantsurl);
    redirect($wantsurl);
}

if ($status === factor::STATE_FAIL || $status === factor::STATE_LOCKED) {
    require_logout();
    redirect($CFG->wwwroot . '/login/index.php', get_string('error:mfafailed', 'tool_mfa'), null, 'error');
}

$PAGE->set_title(get_string('auth:title', 'tool_mfa'));
$PAGE->set_heading(get_string('auth:title', 'tool_mfa'));
$PAGE->set_url(new \core\nexo_url('/admin/tool/mfa/auth.php'));

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!confirm_sesskey()) {
        $errors['sesskey'] = get_string('invalidsesskey', 'error');
    } else {
        if (isset($_POST['logout'])) {
            require_logout();
            redirect($CFG->wwwroot . '/login/index.php');
        }

        if (isset($_POST['resend']) && method_exists($factor, 'resend_code')) {
            $factor->resend_code();
            redirect($CFG->wwwroot . '/admin/tool/mfa/auth.php', get_string('email:checkyourinbox', 'factor_email'), null, 'info');
        }

        $errors = $factor->login_form_validation($_POST);

        if (empty($errors)) {
            $status = manager::get_status();
            if ($status === factor::STATE_PASS) {
                manager::set_pass_state();
                $wantsurl = $SESSION->wantsurl ?? $CFG->wwwroot;
                unset($SESSION->wantsurl);
                redirect($wantsurl);
            }
            if ($status === factor::STATE_FAIL || $status === factor::STATE_LOCKED) {
                require_logout();
                redirect($CFG->wwwroot . '/login/index.php', get_string('error:mfafailed', 'tool_mfa'), null, 'error');
            }
            redirect($CFG->wwwroot . '/admin/tool/mfa/auth.php');
        }
    }
}

$factor->login_form_definition_after_data(null);

echo $OUTPUT->header();
?>
<!-- ...resto igual... -->
