# XML PARSER SYSTEM SPECIFICATION - NexoSupport

**Project:** NexoSupport Authentication System
**Document Type:** Technical Specification
**Version:** 1.0
**Status:** ✅ 95% Implemented (Documentation & Minor Enhancements)
**Date:** 2025-11-13

---

## EXECUTIVE SUMMARY

### Current Status: 95% Complete ✅

**What Exists:**
- ✅ SchemaInstaller (core/Database/SchemaInstaller.php) - 650 lines
- ✅ DatabaseAdapter (multi-DB support)
- ✅ XMLParser utility (core/Utils/XMLParser.php)
- ✅ Full schema.xml support
- ✅ Table creation, indexes, foreign keys
- ✅ Initial data insertion
- ✅ Error handling and logging

**What's Enhance:**
- ⚠️ XML validation against XSD schema
- ⚠️ Better error messages
- ⚠️ Progress callbacks for UI
- ⚠️ Dry-run mode (preview without executing)

**Goal:** Document existing system and specify minor enhancements

---

## 1. SYSTEM ARCHITECTURE

### 1.1 Components

```
XML Schema File (schema.xml)
         ↓
    XMLParser (parse to array)
         ↓
  SchemaInstaller (interpret schema)
         ↓
 DatabaseAdapter (generate SQL)
         ↓
    Database (execute SQL)
         ↓
   Tables Created ✓
```

### 1.2 Key Classes

| Class | Location | Purpose | Lines |
|-------|----------|---------|-------|
| `SchemaInstaller` | `/core/Database/SchemaInstaller.php` | Main installer | 650 |
| `DatabaseAdapter` | `/core/Database/DatabaseAdapter.php` | SQL generation | ~400 |
| `XMLParser` | `/core/Utils/XMLParser.php` | XML parsing | ~150 |
| `Database` | `/core/Database/Database.php` | Execution | ~300 |

---

## 2. XML SCHEMA FORMAT

### 2.1 Complete Schema Structure

```xml
<?xml version="1.0" encoding="UTF-8"?>
<database>
    <!-- Metadata -->
    <metadata>
        <name>Database Name</name>
        <version>1.0.0</version>
        <charset>utf8mb4</charset>
        <collation>utf8mb4_unicode_ci</collation>
        <engine>InnoDB</engine>
    </metadata>

    <!-- Tables -->
    <tables>
        <table name="table_name">
            <description>Table description</description>

            <!-- Columns -->
            <columns>
                <column name="id"
                        type="INT UNSIGNED"
                        autoincrement="true"
                        primary="true"
                        null="false"/>

                <column name="username"
                        type="VARCHAR"
                        length="50"
                        unique="true"
                        null="false"/>

                <column name="email"
                        type="VARCHAR"
                        length="255"
                        unique="true"
                        null="false"/>

                <column name="created_at"
                        type="INT UNSIGNED"
                        null="false"/>
            </columns>

            <!-- Indexes -->
            <indexes>
                <index name="idx_username"
                       columns="username"
                       unique="true"/>

                <index name="idx_email_status"
                       columns="email,status"/>
            </indexes>

            <!-- Foreign Keys -->
            <foreignKeys>
                <foreignKey column="user_id"
                           references="users(id)"
                           onDelete="CASCADE"
                           onUpdate="CASCADE"/>
            </foreignKeys>

            <!-- Initial Data -->
            <data>
                <row>
                    <username>admin</username>
                    <email>admin@example.com</email>
                    <created_at>{{TIMESTAMP}}</created_at>
                </row>
            </data>
        </table>

        <!-- More tables... -->
    </tables>
</database>
```

### 2.2 Supported Column Types

#### **MySQL/MariaDB Types:**

**Integer Types:**
- `TINYINT`, `SMALLINT`, `MEDIUMINT`, `INT`, `BIGINT`
- Modifiers: `UNSIGNED`, `AUTO_INCREMENT`

**String Types:**
- `CHAR(length)`, `VARCHAR(length)`
- `TEXT`, `MEDIUMTEXT`, `LONGTEXT`
- `ENUM('value1','value2',...)`

**Binary Types:**
- `BLOB`, `MEDIUMBLOB`, `LONGBLOB`

**Date/Time Types:**
- `DATE`, `TIME`, `DATETIME`, `TIMESTAMP`, `YEAR`

**Numeric Types:**
- `DECIMAL(precision,scale)`, `FLOAT`, `DOUBLE`

**JSON Type:**
- `JSON` (MySQL 5.7.8+)

