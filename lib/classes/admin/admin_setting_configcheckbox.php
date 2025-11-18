<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Checkbox admin setting
 *
 * Similar to Moodle's admin_setting_configcheckbox.
 * Renders a checkbox for yes/no settings.
 *
 * @package core\admin
 */
class admin_setting_configcheckbox extends admin_setting {

    /** @var string Value when checked */
    public string $yes;

    /** @var string Value when unchecked */
    public string $no;

    /**
     * Constructor
     *
     * @param string $name Setting name
     * @param string $visiblename Visible name
     * @param string $description Description
     * @param int $defaultsetting Default (0 or 1)
     * @param string $yes Value for checked
     * @param string $no Value for unchecked
     */
    public function __construct(
        string $name,
        string $visiblename,
        string $description,
        int $defaultsetting = 0,
        string $yes = '1',
        string $no = '0'
    ) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
        $this->yes = $yes;
        $this->no = $no;
    }

    /**
     * Output HTML for checkbox
     *
     * @param mixed $data Current value
     * @param string $query Search query
     * @return string HTML
     */
    public function output_html($data, string $query = ''): string {
        $name = htmlspecialchars($this->name);
        $checked = ($data == $this->yes) ? 'checked' : '';

        $html = '<input type="hidden" name="s_' . $name . '" value="' . $this->no . '" />';
        $html .= '<input type="checkbox" ';
        $html .= 'name="s_' . $name . '" ';
        $html .= 'value="' . $this->yes . '" ';
        $html .= $checked . ' ';
        $html .= 'class="form-check-input" />';

        return $html;
    }

    /**
     * Convert from database
     *
     * @param string $value Stored value
     * @return string Yes or no value
     */
    protected function config_read(string $value) {
        return ($value == $this->yes) ? $this->yes : $this->no;
    }
}
