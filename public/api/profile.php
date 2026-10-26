<?php
// public/api/profile.php
// GET: Ambil data profil user (users + student_profiles)
// POST: Update profil (nama, visibility, password)
require_once __DIR__ . '/../../includes/auth/auth.php';
require_once __DIR__ . '/../../includes/auth/csrf.php';
require_once __DIR__ . '/../../includes/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Wajib login
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = current_user();
$user_id = $user['id'];

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    // GET: Ambil profil lengkap
    if ($method === 'GET') {
        $stmt = $pdo->prepare('
            SELECT
                u.id,
                u.name,
                u.email,
                u.role,
                u.created_at,
                sp.is_public,
                sp.public_token,
                sp.target_career_id
            FROM users u
            LEFT JOIN student_profiles sp ON sp.user_id = u.id
            WHERE u.id = :user_id
        ');
        $stmt->execute([':user_id' => $user_id]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        echo json_encode([
            'id' => (int)$profile['id'],
            'name' => $profile['name'],
            'email' => $profile['email'],
            'role' => $profile['role'],
            'created_at' => $profile['created_at'],
            'is_public' => (bool)$profile['is_public'],
            'public_token' => $profile['public_token'],
            'target_career_id' => $profile['target_career_id'] ? (int)$profile['target_career_id'] : null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // POST: Update profil
    if ($method === 'POST') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? 'update_info';

        // Update informasi dasar (nama, email, dll)
        if ($action === 'update_info') {
            $name = trim($input['name'] ?? '');
            $email = trim($input['email'] ?? '');

            if (!$name || !$email) {
                http_response_code(400);
                echo json_encode(['error' => 'name and email are required']);
                exit;
            }

            // Validasi email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email format']);
                exit;
            }

            // Cek apakah email sudah dipakai user lain
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :user_id');
            $stmt->execute([':email' => $email, ':user_id' => $user_id]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Email already taken']);
                exit;
            }

            // Update users
            $stmt = $pdo->prepare('
                UPDATE users
                SET name = :name, email = :email, updated_at = CURRENT_TIMESTAMP
                WHERE id = :user_id
            ');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':user_id' => $user_id
            ]);

            // Update session
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;

            echo json_encode(['success' => true, 'message' => 'Profile updated']);
            exit;
        }

        // Update visibility portofolio
        if ($action === 'update_visibility') {
            $is_public = isset($input['is_public']) ? (bool)$input['is_public'] : false;

            // Generate token jika is_public dan belum punya token
            $public_token = null;
            if ($is_public) {
                $stmtToken = $pdo->prepare('SELECT public_token FROM student_profiles WHERE user_id = :user_id');
                $stmtToken->execute([':user_id' => $user_id]);
                $row = $stmtToken->fetch(PDO::FETCH_ASSOC);

                if ($row && $row['public_token']) {
                    $public_token = $row['public_token'];
                } else {
                    $public_token = bin2hex(random_bytes(16)); // Generate UUID-like token
                }
            }

            // UPSERT student_profiles
            if ($is_public && $public_token) {
                $stmt = $pdo->prepare('
                    INSERT INTO student_profiles (user_id, is_public, public_token)
                    VALUES (:user_id, :is_public, :token)
                    ON CONFLICT (user_id)
                    DO UPDATE SET is_public = :is_public, public_token = :token, updated_at = CURRENT_TIMESTAMP
                ');
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':is_public' => $is_public,
                    ':token' => $public_token
                ]);
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO student_profiles (user_id, is_public, public_token)
                    VALUES (:user_id, FALSE, NULL)
                    ON CONFLICT (user_id)
                    DO UPDATE SET is_public = FALSE, public_token = NULL, updated_at = CURRENT_TIMESTAMP
                ');
                $stmt->execute([':user_id' => $user_id]);
            }

            echo json_encode(['success' => true, 'message' => 'Visibility updated', 'public_token' => $public_token]);
            exit;
        }

        // Ganti password
        if ($action === 'change_password') {
            $current_password = $input['current_password'] ?? '';
            $new_password = $input['new_password'] ?? '';

            if (!$current_password || !$new_password) {
                http_response_code(400);
                echo json_encode(['error' => 'current_password and new_password are required']);
                exit;
            }

            if (strlen($new_password) < 6) {
                http_response_code(400);
                echo json_encode(['error' => 'New password must be at least 6 characters']);
                exit;
            }

            // Verifikasi password lama
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :user_id');
            $stmt->execute([':user_id' => $user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($current_password, $row['password_hash'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Current password is incorrect']);
                exit;
            }

            // Update password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('
                UPDATE users
                SET password_hash = :hash, updated_at = CURRENT_TIMESTAMP
                WHERE id = :user_id
            ');
            $stmt->execute([':hash' => $new_hash, ':user_id' => $user_id]);

            echo json_encode(['success' => true, 'message' => 'Password changed']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API profile.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
