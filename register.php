<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        if (!$username || !$email || !$password || !$confirm) {
            $error = '请填写所有字段';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '邮箱格式不正确';
        } elseif ($password !== $confirm) {
            $error = '两次输入的密码不一致';
        } elseif (strlen($password) < 6) {
            $error = '密码至少 6 位';
        } else {
            $pdo = getDB();

            // 检查是否存在
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=?");
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                $error = '用户名或邮箱已存在';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, status, group_id, created_at)
                    VALUES (?, ?, ?, 'active', 1, NOW())
                ");
                $stmt->execute([$username, $email, $hash]);

                $success = '注册成功，请登录';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN" class="dark">
<head>
    <meta charset="UTF-8">
    <title>注册 - 竹叶云控平台</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind（保持不变） -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>

    <!-- Flowbite（保持不变） -->
    <link href="https://unpkg.com/flowbite@2.5.3/dist/flowbite.min.css" rel="stylesheet">

</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">

<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 shadow-xl">

        <!-- Header -->
        <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 text-center">
            <h1 class="text-lg font-semibold">创建账户</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">加入竹叶云控平台</p>
        </div>

        <!-- Body -->
        <div class="px-6 py-6">
            <?php if ($error): ?>
                <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-600 dark:text-red-400">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-4 rounded-lg bg-green-50 dark:bg-green-900/30 px-4 py-3 text-sm text-green-600 dark:text-green-400">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm">用户名</label>
                    <input name="username" required
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600
                                  bg-gray-50 dark:bg-gray-700 px-3 py-2 text-sm
                                  focus:border-red-500 focus:ring-red-500">
                </div>

                <div>
                    <label class="mb-1 block text-sm">邮箱</label>
                    <input type="email" name="email" required
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600
                                  bg-gray-50 dark:bg-gray-700 px-3 py-2 text-sm
                                  focus:border-red-500 focus:ring-red-500">
                </div>

                <div>
                    <label class="mb-1 block text-sm">密码</label>
                    <input type="password" name="password" required
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600
                                  bg-gray-50 dark:bg-gray-700 px-3 py-2 text-sm
                                  focus:border-red-500 focus:ring-red-500">
                </div>

                <div>
                    <label class="mb-1 block text-sm">确认密码</label>
                    <input type="password" name="confirm" required
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600
                                  bg-gray-50 dark:bg-gray-700 px-3 py-2 text-sm
                                  focus:border-red-500 focus:ring-red-500">
                </div>

                <button type="submit"
                        class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold
                               text-white hover:bg-red-700 focus:ring-4 focus:ring-red-300">
                    注册
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 text-center text-sm">
            <span class="text-gray-500 dark:text-gray-400">已有账号？</span>
            <a href="login.php" class="text-red-600 hover:underline">立即登录</a>
        </div>
    </div>
</div>

</body>
</html>
