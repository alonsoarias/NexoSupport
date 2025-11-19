<?php
/**
 * Notification Preferences
 *
 * Allows users to configure their notification preferences.
 *
 * @package core
 * @subpackage user
 */

require_once('../../lib/setup.php');

// Require login
require_login();

// Get current user
global $USER, $DB, $OUTPUT;

// Page setup
$PAGE->set_url('/user/preferences/notification');
$PAGE->set_title(get_string('notificationpreferences', 'core'));
$PAGE->set_heading(get_string('notificationpreferences', 'core'));
$PAGE->set_context(CONTEXT_SYSTEM);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_sesskey()) {
    // Get preferences
    $email_notifications = optional_param('email_notifications', 0, PARAM_INT);
    $digest_type = optional_param('digest_type', 'none', PARAM_ALPHA);

    // Save preferences
    set_user_preference('email_notifications', $email_notifications);
    set_user_preference('digest_type', $digest_type);

    $OUTPUT->notification(get_string('preferencessaved', 'core'), 'success');
}

// Get current preferences
$email_notifications = get_user_preference('email_notifications', 1);
$digest_type = get_user_preference('digest_type', 'none');

// Render page
echo $OUTPUT->header();

// Show breadcrumbs
echo $OUTPUT->render_breadcrumbs();

?>

<div class="notification-preferences">
    <p class="description">
        <?php echo get_string('notificationpreferencesdesc', 'core'); ?>
    </p>

    <form method="post" action="/user/preferences/notification" class="nexo-form">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

        <div class="preference-section">
            <h3><i class="fa fa-envelope"></i> <?php echo get_string('emailnotifications', 'core'); ?></h3>

            <div class="form-check">
                <input type="checkbox"
                       id="email_notifications"
                       name="email_notifications"
                       value="1"
                       <?php echo $email_notifications ? 'checked' : ''; ?>>
                <label for="email_notifications">
                    <?php echo get_string('receivemailnotifications', 'core'); ?>
                </label>
                <small class="help-text">
                    <?php echo get_string('receivemailnotificationshelp', 'core'); ?>
                </small>
            </div>
        </div>

        <div class="preference-section">
            <h3><i class="fa fa-list-ul"></i> <?php echo get_string('digesttype', 'core'); ?></h3>

            <div class="form-radio">
                <input type="radio"
                       id="digest_none"
                       name="digest_type"
                       value="none"
                       <?php echo $digest_type === 'none' ? 'checked' : ''; ?>>
                <label for="digest_none">
                    <?php echo get_string('nodigest', 'core'); ?>
                </label>
                <small class="help-text">
                    <?php echo get_string('nodigesthelp', 'core'); ?>
                </small>
            </div>

            <div class="form-radio">
                <input type="radio"
                       id="digest_daily"
                       name="digest_type"
                       value="daily"
                       <?php echo $digest_type === 'daily' ? 'checked' : ''; ?>>
                <label for="digest_daily">
                    <?php echo get_string('dailydigest', 'core'); ?>
                </label>
                <small class="help-text">
                    <?php echo get_string('dailydigesthelp', 'core'); ?>
                </small>
            </div>

            <div class="form-radio">
                <input type="radio"
                       id="digest_weekly"
                       name="digest_type"
                       value="weekly"
                       <?php echo $digest_type === 'weekly' ? 'checked' : ''; ?>>
                <label for="digest_weekly">
                    <?php echo get_string('weeklydigest', 'core'); ?>
                </label>
                <small class="help-text">
                    <?php echo get_string('weeklydigesthelp', 'core'); ?>
                </small>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> <?php echo get_string('save', 'core'); ?>
            </button>
            <a href="/user/profile" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> <?php echo get_string('back', 'core'); ?>
            </a>
        </div>
    </form>
</div>

<style>
.notification-preferences {
    max-width: 800px;
    margin: 20px auto;
}

.description {
    margin-bottom: 30px;
    padding: 15px;
    background: #f8f9fa;
    border-left: 4px solid #007bff;
    border-radius: 4px;
}

.preference-section {
    margin-bottom: 40px;
    padding: 20px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}

.preference-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 18px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-check,
.form-radio {
    margin-bottom: 20px;
    padding-left: 0;
}

.form-check input[type="checkbox"],
.form-radio input[type="radio"] {
    margin-right: 8px;
}

.form-check label,
.form-radio label {
    font-weight: 500;
    cursor: pointer;
}

.help-text {
    display: block;
    margin-left: 24px;
    margin-top: 5px;
    font-size: 13px;
    color: #6c757d;
}

.form-actions {
    margin-top: 30px;
    display: flex;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
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

// Missing strings - add to language files later
if (!isset($string['notificationpreferencesdesc'])) {
    echo '<script>console.log("Missing language strings - will be added in language files");</script>';
}

echo $OUTPUT->footer();
