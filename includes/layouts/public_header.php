<?php
// includes/layouts/public_header.php
// Header Navigasi Publik untuk Landing Page & Halaman Publik
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/r2.php';
?>
<header class="navbar">
  <a class="navbar-brand" href="<?= e(base_url('index.php')) ?>" aria-label="SkillBridge home">
    <span class="navbar-logo" aria-hidden="true">S</span>
    <span class="navbar-brand-text">
      <span class="navbar-brand-name">SkillBridge</span>
      <span class="navbar-brand-subtitle">STUDENT PORTFOLIO</span>
    </span>
  </a>

  <!-- Mobile Hamburger Toggle -->
  <button type="button" class="navbar-toggle" aria-label="Buka menu navigasi" aria-expanded="false">
    <span class="navbar-toggle-bar"></span>
    <span class="navbar-toggle-bar"></span>
    <span class="navbar-toggle-bar"></span>
  </button>

  <nav class="navbar-nav" aria-label="Main">
    <a class="nav-item <?= ($nav_active ?? '') === 'home' ? 'active' : '' ?>" href="<?= e(base_url('index.php#main')) ?>" <?= ($nav_active ?? '') === 'home' ? 'aria-current="page"' : '' ?>>Home</a>
    <a class="nav-item <?= ($nav_active ?? '') === 'skills' ? 'active' : '' ?>" href="<?= e(base_url('mahasiswa/skills.php')) ?>">Explore Skills</a>
    <a class="nav-item" href="<?= e(base_url('index.php#learning-paths')) ?>">Learning Paths</a>
    <a class="nav-item <?= ($nav_active ?? '') === 'career' ? 'active' : '' ?>" href="<?= e(base_url('mahasiswa/career.php')) ?>">Career Opportunities</a>
    <a class="nav-item" href="<?= e(base_url('index.php#insights')) ?>">Resources</a>
  </nav>

  <?php if (is_logged_in()):
    $user = current_user() ?? [];
    $dashUrl = get_user_role() === 'admin'
      ? 'admin/index.php'
      : (get_user_role() === 'hr' ? 'hr/index.php' : 'mahasiswa/dashboard.php');
  ?>
    <div class="navbar-actions">
      <a class="btn btn-accent btn-44" href="<?= e(base_url($dashUrl)) ?>">Dashboard</a>
    </div>

    <div class="navbar-profile" style="position: relative;">
      <a href="<?= e(base_url($dashUrl)) ?>" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 12px;">
        <div class="navbar-profile-text">
          <div class="navbar-profile-name"><?= e($user['name'] ?? 'User') ?></div>
          <div class="navbar-profile-email"><?= e($user['email'] ?? '') ?></div>
        </div>
        <div class="navbar-avatar" style="overflow: hidden; display: flex; align-items: center; justify-content: center;">
          <?php if (!empty($user['avatar_url'])): ?>
            <img src="<?= e(r2_display_url($user['avatar_url'])) ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;" onerror="this.style.display='none'; this.parentElement.textContent='<?= e($user['initials'] ?? 'SB') ?>';">
          <?php else: ?>
            <?= e($user['initials'] ?? 'SB') ?>
          <?php endif; ?>
        </div>
      </a>
      <a class="navbar-logout-btn" href="<?= e(base_url('logout.php')) ?>" title="Keluar / Logout" aria-label="Logout">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M6.75 15.75H3.375A1.125 1.125 0 012.25 14.625V3.375A1.125 1.125 0 013.375 2.25h3.375M12.375 12.375L15.75 9l-3.375-3.375M15.75 9H6.75"/>
        </svg>
      </a>
    </div>
  <?php else: ?>
    <div class="navbar-actions">
      <a class="navbar-login" href="<?= e(base_url('login.php')) ?>">Login</a>
      <a class="btn btn-accent btn-44" href="<?= e(base_url('register.php')) ?>">Get Started</a>
    </div>
  <?php endif; ?>
</header>
