<?php
/**
 * Stage 10: Regional Configuration
 */

// Obtener valores guardados o por defecto
$timezone = $_SESSION['regional_timezone'] ?? 'America/Bogota';
$locale = $_SESSION['regional_locale'] ?? 'es';
$date_format = $_SESSION['regional_date_format'] ?? 'Y-m-d';
$time_format = $_SESSION['regional_time_format'] ?? 'H:i:s';
$number_format_decimals = $_SESSION['regional_number_decimals'] ?? 2;
$number_format_decimal_sep = $_SESSION['regional_number_decimal_sep'] ?? ',';
$number_format_thousands_sep = $_SESSION['regional_number_thousands_sep'] ?? '.';
$currency = $_SESSION['regional_currency'] ?? 'COP';
$currency_symbol = $_SESSION['regional_currency_symbol'] ?? '$';

// Zonas horarias comunes
$common_timezones = [
    'America/Bogota' => 'Bogotá (COT, UTC-5)',
    'America/Mexico_City' => 'Ciudad de México (CST, UTC-6)',
    'America/Lima' => 'Lima (PET, UTC-5)',
    'America/Santiago' => 'Santiago (CLT, UTC-4/UTC-3)',
    'America/Buenos_Aires' => 'Buenos Aires (ART, UTC-3)',
    'America/Sao_Paulo' => 'São Paulo (BRT, UTC-3)',
    'America/Caracas' => 'Caracas (VET, UTC-4)',
    'America/Panama' => 'Panamá (EST, UTC-5)',
    'America/Costa_Rica' => 'Costa Rica (CST, UTC-6)',
    'America/Guatemala' => 'Guatemala (CST, UTC-6)',
    'America/New_York' => 'Nueva York (EST, UTC-5/UTC-4)',
    'America/Los_Angeles' => 'Los Ángeles (PST, UTC-8/UTC-7)',
    'Europe/Madrid' => 'Madrid (CET, UTC+1/UTC+2)',
    'UTC' => 'UTC (Tiempo Universal Coordinado)'
];
?>

<h3 class="mb-4"><i class="bi bi-globe"></i> Configuración Regional</h3>

<p class="text-muted">Configure las opciones regionales, zona horaria y formato de datos.</p>

