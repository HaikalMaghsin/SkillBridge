<?php
// public/api/gap.php
// GET: Ambil skill gap mahasiswa (skills you have vs what to explore)
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
$user_id = $user['id'];

// Hanya mahasiswa yang boleh akses endpoint ini
if ($user['role'] !== 'mahasiswa') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    // Ambil target career
    $stmt = $pdo->prepare('
        SELECT
            c.id,
            c.name,
            c.description
        FROM student_profiles sp
        JOIN careers c ON c.id = sp.target_career_id
        WHERE sp.user_id = :user_id
    ');
    $stmt->execute([':user_id' => $user_id]);
    $career = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$career) {
        echo json_encode([
            'career' => null,
            'have' => [],
            'explore' => []
        ]);
        exit;
    }

    $career = [
        'id' => (int)$career['id'],
        'name' => $career['name'],
        'description' => $career['description']
    ];

    // Ambil skill yang sudah dimiliki (dari view v_skill_gap)
    $stmt = $pdo->prepare('
        SELECT
            vsg.skill_id AS id,
            vsg.skill_name AS name,
            s.category,
            COUNT(DISTINCT es.evidence_id) AS evidence_count
        FROM v_skill_gap vsg
        JOIN skills s ON s.id = vsg.skill_id
        LEFT JOIN evidence_skills es ON es.skill_id = vsg.skill_id
        LEFT JOIN evidence e ON e.id = es.evidence_id AND e.user_id = vsg.user_id
        WHERE vsg.user_id = :user_id AND vsg.has_skill = TRUE
        GROUP BY vsg.skill_id, vsg.skill_name, s.category
        ORDER BY s.name
    ');
    $stmt->execute([':user_id' => $user_id]);
    $have = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil skill yang belum dimiliki (skill gap)
    $stmt = $pdo->prepare('
        SELECT
            vsg.skill_id AS id,
            vsg.skill_name AS name,
            s.category
        FROM v_skill_gap vsg
        JOIN skills s ON s.id = vsg.skill_id
        WHERE vsg.user_id = :user_id AND vsg.has_skill = FALSE
        ORDER BY s.name
    ');
    $stmt->execute([':user_id' => $user_id]);
    $explore = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transform hasil
    $have = array_map(function($row) {
        return [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'category' => $row['category'],
            'evidence_count' => (int)$row['evidence_count']
        ];
    }, $have);

    $explore = array_map(function($row) {
        return [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'category' => $row['category']
        ];
    }, $explore);

    echo json_encode([
        'career' => $career,
        'have' => $have,
        'explore' => $explore
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('API gap.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
