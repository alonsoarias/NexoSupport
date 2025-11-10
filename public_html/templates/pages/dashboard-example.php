<?php
/**
 * Ejemplo de Dashboard usando el Template Renderer ISER
 * Este archivo muestra cómo usar el sistema de plantillas corporativo
 */

// Incluir el renderer
require_once __DIR__ . '/../TemplateRenderer.php';

// Inicializar el renderer
TemplateRenderer::init(__DIR__ . '/..');

// Datos de ejemplo
$pageData = [
    'pageTitle' => 'Dashboard de Resultados - ISER',
    'headerTitle' => 'Dashboard de Resultados',
    'headerSubtitle' => 'Análisis Detallado - Prueba de competencias',
    'includeCharts' => true,
    'showStats' => true,
    'stats' => [
        ['label' => 'Total Estudiantes', 'value' => '340'],
        ['label' => 'Competencias Evaluadas', 'value' => '6'],
        ['label' => 'Rendimiento Global', 'value' => '87.79%'],
        ['label' => 'Puntos Totales', 'value' => '6030.40']
    ],
    'content' => '', // Se generará abajo
    'customJS' => '' // Se generará abajo
];

// Generar contenido de la página
ob_start();
?>

<h2 class="section-title">Resultados por Competencia</h2>

<div class="stats-grid">
    <?php
    // Datos de competencias
    $competencias = [
        [
            'titulo' => 'Competencia 1',
            'descripcion' => 'Ciudadanía y Convivencia',
            'porcentaje' => 96.25,
            'metricas' => [
                ['label' => 'Estudiantes', 'value' => '340'],
                ['label' => 'Promedio Obtenido', 'value' => '0.77'],
                ['label' => 'Promedio Máximo', 'value' => '0.80'],
                ['label' => 'Total Obtenido', 'value' => '1309.00'],
                ['label' => 'Total Máximo', 'value' => '1360.00']
            ]
        ],
        [
            'titulo' => 'Competencia 2',
            'descripcion' => 'Matemáticas Básicas',
            'porcentaje' => 82.28,
            'metricas' => [
                ['label' => 'Estudiantes', 'value' => '340'],
                ['label' => 'Promedio Obtenido', 'value' => '0.66'],
                ['label' => 'Promedio Máximo', 'value' => '0.80'],
                ['label' => 'Total Obtenido', 'value' => '1119.00'],
                ['label' => 'Total Máximo', 'value' => '1360.00']
            ]
        ],
        [
            'titulo' => 'Competencia 3',
            'descripcion' => 'Física Básica',
            'porcentaje' => 79.71,
            'metricas' => [
                ['label' => 'Estudiantes', 'value' => '340'],
                ['label' => 'Promedio Obtenido', 'value' => '0.60'],
                ['label' => 'Promedio Máximo', 'value' => '0.75'],
                ['label' => 'Total Obtenido', 'value' => '813.00'],
                ['label' => 'Total Máximo', 'value' => '1020.00']
            ]
        ],
        [
            'titulo' => 'Competencia 4',
            'descripcion' => 'Habilidad en Escritura',
            'porcentaje' => 84.53,
            'metricas' => [
                ['label' => 'Estudiantes', 'value' => '340'],
                ['label' => 'Promedio Obtenido', 'value' => '0.42'],
                ['label' => 'Promedio Máximo', 'value' => '0.50'],
                ['label' => 'Total Obtenido', 'value' => '287.40'],
                ['label' => 'Total Máximo', 'value' => '340.00']
            ]
        ],
        [
            'titulo' => 'Competencia 5',
            'descripcion' => 'Comprensión Lectora',
            'porcentaje' => 93.16,
            'metricas' => [
                ['label' => 'Estudiantes', 'value' => '340'],
                ['label' => 'Promedio Obtenido', 'value' => '0.75'],
                ['label' => 'Promedio Máximo', 'value' => '0.80'],
                ['label' => 'Total Obtenido', 'value' => '1267.00'],
                ['label' => 'Total Máximo', 'value' => '1360.00']
            ]
        ],
        [
            'titulo' => 'Competencia 6',
            'descripcion' => 'Informática Básica',
            'porcentaje' => 90.81,
            'metricas' => [
                ['label' => 'Estudiantes', 'value' => '340'],
                ['label' => 'Promedio Obtenido', 'value' => '0.73'],
                ['label' => 'Promedio Máximo', 'value' => '0.80'],
                ['label' => 'Total Obtenido', 'value' => '1235.00'],
                ['label' => 'Total Máximo', 'value' => '1360.00']
            ]
        ]
    ];

    // Renderizar cards de competencias
    foreach ($competencias as $comp) {
        echo TemplateRenderer::card([
            'title' => $comp['titulo'],
            'percentage' => $comp['porcentaje'],
            'description' => $comp['descripcion'],
            'metrics' => $comp['metricas'],
            'progressValue' => $comp['porcentaje']
        ]);
    }
    ?>
</div>

<!-- Sección de Gráficos -->
<div class="charts-section mt-4">
    <h2 class="section-title">Análisis Gráfico</h2>

    <?= TemplateRenderer::chartContainer('performanceChart', 'Rendimiento por competencia') ?>
    <?= TemplateRenderer::chartContainer('pointsChart', 'Puntos Obtenidos vs Puntos Máximos') ?>
</div>

<?php
$pageData['content'] = ob_get_clean();

// Generar JavaScript para los gráficos
ob_start();
?>

// Datos de competencias
const competenciasData = {
    labels: [
        'Ciudadanía y Convivencia',
        'Matemáticas Básicas',
        'Física Básica',
        'Habilidad en Escritura',
        'Comprensión Lectora',
        'Informática Básica'
    ],
    percentages: [96.25, 82.28, 79.71, 84.53, 93.16, 90.81],
    totalObtained: [1309.00, 1119.00, 813.00, 287.40, 1267.00, 1235.00],
    totalMax: [1360.00, 1360.00, 1020.00, 340.00, 1360.00, 1360.00]
};

// Crear gráfico de barras
createBarChart('performanceChart', competenciasData.labels, competenciasData.percentages, {
    label: 'Porcentaje de Rendimiento',
    suffix: '%',
    maxValue: 100
});

// Crear gráfico de líneas
createLineChart('pointsChart', competenciasData.labels, [
    {
        label: 'Puntos Obtenidos',
        data: competenciasData.totalObtained
    },
    {
        label: 'Puntos Máximos',
        data: competenciasData.totalMax
    }
], {
    suffix: ' pts'
});

<?php
$pageData['customJS'] = ob_get_clean();

// Renderizar la página completa
echo TemplateRenderer::render('', $pageData);
