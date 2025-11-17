<?php
/**
 * Strings for component 'tool_mfa', language 'en'
 *
 * @package    tool_mfa
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Multi-factor authentication (MFA)';
$string['mfa'] = 'MFA';
$string['multifactorauthentication'] = 'Multi-factor authentication';

// Settings
$string['enabled'] = 'Enable MFA';
$string['enabled_desc'] = 'Enable multi-factor authentication for the system';
$string['requiremfa'] = 'Require MFA';
$string['requiremfa_desc'] = 'Require all users to configure at least one MFA factor';
$string['graceperiod'] = 'Grace period';
$string['graceperiod_desc'] = 'Number of days users have to configure MFA before being required';

// Factors
$string['factors'] = 'Authentication factors';
$string['availablefactors'] = 'Available factors';
$string['enabledfactors'] = 'Enabled factors';
$string['configuredfactors'] = 'Configured factors';
$string['nofactors'] = 'No factors configured';
$string['addfactor'] = 'Add factor';
$string['removefactor'] = 'Remove factor';

// Factor status
$string['factorsetup'] = 'Configure factor';
$string['factorremove'] = 'Remove factor';
$string['factorenabled'] = 'Factor enabled';
$string['factordisabled'] = 'Factor disabled';
$string['factoractive'] = 'Active';
$string['factorinactive'] = 'Inactive';

// Setup
$string['setupmfa'] = 'Configure MFA';
$string['setupfactor'] = 'Configure factor';
$string['setupinstructions'] = 'Follow the instructions to configure your authentication factor';
$string['setupcomplete'] = 'Configuration complete';
$string['setupfailed'] = 'Configuration error';

// Verification
$string['verify'] = 'Verify';
$string['verification'] = 'Verification';
$string['verificationcode'] = 'Verification code';
$string['verificationrequired'] = 'Verification required';
$string['verificationfailed'] = 'Verification failed';
$string['verificationsuccess'] = 'Verification successful';
$string['entercode'] = 'Enter the verification code';
$string['resendcode'] = 'Resend code';

// Login
$string['mfarequired'] = 'Multi-factor authentication required';
$string['selectfactor'] = 'Select an authentication factor';
$string['continuelogin'] = 'Continue login';
$string['cancellogin'] = 'Cancel login';

// User preferences
$string['preferences'] = 'MFA preferences';
$string['managedfactors'] = 'Manage factors';
$string['yourfactors'] = 'Your configured factors';
$string['addfactor'] = 'Add new factor';

// States
$string['state_pass'] = 'Verified';
$string['state_fail'] = 'Failed';
$string['state_neutral'] = 'Neutral';
$string['state_unknown'] = 'Unknown';

// Messages
$string['factorsetupsuccessfully'] = 'Factor configured successfully';
$string['factorremovedsuccessfully'] = 'Factor removed successfully';
$string['factorverifiedsuccessfully'] = 'Factor verified successfully';
$string['allrequiredfactorspassed'] = 'All required factors have been verified';

// Errors
$string['errorinvalidfactor'] = 'Invalid factor';
$string['errorfactornotfound'] = 'Factor not found';
$string['errorfactornotenabled'] = 'Factor is not enabled';
$string['errorverificationfailed'] = 'Verification error';
$string['errorinvalidcode'] = 'Invalid code';
$string['errorcodeexpired'] = 'Code has expired';
$string['errortoomanyattempts'] = 'Too many failed attempts';
$string['errorsetupfailed'] = 'Error configuring factor';

// Help
$string['mfa_help'] = 'Multi-factor authentication adds an extra layer of security by requiring multiple forms of identity verification.';
$string['factors_help'] = 'You can configure multiple authentication factors. During login, you will need to verify at least one of your configured factors.';
$string['setupmfa_help'] = 'Configure at least one authentication factor to protect your account. It is recommended to configure multiple factors as backup.';

// Notifications
$string['mfarequirednotification'] = 'You must configure multi-factor authentication';
$string['mfarequirednotification_desc'] = 'Multi-factor authentication is required for your account. Please configure at least one authentication factor.';
$string['mfagraceperiod'] = 'MFA grace period';
$string['mfagraceperiod_desc'] = 'You have {$a} days remaining to configure multi-factor authentication.';

// Reports
$string['mfareport'] = 'MFA report';
$string['mfastatus'] = 'MFA status';
$string['userswithmfa'] = 'Users with MFA';
$string['userswithoutmfa'] = 'Users without MFA';
$string['mfacompliance'] = 'MFA compliance';

// Capabilities
$string['tool_mfa:manage'] = 'Manage MFA';
$string['tool_mfa:configure'] = 'Configure MFA';
$string['tool_mfa:view'] = 'View MFA configuration';
$string['tool_mfa:require'] = 'Require MFA for users';

// Privacy
$string['privacy:metadata'] = 'The MFA plugin stores user authentication factor configuration information.';
$string['privacy:metadata:tool_mfa_user_factors'] = 'Authentication factors configured by the user';
$string['privacy:metadata:tool_mfa_user_factors:userid'] = 'User ID';
$string['privacy:metadata:tool_mfa_user_factors:factor'] = 'Factor type';
$string['privacy:metadata:tool_mfa_user_factors:timecreated'] = 'Creation date';
