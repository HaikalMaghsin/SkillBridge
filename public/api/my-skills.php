<?php
// public/api/my-skills.php
// GET: Ambil skill mahasiswa + evidence_count
// POST: Tambah skill baru
// DELETE: Hapus skill
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

    // GET: Ambil daftar skill milik user + evidence_count
    if ($method === 'GET') {
        $stmt = $pdo->prepare('
            SELECT
                us.id,
                us.skill_id,
                s.name AS skill_name,
                s.category,
                us.source,
                us.status,
                COUNT(DISTINCT es.evidence_id) AS evidence_count,
                us.created_at
            FROM user_skills us
            JOIN skills s ON s.id = us.skill_id
            LEFT JOIN evidence_skills es ON es.skill_id = us.skill_id
            LEFT JOIN evidence e ON e.id = es.evidence_id AND e.user_id = us.user_id
            WHERE us.user_id = :user_id AND us.status = :status
            GROUP BY us.id, us.skill_id, s.name, s.category, us.source, us.status, us.created_at
            ORDER BY us.created_at DESC
        ');
        $stmt->execute([':user_id' => $user_id, ':status' => 'confirmed']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $skills = array_map(function($row) {
            $count = (int)$row['evidence_count'];
            return [
                'id' => (int)$row['id'],
                'skill_id' => (int)$row['skill_id'],
                'name' => $row['skill_name'],
                'category' => $row['category'],
                'source' => $row['source'],
                'status' => $row['status'],
                'evidence_count' => $count,
                'label' => $count >= 2 ? 'Strong' : 'Growing',
                'created_at' => $row['created_at']
            ];
        }, $rows);

        echo json_encode($skills, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // POST: Tambah skill baru
    if ($method === 'POST') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $skill_id = isset($input['skill_id']) ? (int)$input['skill_id'] : null;

        if (!$skill_id) {
            http_response_code(400);
            echo json_encode(['error' => 'skill_id required']);
            exit;
        }

        // Cek apakah skill_id valid
        $stmt = $pdo->prepare('SELECT id FROM skills WHERE id = :skill_id');
        $stmt->execute([':skill_id' => $skill_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Skill not found']);
            exit;
        }

        // Insert skill (ON CONFLICT ignore jika sudah ada)
        $stmt = $pdo->prepare('
            INSERT INTO user_skills (user_id, skill_id, source, status)
            VALUES (:user_id, :skill_id, :source, :status)
            ON CONFLICT (user_id, skill_id) DO NOTHING
            RETURNING id
        ');
        $stmt->execute([
            ':user_id' => $user_id,
            ':skill_id' => $skill_id,
            ':source' => 'manual',
            ':status' => 'confirmed'
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'id' => $result ? (int)$result['id'] : null,
            'message' => 'Skill added'
        ]);
        exit;
    }

    // DELETE: Hapus skill
    if ($method === 'DELETE') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $skill_id = isset($input['skill_id']) ? (int)$input['skill_id'] : null;

        if (!$skill_id) {
            http_response_code(400);
            echo json_encode(['error' => 'skill_id required']);
            exit;
        }

        $stmt = $pdo->prepare('
            DELETE FROM user_skills
            WHERE user_id = :user_id AND skill_id = :skill_id
        ');
        $stmt->execute([':user_id' => $user_id, ':skill_id' => $skill_id]);

        echo json_encode(['success' => true, 'message' => 'Skill removed']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API my-skills.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
