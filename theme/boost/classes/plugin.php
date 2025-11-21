<?php
/**
 * Boost Theme Plugin Class
 *
 * @package    theme_boost
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Theme plugin class
 */
class plugin extends \core\plugininfo\theme {

    /**
     * Get available layouts
     *
     * @return array Layout definitions
     */
    public function get_layouts(): array {
        return [
            'base' => [
                'file' => 'columns1.php',
                'regions' => [],
            ],
            'standard' => [
                'file' => 'drawers.php',
                'regions' => ['side-pre'],
                'defaultregion' => 'side-pre',
            ],
            'course' => [
                'file' => 'drawers.php',
                'regions' => ['side-pre'],
                'defaultregion' => 'side-pre',
                'options' => ['langmenu' => true],
            ],
            'admin' => [
                'file' => 'drawers.php',
                'regions' => ['side-pre'],
                'defaultregion' => 'side-pre',
            ],
            'mydashboard' => [
                'file' => 'drawers.php',
                'regions' => ['side-pre'],
                'defaultregion' => 'side-pre',
                'options' => ['nonavbar' => true, 'langmenu' => true],
            ],
            'login' => [
                'file' => 'login.php',
                'regions' => [],
                'options' => ['langmenu' => true, 'nonavbar' => true, 'nofooter' => true],
            ],
            'maintenance' => [
                'file' => 'maintenance.php',
                'regions' => [],
            ],
            'embedded' => [
                'file' => 'embedded.php',
                'regions' => [],
            ],
        ];
    }

    /**
     * Get SCSS source
     *
     * @return string SCSS content
     */
    public function get_scss(): string {
        $scssfile = $this->path . '/scss/preset/default.scss';

        if (file_exists($scssfile)) {
            return file_get_contents($scssfile);
        }

        return '';
    }

    /**
     * Get parent themes
     *
     * @return array Parent theme names
     */
    public function get_parents(): array {
        return []; // Boost is the root theme
    }
}
