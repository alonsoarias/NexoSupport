<?php
/**
 * Stage: Install Database Schema - Refactorizado
 */

$progress = 66;

// Auto-ejecutar instalación de BD
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Primera carga, mostrar UI e iniciar instalación automáticamente
    ?>
    <div class="stage-indicator">
        <i class="fas fa-cog fa-spin icon"></i>
        <div class="text">
            <div class="step-number">Paso 4 de 6</div>
            <strong>Instalando Base de Datos</strong>
        </div>
    </div>

    <h1><i class="fas fa-database icon"></i>Instalación de Base de Datos</h1>
    <h2>Creando tablas del sistema</h2>

    <div class="progress">
        <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> <strong>Instalando...</strong><br>
        Por favor espere mientras se crean las tablas del sistema.
    </div>

    <form id="install-form" method="POST" action="/install?stage=install_db" style="display: none;">
        <input type="hidden" name="action" value="install_schema">
    </form>

    <script>
        // Auto-submit después de 1 segundo
        setTimeout(function() {
            document.getElementById('install-form').submit();
        }, 1000);
    </script>
    <?php
} else {
    // Procesando instalación
    if (isset($action_result)) {
        if ($action_result['success']) {
            // Éxito - redirigir (ya se hace en index.php)
            echo '<div class="alert alert-success">';
            echo '<i class="fas fa-check-circle"></i> Base de datos instalada correctamente';
            echo '</div>';
        } else {
            // Error
            ?>
            <div class="stage-indicator">
                <i class="fas fa-exclamation-triangle icon"></i>
                <div class="text">
                    <div class="step-number">Paso 4 de 6</div>
                    <strong>Error en Instalación</strong>
                </div>
            </div>

            <h1><i class="fas fa-exclamation-triangle icon"></i>Error en Instalación</h1>

            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong><br>
                <?php echo htmlspecialchars($action_result['error']); ?>
            </div>

            <?php if (!empty($action_result['log'])): ?>
                <h3>Log de instalación:</h3>
                <div class="log-output">
                    <?php foreach ($action_result['log'] as $line): ?>
                        <div><?php echo htmlspecialchars($line); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="actions">
                <a href="/install?stage=database" class="btn"><i class="fas fa-arrow-left icon"></i>Volver a Configuración de BD</a>
            </div>
            <?php
        }
    }
}
?>
