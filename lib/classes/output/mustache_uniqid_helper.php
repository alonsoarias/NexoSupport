<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Mustache Uniqid Helper
 *
 * Generates unique IDs for DOM elements.
 * Usage: {{uniqid}}
 *
 * Each call generates the same ID within a single template render,
 * but different IDs across multiple renders.
 *
 * Example:
 *   <div id="container-{{uniqid}}">
 *       <input id="input-{{uniqid}}" type="text">
 *       <button id="btn-{{uniqid}}">Submit</button>
 *   </div>
 *
 *   {{#js}}
 *   document.getElementById('btn-{{uniqid}}').addEventListener('click', function() {
 *       var value = document.getElementById('input-{{uniqid}}').value;
 *       console.log(value);
 *   });
 *   {{/js}}
 *
 * @package    core\output
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class mustache_uniqid_helper {

    /** @var string|null Current unique ID for this render */
    private ?string $uniqid = null;

    /** @var int Counter for generating unique IDs */
    private static int $counter = 0;

    /**
     * Generate unique ID
     *
     * @return string Unique ID
     */
    public function __invoke(): string {
        if ($this->uniqid === null) {
            $this->uniqid = $this->generate_id();
        }
        return $this->uniqid;
    }

    /**
     * Generate a new unique ID
     *
     * @return string Unique ID
     */
    protected function generate_id(): string {
        self::$counter++;
        return 'uniq' . self::$counter . '_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
    }

    /**
     * Reset the ID (for a new template render)
     *
     * @return void
     */
    public function reset(): void {
        $this->uniqid = null;
    }

    /**
     * Create a new instance (for a new template render)
     *
     * @return self New instance
     */
    public static function create(): self {
        return new self();
    }
}
