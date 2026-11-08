<?php
// public/hr/candidate.php
// Detail Kandidat (read-only) · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('hr');

$page_title = 'Detail Kandidat · SkillBridge';
$role = 'hr';
$path_prefix = '../';
$rec_page = 'candidates';
$rec_title = 'Detail Kandidat';
$body_class = 'rec-body';
$body_attrs = 'data-rec-page="candidate"';
$page_script = 'recruiter-candidate.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/hr_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="rec-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/hr_header.php'; ?>

      <main class="rec-page-content">
        <div>
          <div class="rec-page-context">
            <p class="rec-breadcrumb">A &ndash; RECRUITER / DETAIL KANDIDAT</p>
            <div class="rec-date">
              <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="14" height="12.5" rx="1.5"/><path d="M3 8.5h14M7 3v3M13 3v3"/></svg>
              <span id="rec-today">—</span>
            </div>
          </div>
          <h1 class="rec-page-title" id="cand-title">Detail Kandidat</h1>
          <p class="rec-page-desc">Profil publik, skill confirmed, dan evidence kandidat. Akses HR bersifat baca saja.</p>
        </div>

        <section class="rec-card">
          <div class="rec-card-head">
            <div>
              <h2 class="rec-card-title">Profil Kandidat</h2>
              <p class="rec-card-sub" id="cand-target">—</p>
            </div>
            <a href="candidates.php" class="rec-pager-link">&larr; Kembali ke daftar</a>
          </div>
          <div class="rec-field-grid">
            <div class="rec-field">
              <label>Nama</label>
              <p class="rec-row-name-main" id="cand-name">—</p>
            </div>
            <div class="rec-field">
              <label>Target Role</label>
              <p class="rec-row-name-main" id="cand-role">—</p>
            </div>
            <div class="rec-field full">
              <label>Email</label>
              <p class="rec-row-name-sub" id="cand-email">—</p>
            </div>
          </div>
        </section>

        <section class="rec-card">
          <div class="rec-card-head">
            <div>
              <h2 class="rec-card-title">Skill Confirmed</h2>
              <p class="rec-card-sub" id="cand-skill-count">—</p>
            </div>
          </div>
          <div class="rec-cell rec-cell--wrap" id="cand-skills"></div>
        </section>

        <section class="rec-card">
          <div class="rec-card-head">
            <div>
              <h2 class="rec-card-title">Evidence</h2>
              <p class="rec-card-sub" id="cand-evidence-count">—</p>
            </div>
          </div>
          <div class="rec-table-wrap table-responsive">
            <table class="rec-native-table">
              <thead>
                <tr>
                  <th>Judul</th>
                  <th>Tipe</th>
                  <th>Tautan</th>
                </tr>
              </thead>
              <tbody id="cand-evidence">
                <tr><td colspan="3" class="rec-empty">Memuat evidence…</td></tr>
              </tbody>
            </table>
          </div>
        </section>
      </main>

<?php require_once __DIR__ . '/../../includes/layouts/hr_footer.php'; ?>
