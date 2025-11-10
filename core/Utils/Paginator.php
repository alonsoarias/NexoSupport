<?php
/**
 * ISER - Paginator
 * @package ISER\Core\Utils
 */

namespace ISER\Core\Utils;

class Paginator
{
    private int $totalItems;
    private int $itemsPerPage;
    private int $currentPage;
    private int $totalPages;
    private int $offset;
    private string $baseUrl;
    private array $queryParams;

    /**
     * Constructor
     *
     * @param int $totalItems Total number of items
     * @param int $itemsPerPage Number of items per page
     * @param int $currentPage Current page number (1-indexed)
     * @param string $baseUrl Base URL for pagination links
     * @param array $queryParams Additional query parameters to preserve
     */
    public function __construct(
        int $totalItems,
        int $itemsPerPage = 20,
        int $currentPage = 1,
        string $baseUrl = '',
        array $queryParams = []
    ) {
        $this->totalItems = max(0, $totalItems);
        $this->itemsPerPage = max(1, $itemsPerPage);
        $this->currentPage = max(1, $currentPage);
        $this->baseUrl = $baseUrl;
        $this->queryParams = $queryParams;

        // Calculate total pages
        $this->totalPages = (int)ceil($this->totalItems / $this->itemsPerPage);

        // Ensure current page is within bounds
        if ($this->currentPage > $this->totalPages && $this->totalPages > 0) {
            $this->currentPage = $this->totalPages;
        }

        // Calculate offset for database queries
        $this->offset = ($this->currentPage - 1) * $this->itemsPerPage;
    }

    /**
     * Get current page number
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Get total number of pages
     */
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * Get total number of items
     */
    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * Get items per page
     */
    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    /**
     * Get offset for database queries
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Get limit for database queries (alias for getItemsPerPage)
     */
    public function getLimit(): int
    {
        return $this->itemsPerPage;
    }

    /**
     * Check if there is a previous page
     */
    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * Check if there is a next page
     */
    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * Get previous page number
     */
    public function getPreviousPage(): ?int
    {
        return $this->hasPrevious() ? $this->currentPage - 1 : null;
    }

    /**
     * Get next page number
     */
    public function getNextPage(): ?int
    {
        return $this->hasNext() ? $this->currentPage + 1 : null;
    }

    /**
     * Get URL for a specific page
     */
    public function getPageUrl(int $page): string
    {
        $params = array_merge($this->queryParams, ['page' => $page]);
        $queryString = http_build_query($params);

        return $this->baseUrl . ($queryString ? '?' . $queryString : '');
    }

    /**
     * Get URL for previous page
     */
    public function getPreviousUrl(): ?string
    {
        $prevPage = $this->getPreviousPage();
        return $prevPage ? $this->getPageUrl($prevPage) : null;
    }

    /**
     * Get URL for next page
     */
    public function getNextUrl(): ?string
    {
        $nextPage = $this->getNextPage();
        return $nextPage ? $this->getPageUrl($nextPage) : null;
    }

    /**
     * Get array of page numbers to display
     *
     * @param int $adjacentPages Number of pages to show on each side of current page
     * @return array Array of page numbers
     */
    public function getPageNumbers(int $adjacentPages = 2): array
    {
        if ($this->totalPages <= 1) {
            return [1];
        }

        $pages = [];

        // Always show first page
        $pages[] = 1;

        // Calculate start and end of middle range
        $start = max(2, $this->currentPage - $adjacentPages);
        $end = min($this->totalPages - 1, $this->currentPage + $adjacentPages);

        // Add ellipsis after first page if needed
        if ($start > 2) {
            $pages[] = '...';
        }

        // Add middle range
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        // Add ellipsis before last page if needed
        if ($end < $this->totalPages - 1) {
            $pages[] = '...';
        }

        // Always show last page if there is more than one page
        if ($this->totalPages > 1) {
            $pages[] = $this->totalPages;
        }

        return $pages;
    }

