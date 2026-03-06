<?php
// Games data directory
define('GAMES_DIR', __DIR__ . '/games/');
define('GAMES_JSON', GAMES_DIR . 'games.json');

function getGames() {
    if (!file_exists(GAMES_JSON)) return [];
    $data = file_get_contents(GAMES_JSON);
    return json_decode($data, true) ?: [];
}

$games = getGames();
$filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$filtered = array_filter($games, function($g) use ($filter, $search) {
    $typeMatch = $filter === 'all' || $g['type'] === $filter;
    $searchMatch = $search === '' || stripos($g['title'], $search) !== false || stripos($g['desc'], $search) !== false;
    return $typeMatch && $searchMatch;
});
$filtered = array_reverse($filtered); // newest first
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🎮 atlasalan.fun — Oyunlarım</title>
<link href="https://fonts.googleapis.com/css2?family=Boogaloo&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --pink: #ff4daa;
  --purple: #9b5de5;
  --blue: #00bbf9;
  --yellow: #fee440;
  --green: #00f5d4;
  --orange: #f15bb5;
  --bg: #0d0d1a;
  --card: #161628;
  --card2: #1e1e35;
  --text: #f0eeff;
  --muted: #8888aa;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
  background: var(--bg);
  color: var(--text);
  font-family: 'Nunito', sans-serif;
  min-height: 100vh;
  overflow-x: hidden;
}

/* ANIMATED BG */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background:
    radial-gradient(ellipse 600px 400px at 10% 20%, rgba(155,93,229,0.12) 0%, transparent 70%),
    radial-gradient(ellipse 500px 350px at 90% 80%, rgba(255,77,170,0.10) 0%, transparent 70%),
    radial-gradient(ellipse 400px 300px at 50% 50%, rgba(0,187,249,0.06) 0%, transparent 70%);
  pointer-events: none;
  z-index: 0;
}

/* HEADER */
header {
  position: relative;
  z-index: 10;
  padding: 0 40px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 72px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  background: rgba(13,13,26,0.85);
  backdrop-filter: blur(12px);
  position: sticky;
  top: 0;
}
.logo {
  font-family: 'Boogaloo', cursive;
  font-size: 2rem;
  background: linear-gradient(90deg, var(--pink), var(--blue), var(--green));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: 1px;
}
.logo span { font-size: 1.6rem; }

nav { display: flex; gap: 8px; align-items: center; }
nav a {
  color: var(--muted);
  text-decoration: none;
  font-weight: 600;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 0.9rem;
  transition: all 0.2s;
}
nav a:hover { color: var(--text); background: rgba(255,255,255,0.07); }
nav a.active { color: var(--yellow); background: rgba(254,228,64,0.1); }

