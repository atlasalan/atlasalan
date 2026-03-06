<?php
define('GAMES_DIR', __DIR__ . '/games/');
define('GAMES_JSON', GAMES_DIR . 'games.json');
define('SITE_NAME', 'atlasalan.fun');
define('SITE_URL',  'https://atlasalan.fun');

function getGames() {
    if (!file_exists(GAMES_JSON)) return [];
    return json_decode(file_get_contents(GAMES_JSON), true) ?: [];
}

$id = $_GET['id'] ?? '';
$games = getGames();
$game = null; $gameIndex = null;

foreach ($games as $i => $g) {
    if ($g['id'] === $id) { $game = $g; $gameIndex = $i; break; }
}
if (!$game) { header('Location: index.php'); exit; }

// ── İNDİRME ─────────────────────────────────────────────────────
if (isset($_GET['download'])) {
    // Download sayacı artır
    $games[$gameIndex]['downloads'] = ($games[$gameIndex]['downloads'] ?? 0) + 1;
    file_put_contents(GAMES_JSON, json_encode($games, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $filePath = GAMES_DIR . $game['file'];
    if (!file_exists($filePath)) { header('Location: index.php'); exit; }

    $ext = strtolower(pathinfo($game['file'], PATHINFO_EXTENSION));

    // ── HTML → Watermark enjekte et, sonra indir ─────────────────
    if (in_array($ext, ['html', 'htm'])) {
        $html = file_get_contents($filePath);

        $watermark = '
<!-- atlasalan.fun watermark -->
<style>
#atlasalan-badge{
  position:fixed;bottom:12px;right:12px;z-index:999999;
  background:linear-gradient(135deg,#9b5de5,#ff4daa);
  color:#fff;font-family:"Nunito","Arial",sans-serif;font-weight:800;
  font-size:13px;padding:7px 14px;border-radius:30px;
  box-shadow:0 4px 20px rgba(155,93,229,0.5);
  text-decoration:none;letter-spacing:0.3px;
  display:flex;align-items:center;gap:6px;
  opacity:0.92;transition:opacity 0.2s;
  pointer-events:auto;
}
#atlasalan-badge:hover{opacity:1;}
#atlasalan-badge span{font-size:15px;}
</style>
<a id="atlasalan-badge" href="' . SITE_URL . '" target="_blank">
  <span>🎮</span> ' . SITE_NAME . '
</a>';

        // </body> öncesine veya sona ekle
        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $watermark . '</body>', $html);
        } else {
            $html .= $watermark;
        }

        $safeTitle = preg_replace('/[^a-z0-9_\-]/i', '_', $game['title']);
        $filename  = $safeTitle . '_atlasalan.fun.html';

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($html));
        echo $html;
        exit;
    }

    // ── ZIP (html oyun zip) → içine watermark.html dosyası ekle ─
    if ($ext === 'zip' && $game['type'] === 'html') {
        // ZIP'i geçici dizine kopyala, watermark.html ekle
        $tmpDir  = sys_get_temp_dir() . '/gz_' . uniqid();
        $tmpZip  = $tmpDir . '/game.zip';
        @mkdir($tmpDir, 0755, true);
        copy($filePath, $tmpZip);

        $zip = new ZipArchive();
        if ($zip->open($tmpZip) === true) {
            $watermarkHtml = '<!-- ' . SITE_NAME . ' -->';
            // index.html'i bul ve watermark ekle
            for ($z = 0; $z < $zip->numFiles; $z++) {
                $zname = $zip->getNameIndex($z);
                if (strtolower(basename($zname)) === 'index.html') {
                    $content = $zip->getFromIndex($z);
                    $badge = '<style>#atl-badge{position:fixed;bottom:12px;right:12px;z-index:999999;background:linear-gradient(135deg,#9b5de5,#ff4daa);color:#fff;font-family:Arial,sans-serif;font-weight:800;font-size:13px;padding:7px 14px;border-radius:30px;box-shadow:0 4px 20px rgba(155,93,229,0.5);text-decoration:none;opacity:.92;display:flex;align-items:center;gap:6px;}#atl-badge:hover{opacity:1;}</style><a id="atl-badge" href="' . SITE_URL . '" target="_blank">🎮 ' . SITE_NAME . '</a>';
                    if (stripos($content, '</body>') !== false) {
                        $content = str_ireplace('</body>', $badge . '</body>', $content);
                    } else { $content .= $badge; }
                    $zip->deleteName($zname);
                    $zip->addFromString($zname, $content);
                    break;
                }
            }
            $zip->close();
        }

        $safeTitle = preg_replace('/[^a-z0-9_\-]/i', '_', $game['title']);
        $dlName = $safeTitle . '_atlasalan.fun.zip';

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $dlName . '"');
        header('Content-Length: ' . filesize($tmpZip));
        readfile($tmpZip);
        @unlink($tmpZip); @rmdir($tmpDir);
        exit;
    }

    // ── EXE / diğer → direkt indir ──────────────────────────────
    $safeTitle = preg_replace('/[^a-z0-9_\-]/i', '_', $game['title']);
    $dlName    = $safeTitle . '.' . $ext;

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $dlName . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

