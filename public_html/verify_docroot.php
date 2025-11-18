<?php
/**
 * Verify Document Root Configuration
 *
 * This file should ONLY be accessible if Document Root is pointing to public_html
 * Access: http://localhost/verify_docroot.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Document Root Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .success {
            color: #2e7d32;
            padding: 15px;
            background: #e8f5e9;
            border-left: 4px solid #2e7d32;
            margin: 15px 0;
            border-radius: 4px;
        }
        .error {
            color: #c62828;
            padding: 15px;
            background: #ffebee;
            border-left: 4px solid #c62828;
            margin: 15px 0;
            border-radius: 4px;
        }
        .warning {
            color: #f57c00;
            padding: 15px;
            background: #fff3e0;
            border-left: 4px solid #f57c00;
            margin: 15px 0;
            border-radius: 4px;
        }
        .info {
            color: #1976d2;
            padding: 15px;
            background: #e3f2fd;
            border-left: 4px solid #1976d2;
            margin: 15px 0;
            border-radius: 4px;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            overflow-x: auto;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .test-item {
            margin: 20px 0;
            padding: 15px;
            background: #fafafa;
            border-radius: 4px;
        }
        .test-item h3 {
            margin-top: 0;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Document Root Verification</h1>

        <div class="test-item">
            <h3>Test 1: This File Location</h3>
            <?php
            $currentFile = __FILE__;
            $expectedPath = 'public_html' . DIRECTORY_SEPARATOR . 'verify_docroot.php';

            if (strpos($currentFile, $expectedPath) !== false) {
                echo '<div class="success">✓ This file is in public_html (correct location)</div>';
            } else {
                echo '<div class="error">✗ Unexpected file location</div>';
            }
            ?>
            <pre><?php echo htmlspecialchars($currentFile); ?></pre>
        </div>

        <div class="test-item">
            <h3>Test 2: Document Root</h3>
            <?php
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? 'Not set';
            $expectedEnding = 'public_html';

            if (str_ends_with(rtrim($docRoot, '/\\'), $expectedEnding)) {
                echo '<div class="success">✓ Document Root ends with "public_html" (CORRECT!)</div>';
                echo '<p>Your Apache is correctly configured to serve from public_html directory.</p>';
            } else {
                echo '<div class="error">✗ Document Root does NOT end with "public_html" (INCORRECT!)</div>';
                echo '<p><strong>Action Required:</strong> Change your MAMP Document Root to point to the <code>public_html</code> subdirectory.</p>';
                echo '<ol>';
                echo '<li>Open MAMP</li>';
                echo '<li>Go to: <strong>Preferences → Web Server</strong></li>';
                echo '<li>Change Document Root to: <code>' . htmlspecialchars($docRoot) . DIRECTORY_SEPARATOR . 'public_html</code></li>';
                echo '<li>Save and restart Apache</li>';
                echo '</ol>';
            }
            ?>
            <pre>Document Root: <?php echo htmlspecialchars($docRoot); ?></pre>
        </div>

        <div class="test-item">
            <h3>Test 3: Access to Files Outside public_html</h3>
            <?php
            // Try to check if admin/user/edit.php would be accessible
            $testPaths = [
                '/admin/user/edit.php',
                '/lib/functions.php',
                '/config.php'
            ];

            echo '<p>These files should NOT be directly accessible via HTTP:</p>';
            echo '<ul>';
            foreach ($testPaths as $path) {
                $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
                if (file_exists($fullPath)) {
                    echo '<li><code>' . htmlspecialchars($path) . '</code> ';
                    echo '<span style="color: red;">✗ FILE EXISTS in document root (WRONG!)</span></li>';
                } else {
                    echo '<li><code>' . htmlspecialchars($path) . '</code> ';
                    echo '<span style="color: green;">✓ File not in document root (CORRECT!)</span></li>';
                }
            }
            echo '</ul>';
            ?>
        </div>

        <div class="test-item">
            <h3>Test 4: Front Controller Pattern</h3>
            <?php
            $indexPath = $_SERVER['DOCUMENT_ROOT'] . '/index.php';
            if (file_exists($indexPath)) {
                echo '<div class="success">✓ index.php exists in document root</div>';

                // Check if it's the front controller
                $content = file_get_contents($indexPath);
                if (strpos($content, 'Front Controller') !== false) {
                    echo '<div class="success">✓ index.php is the Front Controller</div>';
                } else {
                    echo '<div class="warning">⚠ index.php exists but might not be the Front Controller</div>';
                }
            } else {
                echo '<div class="error">✗ index.php NOT FOUND in document root</div>';
            }
            ?>
        </div>

        <div class="test-item">
            <h3>Test 5: .htaccess Configuration</h3>
            <?php
            $htaccessPath = $_SERVER['DOCUMENT_ROOT'] . '/.htaccess';
            if (file_exists($htaccessPath)) {
                echo '<div class="success">✓ .htaccess exists</div>';

                $htaccess = file_get_contents($htaccessPath);
                if (strpos($htaccess, 'mod_rewrite') !== false) {
                    echo '<div class="success">✓ mod_rewrite configuration found</div>';
                }
                if (strpos($htaccess, 'RewriteRule') !== false) {
                    echo '<div class="success">✓ RewriteRule configured</div>';
                }
            } else {
                echo '<div class="error">✗ .htaccess NOT FOUND</div>';
            }
            ?>
        </div>

        <div class="info">
            <h3>📋 Summary</h3>
            <?php
            $allGood = str_ends_with(rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'), 'public_html');

            if ($allGood) {
                echo '<p><strong style="color: green; font-size: 18px;">✓ CONFIGURATION IS CORRECT!</strong></p>';
                echo '<p>Your MAMP Document Root is properly configured. The front controller pattern should work correctly.</p>';
                echo '<p>You can now access: <a href="/admin">/admin</a>, <a href="/admin/users">/admin/users</a>, etc.</p>';
            } else {
                echo '<p><strong style="color: red; font-size: 18px;">✗ CONFIGURATION NEEDS TO BE FIXED</strong></p>';
                echo '<p>Please update your MAMP Document Root to point to the <code>public_html</code> directory.</p>';
            }
            ?>
        </div>
    </div>
</body>
</html>
