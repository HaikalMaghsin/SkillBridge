<?php
// Diagnostic script untuk debug error 500
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='background: #f5f5f5; padding: 20px; font-family: monospace;'>";
echo "=== PHP ERROR DIAGNOSTIC ===\n\n";

// Test 1: Check include paths
echo "1. Testing file includes:\n";
$files_to_check = [
    'includes/config/app.php',
    'includes/config/database.php',
    'includes/auth/session.php',
    'includes/auth/csrf.php',
    'includes/auth/auth.php',
    'includes/layouts/head.php',
];

foreach ($files_to_check as $file) {
    $path = __DIR__ . '/../' . $file;
    $exists = file_exists($path) ? "✓ EXISTS" : "✗ MISSING";
    echo "  $file: $exists\n";
}

echo "\n2. Testing actual requires (will show error if there's a fatal error):\n";

try {
    echo "  - Loading app.php... ";
    require_once __DIR__ . '/../includes/config/app.php';
    echo "✓\n";

    echo "  - Loading session.php... ";
    require_once __DIR__ . '/../includes/auth/session.php';
    echo "✓\n";

    echo "  - Loading csrf.php... ";
    require_once __DIR__ . '/../includes/auth/csrf.php';
    echo "✓\n";

    echo "  - Loading database.php... ";
    require_once __DIR__ . '/../includes/config/database.php';
    echo "✓\n";

    echo "  - Loading auth.php... ";
    require_once __DIR__ . '/../includes/auth/auth.php';
    echo "✓\n";

    echo "  - Testing get_db_connection()... ";
    $pdo = get_db_connection();
    if ($pdo) {
        echo "✓ Connected\n";
    } else {
        echo "✗ Failed\n";
    }

} catch (Throwable $e) {
    echo "\n\n✗ FATAL ERROR:\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
