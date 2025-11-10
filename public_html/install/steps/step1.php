<?php
/**
 * Paso 1: Verificación de Requisitos del Sistema
 */

$requirements = [
    'php' => [
        'name' => 'PHP Version >= 8.1',
        'required' => true,
        'current' => PHP_VERSION,
        'status' => version_compare(PHP_VERSION, '8.1.0', '>=')
    ],
    'pdo' => [
        'name' => 'PDO Extension',
        'required' => true,
        'current' => extension_loaded('pdo') ? 'Instalado' : 'No instalado',
        'status' => extension_loaded('pdo')
    ],
    'pdo_mysql' => [
        'name' => 'PDO MySQL Driver',
        'required' => true,
        'current' => extension_loaded('pdo_mysql') ? 'Instalado' : 'No instalado',
        'status' => extension_loaded('pdo_mysql')
    ],
    'mbstring' => [
        'name' => 'Mbstring Extension',
        'required' => true,
        'current' => extension_loaded('mbstring') ? 'Instalado' : 'No instalado',
        'status' => extension_loaded('mbstring')
    ],
    'json' => [
        'name' => 'JSON Extension',
        'required' => true,
        'current' => extension_loaded('json') ? 'Instalado' : 'No instalado',
        'status' => extension_loaded('json')
    ],
    'openssl' => [
        'name' => 'OpenSSL Extension',
        'required' => true,
        'current' => extension_loaded('openssl') ? 'Instalado' : 'No instalado',
        'status' => extension_loaded('openssl')
    ],
    'sodium' => [
        'name' => 'Sodium Extension',
        'required' => true,
        'current' => extension_loaded('sodium') ? 'Instalado' : 'No instalado',
        'status' => extension_loaded('sodium')
    ],
    'curl' => [
        'name' => 'cURL Extension',
        'required' => false,
        'current' => extension_loaded('curl') ? 'Instalado' : 'No instalado',
        'status' => extension_loaded('curl')
    ],
    'gd' => [
        'name' => 'GD Extension (para QR codes)',
        'required' => false,
        'current' => extension_loaded('gd') ? 'Instalado' : 'No instalado',
        'status' => extension_loaded('gd')
    ]
];

$permissions = [
    'env' => [
        'path' => BASE_DIR,
        'name' => 'Directorio raíz (para crear .env)',
        'status' => is_writable(BASE_DIR)
    ],
    'logs' => [
        'path' => BASE_DIR . '/var/logs',
        'name' => 'Directorio de logs',
        'status' => is_dir(BASE_DIR . '/var/logs') && is_writable(BASE_DIR . '/var/logs')
    ],
    'cache' => [
        'path' => BASE_DIR . '/var/cache',
        'name' => 'Directorio de cache',
        'status' => is_dir(BASE_DIR . '/var/cache') && is_writable(BASE_DIR . '/var/cache')
    ]
];

$allRequiredOk = true;
foreach ($requirements as $req) {
    if ($req['required'] && !$req['status']) {
        $allRequiredOk = false;
        break;
    }
}

$allPermissionsOk = true;
foreach ($permissions as $perm) {
    if (!$perm['status']) {
        $allPermissionsOk = false;
        break;
    }
}

$canContinue = $allRequiredOk && $allPermissionsOk;
?>

<div class="mb-4">
    <p class="lead">
        Este asistente verificará que su sistema cumple con los requisitos necesarios
        para instalar ISER Authentication System.
    </p>
</div>

<h5 class="mb-3"><i class="bi bi-server me-2"></i>Requisitos del Sistema</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th style="width: 40%">Requisito</th>
                <th style="width: 25%">Requerido</th>
                <th style="width: 20%">Estado Actual</th>
                <th style="width: 15%" class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requirements as $req): ?>
                <tr>
                    <td><?= $req['name'] ?></td>
                    <td>
                        <?php if ($req['required']): ?>
                            <span class="badge bg-danger">Requerido</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Opcional</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $req['current'] ?></td>
                    <td class="text-center">
                        <?php if ($req['status']): ?>
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <?php else: ?>
                            <?php if ($req['required']): ?>
                                <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                            <?php else: ?>
                                <i class="bi bi-exclamation-circle-fill text-warning fs-5"></i>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h5 class="mb-3"><i class="bi bi-folder me-2"></i>Permisos de Archivos</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th style="width: 60%">Directorio</th>
                <th style="width: 25%">Ruta</th>
                <th style="width: 15%" class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($permissions as $perm): ?>
                <tr>
                    <td><?= $perm['name'] ?></td>
                    <td><code><?= $perm['path'] ?></code></td>
                    <td class="text-center">
                        <?php if ($perm['status']): ?>
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <?php else: ?>
                            <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (!$canContinue): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>No se puede continuar</strong>: Su sistema no cumple con todos los requisitos necesarios.
        Por favor, corrija los problemas indicados antes de continuar.
    </div>
<?php else: ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill me-2"></i>
        <strong>¡Excelente!</strong> Su sistema cumple con todos los requisitos necesarios.
    </div>
<?php endif; ?>

<div class="d-flex justify-content-end mt-4">
    <a href="?step=2" class="btn btn-primary btn-lg <?= !$canContinue ? 'disabled' : '' ?>">
        Siguiente <i class="bi bi-arrow-right ms-2"></i>
    </a>
</div>
