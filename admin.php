<?php
session_start();
require_once __DIR__ . '/r2.php';

$msg = ''; $msgType = ''; $view = 'login';

if (!isSetup()) {
    $view = 'setup';
    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='setup') {
        $pw=$_POST['password']??''; $pw2=$_POST['password2']??'';
        if (strlen($pw)<6) { $msg='❌ Şifre en az 6 karakter.'; $msgType='error'; }
        elseif ($pw!==$pw2) { $msg='❌ Şifreler eşleşmiyor.'; $msgType='error'; }
        else { savePasswordHash(password_hash($pw,PASSWORD_BCRYPT)); $_SESSION['admin_logged_in']=true; header('Location: admin.php'); exit; }
    }
} elseif (isset($_GET['logout'])) {
    session_destroy(); header('Location: admin.php'); exit;
} elseif (!isset($_SESSION['admin_logged_in'])) {
    $view = 'login';
    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='login') {
        if (password_verify($_POST['password']??'', getPasswordHash())) {
            session_regenerate_id(true); $_SESSION['admin_logged_in']=true; header('Location: admin.php'); exit;
        } else { $msg='❌ Yanlış şifre.'; $msgType='error'; sleep(1); }
    }
} else {
    $view = 'dashboard';

    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='change_password') {
        $old=$_POST['old_password']??''; $new=$_POST['new_password']??''; $new2=$_POST['new_password2']??'';
        if (!password_verify($old,getPasswordHash())) { $msg='❌ Mevcut şifre yanlış.'; $msgType='error'; }
        elseif (strlen($new)<6) { $msg='❌ En az 6 karakter.'; $msgType='error'; }
        elseif ($new!==$new2) { $msg='❌ Şifreler eşleşmiyor.'; $msgType='error'; }
        else { savePasswordHash(password_hash($new,PASSWORD_BCRYPT)); $msg='✅ Şifre değiştirildi.'; $msgType='success'; }
    }

    if (isset($_GET['delete'])) {
        $games=getGames(); $delId=$_GET['delete'];
        foreach ($games as $i=>$g) {
            if ($g['id']===$delId) {
                if (!empty($g['file'])) deleteFromR2('games/'.$g['file']);
                if (!empty($g['thumb'])) deleteFromR2('games/'.$g['thumb']);
                array_splice($games,$i,1); break;
            }
        }
        saveGames($games); $msg='✅ Oyun silindi.'; $msgType='success';
    }

    if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='upload') {
        $title=trim($_POST['title']??''); $desc=trim($_POST['desc']??''); $type=$_POST['type']??'html';
        if (empty($title)) { $msg='❌ Başlık zorunlu!'; $msgType='error'; }
        elseif (!isset($_FILES['gamefile'])||$_FILES['gamefile']['error']!==UPLOAD_ERR_OK) { $msg='❌ Dosya seçilmedi.'; $msgType='error'; }
        else {
            $file=$_FILES['gamefile']; $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
            $allowed=$type==='html'?['html','htm','zip']:['exe','zip'];
            if (!in_array($ext,$allowed)) { $msg='❌ Geçersiz tür: '.implode(', ',$allowed); $msgType='error'; }
            else {
                $id=uniqid('game_',true); $safe=preg_replace('/[^a-z0-9_\-]/i','_',$title);
                $fn=$id.'_'.$safe.'.'.$ext;
                uploadToR2($file['tmp_name'], 'games/'.$fn);
                $tn='';
                if (isset($_FILES['thumb'])&&$_FILES['thumb']['error']===UPLOAD_ERR_OK) {
                    $tf=$_FILES['thumb']; $te=strtolower(pathinfo($tf['name'],PATHINFO_EXTENSION));
                    if (in_array($te,['jpg','jpeg','png','gif','webp'])) {
                        $tn=$id.'_thumb.'.$te;
                        uploadToR2($tf['tmp_name'], 'games/'.$tn, 'image/'.$te);
                    }
                }
                $games=getGames();
                $games[]=['id'=>$id,'title'=>$title,'desc'=>$desc,'type'=>$type,'file'=>$fn,'thumb'=>$tn,
                          'size'=>humanSize($file['size']),'downloads'=>0,'date'=>date('d.m.Y')];
                saveGames($games);
                $msg='✅ "'.htmlspecialchars($title).'" yüklendi!'; $msgType='success';
            }
        }
    }
}

