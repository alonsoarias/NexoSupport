<?php
namespace core\plugininfo;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Clase base para reportes
 *
 * Todos los plugins report_* DEBEN extender esta clase
 * e implementar los métodos abstractos.
 *
 * @package core\plugininfo
 */
abstract class report extends base {

    /**
     * Obtener datasource del reporte
     *
     * El datasource define de dónde provienen los datos del reporte.
     *
     * @return object
     */
    abstract public function get_datasource(): object;

    /**
     * Obtener columnas disponibles
     *
     * @return array
     */
    abstract public function get_columns(): array;

    /**
     * Obtener filtros disponibles
     *
     * @return array
     */
    abstract public function get_filters(): array;

    /**
     * Obtener configuración del reporte
     *
     * @return array
     */
    public function get_config(): array {
        return get_config($this->component);
    }

    /**
     * Obtener URL del reporte
     *
     * @return string
     */
    public function get_url(): string {
        return "/report/{$this->name}/index.php";
    }

    /**
     * Obtener nombre visible del reporte
     *
     * @return string
     */
    public function get_display_name(): string {
        return get_string('pluginname', $this->component);
    }

    /**
     * Obtener descripción del reporte
     *
     * @return string
     */
    public function get_description(): string {
        if (string_exists('plugindescription', $this->component)) {
            return get_string('plugindescription', $this->component);
        }
        return '';
    }

    /**
     * ¿El usuario actual puede acceder al reporte?
     *
     * @return bool
     */
    public function can_access(): bool {
        $capability = 'report/' . $this->name . ':view';
        return has_capability($capability);
    }

    /**
     * Obtener agregaciones disponibles
     *
     * @return array
     */
    public function get_aggregations(): array {
        return [];
    }

    /**
     * Ejecutar el reporte con los parámetros dados
     *
     * @param array $params
     * @return array
     */
    public function execute(array $params = []): array {
        // Implementación por defecto
        return [];
    }

    /**
     * ¿El reporte puede exportarse?
     *
     * @return bool
     */
    public function can_export(): bool {
        return true;
    }

    /**
     * Obtener formatos de exportación soportados
     *
     * @return array
     */
    public function get_export_formats(): array {
        return ['csv', 'excel', 'pdf'];
    }
}
