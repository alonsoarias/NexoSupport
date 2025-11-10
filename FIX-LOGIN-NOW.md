# 🔧 FIX LOGIN NOW - Quick Start Guide

## The Problem

Your authentication system was crashing with this error:
```
Table 'nexosupport.ndgf_login_attempts' doesn't exist
```

The `login_attempts` table was missing from your database, causing all login attempts to fail.

## The Solution (3 Simple Steps)

### Step 1: Run the Migration

Open your terminal (Command Prompt or PowerShell) and run:

```bash
cd C:\MAMP\htdocs\NexoSupport
php database\migrations\run-migration.php
```

**Expected output:**
```
=============================================================
  MIGRATION: Create login_attempts Table
=============================================================

Reading migration file: 001_create_login_attempts_table.sql
Creating table: login_attempts

Full table name: ndgf_login_attempts

✓ Migration completed successfully!

Table 'ndgf_login_attempts' has been created.

✓ Table verified in database.
```

### Step 2: Set Your Password

Run the password test script with your actual password:

```bash
php tools\test-password.php "Admin.123+"
```

This will update your admin user's password hash to use bcrypt.

### Step 3: Try to Login

1. Go to: https://nexosupport.localhost.com/login
2. Username: `admin`
3. Password: `Admin.123+`
4. Click Login

**You should now be able to login successfully!**

## Alternative: Manual SQL Import

If you prefer to use phpMyAdmin:

1. Open phpMyAdmin: http://localhost/phpMyAdmin
2. Select database: `nexosupport`
3. Click "SQL" tab
4. Copy and paste this SQL:

```sql
CREATE TABLE IF NOT EXISTS `ndgf_login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `attempted_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_username` (`username`),
  INDEX `idx_ip_address` (`ip_address`),
  INDEX `idx_attempted_at` (`attempted_at`),
  INDEX `idx_success` (`success`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

5. Click "Go"

Then run Step 2 and Step 3 above.

## What This Table Does

The `login_attempts` table is used for:
- **Security**: Track all login attempts
- **Failed login detection**: Identify suspicious activity
- **Account lockout**: Prevent brute force attacks
- **Audit trail**: Monitor who's trying to access the system

## Troubleshooting

### Error: "vendor/autoload.php not found"
```bash
cd C:\MAMP\htdocs\NexoSupport
composer install
```

### Error: "Access denied for user"
- Check your `.env` file has correct database credentials
- Make sure MySQL is running in MAMP

### Error: "Table already exists"
- That's fine! It means the table was already created
- Skip to Step 2

### Login still fails after migration
1. Check error log: `C:\MAMP\logs\php_error.log`
2. Look for lines starting with `[AuthController]` or `[AuthService]`
3. Share the log output for further debugging

## Files Added/Modified

- ✅ `database/schema/schema.xml` - Added login_attempts table definition
- ✅ `database/migrations/001_create_login_attempts_table.sql` - SQL migration
- ✅ `database/migrations/run-migration.php` - Automated migration runner
- ✅ `database/migrations/README.md` - Detailed migration instructions
- ✅ `DEBUGGING-AUTH.md` - Updated with migration requirement

## Need More Help?

See the detailed guides:
- **Migration details**: `database/migrations/README.md`
- **Debugging authentication**: `DEBUGGING-AUTH.md`

## After Successful Login

Once you can login, you should see:
- Dashboard with real statistics
- Your full name displayed
- Real user counts from database
- No more "auth.invalid_credentials" errors

The authentication system will now properly:
- ✓ Track all login attempts
- ✓ Lock accounts after 5 failed attempts
- ✓ Record IP addresses for security
- ✓ Maintain audit trail
- ✓ Allow debugging with comprehensive logs
