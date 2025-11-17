<?php
/**
 * Audit log export to CSV
 *
 * @package    report_log
 * @copyright  2025 ISER
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('NEXOSUPPORT_INTERNAL') || die();

use ISER\Report\Log\LogController;
use ISER\Core\Database\Database;
use ISER\Core\Config\Config;
use ISER\Core\I18n\Translator;
use ISER\Core\View\ViewRenderer;

// Initialize dependencies
$db = Database::getInstance();
$config = Config::getInstance();
$translator = Translator::getInstance();
$renderer = ViewRenderer::getInstance();

// Create controller
$controller = new LogController($db, $config, $translator, $renderer);

// Get request parameters
$params = [
    'userid' => $_GET['userid'] ?? 0,
    'action' => $_GET['action'] ?? '',
    'datefrom' => !empty($_GET['datefrom']) ? strtotime($_GET['datefrom']) : 0,
    'dateto' => !empty($_GET['dateto']) ? strtotime($_GET['dateto']) : 0,
];

// Export (this will send headers and exit)
$controller->export($params);
