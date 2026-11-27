<?php
// includes/layouts/head.php
// Head HTML bersama untuk seluruh role (Public, Mahasiswa, HR, Admin)
// Sesuai PRD Bagian 4

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../auth/csrf.php';

$page_title = $page_title ?? 'SkillBridge';
$role = $role ?? 'public';
$body_class = $body_class ?? '';
$body_attrs = $body_attrs ?? '';
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= e($page_title) ?></title>
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <!-- Stylesheet Utama & Per Role -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>" />
    <?php if ($role === 'hr'): ?>
      <link rel="stylesheet" href="<?= asset('css/recruiter.css') ?>" />
    <?php elseif ($role === 'admin'): ?>
      <link rel="stylesheet" href="<?= asset('css/admin.css') ?>" />
    <?php endif; ?>
    <style>
      .password-input-wrap { position: relative !important; display: flex !important; align-items: center !important; width: 100% !important; }
      .password-input-wrap input { width: 100% !important; padding-right: 44px !important; box-sizing: border-box !important; }
      .password-toggle-btn { position: absolute !important; right: 8px !important; top: 50% !important; transform: translateY(-50%) !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 32px !important; height: 32px !important; padding: 0 !important; margin: 0 !important; border: none !important; outline: none !important; border-radius: 6px !important; background: transparent !important; color: #64748b !important; cursor: pointer !important; z-index: 2 !important; }
      .password-toggle-btn:hover { background: #f1f5f9 !important; color: #0f172a !important; }
      .password-toggle-btn .icon-eye, .password-toggle-btn .icon-eye-off { width: 20px !important; height: 20px !important; pointer-events: none !important; }
    </style>
    <script>
      window.APP_BASE_URL = <?= json_encode(rtrim(base_url(), '/')) ?>;
      function togglePasswordVisibility(inputId, btn) {
        var input = null;
        if (btn && btn.closest) {
          var wrap = btn.closest('.password-input-wrap');
          if (wrap) input = wrap.querySelector('input');
        }
        if (!input && inputId) {
          input = document.getElementById(inputId);
        }
        if (!input) return;
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        if (btn) {
          var eye = btn.querySelector('.icon-eye');
          var eyeOff = btn.querySelector('.icon-eye-off');
          if (eye) eye.style.display = isPassword ? 'none' : 'block';
          if (eyeOff) eyeOff.style.display = isPassword ? 'block' : 'none';
          btn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
          btn.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
          btn.setAttribute('title', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        }
      }
      window.togglePasswordVisibility = togglePasswordVisibility;
    </script>
    <?= $extra_head ?? '' ?>
  </head>
  <body class="<?= e($body_class) ?>" <?= $body_attrs ?>>
