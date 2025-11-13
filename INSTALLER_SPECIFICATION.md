# WEB INSTALLER SPECIFICATION - NexoSupport

**Project:** NexoSupport Authentication System
**Document Type:** Technical Specification
**Version:** 2.0
**Status:** 📋 Specification (30% Implemented - Major Redesign)
**Date:** 2025-11-13

---

## EXECUTIVE SUMMARY

### Current Status: 30% Complete

**What Exists:**
- ✅ Functional installer in `/install/`
- ✅ 7-stage installation process
- ✅ Database connection testing
- ✅ Schema installation via XML
- ✅ Admin user creation
- ✅ `.env` file generation
- ✅ Basic requirement checking

**What's Missing:**
- ❌ Modern, responsive UI
- ❌ Enhanced user experience
- ❌ Real-time validation
- ❌ Progress indicators with detailed steps
- ❌ Internationalization (installer in multiple languages)
- ❌ Enhanced error handling
- ❌ Installation verification
- ❌ Post-installation setup wizard
- ❌ Accessibility features

**Goal:** Complete redesign of installer with modern UX, i18n, and enhanced features

---

## 1. DESIGN PHILOSOPHY

### 1.1 Core Principles

**1. User-Friendly First**
- Installation must be **intuitive** even for non-technical users
- **Clear instructions** at every step
- **Visual feedback** for all actions
- **Helpful error messages** with solutions

**2. Professional Appearance**
- **Modern UI** matching the theme system
- **Responsive** on all devices (desktop, tablet, mobile)
- **Accessible** (keyboard navigation, screen readers)
- **Branded** with NexoSupport identity

**3. Robust & Reliable**
- **Comprehensive validation** before proceeding
- **Real-time checks** (connection tests, permission checks)
- **Detailed logging** of installation process
- **Rollback capability** if installation fails

**4. Internationalized**
- **Multiple languages** (es, en, pt, fr, de)
- **Language selector** on welcome screen
- **All text translated** (no hardcoded strings)
- **Date/number formatting** per locale

---

## 2. INSTALLATION STAGES (11 STAGES)

### 2.1 Stage Overview

| # | Stage | Purpose | Est. Time |
|---|-------|---------|-----------|
| 1 | **Welcome** | Language selection, introduction | 30s |
| 2 | **Requirements** | Check PHP, extensions, permissions | 1min |
| 3 | **License** | Display and accept license (optional) | 30s |
| 4 | **Database** | Configure database connection | 2min |
| 5 | **Site Info** | Basic site information | 1min |
| 6 | **Admin User** | Create administrator account | 1min |
| 7 | **Security** | JWT secrets, password policies | 30s |
| 8 | **Email** | Email server configuration (optional) | 2min |
| 9 | **Installation** | Execute schema, create tables | 2min |
| 10 | **Verification** | Verify installation success | 1min |
| 11 | **Finish** | Summary, next steps | 30s |

**Total Estimated Time:** 10-12 minutes

---

### 2.2 Stage 1: Welcome 🎉

**Purpose:** Greet user, select language, show system information

**UI Elements:**
- **Welcome message** (translated)
- **Language selector** (dropdown with flags)
  - Español (es)
  - English (en)
  - Português (pt)
  - Français (fr)
  - Deutsch (de)
- **System information card:**
  - NexoSupport version
  - PHP version detected
  - Server software (Apache/Nginx)
  - Current date/time
- **Installation mode:**
  - New installation (default)
  - Reinstall (if already installed)
- **Next button**

**Validation:** None (informational stage)

**Data Collected:**
- Preferred language (stored in session)

**Example:**
```
┌─────────────────────────────────────────┐
│  🚀 Welcome to NexoSupport Installer    │
│                                         │
│  Select your language:                  │
│  [Español ▼]                            │
│                                         │
│  System Information:                    │
│  Version: 1.0.0                        │
│  PHP: 8.2.3                            │
│  Server: Apache 2.4                     │
│                                         │
│  [Continue →]                          │
└─────────────────────────────────────────┘
```

---

### 2.3 Stage 2: Requirements Check ✅

**Purpose:** Verify server meets minimum requirements

**Checks:**

**1. PHP Version:**
- ✅ Required: PHP >= 8.1
- ⚠️ Warning: PHP >= 8.2 recommended
- ❌ Error: PHP < 8.1

**2. PHP Extensions:**
- ✅ Required:
  - PDO
  - pdo_mysql OR pdo_pgsql OR pdo_sqlite
  - JSON
  - mbstring
  - OpenSSL
  - session
  - ctype
  - hash
- ⚠️ Recommended:
  - curl
  - GD or Imagick
  - zip
  - fileinfo