**Boolean:**
- `BOOLEAN` (alias for TINYINT(1))

#### **PostgreSQL Types:**

All MySQL types plus:
- `SERIAL`, `BIGSERIAL` (auto-increment)
- `ARRAY`
- `JSONB` (binary JSON)
- `UUID`
- `INET`, `CIDR` (network addresses)

#### **SQLite Types:**

- `INTEGER`, `REAL`, `TEXT`, `BLOB`
- `DATETIME` (stored as TEXT or INTEGER)

**Note:** DatabaseAdapter handles type conversions per database engine.

### 2.3 Column Attributes

| Attribute | Type | Description | Example |
|-----------|------|-------------|---------|
| `name` | string | Column name (required) | `username` |
| `type` | string | Data type (required) | `VARCHAR` |
| `length` | int | Length for VARCHAR/CHAR | `255` |
| `precision` | int | Precision for DECIMAL | `10` |
| `scale` | int | Scale for DECIMAL | `2` |
| `null` | bool | Allow NULL values | `true` / `false` |
| `default` | mixed | Default value | `active` |
| `primary` | bool | Primary key | `true` |
| `autoincrement` | bool | Auto increment | `true` |
| `unique` | bool | Unique constraint | `true` |
| `unsigned` | bool | Unsigned (integers) | `true` |

### 2.4 Index Types

**Simple Index:**
```xml
<index name="idx_username" columns="username"/>
```

**Unique Index:**
```xml
<index name="idx_email" columns="email" unique="true"/>
```

**Composite Index:**
```xml
<index name="idx_user_role" columns="user_id,role_id" unique="true"/>
```

**Full-Text Index** (MySQL):
```xml
<index name="idx_fulltext_content" columns="title,content" type="FULLTEXT"/>
```

### 2.5 Foreign Key Actions

**ON DELETE:**
- `CASCADE` - Delete referenced rows
- `SET NULL` - Set to NULL
- `RESTRICT` - Prevent deletion
- `NO ACTION` - Same as RESTRICT

**ON UPDATE:**
- `CASCADE` - Update referenced rows
- `SET NULL` - Set to NULL
- `RESTRICT` - Prevent update
- `NO ACTION` - Same as RESTRICT

### 2.6 Data Insertion

**Static Data:**
```xml
<data>
    <row>
        <config_key>app.name</config_key>
        <config_value>NexoSupport</config_value>
    </row>
</data>
```

**Dynamic Placeholders:**
```xml
<row>
    <created_at>{{TIMESTAMP}}</created_at>
    <uuid>{{UUID}}</uuid>
    <hash>{{MD5:password}}</hash>
    <bcrypt>{{BCRYPT:password}}</bcrypt>
</row>
```

**Supported Placeholders:**
- `{{TIMESTAMP}}` - Current Unix timestamp
- `{{UUID}}` - Generate UUID v4
- `{{MD5:string}}` - MD5 hash
- `{{SHA256:string}}` - SHA256 hash
- `{{BCRYPT:string}}` - Bcrypt hash
- `{{NOW}}` - Current datetime (Y-m-d H:i:s)
- `{{DATE}}` - Current date (Y-m-d)

---

## 3. SCHEMA INSTALLER ARCHITECTURE

### 3.1 Installation Process

```
1. Load and Parse XML
   ├── Read schema.xml file
   ├── Parse with DOMDocument or SimpleXML
   ├── Validate structure
   └── Extract metadata

2. Prepare Database
   ├── Get DatabaseAdapter instance
   ├── Set charset/collation
   ├── Begin transaction

3. Create Tables
   FOR EACH table in schema:
       ├── Generate CREATE TABLE SQL
       ├── Execute SQL
       ├── Log progress
       └── Handle errors

4. Create Indexes
   FOR EACH table with indexes:
       ├── Generate CREATE INDEX SQL
       ├── Execute SQL
       └── Log progress

5. Create Foreign Keys
   FOR EACH table with foreign keys:
       ├── Generate ALTER TABLE ADD FOREIGN KEY SQL
       ├── Execute SQL
       └── Log progress

6. Insert Initial Data
   FOR EACH table with data:
       ├── Process placeholders
       ├── Generate INSERT SQL
       ├── Execute SQL
       └── Log progress

7. Commit Transaction
   ├── Commit if all succeeded
   ├── Rollback if error
   └── Log final status

8. Return Report
   ├── Tables created count
   ├── Indexes created count
   ├── Foreign keys created count
   ├── Rows inserted count
   └── Errors (if any)
```

