<?php
// includes/layouts/hr_header.php
// Header Konsol HR / Recruiter
require_once __DIR__ . '/../config/app.php';

$user = current_user() ?? [];
$rec_title = $rec_title ?? 'Perusahaan Partner';
?>
<header class="rec-header">
  <div class="rec-header-left">
    <!-- Hamburger button for Off-canvas Sidebar Mobile/Tablet -->
    <button type="button" class="sidebar-toggle" aria-label="Buka navigasi sidebar" data-toggle-sidebar>
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 5h14M3 10h14M3 15h14"/></svg>
    </button>
    <div class="rec-workspace-title">
      <svg width="18" height="18" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 1.5L2.5 4v4.5c0 3.5 2.5 6.5 6 7.5 3.5-1 6-4 6-7.5V4L8.5 1.5z"/><path d="M6 8.5l2 2 3.5-3.5"/></svg>
      <span><?= htmlspecialchars($rec_title) ?></span>
    </div>
    <span class="rec-role-pill">Company Admin</span>
  </div>
  <div class="rec-header-right" style="display: flex; align-items: center; gap: 12px">
    <!-- Notification Bell Button -->
    <a href="<?= e(base_url('hr/candidates.php?status=New')) ?>" id="hr-notif-link" title="Pelamar Baru" style="position: relative; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; background: var(--surface-secondary, #f1f5f9); color: var(--text-primary); text-decoration: none; transition: background 0.2s">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
      <span id="hr-notif-badge" style="display: none; position: absolute; top: -2px; right: -2px; min-width: 18px; height: 18px; border-radius: 9px; background: #ef4444; color: white; font-size: 10px; font-weight: 800; display: none; align-items: center; justify-content: center; padding: 0 4px; box-shadow: 0 2px 5px rgba(239, 68, 68, 0.4)">0</span>
    </a>

    <div class="rec-header-profile-text">
      <span class="rec-header-name"><?= htmlspecialchars($user['name'] ?? 'Aditya Pratama') ?></span>
      <span class="rec-header-role"><?= htmlspecialchars($user['role_label'] ?? 'Admin Perusahaan · PT Tech Indonesia') ?></span>
    </div>
    <div class="rec-avatar"><?= htmlspecialchars($user['initials'] ?? 'AP') ?></div>
  </div>
</header>
