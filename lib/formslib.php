<?php
/**
 * formslib.php - NexoSupport form library (Moodle-compatible)
 *
 * Provides nexoform base class for creating forms with validation
 *
 * @package    core
 * @subpackage form
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Base class for NexoSupport forms
 */
abstract class nexoform {
    /** @var NexoQuickForm The form object */
    protected $_form;

    /** @var array Custom data passed to form */
    protected $_customdata;

    /**
     * Constructor
     *
     * @param mixed $action Form action URL
     * @param mixed $customdata Custom data to pass to form
     * @param string $method Form method (post/get)
     * @param string $target Form target
     * @param mixed $attributes Form attributes
     */
    public function __construct($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null) {
        global $CFG;

        if (empty($action)) {
            $action = strip_querystring(qualified_me());
        }

        $this->_customdata = $customdata;
        $this->_form = new NexoQuickForm($this->get_form_identifier(), $method, $action, $target, $attributes);

        $this->definition();
        $this->definition_after_data();
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since NexoSupport 1.1
     */
    public function nexoform($action = null, $customdata = null, $method = 'post', $target = '', $attributes = null) {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct($action, $customdata, $method, $target, $attributes);
    }

    /**
     * Get the unique identifier for this form
     *
     * @return string
     */
    protected function get_form_identifier() {
        $class = get_class($this);
        return preg_replace('/[^a-z0-9_]/i', '_', $class);
    }

    /**
     * Form definition. Abstract method - must be overridden by child classes.
     */
    abstract protected function definition();

    /**
     * After definition hook. Override to modify form after data has been set.
     */
    protected function definition_after_data() {
    }

    /**
     * Perform validation on the form
     *
     * @param array $data Array of submitted form data
     * @param array $files Array of uploaded files
     * @return array Array of errors, keyed by field name
     */
    public function validation($data, $files) {
        return [];
    }

    /**
     * Get submitted data if form has been submitted and is valid
     *
     * @return object|false Object of data, false if not submitted or invalid
     */
    public function get_data() {
        if (!$this->is_submitted()) {
            return false;
        }

        if (!$this->is_validated()) {
            return false;
        }

        $data = $this->_form->exportValues();
        return (object)$data;
    }

    /**
     * Check if form has been submitted
     *
     * @return bool
     */
    public function is_submitted() {
        return $this->_form->isSubmitted();
    }

    /**
     * Check if form has been validated
     *
     * @return bool
     */
    public function is_validated() {
        if ($this->_form->_validated === null) {
            // Run validation
            $data = $this->_form->exportValues();
            $files = []; // File uploads not yet implemented
            $errors = $this->validation($data, $files);
            $this->_form->_errors = $errors;
            $this->_form->_validated = empty($errors);
        }
        return $this->_form->_validated;
    }

    /**
     * Display the form
     */
    public function display() {
        $this->_form->display();
    }

    /**
     * Render form and return as string
     *
     * @return string
     */
    public function render() {
        ob_start();
        $this->display();
        $html = ob_get_clean();
        return $html;
    }

    /**
     * Add action buttons to the form (submit and cancel)
     *
     * @param bool $cancel Whether to show cancel button
     * @param string $submitlabel Label for submit button
     * @param string $cancelabel Label for cancel button
     */
    public function add_action_buttons($cancel = true, $submitlabel = null, $cancellabel = null) {
        if ($submitlabel === null) {
            $submitlabel = get_string('savechanges', 'core');
        }
        if ($cancellabel === null) {
            $cancellabel = get_string('cancel', 'core');
        }

        $buttonarray = [];
        $buttonarray[] = &$this->_form->createElement('submit', 'submitbutton', $submitlabel);
        if ($cancel) {
            $buttonarray[] = &$this->_form->createElement('cancel');
        }
        $this->_form->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $this->_form->closeHeaderBefore('buttonar');
    }

    /**
     * Set form data
     *
     * @param mixed $default_values
     */
    public function set_data($default_values) {
        if (is_object($default_values)) {
            $default_values = (array)$default_values;
        }
        $this->_form->setDefaults($default_values);
    }
}

/**
 * NexoSupport QuickForm-style class for building forms
 */
class NexoQuickForm {
    /** @var string Form name */
    protected $_formName;

    /** @var string Form method */
    protected $_method;

    /** @var string Form action */
    protected $_action;

    /** @var string Form target */
    protected $_target;

    /** @var array Form attributes */
    protected $_attributes;

