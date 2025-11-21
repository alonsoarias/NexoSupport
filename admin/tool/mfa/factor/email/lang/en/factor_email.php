<?php
/**
 * English language strings for email factor.
 *
 * @package    factor_email
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$string['pluginname'] = 'Email verification';
$string['factor:description'] = 'Receive a verification code via email';
$string['factor:setup'] = 'Verification codes will be sent to your registered email address.';

// Form
$string['verificationcode'] = 'Verification code';
$string['email:checkyourinbox'] = 'Check your email inbox for the verification code. It may take a few moments to arrive.';

// Email content
$string['email:subject'] = '{$a}: Your verification code';
$string['email:greeting'] = 'Hello {$a},';
$string['email:message'] = 'Someone is trying to log in to your account at {$a->sitename}. If this was you, use the code below to verify your identity.';
$string['email:validity'] = 'This code is valid for {$a} and can only be used once.';
$string['email:loginlink'] = 'Or click this link to log in automatically: {$a}';
$string['email:revokelink'] = 'If this wasn\'t you, click here to block this login attempt: {$a}';
$string['email:loginbutton'] = 'Log in automatically';
$string['email:blockbutton'] = 'Block this login';
$string['email:notme'] = 'Wasn\'t you?';
$string['email:ipinfo'] = 'Security Information';
$string['email:originatingip'] = 'IP Address: {$a}';
$string['email:geoinfo'] = 'Location:';
$string['email:uadescription'] = 'Device/Browser:';

// Errors
$string['error:emptycode'] = 'Please enter the verification code';
$string['error:wrongverification'] = 'Invalid or expired verification code';
$string['error:badcode'] = 'Invalid verification link';

// Revocation
$string['email:revokesuccess'] = 'Login attempt blocked successfully for {$a}. All sessions have been terminated.';
$string['email:revoketitle'] = 'Block unauthorized login';
$string['email:revokeconfirm'] = 'Are you sure you want to block this login attempt? This will terminate all active sessions for this account.';

// Settings
$string['settings:duration'] = 'Code validity duration';
$string['settings:duration_help'] = 'How long the verification code remains valid';
$string['settings:suspend'] = 'Suspend account on block';
$string['settings:suspend_help'] = 'If enabled, the user account will be suspended when they report an unauthorized login attempt';
