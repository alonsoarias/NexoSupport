# UPDATE SYSTEM SPECIFICATION - NexoSupport

**Project:** NexoSupport Authentication System
**Document Type:** Technical Specification
**Version:** 1.0
**Status:** 📋 Specification (0% Implemented)
**Date:** 2025-11-13
**Inspiration:** Moodle update system principles (NOT code)

---

## ⚠️ IMPORTANT DISCLAIMER

This system is **INSPIRED BY the philosophy and principles of Moodle**, but:

❌ **DO NOT copy code from Moodle**
❌ **DO NOT replicate Moodle's exact architecture**
❌ **DO NOT use Moodle's structure directly**

✅ **DO adopt proven concepts** (security, rollback, migration)
✅ **DO implement using NexoSupport's own architecture**
✅ **DO simplify for authentication/management context**
✅ **DO use our own names, structures, and code**

**Example:** Moodle has robust update system → NexoSupport needs robust update system with same goals (security, rollback, migrations) but implemented our way.

---

## EXECUTIVE SUMMARY

### System Purpose

Provide a **production-grade update system** for:
1. **Core system updates** (NexoSupport framework)
2. **Plugin updates** (installed plugins)
3. **Database migrations** (schema evolution)
4. **Safe rollback** (if updates fail)
5. **Zero-downtime updates** (when possible)

### Key Principles (from Moodle)

1. ✅ **Safety First** - Backups before updates, rollback on failure
2. ✅ **Automation** - Minimize manual intervention
3. ✅ **Transparency** - Clear logging of all actions
4. ✅ **Reliability** - Tested migration paths
5. ✅ **Compatibility** - Check dependencies before updating

---

## 1. SYSTEM ARCHITECTURE

### 1.1 Components Overview

```
┌──────────────────────────────────────────┐
│         Update Orchestrator              │
│  (Coordinates entire update process)     │
└───────────┬──────────────────────────────┘
            │
    ┌───────┴──────────┐
    │                  │
┌───▼─────┐      ┌────▼──────┐
│ Version │      │  Update   │
│ Manager │      │  Checker  │
└───┬─────┘      └────┬──────┘
    │                  │
    │            ┌─────▼──────┐
    │            │  Download  │
    │            │  Manager   │
    │            └─────┬──────┘
    │                  │
┌───▼──────────────────▼─────┐
│    Migration Executor      │
│  (Runs upgrade scripts)    │
└───────┬────────────────────┘
        │
    ┌───▼──────┐
    │  Backup  │
    │  Manager │
    └──────────┘
```

### 1.2 Core Classes

| Class | Location | Purpose |
|-------|----------|---------|
| `UpdateOrchestrator` | `/core/Update/UpdateOrchestrator.php` | Main coordinator |
| `VersionManager` | `/core/Update/VersionManager.php` | Version tracking |
| `UpdateChecker` | `/core/Update/UpdateChecker.php` | Check for updates |
| `DownloadManager` | `/core/Update/DownloadManager.php` | Download packages |
| `MigrationExecutor` | `/core/Update/MigrationExecutor.php` | Run migrations |
| `BackupManager` | `/core/Database/BackupManager.php` | Create backups |
| `RollbackManager` | `/core/Update/RollbackManager.php` | Rollback updates |

---

## 2. VERSION MANAGEMENT

### 2.1 Version Format

**Semantic Versioning 2.0.0:**
```
MAJOR.MINOR.PATCH[-PRERELEASE][+BUILD]

Examples:
- 1.0.0 (stable release)
- 1.1.0 (minor update)
- 2.0.0 (major update)
- 1.5.0-beta (pre-release)
- 1.5.0-rc.1 (release candidate)
- 1.5.0+20251113 (with build metadata)
```

**Version Code:** Numeric for easy comparison
```
MAJOR * 1000000 + MINOR * 1000 + PATCH

Examples:
- 1.0.0  → 1000000
- 1.5.3  → 1005003
- 2.0.0  → 2000000
```

### 2.2 Version File

**File:** `/core/version.php`

```php
<?php
/**
 * NexoSupport Core Version Information
 */

return [
    // Version number (semantic versioning)
    'version' => '1.5.0',

    // Version code (for database comparison)
    'version_code' => 1005000,

    // Release date
    'release_date' => '2025-11-13',

    // Minimum requirements
    'requires' => [
        'php' => '8.1.0',
        'mysql' => '5.7.0',
        'postgresql' => '12.0',
    ],

    // Branch (stable, beta, dev)
    'branch' => 'stable',

    // Build info
    'build' => '20251113',
];
```

### 2.3 Version Tracking Database

**Table:** `system_versions`

```sql
CREATE TABLE system_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    component VARCHAR(100) NOT NULL,      -- 'core' or plugin slug
    version_number VARCHAR(20) NOT NULL,  -- '1.5.0'
    version_code BIGINT UNSIGNED NOT NULL, -- 1005000
    applied_at INT UNSIGNED NOT NULL,
    description TEXT,
    migration_file VARCHAR(255),
    INDEX idx_component (component),
    INDEX idx_version_code (version_code)
);
```

### 2.4 Version Manager API

```php
class VersionManager
{
    /**
     * Get current core version
     */
    public function getCurrentVersion(): string
    {
        $versions = require __DIR__ . '/../version.php';
        return $versions['version'];
    }

    /**
     * Get installed version from database
     */
    public function getInstalledVersion(string $component = 'core'): ?string
    {
        $result = $this->db->queryOne(
            "SELECT version_number FROM system_versions
             WHERE component = ?
             ORDER BY version_code DESC LIMIT 1",
            [$component]
        );

        return $result['version_number'] ?? null;
    }

    /**
     * Register new version
     */
    public function registerVersion(
        string $component,
        string $version,
        int $versionCode,
        ?string $description = null,
        ?string $migrationFile = null
    ): void {
        $this->db->insert('system_versions', [
            'component' => $component,
            'version_number' => $version,
            'version_code' => $versionCode,
            'applied_at' => time(),
            'description' => $description,
            'migration_file' => $migrationFile,
        ]);

        Logger::info("Registered version {$version} for {$component}");
    }

    /**
     * Compare versions
     */
    public function compareVersions(string $v1, string $v2): int
    {
        return version_compare($v1, $v2);
    }

    /**
     * Check if update needed
     */
    public function needsUpdate(string $component = 'core'): bool
    {
        $current = $this->getCurrentVersion();
        $installed = $this->getInstalledVersion($component);

        return $this->compareVersions($current, $installed) > 0;
    }
}
```

