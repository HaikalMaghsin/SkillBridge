<?php
/**
 * tools/migrate.php
 * Automated Database Migration Runner for SkillBridge
 *
 * Usage:
 *   php tools/migrate.php          -> Menjalankan semua migrasi yang belum dieksekusi
 *   php tools/migrate.php --status -> Melihat daftar status migrasi (applied / pending)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden: This script can only be run from command line\n");
}

require_once __DIR__ . '/../includes/config/database.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SkillBridge Database Migration Runner                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    $pdo = get_db_connection();
    if (!$pdo) {
        throw new Exception("Koneksi database gagal. Pastikan konfigurasi di .env sudah benar.");
    }

    echo "✓ Terhubung ke database: " . DB_NAME . " (" . DB_HOST . ":" . DB_PORT . ")\n\n";

    // 1. Buat tabel tracking schema_migrations jika belum ada
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            id SERIAL PRIMARY KEY,
            migration VARCHAR(255) UNIQUE NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // 2. Ambil daftar migrasi yang sudah pernah diaplikasikan
    $stmt = $pdo->query("SELECT migration FROM schema_migrations ORDER BY id ASC");
    $appliedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    // 3. Scan file SQL di folder db/migrations/
    $migrationsDir = realpath(__DIR__ . '/../db/migrations');
    if (!$migrationsDir || !is_dir($migrationsDir)) {
        throw new Exception("Direktori db/migrations tidak ditemukan.");
    }

    $files = glob($migrationsDir . '/*.sql');
    sort($files);

    if (empty($files)) {
        echo "ℹ Tidak ada file migrasi ditemukan di db/migrations/\n\n";
        exit(0);
    }

    $isStatusOnly = in_array('--status', $argv, true);

    if ($isStatusOnly) {
        echo "Status Migrasi Database:\n";
        echo str_repeat('-', 64) . "\n";
        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $status = in_array($filename, $appliedMigrations, true) ? "✓ APPLIED" : "⏳ PENDING";
            printf("  %-45s [%s]\n", $filename, $status);
        }
        echo str_repeat('-', 64) . "\n\n";
        exit(0);
    }

    // 4. Eksekusi file migrasi yang pending
    $executedCount = 0;

    foreach ($files as $filePath) {
        $filename = basename($filePath);

        if (in_array($filename, $appliedMigrations, true)) {
            echo "  ✓ Sudah diterapkan: $filename\n";
            continue;
        }

        echo "  ⏳ Menjalankan migrasi: $filename ... ";
        $sql = file_get_contents($filePath);

        if (empty(trim($sql))) {
            echo "KOSONG (Dilewati)\n";
            continue;
        }

        $pdo->beginTransaction();
        try {
            $pdo->exec($sql);

            $insStmt = $pdo->prepare("INSERT INTO schema_migrations (migration) VALUES (:migration)");
            $insStmt->execute([':migration' => $filename]);

            $pdo->commit();
            echo "SUKSES\n";
            $executedCount++;
        } catch (Exception $ex) {
            $pdo->rollBack();
            echo "GAGAL!\n";
            throw new Exception("Error saat mengeksekusi $filename: " . $ex->getMessage());
        }
    }

    echo "\n";
    if ($executedCount === 0) {
        echo "✓ Database sudah up-to-date! Tidak ada migrasi baru.\n\n";
    } else {
        echo "✓ Berhasil mengeksekusi $executedCount migrasi baru.\n\n";
    }

} catch (Exception $e) {
    echo "\n❌ Gagal: " . $e->getMessage() . "\n\n";
    exit(1);
}
