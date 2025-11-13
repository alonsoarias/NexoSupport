<?php
/**
 * Renderizador específico para el tema ISER
 * @package theme_iser
 * @author ISER Desarrollo
 */

namespace ISER\Theme\Iser;

use ISER\Core\View\MustacheRenderer;

class ThemeRenderer
{
    private MustacheRenderer $mustache;
    private array $templatePaths;

    public function __construct()
    {
        $this->templatePaths = [
            __DIR__ . '/../templates',
            __DIR__ . '/../templates/partials',
            __DIR__ . '/../templates/components'
        ];

        $this->mustache = new MustacheRenderer($this->templatePaths);
    }

    /**
     * Renderizar un template
     */
    public function render(string $template, array $data = []): string
    {
        return $this->mustache->render($template, $data);
    }

    /**
     * Agregar una ruta de templates
     */
    public function addTemplatePath(string $path): void
    {
        if (!in_array($path, $this->templatePaths)) {
            $this->templatePaths[] = $path;
            $this->mustache->addTemplatePath($path);
        }
    }

    /**
     * Obtener templates disponibles
     */
    public function getAvailableTemplates(): array
    {
        return $this->mustache->getAvailableTemplates();
    }

    // ==================== HELPERS PARA BOOTSTRAP 5 ====================

