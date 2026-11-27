<?php
// public/api/profile.php
// GET: Ambil profil pengguna aktif
// POST: Update profil (info, password, visibility, upload avatar)
require_once __DIR__ . '/../../includes/auth/auth.php';
require_once __DIR__ . '/../../includes/auth/csrf.php';
require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/config/r2.php';

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
        $stmt = $pdo->prepare('SELECT id, name, email, role, avatar_url, created_at FROM users WHERE id = :user_id');
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
            'avatar_url' => r2_display_url($userRow['avatar_url']),
            'created_at' => $userRow['created_at']
        ];

        // Jika mahasiswa, ambil juga student_profiles
        if ($user['role'] === 'mahasiswa') {
            $stmt = $pdo->prepare('
                SELECT target_career_id, is_public, public_token, institution, study_program, semester, location, phone, bio
                FROM student_profiles
                WHERE user_id = :user_id
            ');
            $stmt->execute([':user_id' => $user_id]);
            $studentProfile = $stmt->fetch(PDO::FETCH_ASSOC);

            $profile['institution'] = $studentProfile['institution'] ?? 'Politeknik Negeri Malang';
            $profile['study_program'] = $studentProfile['study_program'] ?? 'Teknik Informatika';
            $profile['program'] = $profile['study_program']; // alias untuk kompatibilitas frontend
            $profile['semester'] = isset($studentProfile['semester']) ? (int)$studentProfile['semester'] : 3;
            $profile['location'] = $studentProfile['location'] ?? 'Malang, Indonesia';
            $profile['phone'] = $studentProfile['phone'] ?? null;
            $profile['bio'] = $studentProfile['bio'] ?? null;
            $profile['target_career_id'] = !empty($studentProfile['target_career_id']) ? (int)$studentProfile['target_career_id'] : null;
            $profile['is_public'] = !empty($studentProfile['is_public']);
            $profile['public_token'] = $studentProfile['public_token'] ?? null;

            if ($profile['is_public'] && $profile['public_token']) {
                $profile['public_url'] = base_url('p.php?token=' . $studentProfile['public_token']);
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

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $input['action'] ?? '';

        // Default action ke update_info jika tidak spesifik namun name/email dikirim
        if (empty($action) && (!empty($input['name']) || !empty($input['email']))) {
            $action = 'update_info';
        }

        // Update info (name, email, profiling, password)
        if ($action === 'update_info') {
            $name = trim($input['name'] ?? '');
            $email = trim($input['email'] ?? '');
            $institution = trim($input['institution'] ?? 'Politeknik Negeri Malang');
            $study_program = trim($input['study_program'] ?? $input['program'] ?? 'Teknik Informatika');
            $semester = isset($input['semester']) ? (int)$input['semester'] : 3;
            $location = trim($input['location'] ?? 'Malang, Indonesia');
            $phone = isset($input['phone']) ? trim($input['phone']) : null;
            $bio = isset($input['bio']) ? trim($input['bio']) : null;

            if (!$name || !$email) {
                http_response_code(400);
                echo json_encode(['error' => 'Nama dan email wajib diisi']);
                exit;
            }

            // Cek email duplikat
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :user_id');
            $stmt->execute([':email' => $email, ':user_id' => $user_id]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Email sudah digunakan oleh akun lain']);
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

            // Jika dikirim password baru bersamaan (misal di halaman admin settings)
            if (!empty($input['password'])) {
                $rawPass = trim($input['password']);
                if (strlen($rawPass) < 8) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Password baru minimal 8 karakter']);
                    exit;
                }
                $newHash = password_hash($rawPass, PASSWORD_DEFAULT);
                $stmtPass = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = CURRENT_TIMESTAMP WHERE id = :user_id');
                $stmtPass->execute([':hash' => $newHash, ':user_id' => $user_id]);
            }

            // Update session
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;

            if ($user['role'] === 'mahasiswa') {
                $stmtProfile = $pdo->prepare('
                    INSERT INTO student_profiles (user_id, institution, study_program, semester, location, phone, bio)
                    VALUES (:user_id, :institution, :study_program, :semester, :location, :phone, :bio)
                    ON CONFLICT (user_id)
                    DO UPDATE SET
                        institution = EXCLUDED.institution,
                        study_program = EXCLUDED.study_program,
                        semester = EXCLUDED.semester,
                        location = EXCLUDED.location,
                        phone = COALESCE(EXCLUDED.phone, student_profiles.phone),
                        bio = COALESCE(EXCLUDED.bio, student_profiles.bio),
                        updated_at = CURRENT_TIMESTAMP
                ');
                $stmtProfile->execute([
                    ':user_id'       => $user_id,
                    ':institution'   => $institution,
                    ':study_program' => $study_program,
                    ':semester'      => $semester,
                    ':location'      => $location,
                    ':phone'         => $phone,
                    ':bio'           => $bio,
                ]);
            }

            echo json_encode(['success' => true, 'message' => 'Profile updated']);
            exit;
        }

        // Upload avatar image
        if ($action === 'upload_avatar') {
            $raw = $input['avatar_base64'] ?? '';
            if (!$raw) {
                http_response_code(400);
                echo json_encode(['error' => 'No image data provided']);
                exit;
            }

            $contentType = 'image/webp';
            if (preg_match('/^data:(image\/\w+);base64,/', $raw, $matches)) {
                $contentType = $matches[1];
                $raw = substr($raw, strpos($raw, ',') + 1);
            }
            $binary = base64_decode($raw);
            if (!$binary) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid image base64 data']);
                exit;
            }

            $ext = ($contentType === 'image/jpeg' || $contentType === 'image/jpg') ? 'jpg' : (($contentType === 'image/png') ? 'png' : 'webp');
            $objectKey = "avatars/user_{$user_id}_" . time() . ".{$ext}";

            $uploadRes = r2_upload_bytes($objectKey, $binary, $contentType);
            $avatarUrl = $uploadRes['url'];

            // Update users table
            $stmt = $pdo->prepare('UPDATE users SET avatar_url = :url, updated_at = CURRENT_TIMESTAMP WHERE id = :user_id');
            $stmt->execute([':url' => $avatarUrl, ':user_id' => $user_id]);

            $_SESSION['user']['avatar_url'] = $avatarUrl;

            echo json_encode([
                'success'    => true,
                'message'    => 'Avatar updated successfully',
                'avatar_url' => r2_display_url($avatarUrl),
                'storage'    => $uploadRes['storage'] ?? 'data_uri'
            ]);
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
