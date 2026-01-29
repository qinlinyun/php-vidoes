<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();
$user = getCurrentUser();

$videoId = $_GET['id'] ?? 0;
if (!$videoId) {
    header('Location: index.php');
    exit;
}

$pdo = getDB();

// 获取视频信息
$stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
$stmt->execute([$videoId]);
$video = $stmt->fetch();

if (!$video) {
    header('Location: index.php');
    exit;
}

// 获取视频集数
$stmt = $pdo->prepare("SELECT * FROM video_episodes WHERE video_id = ? ORDER BY episode_order");
$stmt->execute([$videoId]);
$episodes = $stmt->fetchAll();

// 获取当前用户组的域名
$stmt = $pdo->prepare("SELECT d.id, d.domain, d.display_name FROM domains d 
                       INNER JOIN group_domains gd ON d.id = gd.domain_id 
                       WHERE gd.group_id = ? 
                       ORDER BY d.id");
$stmt->execute([$user['group_id']]);
$groupDomains = $stmt->fetchAll();

// 获取所有域名（管理员可以切换）
$allDomains = [];
if (isAdmin()) {
    $stmt = $pdo->query("SELECT * FROM domains ORDER BY id");
    $allDomains = $stmt->fetchAll();
}

// 当前使用的域名
$currentDomainId = $_GET['domain_id'] ?? ($groupDomains[0]['id'] ?? null);
$currentDomain = null;

if ($currentDomainId) {
    $stmt = $pdo->prepare("SELECT * FROM domains WHERE id = ?");
    $stmt->execute([$currentDomainId]);
    $currentDomain = $stmt->fetch();
}

// 如果没有分配域名，使用第一个可用域名
if (!$currentDomain && !empty($allDomains)) {
    $currentDomain = $allDomains[0];
    $currentDomainId = $currentDomain['id'];
}

// 当前播放的集数
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

// 构建播放URL
$playUrl = '';
if ($currentEpisode && $currentDomain) {
    $videoPath = $currentEpisode['video_url'];
    // 确保路径以 / 开头
    if (substr($videoPath, 0, 1) !== '/') {
        $videoPath = '/' . $videoPath;
    }
    $domain = rtrim($currentDomain['domain'], '/');
    $playUrl = 'https://' . $domain . $videoPath;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title']); ?> - 播放</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
    <nav class="bg-white shadow-sm">
        <div class="mx-auto max-w-screen-xl px-4 py-3">
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="index.php">首页</a>
                <?php if (isAdmin()): ?>
                    <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="admin/users.php">用户管理</a>
                    <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="admin/groups.php">分组管理</a>
                    <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="admin/domains.php">域名管理</a>
                    <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="admin/videos.php">视频管理</a>
                <?php endif; ?>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="profile.php"><?php echo htmlspecialchars($user['username']); ?></a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="logout.php">退出</a>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-screen-xl px-4 py-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- 左侧：视频区域 -->
            <section class="lg:col-span-2 space-y-4">
                <!-- 播放器卡片 -->
                <div class="rounded-lg bg-white p-4 shadow">
                    <div class="aspect-video w-full overflow-hidden rounded-md bg-black">
                        <?php if ($playUrl): ?>
                            <video id="videoPlayer" controls autoplay class="h-full w-full">
                                <source src="<?php echo htmlspecialchars($playUrl); ?>" type="video/mp4">
                                您的浏览器不支持视频播放。
                            </video>
                        <?php else: ?>
                            <div class="flex h-full w-full items-center justify-center text-white">暂无可用视频源</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 标题与简介卡片（播放器下方） -->
                <div class="rounded-lg bg-white p-4 shadow">
                    <h1 class="text-lg font-semibold leading-relaxed"><?php echo htmlspecialchars($video['title']); ?></h1>
                    <?php if ($video['description']): ?>
                        <p class="mt-3 text-sm leading-7 text-gray-600"><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- 右侧：线路切换 + 选集 -->
            <aside class="space-y-4">
                <?php if ((isAdmin() && !empty($allDomains)) || (!empty($groupDomains))): ?>
                    <div class="rounded-lg bg-white shadow">
                        <div class="flex items-center justify-between border-b px-4 py-3">
                            <h3 class="text-sm font-semibold">线路切换</h3>
                        </div>
                        <div class="p-4">
                            <select id="domainSelect" onchange="switchDomain(this.value)"
                                    class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                                <?php 
                                $displayDomains = isAdmin() && !empty($allDomains) ? $allDomains : $groupDomains;
                                foreach ($displayDomains as $domain): 
                                ?>
                                    <option value="<?php echo $domain['id']; ?>" <?php echo $domain['id'] == $currentDomainId ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($domain['display_name'] ?: ('线路' . $domain['id'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($episodes)): ?>
                    <div class="rounded-lg bg-white shadow">
                        <div class="flex items-center justify-between border-b px-4 py-3">
                            <h3 class="text-sm font-semibold">视频选集</h3>
                            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600"><?php echo count($episodes); ?>集</span>
                        </div>
                        <div class="max-h-[600px] overflow-y-auto">
                            <?php foreach ($episodes as $episode): ?>
                                <?php
                                $displayName = trim($episode['episode_name']);
                                if (preg_match('/^\d+$/', $displayName)) {
                                    $displayName = '第' . $displayName . '集';
                                }
                                ?>
                                <a href="?id=<?php echo $videoId; ?>&episode=<?php echo $episode['id']; ?>&domain_id=<?php echo $currentDomainId; ?>"
                                   class="flex items-center border-b px-4 py-3 text-sm hover:bg-gray-50 <?php echo $episode['id'] == $episodeId ? 'bg-red-50 text-red-600' : 'text-gray-800'; ?>">
                                    <span class="truncate"><?php echo htmlspecialchars($displayName); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </main>

    <script>
        function switchDomain(domainId) {
            const url = new URL(window.location.href);
            url.searchParams.set('domain_id', domainId);
            window.location.href = url.toString();
        }

        const videoPlayer = document.getElementById('videoPlayer');
        if (videoPlayer) {
            videoPlayer.addEventListener('error', function() {
                alert('视频加载失败，请尝试切换线路');
            });
        }
    </script>
</body>
</html>

