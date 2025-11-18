<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Select/dropdown admin setting
 *
 * Similar to Moodle's admin_setting_configselect.
 * Renders a dropdown select field.
 *
 * @package core\admin
 */
class admin_setting_configselect extends admin_setting {

    /** @var array Choices (value => label) */
    public array $choices;

    /**
     * Constructor
     *
     * @param string $name Setting name
     * @param string $visiblename Visible name
     * @param string $description Description
     * @param string|int $defaultsetting Default value
     * @param array $choices Options (value => label)
     */
    public function __construct(
        string $name,
        string $visiblename,
        string $description,
        $defaultsetting,
        array $choices
    ) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
        $this->choices = $choices;
    }

    /**
     * Output HTML for select
     *
     * @param mixed $data Current value
     * @param string $query Search query
     * @return string HTML
     */
    public function output_html($data, string $query = ''): string {
        $name = htmlspecialchars($this->name);

        $html = '<select name="s_' . $name . '" class="form-select">';

        foreach ($this->choices as $value => $label) {
            $selected = ($data == $value) ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($value) . '" ' . $selected . '>';
            $html .= htmlspecialchars($label);
            $html .= '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * Validate selection
     *
     * @param mixed $data Value
     * @return bool|string
     */
    public function validate($data) {
        if (!array_key_exists($data, $this->choices)) {
            return 'Invalid selection';
        }

        return true;
    }
}
