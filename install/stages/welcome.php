<?php
/**
 * Installation Stage 1: Welcome
 * Language selection and introduction to NexoSupport installer
 */

// Default language
$_SESSION['install_lang'] = $_SESSION['install_lang'] ?? 'es';

// Language selection handling
if (isset($_POST['install_lang'])) {
    $_SESSION['install_lang'] = $_POST['install_lang'];
}

$lang = $_SESSION['install_lang'];

// Multi-language content
$content = [
    'es' => [
        'title' => 'Bienvenido al Instalador de NexoSupport',
        'subtitle' => 'Sistema de Soporte y Gestión Completo',
        'intro' => 'Este asistente lo guiará a través de 11 etapas para configurar completamente su instalación de NexoSupport.',
        'features_title' => 'Características del Instalador',
        'features' => [
            'Verificación automática de requisitos del sistema',
            'Configuración completa de base de datos',
            'Creación de usuario administrador',
            'Configuración avanzada de seguridad (JWT, rate limiting)',
            'Sistema de logging y rotación de archivos',
            'Integración de email (SMTP, Mailgun, Postmark)',
            'Gestión de caché y almacenamiento',
            'Configuración regional y de zona horaria',
            'Generación automática de archivo .env',
            'Verificación final de instalación',
        ],
        'requirements_title' => 'Requisitos Previos',
        'requirements' => [
            'PHP 8.1 o superior',
            'MySQL 5.7+ / PostgreSQL 12+ / SQLite 3.8+',
            'Extensiones: PDO, JSON, mbstring, openssl, session',
            'Permisos de escritura en directorio raíz',
            'Al menos 128MB de memoria PHP',
        ],
        'terms_label' => 'He leído y acepto los términos y condiciones',
        'terms_error' => 'Debe aceptar los términos y condiciones',
        'lang_label' => 'Idioma del Instalador',
        'next_btn' => 'Comenzar Instalación',
        'time_estimate' => 'Tiempo estimado: 10-15 minutos',
    ],
    'en' => [
        'title' => 'Welcome to NexoSupport Installer',
        'subtitle' => 'Complete Support and Management System',
        'intro' => 'This wizard will guide you through 11 stages to fully configure your NexoSupport installation.',
        'features_title' => 'Installer Features',
        'features' => [
            'Automatic system requirements verification',
            'Complete database configuration',
            'Administrator user creation',
            'Advanced security configuration (JWT, rate limiting)',
            'Logging system and file rotation',
            'Email integration (SMTP, Mailgun, Postmark)',
            'Cache and storage management',
            'Regional and timezone configuration',
            'Automatic .env file generation',
            'Final installation verification',
        ],
        'requirements_title' => 'Prerequisites',
        'requirements' => [
            'PHP 8.1 or higher',
            'MySQL 5.7+ / PostgreSQL 12+ / SQLite 3.8+',
            'Extensions: PDO, JSON, mbstring, openssl, session',
            'Write permissions in root directory',
            'At least 128MB PHP memory',
        ],
        'terms_label' => 'I have read and accept the terms and conditions',
        'terms_error' => 'You must accept the terms and conditions',
        'lang_label' => 'Installer Language',
        'next_btn' => 'Start Installation',
        'time_estimate' => 'Estimated time: 10-15 minutes',
    ],
    'pt' => [
        'title' => 'Bem-vindo ao Instalador NexoSupport',
        'subtitle' => 'Sistema Completo de Suporte e Gestão',
        'intro' => 'Este assistente irá guiá-lo através de 11 etapas para configurar completamente sua instalação do NexoSupport.',
        'features_title' => 'Recursos do Instalador',
        'features' => [
            'Verificação automática de requisitos do sistema',
            'Configuração completa do banco de dados',
            'Criação de usuário administrador',
            'Configuração avançada de segurança (JWT, rate limiting)',
            'Sistema de logging e rotação de arquivos',
            'Integração de email (SMTP, Mailgun, Postmark)',
            'Gerenciamento de cache e armazenamento',
            'Configuração regional e de fuso horário',
            'Geração automática de arquivo .env',
            'Verificação final de instalação',
        ],
        'requirements_title' => 'Pré-requisitos',
        'requirements' => [
            'PHP 8.1 ou superior',
            'MySQL 5.7+ / PostgreSQL 12+ / SQLite 3.8+',
            'Extensões: PDO, JSON, mbstring, openssl, session',
            'Permissões de escrita no diretório raiz',
            'Pelo menos 128MB de memória PHP',
        ],
        'terms_label' => 'Li e aceito os termos e condições',
        'terms_error' => 'Você deve aceitar os termos e condições',
        'lang_label' => 'Idioma do Instalador',
        'next_btn' => 'Começar Instalação',
        'time_estimate' => 'Tempo estimado: 10-15 minutos',
    ],
];

$t = $content[$lang];
?>