    /** @var array Form elements */
    protected $_elements = [];

    /** @var array Element types */
    protected $_types = [];

    /** @var array Validation rules */
    protected $_rules = [];

    /** @var array Default values */
    protected $_defaults = [];

    /** @var array Element groups */
    protected $_groups = [];

    /** @var bool Has form been submitted */
    protected $_submitted = null;

    /** @var bool|null Has form been validated */
    public $_validated = null;

    /** @var array Validation errors */
    public $_errors = [];

    /** @var bool Disable short forms */
    protected $_disableShortforms = false;

    /**
     * Constructor
     *
     * @param string $formName Form name
     * @param string $method Form method
     * @param string $action Form action
     * @param string $target Form target
     * @param array $attributes Form attributes
     */
    public function __construct($formName, $method, $action, $target = '', $attributes = null) {
        $this->_formName = $formName;
        $this->_method = $method;
        $this->_action = $action;
        $this->_target = $target;
        $this->_attributes = $attributes ?: [];
    }

    /**
     * Add an element to the form
     *
     * @param string $type Element type
     * @param string $name Element name
     * @param string $label Element label
     * @param mixed $options Element options/attributes
     * @return bool
     */
    public function addElement($type, $name = null, $label = null, $options = null) {
        $element = $this->createElement($type, $name, $label, $options);
        $this->_elements[$name] = $element;
        return true;
    }

    /**
     * Create an element
     *
     * @param string $type Element type
     * @param string $name Element name
     * @param string $label Element label
     * @param mixed $options Element options/attributes
     * @return array
     */
    public function &createElement($type, $name = null, $label = null, $options = null) {
        $element = [
            'type' => $type,
            'name' => $name,
            'label' => $label,
            'options' => $options,
        ];
        return $element;
    }

    /**
     * Add a validation rule
     *
     * @param string $element Element name
     * @param string $message Error message
     * @param string $type Rule type
     * @param mixed $format Rule format
     * @param string $validation Validation location
     */
    public function addRule($element, $message, $type, $format = null, $validation = 'server') {
        $this->_rules[$element][] = [
            'message' => $message,
            'type' => $type,
            'format' => $format,
            'validation' => $validation,
        ];
    }

    /**
     * Set element type (for param cleaning)
     *
     * @param string $element Element name
     * @param string $type PARAM_* constant
     */
    public function setType($element, $type) {
        $this->_types[$element] = $type;
    }

    /**
     * Set default value for element
     *
     * @param string $element Element name
     * @param mixed $value Default value
     */
    public function setDefault($element, $value) {
        $this->_defaults[$element] = $value;
    }

    /**
     * Set default values for multiple elements
     *
     * @param array $defaults Array of default values
     */
    public function setDefaults($defaults) {
        if (is_array($defaults)) {
            foreach ($defaults as $key => $value) {
                $this->_defaults[$key] = $value;
            }
        }
    }

    /**
     * Add a group of elements
     *
     * @param array $elements Array of element references
     * @param string $name Group name
     * @param string $label Group label
     * @param mixed $separator Separator between elements
     * @param bool $appendName Append name to element names
     * @return bool
     */
    public function addGroup($elements, $name, $label = '', $separator = null, $appendName = true) {
        $this->_groups[$name] = [
            'elements' => $elements,
            'label' => $label,
            'separator' => $separator,
        ];
        return true;
    }

    /**
     * Close header before specified element
     *
     * @param string $elementName Element name
     */
    public function closeHeaderBefore($elementName) {
        // Marker for rendering - close any open fieldset before this element
        if (isset($this->_elements[$elementName])) {
            $this->_elements[$elementName]['closeHeaderBefore'] = true;
        }
    }

    /**
     * Disable short forms
     *
     * @param bool $disable
     */
    public function setDisableShortforms($disable = true) {
        $this->_disableShortforms = $disable;
    }

    /**
     * Check if form has been submitted
     *
     * @return bool
     */
    public function isSubmitted() {
        if ($this->_submitted === null) {
            $this->_submitted = ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET');
        }
        return $this->_submitted;
    }

    /**
     * Export form values
     *
     * @return array
     */
    public function exportValues() {
        $values = [];

        foreach ($this->_elements as $name => $element) {
            if ($element['type'] === 'hidden' || $element['type'] === 'static' || $element['type'] === 'header') {
                continue;
            }

            $paramType = $this->_types[$name] ?? PARAM_RAW;

            if ($this->_method === 'post') {
                $value = optional_param($name, null, $paramType);
            } else {
                $value = optional_param($name, null, $paramType);
            }

            if ($value !== null) {
                $values[$name] = $value;
            } else if (isset($this->_defaults[$name])) {
                $values[$name] = $this->_defaults[$name];
            }
        }

        return $values;
    }

