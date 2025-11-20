<?php
/**
 * System Upgrade Page - Refactorizado
 *
 * Detecta y ejecuta actualizaciones del sistema usando la clase Upgrader.
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../config.php');

require_login();

// Verificar que el usuario sea site administrator
global $USER;

if (!is_siteadmin($USER->id)) {
    print_error('upgrademinrequired', 'core', '/', null,
        'Solo los administradores del sitio pueden ejecutar actualizaciones del sistema.');
}

// Cargar clase Upgrader
require_once(BASE_DIR . '/lib/classes/install/upgrader.php');

use core\install\upgrader;

// Instanciar upgrader
$upgrader = new upgrader();

// Verificar requisitos
$requirements = $upgrader->check_requirements();

// Obtener información del upgrade
$upgrade_info = $upgrader->get_upgrade_info();

// Procesar solicitud de upgrade
$upgrade_executed = false;
$upgrade_result = null;

if (isset($_POST['upgrade']) && $_POST['upgrade'] === 'true') {
    // Verificar sesskey para seguridad
    if (!isset($_POST['sesskey']) || $_POST['sesskey'] !== sesskey()) {
        print_error('invalidtoken');
    }

    // Ejecutar upgrade
    $upgrade_executed = true;
    $upgrade_result = $upgrader->execute();
}

// Renderizar página
$PAGE->set_context(\core\rbac\context::system());
$PAGE->set_url('/admin/upgrade.php');
$PAGE->set_title('Actualización del Sistema');
$PAGE->set_heading('Actualización del Sistema');

echo $OUTPUT->header();

?>

<style>
.upgrade-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.upgrade-info-box {
    background: #f8f9fa;
    border-left: 4px solid #667eea;
    padding: 20px;
    margin: 20px 0;
    border-radius: 4px;
}

.upgrade-info-box h3 {
    margin-top: 0;
    color: #333;
}

.version-table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0;
}

.version-table td {
    padding: 10px;
    border-bottom: 1px solid #dee2e6;
}

.version-table td:first-child {
    font-weight: bold;
    width: 200px;
}

.upgrade-list {
    list-style: none;
    padding: 0;
}

.upgrade-list li {
    padding: 10px;
    margin: 5px 0;
    background: #e9ecef;
    border-radius: 4px;
}

.upgrade-list li i {
    color: #667eea;
    margin-right: 10px;
}

.requirements-list {
    list-style: none;
    padding: 0;
}

.requirements-list li {
    padding: 12px;
    margin: 8px 0;
    background: #f8f9fa;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.requirements-list .status {
    font-weight: bold;
}

.requirements-list .status.ok {
    color: #28a745;
}

.requirements-list .status.error {
    color: #dc3545;
}

.log-output {
    background: #2d2d2d;
    color: #f8f8f2;
    padding: 15px;
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    max-height: 400px;
    overflow-y: auto;
    margin: 20px 0;
}

.log-output div {
    margin: 3px 0;
}

.upgrade-actions {
    margin: 30px 0;
    text-align: center;
}

.btn-upgrade {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 15px 40px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.btn-upgrade:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-upgrade:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
}
</style>

<div class="upgrade-container">
    <h1><i class="fas fa-sync-alt"></i> Actualización del Sistema</h1>

    <?php if ($upgrade_executed): ?>
        <!-- Resultado del Upgrade -->
        <?php if ($upgrade_result['success']): ?>
            <div class="alert alert-success">
                <h4><i class="fas fa-check-circle"></i> Actualización Completada Exitosamente</h4>
                <p>El sistema ha sido actualizado a la versión <?php echo $upgrade_info['release']; ?> (<?php echo $upgrade_info['code_version']; ?>).</p>
            </div>

            <?php if (!empty($upgrade_result['log'])): ?>
                <h3>Log de Actualización:</h3>
                <div class="log-output">
                    <?php foreach ($upgrade_result['log'] as $line): ?>
                        <div><?php echo htmlspecialchars($line); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="upgrade-actions">
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home"></i> Ir al Sistema
                </a>
            </div>

        <?php else: ?>
            <!-- Error en el Upgrade -->
            <div class="alert alert-danger">
                <h4><i class="fas fa-exclamation-triangle"></i> Error Durante la Actualización</h4>
                <p>Ocurrió un error durante el proceso de actualización.</p>
            </div>

            <?php if (!empty($upgrade_result['errors'])): ?>
                <h3>Errores Encontrados:</h3>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($upgrade_result['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($upgrade_result['log'])): ?>
                <h3>Log de Actualización:</h3>
                <div class="log-output">
                    <?php foreach ($upgrade_result['log'] as $line): ?>
                        <div><?php echo htmlspecialchars($line); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="upgrade-actions">
                <a href="/admin/upgrade.php" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Intentar Nuevamente
                </a>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Información del Upgrade -->

        <?php if ($upgrader->needs_upgrade()): ?>
            <div class="alert alert-warning">
                <h4><i class="fas fa-exclamation-circle"></i> Actualización Disponible</h4>
                <p>Hay una nueva versión del sistema disponible. Se requiere ejecutar el proceso de actualización.</p>
            </div>

            <div class="upgrade-info-box">
                <h3>Información de Versiones</h3>
                <table class="version-table">
                    <tr>
                        <td>Versión Actual en Base de Datos:</td>
                        <td><?php echo $upgrade_info['db_version'] ?? 'No detectada'; ?></td>
                    </tr>
                    <tr>
                        <td>Versión del Código:</td>
                        <td><strong><?php echo $upgrade_info['code_version']; ?> (<?php echo $upgrade_info['release']; ?>)</strong></td>
                    </tr>
                    <tr>
                        <td>Diferencia de Versión:</td>
                        <td><?php echo $upgrade_info['version_diff'] ?? 0; ?></td>
                    </tr>
                </table>
            </div>

            <?php if (isset($upgrade_info['upgrades_to_execute']) && !empty($upgrade_info['upgrades_to_execute'])): ?>
                <div class="upgrade-info-box">
                    <h3>Upgrades a Ejecutar</h3>
                    <ul class="upgrade-list">
                        <?php foreach ($upgrade_info['upgrades_to_execute'] as $upgrade): ?>
                            <li>
                                <i class="fas fa-arrow-right"></i>
                                Versión <?php echo $upgrade['version']; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Verificación de Requisitos -->
            <?php if (!$requirements['success']): ?>
                <div class="alert alert-danger">
                    <h4><i class="fas fa-exclamation-triangle"></i> Requisitos No Cumplidos</h4>
                    <p>Los siguientes requisitos deben ser cumplidos antes de ejecutar la actualización:</p>
                </div>

                <ul class="requirements-list">
                    <?php foreach ($requirements['issues'] as $issue): ?>
                        <li>
                            <span><?php echo htmlspecialchars($issue); ?></span>
                            <span class="status error"><i class="fas fa-times-circle"></i> Error</span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="alert alert-info">
                    <p>Por favor, corrija estos problemas antes de continuar con la actualización.</p>
                </div>

            <?php else: ?>
                <!-- Todo OK, permitir upgrade -->
                <div class="alert alert-success">
                    <h4><i class="fas fa-check-circle"></i> Requisitos Cumplidos</h4>
                    <p>Todos los requisitos han sido verificados. El sistema está listo para ser actualizado.</p>
                </div>

                <div class="alert alert-info">
                    <h4><i class="fas fa-info-circle"></i> Importante</h4>
                    <ul style="margin: 10px 0 0 20px;">
                        <li>Asegúrese de tener un respaldo de la base de datos antes de continuar.</li>
                        <li>La actualización puede tomar varios minutos dependiendo de la cantidad de cambios.</li>
                        <li>No cierre esta ventana ni apague el servidor durante el proceso.</li>
                    </ul>
                </div>

                <form method="POST" action="/admin/upgrade.php" id="upgrade-form">
                    <input type="hidden" name="upgrade" value="true">
                    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

                    <div class="upgrade-actions">
                        <button type="submit" class="btn-upgrade" onclick="return confirm('¿Está seguro de que desea ejecutar la actualización? Asegúrese de tener un respaldo de la base de datos.');">
                            <i class="fas fa-rocket"></i> Ejecutar Actualización
                        </button>
                    </div>
                </form>
            <?php endif; ?>

        <?php else: ?>
            <!-- Sistema actualizado -->
            <div class="alert alert-success">
                <h4><i class="fas fa-check-circle"></i> Sistema Actualizado</h4>
                <p>El sistema está en la última versión disponible.</p>
            </div>

            <div class="upgrade-info-box">
                <h3>Información de Versión</h3>
                <table class="version-table">
                    <tr>
                        <td>Versión Actual:</td>
                        <td><strong><?php echo $upgrade_info['code_version']; ?> (<?php echo $upgrade_info['release']; ?>)</strong></td>
                    </tr>
                </table>
            </div>

            <div class="upgrade-actions">
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home"></i> Ir al Sistema
                </a>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php

echo $OUTPUT->footer();
