<?php
// public/admin/vacancies.php
// Manajemen Lowongan · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('admin');

$page_title = 'Manajemen Lowongan · SkillBridge';
$role = 'admin';
$path_prefix = '../';
$admin_page = 'vacancies';
$body_class = 'admin-body';
$body_attrs = 'data-admin-page="vacancies"';
$page_script = 'admin-vacancies.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/admin_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="admin-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/admin_header.php'; ?>

      <main class="admin-page-content">
      <div class="admin-page-heading">
        <div>
          <p class="admin-eyebrow">Lowongan</p>
          <h1 class="admin-page-title">Manajemen Lowongan</h1>
          <p class="admin-page-desc">Pantau dan moderasi semua lowongan kerja yang diposting perusahaan mitra di platform SkillBridge.</p>
        </div>
      </div>

      <div class="admin-toolbar">
        <div class="admin-search" style="flex:1;min-width:200px">
          <input type="search" id="vacancy-search" placeholder="Cari judul atau perusahaan..." />
        </div>
        <select id="vacancy-status-filter" class="admin-select">
          <option value="all">Semua status</option>
          <option value="active">Aktif</option>
          <option value="inactive">Non-aktif</option>
          <option value="closed">Ditutup</option>
        </select>
      </div>

      <div class="admin-card">
        <div class="admin-table-wrap table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Posisi & Kategori</th>
                <th>Perusahaan</th>
                <th>Kuota</th>
                <th>Status</th>
                <th>Diposting oleh</th>
                <th>Dibuat</th>
                <th class="admin-text-end">Aksi</th>
              </tr>
            </thead>
            <tbody id="vacancies-table-body"></tbody>
          </table>
        </div>
      </div>
    </main>

<?php require_once __DIR__ . '/../../includes/layouts/admin_footer.php'; ?>