---

## 3. UPDATE DETECTION

### 3.1 Update Sources

**Local Detection:**
```php
// Compare /core/version.php with database
$fileVersion = require 'core/version.php';
$dbVersion = VersionManager::getInstalledVersion('core');

if (version_compare($fileVersion['version'], $dbVersion) > 0) {
    // Update available locally
}
```

**Remote Detection:**
```php
// Check update server
$updateInfo = UpdateChecker::checkRemote('https://updates.nexosupport.com/api/check');

if ($updateInfo['latest_version'] > $currentVersion) {
    // Update available remotely
}
```

### 3.2 Update Information Structure

**Remote API Response:**
```json
{
  "component": "core",
  "current_version": "1.4.0",
  "latest_version": "1.5.0",
  "update_available": true,
  "download_url": "https://updates.nexosupport.com/releases/nexosupport-1.5.0.zip",
  "checksum": {
    "md5": "5d41402abc4b2a76b9719d911017c592",
    "sha256": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"
  },
  "size_bytes": 15728640,
  "release_date": "2025-11-13",
  "release_notes_url": "https://nexosupport.com/releases/1.5.0",
  "changelog": "## Version 1.5.0\n- New feature X\n- Bug fix Y\n- Security patch Z",
  "is_security_update": false,
  "is_critical": false,
  "requires": {
    "nexosupport": ">=1.0.0",
    "php": ">=8.1.0"
  },
  "compatibility": {
    "plugins": {
      "my-plugin": ">=2.0.0"
    }
  }
}
```

### 3.3 Update Checker Implementation

```php
class UpdateChecker
{
    private string $updateServerUrl;

    public function __construct(string $updateServerUrl)
    {
        $this->updateServerUrl = $updateServerUrl;
    }

    /**
     * Check for core updates
     */
    public function checkCoreUpdates(): ?array
    {
        $currentVersion = VersionManager::getCurrentVersion();

        $response = $this->makeRequest('/api/check', [
            'component' => 'core',
            'current_version' => $currentVersion,
            'php_version' => PHP_VERSION,
            'site_url' => Config::get('APP_URL'),
        ]);

        if ($response && $response['update_available']) {
            return $response;
        }

        return null;
    }

    /**
     * Check for plugin updates
     */
    public function checkPluginUpdates(string $pluginSlug, string $currentVersion): ?array
    {
        $response = $this->makeRequest('/api/check', [
            'component' => 'plugin',
            'slug' => $pluginSlug,
            'current_version' => $currentVersion,
        ]);

        return $response['update_available'] ? $response : null;
    }

    /**
     * Get all available updates
     */
    public function checkAllUpdates(): array
    {
        $updates = [];

        // Check core
        $coreUpdate = $this->checkCoreUpdates();
        if ($coreUpdate) {
            $updates['core'] = $coreUpdate;
        }

        // Check plugins
        $plugins = PluginManager::getInstalledPlugins();
        foreach ($plugins as $plugin) {
            $pluginUpdate = $this->checkPluginUpdates($plugin['slug'], $plugin['version']);
            if ($pluginUpdate) {
                $updates['plugins'][$plugin['slug']] = $pluginUpdate;
            }
        }

        return $updates;
    }

    private function makeRequest(string $endpoint, array $params): ?array
    {
        $url = $this->updateServerUrl . $endpoint;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            return json_decode($response, true);
        }

        return null;
    }
}
```

### 3.4 Automatic Update Checks

**Cron Job:** Run daily

```php
// cli/check_updates.php

$checker = new UpdateChecker(Config::get('UPDATE_SERVER_URL'));
$updates = $checker->checkAllUpdates();

if (!empty($updates)) {
    // Notify admin via email
    $mailer = new Mailer();
    $mailer->send(
        Config::get('ADMIN_EMAIL'),
        'Updates Available for NexoSupport',
        'updates_available',
        ['updates' => $updates]
    );

    // Store in database
    foreach ($updates as $component => $updateInfo) {
        UpdateNotification::create([
            'component' => $component,
            'current_version' => $updateInfo['current_version'],
            'latest_version' => $updateInfo['latest_version'],
            'is_security' => $updateInfo['is_security_update'],
            'notified_at' => time(),
        ]);
    }
}
```

---

## 4. UPDATE PROCESS

### 4.1 Pre-Update Checks

```
1. System Health Check
   ├── PHP version compatible
   ├── Database accessible
   ├── Disk space sufficient (>500MB free)
   ├── File permissions correct
   └── No system errors

2. Backup Verification
   ├── Backup directory writable
   ├── Sufficient disk space for backup
   └── Previous backups exist (optional)

3. Compatibility Check
   ├── Plugin compatibility with new version
   ├── Theme compatibility
   ├── No breaking changes (if MAJOR update)
   └── User confirmation for breaking changes

4. Dependency Check
   ├── Required PHP extensions available
   ├── Database version compatible
   └── No conflicting updates pending

5. Lock Check
   ├── No other update running
   └── Create update lock file
```

### 4.2 Update Workflow

