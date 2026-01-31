<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();
$user = getCurrentUser();

$pdo = getDB();
$stmt = $pdo->query("SELECT * FROM videos ORDER BY created_at DESC");
$videos = $stmt->fetchAll();

$showLoginPopup = !empty($_SESSION['login_popup_pending']);
$unreadNotifications = (int)($_SESSION['unread_notification_count'] ?? 0);
$unreadFeedbackReplies = (int)($_SESSION['unread_feedback_reply_count'] ?? 0);
unset($_SESSION['login_popup_pending']);
?>
<!DOCTYPE html>
<html lang="zh-CN" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>首页 - 竹叶云控平台</title>

<!-- Tailwind CSS -->
<script src="https://css.qinlinyun.cn/uploads/css/68cd3c66163ff-20260129212429-5fce6eb9.css?v=20260129212429"></script>

<!-- 🌙 暗色模式 + 毛玻璃 + 动画 -->
<style>
/* =====================
   可选背景图片（不填不生效）
===================== */
:root{
    --bg-pc: url("");        /* PC 背景 */
    --bg-mobile: url("");    /* 手机背景 */
}

/* 背景处理 */
body{
    background:
        linear-gradient(rgba(0,0,0,.25), rgba(0,0,0,.25)),
        var(--bg-pc);
    background-size: cover;
    background-attachment: fixed;
}
@media (max-width:768px){
    body{
        background:
            linear-gradient(rgba(0,0,0,.25), rgba(0,0,0,.25)),
            var(--bg-mobile);
        background-attachment: scroll;
    }
}

