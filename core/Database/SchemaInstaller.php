<?php

declare(strict_types=1);

namespace ISER\Core\Database;

use ISER\Core\Utils\XMLParser;
use PDO;
use Exception;

/**
 * Schema Installer - ISER Authentication System
 *
 * Instala el esquema de base de datos desde archivos XML
 *
 * @package ISER\Core\Database
 * @author ISER Desarrollo
 * @license Propietario
 */
class SchemaInstaller
{
    private PDO $pdo;
    private XMLParser $xmlParser;
    private string $prefix = '';
    private array $createdTables = [];
    private array $errors = [];

    /**
     * Constructor
     *
     * @param PDO $pdo Conexión PDO
     * @param string $prefix Prefijo de tablas
     */
    public function __construct(PDO $pdo, string $prefix = '')
    {
        $this->pdo = $pdo;
        $this->prefix = $prefix;
        $this->xmlParser = new XMLParser();
    }

    /**
     * Instalar esquema desde archivo XML
     *
     * @param string $xmlFile Ruta del archivo XML
     * @return bool True si se instaló exitosamente
     * @throws Exception
     */
    public function installFromXML(string $xmlFile): bool
    {
        if (!file_exists($xmlFile)) {
            throw new Exception("Archivo XML no encontrado: {$xmlFile}");
        }

        echo '<p class="text-info small">→ Parseando archivo XML...</p>';
        flush(); ob_flush();

        // Parsear XML
        $this->xmlParser->parseFile($xmlFile);
        $schema = $this->xmlParser->toArray();

        if (empty($schema)) {
            throw new Exception("El esquema XML está vacío");
        }

        echo '<p class="text-info small">→ XML parseado, obteniendo metadata...</p>';
        flush(); ob_flush();

        // Obtener metadata
        $metadata = $schema['metadata'] ?? [];
        $charset = $metadata['charset'] ?? 'utf8mb4';
        $collation = $metadata['collation'] ?? 'utf8mb4_unicode_ci';
        $engine = $metadata['engine'] ?? 'InnoDB';

        // Procesar tablas
        $tables = $schema['table'] ?? [];

        // Normalizar si solo hay una tabla
        if (isset($tables['@attributes']) || isset($tables['name'])) {
            $tables = [$tables];
        }

        echo '<p class="text-info small">→ Creando ' . count($tables) . ' tablas...</p>';
        flush(); ob_flush();

        foreach ($tables as $index => $tableData) {
            try {
                $tableName = $tableData['name'] ?? 'unknown';
                echo '<p class="text-info small">→ Creando tabla: ' . htmlspecialchars($this->prefix . $tableName) . '</p>';
                flush(); ob_flush();

                $this->createTable($tableData, $charset, $collation, $engine);

                echo '<p class="text-success small">✓ Tabla creada: ' . htmlspecialchars($this->prefix . $tableName) . '</p>';
                flush(); ob_flush();
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
                echo '<p class="text-danger small">✗ Error en tabla: ' . htmlspecialchars($e->getMessage()) . '</p>';
                flush(); ob_flush();
                throw $e;
            }
        }

        echo '<p class="text-info small">→ Insertando datos iniciales...</p>';
        flush(); ob_flush();

        // Insertar datos iniciales
        foreach ($tables as $tableData) {
            if (isset($tableData['data'])) {
                $tableName = $tableData['name'] ?? 'unknown';
                echo '<p class="text-info small">→ Insertando datos en: ' . htmlspecialchars($this->prefix . $tableName) . '</p>';
                flush(); ob_flush();
                $this->insertInitialData($tableData);
            }
        }

        // Asignar permisos al rol admin (si existen las tablas necesarias)
        if (in_array($this->prefix . 'role_permissions', $this->createdTables) &&
            in_array($this->prefix . 'permissions', $this->createdTables)) {
            echo '<p class="text-info small">→ Asignando permisos al rol Admin...</p>';
            flush(); ob_flush();
            $this->assignAdminPermissions();
        }

        return true;
    }

