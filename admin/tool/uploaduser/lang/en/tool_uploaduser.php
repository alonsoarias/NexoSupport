<?php
/**
 * Strings for component 'tool_uploaduser', language 'en'
 *
 * @package    tool_uploaduser
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Bulk user upload';
$string['uploadusers'] = 'Upload users from file';

// Upload form
$string['csvfile'] = 'CSV file';
$string['csvdelimiter'] = 'CSV delimiter';
$string['csvencoding'] = 'Encoding';
$string['uploadfile'] = 'Upload file';
$string['selectfile'] = 'Select file';

// CSV format
$string['csvformat'] = 'CSV file format';
$string['csvformatdesc'] = 'The CSV file must contain the following columns: username, email, firstname, lastname, password (optional)';
$string['csvexample'] = 'CSV example';
$string['requiredfields'] = 'Required fields';
$string['optionalfields'] = 'Optional fields';

// Column headers
$string['col_username'] = 'username';
$string['col_email'] = 'email';
$string['col_firstname'] = 'firstname';
$string['col_lastname'] = 'lastname';
$string['col_password'] = 'password';
$string['col_role'] = 'role';

// Options
$string['createpasswords'] = 'Generate passwords automatically';
$string['sendwelcomeemail'] = 'Send welcome email';
$string['updateexisting'] = 'Update existing users';
$string['skipexisting'] = 'Skip existing users';

// Preview
$string['preview'] = 'Preview';
$string['previewrows'] = 'Preview {$a} rows';
$string['rownum'] = 'Row {$a}';
$string['validrows'] = '{$a} valid row(s)';
$string['invalidrows'] = '{$a} invalid row(s)';

// Results
$string['uploadresults'] = 'Upload results';
$string['userscreated'] = 'Users created: {$a}';
$string['usersupdated'] = 'Users updated: {$a}';
$string['usersskipped'] = 'Users skipped: {$a}';
$string['userserrors'] = 'Errors: {$a}';
$string['uploadcomplete'] = 'Upload complete';

// Errors
$string['csvempty'] = 'CSV file is empty';
$string['invalidcsvfile'] = 'Invalid CSV file';
$string['missingrequiredfield'] = 'Missing required field: {$a}';
$string['invalidrow'] = 'Invalid row at line {$a}';
$string['duplicateusername'] = 'Duplicate username: {$a}';
$string['duplicateemail'] = 'Duplicate email: {$a}';
$string['invalidusername'] = 'Invalid username: {$a}';
$string['invalidemail'] = 'Invalid email: {$a}';
$string['filetoobig'] = 'File is too large. Maximum size: {$a}';

// Download template
$string['downloadtemplate'] = 'Download CSV template';
$string['templatefile'] = 'Template file';

// Help
$string['uploadusers_help'] = 'Upload multiple users from a CSV file. The file must contain a header row with the column names.';
$string['csvdelimiter_help'] = 'CSV file delimiter character. Usually comma (,) or semicolon (;).';
$string['createpasswords_help'] = 'If checked, random passwords will be generated for users who do not have a password in the CSV.';
$string['updateexisting_help'] = 'If checked, existing users will be updated with the new information.';

// Capabilities
$string['tool_uploaduser:upload'] = 'Upload users from CSV';
$string['tool_uploaduser:manage'] = 'Manage user upload';

// Privacy
$string['privacy:metadata'] = 'The user upload tool does not store personal data.';
