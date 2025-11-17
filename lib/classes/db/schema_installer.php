<?php
namespace core\db;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Schema Installer
 *
 * Parsea archivos install.xml e instala esquemas de base de datos.
 *
 * @package core\db
 */
class schema_installer {

    /** @var database Database instance */
    private database $db;

    /** @var ddl_manager DDL manager */
    private ddl_manager $ddl;

    /**
     * Constructor
     *
     * @param database $db
     */
    public function __construct(database $db) {
        $this->db = $db;
        $this->ddl = $db->get_manager();
    }

    /**
     * Instalar desde archivo install.xml
     *
     * @param string $filepath Ruta al archivo install.xml
     * @return bool
     */
    public function install_from_xmlfile(string $filepath): bool {
        if (!file_exists($filepath)) {
            throw new \coding_exception("File not found: $filepath");
        }

        $xml = simplexml_load_file($filepath);

        if ($xml === false) {
            throw new \coding_exception("Invalid XML file: $filepath");
        }

        // Parsear tablas
        if (isset($xml->TABLES)) {
            foreach ($xml->TABLES->TABLE as $tablexml) {
                $table = $this->parse_table($tablexml);

                if (!$this->ddl->table_exists($table->get_name())) {
                    $this->ddl->create_table($table);
                }
            }
        }

        return true;
    }

    /**
     * Parsear definición de tabla desde XML
     *
     * @param \SimpleXMLElement $xml
     * @return xmldb_table
     */
    private function parse_table(\SimpleXMLElement $xml): xmldb_table {
        $tablename = (string)$xml['NAME'];
        $table = new xmldb_table($tablename);

        // Parsear campos
        if (isset($xml->FIELDS)) {
            foreach ($xml->FIELDS->FIELD as $fieldxml) {
                $field = $this->parse_field($fieldxml);
                $table->add_field($field);
            }
        }

        // Parsear claves
        if (isset($xml->KEYS)) {
            foreach ($xml->KEYS->KEY as $keyxml) {
                $key = $this->parse_key($keyxml);
                $table->add_key($key);
            }
        }

        // Parsear índices
        if (isset($xml->INDEXES)) {
            foreach ($xml->INDEXES->INDEX as $indexxml) {
                $index = $this->parse_index($indexxml);
                $table->add_index($index);
            }
        }

        return $table;
    }

    /**
     * Parsear campo desde XML
     *
     * @param \SimpleXMLElement $xml
     * @return xmldb_field
     */
    private function parse_field(\SimpleXMLElement $xml): xmldb_field {
        $name = (string)$xml['NAME'];
        $type = strtolower((string)$xml['TYPE']);

        $field = new xmldb_field($name, $type);

        if (isset($xml['LENGTH'])) {
            $field->set_length((int)$xml['LENGTH']);
        }

        if (isset($xml['PRECISION'])) {
            $field->set_precision((int)$xml['PRECISION']);
        }

        if (isset($xml['NOTNULL'])) {
            $field->set_notnull(strtolower((string)$xml['NOTNULL']) === 'true');
        }

        if (isset($xml['SEQUENCE'])) {
            $field->set_sequence(strtolower((string)$xml['SEQUENCE']) === 'true');
        }

        if (isset($xml['DEFAULT'])) {
            $field->set_default((string)$xml['DEFAULT']);
        }

        return $field;
    }

    /**
     * Parsear clave desde XML
     *
     * @param \SimpleXMLElement $xml
     * @return xmldb_key
     */
    private function parse_key(\SimpleXMLElement $xml): xmldb_key {
        $name = (string)$xml['NAME'];
        $type = strtolower((string)$xml['TYPE']);
        $fields = explode(',', (string)$xml['FIELDS']);
        $fields = array_map('trim', $fields);

        $key = new xmldb_key($name, $type, $fields);

        if (isset($xml['REFTABLE'])) {
            $key->set_reftable((string)$xml['REFTABLE']);
        }

        if (isset($xml['REFFIELDS'])) {
            $reffields = explode(',', (string)$xml['REFFIELDS']);
            $reffields = array_map('trim', $reffields);
            $key->set_reffields($reffields);
        }

        return $key;
    }

    /**
     * Parsear índice desde XML
     *
     * @param \SimpleXMLElement $xml
     * @return xmldb_index
     */
    private function parse_index(\SimpleXMLElement $xml): xmldb_index {
        $name = (string)$xml['NAME'];
        $unique = isset($xml['UNIQUE']) && strtolower((string)$xml['UNIQUE']) === 'true';
        $fields = explode(',', (string)$xml['FIELDS']);
        $fields = array_map('trim', $fields);

        return new xmldb_index($name, $unique, $fields);
    }
}
