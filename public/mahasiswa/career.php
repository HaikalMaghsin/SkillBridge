<?php
// public/mahasiswa/career.php
// Halaman Career Target · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('mahasiswa');

$page_title = 'Career Target · SkillBridge';
$role = 'mahasiswa';
$path_prefix = '../';
$nav_active = 'career';
$body_attrs = 'data-nav="career"';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/mahasiswa_header.php';
?>


    <main class="container">
      <!-- Page Head -->
      <div class="page-head">
        <div class="page-head-left">
          <p class="eyebrow">Choose a direction</p>
          <h1>Career Target</h1>
          <p>Select one simple target to compare with your current skills. You can change it at any time.</p>
        </div>
      </div>

      <!-- STATE 1: Current Target Header (shown when target is set) -->
      <div id="current-target-header" style="display: none">
        <div style="background: var(--surface); border: 1px solid var(--border-light); border-radius: 16px; padding: 24px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: start; gap: 24px">
          <div style="flex: 1; min-width: 0">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px">
              <span style="background: var(--tint-indigo); color: var(--primary); font-size: 12px; font-weight: 700; padding: 5px 12px; border-radius: 20px">Target Saat Ini</span>
              <div id="target-icon" style="width: 32px; height: 32px; display: grid; place-items: center"></div>
            </div>
            <h2 id="target-name" style="font-size: 24px; font-weight: 700; line-height: 1.2; color: var(--text-primary); margin-bottom: 8px"></h2>
            <p id="target-desc" style="font-size: 14px; line-height: 1.45; color: var(--text-muted); margin-bottom: 16px"></p>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap">
              <span style="font-size: 13px; font-weight: 600; color: var(--text-secondary)">Required Skills:</span>
              <div id="target-skills-chips" style="display: flex; gap: 6px; flex-wrap: wrap"></div>
            </div>
          </div>
          <button id="btn-change-role" class="btn btn-secondary" style="gap: 8px; flex-shrink: 0">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M13.333 2.667v3.333h-3.333M2.667 13.333v-3.333h3.333M13.067 5.333a5.333 5.333 0 0 0-9.134-2M2.933 10.667a5.333 5.333 0 0 0 9.134 2"/>
            </svg>
            Ganti Role
          </button>
        </div>
      </div>

      <!-- STATE 1: Vacancies Section (shown when target is set) -->
      <div id="vacancies-container" style="display: none">
        <h3 style="font-size: 20px; font-weight: 700; color: var(--text-primary); margin-bottom: 16px">Lowongan & Kebutuhan Industri Sekitar</h3>
        <div id="vacancies-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(480px, 1fr)); gap: 20px"></div>
        <div id="vacancies-empty" style="display: none; text-align: center; padding: 60px 20px; background: var(--surface-secondary); border-radius: 12px">
          <svg width="48" height="48" viewBox="0 0 48 48" fill="none" stroke="var(--text-muted)" stroke-width="1.5" style="margin: 0 auto 16px">
            <rect x="8" y="12" width="32" height="28" rx="2"/>
            <path d="M16 8v8M32 8v8M8 20h32"/>
          </svg>
          <p style="font-size: 16px; font-weight: 600; color: var(--text-primary); margin-bottom: 8px">Belum Ada Lowongan</p>
          <p style="font-size: 14px; color: var(--text-muted)">Belum ada perusahaan yang membuka lowongan untuk role ini.</p>
        </div>
      </div>

      <!-- STATE 2: Career Grid (shown when no target OR hidden when target exists) -->
      <div class="career-grid" id="career-grid"></div>
    </main>

    <!-- Modal: Vacancy Detail -->
    <div id="vacancy-detail-modal" style="display: none; position: fixed; inset: 0; z-index: 1000; overflow-y: auto">
      <div style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(3px)" id="vacancy-modal-backdrop"></div>
      <div style="position: relative; max-width: 900px; margin: 40px auto; padding: 0 24px">
        <div style="background: var(--surface); border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2); overflow: hidden">
          <!-- Modal Header -->
          <div style="display: flex; justify-content: space-between; align-items: start; padding: 32px 32px 24px; border-bottom: 1px solid var(--border-light)">
            <div style="flex: 1; min-width: 0">
              <h2 id="modal-vacancy-title" style="font-size: 26px; font-weight: 700; color: var(--text-primary); margin-bottom: 12px"></h2>
              <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap">
                <div style="display: flex; align-items: center; gap: 6px">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="var(--text-secondary)" stroke-width="1.5">
                    <rect x="2" y="4" width="12" height="10" rx="1"/>
                    <path d="M6 2v4M10 2v4M2 7h12"/>
                  </svg>
                  <span id="modal-company-name" style="font-size: 14px; font-weight: 600; color: var(--text-secondary)"></span>
                </div>
                <span style="color: var(--text-muted)">•</span>
                <span id="modal-industry" style="font-size: 14px; color: var(--text-secondary)"></span>
                <span style="color: var(--text-muted)">•</span>
                <span id="modal-location" style="font-size: 14px; color: var(--text-secondary)"></span>
              </div>
              <div style="display: flex; gap: 8px; margin-top: 12px">
                <span id="modal-job-type-badge" style="font-size: 12px; font-weight: 600; padding: 5px 12px; border-radius: 20px"></span>
                <span id="modal-tier-badge" style="font-size: 12px; font-weight: 600; padding: 5px 12px; border-radius: 20px"></span>
              </div>
            </div>
            <button id="vacancy-modal-close-btn" style="width: 40px; height: 40px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); display: grid; place-items: center; cursor: pointer; transition: all 0.2s; flex-shrink: 0" onmouseover="this.style.background='var(--surface-secondary)'" onmouseout="this.style.background='var(--surface)'">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M5 5l10 10M15 5l-10 10"/>
              </svg>
            </button>
          </div>

          <!-- Modal Body -->
          <div style="padding: 32px; max-height: 600px; overflow-y: auto">
            <!-- Company Profile -->
            <section style="margin-bottom: 32px">
              <h3 style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="var(--primary)" stroke-width="1.5">
                  <rect x="3" y="3" width="12" height="12" rx="2"/>
                  <path d="M9 6v6M6 9h6"/>
                </svg>
                Tentang Perusahaan
              </h3>
              <p id="modal-company-desc" style="font-size: 14px; line-height: 1.6; color: var(--text-secondary); margin-bottom: 12px"></p>
              <div id="modal-company-vision" style="background: var(--tint-blue); border-left: 3px solid var(--primary); padding: 12px 16px; border-radius: 8px">
                <p style="font-size: 13px; line-height: 1.5; color: var(--text-primary); font-style: italic"></p>
              </div>
            </section>

            <!-- Job Description -->
            <section style="margin-bottom: 32px">
              <h3 style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 12px">Deskripsi Pekerjaan</h3>
              <p id="modal-job-desc" style="font-size: 14px; line-height: 1.6; color: var(--text-secondary); white-space: pre-line"></p>
            </section>

            <!-- Requirements -->
            <section style="margin-bottom: 32px">
              <h3 style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 12px">Kualifikasi</h3>
              <p id="modal-requirements" style="font-size: 14px; line-height: 1.6; color: var(--text-secondary); white-space: pre-line"></p>
            </section>

            <!-- Skills Match Analysis -->
            <section>
              <h3 style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 16px">Analisis Kecocokan Skill</h3>
              <div style="display: grid; gap: 16px">
                <div>
                  <h4 style="font-size: 15px; font-weight: 600; color: var(--success); margin-bottom: 10px; display: flex; align-items: center; gap: 6px">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="8" cy="8" r="6"/>
                      <path d="M5 8l2 2 4-4"/>
                    </svg>
                    Skill yang Sudah Kamu Miliki
                  </h4>
                  <div id="modal-skills-owned" style="display: flex; flex-wrap: wrap; gap: 8px"></div>
                </div>
                <div>
                  <h4 style="font-size: 15px; font-weight: 600; color: var(--text-secondary); margin-bottom: 10px; display: flex; align-items: center; gap: 6px">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="8" cy="8" r="6"/>
                      <path d="M8 5v4M8 11h.01"/>
                    </svg>
                    Skill yang Perlu Dikembangkan
                  </h4>
                  <div id="modal-skills-gap" style="display: flex; flex-wrap: wrap; gap: 8px"></div>
                </div>
              </div>
            </section>
          </div>

          <!-- Modal Footer Actions -->
          <div style="padding: 24px 32px; border-top: 1px solid var(--border-light); background: var(--surface-secondary); display: flex; gap: 12px; justify-content: flex-end">
            <button id="modal-btn-focus-gap" class="btn btn-primary" style="gap: 6px">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="8" cy="8" r="6"/>
                <circle cx="8" cy="8" r="2"/>
              </svg>
              Fokuskan Skill Gap
            </button>
            <button id="modal-btn-express-interest" class="btn btn-accent" style="gap: 6px">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M8 3v10M3 8h10"/>
              </svg>
              <span id="modal-btn-text">Tandai Minat & Buka Portofolio</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Role Selection -->
    <div id="role-selection-modal" style="display: none; position: fixed; inset: 0; z-index: 1000; overflow-y: auto">
      <div style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(2px)" id="modal-backdrop"></div>
      <div style="position: relative; max-width: 1100px; margin: 40px auto; padding: 0 24px">
        <div style="background: var(--surface); border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15); overflow: hidden">
          <div style="display: flex; justify-content: space-between; align-items: center; padding: 24px 32px; border-bottom: 1px solid var(--border-light)">
            <div>
              <h2 style="font-size: 22px; font-weight: 700; color: var(--text-primary)">Pilih Target Karier</h2>
              <p style="font-size: 14px; color: var(--text-muted); margin-top: 4px">Pilih satu role untuk fokus pengembangan skill</p>
            </div>
            <button id="modal-close-btn" style="width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface); display: grid; place-items: center; cursor: pointer; transition: all 0.2s" onmouseover="this.style.background='var(--surface-secondary)'" onmouseout="this.style.background='var(--surface)'">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M5 5l10 10M15 5l-10 10"/>
              </svg>
            </button>
          </div>
          <div style="padding: 32px">
            <div class="career-grid" id="modal-career-grid"></div>
          </div>
        </div>
      </div>
    </div>

    

<?php require_once __DIR__ . '/../../includes/layouts/mahasiswa_footer.php'; ?>

    <script src="<?= e(asset('js/ui.js')) ?>"></script>
    <script src="<?= e(asset('js/api.js')) ?>"></script>
    <script src="<?= e(asset('js/pages/career.js')) ?>"></script>
  </body>
</html>