```
┌─────────────────────┐
│  User Initiates     │
│  Update             │
└──────┬──────────────┘
       │
┌──────▼──────────────┐
│  Pre-Update Checks  │
│  (Exit if fail)     │
└──────┬──────────────┘
       │
┌──────▼──────────────┐
│  Enable Maintenance │
│  Mode               │
└──────┬──────────────┘
       │
┌──────▼──────────────┐
│  Create Backup      │
│  (Database + Files) │
└──────┬──────────────┘
       │
┌──────▼──────────────┐
│  Download Update    │
│  Package            │
└──────┬──────────────┘
       │
┌──────▼──────────────┐
│  Verify Package     │
│  (Checksum)         │
└──────┬──────────────┘
       │
┌──────▼──────────────┐
│  Extract Package    │
│  to Temp Directory  │
└──────┬──────────────┘
       │
┌──────▼──────────────┐
│  Run Pre-Update     │
│  Hooks              │
└──────┬──────────────┘
       │
┌──────▼──────────────┐
│  Execute Database   │
│  Migrations         │
└──────┬──────────────┘
       │       │
       │       ├─ Success
       │       │
       │       └─ Error ──┐
       │                  │
┌──────▼──────────────┐   │
│  Replace Core Files │   │
└──────┬──────────────┘   │
       │                  │
┌──────▼──────────────┐   │
│  Run Post-Update    │   │
│  Hooks              │   │
└──────┬──────────────┘   │
       │                  │
┌──────▼──────────────┐   │
│  Update Version     │   │
│  in Database        │   │
└──────┬──────────────┘   │
       │                  │
┌──────▼──────────────┐   │
│  Clear Caches       │   │
└──────┬──────────────┘   │
       │                  │
┌──────▼──────────────┐   │
│  Disable Maintenance│   │
│  Mode               │   │
└──────┬──────────────┘   │
       │                  │
┌──────▼──────────────┐   │
│  SUCCESS!           │   │
│  Log & Notify       │   │
└─────────────────────┘   │
                          │
                  ┌───────▼────────┐
                  │  ROLLBACK      │
                  │  - Restore DB  │
                  │  - Restore     │
                  │    Files       │
                  │  - Disable     │
                  │    Maintenance │
                  │  - Log Error   │
                  └────────────────┘
```

### 4.3 Update Orchestrator Implementation

```php
class UpdateOrchestrator
{
    private VersionManager $versionManager;
    private BackupManager $backupManager;
    private MigrationExecutor $migrationExecutor;
    private RollbackManager $rollbackManager;

    public function updateCore(array $updateInfo): bool
    {
        $updateId = $this->generateUpdateId();

        try {
            // 1. Pre-update checks
            $this->runPreUpdateChecks($updateInfo);

            // 2. Enable maintenance mode
            $this->enableMaintenanceMode();

            // 3. Create backup
            $backupPath = $this->backupManager->createFullBackup($updateId);
            Logger::info("Backup created: $backupPath");

            // 4. Download update
            $packagePath = $this->downloadUpdate($updateInfo);

            // 5. Verify package
            $this->verifyPackage($packagePath, $updateInfo['checksum']);

            // 6. Extract package
            $extractPath = $this->extractPackage($packagePath, $updateId);

            // 7. Run pre-update hooks
            HookManager::run('update.before', [
                'from_version' => $this->versionManager->getCurrentVersion(),
                'to_version' => $updateInfo['latest_version'],
            ]);

            // 8. Execute database migrations
            $this->migrationExecutor->execute(
                $this->versionManager->getCurrentVersion(),
                $updateInfo['latest_version']
            );

            // 9. Replace core files
            $this->replaceCoreFiles($extractPath);

            // 10. Run post-update hooks
            HookManager::run('update.after', [
                'from_version' => $this->versionManager->getCurrentVersion(),
                'to_version' => $updateInfo['latest_version'],
            ]);

            // 11. Update version in database
            $this->versionManager->registerVersion(
                'core',
                $updateInfo['latest_version'],
                $updateInfo['version_code'],
                'Core update to ' . $updateInfo['latest_version']
            );

            // 12. Clear caches
            $this->clearCaches();

            // 13. Disable maintenance mode
            $this->disableMaintenanceMode();

            Logger::info("Update to {$updateInfo['latest_version']} completed successfully");

            return true;

        } catch (Exception $e) {
            Logger::error("Update failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Rollback
            $this->rollbackManager->rollback($backupPath);

            // Disable maintenance mode
            $this->disableMaintenanceMode();

            throw new UpdateException("Update failed: " . $e->getMessage(), 0, $e);
        }
    }

    private function runPreUpdateChecks(array $updateInfo): void
    {
        // Check PHP version
        if (version_compare(PHP_VERSION, $updateInfo['requires']['php'], '<')) {
            throw new UpdateException(
                "PHP {$updateInfo['requires']['php']} required, you have " . PHP_VERSION
            );
        }

        // Check disk space
        $freeSpace = disk_free_space('/');
        if ($freeSpace < 500 * 1024 * 1024) { // 500MB
            throw new UpdateException("Insufficient disk space. Need at least 500MB free.");
        }

        // Check no update lock
        if (file_exists($this->getLockFile())) {
            throw new UpdateException("Another update is in progress");
        }

        // Create lock file
        file_put_contents($this->getLockFile(), time());
    }

    private function downloadUpdate(array $updateInfo): string
    {
        $downloader = new DownloadManager();
        $packagePath = $downloader->download(
            $updateInfo['download_url'],
            '/tmp/nexosupport-update-' . time() . '.zip'
        );

        return $packagePath;
    }

    private function verifyPackage(string $packagePath, array $checksum): void
    {
        $actualMd5 = md5_file($packagePath);

        if ($actualMd5 !== $checksum['md5']) {
            throw new UpdateException("Package checksum mismatch. Download may be corrupted.");
        }

        Logger::info("Package verified successfully");
    }

    private function extractPackage(string $packagePath, string $updateId): string
    {
        $extractPath = '/tmp/nexosupport-update-' . $updateId;

        $zip = new ZipArchive();
        if ($zip->open($packagePath) !== true) {
            throw new UpdateException("Failed to open update package");
        }

        $zip->extractTo($extractPath);
        $zip->close();

        return $extractPath;
    }

    private function replaceCoreFiles(string $extractPath): void
    {
        // Copy files from extract path to core
        // Preserve: .env, /var/logs/, /var/cache/, /modules/plugins/

        $filesToReplace = [
            'core/',
            'public_html/',
            'resources/',
            'database/schema/',
        ];

        foreach ($filesToReplace as $dir) {
            FileManager::copyDirectory(
                $extractPath . '/' . $dir,
                __DIR__ . '/../../' . $dir
            );
        }

        Logger::info("Core files replaced successfully");
    }

    private function clearCaches(): void
    {
        // Clear all caches
        FileManager::deleteDirectory('/var/cache/');
        mkdir('/var/cache/', 0755, true);

        Logger::info("Caches cleared");
    }

    private function enableMaintenanceMode(): void
    {
        file_put_contents(__DIR__ . '/../../MAINTENANCE', time());
        Logger::info("Maintenance mode enabled");
    }

    private function disableMaintenanceMode(): void
    {
        @unlink(__DIR__ . '/../../MAINTENANCE');
        @unlink($this->getLockFile());
        Logger::info("Maintenance mode disabled");
    }

    private function getLockFile(): string
    {
        return __DIR__ . '/../../UPDATE_LOCK';
    }

    private function generateUpdateId(): string
    {
        return 'update_' . time() . '_' . bin2hex(random_bytes(4));
    }
}
```

