<?php
// public/api/pipeline-status.php
// GET: Ambil status pipeline kandidat untuk mahasiswa yang sedang login
// POST: Mahasiswa mengirim respons konfirmasi kehadiran / reschedule / terima tawaran ke HR
require_once __DIR__ . '/../../includes/auth/auth.php';
require_once __DIR__ . '/../../includes/auth/csrf.php';
require_once __DIR__ . '/../../includes/config/database.php';
require_once __DIR__ . '/../../includes/config/app.php';

header('Content-Type: application/json; charset=utf-8');

// Wajib login sebagai mahasiswa
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = current_user();
if ($user['role'] !== 'mahasiswa') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
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

    // GET: Ambil semua pipeline entries untuk mahasiswa ini
    if ($method === 'GET') {
        $stmt = $pdo->prepare('
            SELECT
                cp.id,
                cp.status,
                cp.notes,
                cp.decision_payload,
                cp.candidate_response,
                cp.candidate_reply_note,
                cp.created_at,
                cp.updated_at,
                c.name AS company_name,
                c.industry,
                jv.title AS vacancy_title,
                jv.positions
            FROM candidate_pipelines cp
            JOIN companies c ON c.id = cp.company_id
            LEFT JOIN job_vacancies jv ON jv.id = cp.vacancy_id
            WHERE cp.user_id = :user_id
            ORDER BY cp.updated_at DESC
        ');
        $stmt->execute([':user_id' => $user_id]);
        $pipelines = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = array_map(function($row) {
            $payload = $row['decision_payload'];
            if (is_string($payload)) {
                $payload = json_decode($payload, true) ?: [];
            } elseif (!is_array($payload)) {
                $payload = [];
            }

            return [
                'id'                   => (int)$row['id'],
                'status'               => $row['status'],
                'notes'                => $row['notes'],
                'decision_payload'     => $payload,
                'candidate_response'   => $row['candidate_response'] ?? 'pending',
                'candidate_reply_note' => $row['candidate_reply_note'] ?? '',
                'company_name'         => $row['company_name'],
                'industry'             => $row['industry'],
                'vacancy_title'        => $row['vacancy_title'],
                'positions'            => $row['positions'] ? (int)$row['positions'] : null,
                'created_at'           => $row['created_at'],
                'updated_at'           => $row['updated_at']
            ];
        }, $pipelines);

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // POST: Kirim respons mahasiswa (konfirmasi hadir / reschedule / terima offering)
    if ($method === 'POST') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $pipeline_id = isset($input['pipeline_id']) ? (int)$input['pipeline_id'] : null;
        $response = trim($input['response'] ?? '');
        $reply_note = isset($input['reply_note']) ? trim($input['reply_note']) : null;

        $allowedResponses = ['confirmed', 'reschedule_requested', 'offer_accepted'];
        if (!$pipeline_id || !in_array($response, $allowedResponses, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid pipeline_id and response are required']);
            exit;
        }

        $stmt = $pdo->prepare('
            UPDATE candidate_pipelines
            SET candidate_response = :response,
                candidate_reply_note = :reply_note,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :pipeline_id AND user_id = :user_id
            RETURNING id, company_id, status
        ');
        $stmt->execute([
            ':response'    => $response,
            ':reply_note'  => $reply_note,
            ':pipeline_id' => $pipeline_id,
            ':user_id'     => $user_id
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Pipeline record not found']);
            exit;
        }

        // Catat ke recruiter_activity_logs
        $company_id = (int)$row['company_id'];
        $studentName = $user['name'] ?? 'Mahasiswa';
        $desc = "Mahasiswa ({$studentName}) mengonfirmasi kehadiran interview.";
        if ($response === 'reschedule_requested') {
            $desc = "Mahasiswa ({$studentName}) mengajukan reschedule interview: " . ($reply_note ?: 'Tanpa catatan khusus');
        } elseif ($response === 'offer_accepted') {
            $desc = "Mahasiswa ({$studentName}) menerima tawaran (offering) perusahaan.";
        }

        try {
            $stmtLog = $pdo->prepare('
                INSERT INTO recruiter_activity_logs (company_id, user_id, action_type, target_type, target_id, description)
                VALUES (:company_id, :user_id, :action_type, :target_type, :target_id, :description)
            ');
            $stmtLog->execute([
                ':company_id'  => $company_id,
                ':user_id'     => $user_id,
                ':action_type' => 'candidate_' . $response,
                ':target_type' => 'candidate',
                ':target_id'   => $user_id,
                ':description' => $desc
            ]);
        } catch (Exception $logEx) {
            error_log('Failed to write activity log: ' . $logEx->getMessage());
        }

        echo json_encode([
            'success'              => true,
            'message'              => 'Respons Anda berhasil dikirim ke tim HR perusahaan.',
            'candidate_response'   => $response,
            'candidate_reply_note' => $reply_note
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('API pipeline-status.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
