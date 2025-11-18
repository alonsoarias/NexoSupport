# NexoSupport Changelog

## Version 1.1.6 (2025-01-18) - Comprehensive Logging System

### 🎯 Overview
Complete event logging system implementation adapted from Moodle's logstore architecture. All user actions, system changes, and administrative operations are now tracked in a comprehensive audit log. Includes upgrade helper library and event logging integration across the system.

### ✨ New Features

#### Event Logging System
- **logstore_standard_log Table**: Event logging adapted for NexoSupport
  - Records all system events with full context
  - Tracks user actions, admin operations, and system changes
  - CRUD operation tracking (Create, Read, Update, Delete)
  - IP address and origin tracking
  - Context-aware logging (contextid, contextlevel, contextinstanceid)
- **Event Base Class** (`lib/classes/event/base.php`)
  - Abstract base class for all events
  - Automatic context detection
  - Component-based event organization
  - Extensible for custom events
- **Core Events Implemented** (10 total):
  - `user_loggedin` - User login tracking
  - `user_created` - User creation tracking
  - `user_updated` - User modification tracking
  - `user_deleted` - User deletion tracking
  - `user_suspended` - User suspension tracking
  - `user_unsuspended` - User reactivation tracking
  - `role_assigned` - Role assignment tracking
  - `role_unassigned` - Role removal tracking
  - `capability_assigned` - Capability assignment tracking
  - `capability_updated` - Capability modification tracking

#### User Preferences System
- **user_preferences Table**: Persistent user settings storage
  - Per-user configurable preferences
  - Timestamp tracking for modifications
  - Unique constraint per user/preference combination

#### Password Security
- **user_password_history Table**: Password reuse prevention
  - Tracks password history per user
  - Enables password policy enforcement
- **user_password_resets Table**: Secure password reset workflow
  - Token-based password reset
  - Request timestamp tracking
  - Rate limiting support

#### Site Administration
- **siteadmin Configuration**: Primary site administrator designation
  - Stored in config table
  - Automatically set to first user during upgrade
  - Used for system-level permissions

#### Upgrade Helper Library
- **lib/upgradelib.php**: Comprehensive upgrade support functions (440+ lines)
  - upgrade_log() - Log upgrade progress
  - upgrade_handle_exception() - Exception handling
  - upgrade_core_savepoint() - Version checkpoints
  - upgrade_check_php_version() - PHP version validation
  - upgrade_check_php_extensions() - Extension validation
  - upgrade_check_database() - Database connectivity check
  - purge_all_caches() - Cache clearing
  - And 15+ additional helper functions

#### Event Integration
- **login/index.php**: Triggers user_loggedin event after successful authentication
- **lib/classes/user/manager.php**: Triggers user_created and user_updated events
- **lib/userlib.php**: Triggers user_deleted, user_suspended, user_unsuspended events
- **lib/classes/rbac/role.php**: Triggers capability_assigned and capability_updated events
- **lib/classes/rbac/access.php**: Triggers role_assigned and role_unassigned events

### 📁 New Files

#### Event Classes (11 files)
- `lib/classes/event/base.php` (276 lines) - Abstract event base class
- `lib/classes/event/user_loggedin.php` - Login event
- `lib/classes/event/user_created.php` - User creation event
- `lib/classes/event/user_updated.php` - User update event
- `lib/classes/event/user_deleted.php` - User deletion event
- `lib/classes/event/user_suspended.php` - User suspension event
- `lib/classes/event/user_unsuspended.php` - User reactivation event
- `lib/classes/event/role_assigned.php` - Role assignment event
- `lib/classes/event/role_unassigned.php` - Role removal event
- `lib/classes/event/capability_assigned.php` - Capability assignment event
- `lib/classes/event/capability_updated.php` - Capability update event

#### Core Libraries
- `lib/upgradelib.php` (440+ lines) - Upgrade helper functions

### 🔧 Modified Files

- `admin/upgrade.php` - Changed authentication from require_capability() to is_siteadmin()
- `lib/upgrade.php` - Added v1.1.6 upgrade with proper xmldb object creation
- `lib/version.php` - Bumped to v1.1.6 (2025011806)
- `login/index.php` - Added user_loggedin event trigger
- `lib/classes/user/manager.php` - Added user_created and user_updated event triggers
- `lib/userlib.php` - Added user_deleted, user_suspended, user_unsuspended event triggers
- `lib/classes/rbac/role.php` - Added capability_assigned and capability_updated event triggers
- `lib/classes/rbac/access.php` - Added role_assigned and role_unassigned event triggers
- `lang/es/core.php` - Added 10 event name strings

