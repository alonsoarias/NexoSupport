<?php
namespace core\rbac;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Context System
 *
 * Sistema de contextos para RBAC.
 * Los contextos definen los niveles donde se aplican los permisos.
 *
 * @package core\rbac
 */

// Context levels (check if already defined to avoid duplicate definition warnings)
if (!defined('CONTEXT_SYSTEM')) {
    define('CONTEXT_SYSTEM', 10);
}
if (!defined('CONTEXT_USER')) {
    define('CONTEXT_USER', 30);
}
if (!defined('CONTEXT_COURSE')) {
    define('CONTEXT_COURSE', 50);
}

class context {

    /** @var int Context ID */
    public int $id;

    /** @var int Context level */
    public int $contextlevel;

    /** @var int Instance ID */
    public int $instanceid;

    /** @var string Path */
    public string $path;

    /** @var int Depth */
    public int $depth;

    /**
     * Constructor
     *
     * @param object $record
     */
    protected function __construct(object $record) {
        $this->id = (int)$record->id;
        $this->contextlevel = (int)$record->contextlevel;
        $this->instanceid = (int)$record->instanceid;
        $this->path = $record->path ?? '';
        $this->depth = (int)$record->depth;
    }

    /**
     * Get system context
     *
     * @return context
     */
    public static function system(): context {
        return self::instance(CONTEXT_SYSTEM, 0);
    }

    /**
     * Get user context
     *
     * @param int $userid User ID
     * @return context
     */
    public static function user(int $userid): context {
        return self::instance(CONTEXT_USER, $userid);
    }

    /**
     * Get context instance
     *
     * @param int $level
     * @param int $instanceid
     * @param bool $create Create if not exists
     * @return context
     */
    public static function instance(int $level, int $instanceid, bool $create = true): context {
        global $DB;

        // Try to get existing context
        $record = $DB->get_record('contexts', [
            'contextlevel' => $level,
            'instanceid' => $instanceid
        ]);

        if ($record) {
            return new self($record);
        }

        if (!$create) {
            throw new \coding_exception("Context not found: level=$level, instance=$instanceid");
        }

        // Create new context
        return self::create($level, $instanceid);
    }

    /**
     * Create new context
     *
     * @param int $level
     * @param int $instanceid
     * @return context
     */
    protected static function create(int $level, int $instanceid): context {
        global $DB;

        // Calculate path and depth
        $depth = 1;
        $path = '';

        if ($level == CONTEXT_SYSTEM) {
            $depth = 1;
        } else if ($level == CONTEXT_USER) {
            $depth = 2;
            $syscontext = self::system();
            $path = $syscontext->path;
        }

        // Insert context
        $record = new \stdClass();
        $record->contextlevel = $level;
        $record->instanceid = $instanceid;
        $record->path = $path;
        $record->depth = $depth;

        $id = $DB->insert_record('contexts', $record);
        $record->id = $id;

        // Update path if needed
        if (empty($path)) {
            $record->path = '/' . $id;
            $DB->update_record('contexts', $record);
        } else {
            $record->path = $path . '/' . $id;
            $DB->update_record('contexts', $record);
        }

        return new self($record);
    }

    /**
     * Get parent contexts
     *
     * @return array Array of context instances
     */
    public function get_parent_contexts(): array {
        global $DB;

        if ($this->depth <= 1) {
            return [];
        }

        $path_parts = explode('/', trim($this->path, '/'));
        array_pop(); // Remove self

        if (empty($path_parts)) {
            return [];
        }

        $ids = array_map('intval', $path_parts);

        list($insql, $params) = $DB->get_in_or_equal($ids);
        $records = $DB->get_records_select('contexts', "id $insql", $params, 'depth ASC');

        $contexts = [];
        foreach ($records as $record) {
            $contexts[] = new self($record);
        }

        return $contexts;
    }

    /**
     * Get context path as array
     *
     * @return array
     */
    public function get_path_array(): array {
        if (empty($this->path)) {
            return [];
        }

        $parts = explode('/', trim($this->path, '/'));
        return array_map('intval', $parts);
    }

    /**
     * Check if this context is a parent of another
     *
     * @param context $other
     * @return bool
     */
    public function is_parent_of(context $other): bool {
        if ($this->depth >= $other->depth) {
            return false;
        }

        return str_starts_with($other->path, $this->path . '/');
    }
}

/**
 * System context class (Moodle-compatible)
 */
class context_system extends context {
    /**
     * Get system context instance
     *
     * @return context_system
     */
    public static function instance(): context_system {
        $ctx = context::instance(CONTEXT_SYSTEM, 0);
        // Return as context_system instance
        return new self((object)[
            'id' => $ctx->id,
            'contextlevel' => $ctx->contextlevel,
            'instanceid' => $ctx->instanceid,
            'path' => $ctx->path,
            'depth' => $ctx->depth,
        ]);
    }
}

/**
 * Course context class (Moodle-compatible)
 */
class context_course extends context {
    /**
     * Get course context instance
     *
     * @param int $courseid Course ID
     * @return context_course
     */
    public static function instance(int $courseid): context_course {
        $ctx = context::instance(CONTEXT_COURSE, $courseid);
        return new self((object)[
            'id' => $ctx->id,
            'contextlevel' => $ctx->contextlevel,
            'instanceid' => $ctx->instanceid,
            'path' => $ctx->path,
            'depth' => $ctx->depth,
        ]);
    }
}

/**
 * User context class (Moodle-compatible)
 */
class context_user extends context {
    /**
     * Get user context instance
     *
     * @param int $userid User ID
     * @return context_user
     */
    public static function instance(int $userid): context_user {
        $ctx = context::instance(CONTEXT_USER, $userid);
        return new self((object)[
            'id' => $ctx->id,
            'contextlevel' => $ctx->contextlevel,
            'instanceid' => $ctx->instanceid,
            'path' => $ctx->path,
            'depth' => $ctx->depth,
        ]);
    }
}
