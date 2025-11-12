<?php

declare(strict_types=1);

/**
 * Search Controller - Advanced Search Functionality (FASE 8)
 *
 * Controlador para búsquedas globales y filtradas
 * Maneja: búsqueda simple, búsqueda avanzada con filtros, paginación
 *
 * @package ISER\Controllers
 * @author ISER Development Team
 * @since FASE 8
 */

namespace ISER\Controllers;

use ISER\Controllers\Traits\NavigationTrait;
use ISER\Core\View\MustacheRenderer;
use ISER\Core\I18n\Translator;
use ISER\Core\Http\Response;
use ISER\Core\Database\Database;
use ISER\Core\Search\SearchManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class SearchController
{
    use NavigationTrait;

    private MustacheRenderer $renderer;
    private Translator $translator;
    private Database $db;
    private SearchManager $searchManager;

    /**
     * Constructor
     */
    public function __construct(Database $db)
    {
        $this->renderer = MustacheRenderer::getInstance();
        $this->translator = Translator::getInstance();
        $this->db = $db;
        $this->searchManager = new SearchManager($db);
    }

    /**
     * Renderizar con layout
     */
    private function renderWithLayout(string $view, array $data = [], string $layout = 'layouts/app'): ResponseInterface
    {
        $html = $this->renderer->render($view, $data, $layout);
        return Response::html($html);
    }

    /**
     * Show search form
     *
     * @param ServerRequestInterface $request PSR-7 Request
     * @return ResponseInterface PSR-7 Response
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
            return Response::redirect('/login');
        }

        // Get search statistics
        $stats = $this->searchManager->getSearchStatistics();

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('search.advanced_search'),
            'header_title' => $this->translator->translate('search.search'),
            'show_advanced_filters' => true,
            'stats' => $stats,
            'entity_types' => [
                ['value' => 'all', 'label' => $this->translator->translate('search.all_entities'), 'count' => $stats['total']],
                ['value' => 'users', 'label' => $this->translator->translate('search.users'), 'count' => $stats['users']],
                ['value' => 'tickets', 'label' => $this->translator->translate('search.tickets'), 'count' => $stats['tickets']],
                ['value' => 'knowledge_base', 'label' => $this->translator->translate('search.knowledge_base'), 'count' => $stats['knowledge_base']],
                ['value' => 'files', 'label' => $this->translator->translate('search.files'), 'count' => $stats['files']],
            ],
            'status_options' => [
                ['value' => '', 'label' => $this->translator->translate('search.all_statuses')],
                ['value' => 'active', 'label' => $this->translator->translate('common.active')],
                ['value' => 'inactive', 'label' => $this->translator->translate('common.inactive')],
                ['value' => 'suspended', 'label' => $this->translator->translate('common.suspended')],
            ],
        ];

        // Enrich with navigation
        $data = $this->enrichWithNavigation($data, '/search');

        return $this->renderWithLayout('search/index', $data);
    }

    /**
     * Display search results
     *
     * @param ServerRequestInterface $request PSR-7 Request
     * @return ResponseInterface PSR-7 Response
     */
    public function results(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
            return Response::redirect('/login');
        }

        // Get query parameters
        $queryParams = $request->getQueryParams();
        $query = (string)($queryParams['q'] ?? '');
        $page = (int)($queryParams['page'] ?? 1);

        // Build filters
        $filters = [];

        if (isset($queryParams['entity_type']) && $queryParams['entity_type'] !== 'all') {
            $filters['entity_type'] = $queryParams['entity_type'];
        }

        if (isset($queryParams['status']) && $queryParams['status'] !== '') {
            $filters['status'] = $queryParams['status'];
        }

        if (isset($queryParams['date_from']) && $queryParams['date_from'] !== '') {
            $filters['date_from'] = $queryParams['date_from'];
        }

        if (isset($queryParams['date_to']) && $queryParams['date_to'] !== '') {
            $filters['date_to'] = $queryParams['date_to'];
        }

        // Perform search
        $searchResults = $this->searchManager->search($query, $filters, $page);

        // Prepare results for rendering
        $formattedResults = [];
        foreach ($searchResults['results'] as $result) {
            $formattedResults[] = $this->formatResultForDisplay($result);
        }

        // Get statistics
        $stats = $this->searchManager->getSearchStatistics();

        $data = [
            'locale' => $this->translator->getLocale(),
            'page_title' => $this->translator->translate('search.search_results'),
            'header_title' => $this->translator->translate('search.search_results_for') . ' "' . htmlspecialchars($query) . '"',
            'query' => htmlspecialchars($query),
            'search_success' => $searchResults['success'],
            'search_message' => $searchResults['message'] ?? '',
            'total_results' => $searchResults['total'],
            'results' => $formattedResults,
            'current_page' => $searchResults['page'],
            'total_pages' => $searchResults['pages'],
            'per_page' => $searchResults['per_page'],
            'has_results' => count($formattedResults) > 0,
            'has_previous' => $searchResults['page'] > 1,
            'has_next' => $searchResults['page'] < $searchResults['pages'],
            'previous_page' => $searchResults['page'] - 1,
            'next_page' => $searchResults['page'] + 1,
            'page_links' => $this->generatePaginationLinks($searchResults['page'], $searchResults['pages'], $query, $filters),
            'grouped_results' => $this->formatGroupedResults($searchResults['grouped'] ?? []),
            'stats' => $stats,
            'filters_applied' => $this->hasFiltersApplied($queryParams),
            'active_filters' => $this->getActiveFiltersDisplay($queryParams),
        ];

        // Enrich with navigation
        $data = $this->enrichWithNavigation($data, '/search/results');

        return $this->renderWithLayout('search/results', $data);
    }

    /**
     * API endpoint for live search suggestions
     *
     * @param ServerRequestInterface $request PSR-7 Request
     * @return ResponseInterface PSR-7 Response
     */
    public function suggestions(ServerRequestInterface $request): ResponseInterface
    {
        // Check authentication
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
            return Response::json(['suggestions' => []]);
        }

        $queryParams = $request->getQueryParams();
        $query = (string)($queryParams['q'] ?? '');

        if (strlen(trim($query)) < 2) {
            return Response::json(['suggestions' => []]);
        }

        // Get suggestions (limit to 5 per entity type)
        $suggestions = [];

        try {
            $searchTerm = '%' . addslashes($query) . '%';

            // Users
            $userSql = "SELECT username, email FROM {$this->db->table('users')}
                       WHERE deleted_at IS NULL
                       AND (username LIKE :search OR email LIKE :search)
                       LIMIT 3";
            $users = $this->db->getConnection()->fetchAll($userSql, [':search' => $searchTerm]);
            foreach ($users as $user) {
                $suggestions[] = [
                    'text' => $user['username'],
                    'type' => 'user',
                    'value' => $user['email'],
                ];
            }

            // Tickets
            $ticketSql = "SELECT ticket_number, title FROM {$this->db->table('tickets')}
                         WHERE title LIKE :search OR ticket_number LIKE :search
                         LIMIT 3";
            $tickets = $this->db->getConnection()->fetchAll($ticketSql, [':search' => $searchTerm]);
            foreach ($tickets as $ticket) {
                $suggestions[] = [
                    'text' => '#' . $ticket['ticket_number'] . ': ' . substr($ticket['title'], 0, 40),
                    'type' => 'ticket',
                    'value' => $ticket['ticket_number'],
                ];
            }

            // Knowledge Base
            $kbSql = "SELECT id, title FROM {$this->db->table('knowledge_base')}
                     WHERE title LIKE :search
                     LIMIT 2";
            $articles = $this->db->getConnection()->fetchAll($kbSql, [':search' => $searchTerm]);
            foreach ($articles as $article) {
                $suggestions[] = [
                    'text' => $article['title'],
                    'type' => 'article',
                    'value' => $article['id'],
                ];
            }
        } catch (\Exception $e) {
            error_log('Search Suggestions Error: ' . $e->getMessage());
        }

        return Response::json(['suggestions' => $suggestions]);
    }

    /**
     * Format result for display
     *
     * @param array $result Search result
     * @return array Formatted result
     */
    private function formatResultForDisplay(array $result): array
    {
        $formatted = $result;
        $formatted['date_formatted'] = $this->formatDate($result['created_at'] ?? 0);
        $formatted['status_badge'] = $this->getStatusBadge($result['status'] ?? '');
        $formatted['icon_class'] = 'bi-' . str_replace('_', '-', $result['icon']);

        return $formatted;
    }

    /**
     * Format grouped results
     *
     * @param array $grouped Grouped results by type
     * @return array Formatted grouped results
     */
    private function formatGroupedResults(array $grouped): array
    {
        $formatted = [];

        $typeLabels = [
            'users' => $this->translator->translate('search.users'),
            'tickets' => $this->translator->translate('search.tickets'),
            'knowledge_base' => $this->translator->translate('search.knowledge_base'),
            'files' => $this->translator->translate('search.files'),
        ];

        foreach ($grouped as $type => $results) {
            $formatted[] = [
                'type' => $type,
                'label' => $typeLabels[$type] ?? ucfirst($type),
                'count' => count($results),
                'results' => array_map([$this, 'formatResultForDisplay'], $results),
            ];
        }

        return $formatted;
    }

    /**
     * Generate pagination links
     *
     * @param int $currentPage Current page
     * @param int $totalPages Total pages
     * @param string $query Search query
     * @param array $filters Active filters
     * @return array Pagination links
     */
    private function generatePaginationLinks(int $currentPage, int $totalPages, string $query, array $filters): array
    {
        $links = [];

        $baseUrl = '/search/results?q=' . urlencode($query);
        foreach ($filters as $key => $value) {
            if ($value !== '' && $value !== 'all') {
                $baseUrl .= '&' . urlencode($key) . '=' . urlencode($value);
            }
        }

        for ($i = 1; $i <= $totalPages; $i++) {
            $links[] = [
                'page' => $i,
                'url' => $baseUrl . '&page=' . $i,
                'active' => $i === $currentPage,
            ];
        }

        return $links;
    }

    /**
     * Check if any filters are applied
     *
     * @param array $queryParams Query parameters
     * @return bool
     */
    private function hasFiltersApplied(array $queryParams): bool
    {
        return (isset($queryParams['entity_type']) && $queryParams['entity_type'] !== 'all')
            || (isset($queryParams['status']) && $queryParams['status'] !== '')
            || (isset($queryParams['date_from']) && $queryParams['date_from'] !== '')
            || (isset($queryParams['date_to']) && $queryParams['date_to'] !== '');
    }

    /**
     * Get display of active filters
     *
     * @param array $queryParams Query parameters
     * @return array Active filters for display
     */
    private function getActiveFiltersDisplay(array $queryParams): array
    {
        $filters = [];

        if (isset($queryParams['entity_type']) && $queryParams['entity_type'] !== 'all') {
            $filters[] = [
                'label' => $this->translator->translate('search.entity_type'),
                'value' => ucfirst(str_replace('_', ' ', $queryParams['entity_type'])),
                'param' => 'entity_type',
            ];
        }

        if (isset($queryParams['status']) && $queryParams['status'] !== '') {
            $filters[] = [
                'label' => $this->translator->translate('common.status'),
                'value' => ucfirst($queryParams['status']),
                'param' => 'status',
            ];
        }

        if (isset($queryParams['date_from']) && $queryParams['date_from'] !== '') {
            $filters[] = [
                'label' => $this->translator->translate('search.date_from'),
                'value' => $queryParams['date_from'],
                'param' => 'date_from',
            ];
        }

        if (isset($queryParams['date_to']) && $queryParams['date_to'] !== '') {
            $filters[] = [
                'label' => $this->translator->translate('search.date_to'),
                'value' => $queryParams['date_to'],
                'param' => 'date_to',
            ];
        }

        return $filters;
    }

    /**
     * Format date for display
     *
     * @param int $timestamp Unix timestamp
     * @return string Formatted date
     */
    private function formatDate(int $timestamp): string
    {
        if ($timestamp === 0) {
            return '-';
        }

        $now = time();
        $diff = $now - $timestamp;

        if ($diff < 60) {
            return $this->translator->translate('search.just_now');
        } elseif ($diff < 3600) {
            $minutes = (int)($diff / 60);
            return $minutes . ' ' . $this->translator->translate('search.minutes_ago');
        } elseif ($diff < 86400) {
            $hours = (int)($diff / 3600);
            return $hours . ' ' . $this->translator->translate('search.hours_ago');
        } elseif ($diff < 604800) {
            $days = (int)($diff / 86400);
            return $days . ' ' . $this->translator->translate('search.days_ago');
        }

        return date('Y-m-d H:i', $timestamp);
    }

    /**
     * Get status badge HTML
     *
     * @param string $status Status value
     * @return string Badge HTML
     */
    private function getStatusBadge(string $status): string
    {
        $classes = match(strtolower($status)) {
            'active', 'open', 'success' => 'badge-success',
            'inactive', 'closed', 'completed' => 'badge-secondary',
            'suspended', 'urgent' => 'badge-danger',
            'pending' => 'badge-warning',
            default => 'badge-info',
        };

        return 'badge ' . $classes;
    }
}
