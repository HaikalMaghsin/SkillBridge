<?php
// public/api/hr/activity.php
// GET: Ambil riwayat aktivitas dan notifikasi pelamar terbaru untuk HR
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    $stmt->execute([':user_id' => $user['id']]);
    $recruiter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recruiter) {
        echo json_encode([
            'activities' => [],
            'recent_applicants' => [],
            'new_count' => 0
        ]);
        exit;
    }

    $company_id = (int)$recruiter['company_id'];

    // 1. Hitung pelamar baru (status = 'New')
    $stmtNew = $pdo->prepare("
        SELECT COUNT(*)
        FROM candidate_pipelines
        WHERE company_id = :company_id AND status = 'New'
    ");
    $stmtNew->execute([':company_id' => $company_id]);
    $newCount = (int)$stmtNew->fetchColumn();

    // 2. Ambil pelamar terbaru yang menyatakan minat (candidate_pipelines)
    $stmtApplicants = $pdo->prepare("
        SELECT
            cp.id AS pipeline_id,
            cp.user_id,
            cp.vacancy_id,
            cp.status,
            cp.created_at,
            cp.updated_at,
            u.name AS candidate_name,
            jv.title AS vacancy_title
        FROM candidate_pipelines cp
        JOIN users u ON u.id = cp.user_id
        LEFT JOIN job_vacancies jv ON jv.id = cp.vacancy_id
        WHERE cp.company_id = :company_id
        ORDER BY cp.created_at DESC
        LIMIT 10
    ");
    $stmtApplicants->execute([':company_id' => $company_id]);
    $applicants = $stmtApplicants->fetchAll(PDO::FETCH_ASSOC);

    // 3. Ambil log aktivitas tim recruiter
    $stmt = $pdo->prepare('
        SELECT
            ral.id,
            ral.action_type,
            ral.target_type,
            ral.target_id,
            ral.description,
            ral.created_at,
            u.name as recruiter_name
        FROM recruiter_activity_logs ral
        JOIN users u ON u.id = ral.user_id
        WHERE ral.company_id = :company_id
        ORDER BY ral.created_at DESC
        LIMIT 30
    ');
    $stmt->execute([':company_id' => $company_id]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format aktivitas campuran agar lengkap
    $activities = [];

    // Tambahkan log pelamar
    foreach ($applicants as $app) {
        $activities[] = [
            'id' => 'app-' . $app['pipeline_id'],
            'type' => 'application',
            'action' => 'express_interest',
            'title' => $app['candidate_name'] . ' menyatakan minat',
            'description' => "Melamar pada posisi {$app['vacancy_title']}",
            'actor' => $app['candidate_name'],
            'status' => $app['status'],
            'time' => $app['created_at'],
            'raw_time' => strtotime($app['created_at'])
        ];
    }

    // Tambahkan log tim
    foreach ($logs as $l) {
        $activities[] = [
            'id' => 'log-' . $l['id'],
            'type' => 'team_log',
            'action' => $l['action_type'],
            'title' => $l['recruiter_name'] . ' ' . $l['action_type'],
            'description' => $l['description'],
            'actor' => $l['recruiter_name'],
            'time' => $l['created_at'],
            'raw_time' => strtotime($l['created_at'])
        ];
    }

    // Urutkan berdasarkan waktu terbaru
    usort($activities, function($a, $b) {
        return $b['raw_time'] - $a['raw_time'];
    });

    $activities = array_slice($activities, 0, 25);

    echo json_encode([
        'success' => true,
        'new_count' => $newCount,
        'recent_applicants' => $applicants,
        'activities' => $activities
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('API hr/activity.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
