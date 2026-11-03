<?php
// public/api/candidates.php
// GET: Ambil pipeline kandidat mahasiswa untuk HR/Admin dari PostgreSQL
require_once __DIR__ . '/../../includes/auth/auth.php';
require_once __DIR__ . '/../../includes/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Wajib login
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = current_user();
// Hanya HR dan Admin yang boleh mengakses pipeline kandidat
if (!in_array($user['role'], ['hr', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - HR/Admin access only']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    // Ambil semua mahasiswa (role 'user' di DB)
    $stmt = $pdo->query("
        SELECT
            u.id,
            u.name,
            u.email,
            COALESCE(c.name, 'Belum Ditentukan') AS target_role,
            COUNT(DISTINCT e.id) AS evidence_count
        FROM users u
        LEFT JOIN student_profiles sp ON sp.user_id = u.id
        LEFT JOIN careers c ON c.id = sp.target_career_id
        LEFT JOIN evidence e ON e.user_id = u.id
        WHERE u.role = 'user'
        GROUP BY u.id, u.name, u.email, c.name
        ORDER BY u.name ASC
    ");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil matched_skills dan gap_skills untuk setiap mahasiswa
    $candidates = [];

    // Query matched skills per user dari user_skills (fallback jika target belum ada)
    $stmtUserSkills = $pdo->prepare("
        SELECT s.name
        FROM user_skills us
        JOIN skills s ON s.id = us.skill_id
        WHERE us.user_id = :user_id AND us.status = 'confirmed'
        ORDER BY s.name
    ");

    // Query gap skills dari v_skill_gap
    $stmtGapSkills = $pdo->prepare("
        SELECT skill_name
        FROM v_skill_gap
        WHERE user_id = :user_id AND has_skill = FALSE
        ORDER BY skill_name
    ");

    // Query matched skills yang sesuai target dari v_skill_gap
    $stmtMatchedSkills = $pdo->prepare("
        SELECT skill_name
        FROM v_skill_gap
        WHERE user_id = :user_id AND has_skill = TRUE
        ORDER BY skill_name
    ");

    foreach ($students as $s) {
        $uid = (int)$s['id'];

        $stmtMatchedSkills->execute([':user_id' => $uid]);
        $matched = $stmtMatchedSkills->fetchAll(PDO::FETCH_COLUMN);

        $stmtGapSkills->execute([':user_id' => $uid]);
        $gaps = $stmtGapSkills->fetchAll(PDO::FETCH_COLUMN);

        // Jika belum ada target karier, ambil seluruh skill yang dimiliki sebagai matched
        if (empty($matched) && $s['target_role'] === 'Belum Ditentukan') {
            $stmtUserSkills->execute([':user_id' => $uid]);
            $matched = $stmtUserSkills->fetchAll(PDO::FETCH_COLUMN);
        }

        $candidates[] = [
            'id' => $uid,
            'name' => $s['name'],
            'email' => $s['email'],
            'target_role' => $s['target_role'],
            'matched_skills' => $matched ?: [],
            'gap_skills' => $gaps ?: [],
            'evidence_count' => (int)$s['evidence_count'],
            'status' => 'active'
        ];
    }

    echo json_encode($candidates, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('API candidates.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
