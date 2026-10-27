<?php
// public/api/hr/candidates.php
// GET: Ambil pipeline kandidat untuk perusahaan ini
// POST: Update status kandidat di pipeline
require_once __DIR__ . '/../../../includes/auth/auth.php';
require_once __DIR__ . '/../../../includes/auth/csrf.php';
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

    // GET: Ambil daftar kandidat
    if ($method === 'GET') {
        // Ambil semua mahasiswa dengan data lengkap
        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.name,
                u.email,
                COALESCE(c.name, 'Belum Ditentukan') AS target_role,
                COUNT(DISTINCT e.id) AS evidence_count,
                cp.status AS pipeline_status,
                cp.notes AS pipeline_notes,
                cp.updated_at AS pipeline_updated
            FROM users u
            LEFT JOIN student_profiles sp ON sp.user_id = u.id
            LEFT JOIN careers c ON c.id = sp.target_career_id
            LEFT JOIN evidence e ON e.user_id = u.id
            LEFT JOIN candidate_pipelines cp ON cp.user_id = u.id AND cp.company_id = :company_id
            WHERE u.role = 'user'
            GROUP BY u.id, u.name, u.email, c.name, cp.status, cp.notes, cp.updated_at
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
                'email' => $s['email'],
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

    // POST: Update status kandidat
    if ($method === 'POST') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $candidate_user_id = isset($input['user_id']) ? (int)$input['user_id'] : null;
        $status = trim($input['status'] ?? '');
        $notes = trim($input['notes'] ?? '');
        $vacancy_id = isset($input['vacancy_id']) ? (int)$input['vacancy_id'] : null;

        if (!$candidate_user_id || !$status) {
            http_response_code(400);
            echo json_encode(['error' => 'user_id and status are required']);
            exit;
        }

        // Validasi status
        $valid_statuses = ['Shortlisted', 'Interview', 'Accepted', 'Rejected', 'New'];
        if (!in_array($status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status']);
            exit;
        }

        // UPSERT candidate_pipelines
        $stmt = $pdo->prepare('
            INSERT INTO candidate_pipelines (company_id, vacancy_id, user_id, status, notes, updated_by)
            VALUES (:company_id, :vacancy_id, :user_id, :status, :notes, :updated_by)
            ON CONFLICT (company_id, user_id, vacancy_id)
            DO UPDATE SET
                status = EXCLUDED.status,
                notes = EXCLUDED.notes,
                updated_by = EXCLUDED.updated_by,
                updated_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([
            ':company_id' => $company_id,
            ':vacancy_id' => $vacancy_id,
            ':user_id' => $candidate_user_id,
            ':status' => $status,
            ':notes' => $notes,
            ':updated_by' => $user_id
        ]);

        // Log aktivitas
        $stmtLog = $pdo->prepare('
            INSERT INTO recruiter_activity_logs (company_id, user_id, action_type, target_type, target_id, description)
            VALUES (:company_id, :user_id, :action_type, :target_type, :target_id, :description)
        ');

        // Ambil nama kandidat untuk log
        $stmtUser = $pdo->prepare('SELECT name FROM users WHERE id = :user_id');
        $stmtUser->execute([':user_id' => $candidate_user_id]);
        $candidateName = $stmtUser->fetchColumn();

        $stmtLog->execute([
            ':company_id' => $company_id,
            ':user_id' => $user_id,
            ':action_type' => 'candidate_status_update',
            ':target_type' => 'candidate',
            ':target_id' => $candidate_user_id,
            ':description' => "Updated candidate {$candidateName} status to {$status}"
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Candidate status updated'
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API hr/candidates.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
