# NexoSupport Changelog

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
