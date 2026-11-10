<?php
// public/api/hr/candidates.php
// GET: Ambil list kandidat yang apply ke lowongan perusahaan HR
// POST: Update status kandidat di pipeline
require_once __DIR__ . '/../../../includes/auth/auth.php';
require_once __DIR__ . '/../../../includes/config/database.php';
require_once __DIR__ . '/../../../includes/config/app.php';

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
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = get_db_connection();

        if (!$pdo) {
            http_response_code(503);
            echo json_encode(['error' => 'Database unavailable']);
            exit;
        }

        $user_id = $user['id'];

        // Get company_id for this HR user
        $stmt = $pdo->prepare('SELECT company_id FROM company_recruiters WHERE user_id = :user_id LIMIT 1');
        $stmt->execute([':user_id' => $user_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$company) {
            http_response_code(404);
            echo json_encode(['error' => 'Company not found for this HR user']);
            exit;
        }

        $company_id = (int)$company['company_id'];

        // Build query with optional filters
        $status_filter = isset($_GET['status']) && $_GET['status'] !== 'all' ? $_GET['status'] : null;
        $vacancy_filter = isset($_GET['vacancy_id']) ? (int)$_GET['vacancy_id'] : null;

        $sql = '
            SELECT
                cp.id,
                cp.id AS pipeline_id,
                cp.status,
                cp.notes,
                cp.created_at,
                cp.updated_at,
                u.id AS user_id,
                u.name AS candidate_name,
                u.email AS candidate_email,
                sp.target_career_id,
                sp.public_token,
                sp.is_public,
                c.name AS target_career_name,
                jv.id AS vacancy_id,
                jv.title AS vacancy_title,
                jv.career_id AS vacancy_career_id
            FROM candidate_pipelines cp
            JOIN users u ON u.id = cp.user_id
            LEFT JOIN student_profiles sp ON sp.user_id = cp.user_id
            LEFT JOIN careers c ON c.id = sp.target_career_id
            LEFT JOIN job_vacancies jv ON jv.id = cp.vacancy_id
            WHERE cp.company_id = :company_id
        ';

        $params = [':company_id' => $company_id];

        if ($status_filter) {
            $sql .= ' AND cp.status = :status';
            $params[':status'] = $status_filter;
        }

        if ($vacancy_filter) {
            $sql .= ' AND cp.vacancy_id = :vacancy_id';
            $params[':vacancy_id'] = $vacancy_filter;
        }

        $sql .= ' ORDER BY cp.updated_at DESC, cp.created_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($candidates as $candidate) {
            $candidate_user_id = (int)$candidate['user_id'];

            // Get candidate confirmed skills
            $stmtSkills = $pdo->prepare('
                SELECT s.id, s.name, s.category
                FROM user_skills us
                JOIN skills s ON s.id = us.skill_id
                WHERE us.user_id = :user_id AND us.status = :status
                ORDER BY s.name
            ');
            $stmtSkills->execute([':user_id' => $candidate_user_id, ':status' => 'confirmed']);
            $skills = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);

            // Get required skills for career benchmark
            $career_id = $candidate['vacancy_career_id'] ?: $candidate['target_career_id'];
            $required_skills = [];
            $skills_owned_count = 0;
            $skills_total_count = 0;
            $tier = 3;
            $tier_label = 'Perlu Pengembangan';

            if ($career_id) {
                $stmtReq = $pdo->prepare('
                    SELECT s.id, s.name
                    FROM career_skill_requirements csr
                    JOIN skills s ON s.id = csr.skill_id
                    WHERE csr.career_id = :career_id
                    ORDER BY s.name
                ');
                $stmtReq->execute([':career_id' => $career_id]);
                $required_skills = $stmtReq->fetchAll(PDO::FETCH_ASSOC);

                $skills_total_count = count($required_skills);
                $user_skill_ids = array_column($skills, 'id');
                $skills_owned_count = count(array_filter($required_skills, function($rs) use ($user_skill_ids) {
                    return in_array($rs['id'], $user_skill_ids);
                }));

                // Calculate tier
                $match_ratio = $skills_total_count > 0 ? ($skills_owned_count / $skills_total_count) : 0;
                if ($match_ratio >= 0.7) {
                    $tier = 1;
                    $tier_label = 'Sangat Sesuai';
                } elseif ($match_ratio >= 0.4) {
                    $tier = 2;
                    $tier_label = 'Cocok Magang/Intern';
                }
            }

            // Count evidence
            $stmtEv = $pdo->prepare('SELECT COUNT(*) FROM evidence WHERE user_id = :user_id');
            $stmtEv->execute([':user_id' => $candidate_user_id]);
            $evidence_count = (int)$stmtEv->fetchColumn();

            // Portfolio public URL
            $portfolio_url = null;
            if ($candidate['public_token']) {
                $portfolio_url = base_url('p.php?token=' . $candidate['public_token']);
            }

            // Provide both flattened fields and nested objects for 100% frontend compatibility
            $result[] = [
                'id' => (int)$candidate['id'],
                'pipeline_id' => (int)$candidate['id'],
                'user_id' => $candidate_user_id,
                'name' => $candidate['candidate_name'],
                'email' => $candidate['candidate_email'],
                'target_role' => $candidate['target_career_name'] ?: 'Belum Ditentukan',
                'target_career_name' => $candidate['target_career_name'] ?: 'Belum Ditentukan',
                'target_career_id' => $candidate['target_career_id'] ? (int)$candidate['target_career_id'] : null,
                'vacancy_id' => $candidate['vacancy_id'] ? (int)$candidate['vacancy_id'] : null,
                'vacancy_title' => $candidate['vacancy_title'] ?: '',
                'status' => $candidate['status'] ?: 'New',
                'notes' => $candidate['notes'] ?: '',
                'tier' => $tier,
                'tier_label' => $tier_label,
                'tier_description' => "Memenuhi {$skills_owned_count} dari {$skills_total_count} skill",
                'skills' => array_map(function($s) {
                    return [
                        'id' => (int)$s['id'],
                        'name' => $s['name'],
                        'category' => $s['category']
                    ];
                }, $skills),
                'skills_matched' => $skills_owned_count,
                'skills_total' => $skills_total_count,
                'skills_owned_count' => $skills_owned_count,
                'skills_total_count' => $skills_total_count,
                'evidence_count' => $evidence_count,
                'public_token' => $candidate['public_token'],
                'portfolio_url' => $portfolio_url,
                'pipeline_updated' => $candidate['updated_at'] ?: $candidate['created_at'],
                'created_at' => $candidate['created_at'],
                'updated_at' => $candidate['updated_at'],
                // Nested structures
                'candidate' => [
                    'user_id' => $candidate_user_id,
                    'name' => $candidate['candidate_name'],
                    'email' => $candidate['candidate_email'],
                    'target_career_id' => $candidate['target_career_id'] ? (int)$candidate['target_career_id'] : null,
                    'target_career_name' => $candidate['target_career_name'],
                    'public_token' => $candidate['public_token'],
                    'is_public' => (bool)$candidate['is_public'],
                    'portfolio_url' => $portfolio_url
                ],
                'vacancy' => [
                    'id' => $candidate['vacancy_id'] ? (int)$candidate['vacancy_id'] : null,
                    'title' => $candidate['vacancy_title'],
                    'career_id' => $candidate['vacancy_career_id'] ? (int)$candidate['vacancy_career_id'] : null
                ]
            ];
        }

        echo json_encode($result, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        error_log('API hr/candidates.php GET error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
    exit;
}

// POST: Update candidate status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../../includes/auth/csrf.php';

    if (!csrf_verify()) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }

    try {
        $pdo = get_db_connection();

        if (!$pdo) {
            http_response_code(503);
            echo json_encode(['error' => 'Database unavailable']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $pipeline_id = isset($input['pipeline_id']) ? (int)$input['pipeline_id'] : null;
        $candidate_user_id = isset($input['user_id']) ? (int)$input['user_id'] : null;
        $vacancy_id = isset($input['vacancy_id']) ? (int)$input['vacancy_id'] : null;
        $new_status = trim($input['status'] ?? '');
        $notes = isset($input['notes']) ? trim($input['notes']) : null;

        if (!$new_status) {
            http_response_code(400);
            echo json_encode(['error' => 'Status is required']);
            exit;
        }

        // Verify HR company
        $user_id = $user['id'];
        $stmt = $pdo->prepare('SELECT company_id FROM company_recruiters WHERE user_id = :user_id LIMIT 1');
        $stmt->execute([':user_id' => $user_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$company) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        $company_id = (int)$company['company_id'];

        // If pipeline_id not explicitly sent, resolve by user_id and vacancy_id
        if (!$pipeline_id && $candidate_user_id) {
            if ($vacancy_id) {
                $stmtFind = $pdo->prepare('SELECT id FROM candidate_pipelines WHERE company_id = :cid AND user_id = :uid AND vacancy_id = :vid LIMIT 1');
                $stmtFind->execute([':cid' => $company_id, ':uid' => $candidate_user_id, ':vid' => $vacancy_id]);
            } else {
                $stmtFind = $pdo->prepare('SELECT id FROM candidate_pipelines WHERE company_id = :cid AND user_id = :uid LIMIT 1');
                $stmtFind->execute([':cid' => $company_id, ':uid' => $candidate_user_id]);
            }
            $pipeline_id = $stmtFind->fetchColumn();
        }

        if (!$pipeline_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Pipeline record not found']);
            exit;
        }

        // Update status in candidate_pipelines
        $stmt = $pdo->prepare('
            UPDATE candidate_pipelines
            SET status = :status,
                notes = COALESCE(:notes, notes),
                updated_by = :updated_by,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :pipeline_id AND company_id = :company_id
            RETURNING user_id, vacancy_id
        ');
        $stmt->execute([
            ':status' => $new_status,
            ':notes' => $notes,
            ':updated_by' => $user_id,
            ':pipeline_id' => $pipeline_id,
            ':company_id' => $company_id
        ]);

        $updatedRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$updatedRow) {
            http_response_code(404);
            echo json_encode(['error' => 'Pipeline not found or unauthorized']);
            exit;
        }

        // Get candidate name for logging
        $targetUserId = (int)$updatedRow['user_id'];
        $stmtName = $pdo->prepare('SELECT name FROM users WHERE id = :uid');
        $stmtName->execute([':uid' => $targetUserId]);
        $candidateName = $stmtName->fetchColumn() ?: 'Kandidat';

        // Log into recruiter_activity_logs
        try {
            $stmtLog = $pdo->prepare('
                INSERT INTO recruiter_activity_logs (company_id, user_id, action_type, target_type, target_id, description)
                VALUES (:company_id, :user_id, :action_type, :target_type, :target_id, :description)
            ');
            $stmtLog->execute([
                ':company_id' => $company_id,
                ':user_id' => $user_id,
                ':action_type' => 'status_update',
                ':target_type' => 'candidate',
                ':target_id' => $targetUserId,
                ':description' => "Memperbarui status {$candidateName} menjadi {$new_status}"
            ]);
        } catch (Exception $logEx) {
            error_log('Failed to write activity log: ' . $logEx->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'Status kandidat berhasil diperbarui',
            'pipeline_id' => $pipeline_id,
            'new_status' => $new_status
        ]);

    } catch (Exception $e) {
        error_log('API hr/candidates.php POST error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
