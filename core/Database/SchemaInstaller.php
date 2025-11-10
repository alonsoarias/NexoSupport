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

        // Parsear XML directamente con DOMDocument (evita problemas del XMLParser)
        try {
            set_time_limit(60); // 60 segundos solo para parsing

            $xmlContent = file_get_contents($xmlFile);
            echo '<p class="text-info small">→ Archivo leído (' . strlen($xmlContent) . ' bytes)...</p>';
            flush(); ob_flush();

            // Usar DOMDocument
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;

            libxml_use_internal_errors(true);
            $loaded = $dom->loadXML($xmlContent, LIBXML_NONET | LIBXML_NOBLANKS);

            if (!$loaded) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                throw new Exception("Error al parsear XML con DOM: " . print_r($errors, true));
            }

            libxml_clear_errors();
            libxml_use_internal_errors(false);

            echo '<p class="text-info small">→ DOM cargado exitosamente...</p>';
            flush(); ob_flush();

            // Convertir DOM a SimpleXML y luego a array (método más confiable)
            $simpleXML = simplexml_import_dom($dom);
            if ($simpleXML === false) {
                throw new Exception("Error al convertir DOM a SimpleXML");
            }

            echo '<p class="text-info small">→ Convertido a SimpleXML...</p>';
            flush(); ob_flush();

            // Convertir SimpleXML a array de forma simple y directa
            $schema = $this->convertSimpleXMLToArray($simpleXML);

            echo '<p class="text-info small">→ XML convertido a array (' . count($schema) . ' elementos)...</p>';
            flush(); ob_flush();

        } catch (Exception $e) {
            echo '<p class="text-danger small">✗ Error parseando XML: ' . htmlspecialchars($e->getMessage()) . '</p>';
            flush(); ob_flush();
            throw $e;
        }

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

                // DEBUG: Mostrar estructura de tableData
                echo '<pre class="small" style="background: #fff3cd; padding: 10px; margin: 10px 20px; border-radius: 5px;">';
                echo 'DEBUG tableData estructura:' . "\n";
                echo 'Keys: ' . implode(', ', array_keys($tableData)) . "\n";
                echo 'Name: ' . ($tableData['name'] ?? 'N/A') . "\n";
                echo 'Has columns: ' . (isset($tableData['columns']) ? 'YES' : 'NO') . "\n";
                if (isset($tableData['columns'])) {
                    echo 'Columns keys: ' . implode(', ', array_keys($tableData['columns'])) . "\n";
                    echo 'Has column array: ' . (isset($tableData['columns']['column']) ? 'YES' : 'NO') . "\n";
                }
                echo '</pre>';
                flush(); ob_flush();

                echo '<p class="text-warning small">→ Llamando a createTable()...</p>';
                flush(); ob_flush();

                set_time_limit(30); // 30 segundos por tabla

                try {
                    $this->createTable($tableData, $charset, $collation, $engine);
                } catch (\Throwable $e) {
                    echo '<p class="text-danger small">✗ Exception capturada: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<p class="text-danger small">✗ Trace: ' . htmlspecialchars($e->getTraceAsString()) . '</p>';
                    flush(); ob_flush();
                    throw $e;
                }

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
        echo '<p class="text-info small" style="margin-left: 20px;">  → Obteniendo nombre de tabla...</p>';
        flush(); ob_flush();

        $tableName = $this->prefix . $tableData['name'];

        echo '<p class="text-info small" style="margin-left: 20px;">  → Obteniendo columnas...</p>';
        flush(); ob_flush();

        $columns = $tableData['columns']['column'] ?? [];

        echo '<p class="text-info small" style="margin-left: 20px;">  → Encontradas ' . (is_array($columns) ? count($columns) : 0) . ' columnas...</p>';
        flush(); ob_flush();

        // Normalizar si solo hay una columna
        if (isset($columns['@attributes']) || isset($columns['name'])) {
            $columns = [$columns];
        }

        echo '<p class="text-info small" style="margin-left: 20px;">  → Construyendo SQL CREATE TABLE...</p>';
        flush(); ob_flush();

        // Construir SQL CREATE TABLE
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (\n";

        $columnDefinitions = [];
        $primaryKeys = [];

        echo '<p class="text-info small" style="margin-left: 20px;">  → Procesando definiciones de columnas...</p>';
        flush(); ob_flush();

        foreach ($columns as $index => $column) {
            echo '<p class="text-info small" style="margin-left: 30px;">    → Columna ' . ($index + 1) . ': ' . ($column['name'] ?? 'unknown') . '</p>';
            flush(); ob_flush();

            $colDef = $this->buildColumnDefinition($column);
            $columnDefinitions[] = $colDef;

            if (isset($column['primary']) && $column['primary'] === 'true') {
                $primaryKeys[] = $column['name'];
            }
        }

        echo '<p class="text-info small" style="margin-left: 20px;">  → Uniendo definiciones...</p>';
        flush(); ob_flush();

        $sql .= implode(",\n", $columnDefinitions);

        // Agregar primary key
        if (!empty($primaryKeys)) {
            $sql .= ",\n PRIMARY KEY (" . implode(',', array_map(function($k) {
                return "`{$k}`";
            }, $primaryKeys)) . ")";
        }

        $sql .= "\n) ENGINE={$engine} DEFAULT CHARSET={$charset} COLLATE={$collation};";

        echo '<p class="text-info small" style="margin-left: 20px;">  → Ejecutando CREATE TABLE...</p>';
        flush(); ob_flush();

        // Ejecutar CREATE TABLE
        try {
            $this->pdo->exec($sql);
            echo '<p class="text-success small" style="margin-left: 20px;">  ✓ CREATE TABLE ejecutado</p>';
            flush(); ob_flush();
        } catch (Exception $e) {
            echo '<p class="text-danger small" style="margin-left: 20px;">  ✗ Error SQL: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p class="text-danger small" style="margin-left: 20px;">  SQL: ' . htmlspecialchars(substr($sql, 0, 500)) . '...</p>';
            flush(); ob_flush();
            throw $e;
        }

        $this->createdTables[] = $tableName;

        echo '<p class="text-info small" style="margin-left: 20px;">  → Creando índices...</p>';
        flush(); ob_flush();

        // Crear índices
        if (isset($tableData['indexes']['index'])) {
            $this->createIndexes($tableName, $tableData['indexes']['index']);
        }

        echo '<p class="text-info small" style="margin-left: 20px;">  → Creando foreign keys...</p>';
        flush(); ob_flush();

        // Crear foreign keys
        if (isset($tableData['foreignKeys']['foreignKey'])) {
            $this->createForeignKeys($tableName, $tableData['foreignKeys']['foreignKey']);
        }

        echo '<p class="text-success small" style="margin-left: 20px;">  ✓ Tabla completada</p>';
        flush(); ob_flush();
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

    /**
     * Convertir SimpleXMLElement a array de forma simple
     * Optimizado para schema.xml sin recursión profunda
     *
     * @param \SimpleXMLElement $xml
     * @return array
     */
    private function convertSimpleXMLToArray(\SimpleXMLElement $xml): array
    {
        $array = [];

        // Procesar cada hijo directo
        foreach ($xml->children() as $childName => $child) {
            $childData = $this->convertElementToValue($child);

            // Si ya existe este nombre de elemento, convertir a array
            if (isset($array[$childName])) {
                if (!is_array($array[$childName]) || !isset($array[$childName][0])) {
                    $array[$childName] = [$array[$childName]];
                }
                $array[$childName][] = $childData;
            } else {
                $array[$childName] = $childData;
            }
        }

        return $array;
    }

    /**
     * Convertir un elemento XML a su valor apropiado (string o array)
     *
     * @param \SimpleXMLElement $element
     * @return mixed
     */
    private function convertElementToValue(\SimpleXMLElement $element)
    {
        $hasChildren = false;
        $hasAttributes = count($element->attributes()) > 0;

        // Verificar si tiene hijos
        foreach ($element->children() as $child) {
            $hasChildren = true;
            break;
        }

        // Si no tiene hijos ni atributos, devolver solo el texto
        if (!$hasChildren && !$hasAttributes) {
            return trim((string)$element);
        }

        // Si tiene hijos o atributos, construir array
        $result = [];

        // Agregar atributos si existen
        if ($hasAttributes) {
            foreach ($element->attributes() as $attrName => $attrValue) {
                $result[$attrName] = (string)$attrValue;
            }
        }

        // Procesar hijos
        if ($hasChildren) {
            foreach ($element->children() as $childName => $child) {
                $childData = $this->convertElementToValue($child);

                // Si ya existe, convertir a array de elementos
                if (isset($result[$childName])) {
                    if (!is_array($result[$childName]) || !isset($result[$childName][0])) {
                        $result[$childName] = [$result[$childName]];
                    }
                    $result[$childName][] = $childData;
                } else {
                    $result[$childName] = $childData;
                }
            }
        }

        // Si solo tiene atributos y texto, agregar el texto
        $text = trim((string)$element);
        if (!$hasChildren && $hasAttributes && !empty($text)) {
            $result['@value'] = $text;
        }

        return $result;
    }
}