**3. Directory Permissions:**
- ✅ Writable:
  - `/var/logs/`
  - `/var/cache/`
  - `/modules/plugins/`
  - `/public_html/assets/uploads/`
  - Project root (for `.env` creation)

**4. Additional Checks:**
- ✅ Memory limit >= 128M
- ✅ Max execution time >= 60s
- ✅ File uploads enabled
- ✅ POST max size >= 20M

**UI Elements:**
- **Checklist** with status icons (✅ ⚠️ ❌)
- **Expandable sections** for details
- **Retry button** (re-run checks)
- **Continue button** (disabled if errors)

**Auto-Fix Options:**
- **Fix permissions:** Offer to fix directory permissions (if possible)
- **Generate php.ini suggestions:** Show recommended php.ini settings

**Example:**
```
┌─────────────────────────────────────────┐
│  Requirements Check                     │
│                                         │
│  ✅ PHP Version (8.2.3)                │
│  ✅ PDO Extension                       │
│  ✅ pdo_mysql Extension                 │
│  ✅ JSON Extension                      │
│  ⚠️  GD Extension (Recommended)         │
│  ❌ /var/logs/ Not Writable             │
│      [Fix Permissions]                  │
│                                         │
│  [Retry Check]  [Continue →]           │
└─────────────────────────────────────────┘
```

---

### 2.4 Stage 3: License Agreement 📜 (Optional)

**Purpose:** Display software license and require acceptance

**UI Elements:**
- **License text** (scrollable)
- **"I accept" checkbox** (required to continue)
- **Print/Download license** link

**Options:**
- This stage can be **disabled** if no license is required
- Configure via `INSTALLER_SHOW_LICENSE` constant

---

### 2.5 Stage 4: Database Configuration 🗄️

**Purpose:** Configure database connection

**Fields:**

**Database Driver:**
- MySQL (default)
- PostgreSQL
- SQLite

**For MySQL/PostgreSQL:**
- Hostname (default: localhost)
- Port (default: 3306 for MySQL, 5432 for PostgreSQL)
- Database name
- Username
- Password (with show/hide toggle)
- Table prefix (optional, default: `nexo_`)

**For SQLite:**
- Database file path (default: `/var/database/nexosupport.sqlite`)
- Auto-create database file

**Additional Options:**
- **Create database if not exists** (checkbox)
- **Use existing tables** (checkbox) - for reinstall

**Real-Time Validation:**
- **Test Connection button**
  - Tests connection with provided credentials
  - Shows success/error message immediately
  - Checks if database exists
  - Checks if user has required privileges (CREATE, ALTER, INSERT, SELECT, UPDATE, DELETE)
- **Database exists check**
  - If database exists, show warning
  - Option to use existing database or create new

**UI Elements:**
- **Form fields** with validation
- **Test Connection** button
- **Connection status** indicator (loading, success, error)
- **Help text** for each field
- **Advanced options** (collapsed by default)

**Example:**
```
┌─────────────────────────────────────────┐
│  Database Configuration                 │
│                                         │
│  Driver: [MySQL ▼]                     │
│  Host: [localhost]                      │
│  Port: [3306]                           │
│  Database: [nexosupport_db]            │
│  Username: [root]                       │
│  Password: [••••••] 👁                 │
│                                         │
│  [Test Connection]                      │
│  ✅ Connection successful!              │
│  ✅ Database exists                     │
│  ✅ User has required privileges        │
│                                         │
│  [← Back]  [Continue →]                │
└─────────────────────────────────────────┘
```

---

### 2.6 Stage 5: Site Information 🏢

**Purpose:** Configure basic site information

**Fields:**
- **Site Name** (default: NexoSupport)
  - Example: "ISER Authentication"
- **Site URL** (auto-detected, editable)
  - Example: https://auth.iser.edu
- **Timezone** (dropdown with search)
  - Example: America/Santiago
  - Group by region
- **Default Language** (es, en, pt, fr, de)
- **Date Format** (based on locale, customizable)
  - Examples: DD/MM/YYYY, MM/DD/YYYY, YYYY-MM-DD
- **Time Format** (12h / 24h)

**Optional Fields:**
- **Support Email** (for system notifications)
- **Organization Name** (optional)

**UI Elements:**
- **Form fields** with validation
- **Timezone search** (type to filter)
- **Preview** of date/time format
- **Auto-detection** for URL and timezone

**Example:**
```
┌─────────────────────────────────────────┐
│  Site Information                       │
│                                         │
│  Site Name: [ISER Authentication]      │
│  Site URL:  [https://auth.iser.edu]   │
│  Timezone:  [America/Santiago ▼]      │
│              Preview: 2025-11-13 15:30 │
│  Language:  [Español ▼]                │
│                                         │
│  Support Email (optional):             │
│  [support@iser.edu]                    │
│                                         │
│  [← Back]  [Continue →]                │
└─────────────────────────────────────────┘
```

