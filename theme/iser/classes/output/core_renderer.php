<?php
/**
 * NexoSupport - Theme ISER - Core Renderer
 *
 * @package    theme_iser
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace theme_iser\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * ISER Theme Renderer
 *
 * Overrides core renderer for ISER branding
 */
class core_renderer extends \theme_core\output\core_renderer
{
    /**
     * Render header with ISER branding
     *
     * @return string
     */
    public function header(): string
    {
        $output = parent::header();

        // Add ISER institutional header
        $output .= $this->render_from_template('theme_iser/institutional_header', [
            'logo_url' => '/theme/iser/pix/logo-iser.svg',
            'institution_name' => 'ISER - Instituto Superior de Educación Rural',
        ]);

        return $output;
    }

    /**
     * Render footer with ISER information
     *
     * @return string
     */
    public function footer(): string
    {
        $data = [
            'year' => date('Y'),
            'institution' => 'ISER',
            'version' => get_config('core', 'version'),
        ];

        return $this->render_from_template('theme_iser/footer', $data);
    }
}
