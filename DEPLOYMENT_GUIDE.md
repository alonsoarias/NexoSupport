# 🚀 Deployment Guide - Frankenstyle Migration

**Project:** NexoSupport
**Version:** 2.0.0 (Frankenstyle)
**Branch:** claude/frankenstyle-refactor-012YT4YqF9imBYn4kATA1fUe
**Date:** 2025-11-17

---

## ⚠️ IMPORTANT NOTES

- **Backup Required:** Always backup before deployment
- **Downtime:** Minimal (< 5 minutes expected)
- **Rollback Plan:** Available (see below)
- **Testing Required:** Run all tests before production

---

## 📋 PRE-DEPLOYMENT CHECKLIST

### Prerequisites

- [ ] PHP 8.1 or higher installed
- [ ] Composer installed
- [ ] Database backup completed
- [ ] Code backup completed
- [ ] Sufficient disk space for caches (~100MB)
- [ ] Write permissions on `var/` directory

### Pre-Deployment Tasks

- [ ] Review all 13 commits in the PR
- [ ] Run full test suite (`./run-tests.sh`)
- [ ] Verify all 133 tests pass
- [ ] Check no syntax errors (`find . -name "*.php" -exec php -l {} \;`)
- [ ] Review documentation
- [ ] Notify team of deployment window

---

## 🔧 DEPLOYMENT PROCESS

### Step 1: Backup (CRITICAL)

```bash
# Create timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Backup database
mysqldump -u [user] -p [database] > backup_db_${TIMESTAMP}.sql

# Backup files
tar -czf backup_files_${TIMESTAMP}.tar.gz \
  --exclude='var/cache' \
  --exclude='var/logs' \
  --exclude='vendor' \
  --exclude='.git' \
  .

# Verify backups
ls -lh backup_*${TIMESTAMP}*
```

**Verify backups are complete before proceeding!**

### Step 2: Maintenance Mode (Optional)

```bash
# Create maintenance flag
touch maintenance.flag

# Or update config
# Set MAINTENANCE_MODE = true in config
```

### Step 3: Pull Changes

```bash
# Ensure you're on the right branch
git fetch origin

# Check current branch
git branch

# Pull latest changes
git pull origin claude/frankenstyle-refactor-012YT4YqF9imBYn4kATA1fUe

# Verify files updated
git log -1 --stat
```

### Step 4: Install Dependencies

```bash
# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Verify installation
composer validate
composer check-platform-reqs
```

### Step 5: Set Permissions

```bash
# Create cache directory if not exists
mkdir -p var/cache
mkdir -p var/cache/templates
mkdir -p var/cache/components

# Set proper permissions
chmod -R 775 var/cache
chmod -R 775 var/logs

# Set ownership (adjust user/group as needed)
chown -R www-data:www-data var/cache
chown -R www-data:www-data var/logs

# Verify permissions
ls -la var/
```

### Step 6: Clear Old Caches (if upgrading)

```bash
# Clear old caches
rm -rf var/cache/*

# Verify empty
ls -la var/cache/
```

### Step 7: Run Tests

```bash
# Run test suite
./run-tests.sh

# Expected output:
# ✓ 133 tests passing
# ✓ No failures
# ✓ Execution time < 5 seconds

# If tests fail, STOP and investigate
```

### Step 8: Smoke Tests

```bash
# Test critical paths
curl -I http://your-domain.com/                    # Homepage
curl -I http://your-domain.com/login/              # Login
curl -I http://your-domain.com/report/log/         # Log report

# All should return 200 OK
```

### Step 9: Disable Maintenance Mode

```bash
# Remove maintenance flag
rm maintenance.flag

# Or update config
# Set MAINTENANCE_MODE = false
```

### Step 10: Monitor

```bash
# Watch error logs
tail -f var/logs/error.log

# Watch access logs
tail -f var/logs/access.log

# Monitor for 10-15 minutes
```

---

## ✅ POST-DEPLOYMENT VERIFICATION

### Functional Tests

- [ ] **Homepage**
  - [ ] Loads without errors
  - [ ] No PHP warnings/errors
  - [ ] All assets load (CSS, JS, images)

- [ ] **Authentication**
  - [ ] Login works
  - [ ] Logout works
  - [ ] Session persists
  - [ ] "Remember me" works

- [ ] **Log Report**
  - [ ] Report page loads
  - [ ] Filters work correctly
  - [ ] Pagination works
  - [ ] CSV export works
  - [ ] No errors in browser console

