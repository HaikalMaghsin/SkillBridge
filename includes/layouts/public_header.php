<?php
// includes/layouts/public_header.php
// Header Navigasi Publik untuk Landing Page & Halaman Publik
$path_prefix = $path_prefix ?? '';
?>
<header class="navbar">
  <a class="navbar-brand" href="<?= $path_prefix ?>index.php" aria-label="SkillBridge home">
    <span class="navbar-logo" aria-hidden="true">S</span>
    <span class="navbar-brand-text">
      <span class="navbar-brand-name">SkillBridge</span>
      <span class="navbar-brand-subtitle">STUDENT PORTFOLIO</span>
    </span>
  </a>

  <nav class="navbar-nav" aria-label="Main">
    <a class="nav-item <?= ($nav_active ?? '') === 'home' ? 'active' : '' ?>" href="<?= $path_prefix ?>index.php#main" <?= ($nav_active ?? '') === 'home' ? 'aria-current="page"' : '' ?>>Home</a>
    <a class="nav-item <?= ($nav_active ?? '') === 'skills' ? 'active' : '' ?>" href="<?= $path_prefix ?>mahasiswa/skills.php">Explore Skills</a>
    <a class="nav-item" href="<?= $path_prefix ?>index.php#learning-paths">Learning Paths</a>
    <a class="nav-item <?= ($nav_active ?? '') === 'career' ? 'active' : '' ?>" href="<?= $path_prefix ?>mahasiswa/career.php">Career Opportunities</a>
    <a class="nav-item" href="<?= $path_prefix ?>index.php#insights">Resources</a>
  </nav>

  <div class="navbar-actions">
    <?php if (is_logged_in()): ?>
      <a class="navbar-login" href="<?= $path_prefix ?>logout.php">Logout</a>
      <a class="btn btn-accent btn-44" href="<?= $path_prefix ?><?= get_user_role() === 'admin' ? 'admin/index.php' : (get_user_role() === 'hr' ? 'hr/index.php' : 'mahasiswa/dashboard.php') ?>">Dashboard</a>
    <?php else: ?>
      <a class="navbar-login" href="<?= $path_prefix ?>login.php">Login</a>
      <a class="btn btn-accent btn-44" href="<?= $path_prefix ?>register.php">Get Started</a>
    <?php endif; ?>
  </div>
</header>
