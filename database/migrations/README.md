# Database Migrations

## Current Migration: Create login_attempts Table

The `login_attempts` table was missing from the original schema, causing authentication errors.

### Option 1: Run Migration Script (Recommended)

Run the PHP migration script from your MAMP environment:

```bash
cd C:\MAMP\htdocs\NexoSupport
php database\migrations\run-migration.php
```

This will:
- Connect to your database automatically
- Create the `login_attempts` table with the correct prefix
- Verify the table was created successfully
- Show the table structure

### Option 2: Import SQL Manually

If you prefer to use phpMyAdmin:

1. Open phpMyAdmin at: http://localhost/phpMyAdmin
2. Select your database: `nexosupport`
3. Go to the "SQL" tab
4. Open the file: `database/migrations/001_create_login_attempts_table.sql`
5. **IMPORTANT**: Make sure to replace `ndgf_` with your actual table prefix if different
6. Click "Go" to execute

### Table Structure

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

### What This Table Does

The `login_attempts` table tracks all authentication attempts (successful and failed) for:
- Security auditing
- Failed login tracking
- Account lockout protection
- IP address monitoring
- Suspicious activity detection

### After Running Migration

Once the table is created, you should be able to login without the database error.

### Troubleshooting

**Error: "Access denied"**
- Make sure your .env file has correct database credentials
- Check that your MySQL server is running

**Error: "Table already exists"**
- That's fine! The migration uses `CREATE TABLE IF NOT EXISTS`
- The table won't be recreated if it already exists

**Error: "Unknown database"**
- Make sure your database exists and is properly configured in .env

### Verifying Migration

After running the migration, verify it worked:

```sql
SHOW TABLES LIKE '%login_attempts';
DESCRIBE ndgf_login_attempts;
```

You should see the table listed and its structure displayed.