    /**
     * Display the form
     */
    public function display() {
        global $OUTPUT;

        echo '<form method="' . s($this->_method) . '" action="' . s($this->_action) . '" class="nform">';
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';

        $currentFieldset = false;

        foreach ($this->_elements as $name => $element) {
            // Close header before if needed
            if (!empty($element['closeHeaderBefore']) && $currentFieldset) {
                echo '</fieldset>';
                $currentFieldset = false;
            }

            // Render element
            switch ($element['type']) {
                case 'header':
                    if ($currentFieldset) {
                        echo '</fieldset>';
                    }
                    echo '<fieldset class="clearfix">';
                    echo '<legend class="ftoggler">' . s($element['label']) . '</legend>';
                    $currentFieldset = true;
                    break;

                case 'hidden':
                    $value = $this->_defaults[$name] ?? ($element['options'] ?? '');
                    echo '<input type="hidden" name="' . s($name) . '" value="' . s($value) . '">';
                    break;

                case 'static':
                    echo '<div class="fitem">';
                    if ($element['label']) {
                        echo '<div class="fitemtitle"><label>' . $element['label'] . '</label></div>';
                    }
                    echo '<div class="felement">' . $element['options'] . '</div>';
                    echo '</div>';
                    break;

                case 'text':
                    $value = $this->_defaults[$name] ?? '';
                    $error = $this->_errors[$name] ?? '';
                    echo '<div class="fitem' . ($error ? ' error' : '') . '">';
                    echo '<div class="fitemtitle"><label for="id_' . s($name) . '">' . s($element['label']) . '</label></div>';
                    echo '<div class="felement">';
                    echo '<input type="text" name="' . s($name) . '" id="id_' . s($name) . '" value="' . s($value) . '" class="form-control">';
                    if ($error) {
                        echo '<span class="error">' . s($error) . '</span>';
                    }
                    echo '</div>';
                    echo '</div>';
                    break;

                case 'password':
                    $error = $this->_errors[$name] ?? '';
                    echo '<div class="fitem' . ($error ? ' error' : '') . '">';
                    echo '<div class="fitemtitle"><label for="id_' . s($name) . '">' . s($element['label']) . '</label></div>';
                    echo '<div class="felement">';
                    echo '<input type="password" name="' . s($name) . '" id="id_' . s($name) . '" class="form-control">';
                    if ($error) {
                        echo '<span class="error">' . s($error) . '</span>';
                    }
                    echo '</div>';
                    echo '</div>';
                    break;

                case 'checkbox':
                    $checked = !empty($this->_defaults[$name]);
                    echo '<div class="fitem">';
                    echo '<div class="fitemtitle"></div>';
                    echo '<div class="felement">';
                    echo '<input type="checkbox" name="' . s($name) . '" id="id_' . s($name) . '" value="1"' . ($checked ? ' checked' : '') . '>';
                    echo '<label for="id_' . s($name) . '">' . s($element['label']) . '</label>';
                    echo '</div>';
                    echo '</div>';
                    break;

                case 'submit':
                    echo '<button type="submit" class="btn btn-primary" name="' . s($name) . '">' . s($element['label']) . '</button>';
                    break;

                case 'cancel':
                    echo '<button type="submit" class="btn btn-secondary" name="cancel">' . get_string('cancel', 'core') . '</button>';
                    break;
            }
        }

        // Render groups
        foreach ($this->_groups as $groupName => $group) {
            echo '<div class="fitem">';
            echo '<div class="fitemtitle"></div>';
            echo '<div class="felement">';
            foreach ($group['elements'] as $element) {
                switch ($element['type']) {
                    case 'submit':
                        echo '<button type="submit" class="btn btn-primary" name="' . s($element['name']) . '">' . s($element['label']) . '</button>';
                        break;
                    case 'cancel':
                        echo '<button type="submit" class="btn btn-secondary" name="cancel">' . get_string('cancel', 'core') . '</button>';
                        break;
                }
                if (isset($group['separator'])) {
                    echo $group['separator'];
                }
            }
            echo '</div>';
            echo '</div>';
        }

        if ($currentFieldset) {
            echo '</fieldset>';
        }

        echo '</form>';
    }
}