---

## 5. DATABASE MIGRATIONS

### 5.1 Migration Files

**Location:** `/database/upgrades/`

**Naming:** `upgrade_{from}_{to}.xml`

Examples:
- `upgrade_1.0.0_1.1.0.xml`
- `upgrade_1.1.0_1.2.0.xml`
- `upgrade_1.5.0_2.0.0.xml`

### 5.2 Migration XML Format

```xml
<?xml version="1.0" encoding="UTF-8"?>
<upgrade from="1.4.0" to="1.5.0">
    <description>Add notification system and user preferences enhancements</description>

    <!-- Database Schema Changes -->
    <database>
        <!-- Create new tables -->
        <create_table name="notifications">
            <columns>
                <column name="id" type="BIGINT UNSIGNED" autoincrement="true" primary="true"/>
                <column name="user_id" type="INT UNSIGNED" null="false"/>
                <column name="title" type="VARCHAR" length="255"/>
                <column name="message" type="TEXT"/>
                <column name="read_at" type="INT UNSIGNED"/>
                <column name="created_at" type="INT UNSIGNED" null="false"/>
            </columns>
            <indexes>
                <index name="idx_user_id" columns="user_id"/>
                <index name="idx_read_at" columns="read_at"/>
            </indexes>
            <foreignKeys>
                <foreignKey column="user_id" references="users(id)" onDelete="CASCADE"/>
            </foreignKeys>
        </create_table>

        <!-- Modify existing tables -->
        <modify_table name="users">
            <!-- Add new column -->
            <add_column name="avatar_url" type="VARCHAR" length="255" after="email"/>

            <!-- Modify existing column -->
            <modify_column name="bio" new_type="TEXT" new_length="2000"/>

            <!-- Add index -->
            <add_index name="idx_avatar" columns="avatar_url"/>

            <!-- Drop column (with caution!) -->
            <drop_column name="deprecated_field"/>
        </modify_table>

        <!-- Drop table (rare, with extreme caution!) -->
        <drop_table name="obsolete_table"/>
    </database>

    <!-- Data Migration -->
    <data>
        <!-- Update existing data -->
        <update table="users"
                set="avatar_url='/assets/images/default-avatar.png'"
                where="avatar_url IS NULL"/>

        <!-- Insert new data -->
        <insert table="config">
            <row>
                <config_key>notifications.enabled</config_key>
                <config_value>true</config_value>
                <config_type>bool</config_type>
                <category>notifications</category>
            </row>
        </insert>

        <!-- Delete old data -->
        <delete table="sessions" where="last_activity < {{TIMESTAMP}}-2592000"/>
    </data>

    <!-- Custom PHP Script (if XML not sufficient) -->
    <custom>
        <script>scripts/upgrade_1.4_to_1.5.php</script>
    </custom>

    <!-- Post-migration cleanup -->
    <cleanup>
        <!-- Remove old files -->
        <delete_file>public_html/old_file.php</delete_file>
        <delete_directory>modules/deprecated/</delete_directory>

        <!-- Clear specific caches -->
        <clear_cache>template_cache</clear_cache>
    </cleanup>
</upgrade>
```

### 5.3 Migration Executor

```php
class MigrationExecutor
{
    private Database $db;
    private SchemaInstaller $schemaInstaller;

    public function execute(string $fromVersion, string $toVersion): void
    {
        Logger::info("Executing migrations from $fromVersion to $toVersion");

        // Find all migration files needed
        $migrationFiles = $this->findMigrationPath($fromVersion, $toVersion);

        foreach ($migrationFiles as $file) {
            $this->executeMigrationFile($file);
        }

        Logger::info("All migrations executed successfully");
    }

    private function findMigrationPath(string $from, string $to): array
    {
        // Simple case: direct migration exists
        $directFile = "/database/upgrades/upgrade_{$from}_{$to}.xml";
        if (file_exists($directFile)) {
            return [$directFile];
        }

        // Complex case: need intermediate migrations
        // e.g., 1.0.0 → 1.5.0 might need 1.0→1.1, 1.1→1.2, ..., 1.4→1.5

        $path = $this->findShortestPath($from, $to);

        if (empty($path)) {
            throw new MigrationException("No migration path found from $from to $to");
        }

        return $path;
    }

    private function executeMigrationFile(string $file): void
    {
        Logger::info("Executing migration: $file");

        $xml = simplexml_load_file($file);

        // Execute database changes
        if (isset($xml->database)) {
            $this->executeDatabaseChanges($xml->database);
        }

        // Execute data migrations
        if (isset($xml->data)) {
            $this->executeDataMigrations($xml->data);
        }

        // Execute custom PHP script
        if (isset($xml->custom->script)) {
            $this->executeCustomScript((string)$xml->custom->script);
        }

        // Execute cleanup
        if (isset($xml->cleanup)) {
            $this->executeCleanup($xml->cleanup);
        }

        Logger::info("Migration completed: $file");
    }

    private function executeDatabaseChanges($databaseXml): void
    {
        foreach ($databaseXml->children() as $change) {
            switch ($change->getName()) {
                case 'create_table':
                    $this->schemaInstaller->createTableFromXML($change);
                    break;

                case 'modify_table':
                    $this->modifyTable($change);
                    break;

                case 'drop_table':
                    $tableName = (string)$change['name'];
                    $this->db->execute("DROP TABLE IF EXISTS `$tableName`");
                    Logger::warning("Dropped table: $tableName");
                    break;
            }
        }
    }

    private function modifyTable($tableXml): void
    {
        $tableName = (string)$tableXml['name'];

        foreach ($tableXml->children() as $modification) {
            switch ($modification->getName()) {
                case 'add_column':
                    $this->addColumn($tableName, $modification);
                    break;

                case 'modify_column':
                    $this->modifyColumn($tableName, $modification);
                    break;

                case 'drop_column':
                    $columnName = (string)$modification['name'];
                    $this->db->execute("ALTER TABLE `$tableName` DROP COLUMN `$columnName`");
                    break;

                case 'add_index':
                    $this->addIndex($tableName, $modification);
                    break;
            }
        }
    }

    private function executeDataMigrations($dataXml): void
    {
        foreach ($dataXml->children() as $operation) {
            switch ($operation->getName()) {
                case 'update':
                    $this->executeUpdate($operation);
                    break;

                case 'insert':
                    $this->executeInsert($operation);
                    break;

                case 'delete':
                    $this->executeDelete($operation);
                    break;
            }
        }
    }

    private function executeCustomScript(string $scriptPath): void
    {
        $fullPath = __DIR__ . '/../../' . $scriptPath;

        if (!file_exists($fullPath)) {
            throw new MigrationException("Custom script not found: $scriptPath");
        }

        Logger::info("Executing custom script: $scriptPath");

        // Include and execute script
        // Script should return true on success, throw exception on failure
        $result = require $fullPath;

        if ($result !== true) {
            throw new MigrationException("Custom script failed: $scriptPath");
        }
    }

    private function executeCleanup($cleanupXml): void
    {
        foreach ($cleanupXml->children() as $cleanup) {
            switch ($cleanup->getName()) {
                case 'delete_file':
                    $file = __DIR__ . '/../../' . (string)$cleanup;
                    if (file_exists($file)) {
                        unlink($file);
                        Logger::info("Deleted file: $file");
                    }
                    break;

                case 'delete_directory':
                    $dir = __DIR__ . '/../../' . (string)$cleanup;
                    if (is_dir($dir)) {
                        FileManager::deleteDirectory($dir);
                        Logger::info("Deleted directory: $dir");
                    }
                    break;

                case 'clear_cache':
                    // Clear specific cache
                    break;
            }
        }
    }
}
```

