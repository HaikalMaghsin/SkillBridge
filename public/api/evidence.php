<?php
// public/api/evidence.php
// GET: Ambil evidence milik user + skill terkait
// POST: Simpan evidence baru + relasi skill dalam transaksi
// PUT: Update data evidence dan skill terkait
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

        $input = json_decode(file_get_contents('php://input'), true);

        $source_type = $input['source_type'] ?? null;
        $title = $input['title'] ?? null;
        $description = $input['description'] ?? '';
        $file_url = $input['file_url'] ?? null;
        $metadata = $input['metadata'] ?? [];
        $skill_ids = $input['skill_ids'] ?? [];

        // Jika skill_ids kosong tapi ada skill names di metadata, convert ke IDs
        if (empty($skill_ids) && !empty($metadata['skills']) && is_array($metadata['skills'])) {
            $skillNames = $metadata['skills'];
            $placeholders = str_repeat('?,', count($skillNames) - 1) . '?';
            $stmt = $pdo->prepare("SELECT id FROM skills WHERE name IN ($placeholders)");
            $stmt->execute($skillNames);
            $skill_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
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

    // PUT: Update evidence data & skill links
    if ($method === 'PUT') {
        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $evidence_id = isset($input['id']) ? (int)$input['id'] : null;
        $skill_ids = $input['skill_ids'] ?? [];

        if (!$evidence_id) {
            http_response_code(400);
            echo json_encode(['error' => 'id required']);
            exit;
        }

        // Verify ownership
        $stmt = $pdo->prepare('SELECT user_id, metadata, title, source_type, file_url FROM evidence WHERE id = :evidence_id');
        $stmt->execute([':evidence_id' => $evidence_id]);
        $evidence = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$evidence || $evidence['user_id'] != $user_id) {
            http_response_code(404);
            echo json_encode(['error' => 'Evidence not found']);
            exit;
        }

        // Update evidence fields & skill links
        $pdo->beginTransaction();

        try {
            $currentMetadata = json_decode($evidence['metadata'] ?? '{}', true) ?: [];
            $newMetadata = isset($input['metadata']) && is_array($input['metadata']) ? $input['metadata'] : $currentMetadata;
            if (isset($input['description'])) {
                $newMetadata['description'] = $input['description'];
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
            if (array_key_exists('file_url', $input)) {
                $updateCols[] = 'file_url = :file_url';
                $params[':file_url'] = $input['file_url'];
            }
            if (isset($input['metadata']) || isset($input['description'])) {
                $updateCols[] = 'metadata = :metadata';
                $params[':metadata'] = json_encode($newMetadata, JSON_UNESCAPED_UNICODE);
            }

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
                    $skill_ids = $stmtS->fetchAll(PDO::FETCH_COLUMN);
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
                'success' => true,
                'message' => 'Evidence updated successfully'
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
