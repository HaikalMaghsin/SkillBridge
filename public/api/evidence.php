<?php
// public/api/evidence.php
// GET: Ambil evidence milik user + skill terkait
// POST: Simpan evidence baru + relasi skill dalam transaksi (dukung multipart file upload & Cloudflare R2)
// PUT: Update data evidence dan skill terkait (dukung multipart file upload & Cloudflare R2)
// DELETE: Hapus evidence
require_once __DIR__ . '/../../includes/auth/auth.php';
require_once __DIR__ . '/../../includes/auth/csrf.php';
require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/config/r2.php';

header('Content-Type: application/json; charset=utf-8');

// Wajib login
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = current_user();
$user_id = (int)$user['id'];

// Hanya mahasiswa yang boleh akses endpoint ini
if ($user['role'] !== 'mahasiswa') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Deteksi HTTP method (termasuk method spoofing via POST + _method / X-HTTP-Method-Override)
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    if (isset($_POST['_method']) && strtoupper($_POST['_method']) === 'PUT') {
        $method = 'PUT';
    } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) && strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) === 'PUT') {
        $method = 'PUT';
    }
}

/**
 * Parser multipart/form-data untuk HTTP method PUT (karena PHP native hanya mem-parse POST)
 */
function parse_raw_multipart(&$post, &$files): void {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return;
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (!preg_match('/boundary=([^;]+)/i', $contentType, $matches)) {
        return;
    }
    $boundary = trim($matches[1], '"\' ');
    $parts = explode('--' . $boundary, $raw);

    foreach ($parts as $part) {
        $part = ltrim($part, "\r\n");
        if ($part === '' || $part === "--\r\n" || $part === '--') {
            continue;
        }

        $pos = strpos($part, "\r\n\r\n");
        if ($pos === false) {
            continue;
        }

        $headersStr = substr($part, 0, $pos);
        $body = substr($part, $pos + 4);
        if (substr($body, -2) === "\r\n") {
            $body = substr($body, 0, -2);
        }

        if (preg_match('/name="([^"]+)"(?:;\s*filename="([^"]+)")?/i', $headersStr, $m)) {
            $name = $m[1];
            $filename = $m[2] ?? null;

            if ($filename !== null) {
                preg_match('/Content-Type:\s*([^\r\n]+)/i', $headersStr, $cm);
                $mime = trim($cm[1] ?? 'application/octet-stream');

                $tmpFile = tempnam(sys_get_temp_dir(), 'ev_put_');
                file_put_contents($tmpFile, $body);

                $files[$name] = [
                    'name'     => $filename,
                    'type'     => $mime,
                    'tmp_name' => $tmpFile,
                    'error'    => UPLOAD_ERR_OK,
                    'size'     => strlen($body),
                ];
            } else {
                $post[$name] = $body;
            }
        }
    }
}

/**
 * Ambil data request baik dari multipart/form-data maupun JSON
 */
function get_request_data(string $httpMethod): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $isMultipart = (stripos($contentType, 'multipart/form-data') !== false) || !empty($_FILES);

    if ($isMultipart) {
        $post = $_POST;
        $files = $_FILES;

        if ($httpMethod === 'PUT' && empty($post) && empty($files)) {
            parse_raw_multipart($post, $files);
        }

        return [$post, $files];
    }

    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true) ?: [];
    return [$json, []];
}

/**
 * Validasi dan upload file fisik / base64 ke Cloudflare R2
 * Mengembalikan array detail upload atau null jika tidak ada file yang dikirim.
 * Menghentikan eksekusi dengan HTTP 400 / 502 jika ada kesalahan validasi atau upload R2 gagal.
 */
