<?php
// includes/config/r2.php
// Pure PHP AWS Signature V4 Client for Cloudflare R2 Storage (Bucket: skillbridge)
// Includes automatic fallback to Data URI when R2 credentials are not configured or connection fails.

(function () {
    $file = __DIR__ . '/../../.env';
    if (!file_exists($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if ($key !== '') {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
})();

function r2_get_config(): array {
    return [
        'account_id' => getenv('R2_ACCOUNT_ID') ?: '',
        'access_key' => getenv('R2_ACCESS_KEY_ID') ?: '',
        'secret_key' => getenv('R2_SECRET_ACCESS_KEY') ?: '',
        'bucket'     => getenv('R2_BUCKET') ?: 'skillbridge',
        'public_url' => getenv('R2_PUBLIC_URL') ?: '',
    ];
}

function r2_is_configured(): bool {
    $cfg = r2_get_config();
    return !empty($cfg['account_id']) &&
           !empty($cfg['access_key']) &&
           !empty($cfg['secret_key']) &&
           !empty($cfg['bucket']) &&
           !empty($cfg['public_url']) &&
           strpos($cfg['account_id'], 'isi_') === false &&
           strpos($cfg['access_key'], 'isi_') === false;
}

/**
 * Upload binary bytes to Cloudflare R2 bucket using AWS Signature V4.
 * Returns array with keys: ok (bool), url (string), storage ('r2'|'data_uri').
 */
function r2_upload_bytes(string $objectKey, string $binaryBody, string $contentType = 'image/webp'): array {
    $fallbackDataUri = 'data:' . $contentType . ';base64,' . base64_encode($binaryBody);

    if (!r2_is_configured()) {
        return [
            'ok'      => true,
            'url'     => $fallbackDataUri,
            'storage' => 'data_uri',
            'note'    => 'R2 credentials not configured. Stored as Data URI.',
        ];
    }

    $cfg = r2_get_config();
    $rawAccount = trim($cfg['account_id']);
    // Normalisasi: jika user memasukkan URL atau hostname, ambil ID murninya
    $rawAccount = preg_replace('#^https?://#i', '', $rawAccount);
    $accountId = explode('.', $rawAccount)[0];
    $accountId = trim($accountId, '/');

    $accessKey = trim($cfg['access_key']);
    $secretKey = trim($cfg['secret_key']);
    $bucket    = trim($cfg['bucket']);
    $publicUrl = rtrim(trim($cfg['public_url']), '/');

    $objectKey = ltrim($objectKey, '/');
    $host = "{$accountId}.r2.cloudflarestorage.com";
    $uriPath = "/{$bucket}/{$objectKey}";
    $endpoint = "https://{$host}{$uriPath}";

    $payloadHash = hash('sha256', $binaryBody);
    $amzDate = gmdate('Ymd\THis\Z');
    $dateStamp = gmdate('Ymd');
    $region = 'auto';
    $service = 's3';

    // Canonical Headers (must be lowercase and sorted alphabetically)
    $canonicalHeaders = "content-type:{$contentType}\n" .
                        "host:{$host}\n" .
                        "x-amz-content-sha256:{$payloadHash}\n" .
                        "x-amz-date:{$amzDate}\n";
    $signedHeaders = "content-type;host;x-amz-content-sha256;x-amz-date";

    // Canonical Request
    $canonicalRequest = "PUT\n" .
                        $uriPath . "\n" .
                        "\n" .
                        $canonicalHeaders . "\n" .
                        $signedHeaders . "\n" .
                        $payloadHash;

    // String to Sign
    $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";
    $stringToSign = "AWS4-HMAC-SHA256\n" .
                    $amzDate . "\n" .
                    $credentialScope . "\n" .
                    hash('sha256', $canonicalRequest);

    // Calculate Signature Key
    $kDate    = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretKey, true);
    $kRegion  = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', $service, $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $stringToSign, $kSigning);

    $authHeader = "AWS4-HMAC-SHA256 Credential={$accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";

    $headers = [
        'Host: ' . $host,
        'Content-Type: ' . $contentType,
        'x-amz-date: ' . $amzDate,
        'x-amz-content-sha256: ' . $payloadHash,
        'Authorization: ' . $authHeader,
        'Content-Length: ' . strlen($binaryBody),
    ];

    if (!function_exists('curl_init')) {
        return [
            'ok'      => true,
            'url'     => $fallbackDataUri,
            'storage' => 'data_uri',
            'note'    => 'cURL not available. Stored as Data URI.',
        ];
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => $binaryBody,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if (($httpCode === 200 || $httpCode === 201) && empty($curlErr)) {
        $finalUrl = rtrim($publicUrl, '/') . '/' . $objectKey;
        return [
            'ok'      => true,
            'url'     => $finalUrl,
            'storage' => 'r2',
        ];
    }

    // Automatic Fallback to Data URI on error
    return [
        'ok'       => true,
        'url'      => $fallbackDataUri,
        'storage'  => 'data_uri',
        'fallback' => true,
        'error'    => "R2 HTTP {$httpCode}: {$curlErr}",
    ];
}
