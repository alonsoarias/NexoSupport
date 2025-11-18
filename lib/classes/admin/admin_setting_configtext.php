<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Text input admin setting
 *
 * Similar to Moodle's admin_setting_configtext.
 * Renders a simple text input field.
 *
 * @package core\admin
 */
class admin_setting_configtext extends admin_setting {

    /** @var int Input field size */
    public int $size;

    /** @var string Input type (text, email, url, etc.) */
    public string $type;

    /**
     * Constructor
     *
     * @param string $name Setting name
     * @param string $visiblename Visible name
     * @param string $description Description
     * @param string $defaultsetting Default value
     * @param int $size Input size
     * @param string $type Input type
     */
    public function __construct(
        string $name,
        string $visiblename,
        string $description,
        string $defaultsetting = '',
        int $size = 30,
        string $type = 'text'
    ) {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
        $this->size = $size;
        $this->type = $type;
    }

    /**
     * Output HTML for text input
     *
     * @param mixed $data Current value
     * @param string $query Search query
     * @return string HTML
     */
    public function output_html($data, string $query = ''): string {
        $value = htmlspecialchars($data ?? '');
        $name = htmlspecialchars($this->name);
        $size = $this->size;
        $type = $this->type;

        $html = '<input type="' . $type . '" ';
        $html .= 'name="s_' . $name . '" ';
        $html .= 'value="' . $value . '" ';
        $html .= 'size="' . $size . '" ';
        $html .= 'class="form-control" />';

        return $html;
    }

    /**
     * Validate text input
     *
     * @param mixed $data Value
     * @return bool|string
     */
    public function validate($data) {
        // Type-specific validation
        if ($this->type === 'email' && !empty($data)) {
            if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
                return 'Invalid email format';
            }
        }

        if ($this->type === 'url' && !empty($data)) {
            if (!filter_var($data, FILTER_VALIDATE_URL)) {
                return 'Invalid URL format';
            }
        }

        return true;
    }
}
