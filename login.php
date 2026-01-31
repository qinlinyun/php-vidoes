<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($username && $password) {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'banned' && $user['ban_until'] && strtotime($user['ban_until']) > time()) {
                    $error = '账户已被封禁，解封时间：' . $user['ban_until'];
                } elseif ($user['status'] === 'frozen') {
                    $error = '账户已被冻结';
                } elseif ($user['status'] === 'banned' && (!$user['ban_until'] || strtotime($user['ban_until']) <= time())) {
                    $stmt = $pdo->prepare("UPDATE users SET status='active', ban_until=NULL WHERE id=?");
                    $stmt->execute([$user['id']]);
                }

                if (!$error) {
                    $_SESSION['user_id']  = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_admin'] = ($user['username'] === 'admin');

                // 统计未读提醒（通知 + 管理员回复）
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM notifications n
                    LEFT JOIN notification_reads r 
                      ON r.notification_id = n.id AND r.user_id = ?
                    WHERE (n.target_type = 'all' OR n.target_user_id = ?)
                      AND r.id IS NULL
                ");
                $stmt->execute([$user['id'], $user['id']]);
                $_SESSION['unread_notification_count'] = (int)$stmt->fetchColumn();

                $stmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM feedback_replies r
                    JOIN feedbacks f ON f.id = r.feedback_id
                    LEFT JOIN feedback_reply_reads rr 
                      ON rr.reply_id = r.id AND rr.user_id = ?
                    WHERE f.user_id = ?
                      AND r.role = 'admin'
                      AND rr.id IS NULL
                ");
                $stmt->execute([$user['id'], $user['id']]);
                $_SESSION['unread_feedback_reply_count'] = (int)$stmt->fetchColumn();

                $_SESSION['login_popup_pending'] = true;
                    header('Location: /');
                    exit;
                }
            } else {
                $error = '用户名或密码错误';
            }
        } else {
            $error = '请填写用户名和密码';
    }
}

if (isLoggedIn()) {
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN" class="dark">
<head>
    <meta charset="UTF-8">
    <title>登录 - 竹叶云控平台</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind（保持不变） -->
    <script src="https://css.qinlinyun.cn/uploads/css/68cd3c66163ff-20260129212429-5fce6eb9.css?v=20260129212429"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>

    <!-- Flowbite（保持不变） -->
    <link href="https://css.qinlinyun.cn/uploads/css/flowbite-min-20260129222650-459307f2.css?v=20260129222650" rel="stylesheet">

</head>

<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">

<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 shadow-xl">

        <!-- Header -->
        <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 text-center">
            <h1 class="text-lg font-semibold">竹叶云控平台</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">账号登录</p>
        </div>

        <!-- Body -->
        <div class="px-6 py-6">
            <?php if ($error): ?>
                <div class="mb-4 rounded-lg bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-600 dark:text-red-400">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm">用户名 / 邮箱</label>
                    <input name="username" required
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

                <button type="submit"
                        class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold
                               text-white hover:bg-red-700 focus:ring-4 focus:ring-red-300">
                    登录
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 text-center text-sm">
            <span class="text-gray-500 dark:text-gray-400">没有账号？</span>
            <a href="register.php" class="text-red-600 hover:underline">立即注册</a>
        </div>
    </div>
</div>

</body>
</html>
