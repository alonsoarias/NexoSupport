# NexoSupport - Security Guide

## Overview

This document outlines security best practices, hardening procedures, and security features in NexoSupport. Following these guidelines will help protect your installation from common vulnerabilities and attacks.

**Version**: 2.0.0 (Frankenstyle Architecture)
**Last Updated**: 2024-11-16
**Security Level**: Enterprise-grade

---

## Table of Contents

1. [Security Features](#security-features)
2. [Authentication & Authorization](#authentication--authorization)
3. [Multi-Factor Authentication (MFA)](#multi-factor-authentication-mfa)
4. [Data Protection](#data-protection)
5. [Input Validation](#input-validation)
6. [Session Security](#session-security)
7. [File Upload Security](#file-upload-security)
8. [Database Security](#database-security)
9. [Server Hardening](#server-hardening)
10. [Security Headers](#security-headers)
11. [GDPR & Privacy Compliance](#gdpr--privacy-compliance)
12. [Audit Logging](#audit-logging)
13. [Vulnerability Management](#vulnerability-management)
14. [Incident Response](#incident-response)

---

## Security Features

NexoSupport includes enterprise-grade security features:

- **RBAC System**: 43 granular capabilities across 5 roles
- **Multi-Factor Authentication**: Email codes and IP range restrictions
- **GDPR Compliance**: Data export, deletion, anonymization
- **Session Security**: Secure cookies, CSRF protection, session fixation prevention
- **Input Validation**: XSS, SQL injection, LDAP injection prevention
- **File Upload Security**: MIME validation, extension whitelist, virus scanning
- **Audit Logging**: Complete audit trail for all sensitive operations
- **Plugin Security**: Addon validator prevents malicious code execution
- **Rate Limiting**: Brute-force attack prevention
- **Password Security**: Bcrypt hashing, strength requirements, password history

---

## Authentication & Authorization

### Password Requirements

**Default password policy** (`lib/config.php`):

```php
define('PASSWORD_MIN_LENGTH', 12);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_DIGITS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);
define('PASSWORD_HISTORY_COUNT', 5);  // Prevent reuse of last 5 passwords
define('PASSWORD_EXPIRY_DAYS', 90);
```

**Enforce strong passwords**:

```php
// In lib/classes/auth/password_validator.php
public static function validate(string $password): array
{
    $errors = [];

    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
    }

    if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }

    if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }

    if (PASSWORD_REQUIRE_DIGITS && !preg_match('/\d/', $password)) {
        $errors[] = 'Password must contain at least one digit';
    }

    if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }

    return $errors;
}
```

### Password Storage

NexoSupport uses **bcrypt** for password hashing:

```php
// Never use MD5 or SHA1 for passwords!
// Always use password_hash() with PASSWORD_BCRYPT or PASSWORD_ARGON2ID

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verification
if (password_verify($password, $hash)) {
    // Password is correct
}

// Rehash if algorithm changes
if (password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12])) {
    $new_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    // Update database
}
```

### RBAC (Role-Based Access Control)

**Five default roles**:

1. **Admin**: Full system access (all 43 capabilities)
2. **Manager**: User management, reporting (22 capabilities)
3. **Agent**: Ticket management, communication (15 capabilities)
4. **User**: Basic access (8 capabilities)
5. **Guest**: Read-only access (3 capabilities)

**Key capabilities**:

```php
// System administration
'moodle/site:config'              // Full system configuration
'moodle/site:backup'              // System backups
'moodle/site:restore'             // System restore

// User management
'moodle/user:create'              // Create users
'moodle/user:delete'              // Delete users
'moodle/user:viewalldetails'      // View all user information

// Role assignment
'moodle/role:assign'              // Assign roles
'moodle/role:override'            // Override permissions
'moodle/role:manage'              // Manage roles

// MFA management
'tool/mfa:manage'                 // Configure MFA
'tool/mfa:viewlogs'               // View MFA logs
```

**Permission checks** (use everywhere):

```php
// In admin/tool/mfa/index.php
require_capability('tool/mfa:manage');

// Or with custom error message
if (!has_capability('moodle/site:config')) {
    throw new AccessDeniedException('System configuration access required');
}
```

---

## Multi-Factor Authentication (MFA)

### Email Factor

**Security features**:

- **Bcrypt hashed codes**: Codes are never stored in plain text
- **10-minute expiration**: Codes auto-expire after 10 minutes
- **3 attempt limit**: Account locked after 3 failed attempts
- **Rate limiting**: Max 5 codes per hour per user
- **IP logging**: All MFA attempts are logged with IP address

**Configuration** (`admin/tool/mfa/config.php`):

```php
define('MFA_CODE_LENGTH', 6);
define('MFA_CODE_EXPIRY', 600);        // 10 minutes
define('MFA_MAX_ATTEMPTS', 3);
define('MFA_RATE_LIMIT', 5);           // 5 codes per hour
define('MFA_LOCKOUT_DURATION', 1800);  // 30 minutes
```

**Usage example**:

```php
use ISER\Admin\Tool\MFA\EmailFactor;

$email_factor = new EmailFactor();

// Send code
$result = $email_factor->send_code($user_id, $email);
if ($result['success']) {
    // Code sent successfully
}

// Verify code
if ($email_factor->verify_code($user_id, $code)) {
    // Code is valid
} else {
    // Code is invalid or expired
}
```

### IP Range Factor

**Security features**:

- **CIDR validation**: Supports IPv4 and IPv6 CIDR notation
- **Whitelist and blacklist**: Allow or deny specific IP ranges
- **Spoofing prevention**: Uses `REMOTE_ADDR` only (no proxy headers)
- **Access logging**: All IP checks are logged

**Usage example**:

```php
use ISER\Admin\Tool\MFA\IPRangeFactor;

$ip_factor = new IPRangeFactor();

// Add whitelist rule
$ip_factor->add_range(
    user_id: $user_id,
    ip_range: '192.168.1.0/24',
    range_type: 'whitelist',
    description: 'Office network'
);

// Check access
if ($ip_factor->check_ip($user_id, $_SERVER['REMOTE_ADDR'])) {
    // IP is allowed
} else {
    // IP is blocked
}
```

### Trusted Devices

**Features**:

- **Device fingerprinting**: User-agent + IP hash
- **30-day expiration**: Trusted devices expire after 30 days
- **Revocation**: Users can revoke trusted devices at any time

---

## Data Protection

### Encryption

**Sensitive data encryption**:

```php
// Use OpenSSL for encryption
define('ENCRYPTION_METHOD', 'AES-256-CBC');
define('ENCRYPTION_KEY', 'YOUR_32_BYTE_ENCRYPTION_KEY_HERE');  // Store in config.php

function encrypt_data(string $data): string
{
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD));
    $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decrypt_data(string $data): string
{
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
}
```

**What to encrypt**:

- Credit card information (if applicable)
- Social security numbers
- API keys and tokens
- Personal health information
- Financial data

**What NOT to encrypt**:

- Passwords (use bcrypt instead)
- Data that needs to be searchable
- High-volume data (performance impact)

### Data Anonymization

**GDPR-compliant anonymization** (`admin/tool/dataprivacy/classes/data_eraser.php`):

```php
public function anonymize_user(int $user_id): array
{
    return [
        'email' => 'deleted_' . $user_id . '@example.com',
        'firstname' => 'Deleted',
        'lastname' => 'User',
        'phone' => '',
        'address' => '',
        'city' => '',
        'country' => '',
        'ip_address' => '0.0.0.0',
    ];
}
```

---

## Input Validation

### XSS Prevention

**Always escape output**:

```php
// HTML context
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// JavaScript context
echo json_encode($user_input, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

// URL context
echo urlencode($user_input);

// Attribute context
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

**Content Security Policy** (prevent inline scripts):

```php
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
```

### SQL Injection Prevention

**Always use prepared statements**:

```php
// NEVER do this:
$query = "SELECT * FROM users WHERE email = '$email'";  // ❌ VULNERABLE

// ALWAYS use PDO prepared statements:
$stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);  // ✅ SAFE
```

### LDAP Injection Prevention

**Escape LDAP special characters**:

```php
function ldap_escape(string $value): string
{
    $replacements = [
        '\\' => '\5c',
        '*'  => '\2a',
        '('  => '\28',
        ')'  => '\29',
        "\0" => '\00',
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $value);
}
```

### Command Injection Prevention

**Avoid shell execution**:

```php
// NEVER do this:
exec("convert $input_file $output_file");  // ❌ VULNERABLE

// If you MUST use shell commands, escape arguments:
$input_file = escapeshellarg($input_file);
$output_file = escapeshellarg($output_file);
exec("convert $input_file $output_file");  // ✅ SAFER

// Better: Use native PHP functions
// imagick extension, GD library, etc.
```

---

## Session Security

### Session Configuration

**Secure session settings** (`lib/config.php`):

```php
// Session security
define('SESSION_SECURE', true);          // HTTPS only
define('SESSION_HTTPONLY', true);        // Prevent JavaScript access
define('SESSION_SAMESITE', 'Strict');    // CSRF protection
define('SESSION_TIMEOUT', 1800);         // 30 minutes
define('SESSION_REGENERATE_INTERVAL', 300);  // Regenerate ID every 5 minutes

// Session initialization
ini_set('session.cookie_secure', SESSION_SECURE);
ini_set('session.cookie_httponly', SESSION_HTTPONLY);
ini_set('session.cookie_samesite', SESSION_SAMESITE);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
```

### CSRF Protection

**Token-based CSRF protection**:

```php
// Generate token
function generate_csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate token
function validate_csrf_token(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// In forms
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <!-- form fields -->
</form>

// In handlers
if (!validate_csrf_token($_POST['csrf_token'])) {
    die('CSRF token validation failed');
}
```

### Session Fixation Prevention

**Regenerate session ID on login**:

```php
// After successful login
session_regenerate_id(true);
$_SESSION['user_id'] = $user_id;
$_SESSION['login_time'] = time();
```

---

## File Upload Security

### MIME Type Validation

**Validate file types** (`lib/classes/upload/file_validator.php`):

```php
public static function validate_upload(array $file): array
{
    // Whitelist approach
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

    // Get MIME type from file content (not from $_FILES)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // Get extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Validate
    if (!in_array($mime_type, $allowed_types)) {
        return ['error' => 'Invalid file type'];
    }

    if (!in_array($extension, $allowed_extensions)) {
        return ['error' => 'Invalid file extension'];
    }

    // Additional checks
    if ($file['size'] > 10 * 1024 * 1024) {  // 10MB
        return ['error' => 'File too large'];
    }

    return ['success' => true];
}
```

### Prevent PHP Execution in Uploads

**Apache** (`.htaccess` in uploads directory):

```apache
# Disable PHP execution
php_flag engine off

# Deny access to .php files
<FilesMatch "\.php$">
    Require all denied
</FilesMatch>
```

**Nginx**:

```nginx
location ^~ /uploads/ {
    location ~ \.php$ {
        deny all;
    }
}
```

### File Storage

**Store files outside web root**:

```php
// Good: Outside web root
define('UPLOADS_DIR', '/var/nexosupport-data/uploads');

// Bad: Inside web root
// define('UPLOADS_DIR', '/var/www/nexosupport/uploads');  // ❌
```

---

## Database Security

### Connection Security

**Use SSL for database connections**:

```php
$options = [
    PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca-cert.pem',
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
];

$db = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
    DB_USER,
    DB_PASSWORD,
    $options
);
```

### Least Privilege Principle

**Grant only necessary permissions**:

```sql
-- Application user (read/write to application tables only)
CREATE USER 'nexosupport_app'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE ON nexosupport.* TO 'nexosupport_app'@'localhost';

-- Backup user (read-only)
CREATE USER 'nexosupport_backup'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT SELECT, LOCK TABLES ON nexosupport.* TO 'nexosupport_backup'@'localhost';

-- Admin user (full access, use sparingly)
CREATE USER 'nexosupport_admin'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON nexosupport.* TO 'nexosupport_admin'@'localhost';

FLUSH PRIVILEGES;
```

### Database Encryption

**Enable encryption at rest** (MySQL 8.0+):

```sql
-- Enable encryption for tablespace
ALTER TABLESPACE nexosupport ENCRYPTION = 'Y';

-- Create encrypted table
CREATE TABLE sensitive_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    data VARCHAR(255)
) ENCRYPTION = 'Y';
```

---

## Server Hardening

### PHP Hardening

**Disable dangerous functions** (`/etc/php/8.0/apache2/php.ini`):

```ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
allow_url_fopen = Off
allow_url_include = Off
```

### Apache Hardening

**Security configuration**:

```apache
# Hide Apache version
ServerTokens Prod
ServerSignature Off

# Disable directory listing
Options -Indexes

# Prevent MIME sniffing
Header set X-Content-Type-Options "nosniff"

# Limit request size
LimitRequestBody 52428800  # 50MB

# Timeout settings
Timeout 60
KeepAliveTimeout 5
```

### Firewall Rules

**UFW (Ubuntu)**:

```bash
# Default policies
ufw default deny incoming
ufw default allow outgoing

# Allow SSH (change port if using non-standard)
ufw allow 22/tcp

# Allow HTTP/HTTPS
ufw allow 80/tcp
ufw allow 443/tcp

# Allow MySQL (only from localhost)
ufw allow from 127.0.0.1 to any port 3306

# Enable firewall
ufw enable
```

---

## Security Headers

**Essential security headers**:

```php
// In lib/setup.php or web server config

// Prevent clickjacking
header('X-Frame-Options: SAMEORIGIN');

// Prevent MIME sniffing
header('X-Content-Type-Options: nosniff');

// XSS protection
header('X-XSS-Protection: 1; mode=block');

// Referrer policy
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'self';");

// HSTS (HTTP Strict Transport Security)
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

// Permissions Policy
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
```

---

## GDPR & Privacy Compliance

### Data Subject Rights

NexoSupport supports all GDPR data subject rights:

1. **Right of Access**: Data export via `admin/tool/dataprivacy/`
2. **Right to Rectification**: User profile editing
3. **Right to Erasure**: Data deletion and anonymization
4. **Right to Restriction**: Temporary account suspension
5. **Right to Data Portability**: JSON/XML export
6. **Right to Object**: Opt-out mechanisms
7. **Right to Automated Decision-Making**: No automated profiling

### Data Retention Policies

**Five retention categories** (`admin/tool/dataprivacy/config.php`):

```php
define('RETENTION_PERMANENT', 0);      // Never delete
define('RETENTION_LONG', 2555);        // 7 years
define('RETENTION_MEDIUM', 1825);      // 5 years
define('RETENTION_SHORT', 365);        // 1 year
define('RETENTION_MINIMAL', 90);       // 90 days
```

### Privacy by Design

- **Data minimization**: Collect only necessary data
- **Purpose limitation**: Use data only for stated purposes
- **Storage limitation**: Delete data when no longer needed
- **Accuracy**: Allow users to update their information
- **Integrity and confidentiality**: Encryption and access controls

---

## Audit Logging

### What to Log

**Security events**:

- Login attempts (successful and failed)
- Logout events
- Password changes
- MFA enrollment/removal
- Permission changes
- Data exports
- Data deletions
- Configuration changes
- Plugin installations

**Example logging** (`lib/classes/audit/audit_logger.php`):

```php
public static function log_event(array $event): void
{
    global $db;

    $stmt = $db->prepare("
        INSERT INTO audit_log (user_id, event_type, ip_address, user_agent, details, created_at)
        VALUES (:user_id, :event_type, :ip_address, :user_agent, :details, NOW())
    ");

    $stmt->execute([
        'user_id' => $event['user_id'] ?? null,
        'event_type' => $event['type'],
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'details' => json_encode($event['details']),
    ]);
}
```

### Log Retention

**Compliance requirements**:

- Security logs: 1 year minimum
- Access logs: 90 days
- Error logs: 30 days
- Audit logs: 7 years (depends on industry)

---

## Vulnerability Management

### Security Updates

**Keep software updated**:

```bash
# System packages
apt-get update && apt-get upgrade

# PHP
apt-get install php8.0

# NexoSupport
git pull origin main
composer update
```

### Security Scanning

**Automated vulnerability scanning**:

```bash
# PHP dependencies (if using Composer)
composer audit

# Server security audit
lynis audit system

# Web application scanner
nikto -h https://support.yourdomain.com
```

### Penetration Testing

**Annual penetration testing recommended**:

- OWASP Top 10 testing
- Authentication bypass attempts
- Authorization testing
- Session management testing
- Input validation testing
- Business logic testing

---

## Incident Response

### Incident Response Plan

**Steps for security incidents**:

1. **Detect**: Monitor logs, health checks, alerts
2. **Contain**: Isolate affected systems, revoke access
3. **Eradicate**: Remove malware, patch vulnerabilities
4. **Recover**: Restore from backups, verify integrity
5. **Learn**: Post-incident review, update procedures

### Emergency Contacts

**Maintain contact list**:

- System administrator
- Database administrator
- Security team
- Legal team (for data breaches)
- Hosting provider support

### Data Breach Response

**EU GDPR requirements**:

- Notify supervisory authority within 72 hours
- Notify affected users without undue delay
- Document breach details and response actions
- Assess risk to individuals' rights and freedoms

---

## Security Checklist

**Pre-production security checklist**:

- [ ] All passwords meet complexity requirements
- [ ] MFA enabled for all administrators
- [ ] HTTPS enabled with valid SSL certificate
- [ ] Security headers configured
- [ ] File upload restrictions in place
- [ ] Database user has minimal privileges
- [ ] Dangerous PHP functions disabled
- [ ] Error display disabled in production
- [ ] Audit logging enabled
- [ ] Backup strategy implemented
- [ ] Firewall rules configured
- [ ] Server hardening complete
- [ ] Security scanning performed
- [ ] Incident response plan documented
- [ ] Privacy policy published
- [ ] Data retention policies configured

---

## Additional Resources

- **OWASP Top 10**: https://owasp.org/www-project-top-ten/
- **PHP Security Cheat Sheet**: https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html
- **GDPR Official Text**: https://gdpr-info.eu/
- **CWE Top 25**: https://cwe.mitre.org/top25/
- **NIST Cybersecurity Framework**: https://www.nist.gov/cyberframework

---

**Last Review Date**: 2024-11-16
**Next Review Due**: 2025-02-16 (Quarterly review recommended)
