<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Interface for any element in the admin settings tree
 *
 * Similar to Moodle's part_of_admin_tree interface.
 * All elements in the admin tree must implement this interface.
 *
 * @package core\admin
 */
interface part_of_admin_tree {

    /**
     * Locate a named part of the tree
     *
     * @param string $name Name of the node to find
     * @return part_of_admin_tree|null Found node or null
     */
    public function locate(string $name): ?part_of_admin_tree;

    /**
     * Remove a named part from the tree
     *
     * @param string $name Name of the node to remove
     * @return bool True if removed successfully
     */
    public function prune(string $name): bool;

    /**
     * Search for text in the tree
     *
     * @param string $query Search query
     * @return array Matching nodes
     */
    public function search(string $query): array;

    /**
     * Check if user has access to this part
     *
     * @return bool True if accessible
     */
    public function check_access(): bool;

    /**
     * Check if this part is hidden
     *
     * @return bool True if hidden
     */
    public function is_hidden(): bool;

    /**
     * Check if save button should be shown
     *
     * @return bool True if should show save button
     */
    public function show_save(): bool;

    /**
     * Get the name of this node
     *
     * @return string Node name
     */
    public function get_name(): string;

    /**
     * Get the visible name of this node
     *
     * @return string Visible name
     */
    public function get_visiblename(): string;
}
