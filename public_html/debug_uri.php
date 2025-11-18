<?php
/**
 * Debug URI Parsing
 *
 * This helps identify why the router is receiving URIs with query strings
 * Access: http://localhost/debug_uri.php?test=value&foo=bar
 */

define('BASE_DIR', dirname(__DIR__));
define('NEXOSUPPORT_INTERNAL', true);

header('Content-Type: text/plain');

echo "=== URI PARSING DEBUG ===\n\n";

// Step 1: Raw server variables
echo "1. SERVER VARIABLES:\n";
echo "   REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "   QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'not set') . "\n\n";

// Step 2: How front controller SHOULD parse it
echo "2. FRONT CONTROLLER PARSING (line 23):\n";
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
echo "   \$uri = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);\n";
echo "   Result: " . var_export($uri, true) . "\n\n";

// Step 3: Method
echo "3. METHOD:\n";
$method = $_SERVER['REQUEST_METHOD'];
echo "   \$method = \$_SERVER['REQUEST_METHOD'];\n";
echo "   Result: " . var_export($method, true) . "\n\n";

// Step 4: What gets logged
echo "4. WHAT GETS LOGGED:\n";
echo "   Front Controller should log:\n";
echo "   - REQUEST_URI = " . ($_SERVER['REQUEST_URI'] ?? 'undefined') . "\n";
echo "   - Parsed URI = $uri\n";
echo "   - Method = $method\n\n";

// Step 5: Load router and test
echo "5. ROUTER TEST:\n";
require_once(BASE_DIR . '/config.php');
require_once(BASE_DIR . '/lib/classes/routing/router.php');

echo "   Creating router instance...\n";
$router = new \core\routing\router();

echo "   Registering test route...\n";
$router->get('/debug_uri.php', function() {
    return 'ROUTER MATCHED!';
});

echo "   Calling \$router->dispatch(\$uri, \$method)...\n";
echo "   Parameters:\n";
echo "     - \$uri = " . var_export($uri, true) . "\n";
echo "     - \$method = " . var_export($method, true) . "\n\n";

try {
    $result = $router->dispatch($uri, $method);
    echo "   ✓ SUCCESS: Router matched and returned: " . var_export($result, true) . "\n";
} catch (\Exception $e) {
    echo "   ✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 6: Check if debugging is enabled
echo "6. DEBUGGING CONFIGURATION:\n";
require_once(BASE_DIR . '/lib/setup.php');

if (defined('DEBUG_DEVELOPER')) {
    echo "   DEBUG_DEVELOPER = " . DEBUG_DEVELOPER . "\n";
} else {
    echo "   DEBUG_DEVELOPER not defined\n";
}

global $CFG;
if (isset($CFG->debug)) {
    echo "   \$CFG->debug = " . $CFG->debug . "\n";
} else {
    echo "   \$CFG->debug not set\n";
}

echo "\n";

// Step 7: Check actual index.php code
echo "7. VERIFY index.php CODE:\n";
$indexPath = __DIR__ . '/index.php';
$indexContent = file_get_contents($indexPath);

// Check line 23
if (preg_match('/\$uri\s*=\s*parse_url\(\$_SERVER\[\'REQUEST_URI\'\],\s*PHP_URL_PATH\);/', $indexContent)) {
    echo "   ✓ Line 23 is correct: Uses PHP_URL_PATH\n";
} else {
    echo "   ✗ Line 23 might be wrong or different\n";
    // Try to find the actual line
    $lines = explode("\n", $indexContent);
    foreach ($lines as $num => $line) {
        if (strpos($line, '$uri') !== false && strpos($line, 'parse_url') !== false) {
            echo "   Found at line " . ($num + 1) . ": " . trim($line) . "\n";
        }
    }
}

// Check line 287
if (preg_match('/\$router->dispatch\(\$uri,\s*\$method\);/', $indexContent)) {
    echo "   ✓ Router dispatch call is correct: Uses \$uri variable\n";
} else {
    echo "   ⚠ Router dispatch might be different\n";
    $lines = explode("\n", $indexContent);
    foreach ($lines as $num => $line) {
        if (strpos($line, 'dispatch') !== false && strpos($line, 'router') !== false) {
            echo "   Found at line " . ($num + 1) . ": " . trim($line) . "\n";
        }
    }
}

echo "\n=== END DEBUG ===\n";
