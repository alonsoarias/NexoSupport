<?php
/**
 * Stage: Finish Installation - Refactorizado
 */

$progress = 100;

// Auto-ejecutar finalización si aún no se ha hecho
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
    <div class="stage-indicator">
        <i class="fas fa-cog fa-spin icon"></i>
        <div class="text">
            <div class="step-number">Paso 6 de 6</div>
            <strong>Finalizando Instalación</strong>
        </div>
    </div>

    <h1><i class="fas fa-rocket icon"></i>Finalizando Instalación</h1>
    <h2>Configurando sistema RBAC y completando instalación</h2>

    <div class="progress">
        <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> <strong>Procesando...</strong><br>
        Instalando sistema de roles y permisos, configurando sistema...
    </div>

    <form id="finalize-form" method="POST" action="/install?stage=finish" style="display: none;">
        <input type="hidden" name="action" value="finalize">
    </form>

    <script>
        // Auto-submit después de 1 segundo
        setTimeout(function() {
            document.getElementById('finalize-form').submit();
        }, 1000);
    </script>
    <?php
} else {
    // Procesando finalización
    if (isset($action_result)) {
        if ($action_result['success']) {
            // Éxito
            ?>
            <div class="stage-indicator">
                <i class="fas fa-check-circle icon"></i>
                <div class="text">
                    <div class="step-number">Paso 6 de 6</div>
                    <strong>¡Instalación Completada!</strong>
                </div>
            </div>

            <h1><i class="fas fa-check-circle icon"></i>¡Instalación Completada!</h1>
            <h2>NexoSupport está listo para usar</h2>

            <div class="progress">
                <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
            </div>

            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <strong>¡Felicidades!</strong><br>
                NexoSupport ha sido instalado exitosamente.
            </div>

            <?php if (!empty($action_result['log'])): ?>
                <div style="background: #f5f5f5; padding: 20px; border-radius: 6px; margin: 20px 0;">
                    <h3 style="margin-top: 0;">Tareas completadas:</h3>
                    <ul style="line-height: 1.8; margin-left: 20px;">
                        <?php foreach ($action_result['log'] as $line): ?>
                            <li><?php echo htmlspecialchars($line); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <strong>Próximos pasos:</strong><br>
                1. Inicie sesión con su cuenta de administrador<br>
                2. Configure el sistema desde el panel de administración<br>
                3. Cree usuarios y asigne roles<br>
                4. Personalice el tema y la apariencia
            </div>

            <div class="actions" style="justify-content: center;">
                <a href="/" class="btn"><i class="fas fa-arrow-right icon"></i>Ir al Sistema</a>
            </div>
            <?php
        } else {
            // Error
            ?>
            <div class="stage-indicator">
                <i class="fas fa-exclamation-triangle icon"></i>
                <div class="text">
                    <div class="step-number">Paso 6 de 6</div>
                    <strong>Error en Finalización</strong>
                </div>
            </div>

            <h1><i class="fas fa-exclamation-triangle icon"></i>Error en Finalización</h1>

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
                <a href="/install?stage=admin" class="btn"><i class="fas fa-arrow-left icon"></i>Volver</a>
            </div>
            <?php
        }
    }
}
?>
