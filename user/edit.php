<?php
/**
 * Edit User Profile
 *
 * Allows users to edit their own profile information.
 *
 * @package core
 * @subpackage user
 */

require_once('../lib/setup.php');

// Require login
require_login();

// Get current user
global $USER, $DB, $OUTPUT;

// Page setup
$PAGE->set_url('/user/edit');
$PAGE->set_title(get_string('editprofile', 'core'));
$PAGE->set_heading(get_string('editprofile', 'core'));
$PAGE->set_context(CONTEXT_SYSTEM);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_sesskey()) {
    $firstname = required_param('firstname', PARAM_TEXT);
    $lastname = required_param('lastname', PARAM_TEXT);
    $email = required_param('email', PARAM_EMAIL);

    // Validate email uniqueness (except for current user)
    $existing = $DB->get_record('users', ['email' => $email]);
    if ($existing && $existing->id != $USER->id) {
        $OUTPUT->notification(get_string('emailexists', 'core'), 'error');
    } else {
        // Update user
        $DB->update_record('users', [
            'id' => $USER->id,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'timemodified' => time(),
        ]);

        // Reload user
        $USER = $DB->get_record('users', ['id' => $USER->id]);

        $OUTPUT->notification(get_string('userupdated', 'core'), 'success');

        // Redirect to profile
        redirect('/user/profile');
    }
}

// Get user data
$userdata = $DB->get_record('users', ['id' => $USER->id], '*', MUST_EXIST);

// Render page
echo $OUTPUT->header();

// Show breadcrumbs
echo $OUTPUT->render_breadcrumbs();

?>

<div class="user-edit-form">
    <form method="post" action="/user/edit" class="nexo-form">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

        <div class="form-group">
            <label for="firstname"><?php echo get_string('firstname', 'core'); ?> <span class="required">*</span></label>
            <input type="text"
                   id="firstname"
                   name="firstname"
                   class="form-control"
                   value="<?php echo s($userdata->firstname); ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="lastname"><?php echo get_string('lastname', 'core'); ?> <span class="required">*</span></label>
            <input type="text"
                   id="lastname"
                   name="lastname"
                   class="form-control"
                   value="<?php echo s($userdata->lastname); ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="username"><?php echo get_string('username', 'core'); ?></label>
            <input type="text"
                   id="username"
                   name="username"
                   class="form-control"
                   value="<?php echo s($userdata->username); ?>"
                   disabled
                   title="<?php echo get_string('cannotchangeusername', 'core'); ?>">
            <small class="form-text text-muted"><?php echo get_string('usernamecannotbechanged', 'core'); ?></small>
        </div>

        <div class="form-group">
            <label for="email"><?php echo get_string('email', 'core'); ?> <span class="required">*</span></label>
            <input type="email"
                   id="email"
                   name="email"
                   class="form-control"
                   value="<?php echo s($userdata->email); ?>"
                   required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> <?php echo get_string('save', 'core'); ?>
            </button>
            <a href="/user/profile" class="btn btn-secondary">
                <i class="fa fa-times"></i> <?php echo get_string('cancel', 'core'); ?>
            </a>
        </div>
    </form>
</div>

<style>
.user-edit-form {
    max-width: 600px;
    margin: 20px auto;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group .required {
    color: #dc3545;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.form-control:focus {
    outline: none;
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.form-control:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
}

.form-text {
    display: block;
    margin-top: 5px;
    font-size: 12px;
}

.text-muted {
    color: #6c757d;
}

.form-actions {
    margin-top: 30px;
    display: flex;
    gap: 10px;
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

echo $OUTPUT->footer();
