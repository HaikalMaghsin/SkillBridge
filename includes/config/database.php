<?php
// includes/config/database.php
// Konfigurasi Database PostgreSQL (Supabase) & Pengaturan Aplikasi

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Database (dapat disesuaikan dengan kredensial Supabase Anda)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: '');

function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 3,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Fallback jika database belum aktif agar prototipe frontend tetap dapat di-run
        error_log('Database connection error: ' . $e->getMessage());
        return null;
    }
}

// CSRF Token Helper
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
