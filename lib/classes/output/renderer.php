<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

use core\navigation\primary_navigation_renderer;
use core\navigation\secondary_navigation_renderer;
use core\navigation\sidebar_navigation_renderer;

/**
 * Renderer
 *
 * Sistema de renderizado de salida HTML con branding ISER.
 * Implementa navegación tipo Moodle 4.x.
 *
 * @package core\output
 */
class renderer {

    /** @var page Page object */
    protected page $page;

    /**
     * Constructor
     *
     * @param page|null $page
     */
    public function __construct(?page $page = null) {
        $this->page = $page ?? new page();
    }

    /**
     * Render page header
     *
     * @return string HTML
     */
    public function header(): string {
        global $CFG, $USER;

        // Initialize navigation if not already done
        if (!$this->page->primary_nav) {
            $this->page->initialize_navigation();
        }

        $title = $this->page->title;
        $heading = $this->page->heading ?: $title;

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - NexoSupport</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- ISER Branding Styles -->
    <style>
        <?php echo $this->get_iser_styles(); ?>
    </style>
    <?php
    // Custom CSS
    foreach ($this->page->css_urls as $url) {
        echo '<link rel="stylesheet" href="' . htmlspecialchars($url) . '">' . "\n";
    }

    // Inline CSS
    if (!empty($this->page->inline_css)) {
        echo "<style>\n" . $this->page->inline_css . "\n</style>\n";
    }
    ?>
</head>
<body class="nexo-body">
    <?php
    // Render primary navigation
    $primary_renderer = new primary_navigation_renderer($this->page);
    echo $primary_renderer->render($this->page->primary_nav);
    ?>

    <!-- Main Layout -->
    <div class="nexo-main-layout">
        <?php if ($this->page->show_sidebar): ?>
        <!-- Sidebar -->
        <aside class="nexo-sidebar" id="nexoSidebar">
            <?php
            $sidebar_renderer = new sidebar_navigation_renderer($this->page);
            echo $sidebar_renderer->render($this->page->sidebar_nav);
            ?>
        </aside>
        <?php endif; ?>

        <!-- Content Area -->
        <main class="nexo-content<?php echo !$this->page->show_sidebar ? ' nexo-content-full' : ''; ?>">
            <?php
            // Render secondary navigation if enabled
            if ($this->page->show_secondary_nav && $this->page->secondary_nav) {
                $secondary_renderer = new secondary_navigation_renderer($this->page);
                echo $secondary_renderer->render($this->page->secondary_nav);
            }
            ?>

            <!-- Breadcrumbs -->
            <?php echo $this->render_breadcrumbs(); ?>

            <!-- Notifications -->
            <div class="nexo-notifications-container">
                <?php
                if (isset($_SESSION['notifications'])) {
                    foreach ($_SESSION['notifications'] as $notification) {
                        echo $this->notification($notification['message'], $notification['type']);
                    }
                    unset($_SESSION['notifications']);
                }
                ?>
            </div>

            <!-- Page Header -->
            <?php if ($heading): ?>
            <div class="nexo-page-header">
                <h1><?php echo htmlspecialchars($heading); ?></h1>
            </div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="nexo-main-content">
        <?php
        return ob_get_clean();
    }

