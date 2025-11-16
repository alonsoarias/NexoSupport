# NexoSupport - Deployment Guide

## Overview

This guide provides comprehensive instructions for deploying NexoSupport to production environments. NexoSupport is built on a modular Frankenstyle architecture with enterprise-grade security and performance features.

**Version**: 2.0.0 (Frankenstyle Architecture)
**Last Updated**: 2024-11-16
**Target Environments**: Production, Staging, Development

---

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Pre-Deployment Checklist](#pre-deployment-checklist)
3. [Installation Steps](#installation-steps)
4. [Configuration](#configuration)
5. [Database Setup](#database-setup)
6. [File Permissions](#file-permissions)
7. [Web Server Configuration](#web-server-configuration)
8. [SSL/TLS Setup](#ssltls-setup)
9. [Performance Optimization](#performance-optimization)
10. [Post-Deployment Verification](#post-deployment-verification)
11. [Monitoring Setup](#monitoring-setup)
12. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Minimum Requirements

**Server:**
- CPU: 2 cores @ 2.0 GHz
- RAM: 4 GB
- Storage: 20 GB SSD
- Network: 100 Mbps

**Software:**
- PHP 8.0 or higher
- MySQL 8.0 / MariaDB 10.5 or higher
- Web server: Apache 2.4+ or Nginx 1.18+
- SSL certificate (required for production)

**PHP Extensions (Required):**
```
pdo
pdo_mysql
json
mbstring
openssl
zip
curl
xml
gd
intl
fileinfo
```

**PHP Extensions (Recommended):**
```
apcu          # Memory caching
opcache       # Opcode caching
redis         # Distributed caching
imagick       # Advanced image processing
```

### Recommended Production Requirements

**Server:**
- CPU: 4+ cores @ 2.5 GHz
- RAM: 8+ GB
- Storage: 50+ GB SSD (NVMe preferred)
- Network: 1 Gbps

**PHP Configuration:**
```ini
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
max_input_vars = 5000
opcache.enable = 1
opcache.memory_consumption = 128
apcu.enabled = 1
apcu.shm_size = 64M
```

---

## Pre-Deployment Checklist

Before deploying to production, verify:

- [ ] All system requirements are met
- [ ] Database server is accessible and configured
- [ ] SSL certificate is obtained and valid
- [ ] Domain DNS is configured
- [ ] Firewall rules allow HTTP/HTTPS traffic
- [ ] PHP extensions are installed and enabled
- [ ] File upload directories exist with correct permissions
- [ ] Email server (SMTP) is configured for notifications
- [ ] Backup strategy is in place
- [ ] Monitoring tools are ready
- [ ] Development and testing completed successfully

---

## Installation Steps

### Step 1: Download and Extract

```bash
# Clone the repository
git clone https://github.com/alonsoarias/NexoSupport.git
cd NexoSupport

# Or download release
wget https://github.com/alonsoarias/NexoSupport/archive/refs/tags/v2.0.0.tar.gz
tar -xzf v2.0.0.tar.gz
cd NexoSupport-2.0.0
```

### Step 2: Set Up Directory Structure

```bash
# Create required directories
mkdir -p cache logs uploads backups

# Set ownership (replace www-data with your web server user)
sudo chown -R www-data:www-data cache logs uploads backups
```

### Step 3: Install Dependencies

```bash
# If using Composer for any dependencies
composer install --no-dev --optimize-autoloader

# Verify PHP version
php -v

# Verify extensions
php -m | grep -E 'pdo|json|mbstring|openssl|zip'
```

### Step 4: Configure Application

```bash
# Copy configuration template
cp lib/config.sample.php lib/config.php

# Edit configuration
nano lib/config.php
```

**Required configuration in lib/config.php:**

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'nexosupport');
define('DB_USER', 'nexosupport_user');
define('DB_PASSWORD', 'STRONG_PASSWORD_HERE');
define('DB_PREFIX', 'ns_');

// Application Settings
define('SITE_URL', 'https://support.yourdomain.com');
define('SITE_NAME', 'NexoSupport');
define('ADMIN_EMAIL', 'admin@yourdomain.com');

// Security
define('SESSION_SECURE', true);  // HTTPS only
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Strict');

// Paths
define('CACHE_DIR', __DIR__ . '/../cache');
define('LOGS_DIR', __DIR__ . '/../logs');
define('UPLOADS_DIR', __DIR__ . '/../uploads');

// Environment
define('ENVIRONMENT', 'production');  // production, staging, development
define('DEBUG_MODE', false);

// Caching
define('CACHE_ENABLED', true);
define('CACHE_DEFAULT_TTL', 3600);

// Email
define('SMTP_HOST', 'smtp.yourdomain.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@yourdomain.com');
define('SMTP_PASSWORD', 'SMTP_PASSWORD_HERE');
define('SMTP_ENCRYPTION', 'tls');

// MFA Settings
define('MFA_ENABLED', true);
define('MFA_CODE_LENGTH', 6);
define('MFA_CODE_EXPIRY', 600);  // 10 minutes
```

### Step 5: Database Installation

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE nexosupport CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create user
mysql -u root -p -e "CREATE USER 'nexosupport_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';"

# Grant privileges
mysql -u root -p -e "GRANT ALL PRIVILEGES ON nexosupport.* TO 'nexosupport_user'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# Import schema
mysql -u nexosupport_user -p nexosupport < install/schema.sql

# Import initial data
mysql -u nexosupport_user -p nexosupport < install/data.sql
```

### Step 6: Run Installer (Optional)

If using the web-based installer:

```bash
# Navigate to installer
https://support.yourdomain.com/install/

# Follow on-screen instructions
# Delete installer after completion
rm -rf install/
```

---

## Configuration

### Environment-Specific Configuration

**Production (`lib/config.php`):**
```php
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', false);
define('ERROR_REPORTING', E_ERROR | E_WARNING);
define('DISPLAY_ERRORS', false);
define('LOG_ERRORS', true);
```

**Staging:**
```php
define('ENVIRONMENT', 'staging');
define('DEBUG_MODE', true);
define('ERROR_REPORTING', E_ALL);
define('DISPLAY_ERRORS', true);
define('LOG_ERRORS', true);
```

**Development:**
```php
define('ENVIRONMENT', 'development');
define('DEBUG_MODE', true);
define('ERROR_REPORTING', E_ALL);
define('DISPLAY_ERRORS', true);
define('LOG_ERRORS', true);
```

### Component Configuration

Each Frankenstyle component has its own `config.php`:

- `modules/Auth/Manual/config.php` - Manual authentication settings
- `admin/tool/mfa/config.php` - MFA configuration
- `admin/tool/dataprivacy/config.php` - GDPR settings
- `theme/iser/config.php` - Theme customization

### Cache Configuration

Edit `lib/config.php`:

```php
// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_DRIVER', 'apcu');  // memory, apcu, file
define('CACHE_DEFAULT_TTL', 3600);
define('CACHE_PREFIX', 'ns_');

// APCu settings (if using APCu)
define('CACHE_APCU_ENABLED', extension_loaded('apcu'));

// File cache fallback
define('CACHE_FILE_DIR', CACHE_DIR . '/cache');
```

---

## Database Setup

### Schema Installation

The database schema includes:

- **Core tables**: users, sessions, roles, permissions
- **RBAC tables**: role_capabilities, role_assignments, role_overrides
- **MFA tables**: mfa_user_factors, mfa_email_codes, mfa_ip_ranges, mfa_trusted_devices, mfa_access_log
- **Privacy tables**: privacy_requests, privacy_audit_log, privacy_retention_policies, privacy_consent
- **Theme tables**: theme_settings
- **Cache tables**: cache_items (if using database cache)

### Database Maintenance

```sql
-- Optimize tables (run monthly)
OPTIMIZE TABLE ns_users, ns_sessions, ns_mfa_access_log;

-- Clean old sessions (run daily via cron)
DELETE FROM ns_sessions WHERE expires_at < NOW();

-- Clean expired MFA codes (run hourly via cron)
DELETE FROM ns_mfa_email_codes WHERE expires_at < NOW();

-- Archive old audit logs (run monthly)
INSERT INTO ns_privacy_audit_log_archive
SELECT * FROM ns_privacy_audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
DELETE FROM ns_privacy_audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

### Database Indexes

Ensure indexes exist for performance:

```sql
-- Sessions
CREATE INDEX idx_sessions_expires ON ns_sessions(expires_at);
CREATE INDEX idx_sessions_user ON ns_sessions(user_id);

-- MFA
CREATE INDEX idx_mfa_codes_expires ON ns_mfa_email_codes(expires_at);
CREATE INDEX idx_mfa_codes_user ON ns_mfa_email_codes(user_id);
CREATE INDEX idx_mfa_access_created ON ns_mfa_access_log(created_at);

-- Audit logs
CREATE INDEX idx_audit_created ON ns_privacy_audit_log(created_at);
CREATE INDEX idx_audit_user ON ns_privacy_audit_log(user_id);
```

---

## File Permissions

### Linux/Unix Permissions

```bash
# Application root
chmod 755 /var/www/nexosupport

# Writable directories
chmod 770 cache logs uploads backups
chmod 770 theme/*/cache

# Configuration files (read-only for web server)
chmod 640 lib/config.php
chmod 640 */*/config.php

# PHP files
find . -type f -name "*.php" -exec chmod 644 {} \;

# Directories
find . -type d -exec chmod 755 {} \;

# Set ownership
chown -R www-data:www-data /var/www/nexosupport

# Protect sensitive files
chmod 600 lib/config.php
chown root:www-data lib/config.php
```

### SELinux (if enabled)

```bash
# Set SELinux context
semanage fcontext -a -t httpd_sys_content_t "/var/www/nexosupport(/.*)?"
semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/nexosupport/(cache|logs|uploads|backups)(/.*)?"
restorecon -Rv /var/www/nexosupport
```

---

## Web Server Configuration

### Apache 2.4

**VirtualHost configuration** (`/etc/apache2/sites-available/nexosupport.conf`):

```apache
<VirtualHost *:443>
    ServerName support.yourdomain.com
    DocumentRoot /var/www/nexosupport

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/nexosupport.crt
    SSLCertificateKeyFile /etc/ssl/private/nexosupport.key
    SSLCertificateChainFile /etc/ssl/certs/nexosupport-chain.crt

    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';"

    # Directory settings
    <Directory /var/www/nexosupport>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Enable .htaccess
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
        </IfModule>
    </Directory>

    # Protect sensitive directories
    <DirectoryMatch "^/var/www/nexosupport/(lib|install|backups)">
        Require all denied
    </DirectoryMatch>

    # Logging
    ErrorLog ${APACHE_LOG_DIR}/nexosupport-error.log
    CustomLog ${APACHE_LOG_DIR}/nexosupport-access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName support.yourdomain.com
    Redirect permanent / https://support.yourdomain.com/
</VirtualHost>
```

**Enable site and modules:**

```bash
a2enmod ssl rewrite headers
a2ensite nexosupport
systemctl reload apache2
```

### Nginx

**Server block** (`/etc/nginx/sites-available/nexosupport`):

```nginx
server {
    listen 443 ssl http2;
    server_name support.yourdomain.com;
    root /var/www/nexosupport;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/nexosupport.crt;
    ssl_certificate_key /etc/ssl/private/nexosupport.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive files
    location ~ ^/(lib|install|backups|cache|logs) {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Logging
    access_log /var/log/nginx/nexosupport-access.log;
    error_log /var/log/nginx/nexosupport-error.log;
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name support.yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

**Enable site:**

```bash
ln -s /etc/nginx/sites-available/nexosupport /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

---

## SSL/TLS Setup

### Let's Encrypt (Certbot)

```bash
# Install Certbot
apt-get install certbot python3-certbot-apache  # Apache
apt-get install certbot python3-certbot-nginx   # Nginx

# Obtain certificate
certbot --apache -d support.yourdomain.com      # Apache
certbot --nginx -d support.yourdomain.com       # Nginx

# Auto-renewal (cron)
certbot renew --dry-run
```

### Manual Certificate Installation

```bash
# Copy certificate files
cp yourdomain.crt /etc/ssl/certs/nexosupport.crt
cp yourdomain.key /etc/ssl/private/nexosupport.key
cp yourdomain-chain.crt /etc/ssl/certs/nexosupport-chain.crt

# Set permissions
chmod 644 /etc/ssl/certs/nexosupport.crt
chmod 600 /etc/ssl/private/nexosupport.key
chmod 644 /etc/ssl/certs/nexosupport-chain.crt
```

---

## Performance Optimization

### PHP Optimization

**Enable OPcache** (`/etc/php/8.0/apache2/conf.d/10-opcache.ini`):

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

**Enable APCu** (`/etc/php/8.0/apache2/conf.d/20-apcu.ini`):

```ini
apc.enabled=1
apc.shm_size=64M
apc.ttl=3600
apc.enable_cli=0
```

### Application Caching

The CacheManager supports three layers:

1. **Memory cache** (fastest, session-based)
2. **APCu cache** (shared across requests)
3. **File cache** (fallback)

Ensure APCu is enabled for best performance.

### Database Optimization

```sql
-- Enable query cache (MySQL)
SET GLOBAL query_cache_size = 67108864;  -- 64MB
SET GLOBAL query_cache_type = 1;

-- Increase buffer pool (MySQL)
SET GLOBAL innodb_buffer_pool_size = 1073741824;  -- 1GB

-- Connection pooling settings
SET GLOBAL max_connections = 200;
SET GLOBAL wait_timeout = 600;
```

### Web Server Optimization

**Apache:**

```apache
# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Enable browser caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

**Nginx:**

```nginx
# Gzip compression
gzip on;
gzip_vary on;
gzip_min_length 256;
gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss;

# Client body buffer
client_body_buffer_size 128k;
client_max_body_size 50m;
```

---

## Post-Deployment Verification

### Health Check

Visit the health check endpoint:

```bash
curl https://support.yourdomain.com/api/health-check.php
```

Expected response:

```json
{
    "status": "ok",
    "timestamp": "2024-11-16T12:00:00Z",
    "checks": {
        "database": "ok",
        "filesystem": "ok",
        "php_extensions": "ok",
        "disk_space": "ok",
        "cache": "ok"
    },
    "message": "All systems operational"
}
```

### Visual Health Dashboard

Login as admin and visit:

```
https://support.yourdomain.com/admin/health/
```

Verify all indicators are green.

### Verify Critical Functions

- [ ] User login works
- [ ] MFA codes are sent via email
- [ ] File uploads work
- [ ] Cache is functioning (check admin dashboard)
- [ ] Themes switch correctly
- [ ] RBAC permissions work
- [ ] Session management works
- [ ] Email notifications are sent

---

## Monitoring Setup

### Application Monitoring

**Health Check Cron** (`/etc/cron.d/nexosupport-health`):

```bash
*/5 * * * * www-data curl -s https://support.yourdomain.com/api/health-check.php | logger -t nexosupport-health
```

### Log Monitoring

**Centralized logging** with `rsyslog`:

```bash
# /etc/rsyslog.d/nexosupport.conf
$ModLoad imfile

$InputFileName /var/www/nexosupport/logs/error.log
$InputFileTag nexosupport-error:
$InputFileStateFile stat-nexosupport-error
$InputFileSeverity error
$InputRunFileMonitor

$InputFileName /var/www/nexosupport/logs/access.log
$InputFileTag nexosupport-access:
$InputFileStateFile stat-nexosupport-access
$InputFileSeverity info
$InputRunFileMonitor
```

### External Monitoring

Integrate with monitoring services:

- **UptimeRobot**: Monitor `/api/health-check.php` endpoint
- **Pingdom**: HTTP checks every 5 minutes
- **New Relic**: Application performance monitoring
- **Datadog**: Metrics and log aggregation

---

## Troubleshooting

### Common Issues

**Issue: White screen (WSOD)**

```bash
# Check PHP error log
tail -f /var/log/apache2/nexosupport-error.log

# Enable display errors temporarily
# Edit lib/config.php
define('DISPLAY_ERRORS', true);

# Check file permissions
ls -la /var/www/nexosupport
```

**Issue: Database connection failed**

```bash
# Test database connection
mysql -u nexosupport_user -p nexosupport -e "SELECT 1;"

# Check config.php credentials
grep DB_ lib/config.php

# Verify database exists
mysql -u root -p -e "SHOW DATABASES LIKE 'nexosupport';"
```

**Issue: Cache not working**

```bash
# Check APCu
php -i | grep apcu

# Check cache directory permissions
ls -ld /var/www/nexosupport/cache

# Clear cache manually
rm -rf /var/www/nexosupport/cache/*
```

**Issue: MFA emails not sending**

```bash
# Test SMTP connection
telnet smtp.yourdomain.com 587

# Check email logs
tail -f /var/www/nexosupport/logs/email.log

# Verify SMTP settings in lib/config.php
grep SMTP_ lib/config.php
```

**Issue: Permission denied errors**

```bash
# Fix ownership
chown -R www-data:www-data /var/www/nexosupport

# Fix directory permissions
find /var/www/nexosupport -type d -exec chmod 755 {} \;

# Fix file permissions
find /var/www/nexosupport -type f -exec chmod 644 {} \;

# Fix writable directories
chmod 770 /var/www/nexosupport/{cache,logs,uploads,backups}
```

---

## Next Steps

After successful deployment:

1. **Read SECURITY.md** - Security best practices and hardening
2. **Read BACKUP_RESTORE.md** - Backup and disaster recovery procedures
3. **Configure monitoring** - Set up health checks and alerts
4. **Train administrators** - User management, RBAC, MFA setup
5. **Plan maintenance** - Schedule regular updates and backups

---

## Support

**Documentation**: https://docs.nexosupport.com
**Issue Tracker**: https://github.com/alonsoarias/NexoSupport/issues
**Email**: support@nexosupport.com

---

**Deployment Checklist**

- [ ] System requirements met
- [ ] Database created and schema installed
- [ ] Configuration file created (lib/config.php)
- [ ] File permissions set correctly
- [ ] Web server configured
- [ ] SSL certificate installed
- [ ] Health checks passing
- [ ] Monitoring configured
- [ ] Backups configured
- [ ] Security hardening complete
- [ ] Documentation reviewed
- [ ] Admin training complete
