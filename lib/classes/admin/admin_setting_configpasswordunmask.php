<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Admin password setting with show/hide toggle
 *
 * Similar to Moodle's admin_setting_configpasswordunmask.
 * Password field with option to reveal text.
 *
 * @package core\admin
 */
class admin_setting_configpasswordunmask extends admin_setting {

    /** @var int Input size */
    public int $size;

    /**
     * Constructor
     *
     * @param string $name Setting name
     * @param string $visiblename Visible title
     * @param string $description Description
     * @param mixed $defaultsetting Default value
     * @param int $size Input size (default 30)
     */
    public function __construct(
        string $name,
        string $visiblename,
        string $description,
        $defaultsetting,
        int $size = 30
    ) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
        $this->size = $size;
    }

    /**
     * Validate data (passwords always valid unless empty when required)
     *
     * @param mixed $data Data to validate
     * @return bool|string True if valid, error string otherwise
     */
    public function validate($data) {
        // Allow empty passwords (can be optional)
        return true;
    }

    /**
     * Generate HTML for setting
     *
     * @param mixed $data Current value
     * @param string $query Search query (for highlighting)
     * @return string HTML
     */
    public function output_html($data, string $query = ''): string {
        global $OUTPUT;

        $value = $data ?? $this->get_setting();
        $context = $this->get_template_data();
        $context['value'] = htmlspecialchars($value);
        $context['size'] = $this->size;
        $context['inputname'] = 's_' . $this->plugin . '_' . $this->name;

        return render_template('admin/setting_configpasswordunmask', $context);
    }

    /**
     * Get template data
     *
     * @return array Template context
     */
    public function get_template_data(): array {
        $data = parent::get_template_data();
        $data['size'] = $this->size;
        return $data;
    }
}
