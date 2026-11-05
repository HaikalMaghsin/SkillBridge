<?php
// public/api/hr/candidates.php
// GET: Ambil pipeline kandidat untuk perusahaan ini (HR read-only).
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

$user_id = $user['id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
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

    // Ambil company_id
    $stmt = $pdo->prepare('
        SELECT company_id
        FROM company_recruiters
        WHERE user_id = :user_id
        LIMIT 1
    ');
    $stmt->execute([':user_id' => $user_id]);
    $recruiter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recruiter) {
        http_response_code(404);
        echo json_encode(['error' => 'Company not found']);
        exit;
    }

    $company_id = (int)$recruiter['company_id'];

    // GET: Ambil daftar kandidat yang profilnya dipublikasikan user
    if ($method === 'GET') {
        // Hanya profil dengan is_public = TRUE yang boleh terlihat HR.
        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.name,
                COALESCE(c.name, 'Belum Ditentukan') AS target_role,
                COUNT(DISTINCT e.id) AS evidence_count,
                cp.status AS pipeline_status,
                cp.notes AS pipeline_notes,
                cp.updated_at AS pipeline_updated
            FROM users u
            JOIN student_profiles sp ON sp.user_id = u.id AND sp.is_public = TRUE
            LEFT JOIN careers c ON c.id = sp.target_career_id
            LEFT JOIN evidence e ON e.user_id = u.id
            LEFT JOIN candidate_pipelines cp ON cp.user_id = u.id AND cp.company_id = :company_id
            WHERE u.role = 'user'
            GROUP BY u.id, u.name, c.name, cp.status, cp.notes, cp.updated_at
            ORDER BY u.name ASC
        ");
        $stmt->execute([':company_id' => $company_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Query matched skills & gap skills per user
        $stmtMatched = $pdo->prepare("
            SELECT skill_name
            FROM v_skill_gap
            WHERE user_id = :user_id AND has_skill = TRUE
            ORDER BY skill_name
        ");

        $stmtGap = $pdo->prepare("
            SELECT skill_name
            FROM v_skill_gap
            WHERE user_id = :user_id AND has_skill = FALSE
            ORDER BY skill_name
        ");

        $stmtUserSkills = $pdo->prepare("
            SELECT s.name
            FROM user_skills us
            JOIN skills s ON s.id = us.skill_id
            WHERE us.user_id = :user_id AND us.status = 'confirmed'
            ORDER BY s.name
        ");

        $candidates = [];
        foreach ($students as $s) {
            $uid = (int)$s['id'];

            // Ambil matched skills
            $stmtMatched->execute([':user_id' => $uid]);
            $matched = $stmtMatched->fetchAll(PDO::FETCH_COLUMN);

            // Ambil gap skills
            $stmtGap->execute([':user_id' => $uid]);
            $gaps = $stmtGap->fetchAll(PDO::FETCH_COLUMN);

            // Jika belum ada target career, ambil semua skill yang dimiliki
            if (empty($matched) && $s['target_role'] === 'Belum Ditentukan') {
                $stmtUserSkills->execute([':user_id' => $uid]);
                $matched = $stmtUserSkills->fetchAll(PDO::FETCH_COLUMN);
            }

            $candidates[] = [
                'id' => $uid,
                'name' => $s['name'],
                'target_role' => $s['target_role'],
                'matched_skills' => $matched ?: [],
                'gap_skills' => $gaps ?: [],
                'evidence_count' => (int)$s['evidence_count'],
                'status' => $s['pipeline_status'] ?? 'New',
                'notes' => $s['pipeline_notes'],
                'pipeline_updated' => $s['pipeline_updated']
            ];
        }

        echo json_encode($candidates, JSON_UNESCAPED_UNICODE);
        exit;
    }

} catch (Exception $e) {
    error_log('API hr/candidates.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
