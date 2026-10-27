<?php
// public/api/hr/team.php
// GET: Ambil daftar anggota tim HR perusahaan
// POST: Tambah anggota tim (invite)
// PUT: Update status anggota
// DELETE: Hapus anggota dari tim
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

    // POST/PUT/DELETE operations require CSRF
    if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }
    }

    // POST: Tambah anggota (simplified - hanya update jika user sudah ada)
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $target_user_id = isset($input['user_id']) ? (int)$input['user_id'] : null;
        $role_title = trim($input['role_title'] ?? 'Recruiter');

        if (!$target_user_id) {
            http_response_code(400);
            echo json_encode(['error' => 'user_id is required']);
            exit;
        }

        // Cek apakah user adalah HR
        $stmt = $pdo->prepare('SELECT role FROM users WHERE id = :user_id');
        $stmt->execute([':user_id' => $target_user_id]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser || $targetUser['role'] !== 'hr') {
            http_response_code(400);
            echo json_encode(['error' => 'User must have HR role']);
            exit;
        }

        // Insert atau update
        $stmt = $pdo->prepare('
            INSERT INTO company_recruiters (company_id, user_id, role_title, status)
            VALUES (:company_id, :user_id, :role_title, :status)
            ON CONFLICT (company_id, user_id)
            DO UPDATE SET role_title = EXCLUDED.role_title, status = EXCLUDED.status, updated_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([
            ':company_id' => $company_id,
            ':user_id' => $target_user_id,
            ':role_title' => $role_title,
            ':status' => 'active'
        ]);

        echo json_encode(['success' => true, 'message' => 'Team member added']);
        exit;
    }

    // PUT: Update status anggota
    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        $member_id = isset($input['id']) ? (int)$input['id'] : null;
        $status = trim($input['status'] ?? 'active');

        if (!$member_id) {
            http_response_code(400);
            echo json_encode(['error' => 'id is required']);
            exit;
        }

        $stmt = $pdo->prepare('
            UPDATE company_recruiters
            SET status = :status, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND company_id = :company_id
        ');
        $stmt->execute([
            ':status' => $status,
            ':id' => $member_id,
            ':company_id' => $company_id
        ]);

        echo json_encode(['success' => true, 'message' => 'Team member updated']);
        exit;
    }

    // DELETE: Hapus anggota
    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $member_id = isset($input['id']) ? (int)$input['id'] : null;

        if (!$member_id) {
            http_response_code(400);
            echo json_encode(['error' => 'id is required']);
            exit;
        }

        $stmt = $pdo->prepare('
            DELETE FROM company_recruiters
            WHERE id = :id AND company_id = :company_id
        ');
        $stmt->execute([
            ':id' => $member_id,
            ':company_id' => $company_id
        ]);

        echo json_encode(['success' => true, 'message' => 'Team member removed']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API hr/team.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
