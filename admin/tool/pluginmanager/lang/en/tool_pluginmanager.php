<?php
/**
 * Strings for component 'tool_pluginmanager', language 'en'
 *
 * @package    tool_pluginmanager
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Plugin manager';
$string['pluginmanager'] = 'Plugin administrator';
$string['manageplugins'] = 'Manage plugins';

// Plugin overview
$string['pluginoverview'] = 'Plugin overview';
$string['installedplugins'] = 'Installed plugins';
$string['availableplugins'] = 'Available plugins';
$string['plugincount'] = '{$a} plugin(s)';

// Plugin types
$string['plugintypes'] = 'Plugin types';
$string['type_auth'] = 'Authentication methods';
$string['type_tool'] = 'Admin tools';
$string['type_theme'] = 'Themes';
$string['type_report'] = 'Reports';
$string['type_factor'] = 'MFA factors';

// Plugin info
$string['plugininfo'] = 'Plugin information';
$string['pluginname_field'] = 'Name';
$string['component'] = 'Component';
$string['version'] = 'Version';
$string['release'] = 'Release';
$string['maturity'] = 'Maturity';
$string['requires'] = 'Requires';
$string['dependencies'] = 'Dependencies';
$string['status'] = 'Status';

// Plugin status
$string['enabled'] = 'Enabled';
$string['disabled'] = 'Disabled';
$string['installed'] = 'Installed';
$string['notinstalled'] = 'Not installed';
$string['updateavailable'] = 'Update available';
$string['uptodate'] = 'Up to date';
$string['missing'] = 'Missing';

// Maturity levels
$string['maturity_alpha'] = 'Alpha (do not use in production)';
$string['maturity_beta'] = 'Beta (do not use in production)';
$string['maturity_rc'] = 'Release Candidate';
$string['maturity_stable'] = 'Stable';

// Actions
$string['enable'] = 'Enable';
$string['disable'] = 'Disable';
$string['install'] = 'Install';
$string['uninstall'] = 'Uninstall';
$string['update'] = 'Update';
$string['settings'] = 'Settings';
$string['viewdetails'] = 'View details';

// Install/Uninstall
$string['installplugin'] = 'Install plugin';
$string['uninstallplugin'] = 'Uninstall plugin';
$string['confirminstall'] = 'Confirm installation';
$string['confirmuninstall'] = 'Confirm uninstallation';
$string['installationinprogress'] = 'Installation in progress...';
$string['uninstallationinprogress'] = 'Uninstallation in progress...';

// Enable/Disable
$string['enableplugin'] = 'Enable plugin';
$string['disableplugin'] = 'Disable plugin';
$string['confirmenable'] = 'Confirm enable';
$string['confirmdisable'] = 'Confirm disable';
$string['disablemessage'] = 'Are you sure you want to disable this plugin?';

// Updates
$string['checkforupdates'] = 'Check for updates';
$string['updatesplugin'] = 'Updates available';
$string['noupdatesavailable'] = 'No updates available';
$string['updateinfo'] = 'Update information';
$string['currentversion'] = 'Current version: {$a}';
$string['latestversion'] = 'Latest version: {$a}';

// Dependencies
$string['dependencies'] = 'Dependencies';
$string['requiresplugin'] = 'Requires: {$a}';
$string['dependentplugins'] = 'Dependent plugins';
$string['nodependencies'] = 'No dependencies';
$string['missingdependency'] = 'Missing dependency: {$a}';
$string['cannotuninstallhasdependents'] = 'Cannot uninstall. Other plugins depend on this one.';

// Validation
$string['validating'] = 'Validating...';
$string['valid'] = 'Valid';
$string['invalid'] = 'Invalid';
$string['validationerrors'] = 'Validation errors';
$string['validationwarnings'] = 'Validation warnings';

// Filters
$string['filterby'] = 'Filter by';
$string['filterbytype'] = 'Filter by type';
$string['filterbystatus'] = 'Filter by status';
$string['searchplugins'] = 'Search plugins';

// Display
$string['showdetails'] = 'Show details';
$string['hidedetails'] = 'Hide details';
$string['viewfiles'] = 'View files';
$string['viewcode'] = 'View code';

// Results
$string['pluginenabled'] = 'Plugin enabled successfully';
$string['plugindisabled'] = 'Plugin disabled successfully';
$string['plugininstalled'] = 'Plugin installed successfully';
$string['pluginuninstalled'] = 'Plugin uninstalled successfully';
$string['pluginupdated'] = 'Plugin updated successfully';

// Errors
$string['errorenablingplugin'] = 'Error enabling plugin';
$string['errordisablingplugin'] = 'Error disabling plugin';
$string['errorinstallingplugin'] = 'Error installing plugin';
$string['erroruninstallingplugin'] = 'Error uninstalling plugin';
$string['errorupdatingplugin'] = 'Error updating plugin';
$string['errorpluginnotfound'] = 'Plugin not found';
$string['errorinvalidplugintype'] = 'Invalid plugin type';
$string['errorcannotenableplugin'] = 'Cannot enable plugin';
$string['errorcannotdisableplugin'] = 'Cannot disable plugin';

// Plugin directory
$string['plugindirectory'] = 'Plugin directory';
$string['directorypath'] = 'Directory path';
$string['browsedirectory'] = 'Browse directory';
$string['directorypermissions'] = 'Directory permissions';

// Backup/Restore
$string['backupplugin'] = 'Backup plugin';
$string['restoreplugin'] = 'Restore plugin';
$string['backupcreated'] = 'Backup created successfully';
$string['backuprestored'] = 'Backup restored successfully';

// Advanced
$string['advanced'] = 'Advanced';
$string['showadvanced'] = 'Show advanced options';
$string['hideadvanced'] = 'Hide advanced options';
$string['forceinstall'] = 'Force installation';
$string['skipdependencycheck'] = 'Skip dependency check';

// Help
$string['pluginmanager_help'] = 'The plugin manager allows you to install, uninstall, enable and disable system plugins.';
$string['installingplugin_help'] = 'To install a plugin, upload the plugin ZIP file or select one from the plugin directory.';
$string['dependencies_help'] = 'Dependencies are other plugins that must be installed for this plugin to work correctly.';

// Capabilities
$string['tool_pluginmanager:manage'] = 'Manage plugins';
$string['tool_pluginmanager:install'] = 'Install plugins';
$string['tool_pluginmanager:uninstall'] = 'Uninstall plugins';
$string['tool_pluginmanager:enable'] = 'Enable/disable plugins';
$string['tool_pluginmanager:view'] = 'View plugins';

// Privacy
$string['privacy:metadata'] = 'The plugin manager does not store personal data.';
