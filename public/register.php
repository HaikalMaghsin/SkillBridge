<?php
// public/register.php
// Halaman Registrasi Mahasiswa Baru
require_once __DIR__ . '/../includes/auth/auth.php';

if (is_logged_in()) {
    redirect_to_dashboard(get_user_role());
}

$page_title = 'Register · SkillBridge';
$role = 'public';
$path_prefix = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_verify()) {
        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($name === '' || $email === '' || $password === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (mb_strlen($name) > 100 || strlen($email) > 150) {
        $error = 'Nama maksimal 100 karakter dan email maksimal 150 karakter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Kata sandi minimal harus 8 karakter.';
    } elseif (strlen($password) > 72) {
        // bcrypt hanya memakai 72 byte pertama
        $error = 'Kata sandi maksimal 72 karakter.';
    } else {
        $pdo = get_db_connection();
        if (!$pdo) {
            $error = 'Layanan pendaftaran sedang tidak tersedia. Coba lagi beberapa saat lagi.';
        } else {
            try {
                $pdo->beginTransaction();
                $ins = $pdo->prepare(
                    'INSERT INTO users (name, email, password_hash, role)
                     VALUES (:name, :email, :hash, :role) RETURNING id'
                );
                $ins->execute([
                    ':name'  => $name,
                    ':email' => $email,
                    ':hash'  => password_hash($password, PASSWORD_DEFAULT),
                    ':role'  => app_role_to_db_role('mahasiswa'), // 'user'
                ]);
                $user_id = (int) $ins->fetchColumn();

                // Setiap mahasiswa punya satu baris profil (target karier diisi kemudian).
                $pdo->prepare('INSERT INTO student_profiles (user_id) VALUES (:id)')
                    ->execute([':id' => $user_id]);
                $pdo->commit();

                $result = attempt_login($email, $password);
                if ($result['success']) {
                    redirect_to_dashboard($result['role'], $path_prefix);
                }
                header('Location: ' . base_url('login.php'));
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($e->getCode() === '23505') { // unique_violation (email)
                    $error = 'Email ini sudah terdaftar. Silakan login.';
                } else {
                    error_log('Register error: ' . $e->getMessage());
                    $error = 'Terjadi kesalahan sistem saat mendaftar.';
                }
            }
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

        <h1 style="font-size: 28px; font-weight: 700; text-align: center; margin-bottom: 8px">Create account</h1>
        <p style="text-align: center; color: var(--text-muted); font-size: 14px; margin-bottom: 24px">Start tracking your skills and building evidence.</p>

        <?php if (!empty($error)): ?>
          <div style="background: #FEF2F2; color: #991B1B; border: 1px solid #F87171; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px;">
            <?= e($error) ?>
          </div>
        <?php endif; ?>

        <form id="register-form" method="POST" action="<?= e(base_url('register.php')) ?>" style="display: grid; gap: 16px">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />

          <div class="field">
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" placeholder="Jhon Doe" required maxlength="100" autocomplete="name" value="<?= e($_POST['name'] ?? '') ?>" />
          </div>

          <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required maxlength="150" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" />
          </div>

          <div class="field">
            <label for="password">Password</label>
            <div class="password-input-wrap">
              <input type="password" id="password" name="password" placeholder="At least 8 characters" minlength="8" maxlength="72" required autocomplete="new-password" />
              <button type="button" class="password-toggle-btn" data-password-toggle="password" aria-label="Tampilkan kata sandi" aria-pressed="false">
                <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; height: 44px">Create Account</button>
        </form>

        <p style="text-align: center; font-size: 14px; color: var(--text-muted); margin-top: 20px">
          Already have an account?
          <a href="login.php" style="color: var(--primary); font-weight: 700; text-decoration: none">Sign in</a>
        </p>

        <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border-light, #e2e8f0); text-align: center;">
          <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 6px;">Merekrut talenta mahasiswa untuk perusahaan Anda?</p>
          <a href="register-hr.php" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; color: var(--primary); font-weight: 700; font-size: 13px; text-decoration: none;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M9 8h1M9 12h1M9 16h1M14 8h1M14 12h1M14 16h1M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/></svg>
            Daftar sebagai Perusahaan / HR &rarr;
          </a>
        </div>
      </article>
    </main>

    <script src="<?= e(asset('js/ui.js')) ?>"></script>
    <script src="<?= e(asset('js/api.js')) ?>"></script>
    <script src="<?= e(asset('js/pages/register.js')) ?>"></script>
  </body>
</html>