    /**
     * Generar un alert de Bootstrap
     */
    public function bsAlert(string $type, string $message, bool $dismissible = true): string
    {
        $classes = "alert alert-{$type}";
        if ($dismissible) {
            $classes .= ' alert-dismissible fade show';
        }

        $html = "<div class=\"{$classes}\" role=\"alert\">";
        $html .= htmlspecialchars($message);

        if ($dismissible) {
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generar un badge de Bootstrap
     */
    public function bsBadge(string $content, string $type = 'primary', bool $pill = false): string
    {
        $classes = "badge bg-{$type}";
        if ($pill) {
            $classes .= ' rounded-pill';
        }

        return "<span class=\"{$classes}\">" . htmlspecialchars($content) . "</span>";
    }

    /**
     * Generar un botón de Bootstrap
     */
    public function bsButton(
        string $text,
        string $type = 'primary',
        string $size = 'md',
        array $attributes = []
    ): string {
        $classes = "btn btn-{$type}";

        if ($size !== 'md') {
            $classes .= " btn-{$size}";
        }

        $attrs = ['class' => $classes];
        $attrs = array_merge($attrs, $attributes);

        $attrString = '';
        foreach ($attrs as $key => $value) {
            $attrString .= " {$key}=\"" . htmlspecialchars($value) . "\"";
        }

        return "<button{$attrString}>" . htmlspecialchars($text) . "</button>";
    }

    /**
     * Generar un spinner de Bootstrap
     */
    public function bsSpinner(string $type = 'border', string $color = 'primary', string $size = ''): string
    {
        $classes = "spinner-{$type} text-{$color}";
        if ($size) {
            $classes .= " spinner-{$type}-{$size}";
        }

        return "<div class=\"{$classes}\" role=\"status\"><span class=\"visually-hidden\">Cargando...</span></div>";
    }

    // ==================== HELPERS PARA FORMULARIOS ====================

    /**
     * Generar un input de formulario
     */
    public function formInput(
        string $name,
        string $label,
        string $value = '',
        array $options = []
    ): string {
        $type = $options['type'] ?? 'text';
        $required = $options['required'] ?? false;
        $placeholder = $options['placeholder'] ?? '';
        $helpText = $options['help'] ?? '';
        $classes = $options['class'] ?? 'form-control';

        $html = '<div class="mb-3">';
        $html .= "<label for=\"{$name}\" class=\"form-label\">" . htmlspecialchars($label);

        if ($required) {
            $html .= ' <span class="text-danger">*</span>';
        }

        $html .= '</label>';

        $attrs = [
            'type' => $type,
            'class' => $classes,
            'id' => $name,
            'name' => $name,
            'value' => $value
        ];

        if ($placeholder) {
            $attrs['placeholder'] = $placeholder;
        }

        if ($required) {
            $attrs['required'] = 'required';
        }

        $attrString = '';
        foreach ($attrs as $key => $val) {
            if ($key === 'required') {
                $attrString .= ' required';
            } else {
                $attrString .= " {$key}=\"" . htmlspecialchars($val) . "\"";
            }
        }

        $html .= "<input{$attrString}>";

        if ($helpText) {
            $html .= "<div class=\"form-text\">" . htmlspecialchars($helpText) . "</div>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generar un textarea de formulario
     */
    public function formTextarea(
        string $name,
        string $label,
        string $value = '',
        array $options = []
    ): string {
        $required = $options['required'] ?? false;
        $rows = $options['rows'] ?? 4;
        $placeholder = $options['placeholder'] ?? '';
        $helpText = $options['help'] ?? '';

        $html = '<div class="mb-3">';
        $html .= "<label for=\"{$name}\" class=\"form-label\">" . htmlspecialchars($label);

        if ($required) {
            $html .= ' <span class="text-danger">*</span>';
        }

        $html .= '</label>';

        $attrs = [
            'class' => 'form-control',
            'id' => $name,
            'name' => $name,
            'rows' => $rows
        ];

        if ($placeholder) {
            $attrs['placeholder'] = $placeholder;
        }

        if ($required) {
            $attrs['required'] = 'required';
        }

        $attrString = '';
        foreach ($attrs as $key => $val) {
            if ($key === 'required') {
                $attrString .= ' required';
            } else {
                $attrString .= " {$key}=\"" . htmlspecialchars($val) . "\"";
            }
        }

        $html .= "<textarea{$attrString}>" . htmlspecialchars($value) . "</textarea>";

        if ($helpText) {
            $html .= "<div class=\"form-text\">" . htmlspecialchars($helpText) . "</div>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generar un select de formulario
     */
    public function formSelect(
        string $name,
        string $label,
        array $options = [],
        string $selected = '',
        array $config = []
    ): string {
        $required = $config['required'] ?? false;
        $helpText = $config['help'] ?? '';

        $html = '<div class="mb-3">';
        $html .= "<label for=\"{$name}\" class=\"form-label\">" . htmlspecialchars($label);

        if ($required) {
            $html .= ' <span class="text-danger">*</span>';
        }

        $html .= '</label>';

        $attrs = [
            'class' => 'form-select',
            'id' => $name,
            'name' => $name
        ];

        if ($required) {
            $attrs['required'] = 'required';
        }

        $attrString = '';
        foreach ($attrs as $key => $val) {
            if ($key === 'required') {
                $attrString .= ' required';
            } else {
                $attrString .= " {$key}=\"" . htmlspecialchars($val) . "\"";
            }
        }

        $html .= "<select{$attrString}>";

        foreach ($options as $value => $text) {
            $selectedAttr = ($value == $selected) ? ' selected' : '';
            $html .= "<option value=\"" . htmlspecialchars($value) . "\"{$selectedAttr}>";
            $html .= htmlspecialchars($text);
            $html .= "</option>";
        }

        $html .= '</select>';

        if ($helpText) {
            $html .= "<div class=\"form-text\">" . htmlspecialchars($helpText) . "</div>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generar un checkbox de formulario
     */
    public function formCheckbox(
        string $name,
        string $label,
        bool $checked = false,
        array $options = []
    ): string {
        $value = $options['value'] ?? '1';
        $helpText = $options['help'] ?? '';

        $html = '<div class="mb-3">';
        $html .= '<div class="form-check">';

        $attrs = [
            'class' => 'form-check-input',
            'type' => 'checkbox',
            'id' => $name,
            'name' => $name,
            'value' => $value
        ];

        if ($checked) {
            $attrs['checked'] = 'checked';
        }

        $attrString = '';
        foreach ($attrs as $key => $val) {
            if ($key === 'checked') {
                $attrString .= ' checked';
            } else {
                $attrString .= " {$key}=\"" . htmlspecialchars($val) . "\"";
            }
        }

        $html .= "<input{$attrString}>";
        $html .= "<label class=\"form-check-label\" for=\"{$name}\">";
        $html .= htmlspecialchars($label);
        $html .= '</label>';

        if ($helpText) {
            $html .= "<div class=\"form-text\">" . htmlspecialchars($helpText) . "</div>";
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    // ==================== HELPERS PARA NAVEGACIÓN ====================

    /**
     * Generar breadcrumb
     */
    public function bsBreadcrumb(array $items): string
    {
        $html = '<nav aria-label="breadcrumb">';
        $html .= '<ol class="breadcrumb">';

        $lastIndex = count($items) - 1;
        foreach ($items as $index => $item) {
            $isLast = ($index === $lastIndex);

            if ($isLast) {
                $html .= '<li class="breadcrumb-item active" aria-current="page">';
                $html .= htmlspecialchars($item['text']);
            } else {
                $html .= '<li class="breadcrumb-item">';
                $html .= '<a href="' . htmlspecialchars($item['url']) . '">';
                $html .= htmlspecialchars($item['text']);
                $html .= '</a>';
            }

            $html .= '</li>';
        }

        $html .= '</ol>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Generar paginación
     */
    public function bsPagination(int $currentPage, int $totalPages, string $baseUrl): string
    {
        if ($totalPages <= 1) {
            return '';
        }

        $html = '<nav aria-label="Paginación">';
        $html .= '<ul class="pagination justify-content-center">';

        // Botón anterior
        $prevDisabled = ($currentPage <= 1) ? ' disabled' : '';
        $prevPage = max(1, $currentPage - 1);
        $html .= "<li class=\"page-item{$prevDisabled}\">";
        $html .= "<a class=\"page-link\" href=\"{$baseUrl}?page={$prevPage}\" aria-label=\"Anterior\">";
        $html .= '<span aria-hidden="true">&laquo;</span>';
        $html .= '</a></li>';

        // Páginas
        $range = 2;
        $start = max(1, $currentPage - $range);
        $end = min($totalPages, $currentPage + $range);

        if ($start > 1) {
            $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$baseUrl}?page=1\">1</a></li>";
            if ($start > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            $active = ($i === $currentPage) ? ' active' : '';
            $html .= "<li class=\"page-item{$active}\">";
            $html .= "<a class=\"page-link\" href=\"{$baseUrl}?page={$i}\">{$i}</a>";
            $html .= '</li>';
        }

        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$baseUrl}?page={$totalPages}\">{$totalPages}</a></li>";
        }

        // Botón siguiente
        $nextDisabled = ($currentPage >= $totalPages) ? ' disabled' : '';
        $nextPage = min($totalPages, $currentPage + 1);
        $html .= "<li class=\"page-item{$nextDisabled}\">";
        $html .= "<a class=\"page-link\" href=\"{$baseUrl}?page={$nextPage}\" aria-label=\"Siguiente\">";
        $html .= '<span aria-hidden="true\">&raquo;</span>';
        $html .= '</a></li>';

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Obtener el motor Mustache
     */
    public function getEngine(): MustacheRenderer
    {
        return $this->mustache;
    }
}
