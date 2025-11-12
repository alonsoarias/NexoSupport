<?php
/**
 * Stage 6: Basic Configuration
 * Essential settings only - advanced config will be done in admin panel
 */

// Get session values or set defaults
$jwtSecret = $_SESSION['jwt_secret'] ?? bin2hex(random_bytes(32));
$timezone = $_SESSION['timezone'] ?? 'America/Bogota';
$locale = $_SESSION['locale'] ?? 'es';
?>

<div class="stage-container">
    <div class="stage-header">
        <i class="bi bi-gear-fill" style="font-size: 3rem; color: var(--iser-green);"></i>
        <h2>Configuración Básica</h2>
        <p class="subtitle">Configuración esencial del sistema</p>
    </div>

    <div class="info-box" style="margin-bottom: 30px;">
        <i class="bi bi-info-circle" style="color: var(--iser-green); font-size: 1.5rem;"></i>
        <div>
            <strong>Configuraciones Avanzadas</strong>
            <p style="margin: 10px 0 0 0;">Email, caché, logging y otras configuraciones avanzadas se pueden configurar después desde el Panel de Administración.</p>
        </div>
    </div>

    <!-- JWT Secret -->
    <div class="form-group">
        <label for="jwt_secret">
            <i class="bi bi-key"></i> JWT Secret Key
            <span class="required">*</span>
        </label>
        <div style="display: flex; gap: 10px;">
            <input type="text"
                   id="jwt_secret"
                   name="jwt_secret"
                   value="<?= htmlspecialchars($jwtSecret) ?>"
                   required
                   readonly
                   style="flex: 1; font-family: monospace; background: var(--bg-light);">
            <button type="button"
                    class="btn btn-secondary"
                    onclick="regenerateJWT()"
                    style="white-space: nowrap;">
                <i class="bi bi-arrow-clockwise"></i> Regenerar
            </button>
        </div>
        <small class="form-text">Clave secreta para tokens JWT (generada automáticamente)</small>
    </div>

    <!-- Timezone -->
    <div class="form-group">
        <label for="timezone">
            <i class="bi bi-clock"></i> Zona Horaria
            <span class="required">*</span>
        </label>
        <select id="timezone" name="timezone" required class="form-control">
            <optgroup label="América Latina">
                <option value="America/Bogota" <?= $timezone === 'America/Bogota' ? 'selected' : '' ?>>Bogotá (GMT-5)</option>
                <option value="America/Mexico_City" <?= $timezone === 'America/Mexico_City' ? 'selected' : '' ?>>Ciudad de México (GMT-6)</option>
                <option value="America/Lima" <?= $timezone === 'America/Lima' ? 'selected' : '' ?>>Lima (GMT-5)</option>
                <option value="America/Santiago" <?= $timezone === 'America/Santiago' ? 'selected' : '' ?>>Santiago (GMT-3/-4)</option>
                <option value="America/Buenos_Aires" <?= $timezone === 'America/Buenos_Aires' ? 'selected' : '' ?>>Buenos Aires (GMT-3)</option>
                <option value="America/Sao_Paulo" <?= $timezone === 'America/Sao_Paulo' ? 'selected' : '' ?>>São Paulo (GMT-3)</option>
                <option value="America/Caracas" <?= $timezone === 'America/Caracas' ? 'selected' : '' ?>>Caracas (GMT-4)</option>
                <option value="America/Panama" <?= $timezone === 'America/Panama' ? 'selected' : '' ?>>Panamá (GMT-5)</option>
            </optgroup>
            <optgroup label="Internacional">
                <option value="America/New_York" <?= $timezone === 'America/New_York' ? 'selected' : '' ?>>New York (GMT-5/-4)</option>
                <option value="Europe/Madrid" <?= $timezone === 'Europe/Madrid' ? 'selected' : '' ?>>Madrid (GMT+1/+2)</option>
                <option value="UTC" <?= $timezone === 'UTC' ? 'selected' : '' ?>>UTC (GMT+0)</option>
            </optgroup>
        </select>
        <small class="form-text">Zona horaria por defecto del sistema</small>
    </div>

    <!-- Current Time Display -->
    <div class="info-box" style="margin: 20px 0;">
        <i class="bi bi-clock-history"></i>
        <div>
            <strong>Hora actual en la zona seleccionada:</strong>
            <div id="current-time" style="font-size: 1.2rem; color: var(--iser-green); margin-top: 5px;">
                --:--:--
            </div>
        </div>
    </div>

    <!-- Locale -->
    <div class="form-group">
        <label>
            <i class="bi bi-translate"></i> Idioma por Defecto
            <span class="required">*</span>
        </label>
        <div class="locale-options">
            <label class="locale-card <?= $locale === 'es' ? 'selected' : '' ?>">
                <input type="radio" name="locale" value="es" <?= $locale === 'es' ? 'checked' : '' ?> required>
                <div class="locale-content">
                    <span class="locale-flag">🇪🇸</span>
                    <span class="locale-name">Español</span>
                    <small>Spanish (Colombia)</small>
                </div>
            </label>
            <label class="locale-card <?= $locale === 'en' ? 'selected' : '' ?>">
                <input type="radio" name="locale" value="en" <?= $locale === 'en' ? 'checked' : '' ?>>
                <div class="locale-content">
                    <span class="locale-flag">🇺🇸</span>
                    <span class="locale-name">English</span>
                    <small>English (United States)</small>
                </div>
            </label>
            <label class="locale-card <?= $locale === 'pt' ? 'selected' : '' ?>">
                <input type="radio" name="locale" value="pt" <?= $locale === 'pt' ? 'checked' : '' ?>>
                <div class="locale-content">
                    <span class="locale-flag">🇧🇷</span>
                    <span class="locale-name">Português</span>
                    <small>Portuguese (Brasil)</small>
                </div>
            </label>
        </div>
        <small class="form-text">Idioma predeterminado de la interfaz</small>
    </div>

