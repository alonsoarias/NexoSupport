<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Admin settings page
 *
 * Similar to Moodle's admin_settingpage.
 * Contains a collection of settings for a specific area.
 *
 * @package core\admin
 */
class admin_settingpage {

    /** @var string Unique page name */
    public string $name;

    /** @var string Visible page title */
    public string $visiblename;

    /** @var string Required capability */
    public string $req_capability;

    /** @var admin_setting[] Settings on this page */
    public array $settings = [];

    /**
     * Constructor
     *
     * @param string $name Page name
     * @param string $visiblename Visible title
     * @param string $req_capability Required capability
     */
    public function __construct(
        string $name,
        string $visiblename,
        string $req_capability = 'nexosupport/admin:manageconfig'
    ) {
        $this->name = $name;
        $this->visiblename = $visiblename;
        $this->req_capability = $req_capability;
    }

    /**
     * Add a setting to this page
     *
     * @param admin_setting $setting Setting to add
     * @return void
     */
    public function add(admin_setting $setting): void {
        $this->settings[] = $setting;
    }

    /**
     * Check if user has permission to access this page
     *
     * @return bool Has permission
     */
    public function check_access(): bool {
        return has_capability($this->req_capability);
    }

    /**
     * Get all settings on this page
     *
     * @return admin_setting[] Settings
     */
    public function get_settings(): array {
        return $this->settings;
    }

    /**
     * Save all settings from form data
     *
     * @param array $data Form data (from $_POST)
     * @return array Errors (empty if success)
     */
    public function save_settings(array $data): array {
        $errors = [];

        foreach ($this->settings as $setting) {
            $key = 's_' . $setting->name;

            if (isset($data[$key])) {
                $result = $setting->write_setting($data[$key]);

                if ($result !== true) {
                    $errors[$setting->name] = $result;
                }
            }
        }

        return $errors;
    }

    /**
     * Get template data for rendering
     *
     * @return array Template data
     */
    public function get_template_data(): array {
        $settings_data = [];

        foreach ($this->settings as $setting) {
            $settings_data[] = $setting->get_template_data();
        }

        return [
            'name' => $this->name,
            'visiblename' => $this->visiblename,
            'settings' => $settings_data,
            'hassettings' => !empty($settings_data),
        ];
    }
}
