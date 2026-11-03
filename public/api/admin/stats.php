<?php
// public/api/admin/stats.php
// GET: Metrik real-time dashboard admin dari PostgreSQL
require_once __DIR__ . '/../../../includes/auth/auth.php';
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

    // Total mahasiswa (role 'user' di DB = 'mahasiswa' di aplikasi)
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $totalStudents = (int)$stmt->fetchColumn();

    // Total HR
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'hr'");
    $totalHR = (int)$stmt->fetchColumn();

    // Total admin
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $totalAdmins = (int)$stmt->fetchColumn();

    // Total master skills
    $stmt = $pdo->query("SELECT COUNT(*) FROM skills");
    $totalSkills = (int)$stmt->fetchColumn();

    // Total master careers
    $stmt = $pdo->query("SELECT COUNT(*) FROM careers");
    $totalCareers = (int)$stmt->fetchColumn();

    // Total career-skill mappings
    $stmt = $pdo->query("SELECT COUNT(*) FROM career_skill_requirements");
    $totalMappings = (int)$stmt->fetchColumn();

    // Total evidence
    $stmt = $pdo->query("SELECT COUNT(*) FROM evidence");
    $totalEvidence = (int)$stmt->fetchColumn();

    // Recent registrations (last 7 days)
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM users
        WHERE created_at >= CURRENT_TIMESTAMP - INTERVAL '7 days'
    ");
    $recentRegistrations = (int)$stmt->fetchColumn();

    echo json_encode([
        'total_students' => $totalStudents,
        'total_hr' => $totalHR,
        'total_admins' => $totalAdmins,
        'total_skills' => $totalSkills,
        'total_careers' => $totalCareers,
        'total_mappings' => $totalMappings,
        'total_evidence' => $totalEvidence,
        'recent_registrations' => $recentRegistrations
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('API admin/stats.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
