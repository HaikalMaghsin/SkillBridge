<?php
// public/api/hr/roles.php
// GET: Ambil daftar lowongan / Target Roles perusahaan
// POST: Tambah lowongan baru
// PUT: Update lowongan
// DELETE: Hapus lowongan
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

    // Ambil company_id dari company_recruiters
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

    // GET: Ambil daftar lowongan
    if ($method === 'GET') {
        $stmt = $pdo->prepare('
            SELECT
                jv.id,
                jv.title,
                jv.positions,
                jv.status,
                jv.description,
                jv.requirements,
                jv.career_id,
                c.name AS career_name,
                jv.created_at,
                jv.updated_at
            FROM job_vacancies jv
            LEFT JOIN careers c ON c.id = jv.career_id
            WHERE jv.company_id = :company_id
            ORDER BY jv.created_at DESC
        ');
        $stmt->execute([':company_id' => $company_id]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Transform untuk kompatibilitas dengan frontend
        $rolesFormatted = array_map(function($r) use ($pdo, $company_id) {
            // Ambil skill requirements untuk role ini (dari career_skill_requirements)
            $skills = [];
            if ($r['career_id']) {
                $stmtSkills = $pdo->prepare('
                    SELECT s.name
                    FROM career_skill_requirements csr
                    JOIN skills s ON s.id = csr.skill_id
                    WHERE csr.career_id = :career_id
                    ORDER BY s.name
                ');
                $stmtSkills->execute([':career_id' => $r['career_id']]);
                $skills = $stmtSkills->fetchAll(PDO::FETCH_COLUMN);
            }

            // Hitung kandidat yang cocok (simulasi - nanti bisa diperbaiki)
            $stmtMatches = $pdo->prepare('
                SELECT COUNT(DISTINCT cp.user_id) as total
                FROM candidate_pipelines cp
                WHERE cp.company_id = :company_id
                  AND (cp.vacancy_id = :vacancy_id OR cp.vacancy_id IS NULL)
            ');
            $stmtMatches->execute([
                ':company_id' => $company_id,
                ':vacancy_id' => $r['id']
            ]);
            $matches = (int)$stmtMatches->fetchColumn();

            return [
                'id' => (int)$r['id'],
                'name' => $r['title'],
                'category' => $r['career_name'] ?? 'General',
                'positions' => (int)$r['positions'],
                'status' => $r['status'],
                'description' => $r['description'],
                'requirements' => $r['requirements'],
                'skills' => $skills,
                'matches' => $matches,
                'created_at' => $r['created_at'],
                'updated_at' => $r['updated_at']
            ];
        }, $roles);

        echo json_encode($rolesFormatted, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // POST: Tambah lowongan baru
    if ($method === 'POST') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $title = trim($input['title'] ?? '');
        $career_id = isset($input['career_id']) ? (int)$input['career_id'] : null;
        $positions = isset($input['positions']) ? (int)$input['positions'] : 1;
        $description = trim($input['description'] ?? '');
        $requirements = trim($input['requirements'] ?? '');

        if (!$title) {
            http_response_code(400);
            echo json_encode(['error' => 'Title is required']);
            exit;
        }

        // Insert lowongan
        $stmt = $pdo->prepare('
            INSERT INTO job_vacancies (company_id, career_id, title, positions, description, requirements, created_by, status)
            VALUES (:company_id, :career_id, :title, :positions, :description, :requirements, :created_by, :status)
            RETURNING id
        ');
        $stmt->execute([
            ':company_id' => $company_id,
            ':career_id' => $career_id,
            ':title' => $title,
            ':positions' => $positions,
            ':description' => $description,
            ':requirements' => $requirements,
            ':created_by' => $user_id,
            ':status' => 'active'
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $vacancy_id = (int)$result['id'];

        // Log aktivitas
        $stmtLog = $pdo->prepare('
            INSERT INTO recruiter_activity_logs (company_id, user_id, action_type, target_type, target_id, description)
            VALUES (:company_id, :user_id, :action_type, :target_type, :target_id, :description)
        ');
        $stmtLog->execute([
            ':company_id' => $company_id,
            ':user_id' => $user_id,
            ':action_type' => 'vacancy_created',
            ':target_type' => 'vacancy',
            ':target_id' => $vacancy_id,
            ':description' => "Created vacancy: {$title}"
        ]);

        echo json_encode([
            'success' => true,
            'id' => $vacancy_id,
            'message' => 'Vacancy created'
        ]);
        exit;
    }

    // PUT: Update lowongan
    if ($method === 'PUT') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $vacancy_id = isset($input['id']) ? (int)$input['id'] : null;
        $title = trim($input['title'] ?? '');
        $positions = isset($input['positions']) ? (int)$input['positions'] : 1;
        $status = trim($input['status'] ?? 'active');
        $description = trim($input['description'] ?? '');
        $requirements = trim($input['requirements'] ?? '');

        if (!$vacancy_id || !$title) {
            http_response_code(400);
            echo json_encode(['error' => 'ID and title are required']);
            exit;
        }

        // Validasi kepemilikan
        $stmt = $pdo->prepare('SELECT id FROM job_vacancies WHERE id = :id AND company_id = :company_id');
        $stmt->execute([':id' => $vacancy_id, ':company_id' => $company_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Vacancy not found']);
            exit;
        }

        // Update lowongan
        $stmt = $pdo->prepare('
            UPDATE job_vacancies
            SET title = :title,
                positions = :positions,
                status = :status,
                description = :description,
                requirements = :requirements,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND company_id = :company_id
        ');
        $stmt->execute([
            ':title' => $title,
            ':positions' => $positions,
            ':status' => $status,
            ':description' => $description,
            ':requirements' => $requirements,
            ':id' => $vacancy_id,
            ':company_id' => $company_id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Vacancy updated'
        ]);
        exit;
    }

    // DELETE: Hapus lowongan
    if ($method === 'DELETE') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $vacancy_id = isset($input['id']) ? (int)$input['id'] : null;

        if (!$vacancy_id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required']);
            exit;
        }

        // Validasi kepemilikan
        $stmt = $pdo->prepare('SELECT id FROM job_vacancies WHERE id = :id AND company_id = :company_id');
        $stmt->execute([':id' => $vacancy_id, ':company_id' => $company_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Vacancy not found']);
            exit;
        }

        // Hapus lowongan
        $stmt = $pdo->prepare('DELETE FROM job_vacancies WHERE id = :id AND company_id = :company_id');
        $stmt->execute([':id' => $vacancy_id, ':company_id' => $company_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Vacancy deleted'
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API hr/roles.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
