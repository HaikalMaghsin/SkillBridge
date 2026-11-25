<?php
// includes/config/app.php
// Konfigurasi URL, Base Path, dan Helper Sanitasi Output

if (!defined('BASE_URL')) {
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $script_dir = str_replace('\\', '/', dirname($script_name));
    if ($script_dir === '.' || $script_dir === '/' || $script_dir === '') {
        $clean_base = '';
    } else {
        $clean_base = rtrim($script_dir, '/');
        do {
            $stripped = preg_replace('#/(mahasiswa|hr|admin|api|tools)$#', '', $clean_base);
            if ($stripped === $clean_base) {
                break;
            }
            $clean_base = rtrim($stripped, '/');
        } while (true);
    }
    define('BASE_URL', $clean_base);
}

function base_url($path = '') {
    $path = ltrim($path, '/');
    $base = BASE_URL;
    if ($base === '' || $base === '/' || $base === '.') {
        return '/' . $path;
    }
    return rtrim($base, '/') . '/' . $path;
}

function asset($path = '') {
    return base_url($path);
}

function app_url($path = '') {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    $base = base_url($path);
    if ($base === '' || $base[0] !== '/') {
        $base = '/' . $base;
    }
    return rtrim($proto . '://' . $host, '/') . $base;
}

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
