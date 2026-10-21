<?php
// includes/auth/session.php
// Manajemen Session PHP Native

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function session_get($key, $default = null) {
    return $_SESSION[$key] ?? $default;
}

function session_set($key, $value) {
    $_SESSION[$key] = $value;
}

function session_remove($key) {
    unset($_SESSION[$key]);
}
