<?php
// public/api/hr/candidate.php
// GET: Detail kandidat publik untuk HR (read-only).
require_once __DIR__ . '/../../../includes/auth/auth.php';
require_once __DIR__ . '/../../../includes/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Wajib login sebagai HR
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = current_user();
if ($user['role'] !== 'hr') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - HR access only']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$candidate_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($candidate_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'id is required']);
    exit;
}

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    // Hanya profil publik; detail tidak pernah membuka profil privat.
    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.name,
            u.email,
            COALESCE(c.name, 'Belum Ditentukan') AS target_role
        FROM users u
        JOIN student_profiles sp ON sp.user_id = u.id AND sp.is_public = TRUE
        LEFT JOIN careers c ON c.id = sp.target_career_id
        WHERE u.id = :id AND u.role = 'user'
        LIMIT 1
    ");
    $stmt->execute([':id' => $candidate_id]);
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidate) {
        http_response_code(404);
        echo json_encode(['error' => 'Candidate not found']);
        exit;
    }

    // Hanya skill berstatus confirmed.
    $stmt = $pdo->prepare("
        SELECT s.name
        FROM user_skills us
        JOIN skills s ON s.id = us.skill_id
        WHERE us.user_id = :id AND us.status = 'confirmed'
        ORDER BY s.name
    ");
    $stmt->execute([':id' => $candidate_id]);
    $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Evidence: hanya field yang aman untuk ditampilkan.
    $stmt = $pdo->prepare("
        SELECT id, title, source_type, file_url, created_at
        FROM evidence
        WHERE user_id = :id
        ORDER BY created_at DESC, id DESC
    ");
    $stmt->execute([':id' => $candidate_id]);
    $evidence = array_map(static function ($row) {
        return [
            'id'         => (int) $row['id'],
            'title'      => $row['title'],
            'type'       => $row['source_type'],
            'url'        => $row['file_url'],
            'created_at' => $row['created_at'],
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    echo json_encode([
        'candidate' => [
            'id'          => (int) $candidate['id'],
            'name'        => $candidate['name'],
            'email'       => $candidate['email'],
            'target_role' => $candidate['target_role'],
        ],
        'skills'   => $skills,
        'evidence' => $evidence,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('API hr/candidate.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
