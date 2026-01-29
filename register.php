<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 验证
    if (empty($username) || empty($email) || empty($password)) {
        $error = '请填写所有字段';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
    } elseif (strlen($password) < 6) {
        $error = '密码长度至少6位';
    } elseif ($password !== $confirm_password) {
        $error = '两次密码输入不一致';
    } else {
        $pdo = getDB();
        
        // 检查用户名是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = '用户名已存在';
        } else {
            // 检查邮箱是否已存在
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = '邮箱已被注册';
            } else {
                // 获取默认用户组（注册用户组）
                $stmt = $pdo->prepare("SELECT id FROM user_groups WHERE name = '注册用户组'");
                $stmt->execute();
                $group = $stmt->fetch();
                $groupId = $group ? $group['id'] : 1;
                
                // 获取注册IP和设备信息
                $registerIp = $_SERVER['REMOTE_ADDR'] ?? '';
                if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $registerIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
                
                $registerDevice = $_SERVER['HTTP_USER_AGENT'] ?? '';
                
                // 创建用户
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, group_id, register_ip, register_device) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $hashedPassword, $groupId, $registerIp, $registerDevice])) {
                    $success = '注册成功！请登录';
                    header('refresh:2;url=login.php');
                } else {
                    $error = '注册失败，请重试';
                }
            }
        }
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
    <title>注册 - 影视系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow">
            <h1 class="mb-6 text-center text-lg font-semibold">注册</h1>
            <?php if ($error): ?>
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-4 rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-600"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm text-gray-600">用户名</label>
                    <input type="text" name="username" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">邮箱</label>
                    <input type="email" name="email" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">密码</label>
                    <input type="password" name="password" required minlength="6" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">确认密码</label>
                    <input type="password" name="confirm_password" required minlength="6" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full rounded bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700">注册</button>
            </form>
            <p class="mt-4 text-center text-sm text-gray-600">已有账号？<a class="text-red-600 hover:underline" href="login.php">立即登录</a></p>
        </div>
    </div>
</body>
</html>

