<?php
// public/api/hr/roles.php
// GET: Ambil daftar lowongan / Target Roles perusahaan (HR read-only).
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

} catch (Exception $e) {
    error_log('API hr/roles.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
