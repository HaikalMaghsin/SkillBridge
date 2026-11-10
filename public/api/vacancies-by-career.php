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
            c.id AS company_id,
            c.name AS company_name,
            c.industry,
            c.website,
            c.description AS company_description
        FROM job_vacancies jv
        JOIN companies c ON c.id = jv.company_id
        WHERE jv.career_id = :career_id
          AND jv.status = :status
        ORDER BY jv.created_at DESC
        LIMIT 4
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

        $ownedCount = count($skillsOwned);
        $totalCount = count($careerSkills);

        // Calculate tier (internal only, not shown as percentage)
        $matchRatio = $totalCount > 0 ? ($ownedCount / $totalCount) : 0;

        if ($matchRatio >= 0.7) {
            $tier = 1; // Strong Match
            $tierLabel = 'Sangat Sesuai';
            $tierColor = 'green';
        } elseif ($matchRatio >= 0.4) {
            $tier = 2; // Learning Opportunity
            $tierLabel = 'Cocok untuk Magang / Intern';
            $tierColor = 'orange';
        } else {
            $tier = 3; // Development Path
            $tierLabel = 'Perlu Pengembangan';
            $tierColor = 'blue';
        }

        // Determine job type from title
        $jobType = 'Full-time';
        if (stripos($v['title'], 'Intern') !== false || stripos($v['title'], 'Magang') !== false) {
            $jobType = 'Internship';
        } elseif (stripos($v['title'], 'Trainee') !== false || stripos($v['title'], 'Training') !== false) {
            $jobType = 'Trainee Program';
        } elseif (stripos($v['title'], 'Junior') !== false || stripos($v['title'], 'Fresh Graduate') !== false) {
            $jobType = 'Fresh Graduate';
        }

        return [
            'id' => (int)$v['id'],
            'title' => $v['title'],
            'job_type' => $jobType,
            'company_id' => (int)$v['company_id'],
            'company_name' => $v['company_name'],
            'company_description' => $v['company_description'],
            'industry' => $v['industry'],
            'website' => $v['website'],
            'location' => 'Remote / Hybrid', // Default, bisa diupdate nanti
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
            'skills_owned_count' => $ownedCount,
            'skills_total_count' => $totalCount,
            'tier' => $tier,
            'tier_label' => $tierLabel,
            'tier_color' => $tierColor,
            'tier_description' => "Memenuhi {$ownedCount} dari {$totalCount} skill",
            'created_at' => $v['created_at']
        ];
    }, $vacancies);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('API vacancies-by-career.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