### 3.2 Error Handling

**Strategy:** Fail-fast with transaction rollback

```php
try {
    $db->beginTransaction();

    foreach ($tables as $table) {
        try {
            $this->createTable($table);
        } catch (Exception $e) {
            Logger::error("Failed to create table {$table['name']}", [
                'error' => $e->getMessage(),
                'sql' => $table['sql']
            ]);
            throw $e; // Re-throw to trigger rollback
        }
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw new InstallationException("Installation failed: " . $e->getMessage());
}
```

**Error Types:**
- XML Parse Error
- SQL Syntax Error
- Constraint Violation (unique, foreign key)
- Database Connection Error
- File Permission Error

### 3.3 Logging

**Log Levels:**
- `DEBUG` - Each SQL statement
- `INFO` - Progress (table created, index created)
- `WARNING` - Skipped items (table exists)
- `ERROR` - Fatal errors

**Log Example:**
```
[INFO] SchemaInstaller: Starting installation
[INFO] SchemaInstaller: Creating table 'users'
[DEBUG] SchemaInstaller: SQL: CREATE TABLE users (...)
[INFO] SchemaInstaller: Table 'users' created successfully
[INFO] SchemaInstaller: Creating index 'idx_username' on 'users'
[INFO] SchemaInstaller: Installation complete (23 tables, 50 indexes, 19 foreign keys)
```

---

## 4. DATABASE ADAPTER

### 4.1 Purpose

Convert abstract schema definitions to database-specific SQL.

**Supports:**
- MySQL/MariaDB
- PostgreSQL
- SQLite

### 4.2 Type Mapping

**Example: AUTO_INCREMENT**

| Database | Syntax |
|----------|--------|
| MySQL | `INT UNSIGNED AUTO_INCREMENT` |
| PostgreSQL | `SERIAL` or `BIGSERIAL` |
| SQLite | `INTEGER PRIMARY KEY AUTOINCREMENT` |

**DatabaseAdapter handles this automatically.**

### 4.3 Engine-Specific Features

**MySQL:**
- Storage engines (InnoDB, MyISAM)
- Character sets and collations
- Full-text indexes

**PostgreSQL:**
- Sequences
- Array types
- JSONB
- Advanced constraints

**SQLite:**
- Simplified types (INTEGER, REAL, TEXT, BLOB)
- No ALTER TABLE for some operations
- No separate boolean type (uses INTEGER)

---

## 5. XML VALIDATION

### 5.1 XSD Schema (Recommended Enhancement)

**Create:** `/database/schema/schema.xsd`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">

  <xs:element name="database">
    <xs:complexType>
      <xs:sequence>
        <xs:element name="metadata" type="MetadataType" minOccurs="0"/>
        <xs:element name="tables" type="TablesType"/>
      </xs:sequence>
    </xs:complexType>
  </xs:element>

  <xs:complexType name="MetadataType">
    <xs:sequence>
      <xs:element name="name" type="xs:string"/>
      <xs:element name="version" type="xs:string"/>
      <xs:element name="charset" type="xs:string"/>
      <xs:element name="collation" type="xs:string"/>
      <xs:element name="engine" type="xs:string"/>
    </xs:sequence>
  </xs:complexType>

  <!-- More definitions... -->

</xs:schema>
```

**Validate Before Installation:**
```php
$dom = new DOMDocument();
$dom->load('schema.xml');

if (!$dom->schemaValidate('schema.xsd')) {
    throw new ValidationException("Schema XML is invalid");
}
```

**Benefits:**
- Catch errors before execution
- IDE autocomplete
- Documentation

### 5.2 Custom Validation Rules

**Check:**
- Table names valid (alphanumeric + underscore)
- Column names valid
- No reserved keywords
- Foreign key references exist
- Unique index names

---

## 6. PLUGIN INSTALLATION VIA XML

### 6.1 Plugin install.xml Format

**Same format as schema.xml, but:**
- Tables prefixed with plugin slug
- Self-contained (no dependencies on core tables usually)

**Example:** `/modules/plugins/tools/my-plugin/install.xml`

```xml
<database>
    <tables>
        <table name="plugin_myplugin_data">
            <columns>
                <column name="id" type="INT UNSIGNED" autoincrement="true" primary="true"/>
                <column name="user_id" type="INT UNSIGNED"/>
                <column name="data" type="TEXT"/>
            </columns>
            <foreignKeys>
                <foreignKey column="user_id" references="users(id)" onDelete="CASCADE"/>
            </foreignKeys>
        </table>
    </tables>
