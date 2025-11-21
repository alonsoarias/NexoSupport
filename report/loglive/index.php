<?php
/**
 * Live log report page.
 *
 * Displays log entries in real-time using AJAX polling.
 *
 * @package    report_loglive
 * @copyright  2025 NexoSupport
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Parameters.
$courseid = optional_param('id', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

// Default refresh interval (seconds).
$refresh = defined('REPORT_LOGLIVE_REFRESH') ? REPORT_LOGLIVE_REFRESH : 60;

// Setup page.
if ($courseid && $courseid != SITEID) {
    $course = $DB->get_record('courses', ['id' => $courseid]);
    if (!$course) {
        throw new nexo_exception('invalidcourseid');
    }
    require_login($course);
    $context = context_course::instance($courseid);
    $PAGE->set_context($context);
} else {
    admin_externalpage_setup('reportloglive', '', null, '', ['pagelayout' => 'report']);
    $context = context_system::instance();
    $courseid = SITEID;
}

// Check capability.
require_capability('report/loglive:view', $context);

// Page URL.
$url = new nexo_url('/report/loglive/index.php', ['id' => $courseid, 'page' => $page]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname', 'report_loglive'));
$PAGE->set_heading(get_string('pluginname', 'report_loglive'));

// Create renderable.
$renderable = new \report_loglive\renderable($courseid, $page, 100, $url);

// Output.
$output = $PAGE->get_renderer('report_loglive');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_loglive'));

// Render the initial table.
echo $output->render($renderable);

// Add JavaScript for live updates (only on first page).
if ($page == 0) {
    $until = $renderable->get_table()->get_until();

    $jsparams = [
        'since' => $until,
        'courseid' => $courseid,
        'page' => $page,
        'interval' => $refresh * 1000, // Convert to milliseconds.
        'perpage' => 100,
        'ajaxurl' => (new nexo_url('/report/loglive/loglive_ajax.php'))->out(false),
    ];

    // Output the JavaScript initialization.
    echo '<script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            var loglive = {
                since: ' . $jsparams['since'] . ',
                courseid: ' . $jsparams['courseid'] . ',
                interval: ' . $jsparams['interval'] . ',
                perpage: ' . $jsparams['perpage'] . ',
                ajaxurl: "' . $jsparams['ajaxurl'] . '",
                paused: false,
                timer: null,

                init: function() {
                    this.startPolling();
                    this.bindPauseButton();
                },

                startPolling: function() {
                    var self = this;
                    this.timer = setInterval(function() {
                        if (!self.paused) {
                            self.fetchLogs();
                        }
                    }, this.interval);
                },

                stopPolling: function() {
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                },

                fetchLogs: function() {
                    var self = this;
                    var xhr = new XMLHttpRequest();
                    var params = "id=" + this.courseid + "&since=" + this.since;

                    xhr.open("GET", this.ajaxurl + "?" + params, true);
                    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                self.updateTable(response);
                            } catch(e) {
                                console.error("Failed to parse response", e);
                            }
                        }
                    };

                    xhr.send();
                },

                updateTable: function(response) {
                    if (response.until) {
                        this.since = response.until;
                    }

                    if (response.logs && response.logs.length > 0) {
                        var tbody = document.querySelector(".loglive-table tbody");
                        if (tbody) {
                            // Insert new rows at the top.
                            tbody.insertAdjacentHTML("afterbegin", response.logs);

                            // Remove excess rows to maintain perpage limit.
                            var rows = tbody.querySelectorAll("tr");
                            while (rows.length > this.perpage) {
                                tbody.removeChild(rows[rows.length - 1]);
                                rows = tbody.querySelectorAll("tr");
                            }

                            // Update count display.
                            var countEl = document.querySelector(".loglive-count");
                            if (countEl && response.newcount) {
                                countEl.textContent = response.newcount + " new entries";
                            }
                        }
                    }
                },

                bindPauseButton: function() {
                    var self = this;
                    var btn = document.getElementById("loglive-pause");
                    if (btn) {
                        btn.addEventListener("click", function() {
                            self.paused = !self.paused;
                            btn.textContent = self.paused ? "' . get_string('resume', 'report_loglive') . '" : "' . get_string('pause', 'report_loglive') . '";
                            btn.className = self.paused ? "btn btn-success" : "btn btn-warning";
                        });
                    }
                }
            };

            loglive.init();
        });
    </script>';
}

echo $OUTPUT->footer();

// Trigger event.
$event = \report_loglive\event\report_viewed::create([
    'context' => $context,
    'other' => ['courseid' => $courseid],
]);
$event->trigger();