    /**
     * Render page footer
     *
     * @return string HTML
     */
    public function footer(): string {
        global $CFG;

        ob_start();
        ?>
            </div> <!-- .nexo-main-content -->
        </main>
    </div> <!-- .nexo-main-layout -->

    <!-- Mobile Drawer Overlay -->
    <div class="nexo-mobile-overlay" id="nexoMobileOverlay"></div>

    <!-- Mobile Drawer -->
    <div class="nexo-mobile-drawer" id="nexoMobileDrawer">
        <?php
        if ($this->page->sidebar_nav) {
            $sidebar_renderer = new sidebar_navigation_renderer($this->page);
            echo $sidebar_renderer->render($this->page->sidebar_nav);
        }
        ?>
    </div>

    <!-- Navigation JavaScript -->
    <script src="/js/navigation/primary-navigation.js"></script>
    <script src="/js/navigation/sidebar-navigation.js"></script>

    <?php
    // Custom JS
    foreach ($this->page->js_urls as $url) {
        echo '<script src="' . htmlspecialchars($url) . '"></script>' . "\n";
    }

    // Inline JS
    if (!empty($this->page->inline_js)) {
        echo "<script>\n" . $this->page->inline_js . "\n</script>\n";
    }
    ?>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    /**
     * Render breadcrumbs
     *
     * @return string HTML
     */
    protected function render_breadcrumbs(): string {
        if (empty($this->page->breadcrumbs)) {
            return '';
        }

        $html = '<nav class="nexo-breadcrumbs" aria-label="Breadcrumb">';
        $html .= '<ol>';

        $count = count($this->page->breadcrumbs);
        foreach ($this->page->breadcrumbs as $i => $crumb) {
            $isLast = ($i === $count - 1);
            $html .= '<li class="nexo-breadcrumb-item' . ($isLast ? ' active' : '') . '">';

            if ($crumb['url'] && !$isLast) {
                $html .= '<a href="' . htmlspecialchars($crumb['url']) . '">' . htmlspecialchars($crumb['text']) . '</a>';
            } else {
                $html .= '<span' . ($isLast ? ' aria-current="page"' : '') . '>' . htmlspecialchars($crumb['text']) . '</span>';
            }

            if (!$isLast) {
                $html .= '<span class="nexo-breadcrumb-separator" aria-hidden="true">&rsaquo;</span>';
            }

            $html .= '</li>';
        }

        $html .= '</ol>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Render notification
     *
     * @param string $message
     * @param string $type success|error|warning|info
     * @return string HTML
     */
    public function notification(string $message, string $type = 'info'): string {
        $icons = [
            'success' => 'fa-check-circle',
            'error' => 'fa-exclamation-circle',
            'warning' => 'fa-exclamation-triangle',
            'info' => 'fa-info-circle',
        ];
        $icon = $icons[$type] ?? $icons['info'];

        return '<div class="nexo-notification nexo-notification-' . htmlspecialchars($type) . '" role="alert">' .
               '<i class="fas ' . $icon . '" aria-hidden="true"></i>' .
               '<span>' . htmlspecialchars($message) . '</span>' .
               '<button type="button" class="nexo-notification-close" aria-label="Cerrar">&times;</button>' .
               '</div>';
    }

    /**
     * Render box
     *
     * @param string $content
     * @param string $classes
     * @return string HTML
     */
    public function box(string $content, string $classes = ''): string {
        return '<div class="nexo-box ' . htmlspecialchars($classes) . '">' . $content . '</div>';
    }

    /**
     * Render button
     *
     * @param string $text
     * @param string $url
     * @param string $type primary|secondary|danger
     * @return string HTML
     */
    public function button(string $text, string $url, string $type = 'primary'): string {
        return '<a href="' . htmlspecialchars($url) . '" class="nexo-btn nexo-btn-' . htmlspecialchars($type) . '">' .
               htmlspecialchars($text) . '</a>';
    }

    /**
     * Get ISER branding styles
     *
     * @return string CSS
     */
    protected function get_iser_styles(): string {
        return <<<'CSS'
/* ============================================
   ISER BRANDING VARIABLES
   ============================================ */
:root {
    /* Primary Colors */
    --iser-verde: #1B9E88;
    --iser-amarillo: #FCBD05;
    --iser-rojo: #EB4335;
    --iser-blanco: #FFFFFF;

    /* Secondary Colors */
    --iser-naranja: #E27C32;
    --iser-lima: #CFDA4B;
    --iser-azul: #5894EF;
    --iser-magenta: #C82260;

    /* Neutral Colors */
    --iser-gris-claro: #CFCFCF;
    --iser-gris-medio: #9C9C9B;
    --iser-gris-oscuro: #646363;
    --iser-negro: #000000;

    /* Layout */
    --nav-primary-height: 60px;
    --nav-secondary-height: 48px;
    --sidebar-width: 280px;
    --sidebar-collapsed-width: 60px;

    /* Transitions */
    --transition-fast: 0.2s ease;
    --transition-normal: 0.3s ease;
}

/* ============================================
   BASE STYLES
   ============================================ */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body.nexo-body {
    font-family: Verdana, Arial, sans-serif;
    background: #f5f7f9;
    color: var(--iser-gris-oscuro);
    line-height: 1.6;
    min-height: 100vh;
}

/* ============================================
   PRIMARY NAVIGATION (HEADER)
   ============================================ */
.nexo-header-primary {
    position: sticky;
    top: 0;
    height: var(--nav-primary-height);
    background: linear-gradient(135deg, var(--iser-verde) 0%, var(--iser-azul) 100%);
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.nexo-header-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    height: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nexo-logo {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: var(--iser-blanco);
    font-weight: bold;
    font-size: 20px;
}

.nexo-logo img {
    height: 40px;
    margin-right: 10px;
}

.nexo-nav-primary-menu {
    display: flex;
    list-style: none;
    gap: 5px;
}

.nexo-nav-primary-item a {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    color: var(--iser-blanco);
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    border-radius: 4px;
    transition: background var(--transition-fast);
}

.nexo-nav-primary-item a i {
    margin-right: 8px;
}

.nexo-nav-primary-item a:hover {
    background: rgba(255, 255, 255, 0.15);
}

.nexo-nav-primary-item.active a {
    background: rgba(255, 255, 255, 0.1);
    border-bottom: 3px solid var(--iser-amarillo);
    border-radius: 4px 4px 0 0;
}

/* User Menu */
.nexo-user-menu {
    display: flex;
    align-items: center;
    gap: 15px;
}

.nexo-notification-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    border-radius: 50%;
    color: var(--iser-blanco);
    font-size: 18px;
    cursor: pointer;
    transition: background var(--transition-fast);
}

.nexo-notification-icon:hover {
    background: rgba(255, 255, 255, 0.2);
}

.nexo-user-dropdown {
    position: relative;
}

.nexo-user-trigger {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 10px;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    border-radius: 6px;
    color: var(--iser-blanco);
    cursor: pointer;
    transition: background var(--transition-fast);
}

.nexo-user-trigger:hover {
    background: rgba(255, 255, 255, 0.2);
}

.nexo-user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--iser-blanco);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--iser-verde);
    font-weight: bold;
    font-size: 14px;
}

.nexo-user-name {
    font-size: 14px;
    font-weight: 500;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.nexo-dropdown-arrow {
    font-size: 12px;
    transition: transform var(--transition-fast);
}

.nexo-user-dropdown.open .nexo-dropdown-arrow {
    transform: rotate(180deg);
}

.nexo-dropdown-menu {
    position: absolute;
    top: calc(100% + 5px);
    right: 0;
    min-width: 200px;
    background: var(--iser-blanco);
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all var(--transition-fast);
    z-index: 1020;
}

.nexo-dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.nexo-dropdown-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    color: var(--iser-gris-oscuro);
    text-decoration: none;
    font-size: 14px;
    transition: background var(--transition-fast);
}

.nexo-dropdown-item i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
    color: var(--iser-verde);
}

