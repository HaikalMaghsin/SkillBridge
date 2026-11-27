<?php
// public/reset-password.php
// Halaman Pembuatan Kata Sandi Baru Berdasarkan Token Verifikasi Email

require_once __DIR__ . '/../includes/auth/auth.php';
require_once __DIR__ . '/../includes/auth/password_reset.php';

if (is_logged_in()) {
    redirect_to_dashboard(get_user_role());
}

$page_title = 'Atur Kata Sandi Baru · SkillBridge';
$role = 'public';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$verification = verify_password_reset_token($token);

$error = '';
$success = false;
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Sesi formulir telah kedaluwarsa. Silakan muat ulang halaman.';
    } elseif (!$verification['valid']) {
        $error = $verification['error'];
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $result = complete_password_reset($token, $new_password, $confirm_password);
        if ($result['success']) {
            $success = true;
            $success_message = $result['message'];
        } else {
            $error = $result['error'] ?? 'Gagal memperbarui kata sandi.';
        }
    }
}

require_once __DIR__ . '/../includes/layouts/head.php';
?>
    <main class="auth-main">
      <article class="auth-card card" style="max-width: 460px;">
        <div class="auth-logo">
          <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--primary); color: #fff; display: grid; place-items: center; font-size: 18px; font-weight: 800">S</div>
          <span>SkillBridge</span>
        </div>

        <?php if ($success): ?>
          <!-- Tampilan Berhasil Reset -->
          <div style="text-align: center; padding: 12px 0 8px;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: #DCFCE7; color: #16A34A; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 16px; font-weight: 800;">
              ✓
            </div>
            <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 8px">Kata Sandi Diperbarui!</h1>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px; line-height: 1.5;">
              <?= e($success_message) ?>
            </p>
            <a href="login.php" class="btn btn-primary btn-block" style="height: 44px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-weight: 600;">
              Masuk ke Akun Sekarang &rarr;
            </a>
          </div>

        <?php elseif (!$verification['valid']): ?>
          <!-- Tampilan Token Tidak Valid / Kedaluwarsa -->
          <div style="text-align: center; padding: 12px 0 8px;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: #FEE2E2; color: #DC2626; display: inline-flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 16px;">
              ✕
            </div>
            <h1 style="font-size: 22px; font-weight: 700; margin-bottom: 8px">Tautan Tidak Valid</h1>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px; line-height: 1.5;">
              <?= e($verification['error']) ?>
            </p>
            <a href="forgot-password.php" class="btn btn-primary btn-block" style="height: 44px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-weight: 600;">
              Minta Tautan Reset Baru &rarr;
            </a>
            <p style="margin-top: 18px; font-size: 13px;">
              <a href="login.php" style="color: var(--text-muted); text-decoration: none;">&larr; Kembali ke Login</a>
            </p>
          </div>

        <?php else: ?>
          <!-- Form Pembuatan Kata Sandi Baru -->
          <h1 style="font-size: 24px; font-weight: 700; text-align: center; margin-bottom: 6px">Buat Kata Sandi Baru</h1>
          <p style="text-align: center; color: var(--text-muted); font-size: 14px; margin-bottom: 18px">
            Atur kata sandi baru untuk akun Anda.
          </p>

          <!-- Kartu Identitas Akun Pengguna -->
          <div style="background: var(--surface-subtle, #F8FAFC); border: 1px solid var(--border-light, #E2E8F0); border-radius: 8px; padding: 12px 14px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
            <div>
              <p style="font-size: 14px; font-weight: 700; color: #0F172A; margin: 0;"><?= e($verification['name']) ?></p>
              <p style="font-size: 12px; color: var(--text-muted); margin: 2px 0 0;"><?= e($verification['email']) ?></p>
            </div>
            <span style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 9999px; background: #EEF2FF; color: #4F46E5; text-transform: uppercase; letter-spacing: 0.3px;">
              <?= e($verification['role_label']) ?>
            </span>
          </div>

          <?php if (!empty($error)): ?>
            <div style="background: #FEF2F2; color: #991B1B; border: 1px solid #F87171; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 18px;">
              <?= e($error) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="<?= e(base_url('reset-password.php')) ?>" style="display: grid; gap: 16px">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
            <input type="hidden" name="token" value="<?= e($token) ?>" />

            <div class="field">
              <label for="new_password">Kata Sandi Baru</label>
              <div class="password-input-wrap" style="position: relative; display: flex; align-items: center; width: 100%;">
                <input
                  type="password"
                  id="new_password"
                  name="new_password"
                  placeholder="Minimal 8 karakter"
                  required
                  minlength="8"
                  maxlength="72"
                  autocomplete="new-password"
                  autofocus
                  style="width: 100%; padding-right: 44px; box-sizing: border-box;"
                />
                <button
                  type="button"
                  class="password-toggle-btn"
                  onclick="togglePasswordVisibility('new_password', this)"
                  data-password-toggle="new_password"
                  aria-label="Tampilkan kata sandi"
                  aria-pressed="false"
                  title="Tampilkan kata sandi"
                  style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; margin: 0; border: none !important; outline: none !important; background: transparent !important; box-shadow: none !important; -webkit-appearance: none; appearance: none; cursor: pointer; color: #64748b; z-index: 2; border-radius: 6px;"
                >
                  <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: block; pointer-events: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg class="icon-eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none; pointer-events: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
            </div>

            <div class="field">
              <label for="confirm_password">Konfirmasi Kata Sandi Baru</label>
              <div class="password-input-wrap" style="position: relative; display: flex; align-items: center; width: 100%;">
                <input
                  type="password"
                  id="confirm_password"
                  name="confirm_password"
                  placeholder="Ulangi kata sandi baru"
                  required
                  minlength="8"
                  maxlength="72"
                  autocomplete="new-password"
                  style="width: 100%; padding-right: 44px; box-sizing: border-box;"
                />
                <button
                  type="button"
                  class="password-toggle-btn"
                  onclick="togglePasswordVisibility('confirm_password', this)"
                  data-password-toggle="confirm_password"
                  aria-label="Tampilkan kata sandi"
                  aria-pressed="false"
                  title="Tampilkan kata sandi"
                  style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; margin: 0; border: none !important; outline: none !important; background: transparent !important; box-shadow: none !important; -webkit-appearance: none; appearance: none; cursor: pointer; color: #64748b; z-index: 2; border-radius: 6px;"
                >
                  <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: block; pointer-events: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg class="icon-eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none; pointer-events: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="height: 44px; font-weight: 600; margin-top: 6px;">
              Simpan Kata Sandi Baru
            </button>
          </form>
        <?php endif; ?>
      </article>
    </main>

    <script>
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
    <script src="<?= e(asset('js/ui.js')) ?>"></script>
  </body>
</html>
