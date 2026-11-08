<?php
// public/api/hr/company.php
// GET: Ambil profil perusahaan + metrik rekrutmen (HR read-only).
require_once __DIR__ . '/../../../includes/auth/auth.php';
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

if ($method !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

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
            // HR tanpa perusahaan tetap read-only; tidak ada auto-provision.
            echo json_encode([
                'company' => null,
                'metrics' => null
            ]);
            exit;
        }

        $company_id = (int)$recruiter['company_id'];

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

} catch (Exception $e) {
    error_log('API hr/company.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
