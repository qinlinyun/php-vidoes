<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();
$user = getCurrentUser();

// 获取视频列表
$pdo = getDB();
$stmt = $pdo->query("SELECT * FROM videos ORDER BY created_at DESC");
$videos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>首页 - 影视系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
    <nav class="bg-white shadow-sm">
        <div class="mx-auto max-w-screen-xl px-4 py-3">
            <div class="flex flex-wrap items-center gap-3 text-sm">
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
        <div class="mb-6 rounded-lg bg-white p-4 shadow">
            <h1 class="text-lg font-semibold">视频列表</h1>
        </div>
        <?php if (empty($videos)): ?>
            <div class="rounded-lg bg-white p-8 text-center text-sm text-gray-500 shadow">暂无视频</div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <?php foreach ($videos as $video): ?>
                    <div class="overflow-hidden rounded-lg bg-white shadow transition hover:shadow-md">
                        <div class="aspect-video w-full bg-black">
                            <?php if ($video['cover']): ?>
                                <img class="h-full w-full object-cover" src="<?php echo htmlspecialchars($video['cover']); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>">
                            <?php else: ?>
                                <div class="flex h-full w-full items-center justify-center text-sm text-gray-300">暂无封面</div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="mb-1 line-clamp-1 text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($video['title']); ?></h3>
                            <p class="mb-3 line-clamp-2 text-xs text-gray-500"><?php echo htmlspecialchars($video['description'] ?? ''); ?></p>
                            <a href="play.php?id=<?php echo $video['id']; ?>" class="inline-block rounded bg-red-600 px-3 py-1 text-xs font-semibold text-white hover:bg-red-700">观看</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>

