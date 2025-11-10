<?php

declare(strict_types=1);

namespace ISER\Core\Utils;

use DOMDocument;
use DOMXPath;
use SimpleXMLElement;
use Exception;

/**
 * XML Parser - ISER Authentication System
 *
 * Clase para parsear y manipular archivos XML de manera segura
 * Soporta múltiples métodos de parsing y validación
 *
 * @package ISER\Core\Utils
 * @author ISER Desarrollo
 * @license Propietario
 */
class XMLParser
{
    /**
     * @var DOMDocument Documento DOM
     */
    private ?DOMDocument $dom = null;

    /**
     * @var SimpleXMLElement Elemento SimpleXML
     */
    private ?SimpleXMLElement $simpleXML = null;

    /**
     * @var array Opciones de parseo
     */
    private array $options = [
        'recover' => true,
        'remove_blank_nodes' => true,
        'strict' => false,
        'encoding' => 'UTF-8'
    ];

    /**
     * Constructor
     *
     * @param array $options Opciones de configuración
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Parsear XML desde string
     *
     * @param string $xml Contenido XML
     * @param bool $useSimpleXML Usar SimpleXML en lugar de DOM
     * @return self
     * @throws Exception Si el XML es inválido
     */
    public function parseString(string $xml, bool $useSimpleXML = false): self
    {
        if (empty($xml)) {
            throw new Exception('El contenido XML está vacío');
        }

        // Deshabilitar errores de libxml para manejarlos manualmente
        $previousValue = libxml_use_internal_errors(true);

        try {
            if ($useSimpleXML) {
                $this->parseWithSimpleXML($xml);
            } else {
                $this->parseWithDOM($xml);
            }

            // Verificar errores de libxml
            $errors = libxml_get_errors();
            if (!empty($errors) && $this->options['strict']) {
                $errorMessages = array_map(function($error) {
                    return trim($error->message);
                }, $errors);
                throw new Exception('Errores al parsear XML: ' . implode(', ', $errorMessages));
            }

            libxml_clear_errors();
        } finally {
            libxml_use_internal_errors($previousValue);
        }

        return $this;
    }

    /**
     * Parsear XML desde archivo
     *
     * @param string $filePath Ruta del archivo
     * @param bool $useSimpleXML Usar SimpleXML
     * @return self
     * @throws Exception Si el archivo no existe o no se puede leer
     */
    public function parseFile(string $filePath, bool $useSimpleXML = false): self
    {
        if (!file_exists($filePath)) {
            throw new Exception("Archivo XML no encontrado: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new Exception("No se puede leer el archivo XML: {$filePath}");
        }

        $xml = file_get_contents($filePath);
        if ($xml === false) {
            throw new Exception("Error al leer el archivo XML: {$filePath}");
        }

        return $this->parseString($xml, $useSimpleXML);
    }

    /**
     * Parsear con DOMDocument
     *
     * @param string $xml Contenido XML
     * @throws Exception Si falla el parseo
     */
    private function parseWithDOM(string $xml): void
    {
        $this->dom = new DOMDocument('1.0', $this->options['encoding']);

        // Configurar opciones del DOM
        $this->dom->preserveWhiteSpace = !$this->options['remove_blank_nodes'];
        $this->dom->formatOutput = true;
        $this->dom->recover = $this->options['recover'];

        // Parsear XML
        $loaded = $this->dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);

        if (!$loaded) {
            throw new Exception('No se pudo parsear el XML con DOMDocument');
        }
    }

    /**
     * Parsear con SimpleXML
     *
     * @param string $xml Contenido XML
     * @throws Exception Si falla el parseo
     */
    private function parseWithSimpleXML(string $xml): void
    {
        $options = LIBXML_NONET | LIBXML_NOBLANKS;

        $this->simpleXML = simplexml_load_string(
            $xml,
            SimpleXMLElement::class,
            $options
        );

        if ($this->simpleXML === false) {
            throw new Exception('No se pudo parsear el XML con SimpleXML');
        }
    }

    /**
     * Obtener valor de un nodo mediante XPath
     *
     * @param string $xpath Expresión XPath
     * @param mixed $default Valor por defecto si no se encuentra
     * @return mixed Valor del nodo
     */
    public function getValue(string $xpath, $default = null)
    {
        if ($this->dom !== null) {
            $domXPath = new DOMXPath($this->dom);
            $nodes = $domXPath->query($xpath);

            if ($nodes && $nodes->length > 0) {
                return $nodes->item(0)->nodeValue;
            }
        } elseif ($this->simpleXML !== null) {
            $result = $this->simpleXML->xpath($xpath);
            if ($result && count($result) > 0) {
                return (string) $result[0];
            }
        }

        return $default;
    }

    /**
     * Obtener múltiples valores mediante XPath
     *
     * @param string $xpath Expresión XPath
     * @return array Valores encontrados
     */
    public function getValues(string $xpath): array
    {
        $values = [];

        if ($this->dom !== null) {
            $domXPath = new DOMXPath($this->dom);
            $nodes = $domXPath->query($xpath);

            if ($nodes) {
                foreach ($nodes as $node) {
                    $values[] = $node->nodeValue;
                }
            }
        } elseif ($this->simpleXML !== null) {
            $result = $this->simpleXML->xpath($xpath);
            if ($result) {
                foreach ($result as $item) {
                    $values[] = (string) $item;
                }
            }
        }

        return $values;
    }

    /**
     * Convertir XML a array
     *
     * @return array Representación en array del XML
     */
    public function toArray(): array
    {
        if ($this->simpleXML !== null) {
            return $this->simpleXMLToArray($this->simpleXML);
        } elseif ($this->dom !== null) {
            // Convertir DOM a SimpleXML primero
            $simpleXML = simplexml_import_dom($this->dom);
            return $this->simpleXMLToArray($simpleXML);
        }

        return [];
    }

