<?php
require_once __DIR__ . '/r2.php';

// Thumbnail serve
if (isset($_GET['thumb'])) {
    $key = 'games/' . basename($_GET['thumb']);
    try {
        $r2 = getR2();
        $result = $r2->getObject(['Bucket' => R2_BUCKET, 'Key' => $key]);
        $ct = $result['ContentType'] ?? 'image/jpeg';
        header('Content-Type: ' . $ct);
        header('Cache-Control: public, max-age=86400');
        echo (string)$result['Body'];
    } catch (Exception $e) {
        http_response_code(404);
    }
    exit;
}

// Game file serve (HTML oyun için iframe)
if (isset($_GET['game'])) {
    $key = 'games/' . basename($_GET['game']);
    try {
        $r2 = getR2();
        $result = $r2->getObject(['Bucket' => R2_BUCKET, 'Key' => $key]);
        $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
        $ct = in_array($ext, ['html','htm']) ? 'text/html; charset=utf-8' : 'application/octet-stream';
        header('Content-Type: ' . $ct);
        header('Cache-Control: public, max-age=3600');
        echo (string)$result['Body'];
    } catch (Exception $e) {
        http_response_code(404);
        echo 'Dosya bulunamadı.';
    }
    exit;
}

http_response_code(400);
echo 'Geçersiz istek.';
