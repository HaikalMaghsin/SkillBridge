<?php
/**
 * tools/seed_demo.php
 * Seeder data demo untuk keperluan testing dan presentasi SkillBridge
 *
 * Mengisi:
 * - 1 Perusahaan mitra: PT Tech Indonesia
 * - Relasi akun HR ke perusahaan
 * - 3 Lowongan aktif
 * - Sample skills & evidence mahasiswa
 *
 * Jalankan: php tools/seed_demo.php
 */

// Guard: hanya boleh dijalankan via CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden: This script can only be run from command line');
}

require_once __DIR__ . '/../includes/config/database.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  SkillBridge Demo Data Seeder                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    $pdo = get_db_connection();

    if (!$pdo) {
        throw new Exception('❌ Database connection failed');
    }

    echo "[1/7] Connected to PostgreSQL\n\n";

    // ============================================
    // 1. Buat Perusahaan PT Tech Indonesia
    // ============================================
    echo "[2/7] Creating company: PT Tech Indonesia...\n";

    $stmt = $pdo->prepare('
        INSERT INTO companies (name, industry, website, description)
        VALUES (:name, :industry, :website, :description)
        ON CONFLICT DO NOTHING
        RETURNING id
    ');

    $stmt->execute([
        ':name' => 'PT Tech Indonesia',
        ':industry' => 'Technology',
        ':website' => 'https://techindonesia.com',
        ':description' => 'Leading technology company specializing in software development and digital transformation services.'
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $company_id = (int)$result['id'];
        echo "   ✓ Company created (ID: {$company_id})\n";
    } else {
        // Company sudah ada, ambil ID-nya
        $stmt = $pdo->query("SELECT id FROM companies WHERE name = 'PT Tech Indonesia' LIMIT 1");
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        $company_id = (int)$existing['id'];
        echo "   ℹ Company already exists (ID: {$company_id})\n";
    }

    // ============================================
    // 2. Link akun HR ke perusahaan
    // ============================================
    echo "\n[3/7] Linking HR users to company...\n";

    // Cari semua user dengan role 'hr'
    $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'hr'");
    $hr_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($hr_users)) {
        echo "   ⚠ No HR users found in database. Please create HR user first.\n";
        echo "   Run: php tools/create_admin.php (choose option to create HR user)\n";
    } else {
        foreach ($hr_users as $hr) {
            $stmt = $pdo->prepare('
                INSERT INTO company_recruiters (company_id, user_id, role_title, status)
                VALUES (:company_id, :user_id, :role_title, :status)
                ON CONFLICT (company_id, user_id) DO NOTHING
            ');
            $stmt->execute([
                ':company_id' => $company_id,
                ':user_id' => $hr['id'],
                ':role_title' => 'Recruiter',
                ':status' => 'active'
            ]);
            echo "   ✓ Linked {$hr['name']} ({$hr['email']})\n";
        }
    }

    // ============================================
    // 3. Buat Lowongan Kerja
    // ============================================
    echo "\n[4/7] Creating job vacancies...\n";

    // Ambil career_id untuk Backend Developer, Frontend Developer, Data Analyst
    $careers = [];
    $career_names = ['Backend Developer', 'Frontend Developer', 'Data Analyst'];

    foreach ($career_names as $name) {
        $stmt = $pdo->prepare('SELECT id FROM careers WHERE name = :name LIMIT 1');
        $stmt->execute([':name' => $name]);
        $career = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($career) {
            $careers[$name] = (int)$career['id'];
        }
    }

    $vacancies = [
        [
            'title' => 'Senior Backend Developer',
            'career' => 'Backend Developer',
            'positions' => 2,
            'description' => 'We are looking for experienced backend developers to join our engineering team.',
            'requirements' => 'Minimum 3 years experience with PHP, PostgreSQL, REST API development'
        ],
        [
            'title' => 'Frontend Developer (React)',
            'career' => 'Frontend Developer',
            'positions' => 3,
            'description' => 'Join our frontend team to build modern web applications.',
            'requirements' => 'Strong JavaScript skills, experience with React, responsive design'
        ],
        [
            'title' => 'Junior Data Analyst',
            'career' => 'Data Analyst',
            'positions' => 1,
            'description' => 'Entry-level position for fresh graduates interested in data analysis.',
            'requirements' => 'Basic SQL, Excel, and data visualization skills'
        ]
    ];

    foreach ($vacancies as $vac) {
        $career_id = isset($careers[$vac['career']]) ? $careers[$vac['career']] : null;

        $stmt = $pdo->prepare('
            INSERT INTO job_vacancies (company_id, career_id, title, positions, description, requirements, status)
            VALUES (:company_id, :career_id, :title, :positions, :description, :requirements, :status)
            ON CONFLICT DO NOTHING
        ');
        $stmt->execute([
            ':company_id' => $company_id,
            ':career_id' => $career_id,
            ':title' => $vac['title'],
            ':positions' => $vac['positions'],
            ':description' => $vac['description'],
            ':requirements' => $vac['requirements'],
            ':status' => 'active'
        ]);

        echo "   ✓ Created: {$vac['title']} ({$vac['positions']} positions)\n";
    }

    // ============================================
    // 4. Sample Evidence untuk Mahasiswa
    // ============================================
    echo "\n[5/7] Adding sample evidence for students...\n";

    // Ambil 1 mahasiswa untuk sample
    $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'user' LIMIT 1");
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $student_id = (int)$student['id'];
        echo "   • Using student: {$student['name']} (ID: {$student_id})\n";

        // Ambil beberapa skill
        $stmt = $pdo->query("SELECT id, name FROM skills ORDER BY RANDOM() LIMIT 5");
        $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sample_evidence = [
            [
                'type' => 'project',
                'title' => 'E-Commerce Web Application',
                'url' => 'https://github.com/student/ecommerce-project',
                'desc' => 'Full-stack e-commerce platform built with PHP and PostgreSQL'
            ],
            [
                'type' => 'portfolio',
                'title' => 'Personal Portfolio Website',
                'url' => 'https://student-portfolio.dev',
                'desc' => 'Responsive portfolio showcasing web development projects'
            ],
            [
                'type' => 'certificate',
                'title' => 'Web Development Certification',
                'url' => 'https://example.com/cert/12345',
                'desc' => 'Certificate from online bootcamp completing 200 hours of web development training'
            ]
        ];

        foreach ($sample_evidence as $idx => $ev) {
            $stmt = $pdo->prepare('
                INSERT INTO evidence (user_id, source_type, title, file_url, metadata)
                VALUES (:user_id, :source_type, :title, :file_url, :metadata)
                RETURNING id
            ');
            $stmt->execute([
                ':user_id' => $student_id,
                ':source_type' => $ev['type'],
                ':title' => $ev['title'],
                ':file_url' => $ev['url'],
                ':metadata' => json_encode(['description' => $ev['desc']])
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $evidence_id = (int)$result['id'];

            echo "   ✓ Evidence: {$ev['title']}\n";

            // Link evidence ke 2 skill random
            $linked_skills = array_slice($skills, $idx % count($skills), 2);
            foreach ($linked_skills as $skill) {
                $stmt = $pdo->prepare('
                    INSERT INTO evidence_skills (evidence_id, skill_id)
                    VALUES (:evidence_id, :skill_id)
                    ON CONFLICT DO NOTHING
                ');
                $stmt->execute([
                    ':evidence_id' => $evidence_id,
                    ':skill_id' => $skill['id']
                ]);
            }
        }
    } else {
        echo "   ⚠ No student users found. Skipping sample evidence.\n";
    }

    // ============================================
    // 5. Sample Candidate Pipeline
    // ============================================
    echo "\n[6/7] Creating sample candidate pipeline...\n";

    if ($student && !empty($hr_users)) {
        $stmt = $pdo->prepare('
            INSERT INTO candidate_pipelines (company_id, user_id, status, notes)
            VALUES (:company_id, :user_id, :status, :notes)
            ON CONFLICT DO NOTHING
        ');
        $stmt->execute([
            ':company_id' => $company_id,
            ':user_id' => $student_id,
            ':status' => 'Shortlisted',
            ':notes' => 'Strong portfolio, good technical skills'
        ]);
        echo "   ✓ Added {$student['name']} to candidate pipeline (Shortlisted)\n";
    }

    // ============================================
    // 6. Sample Activity Log
    // ============================================
    echo "\n[7/7] Creating sample activity log...\n";

    if (!empty($hr_users)) {
        $hr = $hr_users[0];
        $stmt = $pdo->prepare('
            INSERT INTO recruiter_activity_logs (company_id, user_id, action_type, target_type, description)
            VALUES (:company_id, :user_id, :action_type, :target_type, :description)
        ');
        $stmt->execute([
            ':company_id' => $company_id,
            ':user_id' => $hr['id'],
            ':action_type' => 'candidate_shortlist',
            ':target_type' => 'candidate',
            ':description' => 'Reviewed and shortlisted candidate for Backend Developer position'
        ]);
        echo "   ✓ Added sample activity log\n";
    }

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  SEEDING COMPLETE ✓                                            ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    echo "Summary:\n";
    echo "  ✓ Company: PT Tech Indonesia\n";
    echo "  ✓ HR Users linked: " . count($hr_users) . "\n";
    echo "  ✓ Job Vacancies: 3 active positions\n";
    echo "  ✓ Sample Evidence: 3 items\n";
    echo "  ✓ Candidate Pipeline: 1 record\n";
    echo "  ✓ Activity Logs: 1 record\n\n";

    echo "🎉 Demo data ready! Login as HR to see the recruiter console.\n\n";

} catch (Exception $e) {
    echo "\n❌ SEEDING FAILED\n";
    echo "   Error: {$e->getMessage()}\n\n";
    exit(1);
}
