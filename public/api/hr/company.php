<?php
// public/api/hr/company.php
// GET: Ambil profil perusahaan + metrik rekrutmen
// POST: Update profil perusahaan & unggah logo
require_once __DIR__ . '/../../../includes/auth/auth.php';
require_once __DIR__ . '/../../../includes/auth/csrf.php';
require_once __DIR__ . '/../../../includes/config/database.php';
require_once __DIR__ . '/../../../includes/config/r2.php';

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

    // Pastikan kolom logo_url selalu ada di tabel companies
    try {
        $pdo->exec("ALTER TABLE companies ADD COLUMN IF NOT EXISTS logo_url text;");
    } catch (Throwable $t) {
        // Abaikan jika user DB tidak memiliki hak DDL
    }

    // Helper untuk mengambil atau auto-provision perusahaan pengguna HR
    $getOrCreateCompanyId = function() use ($pdo, $user_id, $user) {
        $stmt = $pdo->prepare('
            SELECT company_id
            FROM company_recruiters
            WHERE user_id = :user_id
            LIMIT 1
        ');
        $stmt->execute([':user_id' => $user_id]);
        $recruiter = $stmt->fetch(PDO::FETCH_ASSOC);

        $company_id = null;
        if ($recruiter && !empty($recruiter['company_id'])) {
            $company_id = (int)$recruiter['company_id'];
            // Pastikan row companies benar-benar ada
            $chk = $pdo->prepare('SELECT id FROM companies WHERE id = :id');
            $chk->execute([':id' => $company_id]);
            if ($chk->fetch()) {
                return $company_id;
            }
        }

        // Jika belum ada, buat atau cari perusahaan yang cocok
        $compName = trim($user['company_name'] ?? '');
        if (!$compName) {
            $compName = 'PT ' . ($user['name'] ?? 'Mitra') . ' Indonesia';
        }

        // Cek jika perusahaan dengan nama ini sudah ada
        $stmtFind = $pdo->prepare('SELECT id FROM companies WHERE LOWER(name) = LOWER(:name) LIMIT 1');
        $stmtFind->execute([':name' => $compName]);
        $existing = $stmtFind->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $company_id = (int)$existing['id'];
        } else {
            $stmtNew = $pdo->prepare('
                INSERT INTO companies (name, industry, description)
                VALUES (:name, :industry, :description)
                RETURNING id
            ');
            $stmtNew->execute([
                ':name' => $compName,
                ':industry' => 'Teknologi Informasi & Software',
                ':description' => 'Informasi resmi perusahaan untuk mendukung proses rekrutmen.'
            ]);
            $company_id = (int)$stmtNew->fetchColumn();
        }

        // Hubungkan HR ke perusahaan
        $stmtLink = $pdo->prepare('
            INSERT INTO company_recruiters (company_id, user_id, role_title, status)
            VALUES (:company_id, :user_id, :role_title, :status)
            ON CONFLICT (company_id, user_id)
            DO UPDATE SET status = EXCLUDED.status, updated_at = CURRENT_TIMESTAMP
        ');
        $stmtLink->execute([
            ':company_id' => $company_id,
            ':user_id' => $user_id,
            ':role_title' => 'Lead Recruiter',
            ':status' => 'active'
        ]);

        return $company_id;
    };

    // GET: Ambil profil perusahaan + metrik
    if ($method === 'GET') {
        $company_id = $getOrCreateCompanyId();

        // Ambil profil perusahaan
        $company = null;
        try {
            $stmt = $pdo->prepare('
                SELECT id, name, industry, website, description, logo_url, created_at, updated_at
                FROM companies
                WHERE id = :company_id
            ');
            $stmt->execute([':company_id' => $company_id]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            // Fallback jika logo_url belum terbaca
            $stmt = $pdo->prepare('
                SELECT id, name, industry, website, description, created_at, updated_at
                FROM companies
                WHERE id = :company_id
            ');
            $stmt->execute([':company_id' => $company_id]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($company) {
                $company['logo_url'] = null;
            }
        }

        if (!$company) {
            http_response_code(404);
            echo json_encode(['error' => 'Profil perusahaan tidak ditemukan']);
            exit;
        }

        // Hitung metrik rekrutmen
        $teamSize = 1;
        $openRoles = 0;
        $totalPositions = 0;
        $pipelineCandidates = 0;

        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM company_recruiters WHERE company_id = :id AND status = :st');
            $stmt->execute([':id' => $company_id, ':st' => 'active']);
            $teamSize = (int)$stmt->fetchColumn();
        } catch (Throwable $e) {}

        try {
            $stmt = $pdo->prepare('SELECT COUNT(*), COALESCE(SUM(positions), 0) FROM job_vacancies WHERE company_id = :id AND status = :st');
            $stmt->execute([':id' => $company_id, ':st' => 'active']);
            $vRow = $stmt->fetch(PDO::FETCH_NUM);
            if ($vRow) {
                $openRoles = (int)$vRow[0];
                $totalPositions = (int)$vRow[1];
            }
        } catch (Throwable $e) {}

        try {
            $stmt = $pdo->prepare('SELECT COUNT(DISTINCT user_id) FROM candidate_pipelines WHERE company_id = :id');
            $stmt->execute([':id' => $company_id]);
            $pipelineCandidates = (int)$stmt->fetchColumn();
        } catch (Throwable $e) {}

        echo json_encode([
            'company' => [
                'id' => (int)$company['id'],
                'name' => $company['name'],
                'industry' => $company['industry'] ?? '',
                'website' => $company['website'] ?? '',
                'description' => $company['description'] ?? '',
                'logo_url' => r2_display_url($company['logo_url'] ?? null),
                'created_at' => $company['created_at'],
                'updated_at' => $company['updated_at']
            ],
            'metrics' => [
                'teamSize' => $teamSize,
                'activeRecruiters' => $teamSize,
                'openRoles' => $openRoles,
                'totalPositions' => $totalPositions,
                'pipelineCandidates' => $pipelineCandidates
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // POST: Update profil perusahaan
    if ($method === 'POST') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $company_id = $getOrCreateCompanyId();
        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        $name = trim($input['name'] ?? '');
        $industry = trim($input['industry'] ?? '');
        $website = trim($input['website'] ?? '');
        $description = trim($input['description'] ?? '');

        // Jika hanya upload logo tanpa kirim name, ambil name saat ini
        if (!$name) {
            $cur = $pdo->query("SELECT name, industry, website, description FROM companies WHERE id = {$company_id}")->fetch(PDO::FETCH_ASSOC);
            $name = $cur['name'] ?? 'Perusahaan Mitra';
            if (!$industry) $industry = $cur['industry'] ?? '';
            if (!$website) $website = $cur['website'] ?? '';
            if (!$description) $description = $cur['description'] ?? '';
        }

        // Cek jika ada unggahan logo perusahaan (base64)
        $logoUrl = null;
        if (!empty($input['logo_base64'])) {
            $raw = $input['logo_base64'];
            $contentType = 'image/png';
            if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-\.]+);base64,/', $raw, $matches)) {
                $contentType = $matches[1];
                $raw = substr($raw, strpos($raw, ',') + 1);
            }
            $binary = base64_decode($raw);
            if ($binary) {
                $ext = ($contentType === 'image/jpeg' || $contentType === 'image/jpg') ? 'jpg' : (($contentType === 'image/svg+xml') ? 'svg' : (($contentType === 'image/webp') ? 'webp' : 'png'));
                $filename = "comp_{$company_id}_" . time() . ".{$ext}";

                // 1. Simpan salinan lokal di public/uploads/company_logos/
                $localRelPath = 'uploads/company_logos/' . $filename;
                $localDiskPath = __DIR__ . '/../../../public/uploads/company_logos';
                if (!is_dir($localDiskPath)) {
                    @mkdir($localDiskPath, 0755, true);
                }
                @file_put_contents($localDiskPath . '/' . $filename, $binary);
                $localUrl = base_url($localRelPath);

                // 2. Unggah juga ke Cloudflare R2 jika tersedia
                $objectKey = "company_logos/" . $filename;
                $uploadRes = r2_upload_bytes($objectKey, $binary, $contentType);

                if (!empty($uploadRes['ok']) && !empty($uploadRes['url']) && strpos($uploadRes['url'], 'data:') !== 0) {
                    $logoUrl = $uploadRes['url'];
                } else {
                    $logoUrl = $localUrl;
                }
            }
        }

        if ($logoUrl) {
            $stmt = $pdo->prepare('
                UPDATE companies
                SET name = :name,
                    industry = :industry,
                    website = :website,
                    description = :description,
                    logo_url = :logo_url,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :company_id
            ');
            $stmt->execute([
                ':name' => $name,
                ':industry' => $industry,
                ':website' => $website,
                ':description' => $description,
                ':logo_url' => $logoUrl,
                ':company_id' => $company_id
            ]);
        } else {
            $stmt = $pdo->prepare('
                UPDATE companies
                SET name = :name,
                    industry = :industry,
                    website = :website,
                    description = :description,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :company_id
            ');
            $stmt->execute([
                ':name' => $name,
                ':industry' => $industry,
                ':website' => $website,
                ':description' => $description,
                ':company_id' => $company_id
            ]);
        }

        // Sinkronkan nama perusahaan di session aktif
        if (isset($_SESSION['user'])) {
            $_SESSION['user']['company_name'] = $name;
            $_SESSION['user']['company_id'] = $company_id;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Profil perusahaan berhasil diperbarui',
            'company' => [
                'name' => $name,
                'industry' => $industry,
                'website' => $website,
                'description' => $description,
                'logo_url' => $logoUrl ? r2_display_url($logoUrl) : null
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API hr/company.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
}