---

### 2.7 Stage 6: Administrator Account 👤

**Purpose:** Create the first admin user

**Fields:**
- **Username** (3-50 characters, alphanumeric + underscore)
- **Email** (valid email, will be used for recovery)
- **Password** (with strength indicator)
  - Minimum 8 characters
  - Must include: uppercase, lowercase, number, special char
  - Real-time strength check (weak, medium, strong, very strong)
- **Confirm Password** (must match)
- **First Name**
- **Last Name**

**Real-Time Validation:**
- **Username availability** (check if username exists - for reinstall)
- **Email format** validation
- **Password strength** indicator with suggestions
- **Password match** verification

**Security Features:**
- **Password strength requirements** (configurable)
- **Show/hide password** toggle
- **Suggested strong password** generator button

**UI Elements:**
- **Form with inline validation**
- **Password strength bar** (color-coded)
- **Generate Password** button
- **Copy to clipboard** button

**Example:**
```
┌─────────────────────────────────────────┐
│  Create Administrator Account           │
│                                         │
│  Username: [admin]                      │
│  Email:    [admin@iser.edu]            │
│                                         │
│  Password: [•••••••••] 👁              │
│  ████████░░ Strong                      │
│  ✅ 8+ characters                       │
│  ✅ Uppercase, lowercase, number        │
│  ✅ Special character                   │
│                                         │
│  Confirm:  [•••••••••] ✅ Match        │
│                                         │
│  Name:     [John] [Doe]                │
│                                         │
│  [← Back]  [Continue →]                │
└─────────────────────────────────────────┘
```

---

### 2.8 Stage 7: Security Configuration 🔒

**Purpose:** Configure security settings

**Fields:**

**1. JWT Configuration:**
- **JWT Secret Key** (auto-generated, 64 chars)
  - [Regenerate] button
- **Token Expiration** (default: 3600 seconds = 1 hour)
  - Dropdown: 30 min, 1 hour, 2 hours, 4 hours, 8 hours, 24 hours

**2. Session Configuration:**
- **Session Lifetime** (default: 7200 seconds = 2 hours)
- **Remember Me** duration (default: 30 days)

**3. Password Policy:**
- **Minimum Length** (default: 8, range: 6-32)
- **Require Uppercase** (checkbox, default: yes)
- **Require Lowercase** (checkbox, default: yes)
- **Require Numbers** (checkbox, default: yes)
- **Require Special Characters** (checkbox, default: yes)
- **Password Expiration** (default: never, options: 30/60/90 days, never)

**4. Login Security:**
- **Max Failed Attempts** (default: 5)
- **Lockout Duration** (default: 15 minutes)
- **Enable CAPTCHA** (checkbox, default: no)

**UI Elements:**
- **Auto-generated** secure values
- **Explanatory text** for each setting
- **Use Recommended** button (reset to defaults)

**Example:**
```
┌─────────────────────────────────────────┐
│  Security Configuration                 │
│                                         │
│  JWT Secret: [••••••••••••••••]        │
│  [Regenerate]                           │
│                                         │
│  Token Expiration: [1 hour ▼]         │
│  Session Lifetime: [2 hours ▼]        │
│                                         │
│  Password Policy:                       │
│  Min Length: [8]                        │
│  ☑ Require Uppercase                    │
│  ☑ Require Numbers                      │
│  ☑ Require Special Chars                │
│                                         │
│  [Use Recommended Settings]            │
│  [← Back]  [Continue →]                │
└─────────────────────────────────────────┘
```

---

### 2.9 Stage 8: Email Configuration 📧 (Optional)

**Purpose:** Configure outgoing email server (optional but recommended)

**Options:**
- **Configure now** (recommended)
- **Configure later** (skip this step)

**Fields:**

**Email Driver:**
- SMTP (default)
- PHP mail()
- Sendmail

**For SMTP:**
- **SMTP Host** (e.g., smtp.gmail.com)
- **SMTP Port** (default: 587)
- **SMTP Encryption** (TLS, SSL, None)
- **SMTP Username**
- **SMTP Password** (with show/hide toggle)
- **From Email** (e.g., noreply@iser.edu)
- **From Name** (e.g., ISER Authentication)

**Test Email:**
- **Test Email Address** (send test email)
- **Send Test Email** button
- Real-time status (sending, sent, error)

**UI Elements:**
- **Form fields** with validation
- **Send Test Email** button
- **Skip this step** option
- **Help text** with common providers (Gmail, Office365, etc.)

