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
        global $USER;

        $this->data = [
            'eventname' => get_called_class(),
            'action' => static::get_action(),
            'objecttable' => static::get_objecttable(),
            'objectid' => null,
            'userid' => isset($USER->id) ? $USER->id : 0,
            'contextid' => \core\rbac\context::system()->id,
            'other' => null,
            'timecreated' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
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
        if (empty($this->data['action'])) {
            throw new \coding_exception('Event action is required');
        }
    }

    /**
     * Get event action.
     *
     * @return string Action (e.g., 'created', 'updated', 'deleted', 'viewed')
     */
    abstract protected static function get_action();

    /**
     * Get object table.
     *
     * @return string|null Table name
     */
    protected static function get_objecttable() {
        return null;
    }

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
        $classname = get_called_class();
        $parts = explode('\\', $classname);
        $shortname = end($parts);
        return get_string('event_' . $shortname, 'core');
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
     * @return \nexo_url|null
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