$games=getGames(); $totalDl=array_sum(array_column($games,'downloads'));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🔧 Admin — atlasalan.fun</title>
<link href="https://fonts.googleapis.com/css2?family=Boogaloo&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--pink:#ff4daa;--purple:#9b5de5;--blue:#00bbf9;--yellow:#fee440;--green:#00f5d4;--orange:#f15bb5;--bg:#0d0d1a;--card:#161628;--card2:#1e1e35;--text:#f0eeff;--muted:#8888aa;--red:#ff5555;}
*{margin:0;padding:0;box-sizing:border-box;}
body{background:var(--bg);color:var(--text);font-family:'Nunito',sans-serif;min-height:100vh;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 700px 500px at 0% 0%,rgba(155,93,229,0.1) 0%,transparent 70%),radial-gradient(ellipse 500px 400px at 100% 100%,rgba(255,77,170,0.07) 0%,transparent 70%);pointer-events:none;z-index:0;}
.auth-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;z-index:1;}
.auth-card{background:var(--card);border:1.5px solid rgba(255,255,255,0.09);border-radius:24px;padding:44px 40px;width:100%;max-width:420px;box-shadow:0 40px 100px rgba(0,0,0,0.6);}
.auth-logo{font-family:'Boogaloo',cursive;font-size:2.2rem;text-align:center;background:linear-gradient(90deg,var(--pink),var(--blue),var(--green));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:6px;}
.auth-sub{text-align:center;color:var(--muted);font-size:0.88rem;margin-bottom:32px;}
.form-group{margin-bottom:18px;}
label{display:block;font-size:0.78rem;font-weight:800;color:var(--muted);margin-bottom:7px;text-transform:uppercase;letter-spacing:0.5px;}
.input-wrap{position:relative;}
.input-wrap input{width:100%;background:var(--card2);border:1.5px solid rgba(255,255,255,0.08);border-radius:12px;padding:13px 48px 13px 16px;color:var(--text);font-family:'Nunito',sans-serif;font-size:0.95rem;outline:none;transition:border-color 0.2s;}
.input-wrap input:focus{border-color:var(--purple);}
.toggle-pw{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--muted);padding:4px;}
.pw-strength{margin-top:6px;height:4px;border-radius:2px;background:rgba(255,255,255,0.07);overflow:hidden;}
.pw-strength-bar{height:100%;border-radius:2px;transition:all 0.3s;width:0%;}
.auth-btn{width:100%;padding:15px;border:none;border-radius:14px;cursor:pointer;font-family:'Nunito',sans-serif;font-weight:800;font-size:1rem;background:linear-gradient(135deg,var(--purple),var(--pink));color:#fff;transition:transform 0.2s;margin-top:6px;}
.auth-btn:hover{transform:translateY(-2px);}
.hint{font-size:0.78rem;color:var(--muted);margin-top:16px;text-align:center;line-height:1.6;}
.hint strong{color:var(--yellow);}
header{position:sticky;top:0;z-index:100;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:72px;border-bottom:1px solid rgba(255,255,255,0.06);background:rgba(13,13,26,0.92);backdrop-filter:blur(12px);}
.logo{font-family:'Boogaloo',cursive;font-size:1.8rem;background:linear-gradient(90deg,var(--pink),var(--blue));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hbtn{color:var(--muted);text-decoration:none;font-weight:700;font-size:0.85rem;padding:6px 14px;border-radius:20px;border:1.5px solid rgba(255,255,255,0.1);transition:all 0.2s;margin-left:8px;}
.hbtn:hover{color:var(--text);border-color:var(--purple);}
.hbtn.logout{border-color:rgba(255,85,85,0.3);color:var(--red);}
.hbtn.logout:hover{background:rgba(255,85,85,0.1);}
.page{position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:36px 20px 80px;}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;margin-bottom:36px;}
.stat-card{background:var(--card);border:1.5px solid rgba(255,255,255,0.07);border-radius:16px;padding:20px;}
.stat-num{font-family:'Boogaloo',cursive;font-size:2.2rem;}
.stat-num.y{color:var(--yellow);}.stat-num.g{color:var(--green);}.stat-num.p{color:var(--pink);}.stat-num.b{color:var(--blue);}
.stat-lbl{color:var(--muted);font-size:0.82rem;margin-top:4px;}
.layout{display:grid;grid-template-columns:420px 1fr;gap:30px;align-items:start;}
@media(max-width:820px){.layout{grid-template-columns:1fr;}}
.card{background:var(--card);border:1.5px solid rgba(255,255,255,0.08);border-radius:20px;padding:28px;}
.card-title{font-family:'Boogaloo',cursive;font-size:1.5rem;margin-bottom:22px;}
.tabs{display:flex;gap:6px;margin-bottom:22px;}
.tab{background:var(--card2);border:1.5px solid rgba(255,255,255,0.07);color:var(--muted);border-radius:10px;padding:8px 16px;cursor:pointer;font-family:'Nunito',sans-serif;font-weight:700;font-size:0.85rem;transition:all 0.2s;}
.tab.active{background:rgba(155,93,229,0.15);border-color:var(--purple);color:var(--text);}
.tab-content{display:none;}.tab-content.active{display:block;}
.fg{margin-bottom:18px;}
.fg label{display:block;font-size:0.78rem;font-weight:800;color:var(--muted);margin-bottom:7px;text-transform:uppercase;letter-spacing:0.5px;}
.fg input[type=text],.fg textarea{width:100%;background:var(--card2);border:1.5px solid rgba(255,255,255,0.08);border-radius:10px;padding:11px 14px;color:var(--text);font-family:'Nunito',sans-serif;font-size:0.92rem;outline:none;transition:border-color 0.2s;}
.fg input:focus,.fg textarea:focus{border-color:var(--purple);}
.fg textarea{resize:vertical;min-height:75px;}
.fg .input-wrap input{border-radius:10px;padding:11px 48px 11px 14px;}
.type-selector{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.type-opt{display:none;}
.type-label{border:2px solid rgba(255,255,255,0.1);border-radius:12px;padding:14px;cursor:pointer;text-align:center;transition:all 0.2s;background:var(--card2);}
.type-label .ti{font-size:1.8rem;display:block;margin-bottom:6px;}
.type-label .tn{font-weight:800;font-size:0.88rem;}
.type-label .th{font-size:0.72rem;color:var(--muted);margin-top:3px;}
#typeHtml:checked+.type-label{border-color:var(--green);background:rgba(0,245,212,0.08);}
#typeExe:checked+.type-label{border-color:var(--orange);background:rgba(241,91,181,0.08);}
.file-drop{border:2px dashed rgba(255,255,255,0.12);border-radius:12px;padding:20px;text-align:center;cursor:pointer;transition:border-color 0.2s;background:var(--card2);position:relative;}
.file-drop:hover{border-color:var(--purple);}
.file-drop input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
.file-drop .fi{font-size:2rem;margin-bottom:6px;display:block;}
.file-drop .ft{font-size:0.83rem;color:var(--muted);}
.file-name{font-size:0.8rem;color:var(--green);margin-top:6px;font-weight:700;min-height:16px;}
.sbtn{width:100%;padding:14px;border:none;border-radius:12px;cursor:pointer;font-family:'Nunito',sans-serif;font-weight:800;font-size:0.95rem;background:linear-gradient(135deg,var(--purple),var(--pink));color:#fff;transition:transform 0.2s;}
.sbtn:hover{transform:translateY(-2px);}
.sbtn.green{background:linear-gradient(135deg,var(--green),var(--blue));color:#0d0d1a;}
.msg{padding:13px 17px;border-radius:12px;margin-bottom:20px;font-weight:700;font-size:0.88rem;}
.msg.success{background:rgba(0,245,212,0.1);border:1.5px solid var(--green);color:var(--green);}
.msg.error{background:rgba(255,85,85,0.1);border:1.5px solid var(--red);color:var(--red);}
.game-list{display:flex;flex-direction:column;gap:12px;max-height:680px;overflow-y:auto;padding-right:4px;}
.game-list::-webkit-scrollbar{width:4px;}
.game-list::-webkit-scrollbar-thumb{background:var(--purple);border-radius:4px;}
.game-row{background:var(--card2);border-radius:14px;padding:14px;display:flex;align-items:center;gap:14px;border:1.5px solid transparent;transition:border-color 0.2s;}
.game-row:hover{border-color:rgba(255,255,255,0.08);}
.row-thumb{width:56px;height:56px;border-radius:10px;flex-shrink:0;background:var(--card);display:flex;align-items:center;justify-content:center;font-size:1.6rem;overflow:hidden;}
.row-thumb img{width:100%;height:100%;object-fit:cover;}
.row-info{flex:1;min-width:0;}
.row-title{font-weight:800;font-size:0.92rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.row-meta{font-size:0.76rem;color:var(--muted);margin-top:4px;display:flex;gap:8px;flex-wrap:wrap;}
.badge{padding:2px 8px;border-radius:6px;font-size:0.68rem;font-weight:800;text-transform:uppercase;}
.badge.html{background:var(--green);color:#0d0d1a;}.badge.exe{background:var(--orange);color:#fff;}
.row-btns{display:flex;gap:7px;flex-shrink:0;}
.rbtn{padding:5px 11px;border-radius:7px;font-size:0.76rem;font-weight:800;text-decoration:none;border:none;cursor:pointer;font-family:'Nunito',sans-serif;transition:all 0.2s;}
.rbtn.view{background:rgba(0,187,249,0.13);color:var(--blue);}.rbtn.view:hover{background:rgba(0,187,249,0.23);}
.rbtn.del{background:rgba(255,85,85,0.11);color:var(--red);}.rbtn.del:hover{background:rgba(255,85,85,0.2);}
.empty-list{text-align:center;padding:40px;color:var(--muted);}
.upload-progress{display:none;margin-top:12px;background:var(--card2);border-radius:10px;padding:12px;text-align:center;color:var(--muted);font-size:0.85rem;}
</style>
</head>
<body>

<?php if ($view==='setup'): ?>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">🎮 atlasalan.fun</div>
    <div class="auth-sub">İlk kurulum — admin şifreni belirle</div>
    <?php if($msg):?><div class="msg <?=$msgType?>"><?=$msg?></div><?php endif;?>
    <form method="POST">
      <input type="hidden" name="action" value="setup">
      <div class="form-group"><label>Şifre</label>
        <div class="input-wrap"><input type="password" name="password" id="pw1" placeholder="En az 6 karakter" oninput="checkStr(this.value)"><button type="button" class="toggle-pw" onclick="togglePw('pw1',this)">👁</button></div>
        <div class="pw-strength"><div class="pw-strength-bar" id="sBar"></div></div>
      </div>
      <div class="form-group"><label>Tekrar</label>
        <div class="input-wrap"><input type="password" name="password2" id="pw2" placeholder="Aynı şifreyi gir"><button type="button" class="toggle-pw" onclick="togglePw('pw2',this)">👁</button></div>
      </div>
      <button type="submit" class="auth-btn">🔐 Kaydet &amp; Giriş Yap</button>
    </form>
    <div class="hint">Bu sayfa sadece <strong>bir kez</strong> görünür.</div>
  </div>
</div>

<?php elseif ($view==='login'): ?>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">🎮 atlasalan.fun</div>
    <div class="auth-sub">Admin Paneli</div>
    <?php if($msg):?><div class="msg <?=$msgType?>"><?=$msg?></div><?php endif;?>
    <form method="POST">
      <input type="hidden" name="action" value="login">
      <div class="form-group"><label>Şifre</label>
        <div class="input-wrap"><input type="password" name="password" id="pwL" autofocus><button type="button" class="toggle-pw" onclick="togglePw('pwL',this)">👁</button></div>
      </div>
      <button type="submit" class="auth-btn">🔓 Giriş Yap</button>
    </form>
    <div class="hint">Şifreni mi unuttun? <strong>data/config.php</strong>'yi sil.</div>
  </div>
</div>

<?php else: ?>
<header>
  <div class="logo">🔧 Admin</div>
  <div>
    <a href="index.php" target="_blank" class="hbtn">🎮 Siteyi Gör</a>
    <a href="admin.php?logout=1" class="hbtn logout" onclick="return confirm('Çıkış?')">🚪 Çıkış</a>
  </div>
</header>
<div class="page">
  <div class="stats-row">
    <div class="stat-card"><div class="stat-num y"><?=count($games)?></div><div class="stat-lbl">🎮 Toplam</div></div>
    <div class="stat-card"><div class="stat-num g"><?=count(array_filter($games,fn($g)=>$g['type']==='html'))?></div><div class="stat-lbl">🌐 HTML</div></div>
    <div class="stat-card"><div class="stat-num p"><?=count(array_filter($games,fn($g)=>$g['type']==='exe'))?></div><div class="stat-lbl">💾 EXE</div></div>
    <div class="stat-card"><div class="stat-num b"><?=$totalDl?></div><div class="stat-lbl">📥 İndirme</div></div>
  </div>
  <?php if($msg):?><div class="msg <?=$msgType?>"><?=$msg?></div><?php endif;?>
  <div class="layout">
    <div class="card">
      <div class="tabs">
        <button class="tab active" onclick="switchTab('upload',this)">⬆ Oyun Yükle</button>
        <button class="tab" onclick="switchTab('password',this)">🔑 Şifre</button>
      </div>
      <div class="tab-content active" id="tab-upload">
        <form method="POST" enctype="multipart/form-data" onsubmit="showProgress()">
          <input type="hidden" name="action" value="upload">
          <div class="fg"><label>Tür</label>
            <div class="type-selector">
              <input type="radio" name="type" id="typeHtml" value="html" class="type-opt" checked>
              <label for="typeHtml" class="type-label"><span class="ti">🌐</span><span class="tn">HTML</span><span class="th">.html/.zip</span></label>
              <input type="radio" name="type" id="typeExe" value="exe" class="type-opt">
              <label for="typeExe" class="type-label"><span class="ti">💾</span><span class="tn">EXE</span><span class="th">.exe/.zip</span></label>
            </div>
          </div>
          <div class="fg"><label>Oyun Adı *</label><input type="text" name="title" placeholder="Uzay Savaşı 3000" required></div>
          <div class="fg"><label>Açıklama</label><textarea name="desc" placeholder="Oyunu kısaca anlat..."></textarea></div>
          <div class="fg"><label>Oyun Dosyası *</label>
            <div class="file-drop"><input type="file" name="gamefile" accept=".html,.htm,.exe,.zip" required onchange="setFN(this,'gfn')">
              <span class="fi">📁</span><div class="ft">Sürükle veya tıkla</div><div class="file-name" id="gfn"></div></div>
          </div>
          <div class="fg"><label>Kapak (opsiyonel)</label>
            <div class="file-drop"><input type="file" name="thumb" accept="image/*" onchange="setFN(this,'tfn')">
              <span class="fi">🖼️</span><div class="ft">JPG, PNG, GIF, WEBP</div><div class="file-name" id="tfn"></div></div>
          </div>
          <button type="submit" class="sbtn">⬆ R2'ye Yükle</button>
          <div class="upload-progress" id="progress">⏳ Cloudflare R2'ye yükleniyor, lütfen bekle...</div>
        </form>
      </div>
      <div class="tab-content" id="tab-password">
        <form method="POST">
          <input type="hidden" name="action" value="change_password">
          <div class="fg"><label>Mevcut Şifre</label><div class="input-wrap"><input type="password" name="old_password" id="p0"><button type="button" class="toggle-pw" onclick="togglePw('p0',this)">👁</button></div></div>
          <div class="fg"><label>Yeni Şifre</label><div class="input-wrap"><input type="password" name="new_password" id="p1" oninput="checkStr(this.value)"><button type="button" class="toggle-pw" onclick="togglePw('p1',this)">👁</button></div>
            <div class="pw-strength"><div class="pw-strength-bar" id="sBar"></div></div></div>
          <div class="fg"><label>Tekrar</label><div class="input-wrap"><input type="password" name="new_password2" id="p2"><button type="button" class="toggle-pw" onclick="togglePw('p2',this)">👁</button></div></div>
          <button type="submit" class="sbtn green">🔑 Değiştir</button>
        </form>
      </div>
    </div>
    <div class="card">
      <div class="card-title">🗂 Oyunlar <span style="font-size:1rem;color:var(--muted);font-family:'Nunito'">(<?=count($games)?>)</span></div>
      <?php if(empty($games)):?>
        <div class="empty-list"><div style="font-size:3rem;margin-bottom:12px">👾</div><p>Henüz oyun yok</p></div>
      <?php else:?>
        <div class="game-list">
          <?php foreach(array_reverse($games) as $g):?>
          <div class="game-row">
            <div class="row-thumb">
              <?php if(!empty($g['thumb'])):?><img src="serve.php?thumb=<?=urlencode($g['thumb'])?>" alt="">
              <?php else:?><?=$g['type']==='html'?'🌐':'💾'?><?php endif;?>
            </div>
            <div class="row-info">
              <div class="row-title"><?=htmlspecialchars($g['title'])?></div>
              <div class="row-meta">
                <span class="badge <?=$g['type']?>"><?=strtoupper($g['type'])?></span>
                <span>📥 <?=$g['downloads']??0?></span>
                <?php if(!empty($g['size'])):?><span>📦 <?=htmlspecialchars($g['size'])?></span><?php endif;?>
                <span>📅 <?=htmlspecialchars($g['date']??'')?></span>
              </div>
            </div>
            <div class="row-btns">
              <a href="game.php?id=<?=urlencode($g['id'])?>" target="_blank" class="rbtn view">👁</a>
              <a href="admin.php?delete=<?=urlencode($g['id'])?>" class="rbtn del" onclick="return confirm('<?=addslashes($g['title'])?> silinsin mi?')">🗑</a>
            </div>
          </div>
          <?php endforeach;?>
        </div>
      <?php endif;?>
    </div>
  </div>
</div>
<?php endif;?>
<script>
function togglePw(id,btn){const el=document.getElementById(id);if(!el)return;const s=el.type==='password';el.type=s?'text':'password';btn.textContent=s?'🙈':'👁';}
function checkStr(v){const b=document.getElementById('sBar');if(!b)return;let s=0;if(v.length>=6)s++;if(v.length>=10)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;const c=['','#ff5555','#f15bb5','#fee440','#00bbf9','#00f5d4'];const w=['0%','20%','40%','60%','80%','100%'];b.style.width=w[s]||'0%';b.style.background=c[s]||'transparent';}
function setFN(input,id){const el=document.getElementById(id);if(el&&input.files[0])el.textContent='✅ '+input.files[0].name;}
function switchTab(n,btn){document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));btn.classList.add('active');document.getElementById('tab-'+n).classList.add('active');}
function showProgress(){document.getElementById('progress').style.display='block';}
</script>
</body>
</html>
