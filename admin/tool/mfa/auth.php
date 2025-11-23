<?php
/**
 * MFA authentication page.
 *
 * This page handles the MFA verification flow.
 *
 * @package    tool_mfa
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

require_once(__DIR__ . '/../../../config.php');

use tool_mfa\manager;
use tool_mfa\plugininfo\factor;

// Must be logged in to use MFA
if (!isset($USER->id) || $USER->id == 0) {
    redirect($CFG->wwwroot . '/login/index.php');
}

// Check if already passed MFA
if (!empty($SESSION->tool_mfa_authenticated)) {
    $wantsurl = $SESSION->wantsurl ?? $CFG->wwwroot;
    unset($SESSION->wantsurl);
    redirect($wantsurl);
}

// Get the next factor to verify
$factor = manager::get_next_user_login_factor();

// Check overall status
$status = manager::get_status();

if ($status === factor::STATE_PASS) {
    // All factors passed
    manager::set_pass_state();
    $wantsurl = $SESSION->wantsurl ?? $CFG->wwwroot;
    unset($SESSION->wantsurl);
    redirect($wantsurl);
}

if ($status === factor::STATE_FAIL || $status === factor::STATE_LOCKED) {
    // Failed - log out
    require_logout();
    redirect($CFG->wwwroot . '/login/index.php', get_string('error:mfafailed', 'tool_mfa'), null, 'error');
}

// Setup page
global $PAGE, $OUTPUT;
$PAGE->set_title(get_string('auth:title', 'tool_mfa'));
$PAGE->set_heading(get_string('auth:title', 'tool_mfa'));
$PAGE->set_url(new \core\nexo_url('/admin/tool/mfa/auth.php'));

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check sesskey
    if (!confirm_sesskey()) {
        $errors['sesskey'] = get_string('invalidsesskey', 'error');
    } else {
        // Handle logout
        if (isset($_POST['logout'])) {
            require_logout();
            redirect($CFG->wwwroot . '/login/index.php');
        }

        // Handle resend code
        if (isset($_POST['resend']) && method_exists($factor, 'resend_code')) {
            $factor->resend_code();
            redirect($CFG->wwwroot . '/admin/tool/mfa/auth.php', get_string('email:checkyourinbox', 'factor_email'), null, 'info');
        }

        // Validate factor form
        $errors = $factor->login_form_validation($_POST);

        if (empty($errors)) {
            // Check status again after validation
            $status = manager::get_status();

            if ($status === factor::STATE_PASS) {
                manager::set_pass_state();
                $wantsurl = $SESSION->wantsurl ?? $CFG->wwwroot;
                unset($SESSION->wantsurl);
                redirect($wantsurl);
            }

            // Still need more factors or failed
            if ($status === factor::STATE_FAIL || $status === factor::STATE_LOCKED) {
                require_logout();
                redirect($CFG->wwwroot . '/login/index.php', get_string('error:mfafailed', 'tool_mfa'), null, 'error');
            }

            // Refresh to get next factor
            redirect($CFG->wwwroot . '/admin/tool/mfa/auth.php');
        }
    }
}

// Trigger form data setup (sends email for email factor)
$factor->login_form_definition_after_data(null);

// Render the page
echo $OUTPUT->header();
?>

<div class="mfa-auth-container" style="max-width: 500px; margin: 50px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <div class="mfa-header" style="text-align: center; margin-bottom: 30px;">
        <div class="mfa-icon" style="font-size: 48px; color: #667eea; margin-bottom: 15px;">
            <i class="fa <?php echo htmlspecialchars($factor->get_icon()); ?>"></i>
        </div>
        <h2 style="margin: 0; color: #333;"><?php echo get_string('auth:title', 'tool_mfa'); ?></h2>
        <p style="color: #666; margin-top: 10px;">
            <?php echo get_string('auth:currentfactor', 'tool_mfa', $factor->get_display_name()); ?>
        </p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <?php foreach ($errors as $error): ?>
                <p style="margin: 0;"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo $CFG->wwwroot; ?>/admin/tool/mfa/auth.php" class="mfa-form">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

        <?php
        // Get description if available
        $description = $factor->get_description();
        if (!empty($description)):
        ?>
            <p style="color: #666; margin-bottom: 20px;"><?php echo htmlspecialchars($description); ?></p>
        <?php endif; ?>

        <?php if ($factor->get_name() === 'email'): ?>
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="verificationcode" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">
                    <?php echo get_string('verificationcode', 'factor_email'); ?>
                </label>
                <input type="text"
                       name="verificationcode"
                       id="verificationcode"
                       maxlength="6"
                       autocomplete="one-time-code"
                       inputmode="numeric"
                       pattern="[0-9]{6}"
                       placeholder="000000"
                       autofocus
                       required
                       style="width: 100%; padding: 15px; font-size: 24px; text-align: center; letter-spacing: 8px; border: 2px solid #ddd; border-radius: 8px; outline: none; transition: border-color 0.2s;"
                       onfocus="this.style.borderColor='#667eea'"
                       onblur="this.style.borderColor='#ddd'">
                <p style="margin-top: 10px; font-size: 14px; color: #666;">
                    <?php echo get_string('email:checkyourinbox', 'factor_email'); ?>
                </p>
            </div>
        <?php elseif ($factor->get_name() === 'fallback'): ?>
            <div class="alert alert-warning" style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <?php echo get_string('factor:fallback_message', 'tool_mfa'); ?>
            </div>
        <?php endif; ?>

        <div class="mfa-actions" style="display: flex; flex-direction: column; gap: 10px;">
            <?php if ($factor->get_name() !== 'fallback'): ?>
                <button type="submit" name="verify" class="btn btn-primary" style="width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 500; cursor: pointer;">
                    <?php echo get_string('auth:submit', 'tool_mfa'); ?>
                </button>
            <?php endif; ?>

            <?php if ($factor->get_name() === 'email'): ?>
                <button type="submit" name="resend" class="btn btn-secondary" style="width: 100%; padding: 12px; background: #f8f9fa; color: #333; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; cursor: pointer;">
                    <i class="fa fa-refresh"></i> Resend code
                </button>
            <?php endif; ?>

            <button type="submit" name="logout" class="btn btn-link" style="width: 100%; padding: 12px; background: transparent; color: #dc3545; border: none; font-size: 14px; cursor: pointer;">
                <i class="fa fa-sign-out"></i> <?php echo get_string('auth:logout', 'tool_mfa'); ?>
            </button>
        </div>
    </form>

    <div class="mfa-help" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center;">
        <p style="font-size: 13px; color: #999;">
            <?php echo get_string('auth:subtitle', 'tool_mfa'); ?>
        </p>
    </div>
</div>

<style>
    .mfa-auth-container input[type="text"]:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .btn:hover {
        opacity: 0.9;
    }
</style>

<?php
echo $OUTPUT->footer();
