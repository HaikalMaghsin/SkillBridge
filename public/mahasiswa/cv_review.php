<?php
// public/mahasiswa/cv_review.php
// Halaman Review & Konfirmasi Skill dari CV (FR-MHS-05)
require_once __DIR__ . '/../../includes/auth/auth.php';

check_auth('mahasiswa');

// Ambil data sementara dari session
$detected_skills = $_SESSION['cv_detected_skills'] ?? [];
$cv_data = $_SESSION['cv_upload_data'] ?? null;

// Redirect jika tidak ada data
if (!$cv_data || empty($detected_skills)) {
    header('Location: ' . base_url('mahasiswa/cv.php'));
    exit;
}

$page_title = 'Review CV Skills · SkillBridge';
$active_nav = 'cv';
$role_css = ['style.css'];
$page_js = ['pages/cv_review.js'];

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/mahasiswa_header.php';
?>

<main class="student-main">
  <header class="page-head">
    <div>
      <h1 class="page-title">Review Detected Skills</h1>
      <p class="page-subtitle">We found <?= count($detected_skills) ?> matching skills in your CV. Select which ones to add to your profile.</p>
    </div>
  </header>

  <div class="page-content">
    <!-- CV Info -->
    <section class="card" style="max-width: 1000px; margin: 0 auto;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
          <h2 class="card-title" style="margin-bottom: 4px;"><?= e($cv_data['title']) ?></h2>
          <?php if ($cv_data['url']): ?>
            <a href="<?= e($cv_data['url']) ?>" target="_blank" rel="noopener noreferrer" class="text-link" style="font-size: 14px;">
              View CV Document →
            </a>
          <?php endif; ?>
        </div>
        <span class="badge" style="background: var(--tint-indigo); color: var(--primary);">
          <?= count($detected_skills) ?> skills detected
        </span>
      </div>

      <!-- Detected Skills List -->
      <div id="detected-skills-list" style="display: grid; gap: 12px;">
        <?php foreach ($detected_skills as $skill): ?>
        <label class="skill-checkbox-item" data-skill-id="<?= (int)$skill['id'] ?>">
          <input type="checkbox" name="skill_ids[]" value="<?= (int)$skill['id'] ?>" checked />
          <div class="skill-checkbox-content">
            <div class="skill-checkbox-name"><?= e($skill['name']) ?></div>
            <div class="skill-checkbox-category"><?= e($skill['category']) ?></div>
          </div>
          <svg class="skill-checkbox-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
            <circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="1.5"/>
            <path d="M6 10l3 3 5-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </label>
        <?php endforeach; ?>
      </div>

      <div class="form-actions" style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border);">
        <button type="button" class="btn btn-secondary" onclick="window.location.href='<?= base_url('mahasiswa/cv.php') ?>'">
          Cancel
        </button>
        <button type="button" class="btn btn-primary" id="confirm-btn">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M3 8l4 4 6-8"/>
          </svg>
          Confirm & Add to Profile
        </button>
      </div>
    </section>

    <!-- Instructions -->
    <section class="card" style="max-width: 1000px; margin: 32px auto 0;">
      <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">What Happens Next?</h3>
      <ul style="margin-left: 20px; line-height: 1.8; color: var(--text-muted);">
        <li>Your CV will be saved as <strong>evidence</strong> in your portfolio</li>
        <li>Selected skills will be added to your profile with <strong>candidate</strong> status</li>
        <li>The CV evidence will be automatically linked to these skills</li>
        <li>You can view and manage these skills from the <a href="<?= base_url('mahasiswa/skills.php') ?>">My Skills</a> page</li>
      </ul>
    </section>
  </div>
</main>

<style>
.skill-checkbox-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border: 2px solid var(--border);
  border-radius: var(--radius);
  cursor: pointer;
  transition: all 0.2s;
  background: var(--surface);
}

.skill-checkbox-item:hover {
  border-color: var(--primary);
  background: var(--tint-indigo);
}

.skill-checkbox-item input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
  accent-color: var(--primary);
}

.skill-checkbox-content {
  flex: 1;
}

.skill-checkbox-name {
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 4px;
}

.skill-checkbox-category {
  font-size: 13px;
  color: var(--text-muted);
}

.skill-checkbox-icon {
  color: var(--border);
  transition: color 0.2s;
}

.skill-checkbox-item:has(input:checked) .skill-checkbox-icon {
  color: var(--primary);
}

.skill-checkbox-item:has(input:checked) {
  border-color: var(--primary);
  background: var(--tint-indigo);
}
</style>

<script>
// Pass CV data to JavaScript
window.CV_DATA = <?= json_encode([
    'title' => $cv_data['title'],
    'url' => $cv_data['url'],
    'content' => $cv_data['content']
]) ?>;
</script>

<?php require_once __DIR__ . '/../../includes/layouts/mahasiswa_footer.php'; ?>
