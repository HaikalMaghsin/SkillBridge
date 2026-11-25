<?php
// includes/auth/password_reset.php
// Logika Reset Password untuk 3 Role (Mahasiswa, HR, Admin) dengan Verifikasi Email & Token Aman

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../mail/mailer.php';
require_once __DIR__ . '/auth.php';

/**
 * Format nama peran untuk label antarmuka & email
 */
function get_role_display_name($role) {
    switch ($role) {
        case 'admin':
            return 'Administrator';
        case 'hr':
            return 'Perusahaan / Recruiter (HR)';
        case 'user':
        case 'mahasiswa':
        default:
            return 'Mahasiswa / Talenta';
    }
}

/**
 * Meminta token reset kata sandi dan mengirim email verifikasi ke pengguna.
 *
 * @param string $email
 * @return array
 */
function request_password_reset($email) {
    $email = trim((string) $email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'error'   => 'Format alamat email tidak valid.',
        ];
    }

    $pdo = get_db_connection();
    if (!$pdo) {
        return [
            'success' => false,
            'error'   => 'Layanan database sedang tidak tersedia. Coba lagi beberapa saat lagi.',
        ];
    }

    // Cari akun berdasarkan email (case-insensitive)
    $stmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Untuk standar keamanan (anti-enumeration), jika user tidak ditemukan, tetap beri respons sukses generik
    if (!$user) {
        return [
            'success' => true,
            'message' => 'Jika alamat email tersebut terdaftar di SkillBridge, tautan verifikasi telah dikirim ke kotak masuk Anda.',
            'found'   => false,
        ];
    }

    // Periksa status keaktifan akun
    if (!db_is_true($user['is_active'])) {
        return [
            'success' => false,
            'error'   => 'Akun ini dinonaktifkan oleh administrator. Silakan hubungi pihak kampus / pengelola.',
        ];
    }

    // Buat token acak berkekuatan kriptografi 64 karakter (32 bytes hex)
    $plain_token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $plain_token);

    // Token berlaku 1 jam dari sekarang
    $expires_at = date('Y-m-d H:i:s', time() + 3600);

    try {
        $pdo->beginTransaction();

        // Nonaktifkan token lama yang belum terpakai untuk user ini
        $revokeStmt = $pdo->prepare('UPDATE password_reset_tokens SET used_at = CURRENT_TIMESTAMP WHERE user_id = :uid AND used_at IS NULL');
        $revokeStmt->execute([':uid' => $user['id']]);

        // Simpan token baru (berlaku 1 jam ke depan dihitung langsung oleh database)
        $insertStmt = $pdo->prepare("
            INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
            VALUES (:uid, :token_hash, CURRENT_TIMESTAMP + INTERVAL '1 hour')
        ");
        $insertStmt->execute([
            ':uid'        => $user['id'],
            ':token_hash' => $token_hash,
        ]);

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'success' => false,
            'error'   => 'Gagal memproses token reset: ' . $e->getMessage(),
        ];
    }

    // Bangun URL reset absolut
    $reset_url = app_url('reset-password.php?token=' . $plain_token);
    $role_name = get_role_display_name($user['role']);

    // Rancang template email HTML modern & responsif
    $html_body = compose_reset_email_html($user['name'], $role_name, $reset_url);

    // Kirim email
    $mail_result = send_email(
        $user['email'],
        $user['name'],
        'Verifikasi Reset Kata Sandi · SkillBridge',
        $html_body
    );

    return [
        'success'    => true,
        'found'      => true,
        'message'    => 'Tautan verifikasi telah dikirim ke ' . htmlspecialchars($user['email']) . '. Silakan periksa kotak masuk atau folder spam Anda.',
        'email'      => $user['email'],
        'role'       => $user['role'],
        'role_label' => $role_name,
        'reset_url'  => $reset_url,
        'mail_mode'  => $mail_result['mode'] ?? 'smtp',
    ];
}

/**
 * Memverifikasi validitas token reset dari URL.
 *
 * @param string $plain_token
 * @return array
 */
