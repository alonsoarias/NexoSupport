# NexoSupport - Backup & Restore Guide

## Overview

This guide covers backup strategies, automated backup procedures, disaster recovery, and restore operations for NexoSupport installations. A comprehensive backup strategy is critical for business continuity and data protection.

**Version**: 2.0.0 (Frankenstyle Architecture)
**Last Updated**: 2024-11-16
**Recovery Time Objective (RTO)**: < 4 hours
**Recovery Point Objective (RPO)**: < 24 hours

---

## Table of Contents

1. [Backup Strategy](#backup-strategy)
2. [What to Backup](#what-to-backup)
3. [Database Backups](#database-backups)
4. [File Backups](#file-backups)
5. [Automated Backup Scripts](#automated-backup-scripts)
6. [Backup Verification](#backup-verification)
7. [Backup Storage](#backup-storage)
8. [Restore Procedures](#restore-procedures)
9. [Disaster Recovery](#disaster-recovery)
10. [Testing Backups](#testing-backups)

---

## Backup Strategy

### Backup Types

NexoSupport uses a **3-2-1 backup strategy**:

- **3** copies of data (1 primary + 2 backups)
- **2** different storage media (local disk + cloud/tape)
- **1** off-site copy (cloud storage or remote location)

### Backup Frequency

**Recommended schedule**:

| Component | Frequency | Retention |
|-----------|-----------|-----------|
| Database | Daily (full) + Hourly (incremental) | 30 days full, 7 days incremental |
| User uploads | Daily | 90 days |
| Configuration files | After each change | 365 days |
| Application code | After each deployment | Indefinite (version control) |
| Logs | Weekly | 90 days |
| Full system | Weekly | 4 weeks |

### Backup Windows

**Production schedule example**:

```
02:00 - Full database backup
02:30 - File system backup (uploads, cache)
03:00 - Configuration backup
03:30 - Log rotation and archival
04:00 - Backup verification
04:30 - Off-site transfer
```

---

## What to Backup

### Critical Components

1. **Database**
   - All tables in nexosupport database
   - User accounts, permissions, roles
   - Content, tickets, messages
   - MFA settings, audit logs
   - Theme settings

2. **Files**
   - User uploads (`/uploads/`)
   - Configuration files (`lib/config.php`, `*/*/config.php`)
   - Custom themes (`theme/custom/`)
   - Installed plugins (`modules/`, `admin/tool/`)
   - SSL certificates
   - Cache files (optional, can be regenerated)

3. **Configuration**
   - Web server config (Apache/Nginx)
   - PHP configuration (`php.ini`)
   - Cron jobs
   - Environment variables
   - DNS settings (document separately)

### Components NOT to Backup

- Cache files (`/cache/`) - Can be regenerated
- Temporary files (`/tmp/`)
- Session files
- Compiled templates
- Log files older than retention period
- Third-party vendor code (reinstall from package manager)

---

## Database Backups

### Full Database Backup

**Using mysqldump**:

```bash
#!/bin/bash
# /usr/local/bin/nexosupport-db-backup.sh

# Configuration
DB_NAME="nexosupport"
DB_USER="nexosupport_backup"
DB_PASSWORD="BACKUP_USER_PASSWORD"
BACKUP_DIR="/var/backups/nexosupport/database"
RETENTION_DAYS=30
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="nexosupport_db_${DATE}.sql.gz"

# Create backup directory
mkdir -p "${BACKUP_DIR}"

# Perform backup
mysqldump \
    --user="${DB_USER}" \
    --password="${DB_PASSWORD}" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --routines \
    --triggers \
    --events \
    "${DB_NAME}" | gzip > "${BACKUP_DIR}/${BACKUP_FILE}"

# Verify backup
if [ $? -eq 0 ]; then
    echo "Database backup successful: ${BACKUP_FILE}"

    # Calculate checksum
    sha256sum "${BACKUP_DIR}/${BACKUP_FILE}" > "${BACKUP_DIR}/${BACKUP_FILE}.sha256"

    # Delete old backups
    find "${BACKUP_DIR}" -name "nexosupport_db_*.sql.gz" -mtime +${RETENTION_DAYS} -delete
    find "${BACKUP_DIR}" -name "nexosupport_db_*.sha256" -mtime +${RETENTION_DAYS} -delete
else
    echo "Database backup failed!" >&2
    exit 1
fi
```

**Schedule with cron** (`/etc/cron.d/nexosupport-backup`):

```bash
# Daily full backup at 2:00 AM
0 2 * * * root /usr/local/bin/nexosupport-db-backup.sh >> /var/log/nexosupport-backup.log 2>&1
```

### Incremental Backup (Binary Log)

**Enable binary logging** (`/etc/mysql/mysql.conf.d/mysqld.cnf`):

```ini
[mysqld]
server-id = 1
log_bin = /var/log/mysql/mysql-bin.log
expire_logs_days = 7
max_binlog_size = 100M
binlog_format = ROW
```

**Backup binary logs hourly**:

```bash
#!/bin/bash
# /usr/local/bin/nexosupport-binlog-backup.sh

BACKUP_DIR="/var/backups/nexosupport/binlog"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p "${BACKUP_DIR}"

# Flush logs to start new binlog file
mysql -u root -p -e "FLUSH BINARY LOGS;"

# Copy binary logs
cp /var/log/mysql/mysql-bin.* "${BACKUP_DIR}/"

echo "Binary log backup completed at ${DATE}"
```

**Cron job**:

```bash
# Hourly binary log backup
0 * * * * root /usr/local/bin/nexosupport-binlog-backup.sh >> /var/log/nexosupport-backup.log 2>&1
```

### Database Backup Best Practices

1. **Use dedicated backup user** with read-only access:
   ```sql
   CREATE USER 'nexosupport_backup'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
   GRANT SELECT, LOCK TABLES, SHOW VIEW ON nexosupport.* TO 'nexosupport_backup'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Use --single-transaction** for InnoDB tables (no table locking)

3. **Compress backups** to save disk space (gzip, bzip2, xz)

4. **Calculate checksums** (SHA256) to verify backup integrity

5. **Test restores regularly** (at least monthly)

---

## File Backups

### Rsync-based File Backup

**Full file backup script**:

```bash
#!/bin/bash
# /usr/local/bin/nexosupport-file-backup.sh

# Configuration
SOURCE_DIR="/var/www/nexosupport"
BACKUP_DIR="/var/backups/nexosupport/files"
RETENTION_DAYS=90
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="nexosupport_files_${DATE}"

# Create backup directory
mkdir -p "${BACKUP_DIR}/${BACKUP_NAME}"

# Perform backup with rsync
rsync -av \
    --delete \
    --exclude 'cache/*' \
    --exclude 'logs/*' \
    --exclude 'tmp/*' \
    --exclude '.git' \
    "${SOURCE_DIR}/" \
    "${BACKUP_DIR}/${BACKUP_NAME}/"

if [ $? -eq 0 ]; then
    echo "File backup successful: ${BACKUP_NAME}"

    # Create tarball
    cd "${BACKUP_DIR}"
    tar -czf "${BACKUP_NAME}.tar.gz" "${BACKUP_NAME}"

    # Calculate checksum
    sha256sum "${BACKUP_NAME}.tar.gz" > "${BACKUP_NAME}.tar.gz.sha256"

    # Remove uncompressed backup
    rm -rf "${BACKUP_NAME}"

    # Delete old backups
    find "${BACKUP_DIR}" -name "nexosupport_files_*.tar.gz" -mtime +${RETENTION_DAYS} -delete
    find "${BACKUP_DIR}" -name "nexosupport_files_*.sha256" -mtime +${RETENTION_DAYS} -delete
else
    echo "File backup failed!" >&2
    exit 1
fi
```

**Cron job**:

```bash
# Daily file backup at 2:30 AM
30 2 * * * root /usr/local/bin/nexosupport-file-backup.sh >> /var/log/nexosupport-backup.log 2>&1
```

### Incremental File Backup

**Using rsync with hardlinks** (saves disk space):

```bash
#!/bin/bash
# /usr/local/bin/nexosupport-incremental-backup.sh

SOURCE_DIR="/var/www/nexosupport"
BACKUP_DIR="/var/backups/nexosupport/incremental"
CURRENT_BACKUP="${BACKUP_DIR}/current"
PREVIOUS_BACKUP="${BACKUP_DIR}/previous"
DATE=$(date +%Y%m%d_%H%M%S)

# Rotate backups
if [ -d "${CURRENT_BACKUP}" ]; then
    mv "${CURRENT_BACKUP}" "${PREVIOUS_BACKUP}"
fi

# Create new backup with hardlinks to previous
rsync -av \
    --delete \
    --link-dest="${PREVIOUS_BACKUP}" \
    --exclude 'cache/*' \
    --exclude 'logs/*' \
    "${SOURCE_DIR}/" \
    "${CURRENT_BACKUP}/"

echo "Incremental backup completed at ${DATE}"
```

### Configuration File Backup

**Backup critical configuration files**:

```bash
#!/bin/bash
# /usr/local/bin/nexosupport-config-backup.sh

BACKUP_DIR="/var/backups/nexosupport/config"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="config_${DATE}.tar.gz"

mkdir -p "${BACKUP_DIR}"

# Backup configuration files
tar -czf "${BACKUP_DIR}/${BACKUP_FILE}" \
    /var/www/nexosupport/lib/config.php \
    /var/www/nexosupport/modules/*/config.php \
    /var/www/nexosupport/admin/*/config.php \
    /var/www/nexosupport/theme/*/config.php \
    /etc/apache2/sites-available/nexosupport.conf \
    /etc/php/8.0/apache2/php.ini \
    /etc/cron.d/nexosupport-* \
    2>/dev/null

# Calculate checksum
sha256sum "${BACKUP_DIR}/${BACKUP_FILE}" > "${BACKUP_DIR}/${BACKUP_FILE}.sha256"

# Keep 1 year of config backups
find "${BACKUP_DIR}" -name "config_*.tar.gz" -mtime +365 -delete
find "${BACKUP_DIR}" -name "config_*.sha256" -mtime +365 -delete

echo "Configuration backup completed: ${BACKUP_FILE}"
```

---

## Automated Backup Scripts

### Master Backup Script

**All-in-one backup script**:

```bash
#!/bin/bash
# /usr/local/bin/nexosupport-master-backup.sh

set -e  # Exit on error

LOG_FILE="/var/log/nexosupport-backup.log"
EMAIL_ALERT="admin@yourdomain.com"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "${LOG_FILE}"
}

send_alert() {
    echo "$1" | mail -s "NexoSupport Backup Alert" "${EMAIL_ALERT}"
}

log "Starting NexoSupport master backup"

# 1. Database backup
log "Starting database backup..."
if /usr/local/bin/nexosupport-db-backup.sh >> "${LOG_FILE}" 2>&1; then
    log "Database backup completed successfully"
else
    log "Database backup FAILED"
    send_alert "Database backup failed. Check logs at ${LOG_FILE}"
    exit 1
fi

# 2. File backup
log "Starting file backup..."
if /usr/local/bin/nexosupport-file-backup.sh >> "${LOG_FILE}" 2>&1; then
    log "File backup completed successfully"
else
    log "File backup FAILED"
    send_alert "File backup failed. Check logs at ${LOG_FILE}"
    exit 1
fi

# 3. Configuration backup
log "Starting configuration backup..."
if /usr/local/bin/nexosupport-config-backup.sh >> "${LOG_FILE}" 2>&1; then
    log "Configuration backup completed successfully"
else
    log "Configuration backup FAILED"
    send_alert "Configuration backup failed. Check logs at ${LOG_FILE}"
    exit 1
fi

# 4. Off-site transfer (optional)
log "Starting off-site transfer..."
if /usr/local/bin/nexosupport-offsite-sync.sh >> "${LOG_FILE}" 2>&1; then
    log "Off-site transfer completed successfully"
else
    log "Off-site transfer FAILED"
    send_alert "Off-site transfer failed. Check logs at ${LOG_FILE}"
fi

log "Master backup completed successfully"
```

### Off-Site Backup Sync

**Upload to cloud storage** (AWS S3 example):

```bash
#!/bin/bash
# /usr/local/bin/nexosupport-offsite-sync.sh

BACKUP_DIR="/var/backups/nexosupport"
S3_BUCKET="s3://nexosupport-backups"
AWS_PROFILE="nexosupport"

# Sync to S3
aws s3 sync \
    --profile "${AWS_PROFILE}" \
    --storage-class STANDARD_IA \
    --exclude "*.log" \
    "${BACKUP_DIR}/" \
    "${S3_BUCKET}/"

echo "Off-site backup sync completed"
```

**Or using rsync to remote server**:

```bash
#!/bin/bash
# /usr/local/bin/nexosupport-offsite-sync.sh

BACKUP_DIR="/var/backups/nexosupport"
REMOTE_USER="backup"
REMOTE_HOST="backup.yourdomain.com"
REMOTE_DIR="/backup/nexosupport"

# Sync via rsync over SSH
rsync -avz \
    --delete \
    -e "ssh -i /root/.ssh/backup_key" \
    "${BACKUP_DIR}/" \
    "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR}/"

echo "Off-site rsync completed"
```

---

## Backup Verification

### Automated Verification

**Verify backup integrity**:

```bash
#!/bin/bash
# /usr/local/bin/nexosupport-verify-backup.sh

BACKUP_DIR="/var/backups/nexosupport"

# Find latest database backup
LATEST_DB_BACKUP=$(ls -t ${BACKUP_DIR}/database/nexosupport_db_*.sql.gz | head -1)

if [ -f "${LATEST_DB_BACKUP}" ]; then
    echo "Verifying database backup: ${LATEST_DB_BACKUP}"

    # Verify checksum
    sha256sum -c "${LATEST_DB_BACKUP}.sha256"

    # Test decompression
    gzip -t "${LATEST_DB_BACKUP}"

    # Test import (to temporary database)
    # mysql -u root -p -e "CREATE DATABASE nexosupport_test;"
    # zcat "${LATEST_DB_BACKUP}" | mysql -u root -p nexosupport_test
    # mysql -u root -p -e "DROP DATABASE nexosupport_test;"

    echo "Database backup verification passed"
else
    echo "No database backup found!" >&2
    exit 1
fi
```

### Backup Monitoring

**Monitor backup status**:

```bash
#!/bin/bash
# /usr/local/bin/nexosupport-backup-monitor.sh

BACKUP_DIR="/var/backups/nexosupport"
ALERT_EMAIL="admin@yourdomain.com"
MAX_AGE_HOURS=25  # Alert if backup older than 25 hours

# Check database backup age
LATEST_DB_BACKUP=$(find ${BACKUP_DIR}/database -name "*.sql.gz" -type f -printf '%T@ %p\n' | sort -rn | head -1 | cut -d' ' -f2)
DB_BACKUP_AGE=$(( ($(date +%s) - $(stat -c %Y "${LATEST_DB_BACKUP}")) / 3600 ))

if [ ${DB_BACKUP_AGE} -gt ${MAX_AGE_HOURS} ]; then
    echo "WARNING: Database backup is ${DB_BACKUP_AGE} hours old" | \
        mail -s "NexoSupport Backup Alert - Database" "${ALERT_EMAIL}"
fi

# Check file backup age
LATEST_FILE_BACKUP=$(find ${BACKUP_DIR}/files -name "*.tar.gz" -type f -printf '%T@ %p\n' | sort -rn | head -1 | cut -d' ' -f2)
FILE_BACKUP_AGE=$(( ($(date +%s) - $(stat -c %Y "${LATEST_FILE_BACKUP}")) / 3600 ))

if [ ${FILE_BACKUP_AGE} -gt ${MAX_AGE_HOURS} ]; then
    echo "WARNING: File backup is ${FILE_BACKUP_AGE} hours old" | \
        mail -s "NexoSupport Backup Alert - Files" "${ALERT_EMAIL}"
fi
```

---

## Backup Storage

### Local Storage

**Recommended local storage**:

- Separate disk/partition from application data
- RAID 1 or RAID 10 for redundancy
- Minimum 100GB for 30 days retention
- Automated cleanup of old backups

### Off-Site Storage Options

1. **Cloud Storage**
   - AWS S3 / Glacier
   - Azure Blob Storage
   - Google Cloud Storage
   - Backblaze B2

2. **Remote Server**
   - Dedicated backup server
   - Rsync over SSH
   - Geographic redundancy

3. **Tape Backup** (for long-term archival)
   - LTO tapes
   - Monthly rotation
   - Off-site storage vault

### Encryption for Off-Site Backups

**Encrypt before upload**:

```bash
#!/bin/bash
# Encrypt backup before uploading

BACKUP_FILE="nexosupport_db_20241116.sql.gz"
ENCRYPTION_KEY="/root/.backup-encryption-key"

# Encrypt with GPG
gpg --symmetric --cipher-algo AES256 --passphrase-file "${ENCRYPTION_KEY}" "${BACKUP_FILE}"

# Upload encrypted file
aws s3 cp "${BACKUP_FILE}.gpg" s3://nexosupport-backups/

# Clean up
rm "${BACKUP_FILE}.gpg"
```

---

## Restore Procedures

### Database Restore

**Full database restore**:

```bash
#!/bin/bash
# /usr/local/bin/nexosupport-db-restore.sh

BACKUP_FILE="$1"  # Path to backup file

if [ -z "${BACKUP_FILE}" ]; then
    echo "Usage: $0 /path/to/backup.sql.gz"
    exit 1
fi

# Verify checksum
echo "Verifying backup integrity..."
sha256sum -c "${BACKUP_FILE}.sha256" || exit 1

# Stop application
echo "Stopping web server..."
systemctl stop apache2

# Create safety backup of current database
echo "Creating safety backup of current database..."
mysqldump -u root -p nexosupport | gzip > "/tmp/nexosupport_pre_restore_$(date +%Y%m%d_%H%M%S).sql.gz"

# Drop and recreate database
echo "Dropping current database..."
mysql -u root -p -e "DROP DATABASE IF EXISTS nexosupport;"
mysql -u root -p -e "CREATE DATABASE nexosupport CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Restore from backup
echo "Restoring from backup..."
zcat "${BACKUP_FILE}" | mysql -u root -p nexosupport

if [ $? -eq 0 ]; then
    echo "Database restore successful"

    # Start application
    echo "Starting web server..."
    systemctl start apache2

    # Verify health
    sleep 5
    curl -s https://support.yourdomain.com/api/health-check.php | jq
else
    echo "Database restore FAILED!"
    echo "Safety backup available at /tmp/nexosupport_pre_restore_*.sql.gz"
    exit 1
fi
```

### File Restore

**Restore specific files or directories**:

```bash
#!/bin/bash
# /usr/local/bin/nexosupport-file-restore.sh

BACKUP_FILE="$1"
RESTORE_PATH="$2"

if [ -z "${BACKUP_FILE}" ] || [ -z "${RESTORE_PATH}" ]; then
    echo "Usage: $0 /path/to/backup.tar.gz /restore/path"
    exit 1
fi

# Verify checksum
sha256sum -c "${BACKUP_FILE}.sha256" || exit 1

# Extract backup
tar -xzf "${BACKUP_FILE}" -C "${RESTORE_PATH}"

# Fix permissions
chown -R www-data:www-data "${RESTORE_PATH}"
chmod -R 755 "${RESTORE_PATH}"

echo "File restore completed to ${RESTORE_PATH}"
```

### Point-in-Time Recovery

**Restore database to specific point in time using binary logs**:

```bash
#!/bin/bash
# Restore to specific point in time

# 1. Restore full backup (last backup before target time)
zcat nexosupport_db_20241115_020000.sql.gz | mysql -u root -p nexosupport

# 2. Apply binary logs up to target time
mysqlbinlog \
    --stop-datetime="2024-11-16 14:30:00" \
    /var/backups/nexosupport/binlog/mysql-bin.000001 \
    /var/backups/nexosupport/binlog/mysql-bin.000002 | \
    mysql -u root -p nexosupport

echo "Point-in-time recovery completed"
```

---

## Disaster Recovery

### Disaster Recovery Plan

**Recovery scenarios**:

1. **Data corruption** (RPO: 24h, RTO: 2h)
2. **Server failure** (RPO: 24h, RTO: 4h)
3. **Site disaster** (RPO: 24h, RTO: 8h)

### Full System Restore

**Step-by-step full recovery**:

```bash
# 1. Provision new server
# - Install OS (Ubuntu 22.04 LTS)
# - Configure network
# - Install LAMP stack

# 2. Install NexoSupport
git clone https://github.com/alonsoarias/NexoSupport.git /var/www/nexosupport
cd /var/www/nexosupport

# 3. Restore configuration files
tar -xzf /path/to/config_backup.tar.gz -C /

# 4. Create database
mysql -u root -p -e "CREATE DATABASE nexosupport CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Restore database
zcat /path/to/nexosupport_db_backup.sql.gz | mysql -u root -p nexosupport

# 6. Restore files
tar -xzf /path/to/nexosupport_files_backup.tar.gz -C /var/www/nexosupport

# 7. Fix permissions
chown -R www-data:www-data /var/www/nexosupport
chmod 770 /var/www/nexosupport/{cache,logs,uploads}

# 8. Configure web server
cp /var/www/nexosupport/docs/examples/apache-vhost.conf /etc/apache2/sites-available/nexosupport.conf
a2ensite nexosupport
systemctl reload apache2

# 9. Verify health
curl https://support.yourdomain.com/api/health-check.php

# 10. Update DNS (if needed)
# Point domain to new server IP
```

### Disaster Recovery Checklist

- [ ] Off-site backups are current (< 24h old)
- [ ] Backup verification passed
- [ ] Restoration procedure documented
- [ ] Recovery tested in last 90 days
- [ ] Emergency contacts updated
- [ ] DNS backup/documentation available
- [ ] SSL certificates backed up
- [ ] Credentials stored securely (password manager)
- [ ] Disaster recovery plan reviewed quarterly

---

## Testing Backups

### Monthly Backup Test

**Test restore procedure monthly**:

```bash
#!/bin/bash
# /usr/local/bin/nexosupport-test-restore.sh

# 1. Find latest backup
LATEST_DB_BACKUP=$(ls -t /var/backups/nexosupport/database/*.sql.gz | head -1)
LATEST_FILE_BACKUP=$(ls -t /var/backups/nexosupport/files/*.tar.gz | head -1)

# 2. Create test database
mysql -u root -p -e "DROP DATABASE IF EXISTS nexosupport_test;"
mysql -u root -p -e "CREATE DATABASE nexosupport_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Restore database
echo "Testing database restore..."
zcat "${LATEST_DB_BACKUP}" | mysql -u root -p nexosupport_test

# 4. Verify table count
TABLE_COUNT=$(mysql -u root -p -s -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'nexosupport_test';")
echo "Restored ${TABLE_COUNT} tables"

# 5. Verify record counts
USER_COUNT=$(mysql -u root -p -s -N -e "SELECT COUNT(*) FROM nexosupport_test.users;")
echo "Restored ${USER_COUNT} users"

# 6. Extract files to test location
echo "Testing file restore..."
TEST_DIR="/tmp/nexosupport_restore_test"
mkdir -p "${TEST_DIR}"
tar -xzf "${LATEST_FILE_BACKUP}" -C "${TEST_DIR}"

# 7. Verify file count
FILE_COUNT=$(find "${TEST_DIR}" -type f | wc -l)
echo "Restored ${FILE_COUNT} files"

# 8. Cleanup
mysql -u root -p -e "DROP DATABASE nexosupport_test;"
rm -rf "${TEST_DIR}"

echo "Backup test completed successfully"
```

**Schedule monthly test**:

```bash
# Test backups on 1st of each month at 3 AM
0 3 1 * * root /usr/local/bin/nexosupport-test-restore.sh >> /var/log/nexosupport-backup-test.log 2>&1
```

---

## Backup Checklist

**Pre-production backup setup**:

- [ ] Backup scripts created and tested
- [ ] Cron jobs scheduled
- [ ] Backup directory created with sufficient space
- [ ] Database backup user created
- [ ] Off-site storage configured
- [ ] Backup encryption configured (for off-site)
- [ ] Backup verification script scheduled
- [ ] Backup monitoring alerts configured
- [ ] Restore procedure documented
- [ ] Disaster recovery plan written
- [ ] Backup test scheduled (monthly)
- [ ] Team trained on restore procedures

**Monthly backup review**:

- [ ] Verify backups are running on schedule
- [ ] Check backup logs for errors
- [ ] Verify off-site backups are current
- [ ] Test restore procedure
- [ ] Verify backup retention compliance
- [ ] Review and update disaster recovery plan

---

## Support

**Documentation**: https://docs.nexosupport.com/backup-restore
**Issue Tracker**: https://github.com/alonsoarias/NexoSupport/issues
**Emergency Support**: support@nexosupport.com

---

**Last Review Date**: 2024-11-16
**Next Review Due**: 2025-01-16 (Backup procedures reviewed monthly)