---

## 6. BACKUP & ROLLBACK

### 6.1 Backup Strategy

**What to Backup:**
1. Database (full dump)
2. Core files (/core/, /public_html/, /resources/)
3. Configuration files (.env)
4. Plugin files (optional, usually not changed during core update)

**Backup Location:** `/var/backups/`

**Backup Naming:** `backup_{updateId}_{timestamp}.tar.gz`

### 6.2 BackupManager Implementation

```php
class BackupManager
{
    private string $backupDir = '/var/backups';

    public function createFullBackup(string $updateId): string
    {
        $timestamp = time();
        $backupName = "backup_{$updateId}_{$timestamp}";
        $backupPath = "{$this->backupDir}/$backupName";

        mkdir($backupPath, 0755, true);

        // 1. Backup database
        $this->backupDatabase($backupPath . '/database.sql');

        // 2. Backup files
        $this->backupFiles($backupPath . '/files');

        // 3. Create archive
        $archivePath = "$backupPath.tar.gz";
        $this->createArchive($backupPath, $archivePath);

        // 4. Cleanup temp directory
        FileManager::deleteDirectory($backupPath);

        // 5. Create backup metadata
        $this->saveBackupMetadata($archivePath, [
            'update_id' => $updateId,
            'timestamp' => $timestamp,
            'version' => VersionManager::getCurrentVersion(),
        ]);

        Logger::info("Full backup created: $archivePath");

        return $archivePath;
    }

    private function backupDatabase(string $outputFile): void
    {
        $dbConfig = Config::getDatabaseConfig();

        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s',
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['password']),
            escapeshellarg($dbConfig['database']),
            escapeshellarg($outputFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new BackupException("Database backup failed");
        }

        Logger::info("Database backed up: $outputFile");
    }

    private function backupFiles(string $outputDir): void
    {
        $filesToBackup = [
            'core/',
            'public_html/',
            'resources/',
            '.env',
        ];

        foreach ($filesToBackup as $path) {
            $sourcePath = __DIR__ . '/../../' . $path;
            $destPath = $outputDir . '/' . $path;

            if (is_file($sourcePath)) {
                copy($sourcePath, $destPath);
            } elseif (is_dir($sourcePath)) {
                FileManager::copyDirectory($sourcePath, $destPath);
            }
        }

        Logger::info("Files backed up to: $outputDir");
    }

    private function createArchive(string $sourcePath, string $archivePath): void
    {
        $phar = new PharData($archivePath);
        $phar->buildFromDirectory($sourcePath);
        $phar->compress(Phar::GZ);

        Logger::info("Archive created: $archivePath");
    }

    public function listBackups(): array
    {
        $backups = [];
        $files = glob("{$this->backupDir}/backup_*.tar.gz");

        foreach ($files as $file) {
            $metadata = $this->loadBackupMetadata($file);
            $backups[] = array_merge([
                'file' => $file,
                'size' => filesize($file),
            ], $metadata);
        }

        return $backups;
    }

    private function saveBackupMetadata(string $backupPath, array $metadata): void
    {
        $metadataFile = $backupPath . '.meta.json';
        file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
    }

    private function loadBackupMetadata(string $backupPath): array
    {
        $metadataFile = $backupPath . '.meta.json';
        if (file_exists($metadataFile)) {
            return json_decode(file_get_contents($metadataFile), true);
        }
        return [];
    }

    public function cleanOldBackups(int $keepDays = 30): void
    {
        $cutoff = time() - ($keepDays * 86400);
        $files = glob("{$this->backupDir}/backup_*.tar.gz");

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                @unlink($file . '.meta.json');
                Logger::info("Deleted old backup: $file");
            }
        }
    }
}
```

### 6.3 RollbackManager Implementation