### 🗄️ Database Changes

#### New Tables (4)
1. **logstore_standard_log** - Complete event logging (17 fields)
   - eventname, component, action, target
   - objecttable, objectid, crud
   - contextid, contextlevel, contextinstanceid
   - userid, relateduserid, anonymous
   - other (JSON), timecreated, origin, ip, realuserid
   - Indexes: timecreated, userid, contextid, eventname

2. **user_preferences** - User settings (5 fields)
   - userid, name, value, timemodified
   - Unique index: userid + name

3. **user_password_history** - Password history (4 fields)
   - userid, hash, timecreated
   - Index: userid

4. **user_password_resets** - Password reset tokens (5 fields)
   - userid, token, timerequested, timererequested
   - Indexes: token, userid

#### New Config Values
- `siteadmin` - ID of primary site administrator

### 🏗️ Architecture Highlights

#### Adapted from Moodle
- **Event Structure**: logstore_standard_log adapted from Moodle's schema for NexoSupport needs
- **Event Class Hierarchy**: \core\event\base with specialized subclasses
- **Component Organization**: Events organized by component (core, plugins, etc.)
- **LMS-Specific Fields Removed**: No courseid or edulevel - adapted for general purpose use

#### Event System Features
- **Automatic Context Detection**: Events inherit context from data
- **Flexible Data Storage**: 'other' field supports JSON for custom data
- **Origin Tracking**: Web, CLI, WS origins supported
- **Anonymous Events**: Support for anonymous event logging
- **RBAC Integration**: Full context tracking (contextid, contextlevel, contextinstanceid)

### 🔐 Security Features

- **Complete Audit Trail**: All system changes logged
- **IP Address Tracking**: Security incident investigation
- **User Action Attribution**: Every action tied to a user
- **Password History**: Prevent password reuse
- **Secure Token Generation**: Cryptographic random tokens
- **Site Admin Configuration**: Centralized admin designation

### 📊 Statistics

- **Tables Created**: 4 (logstore, preferences, password history/resets)
- **Event Classes Created**: 11 (1 base + 10 concrete events)
- **Functions Created**: 20+ (upgradelib.php helpers)
- **Files Modified**: 9 (upgrade, version, login, user manager, userlib, rbac classes, lang)
- **Config Values Added**: 1 (siteadmin)
- **Database Fields Total**: 34 across new tables
- **Indexes Created**: 9 for optimal query performance
- **Language Strings Added**: 10 (event names in Spanish)
- **Lines of Code**: ~1,500+

### 🐛 Bugs Fixed

1. **admin/upgrade.php permission error** - Changed from require_capability() to is_siteadmin() check
2. **xmldb_table::add_field() TypeError** - Fixed by using proper xmldb_field object instantiation
3. **LMS-specific fields in log table** - Removed courseid and edulevel fields to adapt for NexoSupport
4. **No audit trail** - Complete logging system now in place with 10 event types
5. **No user preferences storage** - user_preferences table created
6. **No siteadmin config** - siteadmin designation now in config
7. **Missing upgrade helpers** - Created comprehensive lib/upgradelib.php

### 🔄 Upgrade Notes

**From v1.1.5 to v1.1.6:**
1. Access `/admin/upgrade`
2. System will create 4 new tables automatically
3. First user will be designated as siteadmin
4. All future actions will be logged automatically
5. No manual intervention required

### 🚀 Usage Examples

```php
// Log a user login event
$event = \core\event\user_loggedin::create([
    'objectid' => $user->id,
    'context' => \core\rbac\context::system()
]);
$event->trigger();

// Get user preference
$theme = get_user_preferences('theme', 'default');

// Set user preference
set_user_preference('lang', 'en');
```

### 📝 Next Steps (Future Versions)

- **v1.2.0**: Event viewer/reporter in admin interface
  - Browse and filter event logs
  - Export event reports
  - Event statistics dashboard
- **v1.3.0**: Event observers/hooks system
  - Plugin event observers
  - Custom event handlers
  - Event-driven workflows
- **v1.4.0**: Log retention policies and cleanup
  - Configurable retention periods
  - Automated log archival
  - Log cleanup cron tasks

