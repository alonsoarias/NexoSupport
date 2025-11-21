<?php
/**
 * Email factor - sends verification codes via email.
 *
 * @package    factor_email
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace factor_email;

defined('NEXOSUPPORT_INTERNAL') || die();

use tool_mfa\local\factor\object_factor_base;
use tool_mfa\plugininfo\factor as factor_plugininfo;

/**
 * Email factor class.
 *
 * Sends a 6-digit verification code to the user's email address.
 */
class factor extends object_factor_base {

    /** @var string Icon */
    protected string $icon = 'fa-envelope';

    /** @var bool Flag to prevent duplicate email sends */
    private bool $code_sent = false;

    /**
     * Check if factor is active for current user.
     *
     * @return bool True if user has valid email
     */
    public function is_active(): bool {
        if (!$this->is_enabled()) {
            return false;
        }

        return $this->is_ready();
    }

    /**
     * Check if factor is ready (user has valid email).
     *
     * @return bool True if ready
     */
    protected function is_ready(): bool {
        global $DB, $USER;

        // Email must not be empty
        if (empty($USER->email)) {
            return false;
        }

        // Email must be valid format
        if (!filter_var($USER->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Check if factor has been revoked
        if ($DB->record_exists('tool_mfa', [
            'userid' => $USER->id,
            'factor' => 'email',
            'label' => $USER->email,
            'revoked' => 1,
        ])) {
            return false;
        }

        return true;
    }

    /**
     * Define login form elements.
     *
     * @param object $mform Form object
     * @return object Modified form
     */
    public function login_form_definition($mform) {
        // Add verification code input
        $mform->addElement('text', 'verificationcode', get_string('verificationcode', 'factor_email'), [
            'maxlength' => 6,
            'size' => 10,
            'autocomplete' => 'one-time-code',
            'inputmode' => 'numeric',
            'pattern' => '[0-9]{6}',
            'class' => 'mfa-verification-code',
        ]);
        $mform->setType('verificationcode', PARAM_INT);
        $mform->addRule('verificationcode', get_string('required'), 'required', null, 'client');

        // Add resend link info
        $mform->addElement('html', '<p class="mfa-email-hint">' .
            get_string('email:checkyourinbox', 'factor_email') . '</p>');

        return $mform;
    }

    /**
     * Called after form data is set - generates and sends code.
     *
     * @param object $mform Form object
     * @return object Modified form
     */
    public function login_form_definition_after_data($mform) {
        // Only send code once per page load
        if (!$this->code_sent) {
            $this->generate_and_email_code();
            $this->code_sent = true;
        }

        return $mform;
    }

    /**
     * Validate the verification code.
     *
     * @param array $data Form data
     * @return array Validation errors
     */
    public function login_form_validation(array $data): array {
        global $USER;

        $errors = [];

        $code = $data['verificationcode'] ?? '';

        if (empty($code)) {
            $errors['verificationcode'] = get_string('error:emptycode', 'factor_email');
            return $errors;
        }

        // Validate the code
        if (!$this->check_verification_code($code)) {
            $errors['verificationcode'] = get_string('error:wrongverification', 'factor_email');

            // Increment lock counter
            $this->increment_failure();
        }

        return $errors;
    }

    /**
     * Generate a new code and send it via email.
     *
     * @return void
     */
    private function generate_and_email_code(): void {
        global $DB, $USER;

        // Check if there's an existing valid code
        $duration = get_config('factor_email', 'duration') ?: 1800; // 30 minutes default

        $sql = "SELECT * FROM {tool_mfa}
                WHERE userid = ?
                  AND factor = ?
                  AND label != ?
                  AND revoked = 0
                ORDER BY timecreated DESC
                LIMIT 1";

        $record = $DB->get_record_sql($sql, [$USER->id, 'email', $USER->email]);

        // Generate new 6-digit code
        $newcode = random_int(100000, 999999);
        $useragent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip = get_user_ip();

        if (empty($record)) {
            // Create new code record
            $instanceid = $DB->insert_record('tool_mfa', [
                'userid' => $USER->id,
                'factor' => 'email',
                'secret' => $newcode,
                'label' => $useragent,  // Store user agent to identify device
                'timecreated' => time(),
                'createdfromip' => $ip,
                'timemodified' => time(),
                'lastverified' => 0,
                'revoked' => 0,
                'lockcounter' => 0,
            ]);

            // Send the email
            $this->email_verification_code($instanceid);

        } else if ($record->timecreated + $duration < time()) {
            // Code expired, regenerate
            $DB->update_record('tool_mfa', [
                'id' => $record->id,
                'secret' => $newcode,
                'label' => $useragent,
                'timecreated' => time(),
                'createdfromip' => $ip,
                'timemodified' => time(),
                'revoked' => 0,
            ]);

            $this->email_verification_code($record->id);
        }
        // If code is still valid, don't resend (user can refresh)
    }

    /**
     * Send verification email.
     *
     * @param int $instanceid MFA record ID
     * @return bool Success
     */
    public function email_verification_code(int $instanceid): bool {
        global $DB, $USER, $CFG;

        $instance = $DB->get_record('tool_mfa', ['id' => $instanceid]);
        if (!$instance) {
            return false;
        }

        // Build email content
        $sitename = get_config('core', 'sitename') ?: 'NexoSupport';
        $duration = get_config('factor_email', 'duration') ?: 1800;
        $validitytext = $this->format_duration($duration);

        // Get geo info for the IP (simplified)
        $geoinfo = $this->get_ip_location($instance->createdfromip);

        // Build authentication URL (for one-click login)
        $authurl = $CFG->wwwroot . '/admin/tool/mfa/factor/email/email.php?' .
            http_build_query([
                'instance' => $instance->id,
                'pass' => 1,
                'secret' => $instance->secret,
            ]);

        // Build revocation URL (to block unauthorized access)
        $revokeurl = $CFG->wwwroot . '/admin/tool/mfa/factor/email/email.php?' .
            http_build_query([
                'instance' => $instance->id,
                'secret' => $instance->secret,
            ]);

        // Build email body
        $subject = get_string('email:subject', 'factor_email', $sitename);

        $body = get_string('email:greeting', 'factor_email', $USER->firstname) . "\n\n";
        $body .= get_string('email:message', 'factor_email', [
            'sitename' => $sitename,
            'siteurl' => $CFG->wwwroot,
        ]) . "\n\n";
        $body .= "==============================\n";
        $body .= "    " . $instance->secret . "\n";
        $body .= "==============================\n\n";
        $body .= get_string('email:validity', 'factor_email', $validitytext) . "\n\n";
        $body .= get_string('email:loginlink', 'factor_email', $authurl) . "\n\n";
        $body .= get_string('email:revokelink', 'factor_email', $revokeurl) . "\n\n";
        $body .= "---\n";
        $body .= get_string('email:ipinfo', 'factor_email') . "\n";
        $body .= get_string('email:originatingip', 'factor_email', $instance->createdfromip) . "\n";
        if (!empty($geoinfo)) {
            $body .= get_string('email:geoinfo', 'factor_email') . " " . $geoinfo . "\n";
        }
        $body .= get_string('email:uadescription', 'factor_email') . "\n";
        $body .= $instance->label . "\n";

        // HTML version
        $htmlbody = $this->build_html_email($instance, $authurl, $revokeurl, $sitename, $validitytext, $geoinfo);

        // Send email
        return $this->send_email($USER, $subject, $body, $htmlbody);
    }

    /**
     * Build HTML email body.
     *
     * @param object $instance MFA record
     * @param string $authurl Authentication URL
     * @param string $revokeurl Revocation URL
     * @param string $sitename Site name
     * @param string $validitytext Validity text
     * @param string $geoinfo Geographic info
     * @return string HTML body
     */
    private function build_html_email($instance, $authurl, $revokeurl, $sitename, $validitytext, $geoinfo): string {
        global $USER;

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .code { font-size: 32px; font-weight: bold; letter-spacing: 8px; text-align: center;
                background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px;
                  text-decoration: none; border-radius: 6px; margin: 10px 5px; }
        .button.danger { background: #dc3545; }
        .info { font-size: 12px; color: #666; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>' . htmlspecialchars($sitename) . '</h2>
    </div>

    <p>' . get_string('email:greeting', 'factor_email', htmlspecialchars($USER->firstname)) . '</p>

    <p>' . get_string('email:message', 'factor_email', [
        'sitename' => htmlspecialchars($sitename),
        'siteurl' => '',
    ]) . '</p>

    <div class="code">' . htmlspecialchars($instance->secret) . '</div>

    <p>' . get_string('email:validity', 'factor_email', $validitytext) . '</p>

    <p style="text-align: center;">
        <a href="' . htmlspecialchars($authurl) . '" class="button">' .
            get_string('email:loginbutton', 'factor_email') . '</a>
    </p>

    <p style="text-align: center; font-size: 14px; color: #666;">
        ' . get_string('email:notme', 'factor_email') . '<br>
        <a href="' . htmlspecialchars($revokeurl) . '" class="button danger">' .
            get_string('email:blockbutton', 'factor_email') . '</a>
    </p>

    <div class="info">
        <strong>' . get_string('email:ipinfo', 'factor_email') . '</strong><br>
        ' . get_string('email:originatingip', 'factor_email', htmlspecialchars($instance->createdfromip)) . '<br>';

        if (!empty($geoinfo)) {
            $html .= get_string('email:geoinfo', 'factor_email') . ' ' . htmlspecialchars($geoinfo) . '<br>';
        }

        $html .= '<br><strong>' . get_string('email:uadescription', 'factor_email') . '</strong><br>
        ' . htmlspecialchars($instance->label) . '
    </div>
</div>
</body>
</html>';

        return $html;
    }

    /**
     * Check if the entered code is valid.
     *
     * @param string $enteredcode Code entered by user
     * @return bool True if valid
     */
    private function check_verification_code(string $enteredcode): bool {
        global $DB, $USER;

        $duration = get_config('factor_email', 'duration') ?: 1800;

        // Get the code record
        $sql = "SELECT * FROM {tool_mfa}
                WHERE userid = ?
                  AND factor = ?
                  AND label != ?
                  AND revoked = 0
                ORDER BY timecreated DESC
                LIMIT 1";

        $record = $DB->get_record_sql($sql, [$USER->id, 'email', $USER->email]);

        if (!$record) {
            return false;
        }

        // Check code matches
        if ($enteredcode != $record->secret) {
            return false;
        }

        // Check not expired
        if ($record->timecreated + $duration < time()) {
            return false;
        }

        // Code is valid - update state
        $this->set_state(factor_plugininfo::STATE_PASS);

        return true;
    }

    /**
     * Increment failure counter.
     *
     * @return void
     */
    private function increment_failure(): void {
        global $DB, $USER;

        $sql = "UPDATE {tool_mfa}
                SET lockcounter = lockcounter + 1
                WHERE userid = ?
                  AND factor = ?
                  AND label != ?
                  AND revoked = 0";

        $DB->execute($sql, [$USER->id, 'email', $USER->email]);

        // Check if now locked
        $this->load_locked_state();
    }

    /**
     * Clean up after MFA pass.
     *
     * @return void
     */
    public function post_pass_state(): void {
        global $DB, $USER;

        // Delete temporary code records (not the base email record)
        $sql = "DELETE FROM {tool_mfa}
                WHERE userid = ?
                  AND factor = ?
                  AND label != ?";

        $DB->execute($sql, [$USER->id, 'email', $USER->email]);

        parent::post_pass_state();
    }

    /**
     * Format duration in human readable format.
     *
     * @param int $seconds Duration in seconds
     * @return string Formatted string
     */
    private function format_duration(int $seconds): string {
        if ($seconds < 60) {
            return $seconds . ' ' . get_string('seconds', 'core');
        } else if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return $minutes . ' ' . get_string('minutes', 'core');
        } else {
            $hours = floor($seconds / 3600);
            return $hours . ' ' . get_string('hours', 'core');
        }
    }

    /**
     * Get geographic location from IP (simplified).
     *
     * @param string $ip IP address
     * @return string Location string or empty
     */
    private function get_ip_location(string $ip): string {
        // In a full implementation, this would use a GeoIP service
        // For now, return empty (can be extended later)
        return '';
    }

    /**
     * Send email to user.
     *
     * @param object $user User object
     * @param string $subject Email subject
     * @param string $body Plain text body
     * @param string $htmlbody HTML body
     * @return bool Success
     */
    private function send_email($user, string $subject, string $body, string $htmlbody): bool {
        // Use the email_to_user function if available
        if (function_exists('email_to_user')) {
            $noreplyuser = new \stdClass();
            $noreplyuser->email = 'noreply@' . parse_url($GLOBALS['CFG']->wwwroot, PHP_URL_HOST);
            $noreplyuser->firstname = 'NexoSupport';
            $noreplyuser->lastname = '';
            $noreplyuser->id = -1;

            return email_to_user($user, $noreplyuser, $subject, $body, $htmlbody);
        }

        // Fallback to PHP mail
        $headers = [
            'From: noreply@' . parse_url($GLOBALS['CFG']->wwwroot, PHP_URL_HOST),
            'Reply-To: noreply@' . parse_url($GLOBALS['CFG']->wwwroot, PHP_URL_HOST),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
        ];

        return mail($user->email, $subject, $htmlbody, implode("\r\n", $headers));
    }

    /**
     * Resend the verification code.
     *
     * @return bool Success
     */
    public function resend_code(): bool {
        global $DB, $USER;

        // Delete existing code
        $sql = "DELETE FROM {tool_mfa}
                WHERE userid = ?
                  AND factor = ?
                  AND label != ?";

        $DB->execute($sql, [$USER->id, 'email', $USER->email]);

        // Generate and send new code
        $this->code_sent = false;
        $this->generate_and_email_code();

        return true;
    }
}
