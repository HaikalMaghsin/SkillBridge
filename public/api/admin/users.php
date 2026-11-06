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

/**
 * Normalisasi role dari API ke nilai enum database.
 * Menerima user|hr|admin dan alias lama mahasiswa untuk user.
 * Mengembalikan null untuk nilai yang tidak dikenal.
 */
function admin_normalize_role($role) {
    if (!is_string($role)) {
        return null;
    }
    $role = strtolower(trim($role));
    $map = [
        'user'      => 'user',
        'mahasiswa' => 'user',
        'hr'        => 'hr',
        'admin'     => 'admin',
    ];
    return $map[$role] ?? null;
}

/** Serialisasi perubahan akun admin agar pengecekan admin terakhir tidak balapan. */
function admin_guard_lock(PDO $pdo) {
    $pdo->query("SELECT pg_advisory_xact_lock(hashtext('skillbridge.admin.guard')::bigint)")->fetchColumn();
}

/** Jumlah admin yang benar-benar aktif. */
function admin_active_count(PDO $pdo) {
    return (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = TRUE")
        ->fetchColumn();
}

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
                u.is_active,
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
            GROUP BY u.id, u.name, u.email, u.role, u.is_active, u.created_at, sp.target_career_id, c.name
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
                'status' => db_is_true($row['is_active']) ? 'active' : 'inactive',
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

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $role = admin_normalize_role($input['role'] ?? 'user');

        if ($role === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role']);
            exit;
        }

        if (!$name || !$email) {
            http_response_code(400);
            echo json_encode(['error' => 'name and email are required']);
            exit;
        }

        if (!is_string($password) || $password === '') {
            http_response_code(400);
            echo json_encode(['error' => 'password is required']);
            exit;
        }

        $password_length = strlen($password);
        if ($password_length < 8 || $password_length > 72) {
            http_response_code(400);
            echo json_encode(['error' => 'password must be between 8 and 72 characters']);
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

        // Insert user + profil kosong agar user baru dari admin punya student_profiles.
        $pdo->beginTransaction();
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
            ':role' => $role
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $new_user_id = (int) $result['id'];

        if ($role === 'user') {
            $stmt = $pdo->prepare('
                INSERT INTO student_profiles (user_id)
                VALUES (:user_id)
                ON CONFLICT (user_id) DO NOTHING
            ');
            $stmt->execute([':user_id' => $new_user_id]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'id' => $new_user_id,
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

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $user_id = isset($input['id']) ? (int)$input['id'] : null;
        $name = trim($input['name'] ?? '');
        $email = strtolower(trim($input['email'] ?? ''));
        $role = null;

        if (array_key_exists('role', $input)) {
            $role = admin_normalize_role($input['role']);
            if ($role === null) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid role']);
                exit;
            }
        }

        $is_active = null;
        if (array_key_exists('is_active', $input)) {
            $is_active = filter_var($input['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($is_active === null) {
                http_response_code(400);
                echo json_encode(['error' => 'is_active must be a boolean']);
                exit;
            }
        }

        if (!$user_id) {
            http_response_code(400);
            echo json_encode(['error' => 'id is required']);
            exit;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            exit;
        }

        if ($email !== '') {
            // Cek duplikat tanpa memandang huruf besar/kecil, kecuali akun ini sendiri.
            $stmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) AND id <> :id');
            $stmt->execute([':email' => $email, ':id' => $user_id]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Email already exists']);
                exit;
            }
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
        if ($role !== null) {
            $updates[] = 'role = :role';
            $params[':role'] = $role;
        }
        if ($is_active !== null) {
            $updates[] = 'is_active = :is_active';
            $params[':is_active'] = $is_active ? 'true' : 'false';
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit;
        }

        $pdo->beginTransaction();
        $fail = function ($code, $message) use ($pdo) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code($code);
            echo json_encode(['error' => $message]);
            exit;
        };

        admin_guard_lock($pdo);

        $stmt = $pdo->prepare('SELECT id, role, is_active FROM users WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $user_id]);
        $target = $stmt->fetch();

        if (!$target) {
            $fail(404, 'User not found');
        }

        $target_role = (string) $target['role'];
        $target_active = db_is_true($target['is_active']);
        $final_role = $role ?? $target_role;
        $role_changed = ($role !== null && $role !== $target_role);
        $deactivating = ($is_active === false && $target_active);

        if ((int) $user_id === (int) $user['id']
            && (($role !== null && $role !== 'admin') || $is_active === false)) {
            $fail(403, 'Cannot change your own admin role or deactivate your own account');
        }

        if ($target_role === 'admin' && $target_active && ($role_changed || $deactivating)) {
            if (admin_active_count($pdo) <= 1) {
                $fail(409, 'Cannot remove the last active admin');
            }
        }

        $updates[] = 'updated_at = CURRENT_TIMESTAMP';
        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :user_id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($final_role === 'user') {
            $stmt = $pdo->prepare('
                INSERT INTO student_profiles (user_id)
                VALUES (:user_id)
                ON CONFLICT (user_id) DO NOTHING
            ');
            $stmt->execute([':user_id' => $user_id]);
        }

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'User updated']);
        exit;
    }

    // DELETE permanen dihapus; penonaktifan dilakukan lewat PUT is_active=false.

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e->getCode() === '23505') {
        http_response_code(409);
        echo json_encode(['error' => 'Email already exists']);
        exit;
    }
    error_log('API admin/users.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('API admin/users.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
