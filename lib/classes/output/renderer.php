<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Renderer
 *
 * Sistema de renderizado de salida HTML.
 * Similar a Moodle's renderer.
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            line-height: 1.6;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }

        .navbar-nav {
            display: flex;
            gap: 20px;
            list-style: none;
        }

        .navbar-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .navbar-nav a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .content-wrapper {
            max-width: <?php echo $this->page->maxwidth; ?>px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .breadcrumb {
            display: flex;
            gap: 10px;
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .breadcrumb a {
            color: #667eea;
            text-decoration: none;
        }

        .breadcrumb-separator {
            color: #999;
        }

        .notifications {
            margin-bottom: 20px;
        }

        .notification {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .notification-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .notification-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .notification-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
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
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="/" class="navbar-brand">NexoSupport</a>
            <?php if (isset($USER->id) && $USER->id > 0): ?>
                <ul class="navbar-nav">
                    <li><a href="/">Inicio</a></li>
                    <?php if (has_capability('nexosupport/admin:manageusers')): ?>
                        <li><a href="/admin">Administración</a></li>
                    <?php endif; ?>
                    <li><a href="/user/profile">Mi Perfil</a></li>
                    <li><a href="/logout">Cerrar sesión</a></li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav">
                    <li><a href="/login">Iniciar sesión</a></li>
                </ul>
            <?php endif; ?>
        </div>
    </nav>

    <div class="content-wrapper">
        <?php if (!empty($this->page->breadcrumbs)): ?>
            <div class="breadcrumb">
                <?php
                $count = count($this->page->breadcrumbs);
                foreach ($this->page->breadcrumbs as $i => $crumb) {
                    if ($crumb['url']) {
                        echo '<a href="' . htmlspecialchars($crumb['url']) . '">' . htmlspecialchars($crumb['text']) . '</a>';
                    } else {
                        echo '<span>' . htmlspecialchars($crumb['text']) . '</span>';
                    }

                    if ($i < $count - 1) {
                        echo '<span class="breadcrumb-separator">/</span>';
                    }
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if ($heading): ?>
            <div class="page-header">
                <h1><?php echo htmlspecialchars($heading); ?></h1>
            </div>
        <?php endif; ?>

        <div class="notifications">
            <?php
            if (isset($_SESSION['notifications'])) {
                foreach ($_SESSION['notifications'] as $notification) {
                    echo $this->notification($notification['message'], $notification['type']);
                }
                unset($_SESSION['notifications']);
            }
            ?>
        </div>

        <!-- Main content -->
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
    </div> <!-- .content-wrapper -->

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
     * Render notification
     *
     * @param string $message
     * @param string $type success|error|warning|info
     * @return string HTML
     */
    public function notification(string $message, string $type = 'info'): string {
        $class = 'notification notification-' . $type;
        return '<div class="' . $class . '">' . htmlspecialchars($message) . '</div>';
    }

    /**
     * Render box
     *
     * @param string $content
     * @param string $classes
     * @return string HTML
     */
    public function box(string $content, string $classes = ''): string {
        $style = 'background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;';
        return '<div class="' . $classes . '" style="' . $style . '">' . $content . '</div>';
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
        $colors = [
            'primary' => 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);',
            'secondary' => 'background: #6c757d;',
            'danger' => 'background: #dc3545;'
        ];

        $style = ($colors[$type] ?? $colors['primary']) . ' color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block; border: none; cursor: pointer;';

        return '<a href="' . htmlspecialchars($url) . '" style="' . $style . '">' . htmlspecialchars($text) . '</a>';
    }
}
