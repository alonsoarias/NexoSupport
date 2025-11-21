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
class admin_settingpage implements part_of_admin_tree {

    /** @var string Unique page name */
    public string $name;

    /** @var string Visible page title */
    public string $visiblename;

    /** @var string|array Required capability(ies) */
    public $req_capability;

    /** @var int Context level */
    public int $context;

    /** @var admin_setting[] Settings on this page */
    public array $settings = [];

    /** @var bool Whether this page is hidden */
    public bool $hidden = false;

    /** @var array Dependencies between settings */
    protected array $dependencies = [];

    /** @var string Optional CSS class for icon */
    public string $iconclass = '';

    /**
     * Constructor
     *
     * @param string $name Page name
     * @param string $visiblename Visible title
     * @param string|array $req_capability Required capability(ies)
     * @param bool $hidden Whether hidden
     * @param int $context Context level
     */
    public function __construct(
        string $name,
        string $visiblename,
        $req_capability = 'nexosupport/admin:manageconfig',
        bool $hidden = false,
        int $context = CONTEXT_SYSTEM
    ) {
        $this->name = $name;
        $this->visiblename = $visiblename;
        $this->req_capability = $req_capability;
        $this->hidden = $hidden;
        $this->context = $context;
    }

    /**
     * Locate this page by name
     *
     * @param string $name Name to find
     * @return part_of_admin_tree|null This if name matches, null otherwise
     */
    public function locate(string $name): ?part_of_admin_tree {
        if ($this->name === $name) {
            return $this;
        }
        return null;
    }

    /**
     * Can't prune self
     *
     * @param string $name Name to prune
     * @return bool Always false
     */
    public function prune(string $name): bool {
        return false;
    }

    /**
     * Search for text in this page and its settings
     *
     * @param string $query Search query
     * @return array This page if matches, empty otherwise
     */
    public function search(string $query): array {
        $query = strtolower($query);

        // Check page itself
        if (strpos(strtolower($this->name), $query) !== false ||
            strpos(strtolower($this->visiblename), $query) !== false) {
            return [$this];
        }

        // Check settings
        foreach ($this->settings as $setting) {
            if (strpos(strtolower($setting->name), $query) !== false ||
                strpos(strtolower($setting->visiblename), $query) !== false ||
                strpos(strtolower($setting->description), $query) !== false) {
                return [$this];
            }
        }

        return [];
    }

    /**
     * Check if user has access to this page
     *
     * @return bool True if accessible
     */
    public function check_access(): bool {
        if (is_array($this->req_capability)) {
            foreach ($this->req_capability as $cap) {
                if (has_capability($cap, $this->context)) {
                    return true;
                }
            }
            return false;
        }
        return has_capability($this->req_capability, $this->context);
    }

    /**
     * Check if this page is hidden
     *
     * @return bool True if hidden
     */
    public function is_hidden(): bool {
        return $this->hidden;
    }

    /**
     * Settings pages show save button
     *
     * @return bool True if has saveable settings
     */
    public function show_save(): bool {
        foreach ($this->settings as $setting) {
            if (!($setting instanceof admin_setting_heading)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get page name
     *
     * @return string Page name
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get visible name
     *
     * @return string Visible name
     */
    public function get_visiblename(): string {
        return $this->visiblename;
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
     * Get all settings on this page
     *
     * @return admin_setting[] Settings
     */
    public function get_settings(): array {
        return $this->settings;
    }

    /**
     * Add a dependency between settings
     *
     * @param string $settingname Setting that depends
     * @param string $dependson Setting it depends on
     * @param string $condition Condition type
     * @param mixed $value Condition value
     * @return void
     */
    public function add_dependency(string $settingname, string $dependson, string $condition = 'eq', $value = null): void {
        if (!isset($this->dependencies[$settingname])) {
            $this->dependencies[$settingname] = [];
        }
        $this->dependencies[$settingname][] = [
            'dependson' => $dependson,
            'condition' => $condition,
            'value' => $value,
        ];
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
            // Skip headings and non-saveable settings
            if ($setting instanceof admin_setting_heading) {
                continue;
            }

            $key = 's_' . $setting->name;

            // Handle checkboxes (not sent when unchecked)
            if ($setting instanceof admin_setting_configcheckbox) {
                $value = isset($data[$key]) ? $data[$key] : '0';
                $result = $setting->write_setting($value);
            } elseif (isset($data[$key])) {
                // Validate and write
                $validation = $setting->validate($data[$key]);
                if ($validation !== true) {
                    $errors[$setting->name] = $validation;
                    continue;
                }
                $result = $setting->write_setting($data[$key]);
            } else {
                continue;
            }

            if ($result !== true && !isset($errors[$setting->name])) {
                $errors[$setting->name] = get_string('errorwritingsetting', 'admin');
            }
        }

        return $errors;
    }

    /**
     * Set icon class
     *
     * @param string $class CSS class for icon
     * @return void
     */
    public function set_icon(string $class): void {
        $this->iconclass = $class;
    }

    /**
     * Get template data for rendering
     *
     * @return array Template data
     */
    public function get_template_data(): array {
        $settings_data = [];

        foreach ($this->settings as $setting) {
            $data = $setting->get_template_data();

            // Add dependency info
            if (isset($this->dependencies[$setting->name])) {
                $data['dependencies'] = $this->dependencies[$setting->name];
                $data['hasdependencies'] = true;
            }

            $settings_data[] = $data;
        }

        return [
            'name' => $this->name,
            'visiblename' => $this->visiblename,
            'iconclass' => $this->iconclass,
            'settings' => $settings_data,
            'hassettings' => !empty($settings_data),
            'showsave' => $this->show_save(),
            'issettingpage' => true,
            'url' => '/admin/settings?page=' . urlencode($this->name),
        ];
    }
}
