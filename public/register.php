<?php
// public/register.php
// Halaman Registrasi Mahasiswa Baru
require_once __DIR__ . '/../includes/auth/auth.php';

$page_title = 'Register · SkillBridge';
$role = 'public';
$path_prefix = '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($password) < 8) {
        $error = 'Kata sandi minimal harus 8 karakter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        $pdo = get_db_connection();
        if ($pdo) {
            try {
                // Cek email duplikat
                $stmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = :email LIMIT 1');
                $stmt->execute([':email' => $email]);
                if ($stmt->fetch()) {
                    $error = 'Email ini sudah terdaftar. Silakan login.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, :role)');
                    $ins->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':hash' => $hash,
                        ':role' => 'mahasiswa',
                    ]);
                    attempt_login($email, $password);
                    header('Location: mahasiswa/dashboard.php');
                    exit;
                }
            } catch (Exception $e) {
                error_log('Register error: ' . $e->getMessage());
                $error = 'Terjadi kesalahan sistem saat mendaftar.';
            }
        } else {
            // Mode fallback tanpa DB: langsung buat sesi mahasiswa
            attempt_login($email, $password);
            $_SESSION['user']['name'] = $name;
            header('Location: mahasiswa/dashboard.php');
            exit;
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

        <h1 style="font-size: 28px; font-weight: 700; text-align: center; margin-bottom: 8px">Create account</h1>
        <p style="text-align: center; color: var(--text-muted); font-size: 14px; margin-bottom: 24px">Start tracking your skills and building evidence.</p>

        <?php if (!empty($error)): ?>
          <div style="background: #FEF2F2; color: #991B1B; border: 1px solid #F87171; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px;">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form id="register-form" method="POST" action="register.php" style="display: grid; gap: 16px">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>" />

          <div class="field">
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" placeholder="Jhon Doe" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" />
          </div>

          <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
          </div>

          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="At least 8 characters" minlength="8" required />
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; height: 44px">Create Account</button>
        </form>

        <p style="text-align: center; font-size: 14px; color: var(--text-muted); margin-top: 20px">
          Already have an account?
          <a href="login.php" style="color: var(--primary); font-weight: 700; text-decoration: none">Sign in</a>
        </p>
      </article>
    </main>

    <script src="js/ui.js"></script>
    <script src="js/api.js"></script>
    <script src="js/pages/register.js"></script>
  </body>
</html>