</database>
```

### 6.2 Installation Process

```
1. Plugin uploads .zip
2. Extract and find install.xml
3. SchemaInstaller::installFromXML('install.xml')
4. Tables created with plugin prefix
5. Plugin marked as installed in database
```

### 6.3 Uninstallation

**Options:**
1. **Keep data** - Only disable plugin, keep tables
2. **Remove data** - DROP all plugin tables

**User chooses during uninstallation.**

---

## 7. UPGRADE SCRIPTS VIA XML

### 7.1 Upgrade XML Format

**File:** `/database/upgrades/upgrade_1.0.0_1.1.0.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<upgrade from="1.0.0" to="1.1.0">
    <description>Add avatar_url column to users table</description>

    <database>
        <!-- Add new tables -->
        <create_table name="notifications">
            <columns>
                <column name="id" type="INT UNSIGNED" autoincrement="true" primary="true"/>
                <column name="user_id" type="INT UNSIGNED" null="false"/>
                <column name="message" type="TEXT"/>
            </columns>
        </create_table>

        <!-- Modify existing tables -->
        <modify_table name="users">
            <!-- Add column -->
            <add_column name="avatar_url" type="VARCHAR" length="255" after="email"/>

            <!-- Modify column -->
            <modify_column name="bio" new_type="TEXT" new_length="1000"/>

            <!-- Add index -->
            <add_index name="idx_avatar" columns="avatar_url"/>

            <!-- Drop column (use with caution!) -->
            <drop_column name="old_field"/>
        </modify_table>

        <!-- Drop table (use with extreme caution!) -->
        <drop_table name="deprecated_table"/>
    </database>

    <!-- Data migration -->
    <data>
        <!-- Update existing data -->
        <update table="users" set="avatar_url='/assets/images/default-avatar.png'" where="avatar_url IS NULL"/>

        <!-- Insert new data -->
        <insert table="notifications">
            <row>
                <user_id>1</user_id>
                <message>Welcome to the new version!</message>
            </row>
        </insert>

        <!-- Delete old data -->
        <delete table="sessions" where="last_activity < {{TIMESTAMP}}-86400"/>
    </data>

    <!-- Custom PHP script (optional) -->
    <custom>
        <script>upgrade_1.0.0_to_1.1.0.php</script>
    </custom>
</upgrade>
```

### 7.2 Upgrade Execution

```php
$upgrader = new SchemaUpgrader($db);
$upgrader->upgrade('1.0.0', '1.1.0');

// Automatically finds and executes:
// /database/upgrades/upgrade_1.0.0_1.1.0.xml
```

### 7.3 Version Tracking

**Table:** `system_versions`

```sql
CREATE TABLE system_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    component VARCHAR(100) NOT NULL,  -- 'core' or plugin slug
    version_number VARCHAR(20) NOT NULL,
    version_code BIGINT UNSIGNED NOT NULL,
    applied_at INT UNSIGNED NOT NULL,
    description TEXT,
    INDEX idx_component (component)
);
```

**After upgrade:**
```php
INSERT INTO system_versions (component, version_number, version_code, applied_at)
VALUES ('core', '1.1.0', 202501130001, UNIX_TIMESTAMP());
```

---

## 8. ENHANCEMENTS SPECIFICATION

### 8.1 Enhancement #1: Progress Callbacks

**Purpose:** Show installation progress in UI

**Implementation:**
```php
class SchemaInstaller
{
    private $progressCallback = null;

    public function setProgressCallback(callable $callback): void
    {
        $this->progressCallback = $callback;
    }

    private function reportProgress(string $message, int $percent): void
    {
        if ($this->progressCallback) {
            call_user_func($this->progressCallback, $message, $percent);
        }
    }

    public function install(string $xmlFile): void
    {
        $this->reportProgress('Parsing XML...', 5);
        // ... parse XML

        $this->reportProgress('Creating tables...', 20);
        // ... create tables

        $this->reportProgress('Creating indexes...', 60);
        // ... create indexes

        $this->reportProgress('Inserting data...', 80);
        // ... insert data

        $this->reportProgress('Complete!', 100);
    }
}
```

**Usage:**
```php
$installer = new SchemaInstaller($db);
$installer->setProgressCallback(function($message, $percent) {
    echo json_encode(['message' => $message, 'percent' => $percent]);
    flush();
});
$installer->install('schema.xml');
```

### 8.2 Enhancement #2: Dry-Run Mode

**Purpose:** Preview changes without executing

```php
class SchemaInstaller
{
    private bool $dryRun = false;