function verify_password_reset_token($plain_token) {
    $plain_token = trim((string) $plain_token);
    if ($plain_token === '' || strlen($plain_token) !== 64 || !ctype_xdigit($plain_token)) {
        return [
            'valid' => false,
            'error' => 'Format token reset tidak valid atau tidak lengkap.',
        ];
    }

    $pdo = get_db_connection();
    if (!$pdo) {
        return [
            'valid' => false,
            'error' => 'Layanan database tidak tersedia.',
        ];
    }

    $token_hash = hash('sha256', $plain_token);

    $stmt = $pdo->prepare('
        SELECT prt.id AS token_id, prt.expires_at, prt.used_at,
               (prt.expires_at < CURRENT_TIMESTAMP) AS is_expired,
               u.id AS user_id, u.name, u.email, u.role, u.is_active
        FROM password_reset_tokens prt
        JOIN users u ON u.id = prt.user_id
        WHERE prt.token_hash = :hash
        ORDER BY prt.id DESC
        LIMIT 1
    ');
    $stmt->execute([':hash' => $token_hash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return [
            'valid' => false,
            'error' => 'Tautan reset tidak ditemukan. Pastikan Anda membuka link persis seperti di email verifikasi.',
        ];
    }

    if (!empty($row['used_at'])) {
        return [
            'valid' => false,
            'error' => 'Tautan reset ini sudah pernah digunakan sebelumnya. Silakan minta tautan baru.',
        ];
    }

    if (db_is_true($row['is_expired'])) {
        return [
            'valid' => false,
            'error' => 'Tautan reset ini sudah kedaluwarsa (masa berlaku 1 jam). Silakan minta tautan baru.',
        ];
    }

    if (!db_is_true($row['is_active'])) {
        return [
            'valid' => false,
            'error' => 'Akun pengguna ini sedang tidak aktif.',
        ];
    }

    return [
        'valid'      => true,
        'token_id'   => (int) $row['token_id'],
        'user_id'    => (int) $row['user_id'],
        'name'       => $row['name'],
        'email'      => $row['email'],
        'role'       => $row['role'],
        'role_label' => get_role_display_name($row['role']),
    ];
}

/**
 * Menyelesaikan proses reset kata sandi dengan token valid.
 *
 * @param string $plain_token
 * @param string $new_password
 * @param string $confirm_password
 * @return array
 */
function complete_password_reset($plain_token, $new_password, $confirm_password) {
    $verify = verify_password_reset_token($plain_token);
    if (!$verify['valid']) {
        return [
            'success' => false,
            'error'   => $verify['error'],
        ];
    }

    if (strlen($new_password) < 8 || strlen($new_password) > 72) {
        return [
            'success' => false,
            'error'   => 'Kata sandi baru harus memiliki panjang antara 8 hingga 72 karakter.',
        ];
    }

    if ($new_password !== $confirm_password) {
        return [
            'success' => false,
            'error'   => 'Konfirmasi kata sandi baru tidak cocok.',
        ];
    }

    $pdo = get_db_connection();
    if (!$pdo) {
        return [
            'success' => false,
            'error'   => 'Layanan database tidak tersedia.',
        ];
    }

    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // 1. Perbarui password user
        $userStmt = $pdo->prepare('
            UPDATE users
            SET password_hash = :hash, updated_at = CURRENT_TIMESTAMP
            WHERE id = :uid
        ');
        $userStmt->execute([
            ':hash' => $new_hash,
            ':uid'  => $verify['user_id'],
        ]);

        // 2. Tandai token sudah digunakan
        $tokenStmt = $pdo->prepare('
            UPDATE password_reset_tokens
            SET used_at = CURRENT_TIMESTAMP
            WHERE id = :token_id
        ');
        $tokenStmt->execute([
            ':token_id' => $verify['token_id'],
        ]);

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'success' => false,
            'error'   => 'Gagal memperbarui kata sandi di database: ' . $e->getMessage(),
        ];
    }

    // Kirim email notifikasi bahwa password telah berhasil diubah
    $notif_body = compose_reset_success_email_html($verify['name']);
    @send_email(
        $verify['email'],
        $verify['name'],
        'Kata Sandi Berhasil Diperbarui · SkillBridge',
        $notif_body
    );

    return [
        'success'    => true,
        'message'    => 'Kata sandi berhasil diperbarui! Silakan masuk dengan kata sandi baru Anda.',
        'role'       => $verify['role'],
        'role_label' => $verify['role_label'],
    ];
}

/**
 * Template Email Verifikasi Reset Kata Sandi
 */
function compose_reset_email_html($name, $role_label, $reset_url) {
    $safe_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safe_role = htmlspecialchars($role_label, ENT_QUOTES, 'UTF-8');
    $safe_url  = htmlspecialchars($reset_url, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password SkillBridge</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1E293B;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFC; padding: 40px 15px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden;" cellspacing="0" cellpadding="0">
          <!-- Header -->
          <tr>
            <td style="padding: 28px 32px; background: linear-gradient(135deg, #4338CA 0%, #6366F1 100%); text-align: left;">
              <table role="presentation" cellspacing="0" cellpadding="0">
                <tr>
                  <td style="width: 36px; height: 36px; background: rgba(255, 255, 255, 0.2); border-radius: 8px; text-align: center; vertical-align: middle; font-size: 20px; font-weight: 800; color: #FFFFFF;">S</td>
                  <td style="padding-left: 12px; font-size: 20px; font-weight: 700; color: #FFFFFF; letter-spacing: -0.3px;">SkillBridge</td>
                </tr>
              </table>
            </td>
          </tr>
          <!-- Body -->
          <tr>
            <td style="padding: 32px;">
              <h1 style="margin: 0 0 16px; font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.3;">Permintaan Verifikasi Reset Kata Sandi</h1>
              <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #475569;">
                Halo <strong>{$safe_name}</strong>,
              </p>
              <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.6; color: #475569;">
                Kami menerima permintaan pengaturan ulang kata sandi untuk akun Anda dengan peran <strong>{$safe_role}</strong>. Untuk melanjutkan dan mengatur kata sandi baru Anda, silakan klik tombol verifikasi di bawah ini:
              </p>

              <!-- Tombol CTA -->
              <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 28px 0;">
                <tr>
                  <td align="center" style="border-radius: 8px; background-color: #4F46E5;">
                    <a href="{$safe_url}" target="_blank" style="display: inline-block; padding: 14px 28px; font-size: 15px; font-weight: 600; color: #FFFFFF; text-decoration: none; border-radius: 8px;">
                      Verifikasi &amp; Reset Kata Sandi &rarr;
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin: 0 0 16px; font-size: 13px; line-height: 1.6; color: #64748B;">
                Atau salin dan tempel tautan berikut ke browser Anda jika tombol di atas tidak berfungsi:
              </p>
              <p style="margin: 0 0 24px; font-size: 12px; line-height: 1.5; color: #4F46E5; word-break: break-all; background: #F1F5F9; padding: 10px 12px; border-radius: 6px;">
                {$safe_url}
              </p>

              <div style="border-top: 1px solid #E2E8F0; padding-top: 18px; margin-top: 24px;">
                <p style="margin: 0 0 8px; font-size: 13px; color: #94A3B8; line-height: 1.5;">
                  ⏳ <strong>Perhatian:</strong> Tautan verifikasi ini hanya berlaku selama <strong>1 jam</strong>.
                </p>
                <p style="margin: 0; font-size: 13px; color: #94A3B8; line-height: 1.5;">
                  🔒 Jika Anda tidak pernah meminta reset kata sandi ini, abaikan email ini. Akun Anda tetap aman dan kata sandi tidak akan berubah.
                </p>
              </div>
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="padding: 20px 32px; background-color: #F8FAFC; border-top: 1px solid #E2E8F0; text-align: center; font-size: 12px; color: #94A3B8;">
              &copy; SkillBridge — Platform Validasi Bukti &amp; Kesiapan Talenta.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

/**
 * Template Email Notifikasi Berhasil Reset
 */
function compose_reset_success_email_html($name) {
    $safe_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $time = date('d M Y, H:i') . ' WIB';

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Kata Sandi Berhasil Diperbarui</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1E293B;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding: 40px 15px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width: 540px; background-color: #FFFFFF; border-radius: 12px; border: 1px solid #E2E8F0; padding: 32px;" cellspacing="0" cellpadding="0">
          <tr>
            <td>
              <h2 style="margin: 0 0 14px; font-size: 20px; color: #16A34A;">✓ Kata Sandi Berhasil Diperbarui</h2>
              <p style="margin: 0 0 14px; font-size: 14px; color: #475569;">
                Halo <strong>{$safe_name}</strong>,
              </p>
              <p style="margin: 0 0 16px; font-size: 14px; color: #475569; line-height: 1.5;">
                Kata sandi untuk akun SkillBridge Anda telah berhasil diubah pada <strong>{$time}</strong>.
              </p>
              <p style="margin: 0; font-size: 13px; color: #64748B; line-height: 1.5;">
                Jika bukan Anda yang melakukan perubahan ini, segera hubungi tim administrator SkillBridge untuk mengamankan akun Anda.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}
