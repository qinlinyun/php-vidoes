<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();
$user = getCurrentUser();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = '请填写所有字段';
    } elseif (strlen($newPassword) < 6) {
        $error = '新密码长度至少6位';
    } elseif ($newPassword !== $confirmPassword) {
        $error = '两次密码输入不一致';
    } else {
        $pdo = getDB();
        
        // 验证旧密码
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $userData = $stmt->fetch();
        
        if ($userData && password_verify($oldPassword, $userData['password'])) {
            // 更新密码
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashedPassword, $user['id']])) {
                $success = '密码修改成功';
            } else {
                $error = '密码修改失败，请重试';
            }
        } else {
            $error = '旧密码错误';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密码 - 影视系统</title>
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
        <div class="rounded-lg bg-white p-6 shadow">
            <h1 class="mb-4 text-lg font-semibold">修改密码</h1>
            
            <?php if ($error): ?>
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-4 rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-600"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm text-gray-600">旧密码</label>
                    <input type="password" name="old_password" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">新密码</label>
                    <input type="password" name="new_password" required minlength="6" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">确认新密码</label>
                    <input type="password" name="confirm_password" required minlength="6" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">修改密码</button>
                    <a href="profile.php" class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">返回</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>