</div>

<style>
.stage-container {
    max-width: 700px;
    margin: 0 auto;
}

.stage-header {
    text-align: center;
    margin-bottom: 40px;
}

.stage-header h2 {
    font-size: 2rem;
    color: var(--text-primary);
    margin: 20px 0 10px 0;
}

.subtitle {
    font-size: 1.1rem;
    color: var(--text-secondary);
}

.info-box {
    display: flex;
    align-items: start;
    gap: 15px;
    padding: 20px;
    background: var(--bg-light);
    border-left: 4px solid var(--iser-green);
    border-radius: 4px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.required {
    color: var(--iser-red);
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-color);
    border-radius: 4px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: var(--iser-green);
}

.form-text {
    display: block;
    margin-top: 6px;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-secondary {
    background: var(--text-secondary);
    color: white;
}

.btn-secondary:hover {
    background: var(--text-primary);
    transform: translateY(-2px);
}

.locale-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
}

.locale-card {
    position: relative;
    display: block;
    padding: 20px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: white;
}

.locale-card:hover {
    border-color: var(--iser-green);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.locale-card.selected,
.locale-card:has(input:checked) {
    border-color: var(--iser-green);
    background: rgba(27, 158, 136, 0.05);
}

.locale-card input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.locale-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.locale-flag {
    font-size: 3rem;
}

.locale-name {
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--text-primary);
}

.locale-card small {
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .locale-options {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function regenerateJWT() {
    // Generate new random JWT secret (64 characters hex)
    const array = new Uint8Array(32);
    crypto.getRandomValues(array);
    const hex = Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
    document.getElementById('jwt_secret').value = hex;
}

// Update current time based on selected timezone
function updateCurrentTime() {
    const timezone = document.getElementById('timezone').value;
    const now = new Date();

    try {
        const timeStr = now.toLocaleTimeString('es-CO', {
            timeZone: timezone,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });

        const dateStr = now.toLocaleDateString('es-CO', {
            timeZone: timezone,
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        document.getElementById('current-time').innerHTML =
            `<strong>${timeStr}</strong><br><small>${dateStr}</small>`;
    } catch (e) {
        document.getElementById('current-time').textContent = 'Error al obtener hora';
    }
}

// Update time on timezone change
document.addEventListener('DOMContentLoaded', function() {
    const timezoneSelect = document.getElementById('timezone');

    if (timezoneSelect) {
        timezoneSelect.addEventListener('change', updateCurrentTime);
        updateCurrentTime();

        // Update every second
        setInterval(updateCurrentTime, 1000);
    }

    // Update locale card selection
    document.querySelectorAll('.locale-card input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.locale-card').forEach(card => {
                card.classList.remove('selected');
            });
            if (this.checked) {
                this.closest('.locale-card').classList.add('selected');
            }
        });
    });
});
</script>
