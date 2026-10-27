<?php
/**
 * tools/create_user.php
 * Script CLI untuk membuat user dengan role tertentu (mahasiswa, hr, admin)
 *
 * Usage:
 *   php tools/create_user.php
 *   (Interactive mode - pilih role dan input data)
 *
 *   php tools/create_user.php admin admin@skillbridge.id "Admin Utama"
 *   php tools/create_user.php hr hr@techindonesia.com "Aditya Pratama"
 *   php tools/create_user.php mahasiswa jhon@gmail.com "Jhon Doe"
 */

// Guard: hanya boleh dijalankan via CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: This script can only be run from command line');
}

require_once __DIR__ . '/../includes/config/database.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SkillBridge - Create User Account                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Parse arguments atau masuk ke interactive mode
$role = $argv[1] ?? null;
$email = $argv[2] ?? null;
$name = $argv[3] ?? null;

// Interactive mode jika tidak ada arguments
if (!$role) {
    echo "Pilih role user:\n";
    echo "  1. Mahasiswa (Student)\n";
    echo "  2. HR / Recruiter\n";
    echo "  3. Admin\n";
    echo "\nPilihan (1-3): ";

    $choice = trim(fgets(STDIN));

    switch ($choice) {
        case '1':
            $role = 'mahasiswa';
            break;
        case '2':
            $role = 'hr';
            break;
        case '3':
            $role = 'admin';
            break;
        default:
            echo "❌ Pilihan tidak valid.\n\n";
            exit(1);
    }
}

// Validasi role
$valid_roles = ['mahasiswa', 'hr', 'admin'];
if (!in_array($role, $valid_roles)) {
    echo "❌ Role tidak valid. Gunakan: mahasiswa, hr, atau admin\n\n";
    exit(1);
}

// Input email
if (!$email) {
    echo "\nEmail: ";
    $email = trim(fgets(STDIN));
}

$email = strtolower(trim($email));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Format email tidak valid.\n\n";
    exit(1);
}

// Input name
if (!$name) {
    echo "Nama lengkap: ";
    $name = trim(fgets(STDIN));
}

if (empty($name)) {
    echo "❌ Nama tidak boleh kosong.\n\n";
    exit(1);
}

// Input password
echo "Password (min. 8 karakter): ";

// Hide password input
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Windows
    $password = stream_get_line(STDIN, 1024, PHP_EOL);
} else {
    // Unix/Linux/Mac
    system('stty -echo');
    $password = trim(fgets(STDIN));
    system('stty echo');
}

echo "\n";

if (strlen($password) < 8 || strlen($password) > 72) {
    echo "❌ Password harus 8-72 karakter.\n\n";
    exit(1);
}

// Connect to database
try {
    $pdo = get_db_connection();

    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Map mahasiswa -> user for database
    $db_role = ($role === 'mahasiswa') ? 'user' : $role;

    // Check if email already exists
    $stmt = $pdo->prepare('SELECT id, role FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        echo "⚠️  Email sudah terdaftar dengan role: " . $existing['role'] . "\n";
        echo "Apakah Anda ingin mengubah role menjadi '{$role}'? (y/n): ";

        $confirm = trim(fgets(STDIN));

        if (strtolower($confirm) !== 'y') {
            echo "❌ Dibatalkan.\n\n";
            exit(1);
        }

        // Update existing user
        $stmt = $pdo->prepare('
            UPDATE users
            SET name = :name, password_hash = :hash, role = :role
            WHERE email = :email
        ');
        $stmt->execute([
            ':name' => $name,
            ':hash' => password_hash($password, PASSWORD_DEFAULT),
            ':role' => $db_role,
            ':email' => $email
        ]);

        echo "\n✅ User berhasil diupdate!\n";
    } else {
        // Insert new user
        $stmt = $pdo->prepare('
            INSERT INTO users (name, email, password_hash, role)
            VALUES (:name, :email, :hash, :role)
        ');
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':hash' => password_hash($password, PASSWORD_DEFAULT),
            ':role' => $db_role
        ]);

        echo "\n✅ User berhasil dibuat!\n";
    }

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  USER CREDENTIALS                                              ║\n";
    echo "╠════════════════════════════════════════════════════════════════╣\n";
    echo "║  Role  : " . str_pad(ucfirst($role), 52) . "║\n";
    echo "║  Email : " . str_pad($email, 52) . "║\n";
    echo "║  Name  : " . str_pad($name, 52) . "║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // Additional info for HR users
    if ($role === 'hr') {
        echo "ℹ️  INFO: Saat login pertama kali sebagai HR, sistem akan otomatis:\n";
        echo "   - Membuat atau menghubungkan ke perusahaan default\n";
        echo "   - User HR bisa langsung mengakses dashboard rekrutmen\n\n";
    }

    echo "🚀 Login URL: http://localhost:8000/login.php\n\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
