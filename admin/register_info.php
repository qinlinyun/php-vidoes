<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

requireAdmin();

$pdo = getDB();

// 获取用户注册信息列表
$stmt = $pdo->query("SELECT id, username, email, register_ip, register_device, created_at 
                    FROM users 
                    WHERE register_ip IS NOT NULL OR register_device IS NOT NULL
                    ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册信息 - 影视系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
    <nav class="bg-white shadow-sm">
        <div class="mx-auto max-w-screen-xl px-4 py-3">
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../index.php">首页</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="users.php">用户管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="groups.php">分组管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="domains.php">域名管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="videos.php">视频管理</a>
                <a class="rounded-full bg-gray-100 px-3 py-1" href="register_info.php">注册信息</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../logout.php">退出</a>
            </div>
        </div>
    </nav>
    
    <main class="mx-auto max-w-screen-xl px-4 py-6">
        <h1 class="mb-4 text-lg font-semibold">用户注册信息</h1>
        
        <div class="overflow-x-auto rounded-lg bg-white shadow">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">用户名</th>
                        <th class="px-4 py-3">邮箱</th>
                        <th class="px-4 py-3">注册IP</th>
                        <th class="px-4 py-3">注册设备</th>
                        <th class="px-4 py-3">注册时间</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">暂无注册信息</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-4 py-3"><?php echo $user['id']; ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars($user['register_ip'] ?? '未记录'); ?></td>
                                <td class="px-4 py-3 max-w-xs truncate"><?php echo htmlspecialchars($user['register_device'] ?? '未记录'); ?></td>
                                <td class="px-4 py-3"><?php echo $user['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>

