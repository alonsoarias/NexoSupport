<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Admin settings category
 *
 * Similar to Moodle's admin_category.
 * Contains subcategories, setting pages, and external pages.
 *
 * @package core\admin
 */
class admin_category implements parentable_part_of_admin_tree {

    /** @var string Unique category name */
    public string $name;

    /** @var string Visible category title */
    public string $visiblename;

    /** @var part_of_admin_tree[] All children (categories, pages, external pages) */
    protected array $children = [];

    /** @var bool Whether this category is hidden */
    public bool $hidden = false;

    /** @var string|array Required capability(ies) */
    public $req_capability = 'nexosupport/admin:manageconfig';

    /** @var int Context level */
    public int $context = CONTEXT_SYSTEM;

    /** @var string Optional CSS class for icon */
    public string $iconclass = '';

    /**
     * Constructor
     *
     * @param string $name Category name
     * @param string $visiblename Visible title
     * @param bool $hidden Whether hidden
     */
    public function __construct(string $name, string $visiblename, bool $hidden = false) {
        $this->name = $name;
        $this->visiblename = $visiblename;
        $this->hidden = $hidden;
    }

    /**
     * Locate a node by name
     *
     * @param string $name Node name to find
     * @return part_of_admin_tree|null Found node or null
     */
    public function locate(string $name): ?part_of_admin_tree {
        if ($this->name === $name) {
            return $this;
        }

        foreach ($this->children as $child) {
            $found = $child->locate($name);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Remove a node by name
     *
     * @param string $name Node name to remove
     * @return bool True if removed
     */
    public function prune(string $name): bool {
        foreach ($this->children as $key => $child) {
            if ($child->get_name() === $name) {
                unset($this->children[$key]);
                $this->children = array_values($this->children);
                return true;
            }

            if ($child instanceof parentable_part_of_admin_tree) {
                if ($child->prune($name)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Search for text in this category and children
     *
     * @param string $query Search query
     * @return array Matching nodes
     */
    public function search(string $query): array {
        $results = [];
        $query = strtolower($query);

        // Check this category
        if (strpos(strtolower($this->name), $query) !== false ||
            strpos(strtolower($this->visiblename), $query) !== false) {
            $results[] = $this;
        }

        // Search children
        foreach ($this->children as $child) {
            $results = array_merge($results, $child->search($query));
        }

        return $results;
    }

    /**
     * Check if user has access to this category
     *
     * @return bool True if accessible
     */
    public function check_access(): bool {
        // Check own capability
        if (is_array($this->req_capability)) {
            $hasAccess = false;
            foreach ($this->req_capability as $cap) {
                if (has_capability($cap, $this->context)) {
                    $hasAccess = true;
                    break;
                }
            }
            if (!$hasAccess) {
                return false;
            }
        } else {
            if (!has_capability($this->req_capability, $this->context)) {
                return false;
            }
        }

        // Check if at least one child is accessible
        foreach ($this->children as $child) {
            if ($child->check_access() && !$child->is_hidden()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if this category is hidden
     *
     * @return bool True if hidden
     */
    public function is_hidden(): bool {
        return $this->hidden;
    }

    /**
     * Categories don't show save button
     *
     * @return bool Always false
     */
    public function show_save(): bool {
        return false;
    }

    /**
     * Get category name
     *
     * @return string Category name
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
     * Add a child node
     *
     * @param string $destinationname Parent name (or this name for direct)
     * @param part_of_admin_tree $something Node to add
     * @param string|null $beforesibling Add before this sibling
     * @return bool True if added
     */
    public function add(string $destinationname, part_of_admin_tree $something, ?string $beforesibling = null): bool {
        // Add directly to this category
        if ($destinationname === $this->name) {
            if ($beforesibling !== null) {
                foreach ($this->children as $key => $child) {
                    if ($child->get_name() === $beforesibling) {
                        array_splice($this->children, $key, 0, [$something]);
                        return true;
                    }
                }
            }
            $this->children[] = $something;
            return true;
        }

        // Find destination in children
        foreach ($this->children as $child) {
            if ($child instanceof parentable_part_of_admin_tree) {
                if ($child->get_name() === $destinationname) {
                    return $child->add($destinationname, $something, $beforesibling);
                }
                // Search deeper
                $found = $child->locate($destinationname);
                if ($found instanceof parentable_part_of_admin_tree) {
                    return $found->add($destinationname, $something, $beforesibling);
                }
            }
        }

        return false;
    }

    /**
     * Get all children
     *
     * @return part_of_admin_tree[] Children
     */
    public function get_children(): array {
        return $this->children;
    }

    /**
     * Check if has children
     *
     * @return bool True if has children
     */
    public function has_children(): bool {
        return !empty($this->children);
    }

    /**
     * Add a subcategory (convenience method)
     *
     * @param admin_category $category Category to add
     * @return void
     */
    public function add_category(admin_category $category): void {
        $this->children[] = $category;
    }

    /**
     * Add a settings page (convenience method)
     *
     * @param admin_settingpage $page Page to add
     * @return void
     */
    public function add_page(admin_settingpage $page): void {
        $this->children[] = $page;
    }

    /**
     * Add an external page (convenience method)
     *
     * @param admin_externalpage $page External page to add
     * @return void
     */
    public function add_external(admin_externalpage $page): void {
        $this->children[] = $page;
    }

    /**
     * Get all subcategories (for backward compatibility)
     *
     * @return admin_category[] Categories
     */
    public function get_categories(): array {
        $categories = [];
        foreach ($this->children as $child) {
            if ($child instanceof admin_category) {
                $categories[] = $child;
            }
        }
        return $categories;
    }

    /**
     * Get all setting pages (for backward compatibility)
     *
     * @return admin_settingpage[] Pages
     */
    public function get_pages(): array {
        $pages = [];
        foreach ($this->children as $child) {
            if ($child instanceof admin_settingpage) {
                $pages[] = $child;
            }
        }
        return $pages;
    }

    /**
     * Find a page by name (recursive, for backward compatibility)
     *
     * @param string $name Page name
     * @return admin_settingpage|null Found page
     */
    public function find_page(string $name): ?admin_settingpage {
        $node = $this->locate($name);
        if ($node instanceof admin_settingpage) {
            return $node;
        }
        return null;
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
     * Get template data for navigation
     *
     * @return array Template data
     */
    public function get_template_data(): array {
        $children_data = [];
        foreach ($this->children as $child) {
            if ($child->check_access() && !$child->is_hidden()) {
                if (method_exists($child, 'get_template_data')) {
                    $children_data[] = $child->get_template_data();
                }
            }
        }

        $categories = array_values(array_filter($children_data, fn($c) => isset($c['iscategory']) && $c['iscategory']));
        $pages = array_values(array_filter($children_data, fn($c) => !isset($c['iscategory']) || !$c['iscategory']));

        return [
            'name' => $this->name,
            'visiblename' => $this->visiblename,
            'iconclass' => $this->iconclass,
            'children' => $children_data,
            'haschildren' => !empty($children_data),
            'iscategory' => true,
            // Legacy support
            'categories' => $categories,
            'pages' => $pages,
            'hascategories' => count($categories) > 0,
            'haspages' => count($pages) > 0,
        ];
    }
}
