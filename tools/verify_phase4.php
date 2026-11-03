<?php
/**
 * tools/verify_phase4.php
 * Smoke Test untuk memverifikasi endpoint Fase 4
 *
 * Menguji:
 * 1. Endpoint HR: company.php, roles.php, candidates.php, activity.php, team.php
 * 2. Endpoint Admin: vacancies.php
 * 3. Endpoint CV: cv.php
 * 4. Halaman publik: p.php
 *
 * Jalankan: php tools/verify_phase4.php
 */

// Guard: hanya boleh dijalankan via CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: This script can only be run from command line');
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SkillBridge Phase 4 - Smoke Test                             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$results = [];
$files_to_check = [
    // HR API Endpoints
    'public/api/hr/company.php',
    'public/api/hr/roles.php',
    'public/api/hr/candidates.php',
    'public/api/hr/activity.php',
    'public/api/hr/team.php',

    // Admin API Endpoints
    'public/api/admin/vacancies.php',

    // CV API
    'public/api/cv.php',

    // Public Portfolio
    'public/p.php',

    // CV Pages
    'public/mahasiswa/cv.php',
    'public/mahasiswa/cv_review.php',

    // JS Files
    'public/js/pages/cv.js',
    'public/js/pages/cv_review.js',
];

echo "[1/3] Checking file existence...\n";
$missing = [];
foreach ($files_to_check as $file) {
    if (!file_exists(__DIR__ . '/../' . $file)) {
        $missing[] = $file;
        echo "   ✗ MISSING: {$file}\n";
    } else {
        echo "   ✓ {$file}\n";
    }
}

if (!empty($missing)) {
    echo "\n❌ FAILED: " . count($missing) . " file(s) missing\n\n";
    exit(1);
}

$results['file_existence'] = 'PASS';

echo "\n[2/3] Checking PHP syntax...\n";
$php_files = array_filter($files_to_check, function($f) {
    return pathinfo($f, PATHINFO_EXTENSION) === 'php';
});

$syntax_errors = [];
foreach ($php_files as $file) {
    $path = __DIR__ . '/../' . $file;
    $output = [];
    $return_var = 0;
    exec("php -l " . escapeshellarg($path) . " 2>&1", $output, $return_var);

    if ($return_var !== 0) {
        $syntax_errors[] = $file;
        echo "   ✗ SYNTAX ERROR: {$file}\n";
        echo "     " . implode("\n     ", $output) . "\n";
    } else {
        echo "   ✓ {$file}\n";
    }
}

if (!empty($syntax_errors)) {
    echo "\n❌ FAILED: " . count($syntax_errors) . " file(s) with syntax errors\n\n";
    exit(1);
}

$results['syntax_check'] = 'PASS';

echo "\n[3/3] Verifying database schema...\n";

require_once __DIR__ . '/../includes/config/database.php';

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Check HR tables
    $hr_tables = [
        'companies',
        'company_recruiters',
        'job_vacancies',
        'candidate_pipelines',
        'recruiter_activity_logs'
    ];

    foreach ($hr_tables as $table) {
        $stmt = $pdo->query("SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = '{$table}'
        )");
        $exists = $stmt->fetchColumn();

        if ($exists === 't' || $exists === true) {
            echo "   ✓ Table: {$table}\n";
        } else {
            echo "   ✗ MISSING TABLE: {$table}\n";
            throw new Exception("Table {$table} does not exist");
        }
    }

    $results['database_schema'] = 'PASS';

} catch (Exception $e) {
    echo "   ✗ Database error: {$e->getMessage()}\n";
    $results['database_schema'] = 'FAIL';
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SMOKE TEST RESULTS                                            ║\n";
echo "╠════════════════════════════════════════════════════════════════╣\n";

foreach ($results as $test => $status) {
    $icon = $status === 'PASS' ? '✓' : '✗';
    $label = str_pad($test, 30);
    $padding = str_repeat(' ', 32 - strlen($status));
    echo "║  {$icon} {$label} {$status}{$padding}║\n";
}

echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$total = count($results);
$passed = count(array_filter($results, fn($s) => $s === 'PASS'));

if ($passed === $total) {
    echo "🎉 ALL TESTS PASSED ({$passed}/{$total})\n\n";
    echo "Next steps:\n";
    echo "  1. Run seeder: php tools/seed_demo.php\n";
    echo "  2. Start server: php -S localhost:8000 -t public\n";
    echo "  3. Test in browser:\n";
    echo "     - Login as HR → view company dashboard\n";
    echo "     - Login as Mahasiswa → upload CV\n";
    echo "     - Login as Admin → moderate vacancies\n\n";
    exit(0);
} else {
    echo "⚠️  SOME TESTS FAILED ({$passed}/{$total})\n\n";
    exit(1);
}