- [ ] **User Management**
  - [ ] User list displays
  - [ ] User creation works
  - [ ] User editing works
  - [ ] User deletion works (soft delete)

- [ ] **Role Management**
  - [ ] Role list displays
  - [ ] Role creation works
  - [ ] Permission assignment works

- [ ] **Internationalization**
  - [ ] Language switcher present
  - [ ] Spanish language works
  - [ ] English language works
  - [ ] All strings translated (no missing keys)

- [ ] **MFA (Multi-Factor Auth)**
  - [ ] Email factor works
  - [ ] TOTP factor works (if configured)
  - [ ] SMS factor works (if configured)
  - [ ] Backup codes work

### Performance Tests

- [ ] **Caching**
  - [ ] Cache directory has files (`var/cache/templates/`, `var/cache/components/`)
  - [ ] Cache files have recent timestamps
  - [ ] Page loads faster on second request

- [ ] **Page Load Times** (use browser DevTools)
  - [ ] Homepage: < 500ms
  - [ ] Log report: < 1s
  - [ ] User list: < 1s

### Cache Verification

```bash
# Check cache directory
ls -lah var/cache/templates/
ls -lah var/cache/components/

# Expected: Files with recent timestamps
```

### Log Check

```bash
# Check for errors
grep -i error var/logs/error.log | tail -20

# Check for warnings
grep -i warning var/logs/error.log | tail -20

# Should be minimal or none
```

---

## 🔄 ROLLBACK PLAN

### When to Rollback

Rollback immediately if:
- Critical functionality is broken
- Database corruption detected
- Performance degradation > 50%
- Security vulnerability found
- Majority of tests failing

### Rollback Process

#### Step 1: Stop Application

```bash
# Enable maintenance mode
touch maintenance.flag
```

#### Step 2: Restore Code

```bash
# Find your backup
ls -lh backup_files_*.tar.gz

# Extract backup (use actual timestamp)
tar -xzf backup_files_YYYYMMDD_HHMMSS.tar.gz

# Verify extraction
ls -la
```

#### Step 3: Restore Database

```bash
# Find your backup
ls -lh backup_db_*.sql

# Restore database
mysql -u [user] -p [database] < backup_db_YYYYMMDD_HHMMSS.sql

# Verify restoration
mysql -u [user] -p -e "SELECT COUNT(*) FROM users;" [database]
```

#### Step 4: Reinstall Old Dependencies

```bash
# If composer.json was changed
composer install --no-dev --optimize-autoloader
```

#### Step 5: Clear Caches

```bash
# Clear all caches
rm -rf var/cache/*
```

#### Step 6: Verify Rollback

```bash
# Test critical paths
curl -I http://your-domain.com/
curl -I http://your-domain.com/login/

# Check logs
tail -f var/logs/error.log
```

#### Step 7: Disable Maintenance

```bash
rm maintenance.flag
```

#### Step 8: Post-Rollback

- [ ] Notify team of rollback
- [ ] Document issues encountered
- [ ] Create action plan for retry
- [ ] Update deployment checklist

---

## 📊 MONITORING

### First Hour

Monitor these metrics:

- **Error Rate:** Should be near 0%
- **Response Time:** Should improve (faster)
- **CPU Usage:** Should decrease (~40% reduction)
- **Memory Usage:** Stable or slight increase (caching)
- **Cache Hit Rate:** Should increase over time

### Tools to Use

```bash
# Error logs
tail -f var/logs/error.log

# Access logs
tail -f var/logs/access.log

# System resources
top -p $(pgrep -f php-fpm)

# Cache statistics (if implemented)
# View cache stats via admin panel
```

### First 24 Hours

- Check error logs every 2-4 hours
- Monitor cache hit rates
- Track user feedback
- Watch performance metrics

### Metrics to Track

| Metric | Before | Target After |
|--------|--------|--------------|
| Page Load Time | Baseline | -30% |
| Server Response | Baseline | -40% |
| Error Rate | Baseline | Same or better |
| Cache Hit Rate | 0% | 80%+ |

---

## 🐛 TROUBLESHOOTING

### Common Issues

#### Issue: Tests Failing

**Symptoms:** `./run-tests.sh` shows failures

**Solution:**
```bash
# Check PHP version
php -v  # Must be 8.1+

# Check PHPUnit
vendor/bin/phpunit --version

# Run specific failing test
./run-tests.sh --filter TestName

# Check error output
```

#### Issue: Cache Directory Not Writable

**Symptoms:** Errors about cache write permissions

