<?php
// public/register-hr.php
// Halaman Registrasi Akun Perusahaan & Recruiter (HR Partner)
require_once __DIR__ . '/../includes/auth/auth.php';

if (is_logged_in()) {
    redirect_to_dashboard(get_user_role());
}

$page_title = 'Daftar Akun Perusahaan · SkillBridge for Employers';
$role = 'public';
$path_prefix = '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ambil data Recruiter (PIC)
    $name         = trim($_POST['name'] ?? '');
    $email        = strtolower(trim($_POST['email'] ?? ''));
    $role_title   = trim($_POST['role_title'] ?? '');
    $password     = (string) ($_POST['password'] ?? '');

    // 2. Ambil data Perusahaan
    $company_name = trim($_POST['company_name'] ?? '');
    $industry     = trim($_POST['industry'] ?? '');
    $website      = trim($_POST['website'] ?? '');
    $description  = trim($_POST['description'] ?? '');

    // Default role title jika dikosongkan
    if ($role_title === '') {
        $role_title = 'Talent Acquisition / HR';
    }

    // Normalisasi URL website jika belum ada scheme
    if ($website !== '' && !preg_match('#^https?://#i', $website)) {
        $website = 'https://' . $website;
    }

    // Validasi input
    if (!csrf_verify()) {
        $error = 'Sesi formulir kedaluwarsa. Silakan muat ulang halaman lalu coba lagi.';
    } elseif ($name === '' || $email === '' || $password === '' || $company_name === '' || $industry === '') {
        $error = 'Nama PIC, email kerja, kata sandi, nama perusahaan, dan bidang industri wajib diisi.';
    } elseif (mb_strlen($name) > 100 || strlen($email) > 150) {
        $error = 'Nama maksimal 100 karakter dan email maksimal 150 karakter.';
    } elseif (mb_strlen($company_name) > 150 || mb_strlen($industry) > 100) {
        $error = 'Nama perusahaan maksimal 150 karakter dan bidang industri maksimal 100 karakter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email perusahaan tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Kata sandi minimal harus 8 karakter.';
    } elseif (strlen($password) > 72) {
        $error = 'Kata sandi maksimal 72 karakter.';
    } else {
        $pdo = get_db_connection();
        if (!$pdo) {
            $error = 'Layanan pendaftaran sedang tidak tersedia. Coba lagi beberapa saat lagi.';
        } else {
            try {
                $pdo->beginTransaction();

                // Cek email sudah dipakai di users
                $checkUser = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
                $checkUser->execute([':email' => $email]);
                if ($checkUser->fetch()) {
                    throw new Exception('Email ini sudah terdaftar di SkillBridge. Silakan login atau gunakan email lain.');
                }

                // Cek apakah perusahaan dengan nama yang sama sudah ada di tabel companies
                $checkComp = $pdo->prepare('SELECT id FROM companies WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name)) LIMIT 1');
                $checkComp->execute([':name' => $company_name]);
                $existingComp = $checkComp->fetch(PDO::FETCH_ASSOC);

                if ($existingComp) {
                    $company_id = (int) $existingComp['id'];
                } else {
                    // Buat perusahaan baru
                    $insComp = $pdo->prepare('
                        INSERT INTO companies (name, industry, website, description)
                        VALUES (:name, :industry, :website, :description)
                        RETURNING id
                    ');
                    $insComp->execute([
                        ':name'        => $company_name,
                        ':industry'    => $industry,
                        ':website'     => $website ?: null,
                        ':description' => $description ?: null,
                    ]);
                    $company_id = (int) $insComp->fetchColumn();
                }

                // Buat user HR baru
                $insUser = $pdo->prepare('
                    INSERT INTO users (name, email, password_hash, role, is_active)
                    VALUES (:name, :email, :hash, :role, TRUE)
                    RETURNING id
                ');
                $insUser->execute([
                    ':name'  => $name,
                    ':email' => $email,
                    ':hash'  => password_hash($password, PASSWORD_DEFAULT),
                    ':role'  => 'hr',
                ]);
                $user_id = (int) $insUser->fetchColumn();

                // Hubungkan user ke perusahaan sebagai recruiter
                $insRec = $pdo->prepare('
                    INSERT INTO company_recruiters (company_id, user_id, role_title, status)
                    VALUES (:company_id, :user_id, :role_title, :status)
                    ON CONFLICT (company_id, user_id) DO NOTHING
                ');
                $insRec->execute([
                    ':company_id' => $company_id,
                    ':user_id'    => $user_id,
                    ':role_title' => $role_title,
                    ':status'     => 'active',
                ]);

                $pdo->commit();

                // Otomatis login dan redirect ke dashboard HR
                $result = attempt_login($email, $password);
                if ($result['success']) {
                    redirect_to_dashboard($result['role'], $path_prefix);
                }

                header('Location: ' . base_url('login.php?registered=hr'));
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Register HR error: ' . $e->getMessage());
                $error = $e->getMessage() ?: 'Terjadi kesalahan sistem saat mendaftarkan perusahaan.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/layouts/head.php';
?>
    <main class="auth-main" style="padding: 40px 16px;">
      <article class="auth-card card" style="max-width: 640px; padding: 32px;">
        <div class="auth-logo" style="margin-bottom: 12px;">
          <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--primary); color: #fff; display: grid; place-items: center; font-size: 20px; font-weight: 800">S</div>
          <div style="display: flex; align-items: baseline; gap: 8px;">
            <span style="font-weight: 800; font-size: 22px; color: var(--text-primary);">SkillBridge</span>
            <span style="font-size: 11px; font-weight: 700; color: var(--primary); background: var(--tint-indigo); padding: 2px 8px; border-radius: 999px; letter-spacing: 0.5px;">FOR EMPLOYERS</span>
          </div>
        </div>

        <h1 style="font-size: 26px; font-weight: 800; text-align: center; color: var(--text-primary); margin-bottom: 6px;">Daftarkan Perusahaan Mitra</h1>
        <p style="text-align: center; color: var(--text-muted); font-size: 14px; margin-bottom: 24px; line-height: 1.5;">
          Temukan, pantau skill gap, dan rekrut talenta mahasiswa terbaik dengan bukti portofolio yang tervalidasi.
        </p>

        <?php if (!empty($error)): ?>
          <div style="background: #FEF2F2; color: #991B1B; border: 1px solid #F87171; padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor" style="flex-shrink: 0;"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span><?= e($error) ?></span>
          </div>
        <?php endif; ?>

        <form id="register-hr-form" method="POST" action="<?= e(base_url('register-hr.php')) ?>" style="display: grid; gap: 24px;">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />

          <!-- Bagian 1: Data Perusahaan -->
          <fieldset style="border: 1px solid var(--border-light, #e2e8f0); border-radius: 12px; padding: 20px; margin: 0; background: var(--surface-subtle, #fcfdfe);">
            <legend style="font-size: 14px; font-weight: 700; color: var(--primary); padding: 0 10px; display: flex; align-items: center; gap: 6px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M9 8h1M9 12h1M9 16h1M14 8h1M14 12h1M14 16h1M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/></svg>
              1. Profil Perusahaan
            </legend>

            <div style="display: grid; gap: 14px; margin-top: 4px;">
              <div class="field">
                <label for="company_name" style="font-weight: 600; font-size: 13px;">Nama Resmi Perusahaan <span style="color: #ef4444;">*</span></label>
                <input type="text" id="company_name" name="company_name" placeholder="Contoh: PT Tech Indonesia" required maxlength="150" value="<?= e($_POST['company_name'] ?? '') ?>" />
              </div>

              <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                <div class="field">
                  <label for="industry" style="font-weight: 600; font-size: 13px;">Bidang Industri <span style="color: #ef4444;">*</span></label>
                  <select id="industry" name="industry" required style="height: 42px; border-radius: 8px; border: 1px solid var(--border, #cbd5e1); padding: 0 12px; font-size: 14px; background: #fff;">
                    <option value="" disabled <?= empty($_POST['industry']) ? 'selected' : '' ?>>Pilih Bidang Industri...</option>
                    <?php
                    $industries = [
                        'Teknologi Informasi & Software',
                        'Fintech & Perbankan',
                        'E-Commerce & Logistik',
                        'Konsultan IT & Digital Agency',
                        'Telekomunikasi & Jaringan',
                        'Kesehatan & Farmasi',
                        'Manufaktur & Energi',
                        'Media, Desain & Kreatif',
                        'Lainnya'
                    ];
                    foreach ($industries as $ind):
                    ?>
                      <option value="<?= e($ind) ?>" <?= (($_POST['industry'] ?? '') === $ind) ? 'selected' : '' ?>><?= e($ind) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="field">
                  <label for="website" style="font-weight: 600; font-size: 13px;">Website / URL Profil</label>
                  <input type="text" id="website" name="website" placeholder="https://perusahaan.com" maxlength="255" value="<?= e($_POST['website'] ?? '') ?>" />
                </div>
              </div>

              <div class="field">
                <label for="description" style="font-weight: 600; font-size: 13px;">Deskripsi Singkat Perusahaan <span style="font-size: 11px; color: var(--text-muted); font-weight: normal;">(Opsional)</span></label>
                <textarea id="description" name="description" rows="2" placeholder="Jelaskan bidang fokus atau jenis talenta yang sering dicari..." style="border-radius: 8px; border: 1px solid var(--border, #cbd5e1); padding: 10px 12px; font-size: 13px; font-family: inherit; resize: vertical;"><?= e($_POST['description'] ?? '') ?></textarea>
              </div>
            </div>
          </fieldset>

          <!-- Bagian 2: Data Recruiter (PIC) -->
          <fieldset style="border: 1px solid var(--border-light, #e2e8f0); border-radius: 12px; padding: 20px; margin: 0; background: var(--surface-subtle, #fcfdfe);">
            <legend style="font-size: 14px; font-weight: 700; color: var(--primary); padding: 0 10px; display: flex; align-items: center; gap: 6px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              2. Data Recruiter / Penanggung Jawab (PIC)
            </legend>

            <div style="display: grid; gap: 14px; margin-top: 4px;">
              <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                <div class="field">
                  <label for="name" style="font-weight: 600; font-size: 13px;">Nama Lengkap PIC <span style="color: #ef4444;">*</span></label>
                  <input type="text" id="name" name="name" placeholder="Contoh: Aditya Pratama" required maxlength="100" autocomplete="name" value="<?= e($_POST['name'] ?? '') ?>" />
                </div>

                <div class="field">
                  <label for="role_title" style="font-weight: 600; font-size: 13px;">Jabatan / Posisi <span style="color: #ef4444;">*</span></label>
                  <input type="text" id="role_title" name="role_title" placeholder="Contoh: Talent Acquisition Lead" required maxlength="100" value="<?= e($_POST['role_title'] ?? 'Talent Acquisition / HR') ?>" />
                </div>
              </div>

              <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                <div class="field">
                  <label for="email" style="font-weight: 600; font-size: 13px;">Email Perusahaan / Kerja <span style="color: #ef4444;">*</span></label>
                  <input type="email" id="email" name="email" placeholder="nama@perusahaan.com" required maxlength="150" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" />
                </div>

                <div class="field">
                  <label for="password" style="font-weight: 600; font-size: 13px;">Kata Sandi <span style="color: #ef4444;">*</span></label>
                  <div class="password-input-wrap">
                    <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" minlength="8" maxlength="72" required autocomplete="new-password" />
                    <button type="button" class="password-toggle-btn" data-password-toggle="password" aria-label="Tampilkan kata sandi" aria-pressed="false">
                      <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                      <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </fieldset>

          <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; height: 48px; font-size: 15px; font-weight: 700; gap: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            Daftarkan Perusahaan & Mulai Rekrut
          </button>
        </form>

        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-light, #e2e8f0); display: flex; flex-direction: column; gap: 10px; text-align: center; font-size: 14px;">
          <p style="color: var(--text-muted); margin: 0;">
            Sudah memiliki akun Recruiter?
            <a href="login.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Masuk di sini</a>
          </p>
          <p style="color: var(--text-muted); font-size: 13px; margin: 0;">
            Anda mahasiswa pencari karier?
            <a href="register.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Daftar Akun Mahasiswa &rarr;</a>
          </p>
        </div>
      </article>
    </main>

    <script src="<?= e(asset('js/ui.js')) ?>"></script>
    <script src="<?= e(asset('js/api.js')) ?>"></script>
    <script src="<?= e(asset('js/pages/register-hr.js')) ?>"></script>
  </body>
</html>
