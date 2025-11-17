<?php
namespace core\plugininfo;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Clase base para herramientas administrativas
 *
 * Todos los plugins tool_* DEBEN extender esta clase
 * e implementar los métodos abstractos.
 *
 * @package core\plugininfo
 */
abstract class tool extends base {

    /**
     * ¿Tiene capabilities este tool?
     *
     * @return bool
     */
    abstract public function has_capabilities(): bool;

    /**
     * Obtener sección de settings (opcional)
     *
     * @return string|null
     */
    public function get_settings_section(): ?string {
        return null;
    }

    /**
     * Punto de entrada del tool
     *
     * @return string URL del tool
     */
    public function get_url(): string {
        return "/admin/tool/{$this->name}/index.php";
    }

    /**
     * Obtener nombre visible del tool
     *
     * @return string
     */
    public function get_display_name(): string {
        return get_string('pluginname', $this->component);
    }

    /**
     * Obtener descripción del tool
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
     * ¿El tool debe aparecer en el menú de administración?
     *
     * @return bool
     */
    public function show_in_admin_menu(): bool {
        return true;
    }

    /**
     * Obtener capability requerida para acceder al tool
     *
     * @return string
     */
    public function get_required_capability(): string {
        return 'tool/' . $this->name . ':view';
    }

    /**
     * ¿El usuario actual puede acceder al tool?
     *
     * @return bool
     */
    public function can_access(): bool {
        if (!$this->has_capabilities()) {
            return true; // Sin capabilities, todos pueden acceder
        }

        return has_capability($this->get_required_capability());
    }
}
