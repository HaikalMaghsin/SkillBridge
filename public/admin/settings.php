<?php
// public/admin/settings.php
// Pengaturan Sistem · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('admin');

$page_title = 'Pengaturan Sistem · SkillBridge';
$role = 'admin';
$path_prefix = '../';
$admin_page = 'settings';
$body_class = 'admin-body';
$body_attrs = 'data-admin-page="settings"';
$page_script = 'admin-settings.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/admin_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="admin-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/admin_header.php'; ?>

      <main class="admin-page-content">
      <div class="admin-page-heading">
        <div>
          <p class="admin-eyebrow">Konfigurasi</p>
          <h1 class="admin-page-title">Pengaturan Sistem</h1>
          <p class="admin-page-desc">Pengaturan akun administrator dan konfigurasi platform SkillBridge.</p>
        </div>
      </div>

      <!-- Profil administrator -->
      <div class="admin-card">
        <div class="admin-card-head">
          <div>
            <p class="admin-card-title">Profil Administrator</p>
            <p class="admin-card-sub">Informasi akun Admin yang sedang aktif.</p>
          </div>
        </div>
        <form id="form-admin-profile">
          <div class="field">
            <label for="set-name">Nama lengkap</label>
            <input type="text" id="set-name" name="name" value="" required />
          </div>
          <div class="field">
            <label for="set-email">Email</label>
            <input type="email" id="set-email" name="email" value="" required />
          </div>
          <div class="field">
            <label for="set-password">Password baru</label>
            <div class="password-input-wrap" style="position: relative; display: flex; align-items: center; width: 100%;">
              <input type="password" id="set-password" name="password" placeholder="Biarkan kosong jika tidak diubah" minlength="8" style="width: 100%; padding-right: 44px; box-sizing: border-box;" />
              <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('set-password', this)" data-password-toggle="set-password" aria-label="Tampilkan kata sandi" aria-pressed="false" title="Tampilkan kata sandi" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; margin: 0; border: none !important; outline: none !important; background: transparent !important; box-shadow: none !important; -webkit-appearance: none; appearance: none; cursor: pointer; color: #64748b; z-index: 2; border-radius: 6px;">
                <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: block; pointer-events: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="icon-eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none; pointer-events: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
            <small style="color: #6b7280; font-size: 13px;">Minimal 8 karakter jika ingin diubah</small>
          </div>
        </form>
        <div class="row-end" style="display:flex;justify-content:flex-end">
          <button class="btn btn-primary" type="submit" form="form-admin-profile">Simpan Perubahan</button>
        </div>
      </div>

      <!-- Konfigurasi platform -->
      <div class="admin-card">
        <div class="admin-card-head">
          <div>
            <p class="admin-card-title">Konfigurasi Platform</p>
            <p class="admin-card-sub">Preferensi umum yang berlaku untuk seluruh pengguna.</p>
          </div>
        </div>
        <form id="form-platform-config">
          <div class="field">
            <label for="set-sitename">Nama platform</label>
            <input type="text" id="set-sitename" name="site_name" value="SkillBridge" />
          </div>
          <div class="field">
            <label for="set-maxevidence">Batas maksimum evidence per pengguna</label>
            <input type="number" id="set-maxevidence" name="max_evidence" value="50" min="1" max="500" />
          </div>
          <div class="field">
            <label for="set-visibility">Visibilitas portofolio default</label>
            <select id="set-visibility" name="default_visibility">
              <option value="private">Private</option>
              <option value="public">Public</option>
            </select>
          </div>
          <div class="field" style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
            <input type="checkbox" id="set-registration" name="registration_open" checked style="width:auto" />
            <label for="set-registration" style="margin:0">Buka pendaftaran pengguna baru</label>
          </div>
        </form>
        <div style="display:flex;justify-content:flex-end">
          <button class="btn btn-primary" type="submit" form="form-platform-config">Simpan Konfigurasi</button>
        </div>
      </div>

      <!-- Info keamanan -->
      <div class="admin-welcome-strip">
        <div class="admin-welcome-icon">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M10 2L3 5.5v5c0 4 3 7.5 7 8.5 4-1 7-4.5 7-8.5v-5L10 2z" />
            <path d="M7 10l2.5 2.5L14 8" />
          </svg>
        </div>
        <div class="admin-welcome-text">
          <strong>Catatan keamanan</strong>
          <span>Pembatasan peran di sisi klien hanya untuk prototipe. Otorisasi wajib ditegakkan di server.</span>
        </div>
      </div>
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
<?php require_once __DIR__ . '/../../includes/layouts/admin_footer.php'; ?>