<?php
/**
 * Boost Theme Library Functions
 *
 * Contains callbacks for SCSS processing and theme functionality.
 * Similar to Moodle's theme/boost/lib.php
 *
 * @package    theme_boost
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Get SCSS to prepend (variables)
 *
 * @param object $theme The theme config object
 * @return string SCSS code
 */
function theme_boost_get_pre_scss($theme) {
    $scss = '';

    // Configurable settings mapping to SCSS variables
    $configurable = [
        'brandcolor' => ['primary'],
        'secondarycolor' => ['secondary'],
        'successcolor' => ['success'],
        'infocolor' => ['info'],
        'warningcolor' => ['warning'],
        'dangercolor' => ['danger'],
    ];

    // Get settings and generate SCSS variables
    foreach ($configurable as $configkey => $targets) {
        $value = get_config('theme_boost', $configkey);
        if (empty($value)) {
            continue;
        }
        foreach ((array) $targets as $target) {
            $scss .= '$' . $target . ': ' . $value . ";\n";
        }
    }

    // Add custom SCSS from settings
    $prescss = get_config('theme_boost', 'scsspre');
    if (!empty($prescss)) {
        $scss .= $prescss . "\n";
    }

    return $scss;
}

/**
 * Get main SCSS content
 *
 * @param object $theme The theme config object
 * @return string SCSS code
 */
function theme_boost_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';

    // Get preset
    $preset = get_config('theme_boost', 'preset');
    if (empty($preset)) {
        $preset = 'default';
    }

    // Load preset file
    $presetfile = $CFG->dirroot . '/theme/boost/scss/preset/' . $preset . '.scss';
    if (file_exists($presetfile)) {
        $scss .= file_get_contents($presetfile);
    } else {
        // Fallback to default preset
        $defaultpreset = $CFG->dirroot . '/theme/boost/scss/preset/default.scss';
        if (file_exists($defaultpreset)) {
            $scss .= file_get_contents($defaultpreset);
        }
    }

    return $scss;
}

/**
 * Get extra SCSS (appended at end)
 *
 * @param object $theme The theme config object
 * @return string SCSS code
 */
function theme_boost_get_extra_scss($theme) {
    global $CFG;
    $content = '';

    // Background image
    $imageurl = get_config('theme_boost', 'backgroundimage');
    if (!empty($imageurl)) {
        $content .= '@media (min-width: 768px) {';
        $content .= "body { background-image: url('{$imageurl}'); background-size: cover; background-attachment: fixed; }";
        $content .= '}';
    }

    // Login background image
    $loginbg = get_config('theme_boost', 'loginbackgroundimage');
    if (!empty($loginbg)) {
        $content .= ".pagelayout-login { background-image: url('{$loginbg}'); background-size: cover; }";
    }

    // Custom SCSS from settings
    $customscss = get_config('theme_boost', 'scss');
    if (!empty($customscss)) {
        $content .= $customscss;
    }

    return $content;
}

/**
 * Get precompiled CSS
 *
 * Returns path to precompiled CSS if available.
 *
 * @param object $theme The theme config object
 * @return string|null Path to CSS file
 */
function theme_boost_get_precompiled_css($theme) {
    global $CFG;

    $cssfile = $CFG->dirroot . '/theme/boost/style/boost.css';
    if (file_exists($cssfile)) {
        return $cssfile;
    }

    return null;
}

/**
 * Compile SCSS to CSS
 *
 * @param string $scss SCSS content
 * @return string Compiled CSS
 */
function theme_boost_compile_scss($scss) {
    // For now, return the SCSS as-is (basic CSS is valid SCSS)
    // In production, integrate with scssphp library
    return $scss;
}

/**
 * Serves theme files
 *
 * @param stdClass $course Course object
 * @param stdClass $cm Course module
 * @param context $context Context
 * @param string $filearea File area
 * @param array $args Arguments
 * @param bool $forcedownload Force download
 * @param array $options Options
 * @return bool Success
 */
function theme_boost_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    // Theme-specific file areas
    $validareas = ['backgroundimage', 'loginbackgroundimage', 'logo', 'favicon'];

    if (!in_array($filearea, $validareas)) {
        return false;
    }

    // Serve the file
    $filename = array_pop($args);
    $filepath = implode('/', $args);

    // Get file from theme directory
    $themedir = $CFG->dirroot . '/theme/boost';
    $file = $themedir . '/pix/' . $filearea . '/' . $filename;

    if (file_exists($file)) {
        send_file($file, $filename, 0, 0, false, $forcedownload, '', false, $options);
        return true;
    }

    return false;
}

/**
 * Get the navbar output class
 *
 * @param object $theme Theme object
 * @return string CSS class for navbar
 */
function theme_boost_get_navbar_class($theme) {
    $navbartype = get_config('theme_boost', 'navbartype');

    switch ($navbartype) {
        case 'dark':
            return 'navbar-dark bg-dark';
        case 'primary':
            return 'navbar-dark bg-primary';
        case 'light':
        default:
            return 'navbar-light bg-white';
    }
}

/**
 * Initialize page requirements
 *
 * @param moodle_page $page The page object
 */
function theme_boost_page_init($page) {
    // Add any required JavaScript or CSS
    // $page->requires->js_call_amd('theme_boost/loader', 'init');
}
