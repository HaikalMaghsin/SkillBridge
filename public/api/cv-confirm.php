<?php
// public/api/cv-confirm.php
// POST: Confirm atau reject skill hasil ekstraksi CV
require_once __DIR__ . '/../../includes/auth/auth.php';
require_once __DIR__ . '/../../includes/auth/csrf.php';
require_once __DIR__ . '/../../includes/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Wajib login sebagai mahasiswa
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = current_user();
if ($user['role'] !== 'mahasiswa') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!csrf_verify()) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    $skill_id = isset($input['skill_id']) ? (int)$input['skill_id'] : null;
    $action = trim($input['action'] ?? '');

    if (!$skill_id || !in_array($action, ['confirm', 'reject'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid skill_id or action']);
        exit;
    }

    $user_id = $user['id'];

    if ($action === 'confirm') {
        // Update status dari 'candidate' ke 'confirmed'
        $stmt = $pdo->prepare('
            UPDATE user_skills
            SET status = :new_status, updated_at = CURRENT_TIMESTAMP
            WHERE user_id = :user_id
              AND skill_id = :skill_id
              AND source = :source
              AND status = :old_status
        ');
        $stmt->execute([
            ':new_status' => 'confirmed',
            ':user_id' => $user_id,
            ':skill_id' => $skill_id,
            ':source' => 'cv_extracted',
            ':old_status' => 'candidate'
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Skill confirmed'
        ]);
    } else {
        // Reject: hapus skill candidate
        $stmt = $pdo->prepare('
            DELETE FROM user_skills
            WHERE user_id = :user_id
              AND skill_id = :skill_id
              AND source = :source
              AND status = :status
        ');
        $stmt->execute([
            ':user_id' => $user_id,
            ':skill_id' => $skill_id,
            ':source' => 'cv_extracted',
            ':status' => 'candidate'
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Skill rejected'
        ]);
    }

} catch (Exception $e) {
    error_log('API cv-confirm.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
