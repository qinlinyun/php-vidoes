<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人中心 - 竹叶云控平台</title>
    <script src="https://css.qinlinyun.cn/cj/68cd3c66163ff.css"></script>
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
                <a class="rounded-full bg-gray-100 px-3 py-1" href="profile.php"><?php echo htmlspecialchars($user['username']); ?></a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="logout.php">退出</a>
            </div>
        </div>
    </nav>
    
    <main class="mx-auto max-w-screen-xl px-4 py-6">
        <h1 class="mb-4 text-lg font-semibold">个人中心</h1>
        <div class="rounded-lg bg-white p-6 shadow">
            <h2 class="mb-4 text-base font-semibold">个人信息</h2>
            <div class="divide-y text-sm">
                <div class="flex items-center justify-between py-3">
                    <span class="text-gray-500">用户名</span>
                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="flex items-center justify-between py-3">
                    <span class="text-gray-500">邮箱</span>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="flex items-center justify-between py-3">
                    <span class="text-gray-500">用户组</span>
                    <span><?php echo htmlspecialchars($user['group_name'] ?? '未分组'); ?></span>
                </div>
                <div class="flex items-center justify-between py-3">
                    <span class="text-gray-500">状态</span>
                    <span>
                        <?php
                        $statusText = ['active' => '正常', 'banned' => '已封禁', 'frozen' => '已冻结'];
                        echo $statusText[$user['status']] ?? '未知';
                        ?>
                    </span>
                </div>
            </div>
            <div class="mt-6">
                <a href="change_password.php" class="inline-block rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">修改密码</a>
            </div>
        </div>
    </main>
</body>
</html>

