<?php
namespace core\admin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Interface for admin tree elements that can have children
 *
 * Similar to Moodle's parentable_part_of_admin_tree interface.
 * Categories and the root node implement this interface.
 *
 * @package core\admin
 */
interface parentable_part_of_admin_tree extends part_of_admin_tree {

    /**
     * Add a child node to this element
     *
     * @param string $destinationname Name of parent to add to (or 'root' for direct child)
     * @param part_of_admin_tree $something The node to add
     * @param string|null $beforesibling Add before this sibling (or null for end)
     * @return bool True if added successfully
     */
    public function add(string $destinationname, part_of_admin_tree $something, ?string $beforesibling = null): bool;

    /**
     * Get all children of this node
     *
     * @return part_of_admin_tree[] Array of children
     */
    public function get_children(): array;

    /**
     * Check if this node has any children
     *
     * @return bool True if has children
     */
    public function has_children(): bool;
}
