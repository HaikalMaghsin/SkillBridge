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
                COUNT(DISTINCT e.id) AS evidence_count
            FROM users u
            JOIN student_profiles sp ON sp.user_id = u.id AND sp.is_public = TRUE
            LEFT JOIN careers c ON c.id = sp.target_career_id
            LEFT JOIN evidence e ON e.user_id = u.id
            WHERE u.role = 'user'
            GROUP BY u.id, u.name, c.name
            ORDER BY u.name ASC
        ");
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ambil skill dan gap untuk semua kandidat sekaligus (bukan N+1).
        $candidate_ids = array_map(static function ($s) {
            return (int) $s['id'];
        }, $students);

        $confirmed_skills = [];
        $gap_map = [];

        if ($candidate_ids) {
            $placeholders = implode(',', array_fill(0, count($candidate_ids), '?'));

            $stmt = $pdo->prepare("
                SELECT us.user_id, s.name
                FROM user_skills us
                JOIN skills s ON s.id = us.skill_id
                WHERE us.status = 'confirmed' AND us.user_id IN ($placeholders)
                ORDER BY us.user_id, s.name
            ");
            $stmt->execute($candidate_ids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $confirmed_skills[(int) $row['user_id']][] = $row['name'];
            }

            $stmt = $pdo->prepare("
                SELECT user_id, skill_name, has_skill
                FROM v_skill_gap
                WHERE user_id IN ($placeholders)
                ORDER BY user_id, skill_name
            ");
            $stmt->execute($candidate_ids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $uid = (int) $row['user_id'];
                $key = db_is_true($row['has_skill']) ? 'matched' : 'gap';
                $gap_map[$uid][$key][] = $row['skill_name'];
            }
        }

        $candidates = [];
        foreach ($students as $s) {
            $uid = (int)$s['id'];

            $matched = $gap_map[$uid]['matched'] ?? [];
            $gaps = $gap_map[$uid]['gap'] ?? [];

            // Jika belum ada target career, ambil semua skill yang dimiliki
            if (empty($matched) && $s['target_role'] === 'Belum Ditentukan') {
                $matched = $confirmed_skills[$uid] ?? [];
            }

            $candidates[] = [
                'id' => $uid,
                'name' => $s['name'],
                'target_role' => $s['target_role'],
                'matched_skills' => $matched ?: [],
                'gap_skills' => $gaps ?: [],
                'evidence_count' => (int)$s['evidence_count']
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
