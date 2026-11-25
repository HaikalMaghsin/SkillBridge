<?php
// public/login.php
// Halaman Login Multi-Role (Mahasiswa, HR, Admin)
require_once __DIR__ . '/../includes/auth/auth.php';

// Sudah login -> langsung ke dashboard role-nya
if (is_logged_in()) {
    redirect_to_dashboard(get_user_role());
}

$page_title = 'Login · SkillBridge';
$role = 'public';
$path_prefix = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true) ?? [];
        $email = $json['email'] ?? '';
        $password = $json['password'] ?? '';
        $isJson = true;
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $isJson = false;
    }

    if (!csrf_verify()) {
        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } else {
        $result = attempt_login($email, $password);
        if ($result['success']) {
            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'role' => $result['role'],
                    'message' => 'Login berhasil'
                ]);
                exit;
            }
            redirect_to_dashboard($result['role'], $path_prefix);
        } elseif (($result['error'] ?? '') === 'unavailable') {
            $error = 'Layanan login sedang tidak tersedia. Coba lagi beberapa saat lagi.';
        } else {
            if ($isJson) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Email atau kata sandi tidak valid.']);
                exit;
            }
            $error = 'Email atau kata sandi tidak valid.';
        }
    }
}

require_once __DIR__ . '/../includes/layouts/head.php';
?>
    <main class="auth-main">
      <article class="auth-card card">
        <div class="auth-logo">
          <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--primary); color: #fff; display: grid; place-items: center; font-size: 18px; font-weight: 800">S</div>
          <span>SkillBridge</span>
        </div>

        <h1 style="font-size: 28px; font-weight: 700; text-align: center; margin-bottom: 8px">Welcome back</h1>
        <p style="text-align: center; color: var(--text-muted); font-size: 14px; margin-bottom: 20px">Sign in to your account to continue building your portfolio.</p>

        <?php if (!empty($error)): ?>
          <div style="background: #FEF2F2; color: #991B1B; border: 1px solid #F87171; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px;">
            <?= e($error) ?>
          </div>
        <?php endif; ?>

        <?php if (demo_mode()): ?>
        <!-- Pengalih role demo: hanya tampil saat APP_DEMO=1 -->
        <div style="background: var(--surface-subtle, #f8fafc); border: 1px dashed var(--border-light, #cbd5e1); border-radius: 8px; padding: 12px; margin-bottom: 20px;">
          <p style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Akses Cepat Demo Role:</p>
          <div style="display: flex; gap: 6px; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="setDemo('jhondoe@gmail.com', 'password123')">Mahasiswa</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setDemo('hr@techindonesia.com', 'password123')">HR / Recruiter</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="setDemo('admin@skillbridge.id', 'password123')">Admin</button>
          </div>
        </div>
        <script>
          function setDemo(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
          }
        </script>
        <?php endif; ?>

        <form id="login-form" method="POST" action="<?= e(base_url('login.php')) ?>" style="display: grid; gap: 16px">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />

          <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required autocomplete="username" value="<?= e($_POST['email'] ?? '') ?>" />
          </div>

          <div class="field">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
              <label for="password" style="margin-bottom: 0;">Password</label>
              <a href="forgot-password.php" style="font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 500;">Lupa password?</a>
            </div>
            <div class="password-input-wrap">
              <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password" />
              <button type="button" class="password-toggle-btn" data-password-toggle="password" aria-label="Tampilkan kata sandi" aria-pressed="false">
                <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block" style="height: 44px">Sign In</button>
        </form>

        <p style="text-align: center; font-size: 14px; color: var(--text-muted); margin-top: 20px">
          Don't have an account?
          <a href="register.php" style="color: var(--primary); font-weight: 700; text-decoration: none">Register now</a>
        </p>

        <div style="margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border-light, #e2e8f0); text-align: center;">
          <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">Ingin bergabung sebagai mitra rekrutmen?</p>
          <a href="register-hr.php" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; color: var(--primary); font-weight: 600; font-size: 13px; text-decoration: none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M9 8h1M9 12h1M9 16h1M14 8h1M14 12h1M14 16h1M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/></svg>
            Daftar Akun Perusahaan / HR &rarr;
          </a>
        </div>
      </article>
    </main>

    <script src="<?= e(asset('js/ui.js')) ?>"></script>
    <script src="<?= e(asset('js/api.js')) ?>"></script>
    <script src="<?= e(asset('js/pages/login.js')) ?>"></script>
  </body>
</html>