    public function setDryRun(bool $dryRun): void
    {
        $this->dryRun = $dryRun;
    }

    private function execute(string $sql): void
    {
        if ($this->dryRun) {
            echo "WOULD EXECUTE: $sql\n";
        } else {
            $this->db->execute($sql);
        }
    }
}
```

**Usage:**
```php
$installer->setDryRun(true);
$installer->install('schema.xml'); // Shows SQL without executing
```

### 8.3 Enhancement #3: Better Error Messages

**Current:**
```
Error: SQLSTATE[42000]: Syntax error
```

**Enhanced:**
```
Error creating table 'users':
  SQL Syntax Error in column definition
  Column: 'email'
  Type: 'VARCHAR' requires length parameter
  Line: 45 of schema.xml

  Suggestion: Add length="255" attribute
```

**Implementation:**
```php
try {
    $this->createColumn($column);
} catch (SQLException $e) {
    throw new SchemaException(
        "Error creating table '{$table['name']}'\n" .
        "  Column: '{$column['name']}'\n" .
        "  Error: {$e->getMessage()}\n" .
        "  Suggestion: {$this->getSuggestion($e)}"
    );
}
```

### 8.4 Enhancement #4: Schema Diff Tool

**Purpose:** Compare two schemas and generate upgrade XML

```bash
php cli/schema_diff.php --from=schema_v1.xml --to=schema_v2.xml --output=upgrade_1_2.xml
```

**Output:** Automatically generated upgrade XML with:
- Tables to create
- Tables to drop
- Columns to add/modify/drop
- Indexes to add/drop
- Foreign keys to add/drop

---

## 9. USAGE EXAMPLES

### 9.1 Core Installation

```php
<?php
// install/stages/install_db.php

use ISER\Core\Database\SchemaInstaller;
use ISER\Core\Database\Database;

$db = Database::getInstance();
$installer = new SchemaInstaller($db);

try {
    $report = $installer->install(__DIR__ . '/../../database/schema/schema.xml');

    echo "Installation successful!\n";
    echo "Tables created: {$report['tables_created']}\n";
    echo "Indexes created: {$report['indexes_created']}\n";
    echo "Rows inserted: {$report['rows_inserted']}\n";

} catch (Exception $e) {
    echo "Installation failed: " . $e->getMessage();
    exit(1);
}
```

### 9.2 Plugin Installation

```php
<?php
// modules/Plugin/PluginInstaller.php

class PluginInstaller
{
    public function installDatabase(string $pluginPath): void
    {
        $installXml = $pluginPath . '/install.xml';

        if (file_exists($installXml)) {
            $installer = new SchemaInstaller($this->db);
            $installer->install($installXml);

            Logger::info("Plugin database installed from $installXml");
        }
    }
}
```

### 9.3 System Upgrade

```php
<?php
// modules/Update/SystemUpdater.php

class SystemUpdater
{
    public function upgradeFrom(string $fromVersion, string $toVersion): void
    {
        $upgradeFile = "/database/upgrades/upgrade_{$fromVersion}_{$toVersion}.xml";

        if (file_exists($upgradeFile)) {
            $upgrader = new SchemaUpgrader($this->db);
            $upgrader->executeUpgrade($upgradeFile);

            Logger::info("System upgraded from $fromVersion to $toVersion");
        }
    }
}
```

---

## 10. TESTING

### 10.1 Unit Tests

```php
class SchemaInstallerTest extends TestCase
{
    public function testInstallCreatesTable(): void
    {
        $xml = '<database><tables><table name="test_table">...</table></tables></database>';

        $installer = new SchemaInstaller($this->mockDb);
        $installer->install($xml);

        $this->assertTrue($this->tableExists('test_table'));
    }

