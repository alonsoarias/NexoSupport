<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Admin number setting
 *
 * Similar to Moodle's admin_setting_configtext with number validation.
 * Input field for numeric values with min/max validation.
 *
 * @package core\admin
 */
class admin_setting_confignumber extends admin_setting {

    /** @var int Input size */
    public int $size;

    /** @var int|null Minimum allowed value */
    public ?int $min;

    /** @var int|null Maximum allowed value */
    public ?int $max;

    /**
     * Constructor
     *
     * @param string $name Setting name
     * @param string $visiblename Visible title
     * @param string $description Description
     * @param mixed $defaultsetting Default value
     * @param int $size Input size (default 10)
     * @param int|null $min Minimum value (null = no limit)
     * @param int|null $max Maximum value (null = no limit)
     */
    public function __construct(
        string $name,
        string $visiblename,
        string $description,
        $defaultsetting,
        int $size = 10,
        ?int $min = null,
        ?int $max = null
    ) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
        $this->size = $size;
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * Validate number
     *
     * @param mixed $data Data to validate
     * @return bool|string True if valid, error string otherwise
     */
    public function validate($data) {
        // Allow empty (will use default)
        if ($data === '' || $data === null) {
            return true;
        }

        // Must be numeric
        if (!is_numeric($data)) {
            return get_string('validateerror', 'admin') . ': ' . get_string('notnumeric', 'core');
        }

        $number = (int)$data;

        // Check min
        if ($this->min !== null && $number < $this->min) {
            return get_string('validateerror', 'admin') . ': ' .
                   get_string('numbertoosmall', 'core', $this->min);
        }

        // Check max
        if ($this->max !== null && $number > $this->max) {
            return get_string('validateerror', 'admin') . ': ' .
                   get_string('numbertoobig', 'core', $this->max);
        }

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
        $context['min'] = $this->min;
        $context['max'] = $this->max;

        return render_template('admin/setting_confignumber', $context);
    }

    /**
     * Get template data
     *
     * @return array Template context
     */
    public function get_template_data(): array {
        $data = parent::get_template_data();
        $data['size'] = $this->size;
        $data['min'] = $this->min;
        $data['max'] = $this->max;
        return $data;
    }
}
