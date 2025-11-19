<?php
/**
 * Debugging Settings - NexoSupport
 *
 * Configuration page for debugging and error display settings.
 * Similar to Moodle's debugging settings but adapted to NexoSupport's architecture.
 *
 * @package core
 * @subpackage admin
 */

define('NEXOSUPPORT_INTERNAL', 1);
require_once('../../lib/setup.php');

// Require login and admin access
require_login();
require_admin();

// Get current user
global $USER, $DB, $OUTPUT, $CFG;

// Page setup
$PAGE->set_url('/admin/settings/debugging');
$PAGE->set_title(get_string('debugging', 'core'));
$PAGE->set_heading(get_string('debugging', 'core'));
$PAGE->set_context(CONTEXT_SYSTEM);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_sesskey()) {
    $debug_level = required_param('debug', PARAM_INT);
    $debug_display = optional_param('debugdisplay', 0, PARAM_INT);

    // Validate debug level
    $valid_levels = [DEBUG_NONE, DEBUG_MINIMAL, DEBUG_NORMAL, DEBUG_DEVELOPER, DEBUG_ALL];
    if (!in_array($debug_level, $valid_levels)) {
        $OUTPUT->notification(get_string('invaliddebug level', 'core'), 'error');
    } else {
        // Save to database
        $DB->set_config('debug', $debug_level, 'core');
        $DB->set_config('debugdisplay', $debug_display, 'core');

        // Update $CFG immediately
        $CFG->debug = $debug_level;
        $CFG->debugdisplay = (bool)$debug_display;

        // Apply settings immediately
        if ($debug_level !== DEBUG_NONE) {
            error_reporting($debug_level);
            ini_set('display_errors', $debug_display ? '1' : '0');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        $OUTPUT->notification(get_string('configsaved', 'core'), 'success');

        // Redirect to avoid form resubmission
        redirect('/admin/settings/debugging');
    }
}

// Get current settings
$current_debug = get_config('debug', 'core') ?? DEBUG_NONE;
$current_debugdisplay = get_config('debugdisplay', 'core') ?? 0;

// Prepare context for template
$context = [
    'sesskey' => sesskey(),
    'current_debug' => (int)$current_debug,
    'current_debugdisplay' => (int)$current_debugdisplay,
    'debug_none' => DEBUG_NONE,
    'debug_minimal' => DEBUG_MINIMAL,
    'debug_normal' => DEBUG_NORMAL,
    'debug_developer' => DEBUG_DEVELOPER,
    'debug_all' => DEBUG_ALL,
    'debug_none_selected' => ($current_debug == DEBUG_NONE),
    'debug_minimal_selected' => ($current_debug == DEBUG_MINIMAL),
    'debug_normal_selected' => ($current_debug == DEBUG_NORMAL),
    'debug_developer_selected' => ($current_debug == DEBUG_DEVELOPER),
    'debug_all_selected' => ($current_debug == DEBUG_ALL),
    'debugdisplay_checked' => ($current_debugdisplay == 1),
];

// Render page
echo $OUTPUT->header();

?>

<div class="debugging-settings">
    <div class="warning-box">
        <i class="fa fa-exclamation-triangle"></i>
        <strong><?php echo get_string('warning', 'core'); ?>:</strong>
        <?php echo get_string('debuggingwarning', 'core'); ?>
    </div>

    <form method="post" action="/admin/settings/debugging" class="nexo-form">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

        <!-- Debug Level -->
        <div class="form-section">
            <h3><i class="fa fa-bug"></i> <?php echo get_string('debuglevel', 'core'); ?></h3>
            <p class="description"><?php echo get_string('debuglevel_desc', 'core'); ?></p>

            <div class="debug-options">
                <div class="option-item">
                    <input type="radio"
                           id="debug_none"
                           name="debug"
                           value="<?php echo DEBUG_NONE; ?>"
                           <?php echo $context['debug_none_selected'] ? 'checked' : ''; ?>>
                    <label for="debug_none">
                        <strong><?php echo get_string('debugnone', 'core'); ?></strong>
                        <span class="option-desc"><?php echo get_string('debugnone_desc', 'core'); ?></span>
                        <span class="badge badge-success"><?php echo get_string('recommended_production', 'core'); ?></span>
                    </label>
                </div>

                <div class="option-item">
                    <input type="radio"
                           id="debug_minimal"
                           name="debug"
                           value="<?php echo DEBUG_MINIMAL; ?>"
                           <?php echo $context['debug_minimal_selected'] ? 'checked' : ''; ?>>
                    <label for="debug_minimal">
                        <strong><?php echo get_string('debugminimal', 'core'); ?></strong>
                        <span class="option-desc"><?php echo get_string('debugminimal_desc', 'core'); ?></span>
                    </label>
                </div>

                <div class="option-item">
                    <input type="radio"
                           id="debug_normal"
                           name="debug"
                           value="<?php echo DEBUG_NORMAL; ?>"
                           <?php echo $context['debug_normal_selected'] ? 'checked' : ''; ?>>
                    <label for="debug_normal">
                        <strong><?php echo get_string('debugnormal', 'core'); ?></strong>
                        <span class="option-desc"><?php echo get_string('debugnormal_desc', 'core'); ?></span>
                    </label>
                </div>

                <div class="option-item">
                    <input type="radio"
                           id="debug_developer"
                           name="debug"
                           value="<?php echo DEBUG_DEVELOPER; ?>"
                           <?php echo $context['debug_developer_selected'] ? 'checked' : ''; ?>>
                    <label for="debug_developer">
                        <strong><?php echo get_string('debugdeveloper', 'core'); ?></strong>
                        <span class="option-desc"><?php echo get_string('debugdeveloper_desc', 'core'); ?></span>
                        <span class="badge badge-warning"><?php echo get_string('developer_only', 'core'); ?></span>
                    </label>
                </div>

                <div class="option-item">
                    <input type="radio"
                           id="debug_all"
                           name="debug"
                           value="<?php echo DEBUG_ALL; ?>"
                           <?php echo $context['debug_all_selected'] ? 'checked' : ''; ?>>
                    <label for="debug_all">
                        <strong><?php echo get_string('debugall', 'core'); ?></strong>
                        <span class="option-desc"><?php echo get_string('debugall_desc', 'core'); ?></span>
                        <span class="badge badge-danger"><?php echo get_string('experts_only', 'core'); ?></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Debug Display -->
        <div class="form-section">
            <h3><i class="fa fa-desktop"></i> <?php echo get_string('debugdisplay', 'core'); ?></h3>
            <p class="description"><?php echo get_string('debugdisplay_desc', 'core'); ?></p>

            <div class="checkbox-item">
                <input type="checkbox"
                       id="debugdisplay"
                       name="debugdisplay"
                       value="1"
                       <?php echo $context['debugdisplay_checked'] ? 'checked' : ''; ?>>
                <label for="debugdisplay">
                    <strong><?php echo get_string('displayerrors', 'core'); ?></strong>
                    <span class="help-text"><?php echo get_string('displayerrors_help', 'core'); ?></span>
                </label>
            </div>
        </div>

        <!-- Performance Notice -->
        <div class="info-box">
            <i class="fa fa-info-circle"></i>
            <strong><?php echo get_string('performancenotice', 'core'); ?>:</strong>
            <?php echo get_string('debugperformancewarning', 'core'); ?>
        </div>

        <!-- Current Status -->
        <div class="status-box">
            <h4><?php echo get_string('currentsettings', 'core'); ?>:</h4>
            <ul>
                <li>
                    <strong><?php echo get_string('debuglevel', 'core'); ?>:</strong>
                    <?php
                    $level_name = 'NONE';
                    if ($current_debug == DEBUG_MINIMAL) $level_name = 'MINIMAL';
                    elseif ($current_debug == DEBUG_NORMAL) $level_name = 'NORMAL';
                    elseif ($current_debug == DEBUG_DEVELOPER) $level_name = 'DEVELOPER';
                    elseif ($current_debug == DEBUG_ALL) $level_name = 'ALL';
                    echo $level_name . ' (' . $current_debug . ')';
                    ?>
                </li>
                <li>
                    <strong><?php echo get_string('debugdisplay', 'core'); ?>:</strong>
                    <?php echo $current_debugdisplay ? get_string('enabled', 'core') : get_string('disabled', 'core'); ?>
                </li>
                <li>
                    <strong>PHP error_reporting:</strong>
                    <?php echo error_reporting(); ?>
                </li>
                <li>
                    <strong>PHP display_errors:</strong>
                    <?php echo ini_get('display_errors') ? 'On' : 'Off'; ?>
                </li>
            </ul>
        </div>

        <!-- Submit Button -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> <?php echo get_string('save', 'core'); ?>
            </button>
            <a href="/admin" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> <?php echo get_string('back', 'core'); ?>
            </a>
        </div>
    </form>
</div>

<style>
.debugging-settings {
    max-width: 900px;
    margin: 20px auto;
}

.warning-box {
    background: #fff3cd;
    border: 1px solid #ffecb5;
    border-left: 4px solid #ffc107;
    padding: 15px;
    margin-bottom: 30px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.warning-box i {
    color: #f57c00;
    font-size: 20px;
}

.info-box {
    background: #e3f2fd;
    border: 1px solid #bbdefb;
    border-left: 4px solid #2196f3;
    padding: 15px;
    margin: 20px 0;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-box i {
    color: #1976d2;
    font-size: 20px;
}

.status-box {
    background: #f5f5f5;
    border: 1px solid #ddd;
    padding: 15px;
    margin: 20px 0;
    border-radius: 4px;
}

.status-box h4 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
}

.status-box ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.status-box li {
    padding: 8px 0;
    border-bottom: 1px solid #ddd;
}

.status-box li:last-child {
    border-bottom: none;
}

.form-section {
    background: white;
    border: 1px solid #ddd;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
}

.form-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section .description {
    margin-bottom: 20px;
    color: #666;
    font-size: 14px;
}

.debug-options {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.option-item {
    border: 2px solid #e0e0e0;
    padding: 15px;
    border-radius: 8px;
    transition: all 0.2s;
}

.option-item:has(input:checked) {
    border-color: #667eea;
    background: #f8f9ff;
}

.option-item input[type="radio"] {
    margin-right: 12px;
}

.option-item label {
    display: flex;
    flex-direction: column;
    gap: 5px;
    cursor: pointer;
    margin: 0;
    flex: 1;
}

.option-item label strong {
    font-size: 16px;
    color: #333;
}

.option-desc {
    font-size: 14px;
    color: #666;
    line-height: 1.5;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 8px;
    align-self: flex-start;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.checkbox-item {
    padding: 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
}

.checkbox-item input[type="checkbox"] {
    margin-right: 12px;
}

.checkbox-item label {
    display: flex;
    flex-direction: column;
    gap: 5px;
    cursor: pointer;
    margin: 0;
}

.checkbox-item .help-text {
    font-size: 13px;
    color: #666;
    margin-left: 28px;
}

.form-actions {
    margin-top: 30px;
    display: flex;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-primary {
    background-color: #667eea;
    color: white;
}

.btn-primary:hover {
    background-color: #5568d3;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #545b62;
}
</style>

<?php

echo $OUTPUT->footer();
