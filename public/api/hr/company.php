<?php
// public/api/hr/company.php
// GET: Ambil profil perusahaan + metrik rekrutmen
// POST: Update profil perusahaan
require_once __DIR__ . '/../../../includes/auth/auth.php';
require_once __DIR__ . '/../../../includes/auth/csrf.php';
require_once __DIR__ . '/../../../includes/config/database.php';

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

$user_id = $user['id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    // GET: Ambil profil perusahaan + metrik
    if ($method === 'GET') {
        // Ambil company_id dari company_recruiters
        $stmt = $pdo->prepare('
            SELECT company_id
            FROM company_recruiters
            WHERE user_id = :user_id
            LIMIT 1
        ');
        $stmt->execute([':user_id' => $user_id]);
        $recruiter = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recruiter) {
            // AUTO-PROVISION: HR belum terhubung ke perusahaan, buat perusahaan default
            try {
                // Cek apakah ada perusahaan default
                $stmt = $pdo->query("SELECT id FROM companies WHERE name = 'Default Company' LIMIT 1");
                $default_company = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$default_company) {
                    // Buat perusahaan default
                    $stmt = $pdo->prepare('
                        INSERT INTO companies (name, industry, description)
                        VALUES (:name, :industry, :description)
                        RETURNING id
                    ');
                    $stmt->execute([
                        ':name' => 'Default Company',
                        ':industry' => 'General',
                        ':description' => 'Default company for new recruiters'
                    ]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $company_id = (int)$result['id'];
                } else {
                    $company_id = (int)$default_company['id'];
                }

                // Link HR user ke perusahaan default
                $stmt = $pdo->prepare('
                    INSERT INTO company_recruiters (company_id, user_id, role_title, status)
                    VALUES (:company_id, :user_id, :role_title, :status)
                ');
                $stmt->execute([
                    ':company_id' => $company_id,
                    ':user_id' => $user_id,
                    ':role_title' => 'Recruiter',
                    ':status' => 'active'
                ]);

                error_log("Auto-provisioned HR user {$user_id} to company {$company_id}");

            } catch (Exception $e) {
                // Jika gagal auto-provision, return null
                error_log('Auto-provision failed: ' . $e->getMessage());
                echo json_encode([
                    'company' => null,
                    'metrics' => null
                ]);
                exit;
            }
        } else {
            $company_id = (int)$recruiter['company_id'];
        }

        // Ambil profil perusahaan
        $stmt = $pdo->prepare('
            SELECT id, name, industry, website, description, created_at, updated_at
            FROM companies
            WHERE id = :company_id
        ');
        $stmt->execute([':company_id' => $company_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$company) {
            http_response_code(404);
            echo json_encode(['error' => 'Company not found']);
            exit;
        }

        // Hitung metrik rekrutmen
        // Total team size
        $stmt = $pdo->prepare('
            SELECT COUNT(*) as total
            FROM company_recruiters
            WHERE company_id = :company_id AND status = :status
        ');
        $stmt->execute([':company_id' => $company_id, ':status' => 'active']);
        $teamSize = (int)$stmt->fetchColumn();

        // Open roles (vacancies)
        $stmt = $pdo->prepare('
            SELECT COUNT(*) as total
            FROM job_vacancies
            WHERE company_id = :company_id AND status = :status
        ');
        $stmt->execute([':company_id' => $company_id, ':status' => 'active']);
        $openRoles = (int)$stmt->fetchColumn();

        // Total positions
        $stmt = $pdo->prepare('
            SELECT COALESCE(SUM(positions), 0) as total
            FROM job_vacancies
            WHERE company_id = :company_id AND status = :status
        ');
        $stmt->execute([':company_id' => $company_id, ':status' => 'active']);
        $totalPositions = (int)$stmt->fetchColumn();

        // Pipeline candidates
        $stmt = $pdo->prepare('
            SELECT COUNT(DISTINCT user_id) as total
            FROM candidate_pipelines
            WHERE company_id = :company_id
        ');
        $stmt->execute([':company_id' => $company_id]);
        $pipelineCandidates = (int)$stmt->fetchColumn();

        echo json_encode([
            'company' => [
                'id' => (int)$company['id'],
                'name' => $company['name'],
                'industry' => $company['industry'],
                'website' => $company['website'],
                'description' => $company['description'],
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

        // Ambil company_id
        $stmt = $pdo->prepare('
            SELECT company_id
            FROM company_recruiters
            WHERE user_id = :user_id
            LIMIT 1
        ');
        $stmt->execute([':user_id' => $user_id]);
        $recruiter = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recruiter) {
            http_response_code(404);
            echo json_encode(['error' => 'Company not found']);
            exit;
        }

        $company_id = (int)$recruiter['company_id'];
        $input = json_decode(file_get_contents('php://input'), true);

        $name = trim($input['name'] ?? '');
        $industry = trim($input['industry'] ?? '');
        $website = trim($input['website'] ?? '');
        $description = trim($input['description'] ?? '');

        if (!$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Company name is required']);
            exit;
        }

        // Update profil perusahaan
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

        echo json_encode([
            'success' => true,
            'message' => 'Company profile updated'
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API hr/company.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
