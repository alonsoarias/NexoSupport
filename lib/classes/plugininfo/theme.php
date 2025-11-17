<?php
namespace core\plugininfo;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Clase base para temas
 *
 * Todos los plugins theme_* DEBEN extender esta clase
 * e implementar los métodos abstractos.
 *
 * @package core\plugininfo
 */
abstract class theme extends base {

    /**
     * Obtener layouts del tema
     *
     * Retorna un array de layouts disponibles con su configuración.
     * Cada layout define la estructura de una página.
     *
     * @return array
     */
    abstract public function get_layouts(): array;

    /**
     * Obtener SCSS del tema
     *
     * Retorna el código SCSS que será compilado a CSS.
     *
     * @return string
     */
    abstract public function get_scss(): string;

    /**
     * Obtener temas padre
     *
     * Un tema puede heredar de uno o más temas padre.
     *
     * @return array
     */
    public function get_parents(): array {
        return [];
    }

    /**
     * Obtener configuración del tema
     *
     * @return array
     */
    public function get_config(): array {
        return get_config($this->component);
    }

    /**
     * Obtener URL de la hoja de estilos compilada
     *
     * @return string
     */
    public function get_stylesheet_url(): string {
        return "/theme/{$this->name}/style/{$this->name}.css";
    }

    /**
     * ¿El tema necesita recompilación?
     *
     * @return bool
     */
    public function needs_recompile(): bool {
        $cssfile = $this->path . "/style/{$this->name}.css";

        if (!file_exists($cssfile)) {
            return true;
        }

        $scssfile = $this->path . "/scss/{$this->name}.scss";

        if (!file_exists($scssfile)) {
            return false;
        }

        return filemtime($scssfile) > filemtime($cssfile);
    }

    /**
     * Compilar SCSS a CSS
     *
     * @return bool
     */
    public function compile(): bool {
        // Esta funcionalidad se implementará en fases posteriores
        // Por ahora, retornamos true
        return true;
    }

    /**
     * Obtener preview del tema (screenshot)
     *
     * @return string|null URL del screenshot
     */
    public function get_preview_url(): ?string {
        $preview = $this->path . '/pix/screenshot.jpg';

        if (file_exists($preview)) {
            return "/theme/{$this->name}/pix/screenshot.jpg";
        }

        $preview = $this->path . '/pix/screenshot.png';

        if (file_exists($preview)) {
            return "/theme/{$this->name}/pix/screenshot.png";
        }

        return null;
    }

    /**
     * Obtener regiones disponibles del tema
     *
     * @return array
     */
    public function get_regions(): array {
        $layouts = $this->get_layouts();
        $regions = [];

        foreach ($layouts as $layout) {
            if (isset($layout['regions'])) {
                $regions = array_merge($regions, $layout['regions']);
            }
        }

        return array_unique($regions);
    }

    /**
     * Obtener layout por nombre
     *
     * @param string $layoutname
     * @return array|null
     */
    public function get_layout(string $layoutname): ?array {
        $layouts = $this->get_layouts();

        return $layouts[$layoutname] ?? null;
    }
}
