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

        $formatted = array_map(function($t) use ($user) {
            return [
                'id' => (int)$t['id'],
                'user_id' => (int)$t['user_id'],
                'name' => $t['name'],
                'email' => $t['email'],
                'role' => $t['role_title'],
                'status' => $t['status'],
                'joined' => $t['created_at'],
                'is_self' => ((int)$t['user_id'] === (int)$user['id'])
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

    // POST: Tambah anggota tim (buat user baru atau kaitkan user HR yang ada)
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $role_title = trim($input['role_title'] ?? 'Recruiter');
        $password = trim($input['password'] ?? '');

        if (!$name || !$email) {
            http_response_code(400);
            echo json_encode(['error' => 'Nama lengkap dan email perusahaan wajib diisi']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Format email tidak valid']);
            exit;
        }

        // Cek apakah user sudah terdaftar di tabel users
        $stmt = $pdo->prepare('SELECT id, role, is_active FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            if ($existingUser['role'] !== 'hr') {
                http_response_code(400);
                echo json_encode(['error' => 'Email ini terdaftar sebagai role ' . $existingUser['role'] . '. Hanya akun HR yang dapat ditambahkan ke tim rekrutmen.']);
                exit;
            }

            $target_user_id = (int)$existingUser['id'];

            // Cek apakah sudah tergabung di perusahaan ini
            $stmtCheck = $pdo->prepare('SELECT id, status FROM company_recruiters WHERE company_id = :comp_id AND user_id = :uid LIMIT 1');
            $stmtCheck->execute([':comp_id' => $company_id, ':uid' => $target_user_id]);
            $existingMember = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existingMember) {
                // Aktifkan kembali jika status sebelumnya inactive/pending
                $stmtUp = $pdo->prepare('UPDATE company_recruiters SET status = :status, role_title = :role, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
                $stmtUp->execute([':status' => 'active', ':role' => $role_title, ':id' => $existingMember['id']]);

                echo json_encode(['success' => true, 'message' => 'Status anggota tim diperbarui menjadi aktif']);
                exit;
            }
        } else {
            // Buat akun HR baru
            if (empty($password)) {
                $password = 'SkillBridge123!';
            } elseif (strlen($password) < 8) {
                http_response_code(400);
                echo json_encode(['error' => 'Password minimal 8 karakter']);
                exit;
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmtCreate = $pdo->prepare('
                INSERT INTO users (name, email, password_hash, role, is_active)
                VALUES (:name, :email, :password_hash, :role, :is_active)
                RETURNING id
            ');
            $stmtCreate->execute([
                ':name' => $name,
                ':email' => $email,
                ':password_hash' => $password_hash,
                ':role' => 'hr',
                ':is_active' => true
            ]);
            $createdUser = $stmtCreate->fetch(PDO::FETCH_ASSOC);
            $target_user_id = (int)$createdUser['id'];
        }

        // Masukkan ke company_recruiters
        $stmtLink = $pdo->prepare('
            INSERT INTO company_recruiters (company_id, user_id, role_title, status)
            VALUES (:company_id, :user_id, :role_title, :status)
        ');
        $stmtLink->execute([
            ':company_id' => $company_id,
            ':user_id' => $target_user_id,
            ':role_title' => $role_title,
            ':status' => 'active'
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Anggota tim recruiter berhasil ditambahkan'
        ]);
        exit;
    }

    // PUT: Update status atau role_title anggota
    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $member_id = isset($input['id']) ? (int)$input['id'] : null;

        if (!$member_id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID anggota tim wajib disertakan']);
            exit;
        }

        // Cek anggota
        $stmtCheck = $pdo->prepare('SELECT id, user_id FROM company_recruiters WHERE id = :id AND company_id = :comp_id');
        $stmtCheck->execute([':id' => $member_id, ':comp_id' => $company_id]);
        $targetMember = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$targetMember) {
            http_response_code(404);
            echo json_encode(['error' => 'Anggota tim tidak ditemukan']);
            exit;
        }

        $updates = [];
        $params = [':id' => $member_id, ':company_id' => $company_id];

        if (isset($input['status'])) {
            $status = trim($input['status']);
            if (!in_array($status, ['active', 'inactive', 'pending'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Status tidak valid']);
                exit;
            }
            $updates[] = 'status = :status';
            $params[':status'] = $status;
        }

        if (isset($input['role_title']) && !empty(trim($input['role_title']))) {
            $updates[] = 'role_title = :role_title';
            $params[':role_title'] = trim($input['role_title']);
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'Tidak ada perubahan yang dikirim']);
            exit;
        }

        $updates[] = 'updated_at = CURRENT_TIMESTAMP';
        $sql = 'UPDATE company_recruiters SET ' . implode(', ', $updates) . ' WHERE id = :id AND company_id = :company_id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Peran dan status anggota tim berhasil diperbarui'
        ]);
        exit;
    }

    // DELETE: Hapus anggota dari tim
    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $member_id = isset($input['id']) ? (int)$input['id'] : null;

        if (!$member_id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID anggota wajib disertakan']);
            exit;
        }

        // Cek anggota & larang hapus diri sendiri
        $stmtCheck = $pdo->prepare('SELECT user_id FROM company_recruiters WHERE id = :id AND company_id = :comp_id');
        $stmtCheck->execute([':id' => $member_id, ':comp_id' => $company_id]);
        $member = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            http_response_code(404);
            echo json_encode(['error' => 'Anggota tim tidak ditemukan']);
            exit;
        }

        if ((int)$member['user_id'] === (int)$user['id']) {
            http_response_code(400);
            echo json_encode(['error' => 'Anda tidak dapat menghapus akun Anda sendiri']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM company_recruiters WHERE id = :id AND company_id = :company_id');
        $stmt->execute([':id' => $member_id, ':company_id' => $company_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Anggota tim berhasil dihapus'
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API hr/team.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
