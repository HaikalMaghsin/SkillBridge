<?php
// public/api/pipeline-status.php
// GET: Ambil status pipeline kandidat untuk mahasiswa yang sedang login
// Mengembalikan daftar lowongan di mana mahasiswa telah masuk pipeline HR
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

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    $user_id = $user['id'];

    // Ambil semua pipeline entries untuk mahasiswa ini
    $stmt = $pdo->prepare('
        SELECT
            cp.id,
            cp.status,
            cp.notes,
            cp.created_at,
            cp.updated_at,
            c.name AS company_name,
            c.industry,
            jv.title AS vacancy_title,
            jv.positions
        FROM candidate_pipelines cp
        JOIN companies c ON c.id = cp.company_id
        LEFT JOIN job_vacancies jv ON jv.id = cp.vacancy_id
        WHERE cp.user_id = :user_id
        ORDER BY cp.updated_at DESC
    ');
    $stmt->execute([':user_id' => $user_id]);
    $pipelines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = array_map(function($row) {
        return [
            'id' => (int)$row['id'],
            'status' => $row['status'],
            'notes' => $row['notes'],
            'company_name' => $row['company_name'],
            'industry' => $row['industry'],
            'vacancy_title' => $row['vacancy_title'],
            'positions' => $row['positions'] ? (int)$row['positions'] : null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }, $pipelines);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('API pipeline-status.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
