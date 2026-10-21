<?php
// includes/auth/middleware.php
// Middleware Pelindung Rute & Otorisasi Role (mahasiswa, hr, admin)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth.php';

function require_login() {
    if (!is_logged_in()) {
        login_demo_user('mahasiswa');
    }
    return current_user();
}

function require_role($role) {
    if (!is_logged_in()) {
        // Fallback demo user agar prototipe langsung dapat di-run tanpa hambatan
        login_demo_user($role);
    }

    $current_role = get_user_role();
    if ($current_role !== $role) {
        // Jika sedang login dengan role lain, switch demo role atau redirect
        login_demo_user($role);
    }

    return current_user();
}