function handle_evidence_file_upload(array $input, array $files, int $userId, string $sourceType): ?array {
    $hasMultipartFile = !empty($files['attachment_file']) &&
                        is_array($files['attachment_file']) &&
                        ($files['attachment_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    $hasBase64File = !empty($input['file_base64']);

    if (!$hasMultipartFile && !$hasBase64File) {
        return null;
    }

    $rawFileName = '';
    $binaryBytes = '';
    $mimeType = '';

    if ($hasMultipartFile) {
        $file = $files['attachment_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'Gagal mengunggah berkas (Kode error: ' . $file['error'] . ')']);
            exit;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'Ukuran berkas maksimal 5 MB']);
            exit;
        }

        $rawFileName = $file['name'];
        $binaryBytes = file_get_contents($file['tmp_name']);
        if ($binaryBytes === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Gagal membaca isi berkas yang diunggah']);
            exit;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file['tmp_name']);
        $mimeType = $detectedMime ?: ($file['type'] ?? 'application/octet-stream');
    } else {
        // Base64 upload
        $dataUri = $input['file_base64'];
        $rawFileName = $input['file_name'] ?? 'evidence.pdf';

        if (preg_match('#^data:([^;]+);base64,(.+)$#s', $dataUri, $matches)) {
            $mimeType = $matches[1];
            $binaryBytes = base64_decode($matches[2]);
        } else {
            $binaryBytes = base64_decode($dataUri);
        }

        if ($binaryBytes === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Format file_base64 tidak valid']);
            exit;
        }

        if (strlen($binaryBytes) > 5 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'Ukuran berkas maksimal 5 MB']);
            exit;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->buffer($binaryBytes);
        if ($detectedMime) {
            $mimeType = $detectedMime;
        }
    }

    $ext = strtolower(pathinfo($rawFileName, PATHINFO_EXTENSION) ?: '');
    if ($mimeType === 'application/octet-stream' || empty($mimeType)) {
        $mimeMap = [
            'pdf'  => 'application/pdf',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
        ];
        if (isset($mimeMap[$ext])) {
            $mimeType = $mimeMap[$ext];
        }
    }

    // Validasi format file:
    // source_type === 'cv' -> hanya PDF
    // certificate / assessment / lainnya -> PDF, PNG, JPG, JPEG, WEBP
    if ($sourceType === 'cv') {
        $allowedMimes = ['application/pdf'];
        if (!in_array($mimeType, $allowedMimes, true) || $ext !== 'pdf') {
            http_response_code(400);
            echo json_encode(['error' => 'Untuk dokumen CV, hanya berkas format PDF (.pdf) yang diperbolehkan']);
            exit;
        }
    } else {
        $allowedMimes = ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'];
        $allowedExts = ['pdf', 'png', 'jpg', 'jpeg', 'webp'];
        if (!in_array($mimeType, $allowedMimes, true) && !in_array($ext, $allowedExts, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Format berkas tidak didukung. Harap unggah berkas .pdf, .png, .jpg, .jpeg, atau .webp']);
            exit;
        }
    }

    // Validasi konfigurasi Cloudflare R2
    if (!r2_is_configured()) {
        http_response_code(502);
        echo json_encode(['error' => 'Layanan penyimpanan Cloudflare R2 belum dikonfigurasi di server']);
        exit;
    }

    $safeFilename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($rawFileName));
    if (empty($safeFilename) || $safeFilename === '.' || $safeFilename === '..') {
        $safeFilename = 'file_' . time() . ($ext ? ('.' . $ext) : '');
    }

    $objectKey = "evidence/user_{$userId}/" . time() . "_" . $safeFilename;
    $uploadRes = r2_upload_bytes($objectKey, $binaryBytes, $mimeType);

    if (empty($uploadRes['ok']) || ($uploadRes['storage'] ?? '') !== 'r2' || empty($uploadRes['url'])) {
        $errMsg = $uploadRes['error'] ?? 'Gagal mengunggah berkas ke Cloudflare R2';
        http_response_code(502);
        echo json_encode(['error' => "Gagal mengunggah ke Cloudflare R2: {$errMsg}"]);
        exit;
    }

    return [
        'url'           => $uploadRes['url'],
        'safe_filename' => $safeFilename,
        'storage'       => 'r2',
        'r2_key'        => $objectKey,
    ];
}

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    // GET: Ambil semua evidence milik user
    if ($method === 'GET') {
        $stmt = $pdo->prepare('
            SELECT
                e.id,
                e.source_type,
                e.title,
                e.file_url,
                e.metadata,
                e.created_at
            FROM evidence e
            WHERE e.user_id = :user_id
            ORDER BY e.created_at DESC
        ');
        $stmt->execute([':user_id' => $user_id]);
        $evidence = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ambil skill terkait untuk setiap evidence
        foreach ($evidence as &$ev) {
            $ev['id'] = (int)$ev['id'];
            $ev['metadata'] = json_decode($ev['metadata'], true) ?: [];

            // Ambil skill_ids dari evidence_skills
            $stmtSkills = $pdo->prepare('
                SELECT skill_id
                FROM evidence_skills
                WHERE evidence_id = :evidence_id
            ');
            $stmtSkills->execute([':evidence_id' => $ev['id']]);
            $skillRows = $stmtSkills->fetchAll(PDO::FETCH_COLUMN);
            $ev['skill_ids'] = array_map('intval', $skillRows);

            // Tambahkan description dari metadata jika ada
            $ev['description'] = $ev['metadata']['description'] ?? '';
        }

        echo json_encode($evidence, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // POST: Tambah evidence baru
    if ($method === 'POST') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        [$input, $files] = get_request_data('POST');

        $source_type = $input['source_type'] ?? null;
        $title = $input['title'] ?? null;
        $description = $input['description'] ?? '';
        $file_url = $input['file_url'] ?? null;
        $metadata = $input['metadata'] ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?: [];
        }

        $skill_ids = $input['skill_ids'] ?? [];
        if (is_string($skill_ids)) {
            $decoded = json_decode($skill_ids, true);
            if (is_array($decoded)) {
                $skill_ids = $decoded;
            } else {
                $skill_ids = array_filter(array_map('trim', explode(',', $skill_ids)));
            }
        }
        $skill_ids = array_map('intval', (array)$skill_ids);

        // Jika skill_ids kosong tapi ada skill names di metadata, convert ke IDs
        if (empty($skill_ids) && !empty($metadata['skills']) && is_array($metadata['skills'])) {
            $skillNames = $metadata['skills'];
            $placeholders = str_repeat('?,', count($skillNames) - 1) . '?';
            $stmt = $pdo->prepare("SELECT id FROM skills WHERE name IN ($placeholders)");
            $stmt->execute($skillNames);
            $skill_ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }

        // Validasi input
        if (!$source_type || !$title) {
            http_response_code(400);
            echo json_encode(['error' => 'source_type and title are required']);
            exit;
        }

        // Validasi source_type enum
        $valid_types = ['cv', 'project', 'portfolio', 'certificate', 'github', 'assessment'];
        if (!in_array($source_type, $valid_types)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid source_type']);
            exit;
        }

        // Upload berkas fisik / base64 langsung ke Cloudflare R2
        $uploaded = handle_evidence_file_upload($input, $files, $user_id, $source_type);
        if ($uploaded !== null) {
            $file_url = $uploaded['url'];
            $metadata['storage'] = $uploaded['storage'];
            $metadata['r2_key'] = $uploaded['r2_key'];
            $metadata['fileName'] = $uploaded['safe_filename'];
        }

        // Tambahkan description, level, date ke metadata
        if (!isset($metadata['description'])) {
            $metadata['description'] = $description;
        }
        if (isset($input['level']) && !isset($metadata['level'])) {
            $metadata['level'] = $input['level'];
        }
        if (!isset($metadata['date'])) {
            $metadata['date'] = date('Y-m-d');
        }

        // Transaksi: insert evidence + evidence_skills
        $pdo->beginTransaction();

        try {
            // Insert evidence
            $stmt = $pdo->prepare('
                INSERT INTO evidence (user_id, source_type, title, file_url, metadata)
                VALUES (:user_id, :source_type, :title, :file_url, :metadata)
                RETURNING id
            ');
            $stmt->execute([
                ':user_id' => $user_id,
                ':source_type' => $source_type,
                ':title' => $title,
                ':file_url' => $file_url,
                ':metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE)
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $evidence_id = (int)$result['id'];

            // Insert evidence_skills untuk setiap skill_id
            if (!empty($skill_ids) && is_array($skill_ids)) {
                $stmtSkill = $pdo->prepare('
                    INSERT INTO evidence_skills (evidence_id, skill_id)
                    VALUES (:evidence_id, :skill_id)
                    ON CONFLICT DO NOTHING
                ');

                foreach ($skill_ids as $skill_id) {
                    $stmtSkill->execute([
                        ':evidence_id' => $evidence_id,
                        ':skill_id' => (int)$skill_id
                    ]);
                }
            }

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'id' => $evidence_id,
                'file_url' => $file_url,
                'message' => 'Evidence added'
            ]);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // PUT: Update evidence data & skill links
    if ($method === 'PUT') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        [$input, $files] = get_request_data('PUT');

        $evidence_id = isset($input['id']) ? (int)$input['id'] : null;
        $skill_ids = $input['skill_ids'] ?? [];
        if (is_string($skill_ids)) {
            $decoded = json_decode($skill_ids, true);
            if (is_array($decoded)) {
                $skill_ids = $decoded;
            } else {
                $skill_ids = array_filter(array_map('trim', explode(',', $skill_ids)));
            }
        }
        $skill_ids = array_map('intval', (array)$skill_ids);

        if (!$evidence_id) {
            http_response_code(400);
            echo json_encode(['error' => 'id required']);
            exit;
        }

        // Verify ownership
        $stmt = $pdo->prepare('SELECT user_id, metadata, title, source_type, file_url FROM evidence WHERE id = :evidence_id');
        $stmt->execute([':evidence_id' => $evidence_id]);
        $evidence = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$evidence || (int)$evidence['user_id'] !== $user_id) {
            http_response_code(404);
            echo json_encode(['error' => 'Evidence not found']);
            exit;
        }

        $source_type = $input['source_type'] ?? $evidence['source_type'] ?? 'evidence';

        // Upload berkas baru ke Cloudflare R2 jika disertakan saat edit
        $uploaded = handle_evidence_file_upload($input, $files, $user_id, $source_type);

        // Update evidence fields & skill links
        $pdo->beginTransaction();

        try {
            $currentMetadata = json_decode($evidence['metadata'] ?? '{}', true) ?: [];
            $newMetadata = isset($input['metadata'])
                ? (is_string($input['metadata']) ? (json_decode($input['metadata'], true) ?: []) : (array)$input['metadata'])
                : $currentMetadata;

            if (isset($input['description'])) {
                $newMetadata['description'] = $input['description'];
            }
            if (isset($input['level'])) {
                $newMetadata['level'] = $input['level'];
            }

            $updateCols = [];
            $params = [':evidence_id' => $evidence_id, ':user_id' => $user_id];

            if (isset($input['title']) && trim($input['title']) !== '') {
                $updateCols[] = 'title = :title';
                $params[':title'] = trim($input['title']);
            }
            if (isset($input['source_type'])) {
                $valid_types = ['cv', 'project', 'portfolio', 'certificate', 'github', 'assessment'];
                if (in_array($input['source_type'], $valid_types)) {
                    $updateCols[] = 'source_type = :source_type';
                    $params[':source_type'] = $input['source_type'];
                }
            }

            if ($uploaded !== null) {
                $updateCols[] = 'file_url = :file_url';
                $params[':file_url'] = $uploaded['url'];
                $newMetadata['storage'] = $uploaded['storage'];
                $newMetadata['r2_key'] = $uploaded['r2_key'];
                $newMetadata['fileName'] = $uploaded['safe_filename'];
            } elseif (array_key_exists('file_url', $input)) {
                $updateCols[] = 'file_url = :file_url';
                $params[':file_url'] = $input['file_url'];
            }

            $updateCols[] = 'metadata = :metadata';
            $params[':metadata'] = json_encode($newMetadata, JSON_UNESCAPED_UNICODE);

            if (!empty($updateCols)) {
                $sql = 'UPDATE evidence SET ' . implode(', ', $updateCols) . ' WHERE id = :evidence_id AND user_id = :user_id';
                $stmtUpdate = $pdo->prepare($sql);
                $stmtUpdate->execute($params);
            }

            // Update skill links if provided
            if (isset($input['skill_ids']) || !empty($newMetadata['skills'])) {
                if (empty($skill_ids) && !empty($newMetadata['skills']) && is_array($newMetadata['skills'])) {
                    $skillNames = $newMetadata['skills'];
                    $placeholders = str_repeat('?,', count($skillNames) - 1) . '?';
                    $stmtS = $pdo->prepare("SELECT id FROM skills WHERE name IN ($placeholders)");
                    $stmtS->execute($skillNames);
                    $skill_ids = array_map('intval', $stmtS->fetchAll(PDO::FETCH_COLUMN));
                }

                // Delete existing links
                $stmt = $pdo->prepare('DELETE FROM evidence_skills WHERE evidence_id = :evidence_id');
                $stmt->execute([':evidence_id' => $evidence_id]);

                // Insert new links
                if (!empty($skill_ids) && is_array($skill_ids)) {
                    $stmtInsert = $pdo->prepare('
                        INSERT INTO evidence_skills (evidence_id, skill_id)
                        VALUES (:evidence_id, :skill_id)
                        ON CONFLICT DO NOTHING
                    ');

                    foreach ($skill_ids as $skill_id) {
                        $stmtInsert->execute([
                            ':evidence_id' => $evidence_id,
                            ':skill_id' => (int)$skill_id
                        ]);
                    }
                }
            }

            $pdo->commit();

            echo json_encode([
                'success'  => true,
                'id'       => $evidence_id,
                'file_url' => $uploaded ? $uploaded['url'] : ($input['file_url'] ?? $evidence['file_url']),
                'message'  => 'Evidence updated successfully'
            ]);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // DELETE: Hapus evidence
    if ($method === 'DELETE') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $evidence_id = isset($input['id']) ? (int)$input['id'] : null;

        if (!$evidence_id) {
            http_response_code(400);
            echo json_encode(['error' => 'id required']);
            exit;
        }

        // Hapus evidence (CASCADE akan hapus evidence_skills otomatis)
        $stmt = $pdo->prepare('
            DELETE FROM evidence
            WHERE id = :evidence_id AND user_id = :user_id
        ');
        $stmt->execute([
            ':evidence_id' => $evidence_id,
            ':user_id' => $user_id
        ]);

        echo json_encode(['success' => true, 'message' => 'Evidence removed']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API evidence.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
