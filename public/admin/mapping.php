<?php
// public/admin/mapping.php
// Redirect bersih ke admin/careers.php karena pemetaan skill telah menyatu ke manajemen target karier.
require_once __DIR__ . '/../../includes/auth/auth.php';
require_once __DIR__ . '/../../includes/config/app.php';
check_auth('admin');

header('Location: ' . base_url('admin/careers.php'), true, 302);
exit;
