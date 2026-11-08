<?php
// includes/auth/auth.php
// Autentikasi, sesi, dan hak akses multi-role (mahasiswa, hr, admin).
//
// Role di database (enum user_role): 'user' | 'admin' (| 'hr' bila migrasi opsional dijalankan).
// Role di aplikasi (folder & sesi):  'mahasiswa' | 'hr' | 'admin'.
// 'user' (DB) <-> 'mahasiswa' (aplikasi) dipetakan oleh dua fungsi di bawah.

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

/** Mode demo: hanya aktif jika APP_DEMO=1 DAN host adalah localhost/127.0.0.1. Default MATI. */
function demo_mode() {
    if (getenv('APP_DEMO') !== '1') {
        return false;
    }

    // Hanya boleh aktif di localhost/127.0.0.1
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    $is_local = in_array($host, ['localhost', '127.0.0.1', 'localhost:8000', '127.0.0.1:8000'], true)
                || strpos($host, 'localhost:') === 0
                || strpos($host, '127.0.0.1:') === 0;

    return $is_local;
}

function db_role_to_app_role($role) {
    return $role === 'user' ? 'mahasiswa' : (string) $role;
}

function app_role_to_db_role($role) {
    return $role === 'mahasiswa' ? 'user' : (string) $role;
}

/** Normalisasi boolean PostgreSQL ('t'/'f', 1/0, true/false) ke bool PHP. */
function db_is_true($value) {
    if (is_bool($value)) {
        return $value;
    }
    return in_array($value, [1, '1', 't', 'true', 'TRUE', 'y', 'yes'], true);
}

/**
 * Sinkronkan sesi dengan baris users terbaru (A4).
 *
 * - role, nama, dan email mengikuti database, bukan hanya nilai saat login;
 * - akun yang dinonaktifkan atau sudah tidak ada langsung di-logout dari sesi;
 * - bila database sedang tidak tersedia, sesi lama dipertahankan agar halaman
 *   user tidak ikut mati; endpoint yang membutuhkan database tetap menolak.
 */
function sync_session_user() {
    static $synced = false;

    if (empty($_SESSION['user']['id'])) {
        return false;
    }
    if ($synced) {
        return true;
    }
    $synced = true;

    if (demo_mode()) {
        return true;
    }

    $pdo = get_db_connection();
    if (!$pdo) {
        error_log('Session sync skipped: database unavailable');
        return true;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT id, name, email, role, is_active FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => (int) $_SESSION['user']['id']]);
        $row = $stmt->fetch();
    } catch (Exception $e) {
        error_log('Session sync error: ' . $e->getMessage());
        return true;
    }

    if (!$row || !db_is_true($row['is_active'])) {
        unset($_SESSION['user']);
        return false;
    }

    $role = db_role_to_app_role($row['role']);
    $_SESSION['user'] = [
        'id'         => (int) $row['id'],
        'name'       => $row['name'],
        'email'      => $row['email'],
        'role'       => $role,
        'role_label' => role_label($role),
        'initials'   => make_initials($row['name']),
    ];

    return true;
}

function current_user() {
    sync_session_user();
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return sync_session_user();
}

function get_user_role() {
    return $_SESSION['user']['role'] ?? null;
}

function make_initials($name) {
    $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) {
        return 'SB';
    }
    $first = mb_strtoupper(mb_substr($parts[0], 0, 1));
    $second = isset($parts[1])
        ? mb_strtoupper(mb_substr($parts[1], 0, 1))
        : mb_strtoupper(mb_substr($parts[0], 1, 1));
    return $first . $second;
}

function role_label($role) {
    switch ($role) {
        case 'admin': return 'Administrator';
        case 'hr':    return 'Recruiter';
        default:      return 'Student';
    }
}

/**
 * Guard halaman. WAJIB login; jika $required_role diberikan, role harus cocok.
 * - Belum login            -> redirect ke login.php
 * - Role tidak sesuai      -> redirect ke dashboard role miliknya sendiri
 * Parameter $redirect_on_fail dipertahankan demi kompatibilitas, tidak lagi dipakai.
 * Mode demo (APP_DEMO=1) tetap memakai perilaku lama untuk keperluan pengujian UI.
 */