    /**
     * Get range of items being displayed (e.g., "1-20 of 100")
     */
    public function getItemRange(): array
    {
        if ($this->totalItems === 0) {
            return ['from' => 0, 'to' => 0];
        }

        $from = $this->offset + 1;
        $to = min($this->offset + $this->itemsPerPage, $this->totalItems);

        return ['from' => $from, 'to' => $to];
    }

    /**
     * Get formatted range string (e.g., "Showing 1-20 of 100")
     */
    public function getRangeText(string $format = 'Mostrando %d-%d de %d'): string
    {
        $range = $this->getItemRange();
        return sprintf($format, $range['from'], $range['to'], $this->totalItems);
    }

    /**
     * Render pagination HTML (Bootstrap 5 compatible)
     */
    public function render(array $options = []): string
    {
        if ($this->totalPages <= 1) {
            return '';
        }

        $options = array_merge([
            'show_prev_next' => true,
            'show_first_last' => true,
            'prev_text' => '&laquo; Anterior',
            'next_text' => 'Siguiente &raquo;',
            'first_text' => 'Primera',
            'last_text' => 'Última',
            'adjacent_pages' => 2,
            'class' => 'pagination justify-content-center',
        ], $options);

        $html = '<nav aria-label="Page navigation">';
        $html .= '<ul class="' . htmlspecialchars($options['class']) . '">';

        // First page link
        if ($options['show_first_last'] && $this->currentPage > 1) {
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $this->getPageUrl(1) . '">';
            $html .= $options['first_text'];
            $html .= '</a></li>';
        }

        // Previous page link
        if ($options['show_prev_next']) {
            $disabled = !$this->hasPrevious() ? ' disabled' : '';
            $html .= '<li class="page-item' . $disabled . '">';

            if ($this->hasPrevious()) {
                $html .= '<a class="page-link" href="' . $this->getPreviousUrl() . '">';
                $html .= $options['prev_text'];
                $html .= '</a>';
            } else {
                $html .= '<span class="page-link">' . $options['prev_text'] . '</span>';
            }

            $html .= '</li>';
        }

        // Page number links
        foreach ($this->getPageNumbers($options['adjacent_pages']) as $page) {
            if ($page === '...') {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            } else {
                $active = $page === $this->currentPage ? ' active' : '';
                $html .= '<li class="page-item' . $active . '">';
                $html .= '<a class="page-link" href="' . $this->getPageUrl($page) . '">';
                $html .= $page;
                $html .= '</a></li>';
            }
        }

        // Next page link
        if ($options['show_prev_next']) {
            $disabled = !$this->hasNext() ? ' disabled' : '';
            $html .= '<li class="page-item' . $disabled . '">';

            if ($this->hasNext()) {
                $html .= '<a class="page-link" href="' . $this->getNextUrl() . '">';
                $html .= $options['next_text'];
                $html .= '</a>';
            } else {
                $html .= '<span class="page-link">' . $options['next_text'] . '</span>';
            }

            $html .= '</li>';
        }

        // Last page link
        if ($options['show_first_last'] && $this->currentPage < $this->totalPages) {
            $html .= '<li class="page-item">';
            $html .= '<a class="page-link" href="' . $this->getPageUrl($this->totalPages) . '">';
            $html .= $options['last_text'];
            $html .= '</a></li>';
        }

        $html .= '</ul></nav>';

        return $html;
    }

    /**
     * Get pagination data as array (useful for API responses)
     */
    public function toArray(): array
    {
        $range = $this->getItemRange();

        return [
            'current_page' => $this->currentPage,
            'total_pages' => $this->totalPages,
            'total_items' => $this->totalItems,
            'items_per_page' => $this->itemsPerPage,
            'from' => $range['from'],
            'to' => $range['to'],
            'has_previous' => $this->hasPrevious(),
            'has_next' => $this->hasNext(),
            'previous_page' => $this->getPreviousPage(),
            'next_page' => $this->getNextPage(),
        ];
    }
}
