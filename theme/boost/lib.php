<?php
/**
 * Boost Theme Library Functions
 *
 * Contains callbacks for SCSS processing and theme functionality.
 * Replicates Moodle's theme/boost/lib.php architecture.
 *
 * @package    theme_boost
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string SCSS content
 */
function theme_boost_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';

    // Get the preset file
    $preset = get_config('theme_boost', 'preset');
    if (empty($preset)) {
        $preset = 'default';
    }

    // First, we need to include Bootstrap variables
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

    // Include moodle scss files
    $moodlescssdir = $CFG->dirroot . '/theme/boost/scss/moodle';
    if (is_dir($moodlescssdir)) {
        $files = glob($moodlescssdir . '/*.scss');
        sort($files);
        foreach ($files as $file) {
            $scss .= file_get_contents($file);
        }
    }

    return $scss;
}

/**
 * Get SCSS to prepend (variables)
 *
 * @param theme_config $theme The theme config object
 * @return string SCSS code
 */
function theme_boost_get_pre_scss($theme) {
    global $CFG;

    $scss = '';

    // ISER Brand colors
    $scss .= "// ISER Brand Colors\n";
    $scss .= '$iser-verde: #1B9E88;' . "\n";
    $scss .= '$iser-amarillo: #FCBD05;' . "\n";
    $scss .= '$iser-rojo: #EB4335;' . "\n";
    $scss .= '$iser-blanco: #FFFFFF;' . "\n";
    $scss .= '$iser-naranja: #E27C32;' . "\n";
    $scss .= '$iser-lima: #CFDA4B;' . "\n";
    $scss .= '$iser-azul: #5894EF;' . "\n";
    $scss .= '$iser-magenta: #C82260;' . "\n";
    $scss .= "\n";

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
            // Use ISER defaults
            if ($configkey === 'brandcolor') {
                $value = '#1B9E88'; // ISER Verde
            }
            continue;
        }
        foreach ((array) $targets as $target) {
            $scss .= '$' . $target . ': ' . $value . ";\n";
        }
    }

    // Default primary color if not set
    if (empty(get_config('theme_boost', 'brandcolor'))) {
        $scss .= '$primary: #1B9E88;' . "\n"; // ISER Verde
    }

    // Add custom SCSS from settings
    $prescss = get_config('theme_boost', 'scsspre');
    if (!empty($prescss)) {
        $scss .= $prescss . "\n";
    }

    // Include variables file if exists
    $varsfile = $CFG->dirroot . '/theme/boost/scss/_variables.scss';
    if (file_exists($varsfile)) {
        $scss .= file_get_contents($varsfile);
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
 * @param page $page The page object
 */
function theme_boost_page_init($page) {
    global $CFG;

    // Add theme JavaScript modules
    if (method_exists($page, 'requires_js_module')) {
        $page->requires_js_module('theme_boost/loader');
        $page->requires_js_module('theme_boost/drawers');
    }
}

/**
 * Post process the CSS.
 *
 * @param string $css CSS to process
 * @return string Processed CSS
 */
function theme_boost_process_css($css) {
    global $CFG;

    // Replace any [[setting:xyz]] placeholders
    if (preg_match_all('/\[\[setting:([a-z0-9_]+)\]\]/', $css, $matches)) {
        foreach ($matches[1] as $setting) {
            $value = get_config('theme_boost', $setting);
            $css = str_replace('[[setting:' . $setting . ']]', $value ?? '', $css);
        }
    }

    return $css;
}

/**
 * Get the body attributes for the theme.
 *
 * @param array $classes Additional classes
 * @return string Body attributes HTML
 */
function theme_boost_get_body_attributes($classes = []) {
    global $CFG, $PAGE;

    $bodyattributes = [];

    // Get theme config
    $draweropen = get_user_preferences('drawer-open-nav', true);
    $blockdraweropen = get_user_preferences('drawer-open-block', true);

    // Add layout class
    $layout = $PAGE->pagelayout ?? 'standard';
    $classes[] = 'pagelayout-' . $layout;

    // Add drawer classes
    if ($draweropen) {
        $classes[] = 'drawer-open-left';
    }
    if ($blockdraweropen) {
        $classes[] = 'drawer-open-index';
    }

    // Add theme class
    $classes[] = 'theme-boost';

    $bodyattributes['class'] = implode(' ', array_filter($classes));

    // Build attribute string
    $attrs = [];
    foreach ($bodyattributes as $key => $value) {
        $attrs[] = $key . '="' . htmlspecialchars($value) . '"';
    }

    return implode(' ', $attrs);
}

/**
 * Get template context for this theme.
 *
 * Builds the standard template context used by all layout files.
 *
 * @param page $page The page object
 * @return array Template context
 */
function theme_boost_get_template_context($page) {
    global $CFG, $USER, $OUTPUT;

    // User information
    $isloggedin = isloggedin() && !isguestuser();
    $userfullname = $isloggedin ? fullname($USER) : '';
    $useravatar = $isloggedin ? theme_boost_get_user_avatar($USER) : '';

    // Drawer state
    $draweropen = get_user_preferences('drawer-open-nav', true);
    $blockdraweropen = get_user_preferences('drawer-open-block', true);

    // Regions
    $hasblocks = false;
    if (method_exists($page, 'blocks_for_region')) {
        $hasblocks = !empty($page->blocks_for_region('side-pre'));
    }

    return [
        // Site info
        'sitename' => format_string($CFG->sitename ?? 'NexoSupport'),
        'wwwroot' => $CFG->wwwroot,
        'sesskey' => sesskey(),

        // User info
        'isloggedin' => $isloggedin,
        'userfullname' => $userfullname,
        'useravatar' => $useravatar,
        'userid' => $USER->id ?? 0,
        'isadmin' => is_siteadmin(),

        // Navigation URLs
        'homeurl' => $CFG->wwwroot . '/',
        'loginurl' => $CFG->wwwroot . '/login/',
        'logouturl' => $CFG->wwwroot . '/logout/',
        'profileurl' => $CFG->wwwroot . '/user/profile/',
        'dashboardurl' => $CFG->wwwroot . '/my/',
        'adminurl' => $CFG->wwwroot . '/admin/',

        // Drawer state
        'draweropen' => $draweropen,
        'blockdraweropen' => $blockdraweropen && $hasblocks,
        'draweropenright' => $blockdraweropen && $hasblocks,

        // Blocks
        'hasblocks' => $hasblocks,
        'sidepreblocks' => $hasblocks ? $page->blocks_for_region('side-pre') : '',

        // Page info
        'pagetitle' => $page->title ?? '',
        'pageheading' => $page->heading ?? '',
        'pagelayout' => $page->pagelayout ?? 'standard',

        // Body attributes
        'bodyattributes' => theme_boost_get_body_attributes(),

        // Output
        'output' => $OUTPUT,

        // Language
        'currentlang' => current_language(),
        'langdir' => get_string_direction(),

        // Debug
        'debug' => !empty($CFG->debug),
    ];
}

/**
 * Get user avatar URL or HTML
 *
 * @param stdClass $user User object
 * @param int $size Size of the avatar
 * @return string Avatar HTML
 */
function theme_boost_get_user_avatar($user, $size = 36) {
    // Get initials
    $initials = '';
    if (!empty($user->firstname)) {
        $initials .= strtoupper(substr($user->firstname, 0, 1));
    }
    if (!empty($user->lastname)) {
        $initials .= strtoupper(substr($user->lastname, 0, 1));
    }

    // Return initials-based avatar (can be extended to support actual images)
    return '<span class="user-avatar" style="width:' . $size . 'px;height:' . $size . 'px;">' .
           htmlspecialchars($initials) . '</span>';
}

/**
 * Get string direction (ltr or rtl)
 *
 * @return string 'ltr' or 'rtl'
 */
function get_string_direction() {
    $rtl_langs = ['ar', 'he', 'fa', 'ur'];
    $lang = current_language();
    return in_array($lang, $rtl_langs) ? 'rtl' : 'ltr';
}

/**
 * Get current language
 *
 * @return string Language code
 */
function current_language() {
    global $CFG;
    return $CFG->lang ?? 'es';
}

/**
 * Format a string with proper escaping
 *
 * @param string $string String to format
 * @return string Formatted string
 */
function format_string($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is site admin
 *
 * @param int|null $userid User ID (default: current user)
 * @return bool True if admin
 */
function is_siteadmin($userid = null) {
    global $USER, $CFG;

    if ($userid === null) {
        $userid = $USER->id ?? 0;
    }

    // Check admin list
    $admins = explode(',', $CFG->siteadmins ?? '');
    return in_array($userid, $admins);
}

/**
 * Check if user is logged in
 *
 * @return bool True if logged in
 */
function isloggedin() {
    global $USER;
    return !empty($USER->id) && $USER->id > 0;
}

/**
 * Check if user is guest
 *
 * @return bool True if guest
 */
function isguestuser() {
    global $USER, $CFG;
    return ($USER->id ?? 0) == ($CFG->guestid ?? 0);
}

/**
 * Get full name of user
 *
 * @param stdClass $user User object
 * @return string Full name
 */
function fullname($user) {
    $parts = [];
    if (!empty($user->firstname)) {
        $parts[] = $user->firstname;
    }
    if (!empty($user->lastname)) {
        $parts[] = $user->lastname;
    }
    return implode(' ', $parts);
}

/**
 * Get session key
 *
 * @return string Session key
 */
function sesskey() {
    if (empty($_SESSION['sesskey'])) {
        $_SESSION['sesskey'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['sesskey'];
}

/**
 * Get user preference
 *
 * @param string $name Preference name
 * @param mixed $default Default value
 * @return mixed Preference value
 */
function get_user_preferences($name, $default = null) {
    global $USER;

    if (isset($USER->preferences[$name])) {
        return $USER->preferences[$name];
    }

    return $default;
}

/**
 * Set user preference
 *
 * @param string $name Preference name
 * @param mixed $value Value to set
 */
function set_user_preference($name, $value) {
    global $USER;

    if (!isset($USER->preferences)) {
        $USER->preferences = [];
    }

    $USER->preferences[$name] = $value;
}
