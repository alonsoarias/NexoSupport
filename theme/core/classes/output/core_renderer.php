<?php
/**
 * NexoSupport - Theme Core - Core Renderer
 *
 * @package    ISER\Theme\Core\Output
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Theme\Core\Output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Core Theme Renderer
 *
 * Base renderer for all themes
 */
class core_renderer
{
    /** @var \Mustache_Engine Template engine */
    protected $mustache;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->mustache = new \Mustache_Engine([
            'loader' => new \Mustache_Loader_FilesystemLoader(BASE_DIR . '/theme/core/templates'),
        ]);
    }

    /**
     * Render template
     *
     * @param string $template Template name
     * @param array $data Template data
     * @return string
     */
    public function render_from_template(string $template, array $data = []): string
    {
        return $this->mustache->render($template, $data);
    }

    /**
     * Render header
     *
     * @return string
     */
    public function header(): string
    {
        return $this->render_from_template('partials/header', [
            'site_name' => get_config('core', 'sitename'),
            'page_title' => $this->page->title ?? '',
        ]);
    }

    /**
     * Render footer
     *
     * @return string
     */
    public function footer(): string
    {
        return $this->render_from_template('partials/footer', [
            'year' => date('Y'),
        ]);
    }

    /**
     * Render navigation
     *
     * @return string
     */
    public function navbar(): string
    {
        return $this->render_from_template('partials/navbar', [
            'nav_items' => $this->get_nav_items(),
        ]);
    }

    /**
     * Get navigation items
     *
     * @return array
     */
    protected function get_nav_items(): array
    {
        // Override in child themes
        return [];
    }
}
