<?php
// public/p.php
// Halaman Portofolio Publik Mahasiswa (FR-PUB-01)
// URL: p.php?token=<public_token>
require_once __DIR__ . '/../includes/config/app.php';
require_once __DIR__ . '/../includes/config/database.php';
require_once __DIR__ . '/../includes/layouts/head.php';

$page_title = 'Student Portfolio';
$page_css = [];

// Ambil token dari query string
$token = $_GET['token'] ?? '';

if (!$token) {
    http_response_code(400);
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Invalid Link</title></head><body>";
    echo "<div style='text-align: center; margin-top: 100px;'>";
    echo "<h1>Invalid Portfolio Link</h1>";
    echo "<p>The portfolio link you're trying to access is invalid.</p>";
    echo "</div></body></html>";
    exit;
}

$pdo = get_db_connection();

if (!$pdo) {
    http_response_code(503);
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Service Unavailable</title></head><body>";
    echo "<div style='text-align: center; margin-top: 100px;'>";
    echo "<h1>Service Unavailable</h1>";
    echo "<p>Database connection failed. Please try again later.</p>";
    echo "</div></body></html>";
    exit;
}

// Cari profil mahasiswa berdasarkan public_token
$stmt = $pdo->prepare('
    SELECT
        sp.user_id,
        sp.target_career_id,
        sp.is_public,
        u.name,
        u.email,
        c.name AS target_career_name,
        c.description AS target_career_desc
    FROM student_profiles sp
    JOIN users u ON u.id = sp.user_id
    LEFT JOIN careers c ON c.id = sp.target_career_id
    WHERE sp.public_token = :token
');
$stmt->execute([':token' => $token]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika tidak ditemukan atau tidak publik
if (!$profile || !$profile['is_public']) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Portfolio Not Found</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    </head>
    <body>
        <div class="container" style="text-align: center; margin-top: 100px;">
            <h1 style="font-size: 48px; margin-bottom: 16px;">Portfolio Not Found</h1>
            <p style="font-size: 18px; color: var(--text-muted);">This portfolio is either private or does not exist.</p>
            <p style="margin-top: 24px;"><a href="<?= e(base_url('index.php')) ?>" class="btn btn-primary">Back to Home</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$user_id = (int)$profile['user_id'];
$student_name = $profile['name'];
$student_email = $profile['email'];
$target_career = $profile['target_career_name'] ?? 'No Target Set';
$target_career_desc = $profile['target_career_desc'] ?? '';

// Ambil skills mahasiswa
$stmt = $pdo->prepare('
    SELECT
        us.skill_id,
        s.name AS skill_name,
        s.category,
        COUNT(DISTINCT es.evidence_id) AS evidence_count
    FROM user_skills us
    JOIN skills s ON s.id = us.skill_id
    LEFT JOIN evidence_skills es ON es.skill_id = us.skill_id
    LEFT JOIN evidence e ON e.id = es.evidence_id AND e.user_id = us.user_id
    WHERE us.user_id = :user_id AND us.status = :status
    GROUP BY us.skill_id, s.name, s.category
    ORDER BY evidence_count DESC, s.name ASC
');
$stmt->execute([':user_id' => $user_id, ':status' => 'confirmed']);
$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil evidence mahasiswa
$stmt = $pdo->prepare('
    SELECT
        e.id,
        e.source_type,
        e.title,
        e.file_url,
        e.metadata,
        e.created_at
    FROM evidence e
    WHERE e.user_id = :user_id
    ORDER BY e.created_at DESC
');
$stmt->execute([':user_id' => $user_id]);
$evidence = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($student_name) ?> - Portfolio | SkillBridge</title>
    <meta name="description" content="Public portfolio of <?= e($student_name) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <style>
        .portfolio-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
        }
        .portfolio-header h1 {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .portfolio-header p {
            font-size: 18px;
            opacity: 0.9;
        }
        .portfolio-section {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .portfolio-section h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--text-primary);
        }
        .skill-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }
        .skill-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
            box-shadow: var(--shadow-sm);
        }
        .skill-card-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .skill-card-category {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .skill-card-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
        }
        .badge-strong {
            background: var(--tint-green);
            color: var(--success);
        }
        .badge-growing {
            background: var(--tint-yellow);
            color: var(--warning);
        }
        .evidence-list {
            display: grid;
            gap: 16px;
            margin-top: 20px;
        }
        .evidence-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }
        .evidence-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .evidence-card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .evidence-card-type {
            background: var(--tint-indigo);
            color: var(--primary);
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
        }
        .evidence-card-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 12px;
        }
        .evidence-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }
        .evidence-card-date {
            font-size: 13px;
            color: var(--text-light);
        }
        .evidence-card-link {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
        }
        .evidence-card-link:hover {
            text-decoration: underline;
        }
        .portfolio-footer {
            text-align: center;
            padding: 40px 20px;
            background: var(--surface-secondary);
            margin-top: 60px;
        }
    </style>
