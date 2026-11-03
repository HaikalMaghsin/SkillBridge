<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/auth/auth.php';
require_once __DIR__ . '/../includes/config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Evidence Table Test</h2>";

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        die("❌ Database connection failed");
    }

    echo "<p>✓ Connected to PostgreSQL</p>";

    // Check tabel evidence
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = 'evidence'
        )
    ");
    $evidenceExists = $stmt->fetchColumn();

    echo "<p>Table 'evidence': " . ($evidenceExists ? "✓ EXISTS" : "✗ NOT FOUND") . "</p>";

    // Check tabel evidence_skills
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = 'evidence_skills'
        )
    ");
    $evidenceSkillsExists = $stmt->fetchColumn();

    echo "<p>Table 'evidence_skills': " . ($evidenceSkillsExists ? "✓ EXISTS" : "✗ NOT FOUND") . "</p>";

    if (!$evidenceExists) {
        echo "<hr>";
        echo "<h3>❌ SOLUTION: Tabel 'evidence' belum dibuat!</h3>";
        echo "<p>Jalankan command berikut di terminal:</p>";
        echo "<pre>psql [CONNECTION_STRING] -f db/schema.sql</pre>";
    } else {
        // Test insert
        echo "<hr><h3>Test Insert Evidence</h3>";

        $stmt = $pdo->prepare("
            INSERT INTO evidence (user_id, source_type, title, file_url, metadata)
            VALUES (1, 'cv', 'Test Evidence', 'test.pdf', '{}'::jsonb)
            RETURNING id
        ");

        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>✓ Insert successful! Evidence ID: " . $result['id'] . "</p>";

            // Cleanup
            $pdo->exec("DELETE FROM evidence WHERE id = " . $result['id']);
            echo "<p>✓ Cleanup done</p>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
