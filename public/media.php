<?php
// public/media.php
// Proxy same-origin untuk objek Cloudflare R2 (avatars/evidence/cv).
// Dipakai agar gambar/berkas tetap tampil walau domain publik r2.dev diblokir
// oleh jaringan/ISP pengguna.
require_once __DIR__ . '/../includes/config/r2.php';

$key = isset($_GET['key']) ? (string) $_GET['key'] : '';
$key = ltrim($key, '/');

// Validasi ketat: hanya karakter aman, tanpa traversal, dan prefix yang diizinkan.
if ($key === '' || strpos($key, '..') !== false || !preg_match('#^[A-Za-z0-9._\-/]+$#', $key)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid media key';
    exit;
}

$allowedPrefixes = ['avatars/', 'evidence/', 'cv/'];
$allowed = false;
foreach ($allowedPrefixes as $prefix) {
    if (strpos($key, $prefix) === 0) {
        $allowed = true;
        break;
    }
}
if (!$allowed) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    exit;
}

// Cache di browser untuk mengurangi beban server.
$etag = '"' . md5($key) . '"';
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    http_response_code(304);
    exit;
}

$result = r2_get_object($key);
if (empty($result['ok'])) {
    error_log('media.php fetch failed for ' . $key . ': ' . ($result['error'] ?? 'unknown'));
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Media not found';
    exit;
}

$body = (string) $result['body'];
$ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
$contentTypes = [
    'webp' => 'image/webp',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'pdf'  => 'application/pdf',
];
$contentType = $contentTypes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $contentType);
header('Content-Length: ' . strlen($body));
header('Cache-Control: public, max-age=86400');
header('ETag: ' . $etag);
header('X-Content-Type-Options: nosniff');

echo $body;