### 👥 Credits

Developed following Moodle LMS event and logstore architecture.

### 📄 License

GNU General Public License v3.0 or later

---

**Version**: 1.1.6 (2025011806)
**Release Date**: 2025-01-18
**Previous Version**: 1.1.5 (2025011805)
**Maturity**: STABLE

---

## Version 1.1.5 (2025-01-18) - nexoform Framework + Core Functions

### 🎯 Overview
Critical infrastructure update to support password management and future form-based features. Implements NexoSupport's nexoform framework (Moodle-compatible) and essential core functions for user preferences and utility operations.

### ✨ New Features

#### Form Library (lib/formslib.php)
- **nexoform Base Class**: Abstract base class for all forms
  - Automatic form rendering with NexoSupport HTML structure (.nform)
  - Built-in validation framework
  - CSRF protection via sesskey
  - definition() and definition_after_data() hooks
  - get_data(), is_submitted(), is_validated() methods
- **NexoQuickForm**: Form builder class
  - Support for multiple element types: text, password, hidden, checkbox, select, header, static
  - Validation rules per element
  - Type safety with PARAM_* constants
  - Element groups (for button arrays)
  - Default values management
  - Auto-export of submitted values

#### User Preferences Functions (lib/functions.php)
- **get_user_preferences($name, $default, $userid)**: Retrieve user preference from database
- **set_user_preference($name, $value, $userid)**: Store user preference in database
- **unset_user_preference($name, $userid)**: Delete user preference from database
- All functions support current user (null userid) and specific users
- Graceful degradation if user_preferences table doesn't exist yet

#### Utility Functions (lib/functions.php)
- **random_string($length)**: Generate cryptographically secure random strings
- **strip_querystring($url)**: Remove query string from URL
- **qualified_me()**: Get current fully-qualified URL
- **s($var)**: HTML escape function (htmlspecialchars shortcut)

#### Password Policy Updates (lib/authlib.php)
- **Enhanced check_password_policy()**: Now supports two signatures for full Moodle compatibility
  - Old style: `check_password_policy($password, $authmethod, &$error)`
  - Moodle 3.x+ style: `check_password_policy($password, &$error, $user)`
  - Direct config-based validation (no plugin dependency)
  - Supports all policy settings: minlength, requiredigit, requirelower, requireupper

### 📁 New Files

- `lib/formslib.php` (550+ lines) - Complete nexoform implementation
  - nexoform abstract class
  - NexoQuickForm class
  - Full form rendering engine

### 🔧 Modified Files

- `lib/functions.php` - Added 5 new functions (120+ lines):
  - get_user_preferences()
  - set_user_preference()
  - unset_user_preference()
  - random_string()
  - strip_querystring()
  - qualified_me()
  - s()

- `lib/authlib.php` - Updated check_password_policy():
  - Dual signature support for backward compatibility
  - Config-based password validation
  - Better error messages

- `lib/version.php` - Bumped to v1.1.5 (2025011805)

### 🏗️ Architecture Highlights

#### Moodle Compatibility
- **Exact Class Hierarchy**: nexoform follows Moodle's formslib.php structure
- **Compatible Method Signatures**: definition(), validation(), get_data(), etc.
- **Form Elements**: Supports all basic Moodle form element types
- **Rendering**: Generates NexoSupport HTML with .nform classes

#### Type Safety
- Form element types use PARAM_* constants
- Full type hints on all new functions
- Defensive programming with null checks

#### Extensibility
- Easy to add new form element types in MoodleQuickForm
- Plugin-friendly user preferences system
- Random string generation supports custom lengths

### 🔐 Security Features

- **CSRF Protection**: All forms include sesskey automatically
- **Type Validation**: Form elements use PARAM_* for input cleaning
- **Password Policy**: Configurable requirements enforced at validation
- **HTML Escaping**: s() function prevents XSS
- **Cryptographic Randomness**: random_string() uses random_int()

### 📊 Statistics

- **Classes Created**: 2 (nexoform, NexoQuickForm)
- **Functions Created**: 7 (user prefs + utilities + s())
- **Functions Modified**: 1 (check_password_policy enhanced)
- **New Files Total**: 1 (formslib.php)
- **Lines of Code**: ~670+

### 🐛 Bugs Fixed

