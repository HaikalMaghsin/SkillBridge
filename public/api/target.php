<?php
// public/api/target.php
// GET: Ambil target_career_id dari student_profiles
// POST: Update target_career_id
require_once __DIR__ . '/../../includes/auth/auth.php';
require_once __DIR__ . '/../../includes/auth/csrf.php';
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

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    // GET: Ambil target_career_id
    if ($method === 'GET') {
        $stmt = $pdo->prepare('
            SELECT target_career_id
            FROM student_profiles
            WHERE user_id = :user_id
        ');
        $stmt->execute([':user_id' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $target_id = $row ? (int)$row['target_career_id'] : null;

        echo json_encode(['target_career_id' => $target_id]);
        exit;
    }

    // POST: Update target_career_id
    if ($method === 'POST') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $career_id = isset($input['career_id']) ? (int)$input['career_id'] : null;

        if (!$career_id) {
            http_response_code(400);
            echo json_encode(['error' => 'career_id required']);
            exit;
        }

        // Validasi career_id exists
        $stmt = $pdo->prepare('SELECT id FROM careers WHERE id = :career_id');
        $stmt->execute([':career_id' => $career_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Career not found']);
            exit;
        }

        // UPSERT target_career_id
        $stmt = $pdo->prepare('
            INSERT INTO student_profiles (user_id, target_career_id)
            VALUES (:user_id, :career_id)
            ON CONFLICT (user_id)
            DO UPDATE SET target_career_id = :career_id, updated_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([
            ':user_id' => $user_id,
            ':career_id' => $career_id
        ]);

        echo json_encode([
            'success' => true,
            'target_career_id' => $career_id,
            'message' => 'Target career updated'
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API target.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
