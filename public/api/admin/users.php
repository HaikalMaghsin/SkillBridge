<?php
// public/api/admin/users.php
// GET: List semua users
// POST: Create user baru
// PUT: Update user (termasuk ubah role)
// DELETE: Hapus user
require_once __DIR__ . '/../../../includes/auth/auth.php';
require_once __DIR__ . '/../../../includes/auth/csrf.php';
require_once __DIR__ . '/../../../includes/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Wajib login sebagai admin
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

    // GET: List semua users
    if ($method === 'GET') {
        $stmt = $pdo->query("
            SELECT
                u.id,
                u.name,
                u.email,
                u.role,
                u.created_at,
                sp.target_career_id,
                c.name AS career_target,
                COUNT(DISTINCT us.skill_id) AS skills_count,
                COUNT(DISTINCT e.id) AS evidence_count
            FROM users u
            LEFT JOIN student_profiles sp ON sp.user_id = u.id
            LEFT JOIN careers c ON c.id = sp.target_career_id
            LEFT JOIN user_skills us ON us.user_id = u.id AND us.status = 'confirmed'
            LEFT JOIN evidence e ON e.user_id = u.id
            WHERE u.role = 'user'
            GROUP BY u.id, u.name, u.email, u.role, u.created_at, sp.target_career_id, c.name
            ORDER BY u.created_at DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Transform untuk kompatibilitas frontend
        $users = array_map(function($row) {
            return [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'role' => db_role_to_app_role($row['role']),
                'career_target' => $row['career_target'],
                'skills_count' => (int)$row['skills_count'],
                'evidence_count' => (int)$row['evidence_count'],
                'status' => 'active', // Default status
                'created_at' => $row['created_at']
            ];
        }, $users);

        echo json_encode($users, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // POST: Create user baru
    if ($method === 'POST') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? 'password123'; // Default password
        $role = $input['role'] ?? 'user'; // Default mahasiswa

        if (!$name || !$email) {
            http_response_code(400);
            echo json_encode(['error' => 'name and email are required']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            exit;
        }

        // Cek email sudah ada
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already exists']);
            exit;
        }

        // Insert user
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('
            INSERT INTO users (name, email, password_hash, role)
            VALUES (:name, :email, :password_hash, :role)
            RETURNING id
        ');
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password_hash' => $password_hash,
            ':role' => app_role_to_db_role($role)
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'id' => (int)$result['id'],
            'message' => 'User created'
        ]);
        exit;
    }

    // PUT: Update user (termasuk ubah role)
    if ($method === 'PUT') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = isset($input['id']) ? (int)$input['id'] : null;
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $role = $input['role'] ?? null;

        if (!$user_id) {
            http_response_code(400);
            echo json_encode(['error' => 'id is required']);
            exit;
        }

        // Build dynamic update
        $updates = [];
        $params = [':user_id' => $user_id];

        if ($name) {
            $updates[] = 'name = :name';
            $params[':name'] = $name;
        }
        if ($email) {
            $updates[] = 'email = :email';
            $params[':email'] = $email;
        }
        if ($role) {
            $updates[] = 'role = :role';
            $params[':role'] = app_role_to_db_role($role);
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit;
        }

        $updates[] = 'updated_at = CURRENT_TIMESTAMP';
        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :user_id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'User updated']);
        exit;
    }

    // DELETE: Hapus user
    if ($method === 'DELETE') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = isset($input['id']) ? (int)$input['id'] : null;

        if (!$user_id) {
            http_response_code(400);
            echo json_encode(['error' => 'id is required']);
            exit;
        }

        // Tidak boleh hapus diri sendiri
        if ($user_id === $user['id']) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete your own account']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :user_id');
        $stmt->execute([':user_id' => $user_id]);

        echo json_encode(['success' => true, 'message' => 'User deleted']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API admin/users.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
