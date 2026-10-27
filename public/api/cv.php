<?php
// public/api/cv.php
// POST: Extract skills dari CV content
// PUT: Confirm & save CV as evidence dengan linked skills
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
    echo json_encode(['error' => 'Forbidden - Student access only']);
    exit;
}

$user_id = $user['id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    // POST: Extract skills dari CV content
    if ($method === 'POST') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $title = trim($input['title'] ?? '');
        $url = trim($input['url'] ?? '');
        $content = trim($input['content'] ?? '');

        if (!$title || !$content) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and content are required']);
            exit;
        }

        // Ambil semua skills dari database
        $stmt = $pdo->query('SELECT id, name, category FROM skills ORDER BY name');
        $all_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Simple keyword matching (case-insensitive)
        $content_lower = mb_strtolower($content);
        $detected = [];

        foreach ($all_skills as $skill) {
            $skill_name_lower = mb_strtolower($skill['name']);

            // Check if skill name appears in content
            if (mb_strpos($content_lower, $skill_name_lower) !== false) {
                $detected[] = [
                    'id' => (int)$skill['id'],
                    'name' => $skill['name'],
                    'category' => $skill['category']
                ];
            }
        }

        // Store di session untuk cv_review.php
        $_SESSION['cv_detected_skills'] = $detected;
        $_SESSION['cv_upload_data'] = [
            'title' => $title,
            'url' => $url,
            'content' => $content
        ];

        echo json_encode([
            'success' => true,
            'detected' => $detected,
            'count' => count($detected)
        ]);
        exit;
    }

    // PUT: Confirm & save CV as evidence
    if ($method === 'PUT') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $title = trim($input['title'] ?? '');
        $url = trim($input['url'] ?? '');
        $content = trim($input['content'] ?? '');
        $skill_ids = $input['skill_ids'] ?? [];

        if (!$title || !$content) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and content are required']);
            exit;
        }

        if (empty($skill_ids) || !is_array($skill_ids)) {
            http_response_code(400);
            echo json_encode(['error' => 'At least one skill must be selected']);
            exit;
        }

        // 1. Save CV as evidence
        $stmt = $pdo->prepare('
            INSERT INTO evidence (user_id, source_type, title, file_url, metadata)
            VALUES (:user_id, :source_type, :title, :file_url, :metadata)
            RETURNING id
        ');
        $stmt->execute([
            ':user_id' => $user_id,
            ':source_type' => 'cv',
            ':title' => $title,
            ':file_url' => $url,
            ':metadata' => json_encode([
                'description' => 'CV uploaded and processed for skill extraction',
                'content_summary' => mb_substr($content, 0, 500)
            ])
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $evidence_id = (int)$result['id'];

        // 2. Add skills to user_skills (status: candidate)
        $stmt_skill = $pdo->prepare('
            INSERT INTO user_skills (user_id, skill_id, source, status)
            VALUES (:user_id, :skill_id, :source, :status)
            ON CONFLICT (user_id, skill_id)
            DO UPDATE SET updated_at = CURRENT_TIMESTAMP
            RETURNING id
        ');

        $added_count = 0;
        foreach ($skill_ids as $skill_id) {
            $skill_id = (int)$skill_id;

            $stmt_skill->execute([
                ':user_id' => $user_id,
                ':skill_id' => $skill_id,
                ':source' => 'cv',
                ':status' => 'candidate'
            ]);

            // Link evidence to skill
            $stmt_link = $pdo->prepare('
                INSERT INTO evidence_skills (evidence_id, skill_id)
                VALUES (:evidence_id, :skill_id)
                ON CONFLICT DO NOTHING
            ');
            $stmt_link->execute([
                ':evidence_id' => $evidence_id,
                ':skill_id' => $skill_id
            ]);

            $added_count++;
        }

        // Clear session data
        unset($_SESSION['cv_detected_skills']);
        unset($_SESSION['cv_upload_data']);

        echo json_encode([
            'success' => true,
            'evidence_id' => $evidence_id,
            'skills_added' => $added_count,
            'message' => "CV saved and {$added_count} skills added to your profile"
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API cv.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