function check_auth($required_role = null, $redirect_on_fail = true) {
    header('Cache-Control: no-store');

    if (demo_mode()) {
        if (!is_logged_in() && $required_role) {
            login_demo_user($required_role);
        } elseif ($required_role && get_user_role() !== $required_role) {
            login_demo_user($required_role);
        }
        return true;
    }

    if (!is_logged_in()) {
        header('Location: ' . base_url('login.php'));
        exit;
    }

    if ($required_role && get_user_role() !== $required_role) {
        redirect_to_dashboard(get_user_role());
    }

    return true;
}

/** Sesi demo. Hanya boleh dipanggil saat demo_mode() aktif. */
function login_demo_user($role = 'mahasiswa') {
    if (!demo_mode()) {
        return null;
    }
    $demo_users = [
        'mahasiswa' => [
            'id' => 1, 'name' => 'Jhon Doe', 'email' => 'jhondoe@gmail.com',
            'role' => 'mahasiswa', 'role_label' => 'Student', 'initials' => 'JD',
        ],
        'hr' => [
            'id' => 2, 'name' => 'Aditya Pratama', 'email' => 'aditya@techindonesia.com',
            'role' => 'hr', 'role_label' => 'Company Admin · PT Tech Indonesia',
            'company_name' => 'PT Tech Indonesia', 'initials' => 'AP',
        ],
        'admin' => [
            'id' => 3, 'name' => 'Aditya Pratama', 'email' => 'admin@skillbridge.id',
            'role' => 'admin', 'role_label' => 'Admin Utama · Platform Owner', 'initials' => 'AP',
        ],
    ];
    $_SESSION['user'] = $demo_users[$role] ?? $demo_users['mahasiswa'];
    return $_SESSION['user'];
}

/**
 * Login. Hasil: ['success' => bool, 'role' => string, 'error' => 'invalid'|'unavailable'].
 * Pesan error ke pengguna sengaja generik agar tidak membocorkan apakah email terdaftar.
 */
function attempt_login($email, $password) {
    $email = strtolower(trim((string) $email));
    $password = (string) $password;
    $pdo = get_db_connection();

    if ($pdo) {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, name, email, password_hash, role, is_active FROM users WHERE email = :email LIMIT 1'
            );
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            // Selalu jalankan password_verify agar waktu respons tidak membedakan email ada/tidak.
            static $dummy_hash = null;
            $dummy_hash = $dummy_hash ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $valid = password_verify($password, $user['password_hash'] ?? $dummy_hash);

            if ($user && $valid && db_is_true($user['is_active'])) {
                session_regenerate_id(true);
                $role = db_role_to_app_role($user['role']);
                $_SESSION['user'] = [
                    'id'         => (int) $user['id'],
                    'name'       => $user['name'],
                    'email'      => $user['email'],
                    'role'       => $role,
                    'role_label' => role_label($role),
                    'initials'   => make_initials($user['name']),
                ];
                return ['success' => true, 'role' => $role];
            }
            return ['success' => false, 'error' => 'invalid'];
        } catch (Exception $e) {
            error_log('DB login error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'unavailable'];
        }
    }

    // Tanpa database: hanya akun demo yang eksplisit, dan hanya saat APP_DEMO=1.
    if (demo_mode()) {
        $demo_creds = [
            'admin@skillbridge.id'      => 'admin',
            'hr@techindonesia.com'      => 'hr',
            'jhondoe@gmail.com'         => 'mahasiswa',
            'mahasiswa@skillbridge.id'  => 'mahasiswa',
        ];
        if (isset($demo_creds[$email]) && $password === 'password123') {
            session_regenerate_id(true);
            login_demo_user($demo_creds[$email]);
            return ['success' => true, 'role' => $demo_creds[$email]];
        }
        return ['success' => false, 'error' => 'invalid'];
    }

    return ['success' => false, 'error' => 'unavailable'];
}

function redirect_to_dashboard($role, $path_prefix = '') {
    switch ($role) {
        case 'admin':
            $target = 'admin/index.php';
            break;
        case 'hr':
            $target = 'hr/index.php';
            break;
        default:
            $target = 'mahasiswa/dashboard.php';
    }
    header('Location: ' . base_url($target));
    exit;
}

function logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
