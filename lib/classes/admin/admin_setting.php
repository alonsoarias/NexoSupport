<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Base admin setting class
 *
 * Similar to Moodle's admin_setting class.
 * All admin settings extend this base class.
 *
 * @package core\admin
 */
abstract class admin_setting {

    /** @var string Unique name for the setting (component/name format) */
    public string $name;

    /** @var string Setting name without component prefix */
    public string $settingname;

    /** @var string Visible name for display */
    public string $visiblename;

    /** @var string Description/help text */
    public string $description;

    /** @var mixed Default value */
    public $defaultsetting;

    /** @var string Plugin/component name */
    public string $plugin;

    /**
     * Constructor
     *
     * @param string $name Unique setting name
     * @param string $visiblename Visible name
     * @param string $description Description text
     * @param mixed $defaultsetting Default value
     */
    public function __construct(string $name, string $visiblename, string $description, $defaultsetting) {
        // Store full name for form field names
        $this->name = $name;
        $this->visiblename = $visiblename;
        $this->description = $description;
        $this->defaultsetting = $defaultsetting;

        // Extract plugin name from setting name (format: plugin/settingname)
        if (strpos($name, '/') !== false) {
            list($this->plugin, $this->settingname) = explode('/', $name, 2);
        } else {
            $this->plugin = 'core';
            $this->settingname = $name;
        }
    }

    /**
     * Get current setting value from database
     *
     * @return mixed Current value or default if not set
     */
    public function get_setting() {
        global $DB;

        try {
            $record = $DB->get_record('config', [
                'component' => $this->plugin,
                'name' => $this->settingname
            ]);

            if ($record) {
                return $this->config_read($record->value);
            }
        } catch (\Exception $e) {
            // Setting not found, return default
        }

        return $this->defaultsetting;
    }

    /**
     * Write setting value to database
     *
     * @param mixed $data Setting value
     * @return bool Success
     */
    public function write_setting($data): bool {
        global $DB;

        // Validate first
        $validated = $this->validate($data);
        if ($validated !== true) {
            return false;
        }

        // Convert value for storage
        $value = $this->config_write($data);

        try {
            $existing = $DB->get_record('config', [
                'component' => $this->plugin,
                'name' => $this->settingname
            ]);

            if ($existing) {
                // Update
                $existing->value = $value;
                $DB->update_record('config', $existing);
            } else {
                // Insert
                $record = new \stdClass();
                $record->component = $this->plugin;
                $record->name = $this->settingname;
                $record->value = $value;
                $DB->insert_record('config', $record);
            }

            return true;
        } catch (\Exception $e) {
            debugging('Error writing setting: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate setting value
     *
     * @param mixed $data Value to validate
     * @return bool|string True if valid, error message otherwise
     */
    public function validate($data) {
        return true; // Override in subclasses
    }

    /**
     * Convert value from database format
     *
     * @param string $value Stored value
     * @return mixed Converted value
     */
    protected function config_read(string $value) {
        return $value; // Override in subclasses if needed
    }

    /**
     * Convert value to database format
     *
     * @param mixed $value Value to store
     * @return string Converted value
     */
    protected function config_write($value): string {
        return (string)$value; // Override in subclasses if needed
    }

    /**
     * Output HTML for the setting
     *
     * @param mixed $data Current value
     * @param string $query Search query for highlighting
     * @return string HTML output
     */
    abstract public function output_html($data, string $query = ''): string;

    /**
     * Get setting for template rendering
     *
     * @return array Setting data for template
     */
    public function get_template_data(): array {
        $data = $this->get_setting();

        return [
            'name' => $this->name,
            'visiblename' => $this->visiblename,
            'description' => $this->description,
            'value' => $data,
            'defaultvalue' => $this->defaultsetting,
            'html' => $this->output_html($data),
        ];
    }
}
