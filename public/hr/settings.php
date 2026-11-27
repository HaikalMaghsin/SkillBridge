<?php
// public/hr/settings.php
// Pengaturan Akun Recruiter · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
require_once __DIR__ . '/../../includes/config/r2.php';
check_auth('hr');

$user = current_user() ?? [];
$page_title = 'Pengaturan Akun Recruiter · SkillBridge';
$role = 'hr';
$path_prefix = '../';
$rec_page = 'settings';
$body_class = 'rec-body';
$body_attrs = 'data-rec-page="settings"';
$page_script = 'recruiter-settings.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/hr_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="rec-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/hr_header.php'; ?>

      <main class="rec-page-content">
        <div>
          <div class="rec-page-context">
            <p class="rec-breadcrumb">A &ndash; RECRUITER / PENGATURAN SISTEM</p>
            <div class="rec-date">
              <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="14" height="12.5" rx="1.5"/><path d="M3 8.5h14M7 3v3M13 3v3"/></svg>
              <span id="rec-today">18 Oktober 2026</span>
            </div>
          </div>
          <h1 class="rec-page-title">Pengaturan Akun &amp; Profil Perusahaan</h1>
          <p class="rec-page-desc">Kelola profil perusahaan, logo, data akun recruiter, preferensi email notifikasi, serta keamanan kata sandi Anda.</p>
        </div>

        <div class="rec-tabs" role="tablist" aria-label="Pengaturan">
          <button type="button" class="rec-tab active" data-tab="company" role="tab" aria-selected="true">Profil Perusahaan &amp; Akun</button>
          <button type="button" class="rec-tab" data-tab="email" role="tab" aria-selected="false">Notifikasi Email</button>
          <button type="button" class="rec-tab" data-tab="security" role="tab" aria-selected="false">Keamanan &amp; Password</button>
          <button type="button" class="rec-tab" data-tab="api" role="tab" aria-selected="false">Integrasi API</button>
        </div>

        <?php
        $currComp = function_exists('current_user_company') ? current_user_company() : null;
        $compName = $currComp['name'] ?? ($user['company_name'] ?? 'Perusahaan Mitra');
        $compIndustry = $currComp['industry'] ?? '';
        $compWebsite = $currComp['website'] ?? '';
        $compDesc = $currComp['description'] ?? '';
        $compLogo = !empty($currComp['logo_url']) ? r2_display_url($currComp['logo_url']) : '';
        $userAvatar = !empty($user['avatar_url']) ? r2_display_url($user['avatar_url']) : '';
        ?>

        <!-- ===== TAB 1: PROFIL PERUSAHAAN & AKUN ===== -->
        <section id="tab-company" class="rec-tab-panel active" role="tabpanel">
          
          <!-- Kartu 1: Profil Perusahaan -->
          <div class="rec-card" style="margin-bottom: 24px;">
            <div class="rec-card-head">
              <div>
                <h2 class="rec-card-title">Profil Perusahaan</h2>
                <p class="rec-card-sub" id="settings-company-sub">Informasi resmi <?= e($compName) ?> yang ditampilkan kepada kandidat talenta di SkillBridge.</p>
              </div>
              <span class="rec-badge-verified">Terverifikasi</span>
            </div>

            <div class="rec-logo-row" style="display: flex; align-items: center; gap: 20px; padding: 16px 0; border-bottom: 1px solid var(--border-light, #e2e8f0); margin-bottom: 20px;">
              <div class="rec-logo-preview" id="company-logo-preview-box" style="width: 80px; height: 80px; border-radius: 12px; border: 1px solid var(--border-light, #cbd5e1); background: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                <?php if ($compLogo): ?>
                  <img id="company-logo-img" src="<?= e($compLogo) ?>" alt="Logo Perusahaan" style="width: 100%; height: 100%; object-fit: contain; padding: 6px;" />
                <?php else: ?>
                  <svg id="company-logo-default-icon" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="13" rx="1.5"/><path d="M8 8V5a4 4 0 0 1 8 0v3M9 13h.01M15 13h.01"/></svg>
                  <img id="company-logo-img" src="" alt="Logo Perusahaan" style="width: 100%; height: 100%; object-fit: contain; padding: 6px; display: none;" />
                <?php endif; ?>
              </div>
              <div class="rec-logo-controls" style="flex: 1;">
                <span class="rec-logo-label" style="font-weight: 600; font-size: 14px; color: var(--text-heading, #1e293b); display: block; margin-bottom: 4px;">Logo Resmi Perusahaan</span>
                <p class="rec-logo-hint" style="font-size: 13px; color: var(--text-muted, #64748b); margin: 0 0 10px 0;">Format didukung: PNG, JPG, SVG, WebP transparan (Maks. 2MB). Disarankan rasio kotak 1:1.</p>
                <div style="display: flex; gap: 10px; align-items: center;">
                  <input type="file" id="company-logo-file" accept="image/png,image/jpeg,image/svg+xml,image/webp" style="display: none;" />
                  <button type="button" class="btn btn-secondary btn-sm" id="btn-trigger-logo" style="display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Pilih &amp; Unggah Logo
                  </button>
                  <span id="logo-file-status" style="font-size: 12px; color: #64748b;"></span>
                </div>
              </div>
            </div>

            <form id="form-company-profile" class="rec-field-grid">
              <div class="rec-field">
                <label for="company-name">Nama Perusahaan <span style="color: #ef4444;">*</span></label>
                <input id="company-name" name="name" type="text" value="<?= e($compName) ?>" required placeholder="Contoh: PT Latif Sejahtera" />
              </div>
              <div class="rec-field">
                <label for="company-website">Website Resmi Perusahaan</label>
                <input id="company-website" name="website" type="text" value="<?= e($compWebsite) ?>" placeholder="https://perusahaan.co.id" />
              </div>
              <div class="rec-field full">
                <label for="company-location">Bidang Industri &amp; Lokasi Utama</label>
                <input id="company-location" name="industry" type="text" value="<?= e($compIndustry) ?>" placeholder="Contoh: Teknologi Informasi · Malang, Jawa Timur" />
              </div>
              <div class="rec-field full">
                <label for="company-description">Deskripsi Perusahaan</label>
                <textarea id="company-description" name="description" rows="4" placeholder="Jelaskan visi, misi, dan fokus bisnis perusahaan Anda kepada kandidat talenta..."><?= e($compDesc) ?></textarea>
              </div>
              <div class="rec-field full" style="display: flex; justify-content: flex-end; margin-top: 8px;">
                <button type="submit" class="btn btn-primary" id="btn-save-company" style="min-width: 180px;">
                  Simpan Profil Perusahaan
                </button>
              </div>
            </form>
          </div>

          <!-- Kartu 2: Profil Akun Pribadi Recruiter -->
          <div class="rec-card">
            <div class="rec-card-head">
              <div>
                <h2 class="rec-card-title">Profil Akun Recruiter</h2>
                <p class="rec-card-sub">Informasi data diri Anda sebagai perwakilan HR yang mengelola rekrutmen.</p>
              </div>
              <span class="rec-badge-verified">Akun HR</span>
            </div>

            <div style="display: flex; align-items: center; gap: 20px; padding: 16px 0; border-bottom: 1px solid var(--border-light, #e2e8f0); margin-bottom: 20px;">
              <div id="hr-avatar-preview-box" style="width: 72px; height: 72px; border-radius: 50%; border: 2px solid var(--primary, #2563eb); background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                <?php if ($userAvatar): ?>
                  <img id="hr-avatar-img" src="<?= e($userAvatar) ?>" alt="<?= e($user['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;" />
                <?php else: ?>
                  <span id="hr-avatar-initials" style="font-size: 24px; font-weight: 700; color: var(--primary, #2563eb);"><?= strtoupper(substr($user['name'] ?? 'HR', 0, 2)) ?></span>
                  <img id="hr-avatar-img" src="" alt="<?= e($user['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; display: none;" />
                <?php endif; ?>
              </div>
              <div style="flex: 1;">
                <span style="font-weight: 600; font-size: 14px; color: var(--text-heading, #1e293b); display: block; margin-bottom: 4px;">Foto Profil Recruiter</span>
                <p style="font-size: 13px; color: var(--text-muted, #64748b); margin: 0 0 10px 0;">Disarankan foto formal atau pasfoto berdimensi persegi (JPG/PNG/WebP, maks. 2MB).</p>
                <div style="display: flex; gap: 10px; align-items: center;">
                  <input type="file" id="hr-avatar-file" accept="image/png,image/jpeg,image/webp" style="display: none;" />
                  <button type="button" class="btn btn-secondary btn-sm" id="btn-trigger-avatar" style="display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    Ganti Foto Profil
                  </button>
                  <span id="avatar-file-status" style="font-size: 12px; color: #64748b;"></span>
                </div>
              </div>
            </div>

            <form id="form-hr-profile" class="rec-field-grid">
              <div class="rec-field">
                <label for="hr-name">Nama Lengkap Recruiter <span style="color: #ef4444;">*</span></label>
                <input id="hr-name" name="name" type="text" value="<?= e($user['name']) ?>" required placeholder="Nama lengkap Anda" />
              </div>
              <div class="rec-field">
                <label for="hr-email">Alamat Email Login <span style="color: #ef4444;">*</span></label>
                <input id="hr-email" name="email" type="email" value="<?= e($user['email']) ?>" required placeholder="nama@perusahaan.co.id" />
              </div>
              <div class="rec-field full" style="display: flex; justify-content: flex-end; margin-top: 8px;">
                <button type="submit" class="btn btn-primary" id="btn-save-hr-profile" style="min-width: 180px;">
                  Simpan Profil Akun
                </button>
              </div>
            </form>
          </div>

        </section>

        <!-- ===== TAB 2: NOTIFIKASI EMAIL ===== -->
        <section id="tab-email" class="rec-tab-panel" role="tabpanel">
          <div class="rec-card" style="margin-bottom: 24px;">
            <div class="rec-card-head">
              <div>
                <h2 class="rec-card-title">Preferensi Notifikasi Email</h2>
                <p class="rec-card-sub">Tentukan pemberitahuan otomatis yang dikirimkan ke email Anda untuk setiap proses rekrutmen.</p>
              </div>
              <span class="rec-badge-verified">Sistem Notifikasi</span>
            </div>

            <form id="form-email-preferences" style="display: grid; gap: 18px; margin-top: 16px;">
              <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <input type="checkbox" id="pref-new-applicant" name="notify_new_applicant" checked style="width: 18px; height: 18px; margin-top: 2px; cursor: pointer;" />
                <label for="pref-new-applicant" style="margin: 0; cursor: pointer;">
                  <strong style="color: #1e293b; font-size: 14px; display: block;">Pelamar &amp; Kandidat Baru</strong>
                  <span style="color: #64748b; font-size: 13px;">Dapatkan email pemberitahuan setiap kali ada mahasiswa/talenta yang melamar lowongan kerja perusahaan Anda.</span>
                </label>
              </div>

              <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <input type="checkbox" id="pref-pipeline-status" name="notify_pipeline_status" checked style="width: 18px; height: 18px; margin-top: 2px; cursor: pointer;" />
                <label for="pref-pipeline-status" style="margin: 0; cursor: pointer;">
                  <strong style="color: #1e293b; font-size: 14px; display: block;">Perubahan Tahapan Seleksi (Pipeline)</strong>
                  <span style="color: #64748b; font-size: 13px;">Dapatkan notifikasi saat status kandidat diperbarui (Review, Interview, Offered, Hired).</span>
                </label>
              </div>

              <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <input type="checkbox" id="pref-interview" name="notify_interview_schedule" checked style="width: 18px; height: 18px; margin-top: 2px; cursor: pointer;" />
                <label for="pref-interview" style="margin: 0; cursor: pointer;">
                  <strong style="color: #1e293b; font-size: 14px; display: block;">Jadwal Wawancara Kandidat</strong>
                  <span style="color: #64748b; font-size: 13px;">Pengingat email 24 jam sebelum jadwal interview teknis atau HR berlangsung.</span>
                </label>
              </div>

              <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <input type="checkbox" id="pref-weekly-digest" name="notify_weekly_digest" style="width: 18px; height: 18px; margin-top: 2px; cursor: pointer;" />
                <label for="pref-weekly-digest" style="margin: 0; cursor: pointer;">
                  <strong style="color: #1e293b; font-size: 14px; display: block;">Ringkasan Rekrutmen Mingguan</strong>
                  <span style="color: #64748b; font-size: 13px;">Kirim rekap mingguan mengenai statistik lamaran masuk dan aktivitas tim rekruter setiap Senin pagi.</span>
                </label>
              </div>

              <div class="rec-field full" style="margin-top: 4px;">
                <label for="pref-recipient-email">Alamat Email Penerima Notifikasi Utama</label>
                <input id="pref-recipient-email" name="recipient_email" type="email" value="<?= e($user['email']) ?>" placeholder="email-notifikasi@perusahaan.co.id" />
                <small style="color: #64748b; font-size: 12px; margin-top: 4px; display: block;">Bisa disesuaikan jika ingin mengarahkan notifikasi ke email tim rekrutmen bersama.</small>
              </div>

              <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                <button type="submit" class="btn btn-primary" id="btn-save-email-pref" style="min-width: 180px;">
                  Simpan Preferensi Email
                </button>
              </div>
            </form>
          </div>

          <!-- Kartu Uji Coba Pengiriman Email -->
          <div class="rec-card">
            <div class="rec-card-head">
              <div>
                <h2 class="rec-card-title">Uji Coba Pengiriman Email</h2>
                <p class="rec-card-sub">Kirimkan sampel email notifikasi ke kotak masuk Anda untuk memastikan konfigurasi mailer aktif.</p>
              </div>
            </div>

            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px; margin-bottom: 16px; font-size: 13px; color: #1e40af;">
              Sistem pengiriman email menggunakan layanan native SMTP SkillBridge. Jika server pengiriman sedang offline, salinan email akan otomatis dicatat pada log sistem.
            </div>

            <form id="form-test-email" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
              <div class="rec-field" style="flex: 1; min-width: 260px; margin-bottom: 0;">
                <label for="test-email-target">Alamat Email Tujuan Pengujian</label>
                <input id="test-email-target" type="email" value="<?= e($user['email']) ?>" required placeholder="tujuan-test@email.com" />
              </div>
              <button type="submit" class="btn btn-secondary" id="btn-send-test-email" style="height: 42px; display: inline-flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Kirim Email Uji Coba
              </button>
            </form>

            <div id="test-email-result" style="margin-top: 16px; display: none;"></div>
          </div>
        </section>

        <!-- ===== TAB 3: KEAMANAN & PASSWORD ===== -->
        <section id="tab-security" class="rec-tab-panel" role="tabpanel">
          
          <!-- Kartu Ubah Kata Sandi -->
          <div class="rec-card" style="margin-bottom: 24px;">
            <div class="rec-card-head">
              <div>
                <h2 class="rec-card-title">Ubah Kata Sandi</h2>
                <p class="rec-card-sub">Perbarui kata sandi akun HR Anda secara berkala untuk menjaga kerahasiaan data rekrutmen.</p>
              </div>
            </div>

            <form id="form-hr-password" style="display: grid; gap: 18px; max-width: 580px; margin-top: 10px;">
              <div class="field">
                <label for="hr-curr-password" style="font-weight: 600; font-size: 13px; margin-bottom: 6px; display: block;">Kata Sandi Saat Ini <span style="color: #ef4444;">*</span></label>
                <div class="password-input-wrap" style="position: relative; display: flex; align-items: center; width: 100%;">
                  <input type="password" id="hr-curr-password" name="current_password" required placeholder="Masukkan kata sandi lama Anda" autocomplete="current-password" style="width: 100%; padding-right: 44px; box-sizing: border-box; height: 42px; border-radius: 8px; border: 1px solid var(--border-light, #cbd5e1); padding-left: 12px;" />
                  <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('hr-curr-password', this)" data-password-toggle="hr-curr-password" aria-label="Tampilkan kata sandi" aria-pressed="false" title="Tampilkan kata sandi" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; margin: 0; border: none !important; outline: none !important; background: transparent !important; box-shadow: none !important; -webkit-appearance: none; appearance: none; cursor: pointer; color: #64748b; z-index: 2; border-radius: 6px;">
                    <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: block; pointer-events: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="icon-eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none; pointer-events: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  </button>
                </div>
              </div>

              <div class="field">
                <label for="hr-new-password" style="font-weight: 600; font-size: 13px; margin-bottom: 6px; display: block;">Kata Sandi Baru <span style="color: #ef4444;">*</span></label>
                <div class="password-input-wrap" style="position: relative; display: flex; align-items: center; width: 100%;">
                  <input type="password" id="hr-new-password" name="new_password" required minlength="8" placeholder="Minimal 8 karakter" autocomplete="new-password" style="width: 100%; padding-right: 44px; box-sizing: border-box; height: 42px; border-radius: 8px; border: 1px solid var(--border-light, #cbd5e1); padding-left: 12px;" />
                  <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('hr-new-password', this)" data-password-toggle="hr-new-password" aria-label="Tampilkan kata sandi" aria-pressed="false" title="Tampilkan kata sandi" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; margin: 0; border: none !important; outline: none !important; background: transparent !important; box-shadow: none !important; -webkit-appearance: none; appearance: none; cursor: pointer; color: #64748b; z-index: 2; border-radius: 6px;">
                    <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: block; pointer-events: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="icon-eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none; pointer-events: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  </button>
                </div>
                <small style="color: #64748b; font-size: 12px; margin-top: 4px; display: block;">Gunakan kombinasi huruf besar, huruf kecil, angka, dan simbol.</small>
              </div>

              <div class="field">
                <label for="hr-confirm-password" style="font-weight: 600; font-size: 13px; margin-bottom: 6px; display: block;">Konfirmasi Kata Sandi Baru <span style="color: #ef4444;">*</span></label>
                <div class="password-input-wrap" style="position: relative; display: flex; align-items: center; width: 100%;">
                  <input type="password" id="hr-confirm-password" name="confirm_password" required minlength="8" placeholder="Ulangi kata sandi baru" autocomplete="new-password" style="width: 100%; padding-right: 44px; box-sizing: border-box; height: 42px; border-radius: 8px; border: 1px solid var(--border-light, #cbd5e1); padding-left: 12px;" />
                  <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('hr-confirm-password', this)" data-password-toggle="hr-confirm-password" aria-label="Tampilkan kata sandi" aria-pressed="false" title="Tampilkan kata sandi" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; margin: 0; border: none !important; outline: none !important; background: transparent !important; box-shadow: none !important; -webkit-appearance: none; appearance: none; cursor: pointer; color: #64748b; z-index: 2; border-radius: 6px;">
                    <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: block; pointer-events: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="icon-eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none; pointer-events: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  </button>
                </div>
              </div>

              <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                <button type="submit" class="btn btn-primary" id="btn-change-password" style="min-width: 180px;">
                  Perbarui Kata Sandi
                </button>
              </div>
            </form>
          </div>

          <!-- Kartu Info Keamanan Sesi -->
          <div class="rec-card">
            <div class="rec-card-head">
              <div>
                <h2 class="rec-card-title">Informasi Keamanan &amp; Sesi Aktif</h2>
                <p class="rec-card-sub">Data status keamanan dan perlindungan akun Anda saat ini.</p>
              </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 10px;">
              <div style="padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <p style="font-size: 12px; color: #64748b; margin: 0 0 4px 0; text-transform: uppercase; font-weight: 600;">Status Autentikasi</p>
                <p style="font-size: 14px; color: #16a34a; font-weight: 700; margin: 0;">Sesi Aktif &amp; Terverifikasi</p>
              </div>
              <div style="padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <p style="font-size: 12px; color: #64748b; margin: 0 0 4px 0; text-transform: uppercase; font-weight: 600;">Peran Akun</p>
                <p style="font-size: 14px; color: #1e293b; font-weight: 600; margin: 0;">Recruiter / HR Perusahaan</p>
              </div>
              <div style="padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <p style="font-size: 12px; color: #64748b; margin: 0 0 4px 0; text-transform: uppercase; font-weight: 600;">Enkripsi Data</p>
                <p style="font-size: 14px; color: #1e293b; font-weight: 600; margin: 0;">Bcrypt + CSRF Token Protected</p>
              </div>
            </div>
          </div>

        </section>

        <!-- ===== TAB 4: INTEGRASI API ===== -->
        <section id="tab-api" class="rec-tab-panel" role="tabpanel">
          <div class="rec-card">
            <div class="rec-card-head">
              <div>
                <h2 class="rec-card-title">Integrasi API &amp; Webhook</h2>
                <p class="rec-card-sub">Akses API untuk menghubungkan sistem ATS (Applicant Tracking System) internal perusahaan Anda ke SkillBridge.</p>
              </div>
              <span class="rec-badge-verified">Developer API</span>
            </div>

            <div style="padding: 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; margin-top: 14px;">
              <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #1e293b;">Kunci API Perusahaan</p>
              <div style="display: flex; gap: 8px; align-items: center;">
                <input type="text" readonly value="sb_live_<?= substr(md5($compName . 'api_key_salt'), 0, 24) ?>" style="flex: 1; font-family: monospace; font-size: 13px; background: #fff; height: 38px;" />
                <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); UI.toast('API Key disalin ke clipboard');">Salin Key</button>
              </div>
              <p style="font-size: 12px; color: #64748b; margin: 8px 0 0 0;">Dokumentasi REST API &amp; Webhook akan diperluas pada rilis API SkillBridge v1.</p>
            </div>
          </div>
        </section>

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

<?php require_once __DIR__ . '/../../includes/layouts/hr_footer.php'; ?>