    /**
     * Crear tabla desde datos XML
     *
     * @param array $tableData Datos de la tabla
     * @param string $charset Charset
     * @param string $collation Collation
     * @param string $engine Engine
     */
    private function createTable(array $tableData, string $charset, string $collation, string $engine): void
    {
        $tableName = $this->prefix . $tableData['name'];
        $columns = $tableData['columns']['column'] ?? [];

        // Normalizar si solo hay una columna
        if (isset($columns['@attributes']) || isset($columns['name'])) {
            $columns = [$columns];
        }

        // Construir SQL CREATE TABLE
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (\n";

        $columnDefinitions = [];
        $primaryKeys = [];

        foreach ($columns as $column) {
            $colDef = $this->buildColumnDefinition($column);
            $columnDefinitions[] = $colDef;

            if (isset($column['primary']) && $column['primary'] === 'true') {
                $primaryKeys[] = $column['name'];
            }
        }

        $sql .= implode(",\n", $columnDefinitions);

        // Agregar primary key
        if (!empty($primaryKeys)) {
            $sql .= ",\n PRIMARY KEY (" . implode(',', array_map(function($k) {
                return "`{$k}`";
            }, $primaryKeys)) . ")";
        }

        $sql .= "\n) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collation};";

        // Ejecutar CREATE TABLE
        $this->pdo->exec($sql);
        $this->createdTables[] = $tableName;

        // Crear índices
        if (isset($tableData['indexes']['index'])) {
            $this->createIndexes($tableName, $tableData['indexes']['index']);
        }

        // Crear foreign keys
        if (isset($tableData['foreignKeys']['foreignKey'])) {
            $this->createForeignKeys($tableName, $tableData['foreignKeys']['foreignKey']);
        }
    }

    /**
     * Construir definición de columna SQL
     *
     * @param array $column Datos de la columna
     * @return string Definición SQL
     */
    private function buildColumnDefinition(array $column): string
    {
        $name = $column['name'];
        $type = $column['type'];
        $def = "  `{$name}` {$type}";

        if (isset($column['null']) && $column['null'] === 'false') {
            $def .= " NOT NULL";
        }

        if (isset($column['default']) && $column['default'] !== '') {
            $default = $column['default'];
            if ($default === 'false' || $default === 'true') {
                $def .= " DEFAULT " . ($default === 'true' ? 'TRUE' : 'FALSE');
            } elseif (is_numeric($default)) {
                $def .= " DEFAULT {$default}";
            } else {
                $def .= " DEFAULT '{$default}'";
            }
        }

        if (isset($column['autoincrement']) && $column['autoincrement'] === 'true') {
            $def .= " AUTO_INCREMENT";
        }

        if (isset($column['unique']) && $column['unique'] === 'true') {
            $def .= " UNIQUE";
        }

        return $def;
    }

    /**
     * Crear índices
     *
     * @param string $tableName Nombre de la tabla
     * @param array $indexes Definiciones de índices
     */
    private function createIndexes(string $tableName, array $indexes): void
    {
        // Normalizar si solo hay un índice
        if (isset($indexes['@attributes']) || isset($indexes['name'])) {
            $indexes = [$indexes];
        }

        foreach ($indexes as $index) {
            $indexName = $index['name'];
            $columns = explode(',', $index['columns']);
            $unique = isset($index['unique']) && $index['unique'] === 'true' ? 'UNIQUE' : '';

            $columnList = implode(',', array_map(function($col) {
                return "`" . trim($col) . "`";
            }, $columns));

            $sql = "CREATE {$unique} INDEX `{$indexName}` ON `{$tableName}` ({$columnList});";

            try {
                $this->pdo->exec($sql);
            } catch (Exception $e) {
                // Ignorar si el índice ya existe
            }
        }
    }

    /**
     * Crear foreign keys
     *
     * @param string $tableName Nombre de la tabla
     * @param array $foreignKeys Definiciones de foreign keys
     */
    private function createForeignKeys(string $tableName, array $foreignKeys): void
    {
        // Normalizar si solo hay una foreign key
        if (isset($foreignKeys['@attributes']) || isset($foreignKeys['column'])) {
            $foreignKeys = [$foreignKeys];
        }

        foreach ($foreignKeys as $fk) {
            $column = $fk['column'];
            $references = $fk['references'];
            $onDelete = $fk['onDelete'] ?? 'RESTRICT';

            // Agregar prefijo a la tabla referenciada
            $references = preg_replace_callback('/^(\w+)\(/', function($matches) {
                return $this->prefix . $matches[1] . '(';
            }, $references);

            $constraintName = "fk_{$tableName}_{$column}";
            $sql = "ALTER TABLE `{$tableName}`
                    ADD CONSTRAINT `{$constraintName}`
                    FOREIGN KEY (`{$column}`)
                    REFERENCES {$references}
                    ON DELETE {$onDelete};";

            try {
                $this->pdo->exec($sql);
            } catch (Exception $e) {
                // Ignorar si la foreign key ya existe
            }
        }
    }

    /**
     * Insertar datos iniciales
     *
     * @param array $tableData Datos de la tabla
     */
    private function insertInitialData(array $tableData): void
    {
        $tableName = $this->prefix . $tableData['name'];
        $data = $tableData['data']['row'] ?? [];

        // Normalizar si solo hay una fila
        if (!isset($data[0])) {
            $data = [$data];
        }

        foreach ($data as $row) {
            // Agregar timestamps si no existen
            if (!isset($row['created_at']) && isset($tableData['columns']['column'])) {
                $hasCreatedAt = false;
                $columns = $tableData['columns']['column'];
                if (!isset($columns[0])) $columns = [$columns];

                foreach ($columns as $col) {
                    if ($col['name'] === 'created_at') {
                        $hasCreatedAt = true;
                        break;
                    }
                }

                if ($hasCreatedAt) {
                    $row['created_at'] = time();
                    $row['updated_at'] = time();
                }
            }

            $columns = array_keys($row);
            $placeholders = array_map(function() { return '?'; }, $columns);

            $sql = "INSERT IGNORE INTO `{$tableName}`
                    (" . implode(',', array_map(function($c) { return "`{$c}`"; }, $columns)) . ")
                    VALUES (" . implode(',', $placeholders) . ")";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($row));
        }
    }

    /**
     * Asignar todos los permisos al rol de administrador
     */
    private function assignAdminPermissions(): void
    {
        $sql = "INSERT IGNORE INTO `{$this->prefix}role_permissions` (role_id, permission_id, granted_at)
                SELECT 1, id, UNIX_TIMESTAMP() FROM `{$this->prefix}permissions`";

        try {
            $this->pdo->exec($sql);
        } catch (Exception $e) {
            // Ignorar si ya existen
        }
    }

    /**
     * Obtener tablas creadas
     *
     * @return array
     */
    public function getCreatedTables(): array
    {
        return $this->createdTables;
    }

    /**
     * Obtener errores
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
