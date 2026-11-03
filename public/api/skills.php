<?php
// public/api/skills.php
// GET: Ambil master skills, careers, dan career_skill_requirements dari PostgreSQL
// POST: Tambah skill kustom baru (oleh user terotentikasi)
require_once __DIR__ . '/../../includes/auth/auth.php';
require_once __DIR__ . '/../../includes/auth/csrf.php';
require_once __DIR__ . '/../../includes/config/database.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['GET', 'POST'])) {
    http_response_code(405);
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

    // POST: Tambah custom skill baru
    if ($method === 'POST') {
        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        if (!csrf_verify()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $category = trim($input['category'] ?? 'Programming');
        $description = trim($input['description'] ?? '');

        if (!$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Skill name is required']);
            exit;
        }

        // Cek apakah skill sudah ada (case-insensitive)
        $stmt = $pdo->prepare('SELECT id, name, category, description FROM skills WHERE LOWER(name) = LOWER(:name)');
        $stmt->execute([':name' => $name]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            echo json_encode([
                'id' => (int)$existing['id'],
                'name' => $existing['name'],
                'category' => $existing['category'],
                'description' => $existing['description']
            ]);
            exit;
        }

        $stmt = $pdo->prepare('
            INSERT INTO skills (name, category, description)
            VALUES (:name, :category, :description)
            RETURNING id, name, category, description
        ');
        $stmt->execute([
            ':name' => $name,
            ':category' => $category,
            ':description' => $description
        ]);

        $created = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'id' => (int)$created['id'],
            'name' => $created['name'],
            'category' => $created['category'],
            'description' => $created['description']
        ]);
        exit;
    }

    // GET: Ambil semua data master
    // Ambil semua skills
    $stmt = $pdo->query('SELECT id, name, category, description FROM skills ORDER BY name');
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil semua careers
    $stmt = $pdo->query('SELECT id, name, description FROM careers ORDER BY name');
    $careers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil career_skill_requirements dan gabungkan dengan nama skill
    $stmt = $pdo->query('
        SELECT
            csr.career_id,
            c.name AS career_name,
            s.id AS skill_id,
            s.name AS skill_name,
            s.category AS skill_category,
            csr.is_mandatory
        FROM career_skill_requirements csr
        JOIN careers c ON c.id = csr.career_id
        JOIN skills s ON s.id = csr.skill_id
        ORDER BY c.name, s.name
    ');
    $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transform careers untuk include skills array (kompatibel dengan data/careers.json)
    $careersMap = [];
    foreach ($careers as $career) {
        $careersMap[$career['id']] = [
            'id' => (int)$career['id'],
            'name' => $career['name'],
            'description' => $career['description'],
            'skills' => []
        ];
    }

    foreach ($mappings as $mapping) {
        $careersMap[$mapping['career_id']]['skills'][] = $mapping['skill_name'];
    }

    $careersWithSkills = array_values($careersMap);

    // Transform skills untuk kompatibilitas
    $skillsTransformed = array_map(function($skill) {
        return [
            'id' => (int)$skill['id'],
            'name' => $skill['name'],
            'category' => $skill['category'],
            'description' => $skill['description']
        ];
    }, $skills);

    echo json_encode([
        'skills' => $skillsTransformed,
        'careers' => $careersWithSkills,
        'mappings' => $mappings
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('API skills.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
