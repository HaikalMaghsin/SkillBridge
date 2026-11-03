<?php
/**
 * tools/ensure_student_profile.php
 * Pastikan setiap user role 'mahasiswa' punya student_profiles
 *
 * Usage: php tools/ensure_student_profile.php
 */

// Guard: hanya boleh dijalankan via CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: This script can only be run from command line');
}

require_once __DIR__ . '/../includes/config/database.php';

echo "\n=== Ensure Student Profiles ===\n\n";

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        throw new Exception('❌ Database connection failed');
    }

    echo "✓ Connected to PostgreSQL\n\n";

    // Get all mahasiswa users
    $stmt = $pdo->query("
        SELECT id, name, email
        FROM users
        WHERE role = 'user'
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($users) . " mahasiswa users\n\n";

    $created = 0;
    $existing = 0;

    foreach ($users as $user) {
        // Check if profile exists
        $stmt = $pdo->prepare("
            SELECT id FROM student_profiles WHERE user_id = :user_id
        ");
        $stmt->execute([':user_id' => $user['id']]);
        $profile = $stmt->fetch();

        if ($profile) {
            echo "  ✓ {$user['name']} - profile exists\n";
            $existing++;
        } else {
            // Create profile
            $stmt = $pdo->prepare("
                INSERT INTO student_profiles (user_id, is_public, public_token)
                VALUES (:user_id, FALSE, :token)
            ");
            $token = bin2hex(random_bytes(16));
            $stmt->execute([
                ':user_id' => $user['id'],
                ':token' => $token
            ]);
            echo "  + {$user['name']} - profile created\n";
            $created++;
        }
    }

    echo "\n";
    echo "Summary:\n";
    echo "  - Existing profiles: $existing\n";
    echo "  - Created profiles: $created\n";
    echo "\n✓ Done\n\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