.nexo-dropdown-item:hover {
    background: rgba(207, 207, 207, 0.3);
}

.nexo-dropdown-item:first-child {
    border-radius: 6px 6px 0 0;
}

.nexo-dropdown-item:last-child {
    border-radius: 0 0 6px 6px;
}

.nexo-dropdown-divider {
    height: 1px;
    background: var(--iser-gris-claro);
    margin: 5px 0;
}

.nexo-dropdown-item-danger {
    color: var(--iser-rojo);
}

.nexo-dropdown-item-danger i {
    color: var(--iser-rojo);
}

.nexo-dropdown-item-danger:hover {
    background: rgba(235, 67, 53, 0.1);
}

/* Hamburger */
.nexo-hamburger {
    display: none;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 40px;
    height: 40px;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 5px;
}

.nexo-hamburger span {
    display: block;
    width: 24px;
    height: 2px;
    background: var(--iser-blanco);
    margin: 3px 0;
    transition: all var(--transition-fast);
    border-radius: 2px;
}

.nexo-hamburger.active span:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.nexo-hamburger.active span:nth-child(2) {
    opacity: 0;
}

.nexo-hamburger.active span:nth-child(3) {
    transform: rotate(-45deg) translate(5px, -5px);
}

/* ============================================
   MAIN LAYOUT
   ============================================ */
.nexo-main-layout {
    display: flex;
    min-height: calc(100vh - var(--nav-primary-height));
}

/* ============================================
   SIDEBAR
   ============================================ */
.nexo-sidebar {
    width: var(--sidebar-width);
    background: var(--iser-blanco);
    border-right: 1px solid var(--iser-gris-claro);
    padding: 20px 0;
    overflow-y: auto;
    transition: width var(--transition-normal);
    flex-shrink: 0;
}

.nexo-sidebar.collapsed {
    width: var(--sidebar-collapsed-width);
}

.nexo-sidebar-nav {
    list-style: none;
}

.nexo-sidebar-category {
    margin-bottom: 5px;
}

.nexo-sidebar-category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 20px;
    color: var(--iser-negro);
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
    cursor: pointer;
    transition: background var(--transition-fast);
}

.nexo-sidebar-category-header:hover {
    background: rgba(207, 207, 207, 0.2);
}

