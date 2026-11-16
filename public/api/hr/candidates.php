<?php
// public/api/hr/candidates.php
// GET: Ambil list kandidat yang apply ke lowongan perusahaan HR
// POST: Update status kandidat di pipeline
require_once __DIR__ . '/../../../includes/auth/auth.php';
require_once __DIR__ . '/../../../includes/config/database.php';
require_once __DIR__ . '/../../../includes/config/app.php';
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
                cp.decision_payload,
                cp.candidate_response,
                cp.candidate_reply_note,
                cp.created_at,
                cp.updated_at,
                u.id AS user_id,
                u.name AS candidate_name,
                u.email AS candidate_email,
                u.avatar_url,
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

            $decPayload = $candidate['decision_payload'];
            if (is_string($decPayload)) {
                $decPayload = json_decode($decPayload, true) ?: [];
            } elseif (!is_array($decPayload)) {
                $decPayload = [];
            }

            // Provide both flattened fields and nested objects for 100% frontend compatibility
            $result[] = [
                'id' => (int)$candidate['id'],
                'pipeline_id' => (int)$candidate['id'],
                'user_id' => $candidate_user_id,
                'name' => $candidate['candidate_name'],
                'email' => $candidate['candidate_email'],
                'avatar_url' => r2_display_url($candidate['avatar_url'] ?? null),
                'target_role' => $candidate['target_career_name'] ?: 'Belum Ditentukan',
                'target_career_name' => $candidate['target_career_name'] ?: 'Belum Ditentukan',
                'target_career_id' => $candidate['target_career_id'] ? (int)$candidate['target_career_id'] : null,
                'vacancy_id' => $candidate['vacancy_id'] ? (int)$candidate['vacancy_id'] : null,
                'vacancy_title' => $candidate['vacancy_title'] ?: '',
                'status' => $candidate['status'] ?: 'New',
                'notes' => $candidate['notes'] ?: '',
                'decision_payload' => $decPayload,
                'candidate_response' => $candidate['candidate_response'] ?? 'pending',
                'candidate_reply_note' => $candidate['candidate_reply_note'] ?? '',
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
                    'avatar_url' => r2_display_url($candidate['avatar_url'] ?? null),
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
        $decision_payload = isset($input['decision_payload']) && is_array($input['decision_payload']) ? $input['decision_payload'] : [];

        if (!$new_status) {
            http_response_code(400);
            echo json_encode(['error' => 'Status is required']);
            exit;
        }

        // Validasi field wajib sesuai status keputusan HR
        if ($new_status === 'Shortlisted') {
            $reason = trim($decision_payload['reason'] ?? $input['reason'] ?? $notes ?? '');
            if (!$reason) {
                http_response_code(400);
                echo json_encode(['error' => 'Alasan shortlist wajib diisi.']);
                exit;
            }
            $decision_payload['reason'] = $reason;
            $decision_payload['next_step'] = trim($decision_payload['next_step'] ?? $input['next_step'] ?? '');
            if (!$notes) {
                $notes = "Shortlisted: {$reason}" . ($decision_payload['next_step'] ? " (Tahap berikutnya: {$decision_payload['next_step']})" : "");
            }
        } elseif ($new_status === 'Interview') {
            $mode = trim($decision_payload['interview_mode'] ?? $input['interview_mode'] ?? 'online');
            $date = trim($decision_payload['interview_date'] ?? $input['interview_date'] ?? '');
            $time = trim($decision_payload['interview_time'] ?? $input['interview_time'] ?? '');
            $interviewer = trim($decision_payload['interviewer_name'] ?? $input['interviewer_name'] ?? '');
            $message = trim($decision_payload['interview_message'] ?? $input['interview_message'] ?? '');

            if (!$date || !$time || !$interviewer || !$message) {
                http_response_code(400);
                echo json_encode(['error' => 'Tanggal, waktu, nama pewawancara, dan pesan persiapan interview wajib diisi.']);
                exit;
            }

            $decision_payload['interview_mode'] = $mode;
            $decision_payload['interview_date'] = $date;
            $decision_payload['interview_time'] = $time;
            $decision_payload['interviewer_name'] = $interviewer;
            $decision_payload['interview_message'] = $message;

            if ($mode === 'online') {
                $creator = trim($decision_payload['meeting_creator'] ?? $input['meeting_creator'] ?? 'hr_provided');
                $platform = trim($decision_payload['meeting_platform'] ?? $input['meeting_platform'] ?? 'Google Meet');
                $meetingUrl = trim($decision_payload['meeting_url'] ?? $input['meeting_url'] ?? '');

                if ($creator === 'hr_provided' && !$meetingUrl) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Tautan meeting wajib diisi jika link disediakan oleh HR.']);
                    exit;
                }

                $decision_payload['meeting_creator'] = $creator;
                $decision_payload['meeting_platform'] = $platform;
                $decision_payload['meeting_url'] = $meetingUrl;

                if (!$notes) {
                    $notes = "Undangan Interview Online ({$platform}): {$date} {$time} WIB bersama {$interviewer}." . ($meetingUrl ? " Link: {$meetingUrl}" : "");
                }
            } else {
                $address = trim($decision_payload['location_detail'] ?? $decision_payload['offline_address'] ?? $input['offline_address'] ?? '');
                $items = trim($decision_payload['offline_items'] ?? $input['offline_items'] ?? '');

                if (!$address) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Alamat lengkap lokasi interview wajib diisi.']);
                    exit;
                }

                $decision_payload['location_detail'] = $address;
                $decision_payload['offline_address'] = $address;
                $decision_payload['offline_items'] = $items;

                if (!$notes) {
                    $notes = "Undangan Interview Offline di {$address}: {$date} {$time} WIB bersama {$interviewer}.";
                }
            }
        } elseif ($new_status === 'Accepted') {
            $acceptMsg = trim($decision_payload['accept_message'] ?? $input['accept_message'] ?? '');
            $startDate = trim($decision_payload['start_date'] ?? $input['start_date'] ?? '');
            $contactInfo = trim($decision_payload['contact_info'] ?? $input['contact_info'] ?? '');

            if (!$acceptMsg || !$startDate || !$contactInfo) {
                http_response_code(400);
                echo json_encode(['error' => 'Pesan penerimaan, tanggal mulai onboarding, dan kontak HR wajib diisi.']);
                exit;
            }

            $decision_payload['accept_message'] = $acceptMsg;
            $decision_payload['start_date'] = $startDate;
            $decision_payload['contact_info'] = $contactInfo;

            if (!$notes) {
                $notes = "Diterima / Offering. Mulai: {$startDate}. Kontak: {$contactInfo}. {$acceptMsg}";
            }
        } elseif ($new_status === 'Rejected') {
            $rejectReason = trim($decision_payload['reject_reason'] ?? $input['reject_reason'] ?? '');
            $feedback = trim($decision_payload['feedback'] ?? $input['feedback'] ?? '');

            if (!$rejectReason || !$feedback) {
                http_response_code(400);
                echo json_encode(['error' => 'Kategori alasan dan saran evaluasi penolakan wajib diisi.']);
                exit;
            }

            $decision_payload['reject_reason'] = $rejectReason;
            $decision_payload['feedback'] = $feedback;

            if (!$notes) {
                $notes = "Evaluasi: {$rejectReason}. Feedback: {$feedback}";
            }
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

        // Update status in candidate_pipelines with structured decision_payload and reset student response
        $stmt = $pdo->prepare('
            UPDATE candidate_pipelines
            SET status = :status,
                notes = :notes,
                decision_payload = :decision_payload,
                candidate_response = \'pending\',
                candidate_reply_note = NULL,
                updated_by = :updated_by,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :pipeline_id AND company_id = :company_id
            RETURNING user_id, vacancy_id
        ');
        $stmt->execute([
            ':status'           => $new_status,
            ':notes'            => $notes,
            ':decision_payload' => json_encode($decision_payload, JSON_UNESCAPED_UNICODE),
            ':updated_by'       => $user_id,
            ':pipeline_id'      => $pipeline_id,
            ':company_id'       => $company_id
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
