<?php
/**
 * tools/import_schema.php
 * Import schema.sql dan seed.sql ke database via PHP
 *
 * Usage: php tools/import_schema.php
 */

// Guard: hanya boleh dijalankan via CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: This script can only be run from command line');
}

require_once __DIR__ . '/../includes/config/database.php';

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Database Schema Importer                                      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        throw new Exception('❌ Database connection failed');
    }

    echo "✓ Connected to: " . DB_NAME . " at " . DB_HOST . "\n\n";

    // Import schema.sql
    echo "[1/2] Importing schema.sql...\n";
    $schemaFile = __DIR__ . '/../db/schema.sql';

    if (!file_exists($schemaFile)) {
        throw new Exception("❌ File not found: $schemaFile");
    }

    $sql = file_get_contents($schemaFile);

    try {
        $pdo->exec($sql);
        echo "✓ Schema imported successfully\n\n";
    } catch (Exception $e) {
        // Ignore "already exists" errors
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠️  Schema already exists (skipped)\n\n";
        } else {
            throw $e;
        }
    }

    // Import seed.sql
    echo "[2/2] Importing seed.sql...\n";
    $seedFile = __DIR__ . '/../db/seed.sql';

    if (!file_exists($seedFile)) {
        echo "⚠️  seed.sql not found (skipped)\n\n";
    } else {
        $sql = file_get_contents($seedFile);

        try {
            $pdo->exec($sql);
            echo "✓ Seed data imported successfully\n\n";
        } catch (Exception $e) {
            // Ignore duplicate key errors
            if (strpos($e->getMessage(), 'duplicate key') !== false ||
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠️  Seed data already exists (skipped)\n\n";
            } else {
                throw $e;
            }
        }
    }

    // Verify
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  Verification                                                  ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    $tables = ['users', 'skills', 'careers', 'user_skills', 'evidence'];

    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        printf("  ✓ %-20s %d rows\n", $table . ':', $count);
    }

    echo "\n✓ Database ready!\n\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
