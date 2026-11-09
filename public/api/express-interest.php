<?php
// public/api/express-interest.php
// POST: Tandai minat mahasiswa ke lowongan perusahaan
// - Set student_profiles.is_public = TRUE
// - Insert ke candidate_pipelines
require_once __DIR__ . '/../../includes/auth/auth.php';
require_once __DIR__ . '/../../includes/auth/csrf.php';
require_once __DIR__ . '/../../includes/config/database.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!csrf_verify()) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$vacancy_id = isset($input['vacancy_id']) ? (int)$input['vacancy_id'] : null;

if (!$vacancy_id) {
    http_response_code(400);
    echo json_encode(['error' => 'vacancy_id required']);
    exit;
}

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    $user_id = $user['id'];

    // Validasi vacancy exists dan ambil company_id
    $stmt = $pdo->prepare('
        SELECT company_id, title
        FROM job_vacancies
        WHERE id = :vacancy_id AND status = :status
    ');
    $stmt->execute([':vacancy_id' => $vacancy_id, ':status' => 'active']);
    $vacancy = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vacancy) {
        http_response_code(404);
        echo json_encode(['error' => 'Vacancy not found or inactive']);
        exit;
    }

    $company_id = (int)$vacancy['company_id'];

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // 1. Set is_public = TRUE untuk student_profiles
        $stmt = $pdo->prepare('
            UPDATE student_profiles
            SET is_public = TRUE, updated_at = CURRENT_TIMESTAMP
            WHERE user_id = :user_id
        ');
        $stmt->execute([':user_id' => $user_id]);

        // Jika belum ada student_profiles, buat
        if ($stmt->rowCount() === 0) {
            $stmt = $pdo->prepare('
                INSERT INTO student_profiles (user_id, is_public, public_token)
                VALUES (:user_id, TRUE, gen_random_uuid())
            ');
            $stmt->execute([':user_id' => $user_id]);
        }

        // 2. Insert atau update candidate_pipelines
        $stmt = $pdo->prepare('
            INSERT INTO candidate_pipelines (company_id, vacancy_id, user_id, status, notes)
            VALUES (:company_id, :vacancy_id, :user_id, :status, :notes)
            ON CONFLICT (company_id, user_id, vacancy_id)
            DO UPDATE SET
                status = EXCLUDED.status,
                updated_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([
            ':company_id' => $company_id,
            ':vacancy_id' => $vacancy_id,
            ':user_id' => $user_id,
            ':status' => 'New',
            ':notes' => 'Student expressed interest via Career Target page'
        ]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Interest expressed successfully',
            'vacancy_title' => $vacancy['title']
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('API express-interest.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