<div class="stage-container">
    <div class="stage-header">
        <i class="bi bi-rocket-takeoff" style="font-size: 3rem; color: var(--iser-green);"></i>
        <h2><?= htmlspecialchars($t['title']) ?></h2>
        <p class="subtitle"><?= htmlspecialchars($t['subtitle']) ?></p>
    </div>

    <!-- Language Selector -->
    <div class="language-selector" style="margin-bottom: 40px; text-align: center;">
        <label style="display: block; margin-bottom: 10px; font-weight: 600;">
            <i class="bi bi-globe"></i> <?= htmlspecialchars($t['lang_label']) ?>
        </label>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <button type="button" class="lang-btn <?= $lang === 'es' ? 'active' : '' ?>"
                    onclick="selectLanguage('es')">
                <span style="font-size: 2rem;">🇪🇸</span>
                <span>Español</span>
            </button>
            <button type="button" class="lang-btn <?= $lang === 'en' ? 'active' : '' ?>"
                    onclick="selectLanguage('en')">
                <span style="font-size: 2rem;">🇺🇸</span>
                <span>English</span>
            </button>
            <button type="button" class="lang-btn <?= $lang === 'pt' ? 'active' : '' ?>"
                    onclick="selectLanguage('pt')">
                <span style="font-size: 2rem;">🇧🇷</span>
                <span>Português</span>
            </button>
        </div>
        <input type="hidden" name="install_lang" id="install_lang" value="<?= htmlspecialchars($lang) ?>">
    </div>

    <!-- Introduction -->
    <div class="intro-box">
        <p style="font-size: 1.1rem; line-height: 1.6; text-align: center; margin: 0;">
            <?= htmlspecialchars($t['intro']) ?>
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; margin: 40px 0;">
        <!-- Features -->
        <div class="info-card">
            <h3>
                <i class="bi bi-star" style="color: var(--iser-yellow);"></i>
                <?= htmlspecialchars($t['features_title']) ?>
            </h3>
            <ul class="feature-list">
                <?php foreach ($t['features'] as $feature): ?>
                    <li>
                        <i class="bi bi-check-circle-fill" style="color: var(--iser-green);"></i>
                        <?= htmlspecialchars($feature) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Requirements -->
        <div class="info-card">
            <h3>
                <i class="bi bi-gear" style="color: var(--iser-green);"></i>
                <?= htmlspecialchars($t['requirements_title']) ?>
            </h3>
            <ul class="feature-list">
                <?php foreach ($t['requirements'] as $requirement): ?>
                    <li>
                        <i class="bi bi-chevron-right" style="color: var(--text-secondary);"></i>
                        <?= htmlspecialchars($requirement) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Terms and Conditions -->
    <div class="terms-box">
        <label class="checkbox-label">
            <input type="checkbox" name="accept_terms" id="accept_terms" required>
            <span><?= htmlspecialchars($t['terms_label']) ?></span>
        </label>
        <p style="margin-top: 20px; text-align: center; color: var(--text-secondary);">
            <i class="bi bi-clock"></i> <?= htmlspecialchars($t['time_estimate']) ?>
        </p>
    </div>
</div>

<style>
.stage-container {
    max-width: 900px;
    margin: 0 auto;
}

.stage-header {
    text-align: center;
    margin-bottom: 40px;
}

.stage-header h2 {
    font-size: 2.2rem;
    color: var(--text-primary);
    margin: 20px 0 10px 0;
}

.subtitle {
    font-size: 1.2rem;
    color: var(--text-secondary);
    margin: 0;
}

.lang-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 15px 25px;
    border: 2px solid var(--border-color);
    background: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 110px;
}

.lang-btn:hover {
    border-color: var(--iser-green);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.lang-btn.active {
    border-color: var(--iser-green);
    background: var(--iser-green);
    color: white;
    box-shadow: 0 4px 12px rgba(27, 158, 136, 0.3);
}

.intro-box {
    background: linear-gradient(135deg, var(--iser-green) 0%, var(--iser-green-dark) 100%);
    color: white;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 4px 20px rgba(27, 158, 136, 0.2);
}

.info-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    border: 2px solid var(--border-color);
}

.info-card h3 {
    font-size: 1.4rem;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.feature-list li {
    display: flex;
    align-items: start;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid var(--bg-light);
}

.feature-list li:last-child {
    border-bottom: none;
}

.feature-list i {
    margin-top: 3px;
    flex-shrink: 0;
}

.terms-box {
    background: var(--bg-light);
    padding: 30px;
    border-radius: 8px;
    border-left: 4px solid var(--iser-green);
    margin-top: 40px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.1rem;
    cursor: pointer;
    justify-content: center;
}

.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.checkbox-label span {
    user-select: none;
}

@media (max-width: 768px) {
    .stage-header h2 {
        font-size: 1.8rem;
    }

    .info-card {
        padding: 20px;
    }
}
</style>

<script>
function selectLanguage(lang) {
    // Update hidden input
    document.getElementById('install_lang').value = lang;

    // Update active button
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.closest('.lang-btn').classList.add('active');

    // Submit form to reload with new language
    const form = document.getElementById('install-form');
    if (form) {
        form.submit();
    }
}

// Validate terms acceptance before proceeding
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('install-form');
    const acceptTerms = document.getElementById('accept_terms');
    const nextBtn = document.querySelector('button[name="next"]');

    if (form && acceptTerms && nextBtn) {
        form.addEventListener('submit', function(e) {
            if (e.submitter === nextBtn && !acceptTerms.checked) {
                e.preventDefault();
                alert('<?= addslashes($t['terms_error']) ?>');
                acceptTerms.focus();
            }
        });
    }
});
</script>
