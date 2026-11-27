<?php
// public/api/hr/email_settings.php
// GET: Ambil preferensi notifikasi email & riwayat log pengiriman
// POST: Simpan preferensi notifikasi email atau kirim email uji coba
require_once __DIR__ . '/../../../includes/auth/auth.php';
require_once __DIR__ . '/../../../includes/auth/csrf.php';
require_once __DIR__ . '/../../../includes/config/database.php';
require_once __DIR__ . '/../../../includes/mail/mailer.php';

header('Content-Type: application/json; charset=utf-8');

// Wajib login sebagai HR
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = current_user();
if ($user['role'] !== 'hr') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - HR access only']);
    exit;
}

$user_id = (int)$user['id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = get_db_connection();
    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    // Ambil company_id
    $stmt = $pdo->prepare('SELECT company_id FROM company_recruiters WHERE user_id = :user_id LIMIT 1');
    $stmt->execute([':user_id' => $user_id]);
    $recruiter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recruiter) {
        http_response_code(404);
        echo json_encode(['error' => 'Perusahaan tidak ditemukan']);
        exit;
    }

    $company_id = (int)$recruiter['company_id'];

    // Pastikan tabel company_email_settings tersedia
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS company_email_settings (
            id SERIAL PRIMARY KEY,
            company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
            notify_new_applicant BOOLEAN NOT NULL DEFAULT TRUE,
            notify_pipeline_status BOOLEAN NOT NULL DEFAULT TRUE,
            notify_interview_schedule BOOLEAN NOT NULL DEFAULT TRUE,
            notify_weekly_digest BOOLEAN NOT NULL DEFAULT FALSE,
            recipient_email VARCHAR(255),
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT uq_company_email_settings UNIQUE (company_id)
        )
    ");

    if ($method === 'GET') {
        $stmt = $pdo->prepare('SELECT * FROM company_email_settings WHERE company_id = :company_id LIMIT 1');
        $stmt->execute([':company_id' => $company_id]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            $settings = [
                'notify_new_applicant' => true,
                'notify_pipeline_status' => true,
                'notify_interview_schedule' => true,
                'notify_weekly_digest' => false,
                'recipient_email' => $user['email'] ?? ''
            ];
        } else {
            $settings['notify_new_applicant'] = (bool)$settings['notify_new_applicant'];
            $settings['notify_pipeline_status'] = (bool)$settings['notify_pipeline_status'];
            $settings['notify_interview_schedule'] = (bool)$settings['notify_interview_schedule'];
            $settings['notify_weekly_digest'] = (bool)$settings['notify_weekly_digest'];
            if (empty($settings['recipient_email'])) {
                $settings['recipient_email'] = $user['email'] ?? '';
            }
        }

        // Ambil riwayat log pengiriman email untuk email ini (jika ada)
        $logs = [];
        try {
            $targetEmail = $settings['recipient_email'] ?: ($user['email'] ?? '');
            $stmtLogs = $pdo->prepare('
                SELECT id, to_email, to_name, subject, note, created_at
                FROM mail_logs
                WHERE to_email = :email
                ORDER BY created_at DESC
                LIMIT 5
            ');
            $stmtLogs->execute([':email' => $targetEmail]);
            $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            // mail_logs belum ada atau error diabaikan
        }

        echo json_encode([
            'settings' => $settings,
            'logs' => $logs,
            'user_email' => $user['email'] ?? ''
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $action = $input['action'] ?? 'update_preferences';

        if ($action === 'send_test') {
            $targetEmail = trim($input['email'] ?? $user['email'] ?? '');
            if (!$targetEmail || !filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Alamat email tujuan tidak valid']);
                exit;
            }

            // Ambil info perusahaan
            $stmtComp = $pdo->prepare('SELECT name FROM companies WHERE id = :company_id');
            $stmtComp->execute([':company_id' => $company_id]);
            $compRow = $stmtComp->fetch(PDO::FETCH_ASSOC);
            $compName = $compRow['name'] ?? 'Perusahaan Mitra';

            $subject = 'Uji Coba Pengiriman Notifikasi Email · SkillBridge';
            $htmlBody = '
            <div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;">
                <div style="margin-bottom: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 12px;">
                    <h2 style="color: #1e293b; margin: 0; font-size: 20px;">SkillBridge &bull; Uji Coba Email Notifikasi</h2>
                </div>
                <p style="color: #334155; font-size: 15px; line-height: 1.6;">Halo <strong>' . htmlspecialchars($user['name']) . '</strong>,</p>
                <p style="color: #334155; font-size: 14px; line-height: 1.6;">Ini adalah email uji coba dari konsol rekruter <strong>' . htmlspecialchars($compName) . '</strong> di platform SkillBridge.</p>
                <div style="background: #f8fafc; border-left: 4px solid #2563eb; padding: 12px 16px; margin: 20px 0; border-radius: 0 8px 8px 0;">
                    <p style="margin: 0; font-size: 13px; color: #475569;"><strong>Status Pengiriman:</strong> Berhasil dikirim melalui sistem notifikasi SkillBridge.</p>
                    <p style="margin: 4px 0 0 0; font-size: 13px; color: #475569;"><strong>Waktu Kirim:</strong> ' . date('d F Y, H:i:s') . ' WIB</p>
                </div>
                <p style="color: #64748b; font-size: 13px; line-height: 1.5;">Pengaturan notifikasi email pelamar baru dan tahapan seleksi aktif sesuai preferensi yang Anda tentukan.</p>
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 24px 0;">
                <p style="color: #94a3b8; font-size: 12px; margin: 0;">&copy; ' . date('Y') . ' SkillBridge &bull; Penghubung Talenta Vokasi &amp; Industri</p>
            </div>';

            $result = send_email($targetEmail, $user['name'], $subject, $htmlBody);

            echo json_encode([
                'success' => true,
                'message' => 'Email uji coba berhasil dikirim ke ' . $targetEmail,
                'result' => $result
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Action: update_preferences
        $newApp = !empty($input['notify_new_applicant']);
        $pipeStat = !empty($input['notify_pipeline_status']);
        $interview = !empty($input['notify_interview_schedule']);
        $weekly = !empty($input['notify_weekly_digest']);
        $recipient = trim($input['recipient_email'] ?? $user['email'] ?? '');

        $stmt = $pdo->prepare('
            INSERT INTO company_email_settings (
                company_id, notify_new_applicant, notify_pipeline_status, notify_interview_schedule, notify_weekly_digest, recipient_email
            ) VALUES (
                :company_id, :new_app, :pipe_stat, :interview, :weekly, :recipient
            )
            ON CONFLICT (company_id)
            DO UPDATE SET
                notify_new_applicant = EXCLUDED.notify_new_applicant,
                notify_pipeline_status = EXCLUDED.notify_pipeline_status,
                notify_interview_schedule = EXCLUDED.notify_interview_schedule,
                notify_weekly_digest = EXCLUDED.notify_weekly_digest,
                recipient_email = EXCLUDED.recipient_email,
                updated_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([
            ':company_id' => $company_id,
            ':new_app' => $newApp ? 1 : 0,
            ':pipe_stat' => $pipeStat ? 1 : 0,
            ':interview' => $interview ? 1 : 0,
            ':weekly' => $weekly ? 1 : 0,
            ':recipient' => $recipient
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Preferensi notifikasi email berhasil disimpan'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API hr/email_settings.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
