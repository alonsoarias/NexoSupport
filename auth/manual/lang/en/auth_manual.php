<?php
/**
 * Strings for component 'auth_manual', language 'en'
 *
 * @package auth_manual
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$string['pluginname'] = 'Manual Authentication';
$string['auth_manualdescription'] = 'Users are created manually and authenticate against the internal database.';

// Settings page
$string['auth_manual_settings'] = 'Manual Authentication Settings';
$string['passwordpolicy'] = 'Password Policy';
$string['minpasswordlength'] = 'Minimum password length';
$string['minpasswordlength_help'] = 'Minimum number of characters required for user passwords.';
$string['minpasswordlengtherror'] = 'Minimum length must be at least 6 characters.';
$string['minpasswordlengthmaxerror'] = 'Minimum length cannot exceed 64 characters.';
$string['requireuppercase'] = 'Require uppercase';
$string['requireuppercase_help'] = 'Passwords must contain at least one uppercase letter.';
$string['requirelowercase'] = 'Require lowercase';
$string['requirelowercase_help'] = 'Passwords must contain at least one lowercase letter.';
$string['requirenumbers'] = 'Require numbers';
$string['requirenumbers_help'] = 'Passwords must contain at least one number.';
$string['requirespecialchars'] = 'Require special characters';
$string['requirespecialchars_help'] = 'Passwords must contain at least one special character (!@#$%^&*).';

// Password validation errors
$string['passwordminlength'] = 'Password must be at least {$a} characters long.';
$string['passwordrequireuppercase'] = 'Password must contain at least one uppercase letter.';
$string['passwordrequirelowercase'] = 'Password must contain at least one lowercase letter.';
$string['passwordrequirenumbers'] = 'Password must contain at least one number.';
$string['passwordrequirespecialchars'] = 'Password must contain at least one special character.';
