<?php
// tools/test-db.php - File test koneksi database
// Guard: hanya boleh dijalankan via CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: This script can only be run from command line');
}

require_once __DIR__ . '/../includes/config/database.php';

echo "<h2>Database Connection Test</h2>";

// Cek environment variables
echo "<h3>Environment Variables:</h3>";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NOT SET (using default: 127.0.0.1)') . "<br>";
echo "DB_PORT: " . (getenv('DB_PORT') ?: 'NOT SET (using default: 5432)') . "<br>";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'NOT SET (using default: postgres)') . "<br>";
echo "DB_USER: " . (getenv('DB_USER') ?: 'NOT SET (using default: postgres)') . "<br>";
echo "DB_SSLMODE: " . (getenv('DB_SSLMODE') ?: 'NOT SET (using default: prefer)') . "<br>";

// Test koneksi
echo "<h3>Connection Test:</h3>";
$pdo = get_db_connection();

if ($pdo) {
    echo "<span style='color: green; font-weight: bold;'>✓ Database connected successfully!</span><br>";

    // Cek apakah tabel users ada
    try {
        $stmt = $pdo->query("SELECT to_regclass('users')");
        $result = $stmt->fetchColumn();

        if ($result) {
            echo "<span style='color: green;'>✓ Table 'users' exists</span><br>";

            // Cek jumlah users
            $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            echo "Total users in database: <strong>$count</strong><br>";
        } else {
            echo "<span style='color: red;'>✗ Table 'users' NOT found - You need to run schema.sql!</span><br>";
        }

        // Cek tabel careers
        $stmt = $pdo->query("SELECT to_regclass('careers')");
        if ($stmt->fetchColumn()) {
            echo "<span style='color: green;'>✓ Table 'careers' exists</span><br>";
        } else {
            echo "<span style='color: red;'>✗ Table 'careers' NOT found</span><br>";
        }

        // Cek tabel skills
        $stmt = $pdo->query("SELECT to_regclass('skills')");
        if ($stmt->fetchColumn()) {
            echo "<span style='color: green;'>✓ Table 'skills' exists</span><br>";
        } else {
            echo "<span style='color: red;'>✗ Table 'skills' NOT found</span><br>";
        }

    } catch (PDOException $e) {
        echo "<span style='color: red;'>Error checking tables: " . e($e->getMessage()) . "</span><br>";
    }
} else {
    echo "<span style='color: red; font-weight: bold;'>✗ Database connection FAILED</span><br>";
    echo "<p style='color: red;'>Check your .env file or environment variables.</p>";
}

echo "<hr>";
echo "<p><strong>Next steps if connection fails:</strong></p>";
echo "<ol>";
echo "<li>Pastikan PostgreSQL sudah running di komputer kamu</li>";
echo "<li>Cek file <code>.env</code> di root project sudah benar</li>";
echo "<li>Pastikan database <code>skillbridge</code> sudah dibuat</li>";
echo "<li>Jalankan <code>schema.sql</code> dan <code>seed.sql</code></li>";
echo "</ol>";
echo "<p style='color: red; font-weight: bold;'><strong>IMPORTANT: Delete this file (test-db.php) after testing!</strong></p>";
?>
