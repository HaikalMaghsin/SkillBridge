<?php
// public/api/hr/activity.php
// GET: Ambil riwayat aktivitas tim HR perusahaan
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
        echo json_encode([]);
        exit;
    }

    $company_id = (int)$recruiter['company_id'];

    // Ambil aktivitas dengan join ke users untuk nama recruiter
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
        LIMIT 50
    ');
    $stmt->execute([':company_id' => $company_id]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format untuk frontend
    $formatted = array_map(function($a) {
        return [
            'id' => (int)$a['id'],
            'action' => $a['action_type'],
            'description' => $a['description'],
            'recruiter' => $a['recruiter_name'],
            'timestamp' => $a['created_at']
        ];
    }, $activities);

    echo json_encode($formatted, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('API hr/activity.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
