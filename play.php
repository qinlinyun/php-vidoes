<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();
$user = getCurrentUser();

$videoId = $_GET['id'] ?? 0;
if (!$videoId) { header('Location: index.php'); exit; }

$pdo = getDB();

/* 视频信息 */
$stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
$stmt->execute([$videoId]);
$video = $stmt->fetch();
if (!$video) { header('Location: index.php'); exit; }

/* 集数 */
$stmt = $pdo->prepare("SELECT * FROM video_episodes WHERE video_id = ? ORDER BY episode_order");
$stmt->execute([$videoId]);
$episodes = $stmt->fetchAll();

/* 域名（用户组） */
$stmt = $pdo->prepare("
    SELECT d.id, d.domain, d.display_name
    FROM domains d
    JOIN group_domains gd ON d.id = gd.domain_id
    WHERE gd.group_id = ?
    ORDER BY d.id
");
$stmt->execute([$user['group_id']]);
$groupDomains = $stmt->fetchAll();

/* 所有域名（管理员） */
$allDomains = [];
if (isAdmin()) {
    $allDomains = $pdo->query("SELECT * FROM domains ORDER BY id")->fetchAll();
}

/* 当前域名 */
$currentDomainId = $_GET['domain_id'] ?? ($groupDomains[0]['id'] ?? null);
$currentDomain = null;
if ($currentDomainId) {
    $stmt = $pdo->prepare("SELECT * FROM domains WHERE id = ?");
    $stmt->execute([$currentDomainId]);
    $currentDomain = $stmt->fetch();
}
if (!$currentDomain && !empty($allDomains)) {
    $currentDomain = $allDomains[0];
    $currentDomainId = $currentDomain['id'];
}

/* 当前集 */
$episodeId = $_GET['episode'] ?? ($episodes[0]['id'] ?? 0);
$currentEpisode = null;
foreach ($episodes as $ep) {
    if ($ep['id'] == $episodeId) {
        $currentEpisode = $ep;
        break;
    }
}
if (!$currentEpisode && !empty($episodes)) {
    $currentEpisode = $episodes[0];
    $episodeId = $currentEpisode['id'];
}

/* 播放地址 + MIME */
$playUrl = '';
$mime = 'video/mp4';
if ($currentEpisode && $currentDomain) {
    $path = '/' . ltrim($currentEpisode['video_url'], '/');
    $playUrl = 'https://' . rtrim($currentDomain['domain'], '/') . $path;

    if (preg_match('/\.m3u8(\?.*)?$/i', $playUrl)) {
        $mime = 'application/x-mpegURL';
    }
}

/* 从观看进度页面跳转过来的定位时间 t=秒（优先） */
$jumpT = (int)($_GET['t'] ?? 0);
if ($jumpT < 0) $jumpT = 0;
?>
<!DOCTYPE html>
<html lang="zh-CN" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?=htmlspecialchars($video['title'])?> - 播放</title>

<!-- 你原来的静态资源：不动 -->
<script src="https://css.qinlinyun.cn/uploads/css/68cd3c66163ff-20260129212429-5fce6eb9.css?v=20260129212429"></script>

<!-- ✅ video.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/video.js@8.16.1/dist/video-js.min.css">

<style>
/* ================= 可选背景 ================= */
:root{
    --bg-pc: url("");
    --bg-mobile: url("");
}
body{
    background:
        linear-gradient(rgba(0,0,0,.25),rgba(0,0,0,.25)),
        var(--bg-pc);
    background-size: cover;
    background-attachment: fixed;
}
@media (max-width:768px){
    body{
        background:
            linear-gradient(rgba(0,0,0,.25),rgba(0,0,0,.25)),
            var(--bg-mobile);
        background-attachment: scroll;
    }
}

/* ================= 暗色模式 ================= */
.dark body{background:#0f172a;color:#e5e7eb}
.dark .bg-white{
    background:rgba(30,41,59,.75)!important;
    backdrop-filter: blur(14px);
}
.dark .text-gray-900{color:#e5e7eb}
.dark .text-gray-600,
.dark .text-gray-500{color:#9ca3af}

/* ================= select（方案 A） ================= */
.dark select{
    background:#0f172a;
    color:#e5e7eb;
    border-color:#334155;
}
.dark select option{
    background:#0f172a;
    color:#e5e7eb;
}
.dark select:focus{
    outline:none;
    border-color:#ef4444;
    box-shadow:0 0 0 1px rgba(239,68,68,.4);
}

/* ================= 选集暗色 ================= */
.dark .episode-item{
    color:#e5e7eb;
    border-color:#334155;
}
.dark .episode-item:hover{
    background:rgba(51,65,85,.4);
}
.dark .episode-active{
    background:rgba(239,68,68,.15);
    color:#ef4444;
}

/* ================= 动画 ================= */
.fade-up{
    opacity:0;
    transform: translateY(24px);
    transition: .6s ease;
}
.fade-up.show{
    opacity:1;
    transform:none;
}

/* ✅ video.js 适配容器 */
.video-js{ width:100% !important; height:100% !important; }
/* ✅ 防止控制条被覆盖 */
.video-js .vjs-control-bar{ display:flex !important; }
.video-js .vjs-progress-control{ display:flex !important; }
.video-js .vjs-slider{ display:block !important; }
</style>
</head>

<body class="bg-gray-100 text-gray-900">

<nav class="bg-white shadow-sm sticky top-0 z-50">
<div class="max-w-screen-xl mx-auto px-4 py-3 flex justify-between items-center">

<!-- 左侧导航 -->
<div class="flex items-center gap-4 text-sm">

<?php if (isAdmin()): ?>
    <a href="/" class="font-semibold">前台首页</a>
    <a href="admin/groups.php">分组管理</a>
    <a href="admin/users.php">用户管理</a>
    <a href="admin/videos.php">视频管理</a>
    <a href="admin/domains.php">线路管理</a>
<?php else: ?>
    <a href="/">首页</a>
    <a href="profile.php"><?=htmlspecialchars($user['username'])?></a>
    <a href="progress.php">观看进度</a>
<?php endif; ?>

    <a href="logout.php" class="text-red-500">退出</a>
</div>

<!-- 右侧 -->
<div class="flex items-center gap-3">
    <?php if (isAdmin()): ?>
        <span class="text-xs text-gray-500">管理员</span>
    <?php endif; ?>
    <button id="darkToggle" title="切换暗色">🌙</button>
</div>

</div>
</nav>

<main class="max-w-screen-xl mx-auto px-4 py-6 fade-up">
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

<!-- 左侧播放器 -->
<section class="lg:col-span-2 space-y-4">
<div class="bg-white rounded shadow p-4 fade-up">
<div class="aspect-video bg-black rounded overflow-hidden">
<?php if ($playUrl): ?>
<video
    id="videoPlayer"
    class="video-js vjs-default-skin w-full h-full"
    controls
    autoplay
    preload="auto"
    playsinline
    controlsList="nodownload"
    disablePictureInPicture
    oncontextmenu="return false;"
    data-setup='{}'
>
    <source src="<?=htmlspecialchars($playUrl)?>" type="<?=htmlspecialchars($mime)?>">
</video>
<?php else: ?>
<div class="flex h-full items-center justify-center text-white">暂无可用视频源</div>
<?php endif; ?>
</div>
</div>

<div class="bg-white rounded shadow p-4 fade-up">
<h1 class="text-lg font-semibold"><?=htmlspecialchars($video['title'])?></h1>
<?php if ($video['description']): ?>
<p class="mt-3 text-sm leading-7"><?=nl2br(htmlspecialchars($video['description']))?></p>
<?php endif; ?>
</div>
</section>

<!-- 右侧 -->
<aside class="space-y-4 fade-up">

<!-- 线路切换 -->
<?php if ((isAdmin() && !empty($allDomains)) || !empty($groupDomains)): ?>
<div class="bg-white rounded shadow">
<div class="border-b px-4 py-3 text-sm font-semibold">线路切换</div>
<div class="p-4">
<select class="w-full rounded border px-3 py-2 text-sm"
onchange="switchDomain(this.value)">
<?php foreach ((isAdmin() && $allDomains) ? $allDomains : $groupDomains as $d): ?>
<option value="<?=$d['id']?>" <?=$d['id']==$currentDomainId?'selected':''?>>
<?=htmlspecialchars($d['display_name'] ?: ('线路'.$d['id']))?>
</option>
<?php endforeach; ?>
</select>
</div>
</div>
<?php endif; ?>

<!-- 选集 -->
<?php if (!empty($episodes)): ?>
<div class="bg-white rounded shadow">
<div class="flex justify-between items-center border-b px-4 py-3">
<h3 class="text-sm font-semibold">视频选集</h3>
<span class="text-xs text-gray-500"><?=count($episodes)?> 集</span>
</div>

<div class="max-h-[600px] overflow-y-auto">
<?php foreach ($episodes as $ep): ?>
<?php
$name = trim($ep['episode_name']);
if (preg_match('/^\d+$/', $name)) $name = '第'.$name.'集';
?>
<a
href="?id=<?=$videoId?>&episode=<?=$ep['id']?>&domain_id=<?=$currentDomainId?>"
class="episode-item block border-b px-4 py-3 text-sm truncate
<?=$ep['id']==$episodeId?'episode-active':''?>">
<?=htmlspecialchars($name)?>
</a>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

</aside>
</div>
</main>

<!-- ✅ video.js + HLS(VHS) -->
<script src="https://cdn.jsdelivr.net/npm/video.js@8.16.1/dist/video.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@videojs/http-streaming@3.17.2/dist/videojs-http-streaming.min.js"></script>

<script>
/* 暗色模式 */
const html = document.documentElement;
if (localStorage.theme === 'dark') html.classList.add('dark');
darkToggle.onclick = () => {
    html.classList.toggle('dark');
    localStorage.theme = html.classList.contains('dark') ? 'dark' : 'light';
};

/* 动画 */
const ob = new IntersectionObserver(es=>{
    es.forEach(e=>e.isIntersecting && e.target.classList.add('show'))
},{threshold:.15});
document.querySelectorAll('.fade-up').forEach(el=>ob.observe(el));

function switchDomain(id){
    const u = new URL(location.href);
    u.searchParams.set('domain_id', id);
    location.href = u.toString();
}

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('videoPlayer');
    if (!el || typeof videojs === 'undefined') return;

    window.player = videojs(el, {
        controls: true,
        autoplay: true,
        preload: 'auto',
        fluid: true,
        responsive: true,

        // ✅ 倍速
        playbackRates: [0.5, 0.75, 1, 1.25, 1.5, 2],

        // ✅ 控制条：确保进度条/暂停/倍速等显示
        controlBar: {
            children: [
                'playToggle',
                'volumePanel',
                'currentTimeDisplay',
                'timeDivider',
                'durationDisplay',
                'progressControl',
                'remainingTimeDisplay',
                'playbackRateMenuButton',
                'fullscreenToggle'
            ]
        },

        html5: {
            vhs: { enableLowInitialPlaylist: true },
            nativeAudioTracks: false,
            nativeVideoTracks: false
        }
    });

    // ✅ 禁右键（video.js 容器也禁）
    const wrap = el.closest('.video-js') || el.parentElement;
    if (wrap) wrap.addEventListener('contextmenu', e => e.preventDefault());

    // ----------------------------
    // ✅ 观看进度：定位 & 自动续播
    // ----------------------------
    const VIDEO_ID = <?= (int)$videoId ?>;
    const EPISODE_ID = <?= (int)$episodeId ?>;
    const JUMP_T = <?= (int)$jumpT ?>;

    // 1) 若 URL 带 t=秒：优先跳转到该位置
    if (JUMP_T > 0) {
        window.player.one('loadedmetadata', () => {
            const duration = window.player.duration() || 0;
            const t = duration > 0 ? Math.min(JUMP_T, Math.max(0, duration - 1)) : JUMP_T;
            if (t > 0) window.player.currentTime(t);
        });
    }

    // 2) 否则：从数据库读取历史进度自动续播（管理员已在 save_progress 接口排除，不会写入）
    if (JUMP_T <= 0) {
        fetch(`api/get_progress.php?video_id=${VIDEO_ID}&episode_id=${EPISODE_ID}`, { credentials: 'same-origin' })
          .then(r => r.json())
          .then(data => {
            if (!data.ok) return;
            const p = Number(data.progress || 0);

            window.player.one('loadedmetadata', () => {
              const duration = window.player.duration() || 0;
              // 超过 5 秒才续播，且避免跳到片尾
              if (p > 5 && (!duration || p < duration - 10)) {
                window.player.currentTime(p);
              }
            });
          })
          .catch(()=>{});
    }

    // 3) 定时保存进度（10秒一次；暂停/退出也保存）
    let lastSentAt = 0;

    function sendProgress(force=false) {
      const now = Date.now();
      if (!force && now - lastSentAt < 10000) return;
      lastSentAt = now;

      const progress = Math.floor(window.player.currentTime() || 0);
      const duration = Math.floor(window.player.duration() || 0);

      const fd = new FormData();
      fd.append('video_id', VIDEO_ID);
      fd.append('episode_id', EPISODE_ID);
      fd.append('progress', progress);
      fd.append('duration', duration);

      fetch('api/save_progress.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      }).catch(()=>{});
    }

    window.player.on('timeupdate', () => sendProgress(false));
    window.player.on('pause', () => sendProgress(true));
    window.player.on('ended', () => sendProgress(true));
    window.addEventListener('beforeunload', () => sendProgress(true));
});
</script>

</body>
</html>
