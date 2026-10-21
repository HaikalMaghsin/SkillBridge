<?php
// public/login.php
// Halaman Login Multi-Role (Mahasiswa, HR, Admin)
require_once __DIR__ . '/../includes/auth/auth.php';

$page_title = 'Login · SkillBridge';
$role = 'public';
$path_prefix = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $result = attempt_login($email, $password);
        if ($result['success']) {
            redirect_to_dashboard($result['role'], $path_prefix);
        } else {
            $error = 'Email atau kata sandi tidak valid.';
        }
    }
}

require_once __DIR__ . '/../includes/layouts/head.php';
?>
    <main class="auth-main">
      <article class="auth-card card">
        <div class="auth-logo">
          <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--primary); color: #fff; display: grid; place-items: center; font-size: 18px; font-weight: 800">S</div>
          <span>SkillBridge</span>
        </div>

        <h1 style="font-size: 28px; font-weight: 700; text-align: center; margin-bottom: 8px">Welcome back</h1>
        <p style="text-align: center; color: var(--text-muted); font-size: 14px; margin-bottom: 20px">Sign in to your account to continue building your portfolio.</p>

        <?php if (!empty($error)): ?>
          <div style="background: #FEF2F2; color: #991B1B; border: 1px solid #F87171; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px;">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <!-- Quick Role Demo Switcher for fast evaluation -->
        <div style="background: var(--surface-subtle, #f8fafc); border: 1px dashed var(--border-light, #cbd5e1); border-radius: 8px; padding: 12px; margin-bottom: 20px;">
          <p style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Akses Cepat Demo Role:</p>
          <div style="display: flex; gap: 6px; flex-wrap: wrap;">
            <button type="button" class="btn btn-sm" onclick="setDemo('jhondoe@gmail.com', 'password123')" style="font-size: 12px; padding: 4px 10px;">Mahasiswa</button>
            <button type="button" class="btn btn-sm" onclick="setDemo('hr@techindonesia.com', 'password123')" style="font-size: 12px; padding: 4px 10px;">HR / Recruiter</button>
            <button type="button" class="btn btn-sm" onclick="setDemo('admin@skillbridge.id', 'password123')" style="font-size: 12px; padding: 4px 10px;">Admin</button>
          </div>
        </div>

        <form id="login-form" method="POST" action="login.php" style="display: grid; gap: 16px">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>" />

          <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
          </div>

          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required />
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; height: 44px">Sign In</button>
        </form>

        <p style="text-align: center; font-size: 14px; color: var(--text-muted); margin-top: 20px">
          Don't have an account?
          <a href="register.php" style="color: var(--primary); font-weight: 700; text-decoration: none">Register now</a>
        </p>
      </article>
    </main>

    <script>
      function setDemo(email, pass) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = pass;
      }
    </script>
    <script src="js/ui.js"></script>
    <script src="js/api.js"></script>
    <script src="js/pages/login.js"></script>
  </body>
</html>
