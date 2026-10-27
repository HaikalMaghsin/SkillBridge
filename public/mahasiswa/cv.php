<?php
// public/mahasiswa/cv.php
// Halaman Upload & Ekstraksi CV Mahasiswa (FR-MHS-05)
require_once __DIR__ . '/../../includes/auth/auth.php';

check_auth('mahasiswa');

$page_title = 'CV Upload · SkillBridge';
$active_nav = 'cv';
$role_css = ['style.css'];
$page_js = ['pages/cv.js'];

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/mahasiswa_header.php';
?>

<main class="student-main">
  <header class="page-head">
    <div>
      <h1 class="page-title">CV Upload & Skill Extraction</h1>
      <p class="page-subtitle">Upload your CV and let us extract relevant skills for your profile</p>
    </div>
  </header>

  <div class="page-content">
    <!-- Upload Section -->
    <section class="card" style="max-width: 800px; margin: 0 auto;">
      <h2 class="card-title">Upload Your CV</h2>
      <p class="card-desc" style="margin-bottom: 24px;">
        Provide your CV details below. We'll analyze the content and suggest relevant skills from our catalog.
      </p>

      <form id="cv-upload-form" class="form-stack">
        <div class="form-field">
          <label for="cv-title" class="form-label">CV Title *</label>
          <input
            type="text"
            id="cv-title"
            name="title"
            class="form-input"
            placeholder="e.g., Software Engineer CV 2024"
            required
          />
        </div>

        <div class="form-field">
          <label for="cv-url" class="form-label">CV Link (Optional)</label>
          <input
            type="url"
            id="cv-url"
            name="url"
            class="form-input"
            placeholder="https://drive.google.com/your-cv.pdf"
          />
          <p class="form-hint">Link to your CV file (Google Drive, Dropbox, personal website, etc.)</p>
        </div>

        <div class="form-field">
          <label for="cv-content" class="form-label">CV Content / Summary *</label>
          <textarea
            id="cv-content"
            name="content"
            class="form-input"
            rows="12"
            placeholder="Paste your CV text content here or write a summary of your experience, skills, and qualifications...

Example:
- Experience: 2 years as Junior Web Developer at PT ABC
- Skills: PHP, JavaScript, MySQL, React, Git, REST API
- Education: Bachelor of Computer Science
- Projects: E-commerce platform, Inventory management system"
            required
          ></textarea>
          <p class="form-hint">Paste the text content of your CV or write a comprehensive summary. This will be used to detect matching skills.</p>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary" id="submit-btn">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M8 2v12M2 8h12"/>
            </svg>
            Extract Skills from CV
          </button>
        </div>
      </form>
    </section>

    <!-- Instructions -->
    <section class="card" style="max-width: 800px; margin: 32px auto 0;">
      <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 12px;">How It Works</h3>
      <ol style="margin-left: 20px; line-height: 1.8; color: var(--text-muted);">
        <li>Fill in your CV title and paste the text content of your CV</li>
        <li>Click "Extract Skills from CV" to analyze the content</li>
        <li>Review the detected skills on the next page</li>
        <li>Confirm skills you want to add to your profile</li>
        <li>Your CV will be saved as evidence and linked to confirmed skills</li>
      </ol>
    </section>
  </div>
</main>

<?php require_once __DIR__ . '/../../includes/layouts/mahasiswa_footer.php'; ?>
