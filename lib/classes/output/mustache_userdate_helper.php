<?php
namespace core\output;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Mustache User Date Helper
 *
 * Formats timestamps according to user preferences.
 * Usage: {{#userdate}}timestamp, format{{/userdate}}
 *
 * Examples:
 *   {{#userdate}}{{timestamp}}, %d/%m/%Y{{/userdate}}
 *   {{#userdate}}{{timestamp}}, %A, %d %B %Y, %H:%M{{/userdate}}
 *   {{#userdate}}1487655635, %Y-%m-%d{{/userdate}}
 *
 * Format uses strftime() syntax:
 *   %Y - Year (4 digits)
 *   %m - Month (01-12)
 *   %d - Day (01-31)
 *   %H - Hour (00-23)
 *   %M - Minutes (00-59)
 *   %S - Seconds (00-59)
 *   %A - Full weekday name
 *   %B - Full month name
 *   %c - Preferred date and time representation
 *
 * @package    core\output
 * @copyright  NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mustache_userdate_helper {

    /**
     * Format a timestamp
     *
     * @param string $text The helper arguments (timestamp, format)
     * @param \Mustache\LambdaHelper $helper The lambda helper
     * @return string Formatted date
     */
    public function __invoke(string $text, \Mustache\LambdaHelper $helper): string {
        // Render any Mustache variables first
        $text = $helper->render($text);

        // Parse arguments: timestamp, format
        $commapos = strpos($text, ',');
        if ($commapos === false) {
            // No format specified, use default
            $timestamp = trim($text);
            $format = '%c';
        } else {
            $timestamp = trim(substr($text, 0, $commapos));
            $format = trim(substr($text, $commapos + 1));
        }

        // Convert timestamp
        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        if ($timestamp === false || $timestamp === null) {
            return '';
        }

        return $this->format_date((int) $timestamp, $format);
    }

    /**
     * Format a date using user preferences
     *
     * @param int $timestamp Unix timestamp
     * @param string $format strftime format string
     * @return string Formatted date
     */
    protected function format_date(int $timestamp, string $format): string {
        global $USER, $CFG;

        // Get user timezone
        $timezone = $this->get_user_timezone();

        // Create DateTime object
        $datetime = new \DateTime('@' . $timestamp);
        $datetime->setTimezone(new \DateTimeZone($timezone));

        // Convert strftime format to DateTime format
        $dateformat = $this->strftime_to_datetime($format);

        return $datetime->format($dateformat);
    }

    /**
     * Get user timezone
     *
     * @return string Timezone identifier
     */
    protected function get_user_timezone(): string {
        global $USER, $CFG;

        // Try user timezone first
        if (!empty($USER->timezone) && $USER->timezone !== '99') {
            return $USER->timezone;
        }

        // Fall back to site timezone
        if (!empty($CFG->timezone)) {
            return $CFG->timezone;
        }

        // Default to server timezone
        return date_default_timezone_get();
    }

    /**
     * Convert strftime format to DateTime format
     *
     * @param string $format strftime format
     * @return string DateTime format
     */
    protected function strftime_to_datetime(string $format): string {
        $replacements = [
            '%a' => 'D',     // Abbreviated weekday
            '%A' => 'l',     // Full weekday
            '%d' => 'd',     // Day of month (01-31)
            '%e' => 'j',     // Day of month (1-31)
            '%j' => 'z',     // Day of year
            '%u' => 'N',     // ISO weekday (1=Mon, 7=Sun)
            '%w' => 'w',     // Weekday (0=Sun, 6=Sat)
            '%b' => 'M',     // Abbreviated month
            '%B' => 'F',     // Full month
            '%m' => 'm',     // Month (01-12)
            '%y' => 'y',     // 2-digit year
            '%Y' => 'Y',     // 4-digit year
            '%H' => 'H',     // Hour (00-23)
            '%I' => 'h',     // Hour (01-12)
            '%M' => 'i',     // Minutes
            '%S' => 's',     // Seconds
            '%p' => 'A',     // AM/PM
            '%P' => 'a',     // am/pm
            '%Z' => 'T',     // Timezone abbr
            '%z' => 'O',     // Timezone offset
            '%c' => 'r',     // Preferred datetime
            '%x' => 'd/m/Y', // Preferred date
            '%X' => 'H:i:s', // Preferred time
            '%%' => '%',     // Literal %
            '%n' => "\n",    // Newline
            '%t' => "\t",    // Tab
        ];

        return strtr($format, $replacements);
    }
}