    /**
     * Convertir SimpleXMLElement a array recursivamente
     *
     * @param SimpleXMLElement $xml Elemento XML
     * @return array Array resultante
     */
    private function simpleXMLToArray(SimpleXMLElement $xml): array
    {
        $array = [];

        // Obtener atributos
        foreach ($xml->attributes() as $key => $value) {
            $array['@attributes'][$key] = (string) $value;
        }

        // Obtener hijos
        $children = [];
        foreach ($xml->children() as $child) {
            $name = $child->getName();
            $value = $this->simpleXMLToArray($child);

            if (isset($children[$name])) {
                // Si ya existe, convertir a array de elementos
                if (!isset($children[$name][0])) {
                    $children[$name] = [$children[$name]];
                }
                $children[$name][] = $value;
            } else {
                $children[$name] = $value;
            }
        }

        // Obtener texto
        $text = trim((string) $xml);
        if (empty($children)) {
            return empty($text) ? [] : $text;
        }

        if (!empty($text)) {
            $children['@value'] = $text;
        }

        return array_merge($array, $children);
    }

    /**
     * Convertir array a XML
     *
     * @param array $data Datos a convertir
     * @param string $rootElement Nombre del elemento raíz
     * @return string XML generado
     */
    public function fromArray(array $data, string $rootElement = 'root'): string
    {
        $this->dom = new DOMDocument('1.0', $this->options['encoding']);
        $this->dom->formatOutput = true;

        $root = $this->dom->createElement($rootElement);
        $this->dom->appendChild($root);

        $this->arrayToDomElement($data, $root);

        return $this->dom->saveXML();
    }

    /**
     * Convertir array a elementos DOM recursivamente
     *
     * @param array $data Datos
     * @param \DOMElement $element Elemento DOM
     */
    private function arrayToDomElement(array $data, $element): void
    {
        foreach ($data as $key => $value) {
            if ($key === '@attributes') {
                // Agregar atributos
                foreach ($value as $attrKey => $attrValue) {
                    $element->setAttribute($attrKey, $attrValue);
                }
            } elseif ($key === '@value') {
                // Agregar valor de texto
                $element->appendChild($this->dom->createTextNode($value));
            } elseif (is_array($value)) {
                // Manejar arrays
                if (isset($value[0])) {
                    // Array de elementos
                    foreach ($value as $item) {
                        $child = $this->dom->createElement($key);
                        $element->appendChild($child);
                        if (is_array($item)) {
                            $this->arrayToDomElement($item, $child);
                        } else {
                            $child->appendChild($this->dom->createTextNode($item));
                        }
                    }
                } else {
                    // Objeto
                    $child = $this->dom->createElement($key);
                    $element->appendChild($child);
                    $this->arrayToDomElement($value, $child);
                }
            } else {
                // Valor simple
                $child = $this->dom->createElement($key);
                $child->appendChild($this->dom->createTextNode($value));
                $element->appendChild($child);
            }
        }
    }

    /**
     * Validar XML contra un esquema XSD
     *
     * @param string $xsdPath Ruta del archivo XSD
     * @return bool True si es válido
     * @throws Exception Si la validación falla
     */
    public function validateSchema(string $xsdPath): bool
    {
        if ($this->dom === null) {
            throw new Exception('No hay un documento DOM cargado');
        }

        if (!file_exists($xsdPath)) {
            throw new Exception("Archivo XSD no encontrado: {$xsdPath}");
        }

        $previousValue = libxml_use_internal_errors(true);

        try {
            $valid = $this->dom->schemaValidate($xsdPath);

            if (!$valid) {
                $errors = libxml_get_errors();
                $errorMessages = array_map(function($error) {
                    return trim($error->message);
                }, $errors);
                throw new Exception('Errores de validación XSD: ' . implode(', ', $errorMessages));
            }

            return true;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousValue);
        }
    }

    /**
     * Obtener el XML como string
     *
     * @return string XML formateado
     */
    public function toString(): string
    {
        if ($this->dom !== null) {
            return $this->dom->saveXML();
        } elseif ($this->simpleXML !== null) {
            return $this->simpleXML->asXML();
        }

        return '';
    }

    /**
     * Guardar XML en archivo
     *
     * @param string $filePath Ruta del archivo
     * @return bool True si se guardó exitosamente
     * @throws Exception Si no se puede guardar
     */
    public function save(string $filePath): bool
    {
        $xml = $this->toString();

        if (empty($xml)) {
            throw new Exception('No hay contenido XML para guardar');
        }

        $result = file_put_contents($filePath, $xml);

        if ($result === false) {
            throw new Exception("No se pudo guardar el archivo XML: {$filePath}");
        }

        return true;
    }

    /**
     * Obtener el DOMDocument
     *
     * @return DOMDocument|null
     */
    public function getDom(): ?DOMDocument
    {
        return $this->dom;
    }

    /**
     * Obtener el SimpleXMLElement
     *
     * @return SimpleXMLElement|null
     */
    public function getSimpleXML(): ?SimpleXMLElement
    {
        return $this->simpleXML;
    }

    /**
     * Verificar si las extensiones XML están disponibles
     *
     * @return array Estado de las extensiones
     */
    public static function checkExtensions(): array
    {
        return [
            'dom' => extension_loaded('dom'),
            'simplexml' => extension_loaded('simplexml'),
            'libxml' => extension_loaded('libxml'),
            'xml' => extension_loaded('xml'),
            'xmlreader' => extension_loaded('xmlreader'),
            'xmlwriter' => extension_loaded('xmlwriter')
        ];
    }
}
