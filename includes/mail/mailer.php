<?php
// includes/mail/mailer.php
// Layanan pengiriman email SkillBridge (SMTP Native murni tanpa dependensi eksternal + Dev Fallback)

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Mengirim email menggunakan SMTP murni atau mencatat ke log lokal jika SMTP belum dikonfigurasi.
 *
 * @param string $to_email    Alamat email penerima
 * @param string $to_name     Nama penerima
 * @param string $subject     Subjek email
 * @param string $html_body   Konten email dalam format HTML
 * @param string $text_body   Konten alternatif plain text (opsional)
 * @return array ['success' => bool, 'mode' => 'smtp'|'log'|'fallback_log', 'message' => string, 'error' => ?string]
 */
function send_email($to_email, $to_name, $subject, $html_body, $text_body = '') {
    $smtp_host = getenv('SMTP_HOST') ?: '';
    $smtp_port = (int) (getenv('SMTP_PORT') ?: 587);
    $smtp_user = getenv('SMTP_USER') ?: '';
    $smtp_pass = getenv('SMTP_PASS') ?: '';
    $smtp_secure = strtolower(getenv('SMTP_SECURE') ?: 'tls');
    $smtp_from = getenv('SMTP_FROM') ?: 'noreply@skillbridge.id';
    $smtp_from_name = getenv('SMTP_FROM_NAME') ?: 'SkillBridge Support';

    // Jika SMTP_HOST belum disetel, simpan ke log data lokal (Development / Offline Mode)
    if (empty($smtp_host)) {
        return log_local_email($to_email, $to_name, $subject, $html_body, 'SMTP_HOST belum dikonfigurasi di .env');
    }

    try {
        $result = send_via_native_smtp(
            $smtp_host,
            $smtp_port,
            $smtp_user,
            $smtp_pass,
            $smtp_secure,
            $smtp_from,
            $smtp_from_name,
            $to_email,
            $to_name,
            $subject,
            $html_body,
            $text_body
        );

        if ($result['success']) {
            return [
                'success' => true,
                'mode'    => 'smtp',
                'message' => 'Email verifikasi berhasil dikirim ke ' . $to_email,
            ];
        }

        // Jika koneksi SMTP gagal (misal port ditutup ISP / kredensial salah di localhost)
        // Catat ke file log agar alur user tidak mandek di tengah jalan
        log_local_email($to_email, $to_name, $subject, $html_body, 'Fallback karena error SMTP: ' . ($result['error'] ?? ''));
        return [
            'success' => true,
            'mode'    => 'fallback_log',
            'message' => 'Email verifikasi dicatat di log lokal (Koneksi SMTP lokal gagal: ' . ($result['error'] ?? '') . ')',
            'error'   => $result['error'] ?? null,
        ];
    } catch (Throwable $e) {
        log_local_email($to_email, $to_name, $subject, $html_body, 'Exception: ' . $e->getMessage());
        return [
            'success' => true,
            'mode'    => 'fallback_log',
            'message' => 'Email verifikasi dicatat di log lokal',
            'error'   => $e->getMessage(),
        ];
    }
}

/**
 * Socket client SMTP native murni (RFC 5321)
 */
