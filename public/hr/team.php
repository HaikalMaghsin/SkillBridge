<?php
// public/hr/team.php
// Manajemen Recruiter / Tim · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('hr');

$page_title = 'Manajemen Recruiter / Tim · SkillBridge';
$role = 'hr';
$path_prefix = '../';
$rec_page = 'team';
$body_class = 'rec-body';
$body_attrs = 'data-rec-page="team"';
$page_script = 'recruiter-team.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/hr_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="rec-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/hr_header.php'; ?>

      <main class="rec-page-content">

        <!-- Page heading -->
        <div>
          <div class="rec-page-context">
            <p class="rec-breadcrumb">A &ndash; PERUSAHAAN / MANAJEMEN RECRUITER / TIM</p>
            <div class="rec-date">
              <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="14" height="12.5" rx="1.5"/><path d="M3 8.5h14M7 3v3M13 3v3"/></svg>
              <span id="rec-today">18 Oktober 2026</span>
            </div>
          </div>
          <h1 class="rec-page-title">Manajemen Recruiter &amp; Karyawan</h1>
          <p class="rec-page-desc">
            Kelola anggota tim rekruter perusahaan, tentukan hak akses peran masing-masing, dan kontrol status aktif akun untuk peninjauan kandidat talenta.
          </p>
        </div>

        <!-- Metrics -->
        <section class="rec-stats-grid" id="rec-team-stats" aria-label="Metrik tim recruiter">
          <!-- diisi js/pages/recruiter-team.js -->
        </section>

        <!-- Team toolbar -->
        <div class="rec-toolbar" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
          <div class="rec-search" style="flex: 1; min-width: 220px;">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="9" r="5"/><path d="M15 15l-3-3"/></svg>
            <input type="search" id="rec-team-search" placeholder="Cari nama atau email recruiter…" />
          </div>
          <select id="rec-team-role-filter" class="rec-select">
            <option value="all">Semua Peran</option>
            <option value="Lead Recruiter">Lead Recruiter</option>
            <option value="Hiring Manager">Hiring Manager</option>
            <option value="Recruiter">Recruiter</option>
          </select>
          <select id="rec-team-status-filter" class="rec-select">
            <option value="all">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
            <option value="pending">Pending</option>
          </select>
          <button type="button" class="btn btn-primary" id="btn-open-add-member" style="display: inline-flex; align-items: center; gap: 8px; height: 38px; padding: 0 16px; border-radius: 8px; font-weight: 600; font-size: 13px; white-space: nowrap;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Tambah Anggota Tim
          </button>
        </div>

        <!-- Team table -->
        <section class="rec-card">
          <div class="rec-table-wrap table-responsive">
            <table class="rec-native-table">
              <thead>
                <tr>
                  <th class="rec-col-name">Nama &amp; Email</th>
                  <th class="rec-col-role">Peran &amp; Hak Akses</th>
                  <th class="rec-col-status">Status Akun</th>
                  <th class="rec-col-date">Bergabung</th>
                  <th class="rec-col-actions" style="text-align: right; min-width: 140px;">Aksi</th>
                </tr>
              </thead>
              <tbody id="rec-team-tbody">
                <tr><td colspan="5" class="rec-empty">Memuat tim recruiter…</td></tr>
              </tbody>
            </table>
          </div>

          <div class="rec-pager">
            <button type="button" class="rec-pager-link" id="rec-team-prev" disabled>Sebelumnya</button>
            <span class="rec-pager-page" id="rec-team-page">1</span>
            <button type="button" class="rec-pager-link" id="rec-team-next">Berikutnya</button>
          </div>
        </section>

      </main>

    <!-- ===== MODAL TAMBAH ANGGOTA TIM ===== -->
    <div id="modal-add-member" class="rec-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 999; display: none; align-items: center; justify-content: center; padding: 16px;">
      <div class="rec-modal-dialog" style="background: #ffffff; border-radius: 12px; max-width: 500px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid var(--border-light, #e2e8f0); display: flex; justify-content: space-between; align-items: center;">
          <h3 style="margin: 0; font-size: 17px; font-weight: 700; color: #1e293b;">Tambah Anggota Tim Recruiter</h3>
          <button type="button" id="btn-close-add-modal" style="background: transparent; border: none; font-size: 20px; color: #64748b; cursor: pointer; padding: 4px; line-height: 1;">&times;</button>
        </div>

        <form id="form-add-member" style="padding: 20px; display: grid; gap: 16px;">
          <div>
            <label for="add-member-name" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Nama Lengkap <span style="color: #ef4444;">*</span></label>
            <input type="text" id="add-member-name" required placeholder="Contoh: Budi Santoso" style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; box-sizing: border-box;" />
          </div>

          <div>
            <label for="add-member-email" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Email Kantor / Perusahaan <span style="color: #ef4444;">*</span></label>
            <input type="email" id="add-member-email" required placeholder="budi@perusahaan.co.id" style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; box-sizing: border-box;" />
          </div>

          <div>
            <label for="add-member-role" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Peran &amp; Hak Akses Role <span style="color: #ef4444;">*</span></label>
            <select id="add-member-role" required style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; box-sizing: border-box; background: #fff;">
              <option value="Recruiter" selected>Recruiter &mdash; Review pelamar, jadwalkan wawancara</option>
              <option value="Hiring Manager">Hiring Manager &mdash; Kelola pipeline &amp; keputusan kandidat</option>
              <option value="Lead Recruiter">Lead Recruiter &mdash; Akses penuh kelola lowongan &amp; tim</option>
            </select>
          </div>

          <div>
            <label for="add-member-password" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Password Awal</label>
            <div class="password-input-wrap" style="position: relative; display: flex; align-items: center; width: 100%;">
              <input type="password" id="add-member-password" placeholder="Default: SkillBridge123!" minlength="8" style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding-left: 12px; padding-right: 44px; box-sizing: border-box;" />
              <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('add-member-password', this)" data-password-toggle="add-member-password" aria-label="Tampilkan kata sandi" aria-pressed="false" title="Tampilkan kata sandi" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; margin: 0; border: none !important; outline: none !important; background: transparent !important; box-shadow: none !important; -webkit-appearance: none; appearance: none; cursor: pointer; color: #64748b; z-index: 2; border-radius: 6px;">
                <svg class="icon-eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: block; pointer-events: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="icon-eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none; pointer-events: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
            <small style="color: #64748b; font-size: 12px; margin-top: 4px; display: block;">Biarkan kosong untuk menggunakan password bawaan sistem (SkillBridge123!).</small>
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
            <button type="button" class="btn btn-secondary" id="btn-cancel-add-member">Batal</button>
            <button type="submit" class="btn btn-primary" id="btn-submit-add-member">Daftarkan Anggota</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ===== MODAL UBAH ROLE & STATUS ===== -->
    <div id="modal-edit-role" class="rec-modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 999; display: none; align-items: center; justify-content: center; padding: 16px;">
      <div class="rec-modal-dialog" style="background: #ffffff; border-radius: 12px; max-width: 480px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid var(--border-light, #e2e8f0); display: flex; justify-content: space-between; align-items: center;">
          <h3 style="margin: 0; font-size: 17px; font-weight: 700; color: #1e293b;">Ubah Hak Akses &amp; Status Tim</h3>
          <button type="button" id="btn-close-edit-modal" style="background: transparent; border: none; font-size: 20px; color: #64748b; cursor: pointer; padding: 4px; line-height: 1;">&times;</button>
        </div>

        <form id="form-edit-role" style="padding: 20px; display: grid; gap: 16px;">
          <input type="hidden" id="edit-member-id" />

          <div>
            <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 4px;">Nama Anggota</label>
            <input type="text" id="edit-member-name" readonly style="width: 100%; height: 38px; border-radius: 8px; border: 1px solid #e2e8f0; background: #f8fafc; padding: 0 12px; box-sizing: border-box; color: #475569;" />
          </div>

          <div>
            <label for="edit-member-role" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Hak Akses Role</label>
            <select id="edit-member-role" required style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; box-sizing: border-box; background: #fff;">
              <option value="Lead Recruiter">Lead Recruiter (Akses Penuh)</option>
              <option value="Hiring Manager">Hiring Manager (Kelola Pipeline)</option>
              <option value="Recruiter">Recruiter (Review &amp; Wawancara)</option>
            </select>
          </div>

          <div>
            <label for="edit-member-status" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Status Akun</label>
            <select id="edit-member-status" required style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; box-sizing: border-box; background: #fff;">
              <option value="active">Aktif &mdash; Memiliki akses masuk &amp; kelola</option>
              <option value="inactive">Nonaktif &mdash; Akses dibekukan sementara</option>
            </select>
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
            <button type="button" class="btn btn-secondary" id="btn-cancel-edit-member">Batal</button>
            <button type="submit" class="btn btn-primary" id="btn-submit-edit-member">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>

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
