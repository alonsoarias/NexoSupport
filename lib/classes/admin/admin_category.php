<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Admin settings category
 *
 * Similar to Moodle's admin_category.
 * Contains subcategories and setting pages.
 *
 * @package core\admin
 */
class admin_category {

    /** @var string Unique category name */
    public string $name;

    /** @var string Visible category title */
    public string $visiblename;

    /** @var admin_category[] Child categories */
    public array $categories = [];

    /** @var admin_settingpage[] Setting pages */
    public array $pages = [];

    /** @var admin_category|null Parent category */
    public ?admin_category $parent = null;

    /**
     * Constructor
     *
     * @param string $name Category name
     * @param string $visiblename Visible title
     */
    public function __construct(string $name, string $visiblename) {
        $this->name = $name;
        $this->visiblename = $visiblename;
    }

    /**
     * Add a subcategory
     *
     * @param admin_category $category Category to add
     * @return void
     */
    public function add_category(admin_category $category): void {
        $category->parent = $this;
        $this->categories[] = $category;
    }

    /**
     * Add a settings page
     *
     * @param admin_settingpage $page Page to add
     * @return void
     */
    public function add_page(admin_settingpage $page): void {
        $this->pages[] = $page;
    }

    /**
     * Get all child categories
     *
     * @return admin_category[] Categories
     */
    public function get_categories(): array {
        return $this->categories;
    }

    /**
     * Get all setting pages
     *
     * @return admin_settingpage[] Pages
     */
    public function get_pages(): array {
        return $this->pages;
    }

    /**
     * Find a page by name (recursive)
     *
     * @param string $name Page name
     * @return admin_settingpage|null Found page
     */
    public function find_page(string $name): ?admin_settingpage {
        // Search in direct pages
        foreach ($this->pages as $page) {
            if ($page->name === $name) {
                return $page;
            }
        }

        // Search in subcategories
        foreach ($this->categories as $category) {
            $page = $category->find_page($name);
            if ($page) {
                return $page;
            }
        }

        return null;
    }

    /**
     * Get template data for navigation
     *
     * @return array Template data
     */
    public function get_template_data(): array {
        $categories_data = [];
        foreach ($this->categories as $cat) {
            $categories_data[] = $cat->get_template_data();
        }

        $pages_data = [];
        foreach ($this->pages as $page) {
            $pages_data[] = [
                'name' => $page->name,
                'visiblename' => $page->visiblename,
            ];
        }

        return [
            'name' => $this->name,
            'visiblename' => $this->visiblename,
            'categories' => $categories_data,
            'pages' => $pages_data,
            'hascategories' => !empty($categories_data),
            'haspages' => !empty($pages_data),
        ];
    }
}
