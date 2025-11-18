# NexoSupport Changelog

## Version 1.1.4 (2025-01-18) - Admin Settings System + Routing Fix

### 🎯 Overview
Complete implementation of Moodle-compatible admin settings system with hierarchical categories and dynamic settings pages. Critical routing fix to enable direct access to all admin pages.

### ✨ New Features

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

#### Admin Pages
- `admin/settings/index.php` - Complete rewrite using admin_setting classes
- `lib/setup.php` - Added require for adminlib.php

### 🌍 Internationalization

#### New Strings (52 total = 26 ES + 26 EN)
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

- **Classes Created**: 10 (admin_setting hierarchy)
- **Functions Created**: 4 (adminlib.php)
- **Templates Created**: 1 (settings_page.mustache)
- **Files Modified**: 14 (config.php integration)
- **New Files Total**: 16
- **Language Strings Added**: 52 (26 per language)
- **Lines of Code**: ~2,100+

### 🧪 Testing Checklist

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
