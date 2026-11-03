<?php
/**
 * tools/show_recent_data.php
 * Tampilkan data terbaru yang diinput user
 *
 * Usage: php tools/show_recent_data.php
 */

// Guard: hanya boleh dijalankan via CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: This script can only be run from command line');
}

require_once __DIR__ . '/../includes/config/database.php';

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        throw new Exception('❌ Database connection failed');
    }

    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  Recent User Activity                                          ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // User Skills
    echo "📚 Recent Skills Added:\n";
    echo str_repeat("-", 70) . "\n";

    $stmt = $pdo->query("
        SELECT
            us.id,
            u.name as user_name,
            s.name as skill_name,
            s.category,
            us.source,
            us.created_at
        FROM user_skills us
        JOIN users u ON u.id = us.user_id
        JOIN skills s ON s.id = us.skill_id
        ORDER BY us.created_at DESC
        LIMIT 10
    ");

    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($skills)) {
        echo "  (no data)\n";
    } else {
        foreach ($skills as $skill) {
            $time = date('Y-m-d H:i:s', strtotime($skill['created_at']));
            echo "  [{$skill['id']}] {$skill['user_name']} → {$skill['skill_name']} ({$skill['category']})\n";
            echo "       Source: {$skill['source']} | Time: $time\n\n";
        }
    }

    // Evidence
    echo "\n📂 Recent Evidence Added:\n";
    echo str_repeat("-", 70) . "\n";

    $stmt = $pdo->query("
        SELECT
            e.id,
            u.name as user_name,
            e.source_type,
            e.title,
            e.file_url,
            e.created_at,
            (SELECT COUNT(*) FROM evidence_skills es WHERE es.evidence_id = e.id) as skill_count
        FROM evidence e
        JOIN users u ON u.id = e.user_id
        ORDER BY e.created_at DESC
        LIMIT 10
    ");

    $evidences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($evidences)) {
        echo "  (no data)\n";
    } else {
        foreach ($evidences as $ev) {
            $time = date('Y-m-d H:i:s', strtotime($ev['created_at']));
            echo "  [{$ev['id']}] {$ev['user_name']} → \"{$ev['title']}\"\n";
            echo "       Type: {$ev['source_type']} | Skills: {$ev['skill_count']} | Time: $time\n\n";
        }
    }

    // Summary
    echo "\n📊 Summary:\n";
    echo str_repeat("-", 70) . "\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM user_skills");
    $skillCount = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM evidence");
    $evidenceCount = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM user_skills");
    $activeUsers = $stmt->fetchColumn();

    echo "  Total Skills Added: $skillCount\n";
    echo "  Total Evidence: $evidenceCount\n";
    echo "  Active Users: $activeUsers\n";

    echo "\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
