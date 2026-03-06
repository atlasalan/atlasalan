<?php
require_once __DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;

define('R2_BUCKET', getenv('R2_BUCKET') ?: 'atlasalan-games');
define('SITE_NAME', 'atlasalan.fun');
define('SITE_URL',  'https://atlasalan.fun');
define('GAMES_JSON_KEY', 'games.json');
define('DATA_DIR', __DIR__ . '/data/');
define('CONFIG_FILE', DATA_DIR . 'config.php');

function getR2(): S3Client {
    static $client = null;
    if ($client) return $client;
    $client = new S3Client([
        'version'     => 'latest',
        'region'      => 'auto',
        'endpoint'    => getenv('R2_ENDPOINT'),
        'credentials' => [
            'key'    => getenv('R2_ACCESS_KEY_ID'),
            'secret' => getenv('R2_SECRET_KEY'),
        ],
        'use_path_style_endpoint' => true,
    ]);
    return $client;
}

function getGames(): array {
    try {
        $r2 = getR2();
        $result = $r2->getObject([
            'Bucket' => R2_BUCKET,
            'Key'    => GAMES_JSON_KEY,
        ]);
        return json_decode((string)$result['Body'], true) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

function saveGames(array $games): void {
    $r2 = getR2();
    $r2->putObject([
        'Bucket'      => R2_BUCKET,
        'Key'         => GAMES_JSON_KEY,
        'Body'        => json_encode(array_values($games), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        'ContentType' => 'application/json',
    ]);
}

function uploadToR2(string $localPath, string $key, string $contentType = 'application/octet-stream'): void {
    $r2 = getR2();
    $r2->putObject([
        'Bucket'      => R2_BUCKET,
        'Key'         => $key,
        'SourceFile'  => $localPath,
        'ContentType' => $contentType,
    ]);
}

function deleteFromR2(string $key): void {
    try {
        $r2 = getR2();
        $r2->deleteObject(['Bucket' => R2_BUCKET, 'Key' => $key]);
    } catch (Exception $e) {}
}

function getR2Url(string $key): string {
    // Public URL — bucket'ta public access açıksa
    return getenv('R2_PUBLIC_URL') . '/' . $key;
}

function streamFromR2(string $key): void {
    $r2 = getR2();
    $result = $r2->getObject(['Bucket' => R2_BUCKET, 'Key' => $key]);
    echo (string)$result['Body'];
}

function humanSize(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes/1024, 1) . ' KB';
    return round($bytes/1048576, 1) . ' MB';
}

// Config (şifre) - Railway Volume veya local data/ klasöründe
function isSetup(): bool { return file_exists(CONFIG_FILE); }
function getPasswordHash(): ?string {
    if (!isSetup()) return null;
    $cfg = include CONFIG_FILE;
    return $cfg['password_hash'] ?? null;
}
function savePasswordHash(string $hash): void {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    file_put_contents(CONFIG_FILE, "<?php\nreturn ['password_hash'=>'$hash'];\n");
}
