<?php
/**
 * tools/verify_phase3.php
 * Automated End-to-End Smoke Test untuk Fase 3 SkillBridge
 *
 * Menguji:
 * 1. Admin menambah Master Skill, Master Career, dan Mapping
 * 2. Mahasiswa memilih Target Career, menambah My Skill, menambah 2 Evidence
 * 3. Verifikasi status skill otomatis: Growing (< 2 bukti) → Strong (>= 2 bukti)
 * 4. Verifikasi query gap.php mengembalikan have/explore yang akurat
 * 5. Rollback transaksi (cleanup)
 *
 * Jalankan: php tools/verify_phase3.php
 */

// Guard: hanya boleh dijalankan via CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: This script can only be run from command line');
}

require_once __DIR__ . '/../includes/config/database.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SkillBridge Phase 3 - End-to-End Smoke Test                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$results = [];
$pdo = null;

try {
    // Koneksi database
    echo "[1/6] Connecting to PostgreSQL...\n";
    $pdo = get_db_connection();

    if (!$pdo) {
        throw new Exception('❌ Database connection failed');
    }
    echo "   ✓ Connected to PostgreSQL\n\n";

    // Mulai transaksi untuk rollback di akhir
    $pdo->beginTransaction();

    // ============================================
    // TEST 1: Admin menambah Master Skill
    // ============================================
    echo "[2/6] Testing Admin: Add Master Skill...\n";
    $stmt = $pdo->prepare('
        INSERT INTO skills (name, category, description)
        VALUES (:name, :category, :description)
        RETURNING id, name
    ');
    $stmt->execute([
        ':name' => 'Test Skill PHP Native',
        ':category' => 'Programming',
        ':description' => 'Skill untuk smoke test'
    ]);
    $skill = $stmt->fetch(PDO::FETCH_ASSOC);
    $test_skill_id = (int)$skill['id'];

    if ($test_skill_id > 0) {
        echo "   ✓ Master Skill created: '{$skill['name']}' (ID: {$test_skill_id})\n";
        $results['skill_creation'] = 'PASS';
    } else {
        throw new Exception('Failed to create skill');
    }

    // ============================================
    // TEST 2: Admin menambah Master Career
    // ============================================
    echo "\n[3/6] Testing Admin: Add Master Career...\n";
    $stmt = $pdo->prepare('
        INSERT INTO careers (name, description)
        VALUES (:name, :description)
        RETURNING id, name
    ');
    $stmt->execute([
        ':name' => 'Test Career Smoke Test',
        ':description' => 'Career untuk verifikasi smoke test'
    ]);
    $career = $stmt->fetch(PDO::FETCH_ASSOC);
    $test_career_id = (int)$career['id'];

    if ($test_career_id > 0) {
        echo "   ✓ Master Career created: '{$career['name']}' (ID: {$test_career_id})\n";
        $results['career_creation'] = 'PASS';
    } else {
        throw new Exception('Failed to create career');
    }

    // ============================================
    // TEST 3: Admin menambah Career-Skill Mapping
    // ============================================
    echo "\n[4/6] Testing Admin: Add Career-Skill Mapping...\n";
    $stmt = $pdo->prepare('
        INSERT INTO career_skill_requirements (career_id, skill_id, is_mandatory)
        VALUES (:career_id, :skill_id, :is_mandatory)
    ');
    $stmt->execute([
        ':career_id' => $test_career_id,
        ':skill_id' => $test_skill_id,
        ':is_mandatory' => true
    ]);
    echo "   ✓ Mapping created: Career '{$career['name']}' requires '{$skill['name']}'\n";
    $results['mapping_creation'] = 'PASS';

    // ============================================
    // TEST 4: Mahasiswa memilih Target Career & menambah My Skill
    // ============================================
    echo "\n[5/6] Testing Mahasiswa: Set Target Career & Add My Skill...\n";

    // Ambil user test mahasiswa (role 'user' di DB)
    $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'user' LIMIT 1");
    $test_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$test_user) {
        throw new Exception('No test user found. Please create a user with role "user" first.');
    }

    $test_user_id = (int)$test_user['id'];
    echo "   • Using test user: {$test_user['name']} (ID: {$test_user_id})\n";

    // Set target career
    $stmt = $pdo->prepare('
        INSERT INTO student_profiles (user_id, target_career_id)
        VALUES (:user_id, :career_id)
        ON CONFLICT (user_id)
        DO UPDATE SET target_career_id = :career_id, updated_at = CURRENT_TIMESTAMP
    ');
    $stmt->execute([
        ':user_id' => $test_user_id,
        ':career_id' => $test_career_id
    ]);
    echo "   ✓ Target career set to: '{$career['name']}'\n";

    // Tambah skill ke user_skills
    $stmt = $pdo->prepare('
        INSERT INTO user_skills (user_id, skill_id, source, status)
        VALUES (:user_id, :skill_id, :source, :status)
        ON CONFLICT (user_id, skill_id) DO NOTHING
        RETURNING id
    ');
    $stmt->execute([
        ':user_id' => $test_user_id,
        ':skill_id' => $test_skill_id,
        ':source' => 'manual',
        ':status' => 'confirmed'
    ]);
    $user_skill = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_skill) {
        echo "   ✓ Skill added to user: '{$skill['name']}'\n";
        $results['skill_add'] = 'PASS';
    }

    // Verifikasi status skill SEBELUM evidence (harus Growing karena 0 bukti)
    $stmt = $pdo->prepare("
        SELECT
            us.skill_id,
            s.name AS skill_name,
            COUNT(DISTINCT es.evidence_id) AS evidence_count
        FROM user_skills us
        JOIN skills s ON s.id = us.skill_id
        LEFT JOIN evidence_skills es ON es.skill_id = us.skill_id
        LEFT JOIN evidence e ON e.id = es.evidence_id AND e.user_id = us.user_id
        WHERE us.user_id = :user_id AND us.skill_id = :skill_id
        GROUP BY us.skill_id, s.name
    ");
    $stmt->execute([':user_id' => $test_user_id, ':skill_id' => $test_skill_id]);
    $before = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_before = (int)$before['evidence_count'];
    $label_before = $count_before >= 2 ? 'Strong' : 'Growing';

    echo "   • Status BEFORE evidence: {$label_before} ({$count_before} evidence)\n";

    if ($label_before !== 'Growing') {
        throw new Exception("Expected 'Growing' but got '{$label_before}'");
    }

    // ============================================
    // TEST 5: Mahasiswa menambah 2 Evidence yang terhubung ke skill
    // ============================================
    echo "\n[6/6] Testing Mahasiswa: Add 2 Evidence (verify Growing → Strong)...\n";

    $evidence_ids = [];

    for ($i = 1; $i <= 2; $i++) {
        $stmt = $pdo->prepare('
            INSERT INTO evidence (user_id, source_type, title, file_url, metadata)
            VALUES (:user_id, :source_type, :title, :file_url, :metadata)
            RETURNING id
        ');
        $stmt->execute([
            ':user_id' => $test_user_id,
            ':source_type' => 'project',
            ':title' => "Test Evidence #{$i} for Smoke Test",
            ':file_url' => "https://example.com/evidence{$i}.pdf",
            ':metadata' => json_encode(['description' => "Evidence #{$i} description"])
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $evi_id = (int)$result['id'];
        $evidence_ids[] = $evi_id;

        // Link evidence ke skill
        $stmt = $pdo->prepare('
            INSERT INTO evidence_skills (evidence_id, skill_id)
            VALUES (:evidence_id, :skill_id)
        ');
        $stmt->execute([
            ':evidence_id' => $evi_id,
            ':skill_id' => $test_skill_id
        ]);

        echo "   ✓ Evidence #{$i} created and linked (ID: {$evi_id})\n";
    }

    $results['evidence_creation'] = 'PASS';

    // Verifikasi status skill SETELAH 2 evidence (harus Strong)
    $stmt->execute([':user_id' => $test_user_id, ':skill_id' => $test_skill_id]);
    $after = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_after = (int)$after['evidence_count'];
    $label_after = $count_after >= 2 ? 'Strong' : 'Growing';

    echo "   • Status AFTER 2 evidence: {$label_after} ({$count_after} evidence)\n";

    if ($label_after !== 'Strong' || $count_after !== 2) {
        throw new Exception("Expected 'Strong' with 2 evidence but got '{$label_after}' with {$count_after} evidence");
    }

    echo "   ✓ Skill status changed: Growing → Strong ✓\n";
    $results['status_transition'] = 'PASS';

    // ============================================
    // TEST 6: Verifikasi query gap.php (have & explore)
    // ============================================
    echo "\n[7/8] Testing gap.php query (have vs explore)...\n";

    // Query skill yang sudah dimiliki (have)
    $stmt = $pdo->prepare("
        SELECT
            vsg.skill_id,
            vsg.skill_name,
            COUNT(DISTINCT es.evidence_id) AS evidence_count
        FROM v_skill_gap vsg
        LEFT JOIN evidence_skills es ON es.skill_id = vsg.skill_id
        LEFT JOIN evidence e ON e.id = es.evidence_id AND e.user_id = vsg.user_id
        WHERE vsg.user_id = :user_id AND vsg.has_skill = TRUE
        GROUP BY vsg.skill_id, vsg.skill_name
    ");
    $stmt->execute([':user_id' => $test_user_id]);
    $have = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query skill gap (explore)
    $stmt = $pdo->prepare("
        SELECT skill_id, skill_name
        FROM v_skill_gap
        WHERE user_id = :user_id AND has_skill = FALSE
    ");
    $stmt->execute([':user_id' => $test_user_id]);
    $explore = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $found_in_have = false;
    foreach ($have as $h) {
        if ((int)$h['skill_id'] === $test_skill_id) {
            $found_in_have = true;
            echo "   ✓ Test skill found in 'have' list: {$h['skill_name']} ({$h['evidence_count']} evidence)\n";
        }
    }

    $found_in_explore = false;
    foreach ($explore as $e) {
        if ((int)$e['skill_id'] === $test_skill_id) {
            $found_in_explore = true;
        }
    }

    if (!$found_in_have) {
        throw new Exception("Test skill NOT found in 'have' list (v_skill_gap query issue)");
    }

    if ($found_in_explore) {
        throw new Exception("Test skill should NOT be in 'explore' list");
    }

    echo "   ✓ Gap analysis accurate: skill in 'have', not in 'explore'\n";
    $results['gap_query'] = 'PASS';

    // ============================================
    // TEST 7: Rollback (Cleanup)
    // ============================================
    echo "\n[8/8] Cleanup: Rolling back test data...\n";
    $pdo->rollBack();
    echo "   ✓ All test data rolled back successfully\n";
    $results['cleanup'] = 'PASS';

    // ============================================
    // FINAL REPORT
    // ============================================
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  SMOKE TEST RESULTS                                            ║\n";
    echo "╠════════════════════════════════════════════════════════════════╣\n";

    foreach ($results as $test => $status) {
        $icon = $status === 'PASS' ? '✓' : '✗';
        $label = str_pad($test, 30);
        echo "║  {$icon} {$label} {$status}                       ║\n";
    }

    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    $total = count($results);
    $passed = count(array_filter($results, fn($s) => $s === 'PASS'));

    if ($passed === $total) {
        echo "🎉 ALL TESTS PASSED ({$passed}/{$total})\n\n";
        exit(0);
    } else {
        echo "⚠️  SOME TESTS FAILED ({$passed}/{$total})\n\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "\n❌ SMOKE TEST FAILED\n";
    echo "   Error: {$e->getMessage()}\n\n";

    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
        echo "   ✓ Transaction rolled back\n\n";
    }

    exit(1);
}
