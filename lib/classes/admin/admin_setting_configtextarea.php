<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Textarea admin setting
 *
 * Similar to Moodle's admin_setting_configtextarea.
 * Renders a multi-line textarea.
 *
 * @package core\admin
 */
class admin_setting_configtextarea extends admin_setting {

    /** @var int Number of rows */
    public int $rows;

    /** @var int Number of columns */
    public int $cols;

    /**
     * Constructor
     *
     * @param string $name Setting name
     * @param string $visiblename Visible name
     * @param string $description Description
     * @param string $defaultsetting Default value
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     */
    public function __construct(
        string $name,
        string $visiblename,
        string $description,
        string $defaultsetting = '',
        int $rows = 5,
        int $cols = 50
    ) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
        $this->rows = $rows;
        $this->cols = $cols;
    }

    /**
     * Output HTML for textarea
     *
     * @param mixed $data Current value
     * @param string $query Search query
     * @return string HTML
     */
    public function output_html($data, string $query = ''): string {
        $value = htmlspecialchars($data ?? '');
        $name = htmlspecialchars($this->name);

        $html = '<textarea ';
        $html .= 'name="s_' . $name . '" ';
        $html .= 'rows="' . $this->rows . '" ';
        $html .= 'cols="' . $this->cols . '" ';
        $html .= 'class="form-control">';
        $html .= $value;
        $html .= '</textarea>';

        return $html;
    }
}
