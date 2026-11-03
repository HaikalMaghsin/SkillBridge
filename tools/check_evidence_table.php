<?php
/**
 * tools/check_evidence_table.php
 * Quick check: apakah tabel evidence & evidence_skills ada di database
 */

// Guard: hanya boleh dijalankan via CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: This script can only be run from command line');
}

require_once __DIR__ . '/../includes/config/database.php';

echo "\n=== Evidence Table Check ===\n\n";

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        echo "❌ Database connection failed\n";
        exit(1);
    }

    echo "✓ Connected to PostgreSQL\n\n";

    // Check tabel evidence
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = 'evidence'
        )
    ");
    $evidenceExists = $stmt->fetchColumn();

    echo "Table 'evidence': " . ($evidenceExists ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";

    // Check tabel evidence_skills
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = 'evidence_skills'
        )
    ");
    $evidenceSkillsExists = $stmt->fetchColumn();

    echo "Table 'evidence_skills': " . ($evidenceSkillsExists ? "✓ EXISTS" : "✗ NOT FOUND") . "\n\n";

    if ($evidenceExists) {
        // Check struktur kolom
        $stmt = $pdo->query("
            SELECT column_name, data_type
            FROM information_schema.columns
            WHERE table_name = 'evidence'
            ORDER BY ordinal_position
        ");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Columns in 'evidence' table:\n";
        foreach ($columns as $col) {
            echo "  - {$col['column_name']}: {$col['data_type']}\n";
        }
        echo "\n";

        // Count records
        $stmt = $pdo->query("SELECT COUNT(*) FROM evidence");
        $count = $stmt->fetchColumn();
        echo "Total evidence records: $count\n";
    }

    if ($evidenceSkillsExists) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM evidence_skills");
        $count = $stmt->fetchColumn();
        echo "Total evidence_skills records: $count\n";
    }

    echo "\n=== Check Complete ===\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
