<?php
/**
 * NexoSupport - Theme ISER - Admin Layout
 *
 * @package    theme_iser
 * @copyright  2024 ISER
 * @license    Proprietary
 */

defined('NEXOSUPPORT_INTERNAL') || die();

$renderer = $PAGE->get_renderer('theme_iser');

echo $OUTPUT->doctype();
?>
<html lang="es">
<head>
    <?php echo $OUTPUT->standard_head_html(); ?>
    <link rel="stylesheet" href="/theme/iser/scss/iser.css">
    <link rel="stylesheet" href="/theme/iser/scss/admin.css">
</head>
<body <?php echo $OUTPUT->body_attributes(['admin']); ?>>
    <?php echo $OUTPUT->standard_top_of_body_html(); ?>

    <?php echo $renderer->header(); ?>
    <?php echo $renderer->navbar(); ?>

    <div id="page" class="container-fluid admin-page">
        <div class="row">
            <div id="region-side-pre" class="col-md-3">
                <?php echo $OUTPUT->blocks('side-pre'); ?>
            </div>
            <div id="page-content" class="col-md-9">
                <?php echo $OUTPUT->main_content(); ?>
            </div>
        </div>
    </div>

    <?php echo $renderer->footer(); ?>

    <?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>
