<?php
/**
 * Template Renderer - ISER Theme
 * Clase para renderizar plantillas con el diseño corporativo ISER
 */

class TemplateRenderer
{
    private static $templatesPath;

    /**
     * Inicializar el renderer
     */
    public static function init($basePath = null)
    {
        if ($basePath === null) {
            $basePath = __DIR__;
        }
        self::$templatesPath = $basePath;
    }

    /**
     * Renderizar una página completa con el layout base
     *
     * @param string $content Contenido HTML de la página
     * @param array $data Datos para la plantilla
     * @return string HTML renderizado
     */
    public static function render($content, $data = [])
    {
        if (self::$templatesPath === null) {
            self::init();
        }

        // Extraer variables para la plantilla
        extract($data);

        // Capturar el contenido
        ob_start();
        if (is_callable($content)) {
            $content($data);
            $content = ob_get_clean();
        } elseif (file_exists($content)) {
            include $content;
            $content = ob_get_clean();
        }

        // Renderizar el layout
        ob_start();
        include self::$templatesPath . '/layouts/base.php';
        return ob_get_clean();
    }

    /**
     * Renderizar solo el contenido de una página
     *
     * @param string $template Ruta de la plantilla
     * @param array $data Datos para la plantilla
     * @return string HTML renderizado
     */
    public static function renderPartial($template, $data = [])
    {
        if (self::$templatesPath === null) {
            self::init();
        }

        extract($data);

        ob_start();
        include self::$templatesPath . '/' . $template;
        return ob_get_clean();
    }

    /**
     * Renderizar un componente
     *
     * @param string $component Nombre del componente
     * @param array $data Datos para el componente
     * @return string HTML renderizado
     */
    public static function component($component, $data = [])
    {
        return self::renderPartial('components/' . $component . '.php', $data);
    }

    /**
     * Crear una card ISER
     *
     * @param array $data Datos de la card
     * @return string HTML de la card
     */
    public static function card($data)
    {
        $title = $data['title'] ?? '';
        $percentage = $data['percentage'] ?? null;
        $description = $data['description'] ?? '';
        $metrics = $data['metrics'] ?? [];
        $progressValue = $data['progressValue'] ?? null;

        ob_start();
        ?>
        <div class="card fade-in">
            <div class="card-header">
                <div class="card-title"><?= htmlspecialchars($title) ?></div>
                <?php if ($percentage !== null): ?>
                    <div class="card-percentage"><?= htmlspecialchars($percentage) ?>%</div>
                <?php endif; ?>
            </div>

            <?php if ($description): ?>
                <div class="card-description">
                    <strong><?= htmlspecialchars($description) ?></strong>
                </div>
            <?php endif; ?>

            <?php if (!empty($metrics)): ?>
                <div class="metrics-grid">
                    <?php foreach ($metrics as $metric): ?>
                        <div class="metric">
                            <span class="metric-label"><?= htmlspecialchars($metric['label']) ?>:</span>
                            <span class="metric-value"><?= htmlspecialchars($metric['value']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($progressValue !== null): ?>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" data-width="<?= htmlspecialchars($progressValue) ?>">
                            <?= htmlspecialchars($progressValue) ?>%
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Crear una stat card
     *
     * @param string $label Etiqueta
     * @param string $value Valor
     * @return string HTML de la stat card
     */
    public static function statCard($label, $value)
    {
        ob_start();
        ?>
        <div class="stat-card">
            <div class="label"><?= htmlspecialchars($label) ?></div>
            <div class="value"><?= htmlspecialchars($value) ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Crear un contenedor de gráfico
     *
     * @param string $id ID del canvas
     * @param string $title Título del gráfico
     * @return string HTML del contenedor
     */
    public static function chartContainer($id, $title)
    {
        ob_start();
        ?>
        <div class="chart-container">
            <h3 class="chart-title"><?= htmlspecialchars($title) ?></h3>
            <canvas id="<?= htmlspecialchars($id) ?>"></canvas>
        </div>
        <?php
        return ob_get_clean();
    }
}
