<?php
/**
 * English language strings for MFA tool.
 *
 * @package    tool_mfa
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$string['pluginname'] = 'Multi-factor authentication';
$string['mfa'] = 'MFA';

// Authentication page
$string['auth:title'] = 'Verify your identity';
$string['auth:subtitle'] = 'Complete the verification to continue';
$string['auth:currentfactor'] = 'Verification method: {$a}';
$string['auth:submit'] = 'Verify';
$string['auth:cancel'] = 'Cancel';
$string['auth:logout'] = 'Log out';

// Factor states
$string['factor:pass'] = 'Verified';
$string['factor:fail'] = 'Failed';
$string['factor:neutral'] = 'Pending';
$string['factor:locked'] = 'Locked';
$string['factor:unknown'] = 'Unknown';

// Fallback
$string['factor:fallback'] = 'No verification method';
$string['factor:fallback_message'] = 'No verification method is available for your account. Please contact an administrator.';

// Settings
$string['settings:enabled'] = 'Enable MFA';
$string['settings:enabled_help'] = 'Enable multi-factor authentication for the site';
$string['settings:enablefactor'] = 'Enable factor';
$string['settings:enablefactor_help'] = 'Enable this verification method';
$string['settings:weight'] = 'Factor weight';
$string['settings:weight_help'] = 'The weight determines how much this factor contributes to completing MFA. A total of 100 points is required to pass.';
$string['settings:lockout'] = 'Lockout threshold';
$string['settings:lockout_help'] = 'Number of failed attempts before locking the factor';
$string['settings:exemptadmins'] = 'Exempt administrators';
$string['settings:exemptadmins_help'] = 'If enabled, site administrators will not be required to complete MFA';
$string['settings:redir_exclusions'] = 'URL exclusions';
$string['settings:redir_exclusions_help'] = 'URLs that should not trigger MFA (one per line)';

// Errors
$string['error:mloopdetected'] = 'MFA redirect loop detected. Please try logging in again.';
$string['error:mfafailed'] = 'Multi-factor authentication failed. Please try again.';
$string['error:locked'] = 'Your account has been locked due to too many failed attempts. Please contact an administrator.';

// Events
$string['event:userpassed'] = 'User passed MFA';
$string['event:userfailed'] = 'User failed MFA';
$string['event:factorsetup'] = 'Factor configured';
$string['event:factorrevoked'] = 'Factor revoked';

// Setup
$string['setup:title'] = 'Set up verification';
$string['setup:intro'] = 'Your account requires additional verification. Please set up one of the following methods:';
$string['setup:complete'] = 'Verification setup complete';

// Status
$string['status:mfaenabled'] = 'MFA is enabled';
$string['status:mfadisabled'] = 'MFA is disabled';
$string['status:factorsenabled'] = '{$a} verification method(s) enabled';