**Example:**
```
┌─────────────────────────────────────────┐
│  Email Configuration (Optional)         │
│                                         │
│  Driver: [SMTP ▼]                      │
│  Host:   [smtp.gmail.com]              │
│  Port:   [587]                          │
│  Encryption: [TLS ▼]                   │
│  Username: [system@iser.edu]           │
│  Password: [•••••••••] 👁              │
│                                         │
│  From: [noreply@iser.edu]              │
│  Name: [ISER Authentication]           │
│                                         │
│  Test: [test@example.com]              │
│  [Send Test Email]                      │
│                                         │
│  [Skip]  [← Back]  [Continue →]        │
└─────────────────────────────────────────┘
```

---

### 2.10 Stage 9: Installation 🚀

**Purpose:** Execute the installation (create tables, insert data, create admin)

**Process:**

1. **Create .env file**
2. **Connect to database**
3. **Create tables** (via SchemaInstaller)
   - Parse schema.xml
   - Create all 23 tables
   - Create indexes
   - Create foreign keys
4. **Insert initial data**
   - Roles (admin, moderator, user, guest)
   - Permissions (35 permissions)
   - Role-Permission assignments
   - System configuration
5. **Create admin user**
6. **Generate application key**
7. **Clear cache**
8. **Write installation marker**

**UI Elements:**
- **Progress bar** (0-100%)
- **Current step** indicator
- **Detailed log** (scrollable, collapsible)
- **Estimated time remaining**
- **Pause/Resume** (if long installation)

**Real-Time Updates:**
- Each step updates progress bar
- Each table creation shown in log
- Success/error messages in real-time

**Error Handling:**
- If error occurs, show detailed error message
- Option to **View Full Log**
- Option to **Retry Installation**
- Option to **Report Error** (copy log to clipboard)

**Example:**
```
┌─────────────────────────────────────────┐
│  Installing NexoSupport...              │
│                                         │
│  ████████████████████░░░░░ 75%         │
│                                         │
│  Current Step: Creating tables          │
│  Estimated time: 30 seconds             │
│                                         │
│  ✅ Created .env file                   │
│  ✅ Connected to database               │
│  ✅ Created table: users                │
│  ✅ Created table: roles                │
│  ⏳ Creating table: permissions...      │
│                                         │
│  [Show Full Log ▼]                     │
│                                         │
│  [Cancel Installation]                  │
└─────────────────────────────────────────┘
```

---

### 2.11 Stage 10: Verification ✅

**Purpose:** Verify installation was successful

**Checks:**

1. **Database Tables**
   - ✅ All 23 tables created
   - ✅ All indexes created
   - ✅ All foreign keys created

2. **Initial Data**
   - ✅ Roles inserted (4 roles)
   - ✅ Permissions inserted (35 permissions)
   - ✅ Admin user created

3. **Configuration Files**
   - ✅ .env file exists and readable
   - ✅ Application key generated
   - ✅ Database connection works

4. **Directory Permissions**
   - ✅ /var/logs/ writable
   - ✅ /var/cache/ writable
   - ✅ /modules/plugins/ writable

5. **System Health**
   - ✅ Can write to logs
   - ✅ Can create sessions
   - ✅ Can render views

**UI Elements:**
- **Checklist** with status
- **Expandable details** for each check
- **Overall status**: Success / Warning / Error
- **Continue** or **View Issues** buttons

**If Issues Found:**
- List all issues
- Suggest fixes
- Option to retry verification
- Option to continue anyway (not recommended)

**Example:**
```
┌─────────────────────────────────────────┐
│  Installation Verification              │
│                                         │
│  ✅ Database (23/23 tables created)    │
│  ✅ Initial Data (installed)            │
│  ✅ Configuration (.env created)        │
│  ✅ Permissions (all writable)          │
│  ✅ System Health (all checks passed)   │
│                                         │
│  🎉 Installation Successful!            │
│                                         │
│  [Continue to Finish →]                │
└─────────────────────────────────────────┘
```

---

### 2.12 Stage 11: Finish 🎊

**Purpose:** Congratulate user, provide next steps

**UI Elements:**

**Success Message:**
- "Congratulations! NexoSupport has been installed successfully."
- Installation time: X minutes

**Installation Summary:**
- Site Name: [Site Name]
- Admin Username: [username]
- Admin Email: [email]
- Database: [MySQL/PostgreSQL/SQLite]
- Installation Date: [date/time]

**Important Notes:**
- ⚠️ **Delete or protect the /install/ directory**
  - Provide command: `rm -rf /install/` or `chmod 000 /install/`
- ⚠️ **Change admin password** after first login
- ⚠️ **Configure email** if skipped (link to settings)
- ⚠️ **Backup your database** regularly

