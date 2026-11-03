<?php
/**
 * tools/reinstall_schema.php
 * Re-apply schema.sql ke database (hati-hati: DROP TABLE IF EXISTS)
 *
 * Usage: php tools/reinstall_schema.php
 */

// Guard: hanya boleh dijalankan via CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: This script can only be run from command line');
}

require_once __DIR__ . '/../includes/config/database.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SkillBridge Schema Re-installer                               ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "⚠️  WARNING: This will DROP and recreate all tables!\n";
echo "⚠️  All data will be LOST!\n\n";
echo "Type 'YES' to continue: ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if ($confirmation !== 'YES') {
    echo "\n❌ Cancelled.\n\n";
    exit(0);
}

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        throw new Exception('❌ Database connection failed');
    }

    echo "\n✓ Connected to PostgreSQL\n\n";

    // Read schema.sql
    $schemaFile = __DIR__ . '/../db/schema.sql';

    if (!file_exists($schemaFile)) {
        throw new Exception("❌ Schema file not found: $schemaFile");
    }

    $sql = file_get_contents($schemaFile);

    echo "[1/2] Executing schema.sql...\n";

    // Execute schema
    $pdo->exec($sql);

    echo "✓ Schema installed successfully\n\n";

    // Verify tables
    echo "[2/2] Verifying tables...\n\n";

    $tables = [
        'users',
        'student_profiles',
        'skills',
        'careers',
        'career_skill_requirements',
        'evidence',
        'user_skills',
        'evidence_skills',
        'companies',
        'company_recruiters',
        'job_vacancies',
        'candidate_pipelines',
        'recruiter_activity_logs'
    ];

    $allExist = true;

    foreach ($tables as $table) {
        $stmt = $pdo->prepare("
            SELECT EXISTS (
                SELECT FROM information_schema.tables
                WHERE table_schema = 'public'
                AND table_name = :table
            )
        ");
        $stmt->execute([':table' => $table]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            echo "  ✓ $table\n";
        } else {
            echo "  ✗ $table (MISSING)\n";
            $allExist = false;
        }
    }

    echo "\n";

    if ($allExist) {
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║  ✓ Schema installation successful!                            ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";
        echo "Next steps:\n";
        echo "  1. Run seed: php tools/seed_demo.php\n";
        echo "  2. Create admin: php tools/create_admin.php\n\n";
    } else {
        echo "❌ Some tables are missing. Check schema.sql for errors.\n\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