<form method="POST" id="regionalForm">
    <input type="hidden" name="stage" value="<?= STAGE_REGIONAL ?>">

    <!-- Timezone -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-clock"></i> Zona Horaria</h5>

        <div class="mb-3">
            <label class="form-label">Zona Horaria del Sistema *</label>
            <select name="regional_timezone" class="form-select" required>
                <optgroup label="Zonas Horarias Comunes">
                    <?php foreach ($common_timezones as $tz => $label): ?>
                        <option value="<?= $tz ?>" <?= $timezone === $tz ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
            <small class="text-muted">Zona horaria predeterminada para el sistema</small>
        </div>

        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            Hora actual del sistema:
            <strong id="currentTime"><?= date('Y-m-d H:i:s') ?></strong>
        </div>
    </div>

    <!-- Locale -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-translate"></i> Idioma y Localización</h5>

        <div class="mb-3">
            <label class="form-label">Idioma Predeterminado *</label>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card h-100 locale-card <?= $locale === 'es' ? 'border-primary' : '' ?>">
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="regional_locale"
                                       id="locale_es" value="es" <?= $locale === 'es' ? 'checked' : '' ?> required>
                                <label class="form-check-label w-100" for="locale_es">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="fs-3 me-2">🇪🇸</span>
                                        <strong>Español</strong>
                                    </div>
                                    <small class="text-muted">Spanish - Colombia</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 locale-card <?= $locale === 'en' ? 'border-primary' : '' ?>">
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="regional_locale"
                                       id="locale_en" value="en" <?= $locale === 'en' ? 'checked' : '' ?> required>
                                <label class="form-check-label w-100" for="locale_en">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="fs-3 me-2">🇺🇸</span>
                                        <strong>English</strong>
                                    </div>
                                    <small class="text-muted">English - United States</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 locale-card <?= $locale === 'pt' ? 'border-primary' : '' ?>">
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="regional_locale"
                                       id="locale_pt" value="pt" <?= $locale === 'pt' ? 'checked' : '' ?> required>
                                <label class="form-check-label w-100" for="locale_pt">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="fs-3 me-2">🇧🇷</span>
                                        <strong>Português</strong>
                                    </div>
                                    <small class="text-muted">Portuguese - Brasil</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Date and Time Format -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-calendar3"></i> Formato de Fecha y Hora</h5>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Formato de Fecha *</label>
                <select name="regional_date_format" class="form-select" id="dateFormat" required>
                    <option value="Y-m-d" <?= $date_format === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD (2025-01-15)</option>
                    <option value="d/m/Y" <?= $date_format === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY (15/01/2025)</option>
                    <option value="m/d/Y" <?= $date_format === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY (01/15/2025)</option>
                    <option value="d-m-Y" <?= $date_format === 'd-m-Y' ? 'selected' : '' ?>>DD-MM-YYYY (15-01-2025)</option>
                </select>
                <small class="text-muted">Vista previa: <span id="datePreview"></span></small>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Formato de Hora *</label>
                <select name="regional_time_format" class="form-select" id="timeFormat" required>
                    <option value="H:i:s" <?= $time_format === 'H:i:s' ? 'selected' : '' ?>>24 horas (14:30:00)</option>
                    <option value="h:i:s A" <?= $time_format === 'h:i:s A' ? 'selected' : '' ?>>12 horas (02:30:00 PM)</option>
                    <option value="H:i" <?= $time_format === 'H:i' ? 'selected' : '' ?>>24 horas sin segundos (14:30)</option>
                    <option value="h:i A" <?= $time_format === 'h:i A' ? 'selected' : '' ?>>12 horas sin segundos (02:30 PM)</option>
                </select>
                <small class="text-muted">Vista previa: <span id="timePreview"></span></small>
            </div>
        </div>
    </div>

    <!-- Number Format -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-123"></i> Formato de Números</h5>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Decimales *</label>
                <input type="number" name="regional_number_decimals" class="form-control"
                       value="<?= $number_format_decimals ?>" min="0" max="4" required id="numDecimals">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Separador Decimal *</label>
                <select name="regional_number_decimal_sep" class="form-select" required id="decimalSep">
                    <option value="," <?= $number_format_decimal_sep === ',' ? 'selected' : '' ?>>Coma (,)</option>
                    <option value="." <?= $number_format_decimal_sep === '.' ? 'selected' : '' ?>>Punto (.)</option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Separador de Miles *</label>
                <select name="regional_number_thousands_sep" class="form-select" required id="thousandsSep">
                    <option value="." <?= $number_format_thousands_sep === '.' ? 'selected' : '' ?>>Punto (.)</option>
                    <option value="," <?= $number_format_thousands_sep === ',' ? 'selected' : '' ?>>Coma (,)</option>
                    <option value=" " <?= $number_format_thousands_sep === ' ' ? 'selected' : '' ?>>Espacio ( )</option>
                    <option value="" <?= $number_format_thousands_sep === '' ? 'selected' : '' ?>>Ninguno</option>
                </select>
            </div>
        </div>

        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            Vista previa: <strong id="numberPreview">1.234,56</strong>
        </div>
    </div>

    <!-- Currency -->
    <div class="mb-4">
        <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-currency-exchange"></i> Moneda (Opcional)</h5>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Código de Moneda</label>
                <select name="regional_currency" class="form-select" id="currencyCode">
                    <option value="">Sin configurar</option>
                    <option value="COP" <?= $currency === 'COP' ? 'selected' : '' ?>>COP - Peso Colombiano</option>
                    <option value="USD" <?= $currency === 'USD' ? 'selected' : '' ?>>USD - Dólar Estadounidense</option>
                    <option value="EUR" <?= $currency === 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                    <option value="MXN" <?= $currency === 'MXN' ? 'selected' : '' ?>>MXN - Peso Mexicano</option>
                    <option value="ARS" <?= $currency === 'ARS' ? 'selected' : '' ?>>ARS - Peso Argentino</option>
                    <option value="BRL" <?= $currency === 'BRL' ? 'selected' : '' ?>>BRL - Real Brasileño</option>
                    <option value="CLP" <?= $currency === 'CLP' ? 'selected' : '' ?>>CLP - Peso Chileno</option>
                    <option value="PEN" <?= $currency === 'PEN' ? 'selected' : '' ?>>PEN - Sol Peruano</option>
                </select>
                <small class="text-muted">Código ISO de la moneda</small>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Símbolo de Moneda</label>
                <input type="text" name="regional_currency_symbol" class="form-control"
                       value="<?= htmlspecialchars($currency_symbol) ?>" placeholder="$" maxlength="5">
                <small class="text-muted">Símbolo visual de la moneda</small>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <button type="submit" name="previous" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Anterior
        </button>
        <button type="submit" name="next" class="btn btn-primary">
            Siguiente <i class="bi bi-arrow-right"></i>
        </button>
    </div>
