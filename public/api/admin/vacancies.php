<?php
// public/api/admin/vacancies.php
// GET: Ambil semua lowongan dari seluruh perusahaan untuk moderasi
// PUT: Update status lowongan (moderasi)
// DELETE: Hapus lowongan
require_once __DIR__ . '/../../../includes/auth/auth.php';
require_once __DIR__ . '/../../../includes/auth/csrf.php';
require_once __DIR__ . '/../../../includes/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Wajib login sebagai Admin
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = current_user();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Admin access only']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    // GET: Ambil semua lowongan
    if ($method === 'GET') {
        $stmt = $pdo->query('
            SELECT
                jv.id,
                jv.title,
                jv.positions,
                jv.status,
                jv.description,
                jv.requirements,
                jv.created_at,
                jv.updated_at,
                c.name AS company_name,
                car.name AS career_name,
                u.name AS created_by_name
            FROM job_vacancies jv
            JOIN companies c ON c.id = jv.company_id
            LEFT JOIN careers car ON car.id = jv.career_id
            LEFT JOIN users u ON u.id = jv.created_by
            ORDER BY jv.created_at DESC
        ');
        $vacancies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted = array_map(function($v) {
            return [
                'id' => (int)$v['id'],
                'title' => $v['title'],
                'company' => $v['company_name'],
                'category' => $v['career_name'] ?? 'General',
                'positions' => (int)$v['positions'],
                'status' => $v['status'],
                'description' => $v['description'],
                'requirements' => $v['requirements'],
                'posted_by' => $v['created_by_name'],
                'created_at' => $v['created_at'],
                'updated_at' => $v['updated_at']
            ];
        }, $vacancies);

        echo json_encode($formatted, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // PUT: Update status lowongan (moderasi)
    if ($method === 'PUT') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $vacancy_id = isset($input['id']) ? (int)$input['id'] : null;
        $status = trim($input['status'] ?? '');

        if (!$vacancy_id || !$status) {
            http_response_code(400);
            echo json_encode(['error' => 'id and status are required']);
            exit;
        }

        // Validasi status
        $valid_statuses = ['active', 'inactive', 'closed'];
        if (!in_array($status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status']);
            exit;
        }

        // Update status
        $stmt = $pdo->prepare('
            UPDATE job_vacancies
            SET status = :status, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ');
        $stmt->execute([
            ':status' => $status,
            ':id' => $vacancy_id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Vacancy status updated'
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
            echo json_encode(['error' => 'id is required']);
            exit;
        }

        // Hapus lowongan
        $stmt = $pdo->prepare('DELETE FROM job_vacancies WHERE id = :id');
        $stmt->execute([':id' => $vacancy_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Vacancy deleted'
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API admin/vacancies.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
