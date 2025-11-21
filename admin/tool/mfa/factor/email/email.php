<?php
/**
 * Email factor verification/revocation page.
 *
 * Handles:
 * - Direct authentication via email link (pass=1)
 * - Blocking unauthorized login attempts (no pass param)
 *
 * @package    factor_email
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');

use tool_mfa\manager;
use tool_mfa\plugininfo\factor;

// Get parameters
$instanceid = optional_param('instance', 0, PARAM_INT);
$secret = optional_param('secret', '', PARAM_ALPHANUM);
$pass = optional_param('pass', 0, PARAM_INT);

// Setup page
$PAGE = get_page();
$PAGE->set_title(get_string('pluginname', 'factor_email'));
$PAGE->set_url($CFG->wwwroot . '/admin/tool/mfa/factor/email/email.php');

$OUTPUT = get_renderer();

// Validate parameters
if (empty($instanceid) || empty($secret)) {
    echo $OUTPUT->header();
    echo '<div class="alert alert-danger">' . get_string('error:badcode', 'factor_email') . '</div>';
    echo $OUTPUT->footer();
    exit;
}

// Get the instance record
$instance = $DB->get_record('tool_mfa', ['id' => $instanceid]);

if (!$instance || $instance->secret != $secret) {
    echo $OUTPUT->header();
    echo '<div class="alert alert-danger">' . get_string('error:badcode', 'factor_email') . '</div>';
    echo $OUTPUT->footer();
    exit;
}

// Get the user
$user = $DB->get_record('users', ['id' => $instance->userid]);

if (!$user) {
    echo $OUTPUT->header();
    echo '<div class="alert alert-danger">' . get_string('error:badcode', 'factor_email') . '</div>';
    echo $OUTPUT->footer();
    exit;
}

// Check code validity
$duration = get_config('factor_email', 'duration') ?: 1800;
$expired = ($instance->timecreated + $duration < time());

if ($expired) {
    echo $OUTPUT->header();
    echo '<div class="alert alert-warning">' . get_string('error:wrongverification', 'factor_email') . '</div>';
    echo $OUTPUT->footer();
    exit;
}

// Handle authentication (pass=1)
if ($pass == 1) {
    // Must be logged in as the same user
    if (!isset($USER->id) || $USER->id != $instance->userid) {
        // Redirect to login
        $_SESSION['wantsurl'] = $CFG->wwwroot . '/admin/tool/mfa/factor/email/email.php?' .
            http_build_query(['instance' => $instanceid, 'pass' => 1, 'secret' => $secret]);
        redirect($CFG->wwwroot . '/login/index.php');
    }

    // Mark the email factor as passed
    $emailfactor = factor::get_factor('email');
    if ($emailfactor) {
        $emailfactor->set_state(factor::STATE_PASS);
    }

    // Check overall status
    $status = manager::get_status();

    if ($status === factor::STATE_PASS) {
        manager::set_pass_state();
        $wantsurl = $SESSION->wantsurl ?? $CFG->wwwroot;
        unset($SESSION->wantsurl);
        redirect($wantsurl, get_string('auth:title', 'tool_mfa'), null, 'success');
    }

    // Need to complete more factors
    redirect($CFG->wwwroot . '/admin/tool/mfa/auth.php');
}

// Handle revocation (blocking unauthorized access)
$message = '';
$messagetype = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    // Verify sesskey
    if (!confirm_sesskey()) {
        $message = get_string('invalidsesskey', 'error');
        $messagetype = 'error';
    } else {
        // 1. Revoke ALL email factors for this user
        $DB->set_field('tool_mfa', 'revoked', 1, [
            'userid' => $user->id,
            'factor' => 'email',
        ]);

        // 2. Destroy all sessions for this user
        $DB->delete_records('sessions', ['userid' => $user->id]);

        // Also clear PHP sessions if possible
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['USER']) &&
            $_SESSION['USER']->id == $user->id) {
            session_destroy();
        }

        // 3. Optionally suspend the account
        if (get_config('factor_email', 'suspend')) {
            $DB->set_field('users', 'suspended', 1, ['id' => $user->id]);
        }

        $message = get_string('email:revokesuccess', 'factor_email', fullname($user));
        $messagetype = 'success';
    }
}

// Display revocation confirmation page
echo $OUTPUT->header();
?>

<div class="mfa-revoke-container" style="max-width: 500px; margin: 50px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messagetype === 'success' ? 'success' : ($messagetype === 'error' ? 'danger' : 'info'); ?>"
             style="padding: 15px; border-radius: 6px; margin-bottom: 20px;
                    background: <?php echo $messagetype === 'success' ? '#d4edda' : ($messagetype === 'error' ? '#f8d7da' : '#d1ecf1'); ?>;
                    color: <?php echo $messagetype === 'success' ? '#155724' : ($messagetype === 'error' ? '#721c24' : '#0c5460'); ?>;">
            <?php echo htmlspecialchars($message); ?>
        </div>

        <?php if ($messagetype === 'success'): ?>
            <div style="text-align: center;">
                <a href="<?php echo $CFG->wwwroot; ?>" class="btn btn-primary"
                   style="display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 6px;">
                    Go to homepage
                </a>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="mfa-header" style="text-align: center; margin-bottom: 30px;">
            <div class="mfa-icon" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;">
                <i class="fa fa-shield"></i>
            </div>
            <h2 style="margin: 0; color: #333;"><?php echo get_string('email:revoketitle', 'factor_email'); ?></h2>
        </div>

        <div class="alert alert-warning" style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <strong>Warning!</strong> <?php echo get_string('email:revokeconfirm', 'factor_email'); ?>
        </div>

        <div class="security-info" style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
            <p style="margin: 0 0 10px 0;"><strong>Login attempt details:</strong></p>
            <p style="margin: 0 0 5px 0;">IP Address: <?php echo htmlspecialchars($instance->createdfromip); ?></p>
            <p style="margin: 0 0 5px 0;">Time: <?php echo date('Y-m-d H:i:s', $instance->timecreated); ?></p>
            <p style="margin: 0;">Device: <?php echo htmlspecialchars(substr($instance->label, 0, 80)); ?>...</p>
        </div>

        <form method="post" action="">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

            <div style="display: flex; gap: 10px;">
                <button type="submit" name="confirm" value="1" class="btn btn-danger"
                        style="flex: 1; padding: 15px; background: #dc3545; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer;">
                    <i class="fa fa-ban"></i> Block this login
                </button>

                <a href="<?php echo $CFG->wwwroot; ?>" class="btn btn-secondary"
                   style="flex: 1; padding: 15px; background: #6c757d; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; text-align: center; text-decoration: none;">
                    Cancel
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php
echo $OUTPUT->footer();