    public function testInvalidXMLThrowsException(): void
    {
        $this->expectException(XMLParseException::class);

        $xml = '<invalid>xml</invalid>';
        $installer = new SchemaInstaller($this->mockDb);
        $installer->install($xml);
    }
}
```

### 10.2 Integration Tests

```php
class DatabaseInstallationTest extends TestCase
{
    public function testFullSchemaInstallation(): void
    {
        $installer = new SchemaInstaller(Database::getInstance());
        $report = $installer->install('tests/fixtures/test_schema.xml');

        $this->assertEquals(5, $report['tables_created']);
        $this->assertEquals(10, $report['indexes_created']);
        $this->assertTrue($this->tableExists('users'));
        $this->assertTrue($this->tableExists('roles'));
    }
}
```

---

## 11. BEST PRACTICES

### 11.1 Schema Design

**Do's:**
- ✅ Use descriptive table names
- ✅ Follow naming conventions (snake_case)
- ✅ Add descriptions to tables
- ✅ Define foreign keys explicitly
- ✅ Use appropriate data types
- ✅ Add indexes for foreign keys
- ✅ Use transactions (handled automatically)

**Don'ts:**
- ❌ Use reserved keywords as table/column names
- ❌ Forget to specify lengths for VARCHAR
- ❌ Omit foreign key constraints
- ❌ Over-index (every column)
- ❌ Use generic column names (data, value, etc.)

### 11.2 Upgrade Scripts

**Do's:**
- ✅ One upgrade file per version jump
- ✅ Test on copy of production database
- ✅ Add data migration logic
- ✅ Handle NULL values when adding NOT NULL columns
- ✅ Provide rollback scripts

**Don'ts:**
- ❌ Drop columns without backup
- ❌ Drop tables without warning
- ❌ Change primary keys lightly
- ❌ Forget to update indexes after column changes

### 11.3 Plugin Schemas

**Do's:**
- ✅ Prefix tables with plugin slug
- ✅ Use foreign keys to core tables carefully
- ✅ Provide uninstall option
- ✅ Document schema in README

**Don'ts:**
- ❌ Modify core tables directly
- ❌ Assume core table structure
- ❌ Create too many tables (keep it simple)

---

## 12. COMPLETION CHECKLIST

### 12.1 Current Implementation ✅

- [x] SchemaInstaller class
- [x] DatabaseAdapter (MySQL, PostgreSQL, SQLite)
- [x] XMLParser utility
- [x] Table creation
- [x] Index creation
- [x] Foreign key creation
- [x] Data insertion
- [x] Error handling
- [x] Transaction support
- [x] Logging

### 12.2 Enhancements (Optional)

- [ ] XSD validation
- [ ] Progress callbacks for UI
- [ ] Dry-run mode
- [ ] Better error messages with suggestions
- [ ] Schema diff tool
- [ ] Visual schema designer (future)

---

## 13. CONCLUSION

### 13.1 Current State

**Status:** ✅ **95% Complete - Production Ready**

The XML Parser system is fully functional and powers:
- Core installation
- Plugin installation
- System upgrades (with minor additions needed)

### 13.2 Strengths

- ✅ Multi-database support
- ✅ Comprehensive feature set
- ✅ Good error handling
- ✅ Transaction safety
- ✅ Well-tested in core installation

### 13.3 Recommendations

**Priority: LOW** - System works well as-is

**Optional Enhancements (Nice to Have):**
1. Add progress callbacks (8 hours) - For better UX during installation
2. Add dry-run mode (4 hours) - For testing
3. Create XSD schema (8 hours) - For validation
4. Improve error messages (4 hours) - For debugging

**Total Optional Work:** 24 hours

**Recommendation:** ✅ **Use as-is, enhance only if needed**

---

**Document Version:** 1.0
**Status:** Documentation Complete
**Implementation:** 95% Complete
**Next Steps:** Optional enhancements if time permits

---

## APPENDICES

### Appendix A: Complete schema.xml Example

See: `/database/schema/schema.xml` (942 lines)

### Appendix B: Placeholder Reference

| Placeholder | Output | Example |
|-------------|--------|---------|
| `{{TIMESTAMP}}` | Unix timestamp | `1699876543` |
| `{{UUID}}` | UUID v4 | `550e8400-e29b-41d4-a716-446655440000` |
| `{{MD5:text}}` | MD5 hash | `5f4dcc3b5aa765d61d8327deb882cf99` |
| `{{SHA256:text}}` | SHA256 hash | `65e84be33532fb784c48129675f9eff3...` |
| `{{BCRYPT:text}}` | Bcrypt hash | `$2y$10$N9qo8uLOickgx2ZMRZoMye...` |
| `{{NOW}}` | Datetime | `2025-11-13 15:30:00` |
| `{{DATE}}` | Date | `2025-11-13` |

### Appendix C: Database Adapter SQL Examples

**MySQL:**
```sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    created_at INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**PostgreSQL:**
```sql
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    created_at INT NOT NULL
);
```

**SQLite:**
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    created_at INTEGER NOT NULL
);
```

---

**End of XML Parser Specification**
