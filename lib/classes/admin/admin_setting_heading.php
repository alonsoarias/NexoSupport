<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Admin heading (non-setting)
 *
 * Similar to Moodle's admin_setting_heading.
 * Displays a heading/description without an actual setting field.
 * Used for organizing settings visually.
 *
 * @package core\admin
 */
class admin_setting_heading extends admin_setting {

    /**
     * Constructor
     *
     * @param string $name Setting name (for identification)
     * @param string $visiblename Visible heading
     * @param string $description Description/information
     */
    public function __construct(
        string $name,
        string $visiblename,
        string $description = ''
    ) {
        parent::__construct($name, $visiblename, $description, '');
    }

    /**
     * Headings don't get stored
     *
     * @return mixed Always returns empty string
     */
    public function get_setting() {
        return '';
    }

    /**
     * Headings don't write data
     *
     * @param mixed $data Not used
     * @return bool Always true
     */
    public function write_setting($data): bool {
        // Headings don't save anything
        return true;
    }

    /**
     * Headings always validate
     *
     * @param mixed $data Not used
     * @return bool Always true
     */
    public function validate($data) {
        return true;
    }

    /**
     * Generate HTML for heading
     *
     * @param mixed $data Not used
     * @param string $query Search query (for highlighting)
     * @return string HTML
     */
    public function output_html($data, string $query = ''): string {
        global $OUTPUT;

        $context = $this->get_template_data();
        return render_template('admin/setting_heading', $context);
    }

    /**
     * Headings don't read from config
     *
     * @param string $data Not used
     * @return string Empty string
     */
    public function config_read(string $data): string {
        return '';
    }

    /**
     * Headings don't write to config
     *
     * @param mixed $data Not used
     * @return string Empty string
     */
    public function config_write($data): string {
        return '';
    }
}
