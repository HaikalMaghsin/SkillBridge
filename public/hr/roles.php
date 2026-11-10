<?php
// public/hr/roles.php
// Target Role & Kebutuhan Skill · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('hr');

$page_title = 'Target Role & Lowongan · SkillBridge';
$role = 'hr';
$path_prefix = '../';
$rec_page = 'roles';
$body_class = 'rec-body';
$body_attrs = 'data-rec-page="roles"';
$page_script = 'recruiter-roles.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/hr_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="rec-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/hr_header.php'; ?>

      <main class="rec-page-content">

        <!-- Page heading -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 24px">
          <div>
            <div class="rec-page-context">
              <p class="rec-breadcrumb">A &ndash; PERUSAHAAN / TARGET ROLE &amp; LOWONGAN</p>
              <div class="rec-date">
                <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="14" height="12.5" rx="1.5"/><path d="M3 8.5h14M7 3v3M13 3v3"/></svg>
                <span id="rec-today"><?= date('d F Y') ?></span>
              </div>
            </div>
            <h1 class="rec-page-title">Lowongan &amp; Target Role</h1>
            <p class="rec-page-desc">
              Kelola posisi yang dibuka perusahaan, petakan kebutuhan skill untuk benchmark kandidat, dan pantau pelamar per lowongan.
            </p>
          </div>
          <button type="button" class="btn btn-accent btn-44" id="btn-create-role" style="display: inline-flex; align-items: center; gap: 8px">
            <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 4v12M4 10h12"/></svg>
            <span>Tambah Lowongan</span>
          </button>
        </div>

        <!-- Kartu target role -->
        <section class="rec-card" style="margin-bottom: 28px">
          <div class="rec-card-head">
            <h2 class="rec-card-title">Daftar Lowongan Aktif</h2>
            <span class="rec-card-count" id="roles-summary">—</span>
          </div>
          <div class="rec-role-cards" id="rec-role-cards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px">
            <!-- diisi js/pages/recruiter-roles.js -->
          </div>
        </section>

        <!-- Workspace mapping dua panel -->
        <section class="rec-card">
          <div class="rec-card-head">
            <div>
              <h2 class="rec-card-title" id="mapping-panel-title">Benchmark Skill Lowongan Terpilih</h2>
              <p class="rec-card-sub" id="mapping-panel-sub">Pilih salah satu lowongan di atas untuk melihat detail kebutuhan skill.</p>
            </div>
          </div>
          <div class="rec-mapping-grid" id="rec-mapping-panel" style="margin-top: 12px">
            <!-- diisi js/pages/recruiter-roles.js -->
          </div>
        </section>

      </main>

      <!-- ===== MODAL TAMBAH / EDIT LOWONGAN ===== -->
      <div id="modal-role" style="display: none; position: fixed; inset: 0; z-index: 9999">
        <div id="modal-role-backdrop" style="position: absolute; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px)"></div>
        <div style="position: relative; max-width: 560px; width: 90%; max-height: 90vh; overflow-y: auto; margin: 50px auto; background: white; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); border: 1px solid var(--border-light)">
          
          <div style="padding: 20px 24px; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center">
            <h3 id="modal-role-title" style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin: 0">Tambah Lowongan Baru</h3>
            <button type="button" id="modal-role-close" style="background: none; border: none; font-size: 24px; line-height: 1; cursor: pointer; color: var(--text-muted)">&times;</button>
          </div>

          <form id="form-role" style="padding: 24px; display: grid; gap: 16px">
            <input type="hidden" id="role-input-id" value="">

            <div>
              <label for="role-input-title" style="display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px">Judul Lowongan / Posisi *</label>
              <input type="text" id="role-input-title" required placeholder="Contoh: Senior Backend Developer" style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid var(--border-light); border-radius: 8px; font-family: inherit; font-size: 14px">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px">
              <div>
                <label for="role-input-career" style="display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px">Jalur Karier (Target Career) *</label>
                <select id="role-input-career" required style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid var(--border-light); border-radius: 8px; font-family: inherit; font-size: 14px; background: white">
                  <option value="">Pilih Jalur Karier</option>
                </select>
              </div>

              <div>
                <label for="role-input-positions" style="display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px">Jumlah Posisi / Kursi</label>
                <input type="number" id="role-input-positions" min="1" value="1" style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid var(--border-light); border-radius: 8px; font-family: inherit; font-size: 14px">
              </div>
            </div>

            <div>
              <label for="role-input-desc" style="display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px">Deskripsi Pekerjaan</label>
              <textarea id="role-input-desc" rows="3" placeholder="Jelaskan gambaran tanggung jawab pekerjaan..." style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid var(--border-light); border-radius: 8px; font-family: inherit; font-size: 14px; resize: vertical"></textarea>
            </div>

            <div>
              <label for="role-input-req" style="display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px">Persyaratan / Kualifikasi</label>
              <textarea id="role-input-req" rows="2" placeholder="Contoh: Menguasai PHP, PostgreSQL, REST APIs..." style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid var(--border-light); border-radius: 8px; font-family: inherit; font-size: 14px; resize: vertical"></textarea>
            </div>

            <div style="padding-top: 12px; border-top: 1px solid var(--border-light); display: flex; justify-content: flex-end; gap: 10px">
              <button type="button" id="btn-cancel-role" class="btn btn-secondary btn-36">Batal</button>
              <button type="submit" id="btn-submit-role" class="btn btn-primary btn-36">Simpan Lowongan</button>
            </div>
          </form>

        </div>
      </div>

<?php require_once __DIR__ . '/../../includes/layouts/hr_footer.php'; ?>
