<?php
/**
 * NexoSupport - Upload Users Tool
 *
 * Frankenstyle admin tool for bulk user upload via CSV/Excel
 *
 * @package    tool_uploaduser
 * @copyright  2024 ISER
 * @license    Proprietary
 */

// Require system bootstrap
require_once __DIR__ . '/../../../bootstrap.php';

// Define as internal access
if (!defined('NEXOSUPPORT_INTERNAL')) {
    define('NEXOSUPPORT_INTERNAL', true);
}

// Require login and admin capability
require_login();
require_capability('tool/uploaduser:upload');

// Load tool library
require_once __DIR__ . '/lib.php';

use ISER\Core\Database\Database;
use tool_uploaduser\uploader;

$database = Database::getInstance();

// Handle file upload
$message = null;
$error = null;
$results = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['userfile'])) {
    try {
        $uploader = new uploader($database);

        // Validate file
        if ($_FILES['userfile']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $_FILES['userfile']['error']);
        }

        // Process file
        $results = $uploader->process_file($_FILES['userfile']['tmp_name'], $_FILES['userfile']['name']);

        if ($results['success'] > 0) {
            $message = sprintf(
                'Successfully uploaded %d user(s). %d error(s).',
                $results['success'],
                $results['errors']
            );
        } else {
            $error = 'No users were uploaded. Check the file format.';
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Render page
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('tool_uploaduser:title'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        input[type="file"] {
            display: block;
            width: 100%;
            padding: 10px;
            border: 2px dashed #ccc;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="file"]:hover {
            border-color: #667eea;
        }
        button {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #5568d3;
        }
        .help-text {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-top: 30px;
        }
        .help-text h3 {
            margin-bottom: 10px;
            color: #333;
        }
        .help-text ul {
            margin-left: 20px;
            color: #666;
        }
        .help-text li {
            margin-bottom: 5px;
        }
        .sample {
            background: #fff;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
            margin-top: 10px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .results {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .results h3 {
            margin-bottom: 10px;
        }
        .results table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .results th, .results td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .results th {
            background: #e9ecef;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/admin" class="back-link">← Back to Admin</a>

        <h1><?php echo tool_uploaduser_get_title(); ?></h1>
        <p class="subtitle">Upload multiple users at once using a CSV file</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="userfile">Select CSV File:</label>
                <input type="file" name="userfile" id="userfile" accept=".csv,.txt" required>
            </div>

            <button type="submit">Upload Users</button>
        </form>

        <?php if ($results && !empty($results['details'])): ?>
            <div class="results">
                <h3>Upload Results</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['details'] as $detail): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detail['username']); ?></td>
                                <td><?php echo htmlspecialchars($detail['email']); ?></td>
                                <td><?php echo $detail['success'] ? '✓ Success' : '✗ Error'; ?></td>
                                <td><?php echo htmlspecialchars($detail['message']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="help-text">
            <h3>CSV Format Instructions</h3>
            <p>Your CSV file should contain the following columns (comma-separated):</p>
            <ul>
                <li><strong>username</strong> - Unique username (required)</li>
                <li><strong>email</strong> - Email address (required)</li>
                <li><strong>password</strong> - User password (required, min 8 characters)</li>
                <li><strong>firstname</strong> - First name (optional)</li>
                <li><strong>lastname</strong> - Last name (optional)</li>
                <li><strong>status</strong> - User status: active, suspended, pending (optional, default: active)</li>
            </ul>

            <h3 style="margin-top: 15px;">Sample CSV:</h3>
            <div class="sample">
username,email,password,firstname,lastname,status<br>
jdoe,john.doe@example.com,SecurePass123,John,Doe,active<br>
jsmith,jane.smith@example.com,AnotherPass456,Jane,Smith,active
            </div>

            <h3 style="margin-top: 15px;">Notes:</h3>
            <ul>
                <li>First line must be a header row with column names</li>
                <li>Passwords will be automatically hashed</li>
                <li>Usernames and emails must be unique</li>
                <li>Invalid rows will be skipped and reported</li>
            </ul>
        </div>
    </div>
</body>
</html>
