<?php
// public/api/admin/master.php
// POST: Create master skill/career/mapping
// PUT: Update master skill/career/mapping
// DELETE: Delete master skill/career/mapping
require_once __DIR__ . '/../../../includes/auth/auth.php';
require_once __DIR__ . '/../../../includes/auth/csrf.php';
require_once __DIR__ . '/../../../includes/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Wajib login sebagai admin
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = current_user();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Admin access only']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['POST', 'PUT', 'DELETE'])) {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

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

    $input = json_decode(file_get_contents('php://input'), true);
    $entity = $input['entity'] ?? null; // 'skill', 'career', 'mapping'

    if (!$entity || !in_array($entity, ['skill', 'career', 'mapping'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid entity type']);
        exit;
    }

    // ========== SKILL ==========
    if ($entity === 'skill') {
        if ($method === 'POST') {
            $name = trim($input['name'] ?? '');
            $category = trim($input['category'] ?? '');
            $description = trim($input['description'] ?? '');

            if (!$name) {
                http_response_code(400);
                echo json_encode(['error' => 'name is required']);
                exit;
            }

            // Cek duplikat nama
            $stmt = $pdo->prepare('SELECT id FROM skills WHERE name = :name');
            $stmt->execute([':name' => $name]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Skill name already exists']);
                exit;
            }

            $stmt = $pdo->prepare('
                INSERT INTO skills (name, category, description)
                VALUES (:name, :category, :description)
                RETURNING id
            ');
            $stmt->execute([
                ':name' => $name,
                ':category' => $category,
                ':description' => $description
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'id' => (int)$result['id'],
                'message' => 'Skill created'
            ]);
            exit;
        }

        if ($method === 'PUT') {
            $id = isset($input['id']) ? (int)$input['id'] : null;
            $name = trim($input['name'] ?? '');
            $category = trim($input['category'] ?? '');
            $description = trim($input['description'] ?? '');

            if (!$id || !$name) {
                http_response_code(400);
                echo json_encode(['error' => 'id and name are required']);
                exit;
            }

            $stmt = $pdo->prepare('
                UPDATE skills
                SET name = :name, category = :category, description = :description
                WHERE id = :id
            ');
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':category' => $category,
                ':description' => $description
            ]);

            echo json_encode(['success' => true, 'message' => 'Skill updated']);
            exit;
        }

        if ($method === 'DELETE') {
            $id = isset($input['id']) ? (int)$input['id'] : null;

            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'id is required']);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM skills WHERE id = :id');
            $stmt->execute([':id' => $id]);

            echo json_encode(['success' => true, 'message' => 'Skill deleted']);
            exit;
        }
    }

    // ========== CAREER ==========
    if ($entity === 'career') {
        if ($method === 'POST') {
            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');

            if (!$name) {
                http_response_code(400);
                echo json_encode(['error' => 'name is required']);
                exit;
            }

            // Cek duplikat nama
            $stmt = $pdo->prepare('SELECT id FROM careers WHERE name = :name');
            $stmt->execute([':name' => $name]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Career name already exists']);
                exit;
            }

            $stmt = $pdo->prepare('
                INSERT INTO careers (name, description)
                VALUES (:name, :description)
                RETURNING id
            ');
            $stmt->execute([
                ':name' => $name,
                ':description' => $description
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'id' => (int)$result['id'],
                'message' => 'Career created'
            ]);
            exit;
        }

        if ($method === 'PUT') {
            $id = isset($input['id']) ? (int)$input['id'] : null;
            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');

            if (!$id || !$name) {
                http_response_code(400);
                echo json_encode(['error' => 'id and name are required']);
                exit;
            }

            $stmt = $pdo->prepare('
                UPDATE careers
                SET name = :name, description = :description
                WHERE id = :id
            ');
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':description' => $description
            ]);

            echo json_encode(['success' => true, 'message' => 'Career updated']);
            exit;
        }

        if ($method === 'DELETE') {
            $id = isset($input['id']) ? (int)$input['id'] : null;

            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'id is required']);
                exit;
            }

            $stmt = $pdo->prepare('DELETE FROM careers WHERE id = :id');
            $stmt->execute([':id' => $id]);

            echo json_encode(['success' => true, 'message' => 'Career deleted']);
            exit;
        }
    }

    // ========== MAPPING ==========
    if ($entity === 'mapping') {
        if ($method === 'POST') {
            $career_id = isset($input['career_id']) ? (int)$input['career_id'] : null;
            $skill_id = isset($input['skill_id']) ? (int)$input['skill_id'] : null;
            $is_mandatory = isset($input['is_mandatory']) ? (bool)$input['is_mandatory'] : true;

            if (!$career_id || !$skill_id) {
                http_response_code(400);
                echo json_encode(['error' => 'career_id and skill_id are required']);
                exit;
            }

            $stmt = $pdo->prepare('
                INSERT INTO career_skill_requirements (career_id, skill_id, is_mandatory)
                VALUES (:career_id, :skill_id, :is_mandatory)
                ON CONFLICT (career_id, skill_id) DO NOTHING
                RETURNING id
            ');
            $stmt->execute([
                ':career_id' => $career_id,
                ':skill_id' => $skill_id,
                ':is_mandatory' => $is_mandatory
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'id' => $result ? (int)$result['id'] : null,
                'message' => 'Mapping created'
            ]);
            exit;
        }

        if ($method === 'DELETE') {
            $career_id = isset($input['career_id']) ? (int)$input['career_id'] : null;
            $skill_id = isset($input['skill_id']) ? (int)$input['skill_id'] : null;

            if (!$career_id || !$skill_id) {
                http_response_code(400);
                echo json_encode(['error' => 'career_id and skill_id are required']);
                exit;
            }

            $stmt = $pdo->prepare('
                DELETE FROM career_skill_requirements
                WHERE career_id = :career_id AND skill_id = :skill_id
            ');
            $stmt->execute([
                ':career_id' => $career_id,
                ':skill_id' => $skill_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Mapping deleted']);
            exit;
        }

        // PUT tidak diperlukan untuk mapping (hanya create/delete)
        if ($method === 'PUT') {
            http_response_code(400);
            echo json_encode(['error' => 'Mapping does not support update']);
            exit;
        }
    }

} catch (Exception $e) {
    error_log('API admin/master.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