/* HERO */
.hero {
  position: relative;
  z-index: 1;
  text-align: center;
  padding: 70px 20px 50px;
}
.hero h1 {
  font-family: 'Boogaloo', cursive;
  font-size: clamp(2.8rem, 7vw, 5.5rem);
  line-height: 1.1;
  background: linear-gradient(135deg, var(--yellow) 0%, var(--pink) 40%, var(--blue) 80%, var(--green) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: gradShift 4s ease infinite alternate;
}
@keyframes gradShift {
  0% { filter: hue-rotate(0deg); }
  100% { filter: hue-rotate(30deg); }
}
.hero p {
  margin-top: 16px;
  color: var(--muted);
  font-size: 1.1rem;
  font-weight: 600;
}
.stats {
  display: flex;
  justify-content: center;
  gap: 32px;
  margin-top: 30px;
  flex-wrap: wrap;
}
.stat {
  text-align: center;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 14px;
  padding: 14px 28px;
}
.stat-num {
  font-family: 'Boogaloo', cursive;
  font-size: 2rem;
  color: var(--yellow);
}
.stat-label { font-size: 0.8rem; color: var(--muted); margin-top: 2px; }

/* SEARCH & FILTER */
.controls {
  position: relative;
  z-index: 1;
  max-width: 900px;
  margin: 0 auto 40px;
  padding: 0 20px;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
}
.search-wrap {
  flex: 1;
  min-width: 200px;
  position: relative;
}
.search-wrap input {
  width: 100%;
  background: var(--card);
  border: 1.5px solid rgba(255,255,255,0.08);
  border-radius: 30px;
  padding: 12px 20px 12px 46px;
  color: var(--text);
  font-family: 'Nunito', sans-serif;
  font-size: 0.95rem;
  outline: none;
  transition: border-color 0.2s;
}
.search-wrap input:focus { border-color: var(--purple); }
.search-wrap::before {
  content: '🔍';
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.9rem;
}
.filters { display: flex; gap: 8px; flex-wrap: wrap; }
.filter-btn {
  background: var(--card);
  border: 1.5px solid rgba(255,255,255,0.08);
  color: var(--muted);
  border-radius: 20px;
  padding: 8px 18px;
  cursor: pointer;
  font-family: 'Nunito', sans-serif;
  font-weight: 700;
  font-size: 0.85rem;
  transition: all 0.2s;
  text-decoration: none;
  display: inline-block;
}
.filter-btn:hover { border-color: var(--purple); color: var(--text); }
.filter-btn.active { background: var(--purple); border-color: var(--purple); color: #fff; }
.filter-btn.html.active { background: var(--green); border-color: var(--green); color: #0d0d1a; }
.filter-btn.exe.active { background: var(--orange); border-color: var(--orange); color: #fff; }

/* GAMES GRID */
.games-section {
  position: relative;
  z-index: 1;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px 80px;
}
.section-title {
  font-family: 'Boogaloo', cursive;
  font-size: 1.6rem;
  color: var(--text);
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.section-title::after {
  content: '';
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, rgba(155,93,229,0.3), transparent);
}
.games-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 22px;
}

/* GAME CARD */
.game-card {
  background: var(--card);
  border: 1.5px solid rgba(255,255,255,0.06);
  border-radius: 18px;
  overflow: hidden;
  transition: transform 0.25s, border-color 0.25s, box-shadow 0.25s;
  cursor: pointer;
  text-decoration: none;
  color: inherit;
  display: block;
  position: relative;
}
.game-card:hover {
  transform: translateY(-6px) scale(1.01);
  border-color: var(--purple);
  box-shadow: 0 20px 50px rgba(155,93,229,0.2);
}
.card-thumb {
  width: 100%;
  height: 160px;
  background: linear-gradient(135deg, var(--card2), #252540);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 4rem;
  position: relative;
  overflow: hidden;
}
.card-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.card-thumb .thumb-fallback {
  font-size: 4rem;
  filter: drop-shadow(0 0 20px rgba(155,93,229,0.5));
}
.type-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 4px 10px;
  border-radius: 10px;
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.type-badge.html { background: var(--green); color: #0d0d1a; }
.type-badge.exe { background: var(--orange); color: #fff; }
.play-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s;
}
.game-card:hover .play-overlay { opacity: 1; }
.play-btn-big {
  width: 56px;
  height: 56px;
  background: var(--yellow);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
}
.card-body { padding: 16px; }
.card-title {
  font-weight: 800;
  font-size: 1.05rem;
  margin-bottom: 6px;
  color: var(--text);
}
.card-desc {
  font-size: 0.82rem;
  color: var(--muted);
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid rgba(255,255,255,0.05);
}
.dl-count { font-size: 0.78rem; color: var(--muted); }
.play-now {
  font-size: 0.78rem;
  font-weight: 800;
  color: var(--yellow);
}

/* EMPTY STATE */
.empty {
  text-align: center;
  padding: 80px 20px;
  color: var(--muted);
}
.empty-icon { font-size: 4rem; margin-bottom: 16px; }
.empty h3 { font-size: 1.3rem; color: var(--text); margin-bottom: 8px; }

/* FOOTER */
footer {
  position: relative;
  z-index: 1;
  text-align: center;
  padding: 30px;
  border-top: 1px solid rgba(255,255,255,0.05);
  color: var(--muted);
  font-size: 0.85rem;
}
footer span { color: var(--pink); }

/* RESPONSIVE */
@media (max-width: 600px) {
  header { padding: 0 16px; }
  .hero { padding: 50px 16px 30px; }
  .games-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; }
}
</style>
</head>
<body>

<header>
  <div class="logo"><span>🎮</span> atlasalan.fun</div>
  <nav>
    <a href="index.php" class="active">Ana Sayfa</a>
  </nav>
</header>

<section class="hero">
  <h1>atlasalan.fun</h1>
  <p>HTML oyunlarını direkt oyna · EXE oyunlarını indir</p>
  <div class="stats">
    <?php
      $htmlCount = count(array_filter($games, fn($g) => $g['type'] === 'html'));
      $exeCount  = count(array_filter($games, fn($g) => $g['type'] === 'exe'));
    ?>
    <div class="stat">
      <div class="stat-num"><?= count($games) ?></div>
      <div class="stat-label">Toplam Oyun</div>
    </div>
    <div class="stat">
      <div class="stat-num"><?= $htmlCount ?></div>
      <div class="stat-label">HTML Oyun</div>
    </div>
    <div class="stat">
      <div class="stat-num"><?= $exeCount ?></div>
      <div class="stat-label">EXE Oyun</div>
    </div>
  </div>
</section>

<div class="controls">
  <form method="GET" style="display:contents">
    <div class="search-wrap">
      <input type="text" name="q" placeholder="Oyun ara..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="filters">
      <a href="?type=all<?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-btn <?= $filter==='all' ? 'active' : '' ?>">🎮 Tümü</a>
      <a href="?type=html<?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-btn html <?= $filter==='html' ? 'active' : '' ?>">🌐 HTML</a>
      <a href="?type=exe<?= $search ? '&q='.urlencode($search) : '' ?>" class="filter-btn exe <?= $filter==='exe' ? 'active' : '' ?>">💾 EXE</a>
    </div>
    <button type="submit" style="display:none"></button>
  </form>
</div>

<div class="games-section">
  <div class="section-title">
    <?php if ($filter === 'html'): ?>🌐 HTML Oyunlar
    <?php elseif ($filter === 'exe'): ?>💾 EXE Oyunlar
    <?php else: ?>🕹️ Tüm Oyunlar
    <?php endif; ?>
  </div>

  <?php if (empty($filtered)): ?>
    <div class="empty">
      <div class="empty-icon">👾</div>
      <h3>Henüz oyun yok</h3>
      <p>Yakında yeni oyunlar eklenecek!</p>
    </div>
  <?php else: ?>
    <div class="games-grid">
      <?php foreach ($filtered as $i => $game): ?>
        <?php $isHtml = $game['type'] === 'html'; ?>
        <a class="game-card" href="game.php?id=<?= urlencode($game['id']) ?>">
          <div class="card-thumb">
            <?php if (!empty($game['thumb'])): ?>
              <img src="games/<?= htmlspecialchars($game['thumb']) ?>" alt="">
            <?php else: ?>
              <div class="thumb-fallback"><?= $isHtml ? '🌐' : '💾' ?></div>
            <?php endif; ?>
            <span class="type-badge <?= $game['type'] ?>"><?= strtoupper($game['type']) ?></span>
            <div class="play-overlay">
              <div class="play-btn-big"><?= $isHtml ? '▶' : '⬇' ?></div>
            </div>
          </div>
          <div class="card-body">
            <div class="card-title"><?= htmlspecialchars($game['title']) ?></div>
            <div class="card-desc"><?= htmlspecialchars($game['desc']) ?></div>
            <div class="card-footer">
              <span class="dl-count">📥 <?= $game['downloads'] ?? 0 ?> indirme</span>
              <span class="play-now"><?= $isHtml ? '▶ Oyna' : '⬇ İndir' ?></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<footer>
  Tüm oyunlar <span>♥</span> ile yapıldı · <strong>atlasalan.fun</strong>
</footer>

</body>
</html>
