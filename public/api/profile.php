<?php
// public/api/profile.php
// GET: Ambil profil pengguna aktif
// POST: Update profil (info, password, visibility)
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
        // Ambil data user
        $stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = :user_id');
        $stmt->execute([':user_id' => $user_id]);
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userRow) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        $profile = [
            'id' => (int)$userRow['id'],
            'name' => $userRow['name'],
            'email' => $userRow['email'],
            'role' => db_role_to_app_role($userRow['role']),
            'created_at' => $userRow['created_at']
        ];

        // Jika mahasiswa, ambil juga student_profiles
        if ($user['role'] === 'mahasiswa') {
            $stmt = $pdo->prepare('
                SELECT target_career_id, is_public, public_token
                FROM student_profiles
                WHERE user_id = :user_id
            ');
            $stmt->execute([':user_id' => $user_id]);
            $studentProfile = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($studentProfile) {
                $profile['target_career_id'] = $studentProfile['target_career_id'] ? (int)$studentProfile['target_career_id'] : null;
                $profile['is_public'] = (bool)$studentProfile['is_public'];
                $profile['public_token'] = $studentProfile['public_token'];

                if ($profile['is_public'] && $profile['public_token']) {
                    $profile['public_url'] = base_url('p.php?token=' . $studentProfile['public_token']);
                }
            }
        }

        echo json_encode($profile, JSON_UNESCAPED_UNICODE);
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
        $action = $input['action'] ?? '';

        // Update info (name, email)
        if ($action === 'update_info') {
            $name = trim($input['name'] ?? '');
            $email = trim($input['email'] ?? '');

            if (!$name || !$email) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and email are required']);
                exit;
            }

            // Cek email duplikat
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :user_id');
            $stmt->execute([':email' => $email, ':user_id' => $user_id]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Email already taken']);
                exit;
            }

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

        // Change password
        if ($action === 'change_password') {
            $current_password = $input['current_password'] ?? '';
            $new_password = $input['new_password'] ?? '';

            if (!$current_password || !$new_password) {
                http_response_code(400);
                echo json_encode(['error' => 'Current and new password are required']);
                exit;
            }

            // Verifikasi current password
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :user_id');
            $stmt->execute([':user_id' => $user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($current_password, $row['password_hash'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Current password is incorrect']);
                exit;
            }

            // Update password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('
                UPDATE users
                SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP
                WHERE id = :user_id
            ');
            $stmt->execute([
                ':password_hash' => $new_hash,
                ':user_id' => $user_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Password changed']);
            exit;
        }

        // Update visibility (mahasiswa only)
        if ($action === 'update_visibility') {
            if ($user['role'] !== 'mahasiswa') {
                http_response_code(403);
                echo json_encode(['error' => 'Only students can update visibility']);
                exit;
            }

            $is_public = isset($input['is_public']) ? (bool)$input['is_public'] : false;

            // Jika set public, pastikan ada public_token
            if ($is_public) {
                // Generate token jika belum ada
                $stmt = $pdo->prepare('SELECT public_token FROM student_profiles WHERE user_id = :user_id');
                $stmt->execute([':user_id' => $user_id]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                $token = $existing['public_token'] ?? null;
                if (!$token) {
                    $token = bin2hex(random_bytes(16));
                }

                $stmt = $pdo->prepare('
                    INSERT INTO student_profiles (user_id, is_public, public_token)
                    VALUES (:user_id, :is_public, :public_token)
                    ON CONFLICT (user_id)
                    DO UPDATE SET is_public = :is_public, public_token = :public_token, updated_at = CURRENT_TIMESTAMP
                ');
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':is_public' => true,
                    ':public_token' => $token
                ]);

                $public_url = base_url('p.php?token=' . $token);
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile is now public',
                    'public_url' => $public_url
                ]);
            } else {
                // Set private
                $stmt = $pdo->prepare('
                    UPDATE student_profiles
                    SET is_public = false, updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = :user_id
                ');
                $stmt->execute([':user_id' => $user_id]);

                echo json_encode(['success' => true, 'message' => 'Profile is now private']);
            }
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