**Next Steps:**
1. [Access Admin Panel →]
2. [View Documentation]
3. [Configure Additional Settings]

**Quick Actions:**
- **Download Installation Report** (PDF/TXT with all details)
- **Copy Admin Credentials** (clipboard)
- **Email Report to Admin** (if email configured)

**Example:**
```
┌─────────────────────────────────────────┐
│  🎉 Installation Complete!              │
│                                         │
│  NexoSupport has been successfully      │
│  installed in 8 minutes.                │
│                                         │
│  Installation Summary:                  │
│  • Site: ISER Authentication           │
│  • Admin: admin                         │
│  • Email: admin@iser.edu               │
│  • Database: MySQL (nexosupport_db)    │
│                                         │
│  ⚠️ Important:                          │
│  • Delete /install/ directory           │
│  • Change admin password                │
│  • Configure email (if not done)        │
│                                         │
│  [Download Report]                      │
│  [Access Admin Panel →]                │
└─────────────────────────────────────────┘
```

---

## 3. USER INTERFACE DESIGN

### 3.1 Layout

**Header:**
- NexoSupport logo
- Current stage indicator (1 of 11)
- Language selector (always visible)
- Help button (opens documentation)

**Main Area:**
- Current stage content
- Form fields or information
- Validation messages
- Help text

**Footer:**
- Navigation buttons (Back, Next, Skip)
- Progress bar (visual indicator)
- Estimated time remaining
- Copyright notice

**Example Layout:**
```
┌─────────────────────────────────────────────────────┐
│ [Logo] NexoSupport Installer    [Help] [Lang: ES ▼] │
├─────────────────────────────────────────────────────┤
│ Step 4 of 11: Database Configuration                │
│ ━━━━━━━━░░░░░░░░░░░░░░░░░░░░░░░░░ 36%             │
├─────────────────────────────────────────────────────┤
│                                                     │
│           [Stage Content Here]                      │
│                                                     │
│                                                     │
│                                                     │
│                                                     │
├─────────────────────────────────────────────────────┤
│ [← Back]    Est. Time: 2 min        [Continue →]   │
│ © 2025 ISER                                         │
└─────────────────────────────────────────────────────┘
```

---

### 3.2 Visual Design

**Color Scheme:**
- Use theme system colors
- Primary color for actions
- Success green for checks
- Warning amber for warnings
- Danger red for errors

**Typography:**
- Clear, readable fonts (system fonts)
- Large headings (24px)
- Body text (16px)
- Help text (14px, muted)

**Icons:**
- Use Bootstrap Icons or similar
- Consistent icon set throughout
- Meaningful icons (✅ ⚠️ ❌ ⏳ 🚀 🎉)

**Spacing:**
- Generous whitespace
- Clear sections
- Grouped related fields

**Responsive:**
- Desktop: 2-column layout where appropriate
- Tablet: Single column
- Mobile: Optimized forms, larger touch targets

---

### 3.3 Accessibility

**Keyboard Navigation:**
- Tab through all form fields
- Enter to submit/continue
- Escape to cancel/back
- Arrow keys for dropdowns

**Screen Reader:**
- Semantic HTML (headings, labels, descriptions)
- ARIA labels where needed
- Alt text for images
- Status announcements (installation progress)

**Color Contrast:**
- WCAG 2.1 AA compliant
- Text contrast ratio >= 4.5:1
- Interactive elements contrast >= 3:1

**Focus Indicators:**
- Visible focus states
- Clear tab order
- Skip to content link

---

## 4. TECHNICAL IMPLEMENTATION

### 4.1 Architecture

**Frontend:**
- Pure HTML5 + CSS3 (no framework dependency)
- Vanilla JavaScript (or minimal jQuery)
- AJAX for real-time validation
- Progressive enhancement

**Backend:**
- PHP 8.1+
- Single-page installer with stages
- Session-based state management
- JSON responses for AJAX

**File Structure:**
```
/install/
├── index.php               # Main installer entry point
├── config.php              # Installer configuration
├── stages/                 # Stage controllers
│   ├── WelcomeStage.php
│   ├── RequirementsStage.php
│   ├── LicenseStage.php
│   ├── DatabaseStage.php
│   ├── SiteInfoStage.php
│   ├── AdminUserStage.php
│   ├── SecurityStage.php
│   ├── EmailStage.php
│   ├── InstallationStage.php
│   ├── VerificationStage.php
│   └── FinishStage.php
├── includes/               # Installer classes
│   ├── Installer.php       # Main installer class
│   ├── RequirementsChecker.php
│   ├── DatabaseInstaller.php
│   ├── ConfigGenerator.php
│   └── Validator.php
├── templates/              # Mustache templates
│   ├── layout.mustache
│   ├── welcome.mustache
│   ├── requirements.mustache
│   └── ...
├── assets/                 # Installer assets
│   ├── css/
│   │   └── installer.css
│   ├── js/
│   │   └── installer.js
│   └── images/
│       ├── logo.svg
│       └── screenshots/
└── lang/                   # Translations
    ├── es/
    │   └── installer.php
    ├── en/
    │   └── installer.php
    └── ...
```

