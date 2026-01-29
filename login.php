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
            // 检查用户状态
            if ($user['status'] === 'banned' && $user['ban_until'] && strtotime($user['ban_until']) > time()) {
                $error = '账户已被封禁，解封时间：' . $user['ban_until'];
            } elseif ($user['status'] === 'frozen') {
                $error = '账户已被冻结';
            } elseif ($user['status'] === 'banned' && (!$user['ban_until'] || strtotime($user['ban_until']) <= time())) {
                // 封禁期已过，自动解封
                $stmt = $pdo->prepare("UPDATE users SET status = 'active', ban_until = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
                $user['status'] = 'active';
            }
            
            if (!$error) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = ($user['username'] === 'admin');
                header('Location: index.php');
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
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 影视系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow">
            <h1 class="mb-6 text-center text-lg font-semibold">登录</h1>
            <?php if ($error): ?>
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm text-gray-600">用户名/邮箱</label>
                    <input type="text" name="username" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">密码</label>
                    <input type="password" name="password" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full rounded bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700">登录</button>
            </form>
            <p class="mt-4 text-center text-sm text-gray-600">还没有账号？<a class="text-red-600 hover:underline" href="register.php">立即注册</a></p>
        </div>
    </div>
</body>
</html>

