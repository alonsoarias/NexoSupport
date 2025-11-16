<?php
/**
 * NexoSupport - Theme ISER - Base Layout
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
</head>
<body <?php echo $OUTPUT->body_attributes(); ?>>
    <?php echo $OUTPUT->standard_top_of_body_html(); ?>

    <?php echo $renderer->header(); ?>

    <div id="page" class="container-fluid">
        <div id="page-content">
            <?php echo $OUTPUT->main_content(); ?>
        </div>
    </div>

    <?php echo $renderer->footer(); ?>

    <?php echo $OUTPUT->standard_end_of_body_html(); ?>
</body>
</html>