function send_via_native_smtp(
    $host,
    $port,
    $user,
    $pass,
    $secure,
    $from_email,
    $from_name,
    $to_email,
    $to_name,
    $subject,
    $html_body,
    $text_body = ''
) {
    // Penyesuaian otomatis untuk Gmail
    if (stripos($host, 'gmail.com') !== false) {
        $pass = str_replace(' ', '', $pass); // Hapus spasi dari App Password Google 16 karakter
        if (stripos($from_email, 'skillbridge.id') !== false && !empty($user)) {
            $from_email = $user; // Gmail mewajibkan sender sama dengan akun login
        }
    }

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ]);

    $socket = @stream_socket_client($remote, $errno, $errstr, 8, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        return ['success' => false, 'error' => "Koneksi ke $remote gagal: $errstr ($errno)"];
    }

    stream_set_timeout($socket, 8);

    $read_resp = function ($expected_code = null) use ($socket) {
        $response = '';
        while (!feof($socket)) {
            $line = fgets($socket, 512);
            if ($line === false) break;
            $response .= $line;
            if (preg_match('/^[0-9]{3}[ -]/', $line)) {
                if (substr($line, 3, 1) === ' ') break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if ($expected_code !== null && $code !== $expected_code) {
            throw new Exception("SMTP Error: ekspektasi $expected_code, didapat: $response");
        }
        return $response;
    };

    $send_cmd = function ($cmd, $expected_code = null) use ($socket, $read_resp) {
        fwrite($socket, $cmd . "\r\n");
        return $read_resp($expected_code);
    };

    try {
        $read_resp(220); // Banner

        $hostname = gethostname() ?: 'localhost';
        $send_cmd("EHLO $hostname", 250);

        // STARTTLS jika port 587 atau mode tls
        if ($secure === 'tls' || ($port === 587 && $secure !== 'ssl')) {
            $send_cmd("STARTTLS", 220);
            $crypto_ok = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto_ok) {
                throw new Exception("Handshake TLS gagal pada host $host");
            }
            $send_cmd("EHLO $hostname", 250);
        }

        // Autentikasi jika username diberikan
        if (!empty($user)) {
            $send_cmd("AUTH LOGIN", 334);
            $send_cmd(base64_encode($user), 334);
            $send_cmd(base64_encode($pass), 235);
        }

        $send_cmd("MAIL FROM:<$from_email>", 250);
        $send_cmd("RCPT TO:<$to_email>", 250);
        $send_cmd("DATA", 354);

        $boundary = '=_sb_' . md5(uniqid((string) time(), true));

        $headers = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'From: =?UTF-8?B?' . base64_encode($from_name) . '?= <' . $from_email . '>';
        $headers[] = 'To: =?UTF-8?B?' . base64_encode($to_name) . '?= <' . $to_email . '>';
        $headers[] = 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $headers[] = 'X-Mailer: SkillBridge Native Mailer';

        $plain = empty($text_body) ? strip_tags(str_replace(['<br>', '<p>'], ["\n", "\n\n"], $html_body)) : $text_body;

        $body_content = implode("\r\n", $headers) . "\r\n\r\n";
        $body_content .= "--" . $boundary . "\r\n";
        $body_content .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body_content .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body_content .= chunk_split(base64_encode($plain)) . "\r\n";

        $body_content .= "--" . $boundary . "\r\n";
        $body_content .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body_content .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body_content .= chunk_split(base64_encode($html_body)) . "\r\n";

        $body_content .= "--" . $boundary . "--\r\n";
        $body_content .= ".";

        $send_cmd($body_content, 250);
        $send_cmd("QUIT", 221);
        fclose($socket);

        return ['success' => true];
    } catch (Throwable $e) {
        @fclose($socket);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Mencatat isi email ke database dan file log temp untuk kemudahan pengujian di lokal/dev
 */
function log_local_email($to_email, $to_name, $subject, $html_body, $note = '') {
    // 1. Simpan ke database jika koneksi tersedia
    try {
        $pdo = get_db_connection();
        if ($pdo) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS mail_logs (
                    id SERIAL PRIMARY KEY,
                    to_email VARCHAR(255) NOT NULL,
                    to_name VARCHAR(255),
                    subject VARCHAR(255) NOT NULL,
                    body_html TEXT,
                    note TEXT,
                    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $stmt = $pdo->prepare("
                INSERT INTO mail_logs (to_email, to_name, subject, body_html, note)
                VALUES (:to_email, :to_name, :subject, :body, :note)
            ");
            $stmt->execute([
                ':to_email' => $to_email,
                ':to_name'  => $to_name,
                ':subject'  => $subject,
                ':body'     => $html_body,
                ':note'     => $note,
            ]);
        }
    } catch (Throwable $e) {
        error_log('Gagal menyimpan mail_log ke DB: ' . $e->getMessage());
    }

    // 2. Simpan juga ke file temp sistem
    $temp_file = sys_get_temp_dir() . '/skillbridge_mail_logs.json';
    try {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to'        => $to_email,
            'to_name'   => $to_name,
            'subject'   => $subject,
            'note'      => $note,
            'body_html' => $html_body,
        ];
        $logs = [];
        if (file_exists($temp_file)) {
            $existing = @file_get_contents($temp_file);
            $logs = @json_decode($existing, true) ?: [];
        }
        array_unshift($logs, $entry);
        if (count($logs) > 30) {
            $logs = array_slice($logs, 0, 30);
        }
        @file_put_contents($temp_file, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    } catch (Throwable $e) {
        // ignore
    }

    return [
        'success'   => true,
        'mode'      => 'log',
        'message'   => 'Email verifikasi dicatat di log lokal (DB table: mail_logs)',
        'log_file'  => $temp_file,
    ];
}
