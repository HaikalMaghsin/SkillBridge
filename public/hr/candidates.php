<?php
// public/hr/candidates.php
// Pipeline & Kualifikasi Kandidat · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('hr');

$page_title = 'Pipeline Kandidat · SkillBridge';
$role = 'hr';
$path_prefix = '../';
$rec_page = 'candidates';
$body_class = 'rec-body';
$body_attrs = 'data-rec-page="candidates"';
$page_script = 'hr-candidates.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/hr_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="rec-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/hr_header.php'; ?>

      <main class="rec-page-content">
        <!-- Header -->
        <div style="margin-bottom: 24px">
          <div class="rec-page-context">
            <p class="rec-breadcrumb">A &ndash; RECRUITER / PIPELINE KANDIDAT</p>
            <div class="rec-date">
              <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="14" height="12.5" rx="1.5"/><path d="M3 8.5h14M7 3v3M13 3v3"/></svg>
              <span id="rec-today"><?= date('d F Y') ?></span>
            </div>
          </div>
          <h1 class="rec-page-title">Pipeline Kandidat</h1>
          <p class="rec-page-desc">Tinjau mahasiswa yang menyatakan minat pada lowongan perusahaan Anda, analisis kecocokan skill, dan perbarui status seleksi.</p>
        </div>

        <!-- Filter & Search Toolbar -->
        <section class="rec-card" style="margin-bottom: 24px">
          <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px">
            <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 280px">
              <label class="rec-search" style="flex: 1; max-width: 360px">
                <svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="9" r="6"/><path d="M14 14l3.5 3.5"/></svg>
                <input type="search" id="candidate-search" placeholder="Cari nama, skill, atau role…" aria-label="Cari kandidat" />
              </label>

              <select class="rec-select" id="status-filter" aria-label="Filter status kandidat" style="min-width: 170px">
                <option value="all">Semua Status</option>
                <option value="New">Baru Masuk</option>
                <option value="Shortlisted">Di-shortlist</option>
                <option value="Interview">Undangan Interview</option>
                <option value="Accepted">Diterima</option>
                <option value="Rejected">Ditolak</option>
              </select>
            </div>

            <div style="display: flex; align-items: center; gap: 8px">
              <span style="font-size: 13px; color: var(--text-muted)">Total:</span>
              <span id="candidates-count" class="badge badge-growing" style="font-size: 13px; padding: 4px 12px; border-radius: 20px; font-weight: 700">0 kandidat</span>
            </div>
          </div>
        </section>

        <!-- Loading State -->
        <div id="loading-state" style="display: none; padding: 60px 20px; text-align: center">
          <div class="spinner" style="margin: 0 auto 16px"></div>
          <p style="color: var(--text-muted); font-size: 14px">Memuat data kandidat...</p>
        </div>

        <!-- Empty State -->
        <div id="empty-state" style="display: none; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; background: var(--surface); border: 1px dashed var(--border); border-radius: 16px; text-align: center">
          <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--tint-blue); color: var(--primary); display: grid; place-items: center; font-size: 28px; margin-bottom: 16px">
            👥
          </div>
          <h3 style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px">Belum Ada Kandidat</h3>
          <p style="font-size: 14px; color: var(--text-muted); max-width: 440px; line-height: 1.5">
            Belum ada mahasiswa yang melamar atau cocok dengan kriteria filter yang dipilih. Mahasiswa yang menyatakan minat pada lowongan Anda akan langsung muncul di sini.
          </p>
        </div>

        <!-- Candidates Grid -->
        <div id="candidates-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 32px">
          <!-- Diisi otomatis oleh hr-candidates.js -->
        </div>

        <!-- Info Strip -->
        <section class="rec-info-strip rec-info-strip--indigo">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="7.5"/><path d="M10 6.5v4.5M10 13.5h.01"/></svg>
          <div class="rec-info-strip-text">
            <span class="rec-info-strip-title">Sistem Penilaian Tier Otomatis Berbasis Skill Gap</span>
            <span class="rec-info-strip-sub">
              <strong>Tier 1 (Sangat Sesuai):</strong> Memenuhi ≥ 70% skill wajib lowongan. &bull; 
              <strong>Tier 2 (Cocok Magang/Intern):</strong> Memenuhi 40%–69% skill. &bull; 
              <strong>Tier 3 (Perlu Pengembangan):</strong> &lt; 40% skill.
            </span>
          </div>
        </section>
      </main>

      <!-- ===== MODAL DETAIL KANDIDAT ===== -->
      <div id="candidate-detail-modal" style="display: none; position: fixed; inset: 0; z-index: 9999">
        <!-- Backdrop -->
        <div id="candidate-modal-backdrop" style="position: absolute; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px)"></div>

        <!-- Modal Dialog -->
        <div style="position: relative; max-width: 680px; width: 90%; max-height: 90vh; overflow-y: auto; margin: 40px auto; background: var(--surface, #ffffff); border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid var(--border-light, #e2e8f0)">
          
          <!-- Modal Header -->
          <div style="padding: 24px 28px; border-bottom: 1px solid var(--border-light, #e2e8f0); display: flex; justify-content: space-between; align-items: flex-start; gap: 16px">
            <div style="display: flex; gap: 16px; align-items: center">
              <div id="modal-candidate-avatar" style="width: 56px; height: 56px; border-radius: 14px; background: var(--primary, #4338ca); color: white; display: grid; place-items: center; font-size: 24px; font-weight: 700; flex-shrink: 0">
                A
              </div>
              <div>
                <h2 id="modal-candidate-name" style="font-size: 20px; font-weight: 700; color: var(--text-primary, #0f172a); margin: 0 0 4px">Nama Kandidat</h2>
                <p id="modal-candidate-email" style="font-size: 13px; color: var(--text-muted, #64748b); margin: 0 0 6px">email@example.com</p>
                <div style="display: flex; gap: 8px; flex-wrap: wrap">
                  <span id="modal-target-role" class="rec-badge rec-badge--indigo" style="font-size: 11px">Target Role</span>
                  <span id="modal-status-badge" style="font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px">Status</span>
                  <span id="modal-tier-badge" style="font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px">Tier</span>
                </div>
              </div>
            </div>
            <button type="button" id="candidate-modal-close" style="background: none; border: none; font-size: 24px; line-height: 1; color: var(--text-muted, #64748b); cursor: pointer; padding: 4px; border-radius: 8px">&times;</button>
          </div>

          <!-- Modal Body -->
          <div style="padding: 24px 28px; display: grid; gap: 20px">
            
            <!-- Quick Stats -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 16px; background: var(--tint-gray, #f8fafc); border-radius: 12px; border: 1px solid var(--border-light, #e2e8f0)">
              <div>
                <p style="font-size: 12px; color: var(--text-muted, #64748b); margin: 0 0 4px; font-weight: 600">Skill Match Target</p>
                <p id="modal-skill-match" style="font-size: 18px; font-weight: 800; color: var(--text-primary, #0f172a); margin: 0">0/0</p>
              </div>
              <div>
                <p style="font-size: 12px; color: var(--text-muted, #64748b); margin: 0 0 4px; font-weight: 600">Bukti Portofolio / Evidence</p>
                <p id="modal-evidence-count" style="font-size: 18px; font-weight: 800; color: var(--text-primary, #0f172a); margin: 0">0 Items</p>
              </div>
            </div>

            <!-- Vacancy Info -->
            <div style="padding: 12px 16px; background: var(--tint-blue, #eef2ff); border-radius: 10px; display: flex; align-items: center; justify-content: space-between">
              <div>
                <span style="font-size: 11px; font-weight: 700; color: var(--primary, #4338ca); text-transform: uppercase">Melamar Posisi:</span>
                <p id="modal-vacancy-info" style="font-size: 14px; font-weight: 700; color: var(--text-primary, #0f172a); margin: 2px 0 0">—</p>
              </div>
              <button type="button" id="modal-btn-portfolio" class="btn btn-accent btn-36" style="font-size: 12px">
                Lihat Portofolio Lengkap ↗
              </button>
            </div>

            <!-- Skills List -->
            <div>
              <h4 style="font-size: 14px; font-weight: 700; color: var(--text-primary, #0f172a); margin: 0 0 10px">Keahlian Terkonfirmasi (Confirmed Skills)</h4>
              <div id="modal-skills-list" style="display: flex; flex-wrap: wrap; gap: 8px">
                <!-- Diisi otomatis -->
              </div>
            </div>

            <!-- Catatan Recruiter -->
            <div>
              <label for="modal-notes" style="display: block; font-size: 14px; font-weight: 700; color: var(--text-primary, #0f172a); margin-bottom: 6px">Catatan Reviewer / HR</label>
              <textarea id="modal-notes" rows="3" placeholder="Tambahkan catatan hasil review portofolio atau jadwal wawancara di sini..." style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid var(--border-light, #e2e8f0); border-radius: 10px; font-family: inherit; font-size: 14px; resize: vertical"></textarea>
            </div>
          </div>

          <!-- Modal Footer (Actions) -->
          <div style="padding: 20px 28px; border-top: 1px solid var(--border-light, #e2e8f0); background: var(--tint-gray, #f8fafc); border-radius: 0 0 20px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px">
            <span style="font-size: 13px; font-weight: 600; color: var(--text-muted, #64748b)">Update Status:</span>
            <div style="display: flex; gap: 8px; flex-wrap: wrap">
              <button type="button" id="modal-btn-reject" class="btn btn-danger btn-36" style="font-size: 13px">Tolak</button>
              <button type="button" id="modal-btn-shortlist" class="btn btn-primary btn-36" style="font-size: 13px">Shortlist</button>
              <button type="button" id="modal-btn-interview" class="btn btn-accent btn-36" style="font-size: 13px; background: #d97706; border-color: #d97706">Undang Interview</button>
              <button type="button" id="modal-btn-accept" class="btn btn-accent btn-36" style="font-size: 13px; background: #059669; border-color: #059669">Terima</button>
            </div>
          </div>

        </div>
      </div>

<?php require_once __DIR__ . '/../../includes/layouts/hr_footer.php'; ?>