```php
class RollbackManager
{
    public function rollback(string $backupPath): void
    {
        Logger::warning("Starting rollback from: $backupPath");

        try {
            // 1. Extract backup
            $extractPath = $this->extractBackup($backupPath);

            // 2. Restore database
            $this->restoreDatabase($extractPath . '/database.sql');

            // 3. Restore files
            $this->restoreFiles($extractPath . '/files');

            // 4. Clear caches
            FileManager::deleteDirectory('/var/cache/');

            // 5. Cleanup
            FileManager::deleteDirectory($extractPath);

            Logger::info("Rollback completed successfully");

        } catch (Exception $e) {
            Logger::critical("Rollback failed", [
                'error' => $e->getMessage()
            ]);

            throw new RollbackException("Rollback failed: " . $e->getMessage(), 0, $e);
        }
    }

    private function extractBackup(string $backupPath): string
    {
        $extractPath = '/tmp/rollback_' . time();
        mkdir($extractPath, 0755, true);

        $phar = new PharData($backupPath);
        $phar->extractTo($extractPath);

        return $extractPath;
    }

    private function restoreDatabase(string $sqlFile): void
    {
        $dbConfig = Config::getDatabaseConfig();

        $command = sprintf(
            'mysql -h %s -u %s -p%s %s < %s',
            escapeshellarg($dbConfig['host']),
            escapeshellarg($dbConfig['username']),
            escapeshellarg($dbConfig['password']),
            escapeshellarg($dbConfig['database']),
            escapeshellarg($sqlFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RollbackException("Database restore failed");
        }

        Logger::info("Database restored from: $sqlFile");
    }

    private function restoreFiles(string $filesPath): void
    {
        $filesToRestore = [
            'core/',
            'public_html/',
            'resources/',
            '.env',
        ];

        foreach ($filesToRestore as $path) {
            $sourcePath = $filesPath . '/' . $path;
            $destPath = __DIR__ . '/../../' . $path;

            if (is_file($sourcePath)) {
                copy($sourcePath, $destPath);
            } elseif (is_dir($sourcePath)) {
                FileManager::deleteDirectory($destPath);
                FileManager::copyDirectory($sourcePath, $destPath);
            }
        }

        Logger::info("Files restored from: $filesPath");
    }
}
```

---

## 7. ADMIN UI

### 7.1 Updates Dashboard

**Location:** `/admin/updates`

**Features:**
- List available updates (core + plugins)
- Show update details (version, changelog, size)
- One-click update button
- View update history
- Rollback to previous version

**Screenshot Mockup:**

```
╔══════════════════════════════════════════════════════════════╗
║  Updates Available                                           ║
╠══════════════════════════════════════════════════════════════╣
║                                                              ║
║  ⚠️ Core Update Available                                   ║
║  Current: 1.4.0  →  Latest: 1.5.0                          ║
║                                                              ║
║  📋 What's New:                                             ║
║  - New notification system                                   ║
║  - Enhanced user preferences                                 ║
║  - Security improvements                                     ║
║                                                              ║
║  📦 Size: 15 MB  |  🔒 Security: No  |  ⚡ Critical: No   ║
║                                                              ║
║  [View Changelog]  [Update Now]                             ║
║                                                              ║
╟──────────────────────────────────────────────────────────────╢
║                                                              ║
║  Plugin Updates (2)                                          ║
║                                                              ║
║  📦 my-plugin: 1.0.0 → 1.1.0       [Update]                ║
║  📦 another-plugin: 2.3.0 → 2.4.0  [Update]                ║
║                                                              ║
╟──────────────────────────────────────────────────────────────╢
║                                                              ║
║  Update History                                              ║
║                                                              ║
║  ✓ 2025-11-10 | 1.3.0 → 1.4.0 | Success                   ║
║  ✓ 2025-10-15 | 1.2.0 → 1.3.0 | Success                   ║
║  ✗ 2025-09-20 | 1.1.0 → 1.2.0 | Failed (Rolled back)      ║
║                                                              ║
║  [View All]                                                  ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
```

### 7.2 Update Progress UI

**Real-time Progress:**

```
╔══════════════════════════════════════════════════════════════╗
║  Updating to Version 1.5.0                                   ║
╠══════════════════════════════════════════════════════════════╣
║                                                              ║
║  [█████████████████████░░░░░░░░░░]  65%                    ║
║                                                              ║
║  Current Step: Executing database migrations                 ║
║                                                              ║
║  Progress:                                                   ║
║  ✓ Pre-update checks completed                              ║
║  ✓ Maintenance mode enabled                                 ║
║  ✓ Backup created (150 MB)                                  ║
║  ✓ Update package downloaded                                ║
║  ✓ Package verified                                          ║
║  ⏳ Executing migrations... (3/5 completed)                 ║
║  ⏸️ Replacing core files                                     ║
║  ⏸️ Clearing caches                                          ║
║  ⏸️ Finalizing update                                        ║
║                                                              ║
║  ⚠️ Do not close this window or refresh the page           ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
```

### 7.3 Controller Implementation

```php
class UpdateController
{
    public function index(): string
    {
        $checker = new UpdateChecker(Config::get('UPDATE_SERVER_URL'));
        $updates = $checker->checkAllUpdates();

        $updateHistory = $this->getUpdateHistory();

        return MustacheRenderer::getInstance()->renderWithLayout(
            'admin/updates/index',
            [
                'updates' => $updates,
                'history' => $updateHistory,
            ],
            'admin'
        );
    }

    public function updateCore(): string
    {
        $updateInfo = $this->getLatestCoreUpdate();

        // Return SSE endpoint for progress
        return json_encode([
            'status' => 'started',
            'progress_url' => '/admin/updates/progress/' . $updateId
        ]);
    }

    public function progress(string $updateId): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');

        $orchestrator = new UpdateOrchestrator();

        $orchestrator->setProgressCallback(function($message, $percent) {
            echo "data: " . json_encode([
                'message' => $message,
                'percent' => $percent
            ]) . "\n\n";
            flush();
        });

        try {
            $orchestrator->updateCore($updateInfo);

            echo "data: " . json_encode([
                'status' => 'complete',
                'message' => 'Update completed successfully!'
            ]) . "\n\n";

        } catch (Exception $e) {
            echo "data: " . json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]) . "\n\n";
        }
    }

    private function getUpdateHistory(): array
    {
        return $this->db->query("
            SELECT * FROM system_versions
            WHERE component = 'core'
            ORDER BY applied_at DESC
            LIMIT 10
        ");
    }
}
```

---

## 8. CLI TOOL

### 8.1 Update CLI

**File:** `/cli/update.php`

