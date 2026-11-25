<?php
// public/forgot-password.php
// Halaman Permintaan Reset Kata Sandi untuk 3 Role (Mahasiswa, HR, Admin)

require_once __DIR__ . '/../includes/auth/auth.php';
require_once __DIR__ . '/../includes/auth/password_reset.php';

// Jika sudah login, langsung lempar ke dashboard role masing-masing
if (is_logged_in()) {
    redirect_to_dashboard(get_user_role());
}

$page_title = 'Lupa Kata Sandi · SkillBridge';
$role = 'public';
$error = '';
$success = '';
$preview_link = '';
$email_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Sesi formulir telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $email_input = trim($_POST['email'] ?? '');
        if ($email_input === '') {
            $error = 'Alamat email wajib diisi.';
        } else {
            $result = request_password_reset($email_input);
            if ($result['success']) {
                $success = $result['message'];
                // Jika sedang dalam lingkungan offline/lokal tanpa SMTP aktif, berikan link langsung untuk mempermudah testing
                if (!empty($result['reset_url']) && in_array($result['mail_mode'] ?? '', ['log', 'fallback_log'], true)) {
                    $preview_link = $result['reset_url'];
                }
            } else {
                $error = $result['error'] ?? 'Terjadi kesalahan saat memproses permintaan.';
            }
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

        <h1 style="font-size: 26px; font-weight: 700; text-align: center; margin-bottom: 8px">Lupa Kata Sandi?</h1>
        <p style="text-align: center; color: var(--text-muted); font-size: 14px; margin-bottom: 22px; line-height: 1.5;">
          Masukkan alamat email akun Anda (berlaku untuk <strong>Mahasiswa</strong>, <strong>HR Mitra</strong>, maupun <strong>Admin</strong>). Kami akan mengirimkan tautan verifikasi untuk membuat kata sandi baru.
        </p>

        <?php if (!empty($error)): ?>
          <div style="background: #FEF2F2; color: #991B1B; border: 1px solid #F87171; padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; line-height: 1.5;">
            <strong>Gagal:</strong> <?= e($error) ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
          <div style="background: #F0FDF4; color: #166534; border: 1px solid #86EFAC; padding: 14px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; line-height: 1.6;">
            <div style="display: flex; gap: 8px; align-items: flex-start;">
              <span style="font-size: 16px;">✓</span>
              <div>
                <strong>Tautan Berhasil Dikirim!</strong><br />
                <?= e($success) ?>
              </div>
            </div>
          </div>

          <?php if (!empty($preview_link)): ?>
            <!-- Helper box saat pengujian di localhost / offline -->
            <div style="background: #EFF6FF; border: 1px dashed #93C5FD; border-radius: 8px; padding: 14px; margin-bottom: 20px;">
              <p style="font-size: 12px; font-weight: 700; color: #1E40AF; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                ⚡ Mode Pengujian Lokal (SMTP Offline):
              </p>
              <p style="font-size: 12px; color: #1E3A8A; margin-bottom: 10px; line-height: 1.4;">
                Email dicatat di <code>data/mail_logs.json</code>. Anda dapat mengklik tombol di bawah ini untuk langsung mencoba verifikasi reset kata sandi:
              </p>
              <a href="<?= e($preview_link) ?>" class="btn btn-primary btn-sm" style="display: inline-block; text-decoration: none; font-size: 13px;">
                Buka Link Verifikasi Reset &rarr;
              </a>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="<?= e(base_url('forgot-password.php')) ?>" style="display: grid; gap: 18px">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />

          <div class="field">
            <label for="email">Alamat Email Terdaftar</label>
            <input
              type="email"
              id="email"
              name="email"
              placeholder="nama@email.com"
              required
              autocomplete="email"
              value="<?= e($email_input) ?>"
              autofocus
            />
            <small style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">
              Pastikan email yang dimasukkan sama dengan yang terdaftar saat registrasi.
            </small>
          </div>

          <button type="submit" class="btn btn-primary btn-block" style="height: 44px; font-weight: 600;">
            Kirim Tautan Verifikasi Email
          </button>
        </form>

        <p style="text-align: center; font-size: 14px; color: var(--text-muted); margin-top: 24px">
          Ingat kata sandi Anda?
          <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: none">
            &larr; Kembali ke halaman Login
          </a>
        </p>
      </article>
    </main>

    <script src="<?= e(asset('js/ui.js')) ?>"></script>
  </body>
</html>
