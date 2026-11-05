<?php
// public/api/hr/team.php
// GET: Ambil daftar anggota tim HR perusahaan (HR read-only).
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
    $stmt->execute([':user_id' => $user['id']]);
    $recruiter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recruiter) {
        http_response_code(404);
        echo json_encode(['error' => 'Company not found']);
        exit;
    }

    $company_id = (int)$recruiter['company_id'];

    // GET: Ambil daftar tim
    if ($method === 'GET') {
        $stmt = $pdo->prepare('
            SELECT
                cr.id,
                cr.role_title,
                cr.status,
                cr.created_at,
                u.id as user_id,
                u.name,
                u.email
            FROM company_recruiters cr
            JOIN users u ON u.id = cr.user_id
            WHERE cr.company_id = :company_id
            ORDER BY cr.created_at ASC
        ');
        $stmt->execute([':company_id' => $company_id]);
        $team = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted = array_map(function($t) {
            return [
                'id' => (int)$t['id'],
                'user_id' => (int)$t['user_id'],
                'name' => $t['name'],
                'email' => $t['email'],
                'role' => $t['role_title'],
                'status' => $t['status'],
                'joined' => $t['created_at']
            ];
        }, $team);

        echo json_encode($formatted, JSON_UNESCAPED_UNICODE);
        exit;
    }

} catch (Exception $e) {
    error_log('API hr/team.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