/* =====================
   暗色模式
===================== */
.dark body{
    background-color:#0f172a;
    color:#e5e7eb;
}
.dark .bg-white{
    background: rgba(30,41,59,.75) !important;
    backdrop-filter: blur(12px);
    color:#e5e7eb;
}
.dark .text-gray-900{color:#e5e7eb}
.dark .text-gray-500{color:#9ca3af}

/* =====================
   毛玻璃卡片
===================== */
.glass{
    background: rgb(255, 255, 255);
    backdrop-filter: blur(14px);
}
.dark .glass{
    background: rgba(30,41,59,.65);
}

/* =====================
   进入动画
===================== */
.fade-up{
    opacity:0;
    transform: translateY(30px);
    transition: all .6s ease;
}
.fade-up.show{
    opacity:1;
    transform:none;
}

/* =====================
   登录提醒弹窗
===================== */
.popup-backdrop{
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 60;
    opacity: 0;
    pointer-events: none;
    transition: opacity .25s ease;
}
.popup-backdrop.show{
    opacity: 1;
    pointer-events: auto;
}
.popup-card{
    width: min(520px, 92vw);
    background: rgba(255,255,255,0.98);
    border-radius: 16px;
    box-shadow: 0 30px 80px rgba(15,23,42,.25);
    padding: 20px;
    transform: translateY(12px) scale(0.98);
    transition: transform .25s ease;
}
.popup-backdrop.show .popup-card{
    transform: translateY(0) scale(1);
}
.badge{
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
}
</style>
</head>

<body class="bg-gray-100 text-gray-900">

<!-- 顶部导航 -->
<nav class="bg-white/80 glass shadow-sm sticky top-0 z-50">
    <div class="mx-auto max-w-screen-xl px-4 py-3 flex justify-between items-center">
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <?php if (isAdmin()): ?>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100/50" href="admin/users.php">用户管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100/50" href="admin/groups.php">分组管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100/50" href="admin/domains.php">域名管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100/50" href="admin/videos.php">视频管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100/50" href="admin/watch_records.php">记录管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100/50" href="admin/notifications.php">站内通知</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100/50" href="admin/feedback.php">意见反馈</a>
            <?php endif; ?>
            
<?php if (!isAdmin()): ?>
    <a class="rounded-full px-3 py-1 hover:bg-gray-100/50" href="/progress.php">观看记录</a>
    <a class="rounded-full px-3 py-1 hover:bg-gray-100/50" href="notifications.php">站内通知</a>
    <a class="rounded-full px-3 py-1 hover:bg-gray-100/50" href="feedback.php">意见反馈</a>
<?php endif; ?>

            <a class="rounded-full px-3 py-1 hover:bg-gray-100/50" href="GF-token/">视频下载</a>
            <a class="rounded-full px-3 py-1 hover:bg-gray-100/50" href="profile.php"><?=htmlspecialchars($user['username'])?></a>
            <a class="rounded-full px-3 py-1 hover:bg-gray-100/50" href="logout.php">退出</a>
        </div>

        <!-- 🌙 暗色模式按钮 -->
        <button id="darkToggle" class="rounded-full px-3 py-1 text-sm hover:bg-gray-100/50">
            🌙深色模式
        </button>
    </div>
</nav>

<main class="mx-auto max-w-screen-xl px-4 py-6">

    <div class="mb-6 rounded-lg glass p-4 shadow fade-up">
        <h3 class="text-lg font-semibold">由竹叶云控平台代管理</h3>
    </div>

    <?php if (empty($videos)): ?>
        <div class="rounded-lg glass p-8 text-center text-sm shadow fade-up">暂无视频</div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($videos as $video): ?>
                <div class="fade-up">
                    <div class="overflow-hidden rounded-lg glass shadow hover:shadow-lg transition">
                        <div class="aspect-video bg-black">
                            <?php if ($video['cover']): ?>
                                <img class="h-full w-full object-cover" src="<?=htmlspecialchars($video['cover'])?>">
                            <?php else: ?>
                                <div class="flex h-full items-center justify-center text-gray-300">暂无封面</div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="mb-1 text-sm font-semibold line-clamp-1"><?=htmlspecialchars($video['title'])?></h3>
                            <p class="mb-3 text-xs text-gray-500 line-clamp-2"><?=htmlspecialchars($video['description'] ?? '')?></p>
                            <a href="play.php?id=<?=$video['id']?>" class="inline-block rounded bg-red-600 px-3 py-1 text-xs font-semibold text-white hover:bg-red-700">
                                观看
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php if ($showLoginPopup && ($unreadNotifications > 0 || $unreadFeedbackReplies > 0)): ?>
<div id="login-popup" class="popup-backdrop">
    <div class="popup-card">
        <div class="flex items-center justify-between">
            <div class="text-base font-semibold">登录提醒</div>
            <button class="text-gray-400 hover:text-gray-600" onclick="closeLoginPopup()">✕</button>
        </div>
        <div class="mt-2 text-sm text-gray-600">你有新的未读消息，请及时查看。</div>
        <div class="mt-4 flex flex-wrap gap-2">
            <?php if ($unreadNotifications > 0): ?>
                <span class="badge bg-blue-50 text-blue-600">📣 未读通知 <?= $unreadNotifications ?></span>
            <?php endif; ?>
            <?php if ($unreadFeedbackReplies > 0): ?>
                <span class="badge bg-amber-50 text-amber-600">💬 反馈回复 <?= $unreadFeedbackReplies ?></span>
            <?php endif; ?>
        </div>
        <div class="mt-5 flex flex-wrap gap-2">
            <a href="notifications.php" class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">查看通知</a>
            <a href="feedback.php" class="rounded bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-900">查看反馈</a>
            <button class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50" onclick="closeLoginPopup()">稍后提醒</button>
        </div>
    </div>
</div>
<script>
const popupEl = document.getElementById('login-popup');
if (popupEl) {
    requestAnimationFrame(() => popupEl.classList.add('show'));
}
function closeLoginPopup() {
    if (!popupEl) return;
    popupEl.classList.remove('show');
    setTimeout(() => popupEl.remove(), 200);
}
</script>
<?php endif; ?>

<!-- JS：暗色模式 + 滑动加载 -->
<script>
/* 暗色模式 */
const html = document.documentElement;
const toggle = document.getElementById('darkToggle');

if(localStorage.theme === 'dark'){
    html.classList.add('dark');
}
toggle.onclick = ()=>{
    html.classList.toggle('dark');
    localStorage.theme = html.classList.contains('dark') ? 'dark' : 'light';
};

/* 滚动加载动画 */
const observer = new IntersectionObserver(entries=>{
    entries.forEach(e=>{
        if(e.isIntersecting){
            e.target.classList.add('show');
        }
    });
},{threshold:.15});

document.querySelectorAll('.fade-up').forEach(el=>observer.observe(el));
</script>

</body>
</html>