$isHtml = $game['type'] === 'html';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🎮 <?= htmlspecialchars($game['title']) ?> — <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Boogaloo&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--pink:#ff4daa;--purple:#9b5de5;--blue:#00bbf9;--yellow:#fee440;--green:#00f5d4;--orange:#f15bb5;--bg:#0d0d1a;--card:#161628;--card2:#1e1e35;--text:#f0eeff;--muted:#8888aa;}
*{margin:0;padding:0;box-sizing:border-box;}
body{background:var(--bg);color:var(--text);font-family:'Nunito',sans-serif;min-height:100vh;}
body::before{content:'';position:fixed;inset:0;
  background:radial-gradient(ellipse 600px 400px at 15% 25%,rgba(155,93,229,0.1) 0%,transparent 70%),
             radial-gradient(ellipse 500px 350px at 85% 75%,rgba(255,77,170,0.08) 0%,transparent 70%);
  pointer-events:none;z-index:0;}

header{position:sticky;top:0;z-index:100;padding:0 40px;display:flex;align-items:center;gap:16px;height:72px;
  border-bottom:1px solid rgba(255,255,255,0.06);background:rgba(13,13,26,0.9);backdrop-filter:blur(12px);}
.logo{font-family:'Boogaloo',cursive;font-size:1.8rem;text-decoration:none;
  background:linear-gradient(90deg,var(--pink),var(--blue),var(--green));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.back-btn{color:var(--muted);text-decoration:none;font-weight:700;font-size:0.9rem;
  padding:6px 14px;border-radius:20px;border:1.5px solid rgba(255,255,255,0.1);transition:all 0.2s;}
.back-btn:hover{color:var(--text);border-color:var(--purple);}

.page{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:40px 20px 80px;}
.top-section{display:grid;grid-template-columns:1fr 340px;gap:40px;align-items:start;}
@media(max-width:800px){.top-section{grid-template-columns:1fr;}}

/* GAME FRAME */
.game-frame-wrap{background:var(--card);border-radius:20px;overflow:hidden;
  border:1.5px solid rgba(255,255,255,0.08);box-shadow:0 30px 80px rgba(0,0,0,0.5);}
.frame-header{padding:12px 20px;background:var(--card2);display:flex;align-items:center;
  justify-content:space-between;border-bottom:1px solid rgba(255,255,255,0.06);}
.frame-header span{font-size:0.8rem;color:var(--muted);font-weight:700;}
.frame-btns{display:flex;gap:8px;}
.fbtn{background:rgba(255,255,255,0.07);border:none;color:var(--text);
  padding:5px 12px;border-radius:8px;cursor:pointer;font-size:0.8rem;
  font-family:'Nunito',sans-serif;font-weight:700;transition:background 0.2s;}
.fbtn:hover{background:rgba(255,255,255,0.13);}
iframe{width:100%;height:520px;border:none;display:block;background:#000;}

/* EXE PREVIEW */
.exe-preview{background:var(--card);border-radius:20px;overflow:hidden;
  border:1.5px solid rgba(255,255,255,0.08);min-height:380px;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  gap:16px;padding:40px;}
.exe-icon{font-size:6rem;filter:drop-shadow(0 0 30px rgba(241,91,181,0.4));}
.exe-preview h3{font-family:'Boogaloo',cursive;font-size:1.6rem;text-align:center;}
.exe-preview p{color:var(--muted);text-align:center;font-size:0.9rem;max-width:300px;line-height:1.6;}

/* SIDEBAR */
.game-meta{background:var(--card);border-radius:20px;padding:28px;border:1.5px solid rgba(255,255,255,0.08);}
.game-thumb-side{width:100%;height:180px;border-radius:14px;overflow:hidden;
  background:var(--card2);display:flex;align-items:center;justify-content:center;
  margin-bottom:20px;font-size:4rem;}
.game-thumb-side img{width:100%;height:100%;object-fit:cover;}
.type-badge{display:inline-block;padding:4px 12px;border-radius:10px;
  font-size:0.72rem;font-weight:800;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:12px;}
.type-badge.html{background:var(--green);color:#0d0d1a;}
.type-badge.exe{background:var(--orange);color:#fff;}
.game-title{font-family:'Boogaloo',cursive;font-size:1.8rem;line-height:1.2;margin-bottom:10px;}
.game-desc{color:var(--muted);font-size:0.9rem;line-height:1.7;margin-bottom:20px;}
.meta-stats{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;}
.meta-stat{background:var(--card2);border-radius:10px;padding:10px 14px;flex:1;min-width:70px;text-align:center;}
.meta-stat-num{font-family:'Boogaloo',cursive;font-size:1.4rem;color:var(--yellow);}
.meta-stat-lbl{font-size:0.72rem;color:var(--muted);}

/* ACTION BUTTONS */
.action-btn{display:block;width:100%;padding:15px;border-radius:14px;text-align:center;
  text-decoration:none;font-family:'Nunito',sans-serif;font-weight:800;font-size:1rem;
  cursor:pointer;border:none;transition:transform 0.2s,box-shadow 0.2s;margin-bottom:10px;}
.action-btn:last-of-type{margin-bottom:0;}
.action-btn:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(0,0,0,0.3);}
.btn-play{background:linear-gradient(135deg,var(--green),var(--blue));color:#0d0d1a;font-size:1.1rem;}
.btn-download-html{background:linear-gradient(135deg,#6c47ff,var(--purple));color:#fff;}
.btn-download-exe{background:linear-gradient(135deg,var(--pink),var(--purple));color:#fff;font-size:1.1rem;}

.dl-note{font-size:0.75rem;color:var(--muted);text-align:center;margin-top:10px;line-height:1.5;}
.dl-note strong{color:var(--yellow);}
.added-date{font-size:0.78rem;color:var(--muted);text-align:center;margin-top:14px;}

/* WATERMARK BADGE (iframe üstünde göster) */
.iframe-badge{
  display:inline-flex;align-items:center;gap:6px;
  background:linear-gradient(135deg,var(--purple),var(--pink));
  color:#fff;font-weight:800;font-size:0.75rem;
  padding:5px 12px;border-radius:20px;text-decoration:none;
  margin-left:auto;opacity:0.85;transition:opacity 0.2s;
}
.iframe-badge:hover{opacity:1;}
</style>
</head>
<body>

<header>
  <a href="index.php" class="back-btn">← Geri</a>
  <a href="index.php" class="logo">🎮 <?= SITE_NAME ?></a>
</header>

<div class="page">
  <div class="top-section">

    <!-- OYUN ALANI -->
    <div>
      <?php if ($isHtml): ?>
        <div class="game-frame-wrap">
          <div class="frame-header">
            <span>🌐 <?= htmlspecialchars($game['title']) ?></span>
            <div class="frame-btns">
              <a href="?id=<?= urlencode($id) ?>&download=1" class="fbtn">⬇ İndir</a>
              <button class="fbtn" onclick="goFullscreen()">⛶ Tam Ekran</button>
            </div>
          </div>
          <iframe src="games/<?= htmlspecialchars($game['file']) ?>" id="gameFrame" allowfullscreen></iframe>
        </div>
      <?php else: ?>
        <div class="exe-preview">
          <div class="exe-icon">💾</div>
          <h3><?= htmlspecialchars($game['title']) ?></h3>
          <p>Bu oyun Windows için EXE formatındadır. İndirip bilgisayarında çalıştırabilirsin.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- SIDEBAR -->
    <div>
      <div class="game-meta">
        <div class="game-thumb-side">
          <?php if (!empty($game['thumb'])): ?>
            <img src="games/<?= htmlspecialchars($game['thumb']) ?>" alt="">
          <?php else: ?>
            <?= $isHtml ? '🌐' : '💾' ?>
          <?php endif; ?>
        </div>

        <span class="type-badge <?= $game['type'] ?>"><?= $isHtml ? '🌐 HTML Oyun' : '💾 EXE Oyun' ?></span>
        <div class="game-title"><?= htmlspecialchars($game['title']) ?></div>
        <div class="game-desc"><?= nl2br(htmlspecialchars($game['desc'])) ?></div>

        <div class="meta-stats">
          <div class="meta-stat">
            <div class="meta-stat-num"><?= $games[$gameIndex]['downloads'] ?? 0 ?></div>
            <div class="meta-stat-lbl">İndirme</div>
          </div>
          <?php if (!empty($game['size'])): ?>
          <div class="meta-stat">
            <div class="meta-stat-num"><?= htmlspecialchars($game['size']) ?></div>
            <div class="meta-stat-lbl">Boyut</div>
          </div>
          <?php endif; ?>
        </div>

        <?php if ($isHtml): ?>
          <!-- HTML: Oyna + İndir -->
          <a href="#gameFrame" class="action-btn btn-play"
            onclick="document.getElementById('gameFrame').scrollIntoView({behavior:'smooth'});return false;">
            ▶ Hemen Oyna
          </a>
          <a href="?id=<?= urlencode($id) ?>&download=1" class="action-btn btn-download-html">
            ⬇ HTML Olarak İndir
          </a>
          <div class="dl-note">
            İndirilen dosyaya <strong>🎮 <?= SITE_NAME ?></strong> rozeti eklenir.
          </div>
        <?php else: ?>
          <!-- EXE: Sadece indir -->
          <a href="?id=<?= urlencode($id) ?>&download=1" class="action-btn btn-download-exe">
            ⬇ Oyunu İndir (.exe)
          </a>
        <?php endif; ?>

        <div class="added-date">📅 Eklenme: <?= htmlspecialchars($game['date'] ?? 'Bilinmiyor') ?></div>
      </div>
    </div>

  </div>
</div>

<script>
function goFullscreen() {
  const frame = document.getElementById('gameFrame');
  if (!frame) return;
  if (frame.requestFullscreen) frame.requestFullscreen();
  else if (frame.webkitRequestFullscreen) frame.webkitRequestFullscreen();
}
</script>
</body>
</html>
