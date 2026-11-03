<?php
/**
 * tools/clean_test_data.php
 * Bersihkan data testing (evidence, user_skills, dll) tapi KEEP users & master data
 *
 * Usage: php tools/clean_test_data.php
 */

// Guard: hanya boleh dijalankan via CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: This script can only be run from command line');
}

require_once __DIR__ . '/../includes/config/database.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SkillBridge Data Cleaner                                      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "⚠️  WARNING: This will DELETE all user-generated data!\n\n";
echo "Data yang akan dihapus:\n";
echo "  - Evidence (bukti kompetensi)\n";
echo "  - User Skills (skills mahasiswa)\n";
echo "  - Candidate Pipelines (HR tracking)\n";
echo "  - Recruiter Activity Logs\n\n";
echo "Data yang TETAP ADA:\n";
echo "  ✓ Users (akun mahasiswa, HR, admin)\n";
echo "  ✓ Student Profiles\n";
echo "  ✓ Skills (katalog skill)\n";
echo "  ✓ Careers (katalog karier)\n";
echo "  ✓ Companies (data perusahaan)\n\n";

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

    // Array of tables to clean
    $tables = [
        'recruiter_activity_logs' => 'Recruiter activity logs',
        'candidate_pipelines' => 'Candidate pipelines',
        'evidence_skills' => 'Evidence-skill relations',
        'evidence' => 'Evidence items',
        'user_skills' => 'User skills'
    ];

    echo "Cleaning data...\n\n";

    foreach ($tables as $table => $description) {
        // Count before
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $before = $stmt->fetchColumn();

        // Delete
        $pdo->exec("DELETE FROM $table");

        echo "  - $description: $before records deleted\n";
    }

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ✓ Data cleaned successfully!                                  ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // Show remaining data
    echo "Remaining data:\n";

    $counts = [
        'users' => 'Users',
        'student_profiles' => 'Student profiles',
        'skills' => 'Skills',
        'careers' => 'Careers',
        'companies' => 'Companies',
        'job_vacancies' => 'Job vacancies'
    ];

    foreach ($counts as $table => $label) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "  ✓ $label: $count\n";
    }

    echo "\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
