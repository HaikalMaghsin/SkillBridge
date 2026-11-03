<?php
// Debug version of gap.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/auth/auth.php';
require_once __DIR__ . '/../../includes/config/database.php';

header('Content-Type: application/json; charset=utf-8');

error_log("=== Gap API Debug ===");

if (!is_logged_in()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user = current_user();
$user_id = $user['id'];

error_log("User ID: $user_id, Role: " . $user['role']);

if ($user['role'] !== 'mahasiswa') {
    echo json_encode(['error' => 'Not mahasiswa role']);
    exit;
}

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        echo json_encode(['error' => 'No DB connection']);
        exit;
    }

    error_log("DB connected");

    // Check if student_profiles exists
    $stmt = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Profile: " . json_encode($profile));

    if (!$profile) {
        echo json_encode([
            'error' => 'No student profile found',
            'debug' => 'Run: INSERT INTO student_profiles (user_id) VALUES (' . $user_id . ')'
        ]);
        exit;
    }

    if (!$profile['target_career_id']) {
        echo json_encode([
            'career' => null,
            'have' => [],
            'explore' => [],
            'debug' => 'No target career set'
        ]);
        exit;
    }

    // Check if v_skill_gap view exists
    $stmt = $pdo->query("SELECT EXISTS (
        SELECT FROM information_schema.views
        WHERE table_name = 'v_skill_gap'
    )");
    $viewExists = $stmt->fetchColumn();

    if (!$viewExists) {
        echo json_encode([
            'error' => 'VIEW v_skill_gap not found',
            'debug' => 'Run db/schema.sql to create it'
        ]);
        exit;
    }

    error_log("View exists, target_career_id: " . $profile['target_career_id']);

    // Rest of the logic from gap.php
    echo json_encode([
        'success' => true,
        'debug' => 'All checks passed',
        'profile' => $profile
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
