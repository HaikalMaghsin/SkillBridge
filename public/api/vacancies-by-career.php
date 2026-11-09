<?php
// public/api/vacancies-by-career.php
// GET: Ambil lowongan aktif berdasarkan career_id untuk mahasiswa
require_once __DIR__ . '/../../includes/auth/auth.php';
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$career_id = isset($_GET['career_id']) ? (int)$_GET['career_id'] : null;

if (!$career_id) {
    http_response_code(400);
    echo json_encode(['error' => 'career_id required']);
    exit;
}

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    // Ambil lowongan aktif untuk career_id ini
    $stmt = $pdo->prepare('
        SELECT
            jv.id,
            jv.title,
            jv.positions,
            jv.description,
            jv.requirements,
            jv.created_at,
            c.name AS company_name,
            c.industry,
            c.website
        FROM job_vacancies jv
        JOIN companies c ON c.id = jv.company_id
        WHERE jv.career_id = :career_id
          AND jv.status = :status
        ORDER BY jv.created_at DESC
    ');
    $stmt->execute([
        ':career_id' => $career_id,
        ':status' => 'active'
    ]);
    $vacancies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Untuk setiap lowongan, ambil skill requirements dari career_skill_requirements
    $user_id = $user['id'];

    // Ambil skill mahasiswa
    $stmtUserSkills = $pdo->prepare('
        SELECT skill_id
        FROM user_skills
        WHERE user_id = :user_id AND status = :status
    ');
    $stmtUserSkills->execute([':user_id' => $user_id, ':status' => 'confirmed']);
    $userSkillIds = $stmtUserSkills->fetchAll(PDO::FETCH_COLUMN);

    // Ambil skill requirements untuk career ini
    $stmtSkills = $pdo->prepare('
        SELECT s.id, s.name
        FROM career_skill_requirements csr
        JOIN skills s ON s.id = csr.skill_id
        WHERE csr.career_id = :career_id
        ORDER BY s.name
    ');
    $stmtSkills->execute([':career_id' => $career_id]);
    $careerSkills = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);

    $result = array_map(function($v) use ($careerSkills, $userSkillIds) {
        // Hitung skill yang sudah dimiliki
        $skillsOwned = array_filter($careerSkills, function($skill) use ($userSkillIds) {
            return in_array($skill['id'], $userSkillIds);
        });

        return [
            'id' => (int)$v['id'],
            'title' => $v['title'],
            'company_name' => $v['company_name'],
            'industry' => $v['industry'],
            'website' => $v['website'],
            'positions' => (int)$v['positions'],
            'description' => $v['description'],
            'requirements' => $v['requirements'],
            'skills' => array_map(function($s) use ($userSkillIds) {
                return [
                    'id' => (int)$s['id'],
                    'name' => $s['name'],
                    'owned' => in_array($s['id'], $userSkillIds)
                ];
            }, $careerSkills),
            'skills_owned_count' => count($skillsOwned),
            'skills_total_count' => count($careerSkills),
            'created_at' => $v['created_at']
        ];
    }, $vacancies);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('API vacancies-by-career.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
