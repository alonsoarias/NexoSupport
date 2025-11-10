# Authentication Debugging Guide

## CRITICAL: Missing Table Fix Required

**⚠️ IMPORTANT: You must run the database migration first!**

The `login_attempts` table is missing from your database, which causes authentication to crash.

### Run This First:

```bash
cd C:\MAMP\htdocs\NexoSupport
php database\migrations\run-migration.php
```

**OR** import the SQL file manually via phpMyAdmin:
- File: `database/migrations/001_create_login_attempts_table.sql`
- See: `database/migrations/README.md` for detailed instructions

After running the migration, continue with the debugging steps below.

---

## Current Issue
Login fails with `auth.invalid_credentials` error or database table errors.

## Debugging Steps Added

I've added comprehensive debugging throughout the authentication system. All debug messages are logged using `error_log()` and will appear in your PHP error log.

### 1. Location of PHP Error Log

On MAMP (Windows):
```
C:\MAMP\logs\php_error.log
```

### 2. Debug Messages to Look For

The authentication flow now logs every step with prefixes:

#### **[AuthController]** - HTTP Request Processing
```
[AuthController] ======== LOGIN REQUEST START ========
[AuthController] Request method: POST
[AuthController] Request URI: https://nexosupport.localhost.com/login
[AuthController] Parsed body is_array: yes
[AuthController] Parsed body count: 2
[AuthController] Username: 'admin'
[AuthController] Password present: YES
[AuthController] Password length: 10
[AuthController] POST data keys: ["username","password"]
[AuthController] Client IP: 127.0.0.1
[AuthController] Authentication FAILED for username: admin
[AuthController] Authentication SUCCESS, creating session
[AuthController] Session created, redirecting to dashboard
```

#### **[UserManager]** - Database User Lookups
```
[UserManager::getUserByUsername] Looking for username: 'admin'
[UserManager::getUserByUsername] Result: found ID 1
[UserManager::getUserByEmail] Looking for email: 'admin@example.com'
[UserManager::getUserByEmail] Result: found ID 1
```

#### **[AuthService]** - Authentication Logic
```
[AuthService] Attempting authentication for: admin
[AuthService] Account is locked: admin
[AuthService] getUserByUsername result: found user ID 1
[AuthService] getUserByEmail result: found user ID 1
[AuthService] User not found: admin
[AuthService] User found - ID: 1, Username: admin, Status: active
[AuthService] Password hash from DB: $2y$12$...
[AuthService] Password verification: SUCCESS
[AuthService] Password verification: FAILED
[AuthService] Password mismatch for user: admin
[AuthService] Failed attempts: 3
[AuthService] Account locked after 5 failed attempts
[AuthService] User is deleted: admin
[AuthService] User status is not active: suspended
[AuthService] Authentication SUCCESSFUL for user: admin
```

#### **[Helpers::verifyPassword]** - Password Verification Details
```
[Helpers::verifyPassword] Hash algorithm: bcrypt
[Helpers::verifyPassword] Hash (first 30 chars): $2y$12$abcdefghijklmnopqrstuv
[Helpers::verifyPassword] Password length: 10
[Helpers::verifyPassword] Verification result: SUCCESS
[Helpers::verifyPassword] Verification result: FAILED
```

### 3. How to Test

#### Step 1: Set the Correct Password
Run this command on your MAMP environment:
```bash
php tools\test-password.php "Admin.123+"
```

This will:
- Test the password "Admin.123+"
- Update the hash if verification fails
- Show you the verification result

#### Step 2: Try to Login via Web
1. Open: https://nexosupport.localhost.com/login
2. Username: `admin`
3. Password: `Admin.123+`
4. Click Login

#### Step 3: Check the Error Log
Immediately after trying to login, open:
```
C:\MAMP\logs\php_error.log
```

Look for lines starting with:
- `[AuthController]`
- `[AuthService]`
- `[UserManager]`
- `[Helpers::verifyPassword]`

