<?php
/**
 * Stage 1: Requirements Check
 */

$checks = [
    'PHP >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'PDO' => extension_loaded('pdo'),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'MBString' => extension_loaded('mbstring'),
    'JSON' => extension_loaded('json'),
    'OpenSSL' => extension_loaded('openssl'),
    'DOM' => extension_loaded('dom'),
    'SimpleXML' => extension_loaded('simplexml'),
];

$allOk = !in_array(false, $checks, true);
?>

<h3 class="mb-4">Requisitos del Sistema</h3>

<table class="table">
    <thead>
        <tr>
            <th>Requisito</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($checks as $name => $status): ?>
            <tr>
                <td><?= $name ?></td>
                <td>
                    <?php if ($status): ?>
                        <span class="text-success"><i class="bi bi-check-circle-fill"></i> OK</span>
                    <?php else: ?>
                        <span class="text-danger"><i class="bi bi-x-circle-fill"></i> Falta</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<form method="POST">
    <input type="hidden" name="stage" value="<?= STAGE_REQUIREMENTS ?>">
    <div class="text-end">
        <button type="submit" name="next" class="btn btn-primary" <?= !$allOk ? 'disabled' : '' ?>>
            Siguiente <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</form>