```php
#!/usr/bin/env php
<?php
/**
 * NexoSupport Update CLI Tool
 *
 * Usage:
 *   php cli/update.php check           - Check for updates
 *   php cli/update.php list             - List available updates
 *   php cli/update.php core             - Update core
 *   php cli/update.php plugin:{slug}    - Update specific plugin
 *   php cli/update.php all              - Update everything
 *   php cli/update.php rollback         - Rollback last update
 *   php cli/update.php history          - Show update history
 */

require __DIR__ . '/../vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Core\Update\UpdateChecker;
use ISER\Core\Update\UpdateOrchestrator;
use ISER\Core\Update\RollbackManager;

$bootstrap = new Bootstrap();
$bootstrap->init();

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'check':
        checkForUpdates();
        break;

    case 'list':
        listAvailableUpdates();
        break;

    case 'core':
        updateCore();
        break;

    case 'all':
        updateAll();
        break;

    case 'rollback':
        rollbackLastUpdate();
        break;

    case 'history':
        showUpdateHistory();
        break;

    case 'help':
    default:
        showHelp();
        break;
}

function checkForUpdates(): void
{
    echo "Checking for updates...\n";

    $checker = new UpdateChecker(Config::get('UPDATE_SERVER_URL'));
    $updates = $checker->checkAllUpdates();

    if (empty($updates)) {
        echo "✓ No updates available. You're running the latest version.\n";
        return;
    }

    echo "Updates available:\n\n";

    if (isset($updates['core'])) {
        $u = $updates['core'];
        echo "  CORE: {$u['current_version']} → {$u['latest_version']}\n";

        if ($u['is_security_update']) {
            echo "  ⚠️  SECURITY UPDATE - Install immediately!\n";
        }
    }

    if (isset($updates['plugins'])) {
        echo "\n  PLUGINS:\n";
        foreach ($updates['plugins'] as $slug => $u) {
            echo "    - $slug: {$u['current_version']} → {$u['latest_version']}\n";
        }
    }

    echo "\nRun 'php cli/update.php core' to update the core system.\n";
}

function updateCore(): void
{
    echo "Starting core update...\n\n";

    $checker = new UpdateChecker(Config::get('UPDATE_SERVER_URL'));
    $coreUpdate = $checker->checkCoreUpdates();

    if (!$coreUpdate) {
        echo "✓ Already running the latest version.\n";
        return;
    }

    echo "Updating from {$coreUpdate['current_version']} to {$coreUpdate['latest_version']}...\n";

    // Confirm
    echo "\nThis will:\n";
    echo "  1. Create a backup of your database and files\n";
    echo "  2. Download the update package\n";
    echo "  3. Run database migrations\n";
    echo "  4. Replace core files\n";
    echo "  5. Clear caches\n\n";

    echo "Continue? (yes/no): ";
    $confirm = trim(fgets(STDIN));

    if ($confirm !== 'yes') {
        echo "Update cancelled.\n";
        return;
    }

    // Run update
    $orchestrator = new UpdateOrchestrator();

    $orchestrator->setProgressCallback(function($message, $percent) {
        echo sprintf("[%3d%%] %s\n", $percent, $message);
    });

    try {
        $orchestrator->updateCore($coreUpdate);
        echo "\n✓ Update completed successfully!\n";

    } catch (Exception $e) {
        echo "\n✗ Update failed: " . $e->getMessage() . "\n";
        echo "System has been rolled back to previous version.\n";
        exit(1);
    }
}

function rollbackLastUpdate(): void
{
    echo "⚠️  WARNING: This will restore your system to the previous version.\n";
    echo "All changes since the last update will be lost.\n\n";

    echo "Continue? (yes/no): ";
    $confirm = trim(fgets(STDIN));

    if ($confirm !== 'yes') {
        echo "Rollback cancelled.\n";
        return;
    }

    $backupManager = new BackupManager();
    $backups = $backupManager->listBackups();

    if (empty($backups)) {
        echo "✗ No backups found.\n";
        return;
    }

    $latestBackup = $backups[0];

    echo "Rolling back to backup from " . date('Y-m-d H:i:s', $latestBackup['timestamp']) . "...\n";

    $rollbackManager = new RollbackManager();

    try {
        $rollbackManager->rollback($latestBackup['file']);
        echo "✓ Rollback completed successfully.\n";

    } catch (Exception $e) {
        echo "✗ Rollback failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function showHelp(): void
{
    echo <<<HELP
NexoSupport Update CLI Tool

Usage:
  php cli/update.php <command> [options]

Commands:
  check             Check for available updates
  list              List all available updates
  core              Update core system
  plugin:{slug}     Update specific plugin
  all               Update core and all plugins
  rollback          Rollback to previous version
  history           Show update history
  help              Show this help message

Examples:
  php cli/update.php check
  php cli/update.php core
  php cli/update.php plugin:my-plugin
  php cli/update.php all --yes  (skip confirmation)

HELP;
}
```

---

## 9. TESTING

### 9.1 Test Scenarios

**Test Update Flow:**
1. Install version 1.0.0
2. Update to 1.1.0
3. Verify database migrations ran
4. Verify files updated
5. Verify functionality works
6. Rollback to 1.0.0
7. Verify database reverted
8. Verify files reverted

**Test Update Failure:**
1. Install version 1.0.0
2. Simulate update failure (corrupt package)
3. Verify automatic rollback
4. Verify system still on 1.0.0
5. Verify functionality still works

**Test Multi-Version Jump:**
1. Install version 1.0.0
2. Update to 1.5.0 (skipping 1.1-1.4)
3. Verify all intermediate migrations run
4. Verify final version correct

### 9.2 Integration Tests

```php
class UpdateSystemTest extends TestCase
{
    public function testCoreUpdateFlow(): void
    {
        // 1. Create test environment
        $this->installVersion('1.0.0');

        // 2. Run update
        $orchestrator = new UpdateOrchestrator();
        $result = $orchestrator->updateCore([
            'latest_version' => '1.1.0',
            'download_url' => 'file:///tmp/test-update-1.1.0.zip',
            // ... other update info
        ]);

        // 3. Verify
        $this->assertTrue($result);
        $this->assertEquals('1.1.0', VersionManager::getCurrentVersion());
        $this->assertTableExists('new_table_in_1_1_0');
    }

    public function testRollbackOnFailure(): void
    {
        $this->installVersion('1.0.0');

        $orchestrator = new UpdateOrchestrator();

        try {
            $orchestrator->updateCore([
                'latest_version' => '1.1.0',
                'download_url' => 'file:///tmp/corrupt-update.zip', // Corrupt
            ]);

            $this->fail('Expected UpdateException');

        } catch (UpdateException $e) {
            // Verify rollback happened
            $this->assertEquals('1.0.0', VersionManager::getCurrentVersion());
            $this->assertFalse($this->tableExists('new_table_in_1_1_0'));
        }
    }
}
```

---

## 10. SECURITY CONSIDERATIONS

### 10.1 Update Server Security

**HTTPS Only:**
- All update checks and downloads over HTTPS
- Verify SSL certificates