### 4. What to Look For

The log will show you EXACTLY where the authentication fails:

**Scenario A: POST data not received**
```
[AuthController] Parsed body count: 0
[AuthController] Empty credentials
```
→ Problem: Form data not being sent

**Scenario B: User not found in database**
```
[UserManager::getUserByUsername] Result: not found
[UserManager::getUserByEmail] Result: not found
[AuthService] User not found: admin
```
→ Problem: User doesn't exist or database connection issue

**Scenario C: Password hash algorithm mismatch**
```
[Helpers::verifyPassword] Hash algorithm: argon2id
[Helpers::verifyPassword] Verification result: FAILED
```
→ Problem: Password hashed with different algorithm

**Scenario D: Password simply doesn't match**
```
[Helpers::verifyPassword] Hash algorithm: bcrypt
[Helpers::verifyPassword] Verification result: FAILED
[AuthService] Password mismatch for user: admin
```
→ Problem: Wrong password or hash doesn't match

**Scenario E: User account locked**
```
[AuthService] Account is locked: admin
```
→ Problem: Too many failed login attempts

**Scenario F: User inactive or deleted**
```
[AuthService] User status is not active: suspended
[AuthService] User is deleted: admin
```
→ Problem: User status issue

## Next Steps

1. **Run the test-password.php script** with your actual password
2. **Try to login** via the web interface
3. **Copy the relevant log lines** from `C:\MAMP\logs\php_error.log`
4. **Share the log output** so we can identify the exact failure point

## Tools Available

### 1. `tools/test-password.php` - Password Testing
```bash
# Test default password (Admin.123+)
php tools\test-password.php

# Test custom password
php tools\test-password.php "YourPassword123"
```

### 2. `tools/debug-auth.php` - Full Authentication Debug
```bash
php tools\debug-auth.php
```
Shows:
- All users in database
- Password hashes
- Password verification tests
- Creates admin user if missing

### 3. `tools/create-test-user.php` - Create/Update Test User
```bash
php tools\create-test-user.php
```
Creates or updates admin user with password: admin123

## Common Issues

### Issue: "Argon2id" hash algorithm instead of "bcrypt"
**Solution**: Run test-password.php to update the hash
```bash
php tools\test-password.php "Admin.123+"
```

### Issue: Account locked
**Solution**: Check database and reset failed_login_attempts
```sql
UPDATE iser_users SET failed_login_attempts = 0, account_locked_until = NULL WHERE username = 'admin';
```

### Issue: User inactive
**Solution**: Update user status
```sql
UPDATE iser_users SET status = 'active' WHERE username = 'admin';
```

### Issue: POST data not received
**Solution**: Check form field names in login template match 'username' and 'password'

## Expected Successful Login Flow

```
[AuthController] ======== LOGIN REQUEST START ========
[AuthController] Request method: POST
[AuthController] Username: 'admin'
[AuthController] Password present: YES
[AuthController] Password length: 10
[AuthController] POST data keys: ["username","password"]
[AuthController] Client IP: 127.0.0.1
[UserManager::getUserByUsername] Looking for username: 'admin'
[UserManager::getUserByUsername] Result: found ID 1
[AuthService] Attempting authentication for: admin
[AuthService] getUserByUsername result: found user ID 1
[AuthService] User found - ID: 1, Username: admin, Status: active
[AuthService] Password hash from DB: $2y$12$...
[Helpers::verifyPassword] Hash algorithm: bcrypt
[Helpers::verifyPassword] Hash (first 30 chars): $2y$12$...
[Helpers::verifyPassword] Password length: 10
[Helpers::verifyPassword] Verification result: SUCCESS
[AuthService] Password verification: SUCCESS
[AuthService] Authentication SUCCESSFUL for user: admin
[AuthController] Authentication SUCCESS, creating session
[AuthController] Session created, redirecting to dashboard
```

If you see this sequence, authentication is working correctly!