---

### 4.2 State Management

**Session Variables:**
```php
$_SESSION['installer'] = [
    'language' => 'es',
    'current_stage' => 4,
    'completed_stages' => [1, 2, 3],
    'data' => [
        'database' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'nexosupport_db',
            'username' => 'root',
            'password' => '***',
        ],
        'site' => [
            'name' => 'ISER Authentication',
            'url' => 'https://auth.iser.edu',
            'timezone' => 'America/Santiago',
            'language' => 'es',
        ],
        'admin' => [
            'username' => 'admin',
            'email' => 'admin@iser.edu',
            'password' => '***',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ],
        // ...
    ],
];
```

---

### 4.3 Real-Time Validation

**AJAX Endpoints:**

**Test Database Connection:**
```php
// POST /install/ajax/test-database
{
    "driver": "mysql",
    "host": "localhost",
    "port": 3306,
    "database": "nexosupport_db",
    "username": "root",
    "password": "secret"
}

// Response
{
    "success": true,
    "message": "Connection successful",
    "database_exists": true,
    "user_has_privileges": true
}
```

**Check Username Availability:**
```php
// POST /install/ajax/check-username
{
    "username": "admin"
}

// Response
{
    "available": true,
    "message": "Username is available"
}
```

**Send Test Email:**
```php
// POST /install/ajax/test-email
{
    "smtp_host": "smtp.gmail.com",
    "smtp_port": 587,
    // ...
    "test_email": "test@example.com"
}

// Response
{
    "success": true,
    "message": "Test email sent successfully"
}
```

---

### 4.4 Installation Process

**Main Installation Function:**
```php
class DatabaseInstaller
{
    public function install(array $config): InstallationResult
    {
        try {
            // 1. Create .env file
            $this->createEnvFile($config);

            // 2. Connect to database
            $this->connectDatabase($config['database']);

            // 3. Run SchemaInstaller
            $schemaInstaller = new SchemaInstaller($this->db);
            $schemaInstaller->installFromXML('/database/schema/schema.xml', function($progress) {
                $this->updateProgress($progress);
            });

            // 4. Insert initial data
            $this->insertRoles();
            $this->insertPermissions();
            $this->assignPermissionsToRoles();
            $this->insertSystemConfig($config['site']);

            // 5. Create admin user
            $this->createAdminUser($config['admin']);

            // 6. Finalize
            $this->writeInstalledMarker();
            $this->clearCache();

            return InstallationResult::success();

        } catch (\Exception $e) {
            Logger::error('Installation failed', ['error' => $e->getMessage()]);
            return InstallationResult::failure($e->getMessage());
        }
    }

    private function updateProgress(float $progress): void
    {
        // Update session with progress
        $_SESSION['installer']['progress'] = $progress;

        // Flush output to client (for real-time updates)
        flush();
    }
}
```

---

## 5. INTERNATIONALIZATION

### 5.1 Supported Languages

**Initial Release:**
- Spanish (es) - Default
- English (en)

**Future:**
- Portuguese (pt)
- French (fr)
- German (de)

---

### 5.2 Translation Structure

**File:** `/install/lang/es/installer.php`

```php
return [
    'welcome' => [
        'title' => 'Bienvenido al Instalador de NexoSupport',
        'subtitle' => 'Siga los pasos para instalar el sistema',
        'language' => 'Idioma',
        'system_info' => 'Información del Sistema',
        'version' => 'Versión',
        'continue' => 'Continuar',
    ],
    'requirements' => [
        'title' => 'Verificación de Requisitos',
        'php_version' => 'Versión de PHP',
        'extensions' => 'Extensiones PHP',
        'permissions' => 'Permisos de Directorios',
        'required' => 'Requerido',
        'recommended' => 'Recomendado',
        'passed' => 'Correcto',
        'failed' => 'Error',
        'warning' => 'Advertencia',
        'retry' => 'Reintentar Verificación',
    ],
    // ... more sections
];
```

---

### 5.3 Usage in Templates

```mustache
<!-- Mustache template -->
<h1>{{#__}}welcome.title{{/__}}</h1>
<p>{{#__}}welcome.subtitle{{/__}}</p>
<button>{{#__}}welcome.continue{{/__}}</button>
```