**Solution:**
```bash
# Fix permissions
chmod -R 775 var/cache
chown -R www-data:www-data var/cache

# Verify
ls -la var/
```

#### Issue: Templates Not Rendering

**Symptoms:** Blank pages or template errors

**Solution:**
```bash
# Check Mustache is installed
composer show mustache/mustache

# If missing:
composer require mustache/mustache:^2.14

# Clear template cache
rm -rf var/cache/templates/*
```

#### Issue: Language Strings Missing

**Symptoms:** Seeing translation keys instead of text

**Solution:**
```bash
# Verify language files exist
ls -la lang/es/
ls -la lang/en/

# Check file permissions
chmod 644 lang/*/*.php

# Clear cache
rm -rf var/cache/*
```

#### Issue: Slow Performance

**Symptoms:** Pages load slower than before

**Solution:**
```bash
# Check cache is enabled
# In config/cache.php, verify enabled = true

# Check cache directory has files
ls -lah var/cache/templates/
ls -lah var/cache/components/

# If empty, caching might not be working
# Check error logs for cache write failures

# Manually test cache
php -r "require 'vendor/autoload.php';
        use ISER\Core\Cache\Cache;
        $cache = new Cache('test');
        $cache->set('key', 'value');
        echo $cache->get('key');"
```

#### Issue: Autoloading Errors

**Symptoms:** Class not found errors

**Solution:**
```bash
# Regenerate autoloader
composer dump-autoload

# Verify PSR-4 mappings
composer validate

# Check class exists
find . -name "ClassName.php"
```

---

## 📝 DEPLOYMENT LOG TEMPLATE

```
DEPLOYMENT LOG
==============

Date: YYYY-MM-DD
Time: HH:MM (timezone)
Version: 2.0.0 (Frankenstyle)
Deployed By: [Name]

PRE-DEPLOYMENT:
- [ ] Backup completed: backup_YYYYMMDD_HHMMSS
- [ ] Tests passing: Yes/No
- [ ] Team notified: Yes/No

DEPLOYMENT:
- Start Time: HH:MM
- Maintenance Mode: HH:MM to HH:MM
- Code Pulled: HH:MM
- Dependencies Installed: HH:MM
- Permissions Set: HH:MM
- Tests Run: HH:MM (Pass/Fail)
- Maintenance Ended: HH:MM

POST-DEPLOYMENT:
- [ ] Smoke tests passed
- [ ] Cache verified
- [ ] Performance checked
- [ ] Logs reviewed
- [ ] No errors detected

ISSUES:
- None / [List any issues]

ROLLBACK:
- Required: Yes/No
- Reason: [If applicable]
- Time: [If applicable]

FINAL STATUS:
- Deployment: Success/Failed
- System Status: Normal/Issues
- Next Steps: [If applicable]

NOTES:
[Any additional notes]
```

---

## 🎯 SUCCESS CRITERIA

Deployment is successful if:

- [x] All 133 tests passing
- [x] No errors in logs (first 15 minutes)
- [x] Homepage loads in < 500ms
- [x] Login/logout works
- [x] Log report displays correctly
- [x] Cache files being created
- [x] Both languages (ES/EN) work
- [x] No user-reported issues (first hour)

---

## 📞 EMERGENCY CONTACTS

**In case of critical issues:**

- **Technical Lead:** [Name/Contact]
- **Database Admin:** [Name/Contact]
- **System Admin:** [Name/Contact]
- **On-Call Dev:** [Name/Contact]

**Escalation Path:**
1. Check rollback criteria
2. Execute rollback if needed
3. Notify technical lead
4. Document issues
5. Plan retry deployment

---

## 📚 ADDITIONAL RESOURCES

- **Full Documentation:** `docs/FRANKENSTYLE_PROJECT_COMPLETE.md`
- **Testing Guide:** `tests/UNIT_TEST_SUMMARY.md`
- **Caching Guide:** `CACHE_QUICK_REFERENCE.md`
- **Pull Request:** `PULL_REQUEST.md`

---

## ✅ FINAL CHECKLIST

Before marking deployment complete:

- [ ] All functional tests passed
- [ ] Performance metrics acceptable
- [ ] Cache working correctly
- [ ] Logs show no errors
- [ ] Team notified of completion
- [ ] Documentation updated
- [ ] Backup verified and stored
- [ ] Monitoring in place
- [ ] Support team briefed

---

**Deployment prepared by:** Claude (Anthropic AI)
**Review required by:** Project Lead
**Approval required by:** Technical Director

**Status:** ✅ Ready for Production Deployment
