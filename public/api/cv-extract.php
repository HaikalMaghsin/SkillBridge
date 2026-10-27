<?php
// public/api/cv-extract.php
// POST: Extract skills dari teks CV (keyword matching)
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

    $cv_text = trim($input['cv_text'] ?? '');
    $cv_title = trim($input['cv_title'] ?? '');
    $cv_url = trim($input['cv_url'] ?? '');

    if (!$cv_text) {
        http_response_code(400);
        echo json_encode(['error' => 'CV text is required']);
        exit;
    }

    // Normalisasi teks: lowercase untuk matching
    $cv_text_lower = mb_strtolower($cv_text, 'UTF-8');

    // Ambil semua skills dari database
    $stmt = $pdo->query('SELECT id, name, category FROM skills ORDER BY LENGTH(name) DESC');
    $all_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Keyword matching: cari skill yang ada di CV
    $matched_skills = [];
    foreach ($all_skills as $skill) {
        $skill_name_lower = mb_strtolower($skill['name'], 'UTF-8');

        // Match whole word atau dengan boundary
        // Contoh: "JavaScript" match di "I know JavaScript" tapi tidak di "JavaScripter"
        $pattern = '/\b' . preg_quote($skill_name_lower, '/') . '\b/u';

        if (preg_match($pattern, $cv_text_lower)) {
            $matched_skills[] = [
                'id' => (int)$skill['id'],
                'name' => $skill['name'],
                'category' => $skill['category']
            ];
        }
    }

    // Simpan CV sebagai evidence
    $user_id = $user['id'];

    $stmt = $pdo->prepare('
        INSERT INTO evidence (user_id, source_type, title, file_url, metadata)
        VALUES (:user_id, :source_type, :title, :file_url, :metadata)
        RETURNING id
    ');
    $stmt->execute([
        ':user_id' => $user_id,
        ':source_type' => 'cv',
        ':title' => $cv_title ?: 'My CV',
        ':file_url' => $cv_url ?: null,
        ':metadata' => json_encode([
            'description' => 'CV uploaded via CV page',
            'extracted_at' => date('Y-m-d H:i:s'),
            'skills_found' => count($matched_skills)
        ])
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $evidence_id = (int)$result['id'];

    // Simpan matched skills sebagai candidate
    $skill_ids = [];
    foreach ($matched_skills as $skill) {
        // Insert ke user_skills dengan status 'candidate'
        $stmt = $pdo->prepare('
            INSERT INTO user_skills (user_id, skill_id, source, status)
            VALUES (:user_id, :skill_id, :source, :status)
            ON CONFLICT (user_id, skill_id) DO NOTHING
            RETURNING id
        ');
        $stmt->execute([
            ':user_id' => $user_id,
            ':skill_id' => $skill['id'],
            ':source' => 'cv_extracted',
            ':status' => 'candidate'
        ]);

        $skill_ids[] = $skill['id'];

        // Link ke evidence
        $stmt = $pdo->prepare('
            INSERT INTO evidence_skills (evidence_id, skill_id)
            VALUES (:evidence_id, :skill_id)
            ON CONFLICT DO NOTHING
        ');
        $stmt->execute([
            ':evidence_id' => $evidence_id,
            ':skill_id' => $skill['id']
        ]);
    }

    echo json_encode([
        'success' => true,
        'evidence_id' => $evidence_id,
        'matched_skills' => $matched_skills,
        'total_matched' => count($matched_skills),
        'message' => count($matched_skills) > 0
            ? 'Found ' . count($matched_skills) . ' skills in your CV'
            : 'No skills found. You can add skills manually.'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('API cv-extract.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
