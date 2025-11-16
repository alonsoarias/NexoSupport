<?php
/**
 * Admin settings for auth_manual
 *
 * @package    auth_manual
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

if ($hassiteconfig) {
    // No hay configuraciones específicas para autenticación manual
    // Los usuarios se gestionan completamente desde la interfaz de administración

    $settings->add(new admin_setting_heading(
        'auth_manual/heading',
        get_string('pluginname', 'auth_manual'),
        get_string('auth_manualdescription', 'auth_manual')
    ));

    // Configuración de expiración de contraseñas (opcional)
    $settings->add(new admin_setting_configcheckbox(
        'auth_manual/expiration',
        get_string('expiration', 'auth_manual'),
        get_string('expiration_desc', 'auth_manual'),
        0
    ));

    // Días hasta la expiración
    $settings->add(new admin_setting_configtext(
        'auth_manual/expiration_warning',
        get_string('expiration_warning', 'auth_manual'),
        get_string('expiration_warning_desc', 'auth_manual'),
        30,
        PARAM_INT
    ));

    // Complejidad mínima de contraseña
    $settings->add(new admin_setting_configselect(
        'auth_manual/minpasswordlength',
        get_string('minpasswordlength', 'auth_manual'),
        get_string('minpasswordlength_desc', 'auth_manual'),
        8,
        [
            4 => '4',
            6 => '6',
            8 => '8',
            10 => '10',
            12 => '12',
            14 => '14',
            16 => '16',
        ]
    ));

    // Requerir caracteres especiales
    $settings->add(new admin_setting_configcheckbox(
        'auth_manual/passwordpolicy',
        get_string('passwordpolicy', 'auth_manual'),
        get_string('passwordpolicy_desc', 'auth_manual'),
        1
    ));
}