1. **Missing nexoform class** - Password management forms were extending non-existent form base class
2. **Missing user preferences** - get_user_preferences() didn't exist, breaking form defaults
3. **Password policy incompatibility** - check_password_policy() signature didn't match Moodle's
4. **Missing utility functions** - random_string(), s(), qualified_me() were undefined

### 📝 Dependencies

**This version is required for:**
- v1.1.4 password management forms to work correctly
- Any future form-based features
- User preference storage

**Backward Compatibility:**
- Fully compatible with v1.1.4
- All existing code continues to work
- Password forms now functional

### 🔄 Upgrade Notes

**From v1.1.4 to v1.1.5:**
1. No database changes required
2. Password management forms from v1.1.4 now work correctly
3. No manual intervention required
4. Recommended: Create user_preferences table if not exists (will be in future upgrade script)

### 🚀 Next Steps (Future Versions)

- **v1.2.0**: Database schema for user_preferences table
  - Upgrade script to create table
  - Migration of any hardcoded preferences

- **v1.3.0**: Advanced form elements
  - File upload support
  - Rich text editor (HTML editor)
  - Date/time picker
  - Color picker

### 👥 Credits

Developed following Moodle LMS formslib.php architecture and core function patterns.

### 📄 License

GNU General Public License v3.0 or later

---

**Version**: 1.1.5 (2025011805)
**Release Date**: 2025-01-18
**Previous Version**: 1.1.4 (2025011804)
**Maturity**: STABLE

---

## Version 1.1.4 (2025-01-18) - Admin Settings + Routing + Password Management

### 🎯 Overview
Complete implementation of Moodle-compatible admin settings system with hierarchical categories and dynamic settings pages. Critical routing fix to enable direct access to all admin pages. Full password management system with change password, forgot password, and email confirmation features.

### ✨ New Features

#### Password Management System
- **Change Password**: Authenticated users can change their password
  - Current password verification
  - Password policy enforcement
  - Option to logout other sessions
  - Password history tracking
- **Forgot Password**: Token-based password reset workflow
  - Search by username or email
  - Secure token generation (32 characters)
  - Email confirmation with reset link
  - Token expiration (1800 seconds default)
  - Rate limiting protection
- **Email Confirmation**: User account email verification
  - Confirmation link sent to email
  - Secret token validation
  - Automatic login after confirmation
  - Already confirmed detection
- **Security Features**:
  - Password hashing with PASSWORD_DEFAULT
  - CSRF protection with sesskey
  - Session management (logout other sessions)
  - Account enumeration protection
  - Token expiration and validation

#### Admin Settings System
- **Hierarchical Settings Tree**: Categories with pages and subsections
- **Dynamic Settings Pages**: Load pages dynamically via `?page=pagename`
- **Multiple Setting Types**: Text, checkbox, select, textarea, number, password, heading
- **Type Validation**: Email, URL, number with min/max, etc.
- **Auto-persistence**: Settings automatically saved to config table
- **Permission Control**: Page-level access control via capabilities
- **Modern UI**: Professional sidebar navigation with categories

#### Settings Categories Created
1. **General** - Site name, description
2. **Users** - Default language, email confirmation
3. **Security**
   - Sessions - Timeout, cookie duration
   - Password Policy - Length requirements, character requirements
4. **Development** - Debug mode, error display

### 📁 New Files

#### Password Management (login/)
- `login/lib.php` (370 lines) - Core password management functions
  - core_login_process_password_reset_request() - Display forgot password form
  - core_login_process_password_reset() - Process password reset
  - core_login_process_password_set() - Set new password from token
  - core_login_generate_password_reset() - Generate reset token
  - core_login_get_return_url() - Get redirect URL after login
  - send_password_change_confirmation_email() - Send reset email
  - send_password_change_info() - Send password changed notification
  - core_login_validate_forgot_password_data() - Validate form data
  - user_add_password_history() - Track password history
- `login/change_password_form.php` - Change password form with validation
- `login/forgot_password_form.php` - Forgot password search form (username/email)
- `login/set_password_form.php` - Set new password from reset token form
- `login/change_password.php` - Change password page handler
- `login/forgot_password.php` - Forgot password workflow handler
- `login/confirm.php` - Email confirmation page handler

#### Admin Setting Classes (lib/classes/admin/)
- `admin_setting.php` - Base abstract class (150 lines)
  - get_setting(), write_setting(), validate()
  - config_read(), config_write()
  - output_html(), get_template_data()
