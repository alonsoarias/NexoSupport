<?php
/**
 * MFA Authentication Page
 *
 * This is the main MFA verification page where users complete
 * their multi-factor authentication.
 *
 * @package    tool_mfa
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

require_once(__DIR__ . '/../../../config.php');

use tool_mfa\manager;
use tool_mfa\plugininfo\factor;

global $PAGE, $OUTPUT, $CFG, $USER, $SESSION, $DB;

// Must be logged in
if (!isset($USER->id) || $USER->id == 0) {
    redirect($CFG->wwwroot . '/login/index.php');
}

// Already authenticated - redirect to destination
if (!empty($SESSION->tool_mfa_authenticated)) {
    $wantsurl = $SESSION->wantsurl ?? $CFG->wwwroot;
    unset($SESSION->wantsurl);
    unset($SESSION->tool_mfa_setwantsurl);
    redirect($wantsurl);
}

// Get current status and next factor
$factor = manager::get_next_user_login_factor();
$status = manager::get_status();

// Check if already passed
if ($status === factor::STATE_PASS) {
    manager::set_pass_state();
    $wantsurl = $SESSION->wantsurl ?? $CFG->wwwroot;
    unset($SESSION->wantsurl);
    unset($SESSION->tool_mfa_setwantsurl);
    redirect($wantsurl);
}

// Check if failed or locked
if ($status === factor::STATE_FAIL || $status === factor::STATE_LOCKED) {
    require_logout();
    redirect($CFG->wwwroot . '/login/index.php',
        get_string('error:mfafailed', 'tool_mfa'), null, 'error');
}

// Page setup
$PAGE->set_title(get_string('auth:title', 'tool_mfa'));
$PAGE->set_heading(get_string('auth:title', 'tool_mfa'));
$PAGE->set_url(new \core\nexo_url('/admin/tool/mfa/auth.php'));
$PAGE->set_pagelayout('login');

// Process form submission
$errors = [];
$message = null;
$messagetype = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!confirm_sesskey()) {
        $errors['sesskey'] = get_string('invalidsesskey', 'error');
    } else {
        // Handle logout request
        if (isset($_POST['logout'])) {
            require_logout();
            redirect($CFG->wwwroot . '/login/index.php');
        }

        // Handle resend code request
        if (isset($_POST['resend']) && method_exists($factor, 'resend_code')) {
            $factor->resend_code();
            $message = get_string('email:checkyourinbox', 'factor_email');
            $messagetype = 'success';
        } else {
            // Validate the factor
            $errors = $factor->login_form_validation($_POST);

            if (empty($errors)) {
                // Re-check status after validation
                $status = manager::get_status();

                if ($status === factor::STATE_PASS) {
                    manager::set_pass_state();
                    $wantsurl = $SESSION->wantsurl ?? $CFG->wwwroot;
                    unset($SESSION->wantsurl);
                    unset($SESSION->tool_mfa_setwantsurl);
                    redirect($wantsurl);
                }

                if ($status === factor::STATE_FAIL || $status === factor::STATE_LOCKED) {
                    require_logout();
                    redirect($CFG->wwwroot . '/login/index.php',
                        get_string('error:mfafailed', 'tool_mfa'), null, 'error');
                }

                // More factors needed
                redirect($CFG->wwwroot . '/admin/tool/mfa/auth.php');
            }
        }
    }
}

// Generate/send code if needed (for email factor)
$factor->login_form_definition_after_data(null);

// Get factor info
$factorname = $factor->get_name();
$factordisplay = $factor->get_display_name();
$factoricon = $factor->get_icon();
$factordesc = $factor->get_description();
$isFallback = ($factorname === 'fallback');

// Output page
echo $OUTPUT->header();
?>
<!DOCTYPE html>
<html lang="<?php echo current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_string('auth:title', 'tool_mfa'); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .mfa-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        .mfa-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .mfa-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .mfa-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .mfa-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
        }
        .mfa-body {
            padding: 30px;
        }
        .factor-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: center;
        }
        .factor-info h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 4px;
        }
        .factor-info p {
            font-size: 13px;
            color: #666;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-group input[type="text"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e1e5eb;
            border-radius: 8px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: 600;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group input.error {
            border-color: #dc3545;
        }
        .error-message {
            color: #dc3545;
            font-size: 13px;
            margin-top: 8px;
        }
        .hint {
            color: #666;
            font-size: 13px;
            margin-top: 8px;
            text-align: center;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            margin-top: 12px;
        }
        .btn-secondary:hover {
            background: #e9ecef;
        }
        .btn-link {
            background: none;
            color: #667eea;
            font-size: 14px;
            margin-top: 16px;
        }
        .btn-link:hover {
            text-decoration: underline;
        }
        .mfa-footer {
            padding: 20px 30px;
            border-top: 1px solid #e1e5eb;
            text-align: center;
        }
        .user-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            color: #666;
            margin-bottom: 12px;
        }
        .user-info i {
            color: #667eea;
        }
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
            color: #999;
            font-size: 12px;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e1e5eb;
        }
        .divider span {
            padding: 0 12px;
        }
        .fallback-message {
            text-align: center;
            padding: 20px;
        }
        .fallback-message i {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 16px;
        }
        .fallback-message h3 {
            color: #333;
            margin-bottom: 8px;
        }
        .fallback-message p {
            color: #666;
            font-size: 14px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="mfa-container">
        <div class="mfa-header">
            <div class="mfa-icon">
                <i class="fas <?php echo htmlspecialchars($factoricon); ?>"></i>
            </div>
            <h1><?php echo get_string('auth:title', 'tool_mfa'); ?></h1>
            <p><?php echo get_string('auth:subtitle', 'tool_mfa'); ?></p>
        </div>

        <div class="mfa-body">
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messagetype; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors['sesskey'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($errors['sesskey']); ?>
            </div>
            <?php endif; ?>

            <div class="factor-info">
                <h3><i class="fas <?php echo htmlspecialchars($factoricon); ?>"></i> <?php echo htmlspecialchars($factordisplay); ?></h3>
                <?php if ($factordesc): ?>
                <p><?php echo htmlspecialchars($factordesc); ?></p>
                <?php endif; ?>
            </div>

            <?php if ($isFallback): ?>
            <!-- Fallback - no factors available -->
            <div class="fallback-message">
                <i class="fas fa-exclamation-triangle"></i>
                <h3><?php echo get_string('factor:fallback', 'tool_mfa'); ?></h3>
                <p><?php echo get_string('factor:fallback_message', 'tool_mfa'); ?></p>
            </div>

            <form method="post">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                <button type="submit" name="logout" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i> <?php echo get_string('auth:logout', 'tool_mfa'); ?>
                </button>
            </form>

            <?php else: ?>
            <!-- Factor verification form -->
            <form method="post" id="mfa-form">
                <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                <?php if ($factorname === 'email'): ?>
                <!-- Email factor -->
                <div class="form-group">
                    <label for="verificationcode"><?php echo get_string('verificationcode', 'factor_email'); ?></label>
                    <input type="text"
                           id="verificationcode"
                           name="verificationcode"
                           maxlength="6"
                           autocomplete="one-time-code"
                           inputmode="numeric"
                           pattern="[0-9]{6}"
                           placeholder="000000"
                           class="<?php echo !empty($errors['verificationcode']) ? 'error' : ''; ?>"
                           autofocus
                           required>
                    <?php if (!empty($errors['verificationcode'])): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['verificationcode']); ?>
                    </div>
                    <?php endif; ?>
                    <p class="hint"><?php echo get_string('email:checkyourinbox', 'factor_email'); ?></p>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> <?php echo get_string('auth:submit', 'tool_mfa'); ?>
                </button>

                <?php if ($factorname === 'email'): ?>
                <div class="divider"><span><?php echo get_string('or', 'core'); ?></span></div>

                <button type="submit" name="resend" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> <?php echo get_string('resend', 'core'); ?>
                </button>
                <?php endif; ?>

                <button type="submit" name="logout" class="btn btn-link">
                    <i class="fas fa-sign-out-alt"></i> <?php echo get_string('auth:logout', 'tool_mfa'); ?>
                </button>
            </form>
            <?php endif; ?>
        </div>

        <div class="mfa-footer">
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span><?php echo htmlspecialchars($USER->email); ?></span>
            </div>
            <small style="color: #999;">
                <?php echo htmlspecialchars(get_config('core', 'sitename') ?: 'NexoSupport'); ?>
            </small>
        </div>
    </div>

    <script>
    // Auto-submit when 6 digits entered
    document.addEventListener('DOMContentLoaded', function() {
        var codeInput = document.getElementById('verificationcode');
        if (codeInput) {
            codeInput.addEventListener('input', function() {
                // Only allow digits
                this.value = this.value.replace(/[^0-9]/g, '');

                // Auto-submit when 6 digits
                if (this.value.length === 6) {
                    document.getElementById('mfa-form').submit();
                }
            });
        }
    });
    </script>
</body>
</html>
<?php
echo $OUTPUT->footer();