.nexo-sidebar-category-header i {
    color: var(--iser-verde);
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.nexo-sidebar-category-arrow {
    font-size: 10px;
    transition: transform var(--transition-fast);
}

.nexo-sidebar-category.expanded .nexo-sidebar-category-arrow {
    transform: rotate(180deg);
}

.nexo-sidebar-category-items {
    display: none;
    list-style: none;
    padding-left: 20px;
}

.nexo-sidebar-category.expanded .nexo-sidebar-category-items {
    display: block;
}

.nexo-sidebar-item {
    margin: 2px 0;
}

.nexo-sidebar-item a {
    display: flex;
    align-items: center;
    padding: 10px 20px;
    color: var(--iser-gris-oscuro);
    text-decoration: none;
    font-size: 13px;
    transition: all var(--transition-fast);
    border-left: 4px solid transparent;
}

.nexo-sidebar-item a i {
    color: var(--iser-verde);
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.nexo-sidebar-item a:hover {
    background: rgba(207, 207, 207, 0.2);
}

.nexo-sidebar-item.active a {
    background: rgba(27, 158, 136, 0.1);
    border-left-color: var(--iser-verde);
    color: var(--iser-verde);
    font-weight: 600;
}

.nexo-sidebar-separator {
    height: 1px;
    background: var(--iser-gris-claro);
    margin: 10px 20px;
}

/* ============================================
   CONTENT AREA
   ============================================ */
.nexo-content {
    flex: 1;
    padding: 0;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.nexo-content-full {
    max-width: 100%;
}

/* ============================================
   SECONDARY NAVIGATION (TABS)
   ============================================ */
.nexo-nav-secondary {
    height: var(--nav-secondary-height);
    background: var(--iser-blanco);
    border-bottom: 1px solid var(--iser-gris-claro);
    display: flex;
    align-items: center;
    padding: 0 20px;
    overflow-x: auto;
}

.nexo-nav-secondary-tabs {
    display: flex;
    list-style: none;
    gap: 5px;
    height: 100%;
}

.nexo-nav-secondary-tab {
    height: 100%;
}

.nexo-nav-secondary-tab a {
    display: flex;
    align-items: center;
    height: 100%;
    padding: 0 16px;
    color: var(--iser-gris-oscuro);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    border-bottom: 3px solid transparent;
    transition: all var(--transition-fast);
}

.nexo-nav-secondary-tab a i {
    margin-right: 8px;
    color: var(--iser-gris-medio);
}

.nexo-nav-secondary-tab a:hover {
    background: rgba(207, 207, 207, 0.3);
}

.nexo-nav-secondary-tab.active a {
    color: var(--iser-verde);
    border-bottom-color: var(--iser-verde);
}

.nexo-nav-secondary-tab.active a i {
    color: var(--iser-verde);
}

/* More menu */
.nexo-nav-secondary-more {
    position: relative;
    height: 100%;
}

.nexo-nav-secondary-more-btn {
    display: flex;
    align-items: center;
    height: 100%;
    padding: 0 16px;
    background: none;
    border: none;
    color: var(--iser-gris-oscuro);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.nexo-nav-secondary-more-menu {
    position: absolute;
    top: 100%;
    right: 0;
    min-width: 180px;
    background: var(--iser-blanco);
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: none;
    z-index: 1010;
}

.nexo-nav-secondary-more-menu.show {
    display: block;
}

.nexo-nav-secondary-more-menu a {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    color: var(--iser-gris-oscuro);
    text-decoration: none;
    font-size: 13px;
}

.nexo-nav-secondary-more-menu a:hover {
    background: rgba(207, 207, 207, 0.3);
}

/* ============================================
   BREADCRUMBS
   ============================================ */
.nexo-breadcrumbs {
    padding: 15px 20px;
    background: transparent;
}

.nexo-breadcrumbs ol {
    display: flex;
    flex-wrap: wrap;
    list-style: none;
    gap: 5px;
    align-items: center;
}

.nexo-breadcrumb-item {
    display: flex;
    align-items: center;
    font-size: 13px;
}

.nexo-breadcrumb-item a {
    color: var(--iser-verde);
    text-decoration: none;
}

.nexo-breadcrumb-item a:hover {
    text-decoration: underline;
}

.nexo-breadcrumb-item span {
    color: var(--iser-gris-medio);
}

.nexo-breadcrumb-item.active span {
    color: var(--iser-gris-oscuro);
}

.nexo-breadcrumb-separator {
    margin: 0 5px;
    color: var(--iser-gris-medio);
}

/* ============================================
   PAGE HEADER
   ============================================ */
.nexo-page-header {
    padding: 0 20px 20px;
}

.nexo-page-header h1 {
    color: var(--iser-negro);
    font-size: 24px;
    font-weight: bold;
    margin: 0;
}

/* ============================================
   MAIN CONTENT
   ============================================ */
.nexo-main-content {
    flex: 1;
    padding: 0 20px 20px;
}

/* ============================================
   NOTIFICATIONS
   ============================================ */
.nexo-notifications-container {
    padding: 0 20px;
}

.nexo-notification {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    border-radius: 6px;
    margin-bottom: 10px;
    position: relative;
}

.nexo-notification i {
    margin-right: 12px;
    font-size: 18px;
}

.nexo-notification-close {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    opacity: 0.7;
}

.nexo-notification-close:hover {
    opacity: 1;
}

.nexo-notification-success {
    background: rgba(27, 158, 136, 0.1);
    color: var(--iser-verde);
    border-left: 4px solid var(--iser-verde);
}

.nexo-notification-error {
    background: rgba(235, 67, 53, 0.1);
    color: var(--iser-rojo);
    border-left: 4px solid var(--iser-rojo);
}

.nexo-notification-warning {
    background: rgba(252, 189, 7, 0.15);
    color: #8a6d00;
    border-left: 4px solid var(--iser-amarillo);
}

.nexo-notification-info {
    background: rgba(88, 148, 239, 0.1);
    color: var(--iser-azul);
    border-left: 4px solid var(--iser-azul);
}

/* ============================================
   BOX & BUTTONS
   ============================================ */
.nexo-box {
    background: var(--iser-blanco);
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.nexo-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all var(--transition-fast);
}

.nexo-btn-primary {
    background: var(--iser-verde);
    color: var(--iser-blanco);
}

.nexo-btn-primary:hover {
    background: #178a77;
}

.nexo-btn-secondary {
    background: var(--iser-gris-claro);
    color: var(--iser-gris-oscuro);
}

.nexo-btn-secondary:hover {
    background: #b8b8b8;
}

.nexo-btn-danger {
    background: var(--iser-rojo);
    color: var(--iser-blanco);
}

.nexo-btn-danger:hover {
    background: #d32f2f;
}

/* ============================================
   MOBILE DRAWER
   ============================================ */
.nexo-mobile-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1050;
    opacity: 0;
    visibility: hidden;
    transition: all var(--transition-normal);
}

.nexo-mobile-overlay.show {
    opacity: 1;
    visibility: visible;
}

.nexo-mobile-drawer {
    position: fixed;
    top: 0;
    left: -280px;
    width: 280px;
    height: 100vh;
    background: var(--iser-blanco);
    z-index: 1100;
    overflow-y: auto;
    transition: left var(--transition-normal);
    padding-top: 20px;
}

.nexo-mobile-drawer.open {
    left: 0;
}

body.drawer-open {
    overflow: hidden;
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 1024px) {
    .nexo-sidebar {
        width: 240px;
    }

    .nexo-user-name {
        display: none;
    }
}

@media (max-width: 768px) {
    .nexo-hamburger {
        display: flex;
    }

    .nexo-nav-primary-menu {
        display: none;
    }

    .nexo-sidebar {
        display: none;
    }

    .nexo-user-dropdown .nexo-user-name,
    .nexo-user-dropdown .nexo-dropdown-arrow {
        display: none;
    }

    .nexo-notifications {
        display: none;
    }

    .nexo-nav-secondary {
        padding: 0 10px;
    }

    .nexo-nav-secondary-tab a {
        padding: 0 12px;
        font-size: 12px;
    }

    .nexo-breadcrumbs {
        padding: 10px;
    }

    .nexo-page-header {
        padding: 0 10px 15px;
    }

    .nexo-page-header h1 {
        font-size: 20px;
    }

    .nexo-main-content {
        padding: 0 10px 20px;
    }
}

/* ============================================
   FORMS (Basic Styling)
   ============================================ */
.nexo-form-group {
    margin-bottom: 20px;
}

.nexo-form-label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: var(--iser-gris-oscuro);
}

.nexo-form-input,
.nexo-form-select,
.nexo-form-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--iser-gris-claro);
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
    transition: border-color var(--transition-fast);
}

.nexo-form-input:focus,
.nexo-form-select:focus,
.nexo-form-textarea:focus {
    outline: none;
    border-color: var(--iser-verde);
    box-shadow: 0 0 0 3px rgba(27, 158, 136, 0.1);
}

/* ============================================
   TABLES
   ============================================ */
.nexo-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--iser-blanco);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.nexo-table th,
.nexo-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid var(--iser-gris-claro);
}

.nexo-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: var(--iser-negro);
    font-size: 13px;
    text-transform: uppercase;
}

.nexo-table tr:hover {
    background: rgba(27, 158, 136, 0.03);
}

.nexo-table tr:last-child td {
    border-bottom: none;
}
CSS;
    }
}