</form>

<script>
// Actualizar vista previa de fecha
function updateDatePreview() {
    const format = document.getElementById('dateFormat').value;
    const now = new Date();
    let preview = '';

    switch(format) {
        case 'Y-m-d':
            preview = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(now.getDate()).padStart(2,'0')}`;
            break;
        case 'd/m/Y':
            preview = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()}`;
            break;
        case 'm/d/Y':
            preview = `${String(now.getMonth()+1).padStart(2,'0')}/${String(now.getDate()).padStart(2,'0')}/${now.getFullYear()}`;
            break;
        case 'd-m-Y':
            preview = `${String(now.getDate()).padStart(2,'0')}-${String(now.getMonth()+1).padStart(2,'0')}-${now.getFullYear()}`;
            break;
    }

    document.getElementById('datePreview').textContent = preview;
}

// Actualizar vista previa de hora
function updateTimePreview() {
    const format = document.getElementById('timeFormat').value;
    const now = new Date();
    let preview = '';

    const hours = now.getHours();
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');

    switch(format) {
        case 'H:i:s':
            preview = `${String(hours).padStart(2,'0')}:${minutes}:${seconds}`;
            break;
        case 'h:i:s A':
            const h12 = hours % 12 || 12;
            const ampm = hours >= 12 ? 'PM' : 'AM';
            preview = `${String(h12).padStart(2,'0')}:${minutes}:${seconds} ${ampm}`;
            break;
        case 'H:i':
            preview = `${String(hours).padStart(2,'0')}:${minutes}`;
            break;
        case 'h:i A':
            const h12b = hours % 12 || 12;
            const ampmb = hours >= 12 ? 'PM' : 'AM';
            preview = `${String(h12b).padStart(2,'0')}:${minutes} ${ampmb}`;
            break;
    }

    document.getElementById('timePreview').textContent = preview;
}

// Actualizar vista previa de números
function updateNumberPreview() {
    const decimals = parseInt(document.getElementById('numDecimals').value) || 0;
    const decimalSep = document.getElementById('decimalSep').value;
    const thousandsSep = document.getElementById('thousandsSep').value;

    let preview = '1234';
    if (thousandsSep) {
        preview = '1' + thousandsSep + '234';
    }

    if (decimals > 0) {
        preview += decimalSep + '56'.padEnd(decimals, '0');
    }

    document.getElementById('numberPreview').textContent = preview;
}

// Event listeners
document.getElementById('dateFormat').addEventListener('change', updateDatePreview);
document.getElementById('timeFormat').addEventListener('change', updateTimePreview);
document.getElementById('numDecimals').addEventListener('input', updateNumberPreview);
document.getElementById('decimalSep').addEventListener('change', updateNumberPreview);
document.getElementById('thousandsSep').addEventListener('change', updateNumberPreview);

// Actualizar border de las cards de locale
document.querySelectorAll('input[name="regional_locale"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.locale-card').forEach(card => {
            card.classList.remove('border-primary');
        });
        this.closest('.locale-card').classList.add('border-primary');
    });
});

// Actualizar hora actual cada segundo
setInterval(() => {
    const now = new Date();
    document.getElementById('currentTime').textContent =
        now.toISOString().slice(0, 19).replace('T', ' ');
}, 1000);

// Inicializar vistas previas
updateDatePreview();
updateTimePreview();
updateNumberPreview();
</script>

<style>
.locale-card {
    cursor: pointer;
    transition: all 0.2s;
}

.locale-card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.locale-card.border-primary {
    box-shadow: 0 0 0 0.2rem rgba(27, 158, 136, 0.25);
    border-color: var(--iser-green) !important;
}

.alert-info {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.border-bottom {
    border-color: var(--border-color) !important;
}
</style>