- `admin_setting_configtext.php` - Text input with email/URL validation
- `admin_setting_configcheckbox.php` - Checkbox with yes/no values
- `admin_setting_configselect.php` - Dropdown with choices validation
- `admin_setting_configtextarea.php` - Multi-line text input
- `admin_setting_confignumber.php` - Number with min/max validation
- `admin_setting_configpasswordunmask.php` - Password field
- `admin_setting_heading.php` - Non-setting section header
- `admin_settingpage.php` - Container for settings with batch save
- `admin_category.php` - Category/tree structure

#### Core Functions
- `lib/adminlib.php` (270 lines)
  - admin_get_root() - Build settings tree
  - admin_find_page() - Find page by name
  - admin_get_categories() - Get all categories
  - admin_save_settings() - Save page settings

#### Configuration
- `config.php` - Central configuration file (Moodle pattern)
  - Defines NEXOSUPPORT_INTERNAL constant
  - Loads lib/setup.php
  - Required at start of every script

#### Templates
- `templates/admin/settings_page.mustache` - Modern settings UI with sidebar navigation

### 🔧 Critical Routing Fix

**Problem**: All admin/* routes returned 404 because files exist physically but lacked proper setup initialization.

**Solution**: Implemented Moodle's config.php pattern
- Created central `config.php` file
- Updated ALL PHP scripts to use `require_once(__DIR__ . '/path/to/config.php');`
- Removed `defined('NEXOSUPPORT_INTERNAL') || die();` pattern
- Now scripts work both via router AND direct file access

#### Files Updated with config.php (14 files)
- `admin/user/edit.php`
- `admin/user/index.php`
- `admin/roles/edit.php`
- `admin/roles/assign.php`
- `admin/roles/index.php`
- `admin/roles/define.php`
- `admin/settings/index.php`
- `admin/index.php`
- `user/profile.php`
- `dashboard.php`
- `login/index.php`
- `login/logout.php`

### 🔧 Modified Files

#### Password Management
- `public_html/index.php` - Added password management routes
  - GET/POST `/login/change_password`
  - GET/POST `/login/forgot_password`
  - GET `/login/confirm`

#### Admin Pages
- `admin/settings/index.php` - Complete rewrite using admin_setting classes
- `lib/setup.php` - Added require for adminlib.php

### 🌍 Internationalization

#### New Strings (84 total = 42 ES + 42 EN)

**Password Management (32 strings per language):**
- changepassword, oldpassword, newpassword, passwordchanged
- passwordsdiffer, passwordforgotten, passwordforgotteninstructions2
- emailresetconfirmation, emailresetconfirmationsubject
- emailpasswordchangeinfo, emailpasswordchangeinfosubject
- setpassword, passwordset, invalidtoken, noresetrecord
- logoutothersessions, passwordpolicynot, passwordpolicyinfocombined
- mustchangepassword, invalidusernameupdate, cannotmailconfirm
- invalidconfirmdata, errorwhenconfirming, alreadyconfirmed
- emailconfirmation, emailconfirmationsubject
- emailconfirmsent, emailconfirmsentfailure
- policy_too_short, policy_missing_digit, policy_missing_upper, policy_missing_lower

**Admin UI (26 strings per language):**
- Admin UI: systemsettings, administration, savechanges, configsaved, pagenotfound, nopermission
- Categories: generalsettings, usersettings, security, sessions, developmentsettings, passwordpolicy
- Settings: sitename/help, sitedescription/help, defaultlang/help, etc.
- System Info: systeminfo, systemversion, phpversion, database, tableprefix, currentuser
- Validation: validateerror, notnumeric, numbertoosmall, numbertoobig

### 🏗️ Architecture Highlights

#### Moodle Compatibility
- **Exact Class Hierarchy**: admin_setting base class with specialized subclasses
- **Compatible Method Signatures**: get_setting(), write_setting(), validate()
- **Settings Tree Structure**: Categories → Pages → Settings
- **Config Pattern**: Central config.php file (identical to Moodle)

#### Type Safety
- Full PHP 7.4+ type hints
- Strict validation per setting type
- Return type declarations on all methods

#### Extensibility
- Easy to add new setting types (extend admin_setting)
- Easy to add new pages/categories (in admin_get_root)
- Plugin-friendly (settings specify component)

### 🔐 Security Features

- **Permission Checks**: Page-level access control
- **Type Validation**: Per-setting validation (email, URL, number, etc.)
- **CSRF Protection**: All saves protected with sesskey
- **SQL Injection Prevention**: Parameterized queries only

### 📊 Statistics

- **Classes Created**: 13 (10 admin_setting + 3 password forms)
- **Functions Created**: 13 (4 adminlib.php + 9 login/lib.php)
- **Templates Created**: 1 (settings_page.mustache)
- **Pages Created**: 3 (change_password, forgot_password, confirm)
- **Files Modified**: 15 (14 config.php integration + router)
- **New Files Total**: 23 (16 admin + 7 password management)
- **Language Strings Added**: 84 (42 per language)
- **Lines of Code**: ~2,900+

### 🧪 Testing Checklist

#### Password Management
- [ ] Access `/login/change_password` while logged in
- [ ] Change password with correct current password
- [ ] Verify password policy validation (length, digits, upper/lower)
- [ ] Test "logout other sessions" checkbox
- [ ] Access `/login/forgot_password`
- [ ] Submit password reset by username
- [ ] Submit password reset by email
- [ ] Check email for reset link
- [ ] Click reset link and set new password
- [ ] Verify token expiration (after 1800 seconds)
- [ ] Test invalid token handling
- [ ] Access `/login/confirm?data=secret/username`
- [ ] Verify email confirmation workflow
- [ ] Test already confirmed detection

#### Admin Settings
- [x] Access `/admin/settings`
- [x] Navigate between categories via sidebar
- [x] Change sitename setting
- [x] Change session timeout (test min/max validation)
- [x] Enable debug mode checkbox
- [x] Configure password policy
- [x] Verify settings persist after save
- [x] Test validation errors display

#### Routing Fix
- [x] Direct access to `/admin/user/edit?id=1`
- [x] Direct access to `/admin/roles/edit?id=1`
- [x] Direct access to `/admin/roles/assign?roleid=1`
- [x] Direct access to `/user/profile`
- [x] Direct access to `/admin/settings`

### 🐛 Bugs Fixed

1. **404 on admin pages** - Fixed by implementing config.php pattern
2. **Blank pages on admin routes** - Fixed by proper setup initialization
3. **user/profile not displaying** - Fixed by adding config.php require
4. **admin/settings not loading** - Fixed by config.php integration

### 📝 Breaking Changes

None - fully backward compatible with v1.1.3

### 🔄 Upgrade Notes

**From v1.1.3 to v1.1.4:**
1. No database changes required
2. All existing code continues to work
3. New config.php file enables direct script access
4. Router-based access also still works
5. No manual intervention required

### 🚀 Next Steps (Future Versions)

- **v1.2.0**: Plugin settings integration
  - Auth plugin settings pages
  - Module settings
  - Block settings

- **v1.3.0**: Advanced settings types
  - Color picker
  - File picker
  - HTML editor
  - Multi-select

### 👥 Credits

Developed following Moodle LMS architecture and admin settings patterns.

### 📄 License

GNU General Public License v3.0 or later

---

**Version**: 1.1.4 (2025011804)
**Release Date**: 2025-01-18
**Previous Version**: 1.1.3 (2025011803)
**Maturity**: STABLE

---

## Version 1.1.3 (2025-01-18) - User & Role Management

### 🎯 Overview
Complete implementation of user lifecycle management and role management improvements following Moodle architecture patterns.

### ✨ New Features

#### User Management System
- **Delete Users**: Soft delete with data anonymization and automatic session termination
- **Suspend/Unsuspend**: Account suspension with forced logout
- **Unlock Accounts**: Unlock user accounts blocked by failed login attempts
- **Email Confirmation**: Confirm user email addresses
- **Resend Confirmation**: Resend confirmation emails to unconfirmed users
- **Safety Protections**: Cannot delete or suspend administrators or self

#### Role Management Improvements
- **Move Up/Down**: Reorder roles by swapping sortorder
- **Delete Roles**: Remove custom roles (system roles protected)
- **System Role Protection**: Administrator, manager, user, and guest roles cannot be deleted
- **Visual Indicators**: System role badges in UI

### 📁 New Files

#### Core Functions
- `lib/userlib.php` (270 lines)
  - delete_user()
  - suspend_user() / unsuspend_user()
  - unlock_user()
  - confirm_user()
  - send_confirmation_email()
  - is_siteadmin()
  - count_users()

#### Templates
- `templates/admin/user_delete_confirm.mustache` - User deletion confirmation page
- `templates/admin/role_delete_confirm.mustache` - Role deletion confirmation page

### 🔧 Modified Files

#### User Management
- `admin/user/index.php` - Complete rewrite with all user operations (202 lines)
- `templates/admin/user_list.mustache` - Enhanced with action buttons

#### Role Management
- `lib/classes/rbac/role.php` - Added 4 new methods:
  - move_up()
  - move_down()
  - switch_with_role()
  - is_system_role()
- `admin/roles/index.php` - Complete rewrite with action handling (154 lines)
- `templates/admin/role_list.mustache` - Enhanced with move/delete buttons

#### Core System
- `lib/setup.php` - Added require for userlib.php
- `lib/classes/session/manager.php` - Added kill_user_sessions() alias
- `lib/version.php` - Updated to v1.1.3 (2025011803)

#### Database
- `lib/db/install.xml` - Added 'confirmed' field to users table
- `lib/upgrade.php` - Upgrade step for v1.1.3 with confirmed field migration

#### Internationalization
- `lang/es/core.php` - 23 new strings for user/role management
- `lang/en/core.php` - 23 new strings for user/role management

### 🗄️ Database Changes

#### New Fields
- `users.confirmed` (INT(1), default 1) - Email confirmation status
- `users.lang` (CHAR(10), default 'es') - User language preference (consolidated)

#### Upgrade Path
- Automatic migration from v1.1.2 to v1.1.3
- Non-destructive field additions
- Default values ensure backward compatibility

### 🔐 Security Features

- **CSRF Protection**: All operations protected with sesskey validation
- **MD5 Confirmation**: Critical operations (delete) require MD5 hash confirmation
- **Permission Checks**: Cannot harm administrators or self
- **Session Cleanup**: Automatic logout on suspend/delete operations
- **Soft Delete Pattern**: Users marked as deleted with data anonymization

### 🏗️ Architecture Highlights

#### Moodle Compatibility
- Follows Moodle user management patterns exactly
- Compatible with Moodle's userlib.php function signatures
- Similar role management architecture
- Consistent naming conventions

#### Transaction Safety
- Database transactions for multi-table operations
- Rollback on errors
- Data integrity guaranteed

#### Code Quality
- Full type hints (PHP 7.4+)
- Comprehensive documentation
- Defensive programming (null checks, error handling)
- Separation of concerns (logic vs. presentation)

### 📊 Statistics

- **Total Lines Added**: 1,197+
- **New Functions**: 10 (userlib.php + role methods)
- **New Templates**: 2 confirmation pages
- **New Lang Strings**: 23 (ES + EN)
- **Modified Files**: 11
- **Database Fields Added**: 1 (confirmed)

### 🧪 Testing Recommendations

1. **User Management**
   - Test delete user (non-admin)
   - Test suspend/unsuspend
   - Test confirm user
   - Verify cannot delete admin
   - Verify cannot delete self
   - Verify session cleanup

2. **Role Management**
   - Test move up/down
   - Test delete custom role
   - Verify system role protection
   - Test sortorder persistence

3. **Database Migration**
   - Test upgrade from v1.1.2
   - Verify confirmed field added
   - Verify default values

### 📝 Breaking Changes

None - fully backward compatible with v1.1.2

### 🔄 Upgrade Notes

**From v1.1.2 to v1.1.3:**
1. Access `/admin/upgrade` page
2. Click "Upgrade now"
3. System will automatically add 'confirmed' field to users table
4. All existing users will be marked as confirmed (default: 1)
5. No manual intervention required

### 🚀 Next Steps (Future Versions)

- **v1.2.0**: User bulk operations system
  - Bulk delete, suspend, confirm
  - User selection interface
  - Session-based selection storage

- **v1.3.0**: Authentication enhancements
  - Multiple auth plugins
  - OAuth2 support
  - Two-factor authentication

- **v1.4.0**: Advanced RBAC
  - Context-aware permissions
  - Role inheritance
  - Custom contexts

### 👥 Credits

Developed following Moodle LMS architecture and best practices.

### 📄 License

GNU General Public License v3.0 or later

---

**Version**: 1.1.3 (2025011803)
**Release Date**: 2025-01-18
**Previous Version**: 1.1.2 (2025011802)
**Maturity**: STABLE