---

## 6. ERROR HANDLING

### 6.1 Error Categories

**1. Validation Errors:**
- Field-level errors (email invalid, password too weak)
- Form-level errors (passwords don't match)
- Display inline with field

**2. Connection Errors:**
- Database connection failed
- Email connection failed
- Display in alert box with details

**3. Installation Errors:**
- Table creation failed
- Permission denied
- Display with full log and option to retry

**4. System Errors:**
- PHP error
- Disk full
- Display technical error page with support info

---

### 6.2 Error Display

**Inline Validation:**
```html
<div class="form-group">
    <label>Email</label>
    <input type="email" class="is-invalid" value="notanemail">
    <div class="invalid-feedback">
        Please enter a valid email address
    </div>
</div>
```

**Alert Messages:**
```html
<div class="alert alert-danger">
    <strong>Database Connection Failed</strong>
    <p>Could not connect to MySQL server on localhost:3306</p>
    <details>
        <summary>Technical Details</summary>
        <pre>PDOException: SQLSTATE[HY000] [2002] Connection refused</pre>
    </details>
    <button class="btn btn-danger">Retry Connection</button>
</div>
```

---

## 7. POST-INSTALLATION

### 7.1 Security Recommendations

**Immediate Actions:**
- ⚠️ **DELETE /install/ directory** (automated or manual)
- ⚠️ **Change admin password** on first login
- ⚠️ **Review security settings** in admin panel

**Optional Actions:**
- Configure two-factor authentication
- Set up email notifications
- Configure backup schedule
- Review user roles and permissions

---

### 7.2 Installation Report

**Generate report with:**
- Installation timestamp
- System configuration summary
- Admin user details (username, email)
- Database details (driver, database name)
- Security settings summary
- Next steps checklist

**Formats:**
- **HTML** (view in browser)
- **PDF** (download)
- **TXT** (plain text)
- **JSON** (for programmatic access)

---

## 8. IMPLEMENTATION PLAN

### 8.1 Phase 1: Core Functionality (Week 1)

**Tasks:**
- [ ] Refactor installer architecture
- [ ] Create stage controllers (11 stages)
- [ ] Implement state management
- [ ] Create installer classes (Installer, RequirementsChecker, etc.)
- **Estimated:** 30 hours

---

### 8.2 Phase 2: UI Design & Templates (Week 1-2)

**Tasks:**
- [ ] Design modern UI (Figma/Sketch mockups)
- [ ] Create Mustache templates for all stages
- [ ] Implement responsive CSS
- [ ] Add transitions and animations
- **Estimated:** 20 hours

---

### 8.3 Phase 3: Real-Time Features (Week 2)

**Tasks:**
- [ ] AJAX endpoints (test connection, check username, test email)
- [ ] Real-time validation
- [ ] Progress bar updates
- [ ] Live preview features
- **Estimated:** 15 hours

---

### 8.4 Phase 4: Internationalization (Week 2)

**Tasks:**
- [ ] Extract all strings to lang files
- [ ] Create translations (es, en)
- [ ] Implement language switcher
- [ ] Test all stages in both languages
- **Estimated:** 10 hours

---

### 8.5 Phase 5: Testing & Polish (Week 3)

**Tasks:**
- [ ] Test all 11 stages end-to-end
- [ ] Test error scenarios
- [ ] Test on different browsers/devices
- [ ] Accessibility audit
- [ ] Performance optimization
- [ ] Documentation
- **Estimated:** 15 hours

---

**Total Estimated Effort:** 90 hours (2-3 weeks)

---

## 9. SUCCESS CRITERIA

### 9.1 Functional Requirements

**Must Have:**
- ✅ 11-stage installation process
- ✅ Requirements check before installation
- ✅ Real-time database connection test
- ✅ Real-time password strength check
- ✅ Real-time email test
- ✅ Progress bar during installation
- ✅ Installation verification
- ✅ Multi-language support (es, en)
- ✅ Responsive on all devices
- ✅ Accessible (WCAG 2.1 AA)
- ✅ Generates .env file correctly
- ✅ Creates all tables via SchemaInstaller

**Should Have:**
- ✅ Live preview of settings
- ✅ Installation report download
- ✅ Auto-delete /install/ directory option
- ✅ Detailed error messages with solutions

**Nice to Have:**
- Email installation report
- Video tutorials inline
- Chatbot help
- One-click demo installation

---

### 9.2 Performance Requirements

- **Load time:** < 2 seconds per stage
- **Installation time:** < 5 minutes for complete installation
- **AJAX response:** < 500ms for validation requests
- **No page reloads** during multi-stage process

---

### 9.3 Compatibility Requirements

- **Browsers:** Chrome, Firefox, Safari, Edge (latest 2 versions)
- **Devices:** Desktop (primary), tablet, mobile (supported)
- **PHP:** 8.1, 8.2, 8.3
- **Databases:** MySQL 5.7+, PostgreSQL 12+, SQLite 3.x

---

## 10. TECHNICAL CONSIDERATIONS

### 10.1 Security

**Input Validation:**
- Sanitize all user inputs
- Validate database credentials format
- Validate email addresses
- Check password strength

**File Security:**
- Validate uploaded files (if any)
- Restrict file types
- Check file sizes
- Prevent path traversal

**Post-Installation:**
- Recommend deleting /install/
- Set proper file permissions
- Generate strong secrets

---

### 10.2 Performance

**Optimization:**
- Minimal JavaScript (< 50 KB)
- Optimized CSS (< 30 KB)
- Image optimization
- Lazy loading of non-critical resources

**Caching:**
- No caching during installation (always fresh)
- Cache-bust CSS/JS with version query string

---

### 10.3 Error Recovery

**Retry Mechanisms:**
- Retry database connection
- Retry table creation on specific errors
- Resume installation from last successful step

**Rollback:**
- If installation fails, offer to rollback
- Delete partially created tables
- Remove .env file
- Clear installation marker

---

## 11. DOCUMENTATION

### 11.1 User Documentation

**Installation Guide:**
- Prerequisites
- Step-by-step installation instructions
- Screenshots of each stage
- Troubleshooting common issues
- Post-installation checklist

**Video Tutorials:**
- Complete installation walkthrough
- Common error solutions
- Advanced configuration

---

### 11.2 Developer Documentation

**Installer API:**
- How to add new installation stages
- How to extend installer
- How to customize installer UI

**Stage Development:**
- Create custom stages
- Validate stage data
- Add real-time checks

---

## 12. FUTURE ENHANCEMENTS

### 12.1 V1.1

- **Auto-updater:** Update installer independently
- **Plugin pre-installation:** Install plugins during setup
- **Theme selection:** Choose theme during installation
- **Demo data:** Option to install demo users/data

### 12.2 V1.2

- **CLI installer:** Command-line installation
- **Docker installer:** One-command Docker installation
- **Cloud installer:** Deploy to cloud platforms (AWS, Azure, GCP)

### 12.3 V2.0

- **Multi-tenancy setup:** Configure multi-tenant during installation
- **Cluster setup:** Install on multiple servers
- **Migration assistant:** Migrate from other systems

---

## 13. CONCLUSION

The redesigned installer will provide a **world-class installation experience** that is:
- ✅ **User-friendly** for non-technical users
- ✅ **Professional** with modern UI
- ✅ **Robust** with comprehensive checks
- ✅ **Internationalized** in multiple languages
- ✅ **Accessible** to all users

**Key Improvements over Current Installer:**
- ✅ Modern, responsive UI (+80% visual improvement)
- ✅ Real-time validation (+5 validation points)
- ✅ Enhanced security configuration (+4 new settings)
- ✅ Installation verification (new stage)
- ✅ Multi-language support (+4 languages planned)
- ✅ Better error handling (+200% clarity)

**Timeline:** 2-3 weeks (90 hours)
**Priority:** HIGH (required for V1.0)
**Dependencies:** None (can start immediately)

---

**Document Version:** 2.0
**Created:** 2025-11-13
**Status:** 📋 **Ready for implementation**

---

## APPENDIX A: Stage Checklist

| # | Stage | Template | Controller | Validation | i18n | Status |
|---|-------|----------|------------|------------|------|--------|
| 1 | Welcome | ☐ | ☐ | N/A | ☐ | 📋 Planned |
| 2 | Requirements | ☐ | ☐ | ☐ | ☐ | 📋 Planned |
| 3 | License | ☐ | ☐ | ☐ | ☐ | 📋 Planned |
| 4 | Database | ☐ | ☐ | ☐ | ☐ | 📋 Planned |
| 5 | Site Info | ☐ | ☐ | ☐ | ☐ | 📋 Planned |
| 6 | Admin User | ☐ | ☐ | ☐ | ☐ | 📋 Planned |
| 7 | Security | ☐ | ☐ | ☐ | ☐ | 📋 Planned |
| 8 | Email | ☐ | ☐ | ☐ | ☐ | 📋 Planned |
| 9 | Installation | ☐ | ☐ | N/A | ☐ | 📋 Planned |
| 10 | Verification | ☐ | ☐ | ☐ | ☐ | 📋 Planned |
| 11 | Finish | ☐ | ☐ | N/A | ☐ | 📋 Planned |

---

**End of Installer Specification**