**Authentication:**
- Optional API key for update server
- License validation for premium updates

**Signed Updates:**
- Digital signatures for update packages
- Verify signature before installation

### 10.2 Update Package Security

**Checksum Verification:**
- MD5 and SHA256 checksums
- Reject packages with mismatched checksums

**Code Scanning:**
- Optional: Scan for known malware signatures
- Scan for suspicious code patterns

**Sandboxed Extraction:**
- Extract to temporary directory
- Validate contents before moving to production

### 10.3 Permission Checks

**File Permissions:**
- Verify write permissions before update
- Restore proper permissions after update

**User Permissions:**
- Only admins can trigger updates
- Log all update attempts

---

## 11. COMPLETION ROADMAP

### 11.1 Phase 1: Foundation (Week 1-2, 40 hours)

**Tasks:**
- Create VersionManager class
- Create UpdateChecker class
- Create database migrations table
- Basic update detection
- Admin UI for viewing updates

**Deliverables:**
- Can detect available updates
- Can show update info in admin panel

---

### 11.2 Phase 2: Core Update (Week 3-5, 60 hours)

**Tasks:**
- Create UpdateOrchestrator
- Create DownloadManager
- Create MigrationExecutor
- Create BackupManager
- Create RollbackManager
- Implement full update flow
- Add progress callbacks

**Deliverables:**
- Can update core system
- Automatic backup before update
- Automatic rollback on failure

---

### 11.3 Phase 3: UI & UX (Week 6, 20 hours)

**Tasks:**
- Create update dashboard UI
- Create progress UI (real-time)
- Create update history view
- Create rollback UI

**Deliverables:**
- Professional admin interface
- Real-time progress feedback
- Easy rollback option

---

### 11.4 Phase 4: CLI & Automation (Week 7, 16 hours)

**Tasks:**
- Create update CLI tool
- Add automatic update checks (cron)
- Add email notifications
- Add update scheduling

**Deliverables:**
- CLI for server-side updates
- Automatic update notifications
- Scheduled updates (optional)

---

### 11.5 Phase 5: Plugin Updates (Week 8, 24 hours)

**Tasks:**
- Extend update system for plugins
- Plugin update UI
- Plugin rollback
- Plugin dependency checking

**Deliverables:**
- Can update plugins
- Dependency resolution
- Plugin-specific rollback

---

### 11.6 Total Effort

**Total Development Time:** 160 hours (~4 months part-time)

**Team:** 1-2 developers

**Priority:** HIGH - Essential for production system

---

## 12. SUCCESS CRITERIA

### 12.1 Must Have ✅

- [ ] Detect core updates
- [ ] Download updates securely
- [ ] Create backups before update
- [ ] Execute database migrations
- [ ] Replace core files safely
- [ ] Automatic rollback on failure
- [ ] Admin UI for updates
- [ ] Update history tracking
- [ ] Email notifications
- [ ] CLI tool for updates

### 12.2 Should Have ✅

- [ ] Plugin update system
- [ ] Real-time progress UI
- [ ] Multiple version jump support
- [ ] Update scheduling
- [ ] Manual rollback option
- [ ] Update logs

### 12.3 Nice to Have ✅

- [ ] Zero-downtime updates (advanced)
- [ ] Automatic updates for patches
- [ ] Update marketplace integration
- [ ] Staged rollouts (canary)
- [ ] Delta updates (only changed files)

---

## 13. CONCLUSION

### 13.1 Summary

This specification defines a **production-grade update system** for NexoSupport, inspired by proven principles from Moodle but implemented with NexoSupport's own architecture.

### 13.2 Key Features

✅ **Safe** - Backups, rollback, verification
✅ **Automated** - One-click updates
✅ **Transparent** - Detailed logging
✅ **Reliable** - Tested migration paths
✅ **User-Friendly** - Admin UI + CLI

### 13.3 Implementation Status

**Current:** 0% (Specification only)
**Target:** 100% functional update system
**Effort:** 160 hours over 8 weeks
**Priority:** HIGH

### 13.4 Recommendation

✅ **IMPLEMENT THIS SYSTEM**

An update system is **critical** for any production application. Without it:
- Manual updates are error-prone
- Database migrations are risky
- No rollback capability
- Poor user experience

**With this system:**
- Professional, reliable updates
- Safe migration path
- Happy users
- Production-ready platform

---

**Document Version:** 1.0
**Status:** Specification Complete - Ready for Implementation
**Next Steps:** Begin Phase 1 (Foundation)
**Estimated Completion:** 2 months (part-time development)

---

## APPENDICES

### Appendix A: Moodle Principles Adopted (NOT Code)

**Principles from Moodle that inspired this design:**

1. **Version Tracking** - Detailed version history in database
2. **Migration Scripts** - XML-based upgrade scripts
3. **Backup Before Update** - Always backup first
4. **Rollback Capability** - Can revert if problems
5. **Component Updates** - Core + plugins separately
6. **Automatic Detection** - Check for updates automatically
7. **Safe Defaults** - Conservative, safe approach

**What we DID NOT do:**
- Copy Moodle's code
- Use Moodle's class names
- Replicate Moodle's exact architecture
- Use Moodle's file structure

**What we DID do:**
- Learn from proven patterns
- Adapt to our context (authentication system)
- Implement with our own architecture
- Simplify for our use case

### Appendix B: Version Comparison Examples

```php
version_compare('1.0.0', '1.1.0')  // Returns -1 (1.0.0 < 1.1.0)
version_compare('2.0.0', '1.9.0')  // Returns 1  (2.0.0 > 1.9.0)
version_compare('1.5.0', '1.5.0')  // Returns 0  (equal)

version_compare('1.0.0-beta', '1.0.0')  // Returns -1 (beta < stable)
```

### Appendix C: Migration Path Finding

**Example:** Upgrade from 1.0.0 to 1.5.0

**Direct Path:** `upgrade_1.0.0_1.5.0.xml` (if exists)

**Indirect Path:**
- `upgrade_1.0.0_1.1.0.xml`
- `upgrade_1.1.0_1.2.0.xml`
- `upgrade_1.2.0_1.3.0.xml`
- `upgrade_1.3.0_1.4.0.xml`
- `upgrade_1.4.0_1.5.0.xml`

**Algorithm:** Dijkstra's shortest path (version as nodes, migrations as edges)

---

**End of Update System Specification**
