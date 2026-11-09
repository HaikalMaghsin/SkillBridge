<?php
// public/admin/admins.php
// Manajemen Administrator · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('admin');

$page_title = 'Manajemen Administrator · SkillBridge';
$role = 'admin';
$path_prefix = '../';
$admin_page = 'admins';
$body_class = 'admin-body';
$body_attrs = 'data-admin-page="admins"';
$page_script = 'admin-admins.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/admin_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="admin-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/admin_header.php'; ?>

      <main class="admin-page-content">
      <div class="admin-page-heading">
        <div>
          <p class="admin-eyebrow">Administrator</p>
          <h1 class="admin-page-title">Manajemen Administrator</h1>
          <p class="admin-page-desc">Kelola akun Admin Job dan Admin SkillBridge. Supervisor dan tim mereka dapat
            mengelola lowongan dan data masing-masing.</p>
        </div>
      </div>

      <div class="admin-toolbar">
        <div class="admin-search"><input type="text" id="admin-search" placeholder="Cari nama atau email..." /></div>
        <select id="admin-status-filter" class="admin-select">
          <option value="all">Semua status</option>
          <option value="active">Aktif</option>
          <option value="inactive">Non-aktif</option>
        </select>
      </div>

      <div class="admin-card">
        <div class="admin-table-wrap table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Administrator</th>
                <th>Status</th>
                <th>Dibuat</th>
                <th>Diperbarui</th>
                <th class="admin-text-end">Aksi</th>
              </tr>
            </thead>
            <tbody id="admins-table-body">
            </tbody>
          </table>
        </div>
      </div>
    </main>

<?php require_once __DIR__ . '/../../includes/layouts/admin_footer.php'; ?>