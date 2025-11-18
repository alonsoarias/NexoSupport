<?php
/**
 * Debug Routing Script
 *
 * Run this to see how URIs are being parsed
 * Access: http://localhost/admin/debug_routing.php?test=1&foo=bar
 */

define('BASE_DIR', dirname(__DIR__));
define('NEXOSUPPORT_INTERNAL', true);

echo '<pre>';
echo "=== URI PARSING DEBUG ===\n\n";

echo "1. RAW _SERVER variables:\n";
echo "   REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "   SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "   PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'not set') . "\n";
echo "   QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'not set') . "\n\n";

echo "2. parse_url() results:\n";
$parsed = parse_url($_SERVER['REQUEST_URI'] ?? '');
echo "   Full parse_url():\n";
print_r($parsed);
echo "\n";
echo "   PHP_URL_PATH only: " . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . "\n";
echo "   PHP_URL_QUERY only: " . (parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '(none)') . "\n\n";

echo "3. What the front controller SHOULD be doing:\n";
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
echo "   \$uri = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);\n";
echo "   Result: " . $uri . "\n\n";

echo "4. Test router matching:\n";
require_once(BASE_DIR . '/config.php');
require_once(BASE_DIR . '/lib/classes/routing/router.php');

$router = new \core\routing\router();
$router->get('/admin/debug_routing.php', function() {
    return 'MATCHED!';
});

try {
    $result = $router->dispatch($uri, 'GET');
    echo "   ✓ Router matched successfully!\n";
    echo "   Result: " . $result . "\n";
} catch (\Exception $e) {
    echo "   ✗ Router did not match\n";
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "5. Test with query string:\n";
echo "   If you accessed this with ?test=1&foo=bar, the query string should be:\n";
echo "   test = " . ($_GET['test'] ?? 'not set') . "\n";
echo "   foo = " . ($_GET['foo'] ?? 'not set') . "\n";
echo "   (Query strings should still work even though they're not in the route path)\n";

echo "\n=== END DEBUG ===\n";
echo '</pre>';
