<?php
// includes/layouts/admin_footer.php
// Footer & Script Loader Konsol Admin
$path_prefix = $path_prefix ?? '../';
$page_script = $page_script ?? '';
?>
      <footer style="padding: 24px 32px 32px; color: var(--text-muted); font-size: 13px; text-align: center;">
        <p>© 2026 SkillBridge · Master Data &amp; Platform Admin Console</p>
      </footer>
    </div><!-- /.admin-workspace -->

    <script src="<?= $path_prefix ?>js/ui.js"></script>
    <script src="<?= $path_prefix ?>js/admin-nav.js"></script>
    <script src="<?= $path_prefix ?>js/api.js"></script>
    <?php if ($page_script): ?>
      <script src="<?= $path_prefix ?>js/pages/<?= htmlspecialchars($page_script) ?>"></script>
    <?php endif; ?>
  </body>
</html>