</head>
<body>
    <div class="portfolio-header">
        <h1><?= e($student_name) ?></h1>
        <p><?= e($student_email) ?></p>
        <p style="margin-top: 16px; font-size: 16px;">
            <strong>Target Career:</strong> <?= e($target_career) ?>
        </p>
    </div>

    <?php if ($target_career_desc): ?>
    <div class="portfolio-section">
        <p style="text-align: center; font-size: 16px; color: var(--text-muted); max-width: 700px; margin: 0 auto;">
            <?= e($target_career_desc) ?>
        </p>
    </div>
    <?php endif; ?>

    <div class="portfolio-section">
        <h2>Skills (<?= count($skills) ?>)</h2>
        <?php if (empty($skills)): ?>
            <p style="color: var(--text-muted); font-size: 15px;">No skills added yet.</p>
        <?php else: ?>
            <div class="skill-grid">
                <?php foreach ($skills as $skill):
                    $evidence_count = (int)$skill['evidence_count'];
                    $label = $evidence_count >= 2 ? 'Strong' : 'Growing';
                    $badge_class = $label === 'Strong' ? 'badge-strong' : 'badge-growing';
                ?>
                <div class="skill-card">
                    <div class="skill-card-name"><?= e($skill['skill_name']) ?></div>
                    <div class="skill-card-category"><?= e($skill['category']) ?></div>
                    <span class="skill-card-badge <?= $badge_class ?>"><?= e($label) ?> (<?= $evidence_count ?> evidence)</span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="portfolio-section">
        <h2>Evidence & Portfolio (<?= count($evidence) ?>)</h2>
        <?php if (empty($evidence)): ?>
            <p style="color: var(--text-muted); font-size: 15px;">No evidence items added yet.</p>
        <?php else: ?>
            <div class="evidence-list">
                <?php foreach ($evidence as $ev):
                    $metadata = json_decode($ev['metadata'], true);
                    $description = $metadata['description'] ?? 'No description provided.';
                    $date = date('d M Y', strtotime($ev['created_at']));
                ?>
                <div class="evidence-card">
                    <div class="evidence-card-header">
                        <div class="evidence-card-title"><?= e($ev['title']) ?></div>
                        <span class="evidence-card-type"><?= e(ucfirst($ev['source_type'])) ?></span>
                    </div>
                    <div class="evidence-card-desc"><?= e($description) ?></div>
                    <div class="evidence-card-footer">
                        <span class="evidence-card-date">Added <?= e($date) ?></span>
                        <?php if ($ev['file_url']): ?>
                            <a href="<?= e($ev['file_url']) ?>" target="_blank" rel="noopener noreferrer" class="evidence-card-link">
                                View Evidence →
                            </a>
                        <?php else: ?>
                            <span style="font-size: 13px; color: var(--text-muted);">No link</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="portfolio-footer">
        <p style="font-size: 14px; color: var(--text-muted);">Powered by <strong>SkillBridge</strong></p>
        <p style="margin-top: 8px;"><a href="<?= e(base_url('index.php')) ?>" class="btn btn-primary">Visit SkillBridge</a></p>
    </div>
</body>
</html>
