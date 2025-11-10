# Sistema de Plantillas ISER

Sistema de plantillas corporativo para el Instituto Superior de Educación Rural (ISER) con diseño consistente y colores institucionales.

## Colores Corporativos

- **Verde Principal**: `#1B9E88`
- **Amarillo**: `#F4C430`
- **Rojo**: `#EB4335`
- **Texto Principal**: `#242424`
- **Texto Secundario**: `#646363`

## Estructura de Archivos

```
templates/
├── layouts/
│   └── base.php              # Layout base con header y footer
├── components/
│   ├── header.php            # Header corporativo ISER
│   ├── footer.php            # Footer con franja de colores
│   └── stats.php             # Grid de estadísticas
├── pages/
│   └── (coloque aquí sus páginas personalizadas)
├── TemplateRenderer.php      # Clase helper para renderizar
└── README.md                 # Esta documentación
```

## Uso Básico

### 1. Renderizar una página completa

```php
<?php
require_once 'templates/TemplateRenderer.php';
TemplateRenderer::init(__DIR__ . '/templates');

$html = TemplateRenderer::render('', [
    'pageTitle' => 'Mi Página',
    'headerTitle' => 'Dashboard',
    'headerSubtitle' => 'Análisis de Datos',
    'content' => '<h1>Contenido de mi página</h1>',
    'showStats' => true,
    'stats' => [
        ['label' => 'Total', 'value' => '100'],
        ['label' => 'Activos', 'value' => '85']
    ]
]);

echo $html;
```

### 2. Crear Cards

```php
echo TemplateRenderer::card([
    'title' => 'Competencia 1',
    'percentage' => 95.5,
    'description' => 'Matemáticas Básicas',
    'metrics' => [
        ['label' => 'Estudiantes', 'value' => '340'],
        ['label' => 'Promedio', 'value' => '0.95']
    ],
    'progressValue' => 95.5
]);
```

### 3. Crear Gráficos (JavaScript)

```javascript
// Gráfico de barras
createBarChart('myChart', labels, data, {
    label: 'Mi Gráfico',
    suffix: '%',
    maxValue: 100
});

// Gráfico de líneas
createLineChart('myLineChart', labels, [
    { label: 'Serie 1', data: data1 },
    { label: 'Serie 2', data: data2 }
], {
    suffix: ' pts'
});
```

## Variables Disponibles

### Layout Base

- `$pageTitle`: Título de la página (meta title)
- `$headerTitle`: Título del header
- `$headerSubtitle`: Subtítulo del header
- `$content`: Contenido principal de la página
- `$showStats`: Mostrar grid de estadísticas (boolean)
- `$stats`: Array de estadísticas
- `$includeCharts`: Incluir Chart.js (boolean)
- `$customCSS`: CSS personalizado
- `$customJS`: JavaScript personalizado
- `$additionalCSS`: Array de URLs de CSS adicionales
- `$additionalJS`: Array de URLs de JS adicionales

### Stats Array

```php
$stats = [
    ['label' => 'Etiqueta', 'value' => 'Valor'],
    ...
];
```

## Clases CSS Disponibles

### Layout
- `.container`: Contenedor principal
- `.content`: Área de contenido

### Componentes
- `.iser-header`: Header corporativo
- `.stat-card`: Tarjeta de estadística
- `.card`: Tarjeta genérica
- `.chart-container`: Contenedor de gráfico

### Utilidades
- `.text-center`, `.text-left`, `.text-right`
- `.mt-1` a `.mt-4`: Margin top
- `.mb-1` a `.mb-4`: Margin bottom
- `.p-1` a `.p-4`: Padding
- `.d-flex`, `.justify-content-between`, `.align-items-center`

## Funciones JavaScript Globales

- `createBarChart(canvasId, labels, data, options)`: Crear gráfico de barras
- `createLineChart(canvasId, labels, datasets, options)`: Crear gráfico de líneas
- `formatNumber(num, decimals)`: Formatear números
- `calculatePercentage(obtained, max)`: Calcular porcentaje
- `showNotification(message, type, duration)`: Mostrar notificación

## Ejemplo de Uso Completo

```php
<?php
require_once 'templates/TemplateRenderer.php';
TemplateRenderer::init(__DIR__ . '/templates');

// Crear contenido de la página
ob_start();
?>
<h2 class="section-title">Mi Dashboard</h2>

<div class="stats-grid">
    <?php
    echo TemplateRenderer::card([
        'title' => 'Competencia 1',
        'percentage' => 95.5,
        'description' => 'Matemáticas',
        'metrics' => [
            ['label' => 'Total', 'value' => '100']
        ],
        'progressValue' => 95.5
    ]);
    ?>
</div>

<?= TemplateRenderer::chartContainer('myChart', 'Mi Gráfico') ?>

<script>
createBarChart('myChart', ['A', 'B', 'C'], [10, 20, 30]);
</script>

<?php
$content = ob_get_clean();

// Renderizar página completa
echo TemplateRenderer::render('', [
    'pageTitle' => 'Mi Dashboard',
    'headerTitle' => 'Dashboard',
    'content' => $content,
    'includeCharts' => true
]);
```

## Personalización

### Agregar CSS personalizado

```php
$pageData = [
    'customCSS' => '
        .mi-clase { color: red; }
    '
];
```

### Agregar JavaScript personalizado

```php
$pageData = [
    'customJS' => '
        console.log("Mi script");
    '
];
```

## Soporte

Para más información, consulte la documentación del sistema ISER o contacte al equipo de desarrollo.
