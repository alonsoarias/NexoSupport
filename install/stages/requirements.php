<?php
/**
 * Stage: Requirements Check
 */

$progress = 16;

// Verificar requisitos
$requirements = [];

// PHP Version
$requirements[] = [
    'name' => 'PHP Version >= 8.1',
    'status' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'current' => PHP_VERSION
];

// PDO
$requirements[] = [
    'name' => 'PDO Extension',
    'status' => extension_loaded('pdo'),
    'current' => extension_loaded('pdo') ? 'Installed' : 'Not installed'
];

// PDO MySQL
$requirements[] = [
    'name' => 'PDO MySQL Driver',
    'status' => extension_loaded('pdo_mysql'),
    'current' => extension_loaded('pdo_mysql') ? 'Installed' : 'Not installed'
];

// JSON
$requirements[] = [
    'name' => 'JSON Extension',
    'status' => extension_loaded('json'),
    'current' => extension_loaded('json') ? 'Installed' : 'Not installed'
];

// mbstring
$requirements[] = [
    'name' => 'mbstring Extension',
    'status' => extension_loaded('mbstring'),
    'current' => extension_loaded('mbstring') ? 'Installed' : 'Not installed'
];

// Writable directories
$writable_dirs = [
    BASE_DIR . '/var',
    BASE_DIR . '/var/cache',
    BASE_DIR . '/var/logs',
    BASE_DIR . '/var/sessions',
];

foreach ($writable_dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $requirements[] = [
        'name' => 'Writable: ' . str_replace(BASE_DIR, '', $dir),
        'status' => is_writable($dir),
        'current' => is_writable($dir) ? 'Writable' : 'Not writable'
    ];
}

// Verificar si todos los requisitos se cumplen
$all_ok = true;
foreach ($requirements as $req) {
    if (!$req['status']) {
        $all_ok = false;
        break;
    }
}
?>

<h1>Verificación de Requisitos</h1>
<h2>Comprobando que el servidor cumple los requisitos mínimos</h2>

<div class="progress">
    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
</div>

<?php if ($all_ok): ?>
    <div class="alert alert-success">
        <strong>¡Perfecto!</strong> Su servidor cumple con todos los requisitos.
    </div>
<?php else: ?>
    <div class="alert alert-error">
        <strong>Atención:</strong> Su servidor no cumple con algunos requisitos. Por favor corrija los problemas antes de continuar.
    </div>
<?php endif; ?>

<div style="margin: 20px 0;">
    <?php foreach ($requirements as $req): ?>
        <div class="requirement">
            <span><?php echo htmlspecialchars($req['name']); ?></span>
            <span class="status <?php echo $req['status'] ? 'ok' : 'error'; ?>">
                <?php echo $req['status'] ? '✓ ' : '✗ '; ?>
                <?php echo htmlspecialchars($req['current']); ?>
            </span>
        </div>
    <?php endforeach; ?>
</div>

<div class="actions">
    <a href="/install?stage=welcome" class="btn btn-secondary">Atrás</a>
    <?php if ($all_ok): ?>
        <a href="/install?stage=database" class="btn">Continuar</a>
    <?php else: ?>
        <a href="/install?stage=requirements" class="btn">Verificar nuevamente</a>
    <?php endif; ?>
</div>
