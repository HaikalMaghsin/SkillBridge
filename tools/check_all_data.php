<?php
/**
 * tools/check_all_data.php
 * Cek semua data di database - mana yang ada, mana yang kosong
 */

// Guard: hanya boleh dijalankan via CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: This script can only be run from command line');
}

require_once __DIR__ . '/../includes/config/database.php';

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Database Content Check                                        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        throw new Exception('❌ Database connection failed');
    }

    echo "✓ Connected to PostgreSQL\n\n";

    // Tables to check
    $tables = [
        'Master Data' => [
            'users' => 'User accounts (mahasiswa, HR, admin)',
            'skills' => 'Katalog skill master',
            'careers' => 'Katalog karier master',
            'career_skill_requirements' => 'Mapping karier ke skill',
            'companies' => 'Data perusahaan'
        ],
        'User Data' => [
            'student_profiles' => 'Profil mahasiswa',
            'user_skills' => 'Skills yang dimiliki mahasiswa',
            'evidence' => 'Bukti kompetensi mahasiswa',
            'evidence_skills' => 'Relasi evidence-skill'
        ],
        'HR Data' => [
            'company_recruiters' => 'HR terkait perusahaan',
            'job_vacancies' => 'Lowongan pekerjaan',
            'candidate_pipelines' => 'Pipeline kandidat',
            'recruiter_activity_logs' => 'Log aktivitas HR'
        ]
    ];

    foreach ($tables as $category => $categoryTables) {
        echo "=== $category ===\n";

        foreach ($categoryTables as $table => $description) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();

            $status = $count > 0 ? "✓" : "✗";
            $color = $count > 0 ? "" : " (EMPTY)";

            printf("  %s %-30s %5d rows%s\n", $status, $table . ':', $count, $color);
        }

        echo "\n";
    }

    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  Diagnosis                                                     ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // Check specific issues
    $stmt = $pdo->query("SELECT COUNT(*) FROM skills");
    $skillCount = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM careers");
    $careerCount = $stmt->fetchColumn();

    if ($skillCount == 0) {
        echo "❌ PROBLEM: Tabel 'skills' kosong!\n";
        echo "   → Solusi: Jalankan 'php tools/seed_demo.php' atau import db/seed.sql\n\n";
    }

    if ($careerCount == 0) {
        echo "❌ PROBLEM: Tabel 'careers' kosong!\n";
        echo "   → Solusi: Jalankan 'php tools/seed_demo.php' atau import db/seed.sql\n\n";
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $mahasiswaCount = $stmt->fetchColumn();

    if ($mahasiswaCount > 0) {
        echo "✓ Ada $mahasiswaCount mahasiswa terdaftar\n";

        $stmt = $pdo->query("SELECT COUNT(*) FROM student_profiles");
        $profileCount = $stmt->fetchColumn();

        if ($profileCount < $mahasiswaCount) {
            echo "⚠️  WARNING: $mahasiswaCount mahasiswa tapi hanya $profileCount profil!\n";
            echo "   → Solusi: Jalankan 'php tools/ensure_student_profile.php'\n\n";
        }
    }

    echo "\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
