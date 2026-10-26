<?php
// public/api/evidence.php
// GET: Ambil evidence milik user + skill terkait
// POST: Simpan evidence baru + relasi skill dalam transaksi
// DELETE: Hapus evidence
require_once __DIR__ . '/../../includes/auth/auth.php';
require_once __DIR__ . '/../../includes/auth/csrf.php';
require_once __DIR__ . '/../../includes/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Wajib login
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = current_user();
$user_id = $user['id'];

// Hanya mahasiswa yang boleh akses endpoint ini
if ($user['role'] !== 'mahasiswa') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

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
            $ev['metadata'] = json_decode($ev['metadata'], true);

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

        $input = json_decode(file_get_contents('php://input'), true);

        $source_type = $input['source_type'] ?? null;
        $title = $input['title'] ?? null;
        $description = $input['description'] ?? '';
        $file_url = $input['file_url'] ?? null;
        $metadata = $input['metadata'] ?? [];
        $skill_ids = $input['skill_ids'] ?? [];

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

        // Tambahkan description ke metadata
        if (!isset($metadata['description'])) {
            $metadata['description'] = $description;
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
                'message' => 'Evidence added'
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
