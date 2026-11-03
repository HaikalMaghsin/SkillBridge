<?php
// Temporary debug version of evidence.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../includes/auth/auth.php';
require_once __DIR__ . '/../../includes/auth/csrf.php';
require_once __DIR__ . '/../../includes/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Log semua yang masuk
error_log("=== Evidence API Debug ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Input: " . file_get_contents('php://input'));

// Wajib login
if (!is_logged_in()) {
    error_log("ERROR: Not logged in");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = current_user();
$user_id = $user['id'];
error_log("User ID: $user_id, Role: " . $user['role']);

// Hanya mahasiswa yang boleh akses endpoint ini
if ($user['role'] !== 'mahasiswa') {
    error_log("ERROR: Forbidden - role is " . $user['role']);
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        error_log("ERROR: Database unavailable");
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }

    if ($method === 'POST') {
        error_log("POST: Processing add evidence");

        if (!csrf_verify()) {
            error_log("ERROR: CSRF verification failed");
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        error_log("Parsed input: " . json_encode($input));

        $source_type = $input['source_type'] ?? null;
        $title = $input['title'] ?? null;
        $description = $input['description'] ?? '';
        $file_url = $input['file_url'] ?? null;
        $metadata = $input['metadata'] ?? [];
        $skill_ids = $input['skill_ids'] ?? [];

        error_log("source_type: $source_type, title: $title");
        error_log("skill_ids: " . json_encode($skill_ids));

        // Validasi input
        if (!$source_type || !$title) {
            error_log("ERROR: Missing required fields");
            http_response_code(400);
            echo json_encode(['error' => 'source_type and title are required']);
            exit;
        }

        // Validasi source_type enum
        $valid_types = ['cv', 'project', 'portfolio', 'certificate', 'github', 'assessment'];
        if (!in_array($source_type, $valid_types)) {
            error_log("ERROR: Invalid source_type: $source_type");
            http_response_code(400);
            echo json_encode(['error' => 'Invalid source_type']);
            exit;
        }

        // Tambahkan description ke metadata
        if (!isset($metadata['description'])) {
            $metadata['description'] = $description;
        }

        error_log("Starting transaction");
        $pdo->beginTransaction();

        try {
            // Insert evidence
            $stmt = $pdo->prepare('
                INSERT INTO evidence (user_id, source_type, title, file_url, metadata)
                VALUES (:user_id, :source_type, :title, :file_url, :metadata)
                RETURNING id
            ');

            $metadataJson = json_encode($metadata, JSON_UNESCAPED_UNICODE);
            error_log("Metadata JSON: $metadataJson");

            $stmt->execute([
                ':user_id' => $user_id,
                ':source_type' => $source_type,
                ':title' => $title,
                ':file_url' => $file_url,
                ':metadata' => $metadataJson
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $evidence_id = (int)$result['id'];
            error_log("Evidence inserted with ID: $evidence_id");

            // Insert evidence_skills untuk setiap skill_id
            if (!empty($skill_ids) && is_array($skill_ids)) {
                error_log("Inserting " . count($skill_ids) . " skill relations");

                $stmtSkill = $pdo->prepare('
                    INSERT INTO evidence_skills (evidence_id, skill_id)
                    VALUES (:evidence_id, :skill_id)
                    ON CONFLICT DO NOTHING
                ');

                foreach ($skill_ids as $skill_id) {
                    error_log("  - Linking skill_id: $skill_id");
                    $stmtSkill->execute([
                        ':evidence_id' => $evidence_id,
                        ':skill_id' => (int)$skill_id
                    ]);
                }
            } else {
                error_log("No skill_ids to insert");
            }

            $pdo->commit();
            error_log("Transaction committed successfully");

            echo json_encode([
                'success' => true,
                'id' => $evidence_id,
                'message' => 'Evidence added'
            ]);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Transaction rolled back: " . $e->getMessage());
            throw $e;
        }
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);

} catch (Exception $e) {
    error_log('EXCEPTION: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'debug_message' => $e->getMessage(),
        'debug_trace' => $e->getTraceAsString()
    ]);
}
