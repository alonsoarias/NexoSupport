<!-- Header ISER -->
<div class="iser-header">
    <div id="logo-container">
        <img src="https://www.iser.edu.co/wp-content/uploads/2024/07/cropped-cropped-LOGO-NEGRO-HORIZONTAL-01-2.png"
             alt="ISER Logo"
             class="iser-header-logo"
             onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='block';">
        <div id="logo-fallback" class="iser-header-logo-fallback" style="display: none;">
            <div>Instituto Superior de</div>
            <div>Educación Rural</div>
            <div class="iser">ISER</div>
        </div>
    </div>
    <div class="iser-header-info">
        <h1><?= htmlspecialchars($headerTitle ?? 'Dashboard de Resultados') ?></h1>
        <p><?= htmlspecialchars($headerSubtitle ?? 'Sistema de Gestión') ?></p>
        <p class="vigilado">Vigilado Mineducación</p>
    </div>
</div>
