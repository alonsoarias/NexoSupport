<?php
/**
 * Event base class.
 *
 * All event classes must extend this base class.
 * Similar to Moodle's \core\event\base
 *
 * @package    core
 * @subpackage event
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\event;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Base event class.
 *
 * @package    core
 * @since      NexoSupport 1.1.6
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {

    /** CRUD constants */
    const ACTION_CREATED = 'c';
    const ACTION_UPDATED = 'u';
    const ACTION_DELETED = 'd';
    const ACTION_READ = 'r';

    /**
     * @var array Event data
     */
    protected $data = [];

    /**
     * @var \stdClass Context object
     */
    protected $context;

    /**
     * Create an event.
     *
     * @param array $data Event data
     * @return \core\event\base Event object
     */
    public static function create(array $data = null) {
        $event = new static();
        $event->init($data);
        return $event;
    }

    /**
     * Initialize event data.
     *
     * @param array|null $data Event data
     */
    protected function init($data = null) {
        global $USER, $CFG;

        $this->data = [
            'eventname' => get_called_class(),
            'component' => $this->get_component(),
            'action' => static::get_action(),
            'target' => static::get_target(),
            'objecttable' => static::get_objecttable(),
            'objectid' => null,
            'crud' => static::get_crud(),
            'contextid' => \core\rbac\context::system()->id,
            'contextlevel' => CONTEXT_SYSTEM,
            'contextinstanceid' => 0,
            'userid' => isset($USER->id) ? $USER->id : 0,
            'relateduserid' => null,
            'anonymous' => 0,
            'other' => null,
            'timecreated' => time(),
            'origin' => 'web',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'realuserid' => null,
        ];

        if ($data) {
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $this->data)) {
                    $this->data[$key] = $value;
                }
            }
        }

        // Set context if provided
        if (isset($data['context'])) {
            $this->context = $data['context'];
            $this->data['contextid'] = $this->context->id;
            $this->data['contextlevel'] = $this->context->contextlevel;
            $this->data['contextinstanceid'] = $this->context->instanceid;
        } else if (isset($data['contextid'])) {
            $this->context = \core\rbac\context::instance_by_id($data['contextid']);
        }

        // Validate required data
        $this->validate_data();
    }

    /**
     * Trigger the event.
     *
     * @return bool Success
     */
    public function trigger() {
        global $DB;

        // Validate before triggering
        $this->validate_data();

        // Insert into logstore_standard_log
        $record = new \stdClass();
        foreach ($this->data as $key => $value) {
            if ($key === 'other' && is_array($value)) {
                $record->$key = json_encode($value);
            } else {
                $record->$key = $value;
            }
        }

        try {
            $DB->insert_record('logstore_standard_log', $record);
            return true;
        } catch (\Exception $e) {
            debugging('Failed to log event: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Validate event data.
     */
    protected function validate_data() {
        if (empty($this->data['eventname'])) {
            throw new \coding_exception('Event name is required');
        }
        if (empty($this->data['component'])) {
            throw new \coding_exception('Event component is required');
        }
    }

    /**
     * Get component name.
     *
     * @return string Component name (e.g., 'core', 'mod_forum')
     */
    protected function get_component() {
        $classname = get_called_class();
        $parts = explode('\\', $classname);

        if ($parts[0] === 'core') {
            return 'core';
        }

        // For plugins: mod_forum, auth_manual, etc.
        if (count($parts) >= 2) {
            return $parts[0] . '_' . $parts[1];
        }

        return 'core';
    }

    /**
     * Get event action.
     *
     * @return string Action (e.g., 'viewed', 'created', 'updated', 'deleted')
     */
    abstract protected static function get_action();

    /**
     * Get event target.
     *
     * @return string Target (e.g., 'user', 'course', 'role')
     */
    abstract protected static function get_target();

    /**
     * Get object table.
     *
     * @return string|null Table name
     */
    protected static function get_objecttable() {
        return null;
    }

    /**
     * Get CRUD type.
     *
     * @return string CRUD (c=create, r=read, u=update, d=delete)
     */
    abstract protected static function get_crud();

    /**
     * Get event data.
     *
     * @param string $name Data key
     * @return mixed Data value
     */
    public function get_data($name = null) {
        if ($name === null) {
            return $this->data;
        }
        return $this->data[$name] ?? null;
    }

    /**
     * Get event context.
     *
     * @return \core\rbac\context Context object
     */
    public function get_context() {
        if ($this->context === null) {
            $this->context = \core\rbac\context::instance_by_id($this->data['contextid']);
        }
        return $this->context;
    }

    /**
     * Get event name.
     *
     * @return string Event name
     */
    public static function get_name() {
        return get_string('event' . static::get_action() . static::get_target(), 'core');
    }

    /**
     * Get event description.
     *
     * @return string Event description
     */
    abstract public function get_description();

    /**
     * Get URL related to the event.
     *
     * @return \moodle_url|null
     */
    public function get_url() {
        return null;
    }

    /**
     * Get object ID.
     *
     * @return int|null Object ID
     */
    public function get_objectid() {
        return $this->data['objectid'];
    }

    /**
     * Get user ID.
     *
     * @return int User ID
     */
    public function get_userid() {
        return $this->data['userid'];
    }

    /**
     * Set other data.
     *
     * @param mixed $other Other data (array or string)
     */
    public function set_other($other) {
        $this->data['other'] = $other;
    }
}
