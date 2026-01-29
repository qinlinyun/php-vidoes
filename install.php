<?php
// 检查是否已安装
$db_file = __DIR__ . '/.installed';
if (file_exists($db_file)) {
    die('系统已安装，如需重新安装请删除 .installed 文件');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? 'localhost';
    $dbname = $_POST['dbname'] ?? 'video_system';
    $username = $_POST['username'] ?? 'root';
    $password = $_POST['password'] ?? '';
    
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 创建数据库
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
        
        // 创建用户表
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL UNIQUE,
            `email` varchar(100) NOT NULL UNIQUE,
            `password` varchar(255) NOT NULL,
            `group_id` int(11) NOT NULL DEFAULT 1,
            `status` enum('active','banned','frozen') NOT NULL DEFAULT 'active',
            `ban_until` datetime DEFAULT NULL,
            `register_ip` varchar(45) DEFAULT NULL,
            `register_device` varchar(255) DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `group_id` (`group_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // 创建用户组表
        $pdo->exec("CREATE TABLE IF NOT EXISTS `user_groups` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL UNIQUE,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // 创建域名表
        $pdo->exec("CREATE TABLE IF NOT EXISTS `domains` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `domain` varchar(255) NOT NULL,
            `display_name` varchar(100) DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // 创建用户组域名关联表
        $pdo->exec("CREATE TABLE IF NOT EXISTS `group_domains` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `group_id` int(11) NOT NULL,
            `domain_id` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `group_domain` (`group_id`,`domain_id`),
            KEY `group_id` (`group_id`),
            KEY `domain_id` (`domain_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // 创建视频表
        $pdo->exec("CREATE TABLE IF NOT EXISTS `videos` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `description` text,
            `cover` varchar(255),
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // 创建视频集数表
        $pdo->exec("CREATE TABLE IF NOT EXISTS `video_episodes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `video_id` int(11) NOT NULL,
            `episode_name` varchar(255) NOT NULL,
            `video_url` text NOT NULL,
            `episode_order` int(11) NOT NULL DEFAULT 0,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `video_id` (`video_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // 插入默认用户组
        $pdo->exec("INSERT INTO `user_groups` (`name`) VALUES ('注册用户组')");
        
        // 创建管理员账户（用户名：admin，密码：admin123）
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO `users` (`username`, `email`, `password`, `group_id`) 
                   VALUES ('admin', 'admin@example.com', '$adminPassword', 1)");
        
        // 更新数据库配置
        $config = "<?php\n";
        $config .= "define('DB_HOST', '$host');\n";
        $config .= "define('DB_NAME', '$dbname');\n";
        $config .= "define('DB_USER', '$username');\n";
        $config .= "define('DB_PASS', '$password');\n";
        $config .= "define('DB_CHARSET', 'utf8mb4');\n";
        file_put_contents(__DIR__ . '/config/database_config.php', $config);
        
        // 创建安装标记文件
        file_put_contents($db_file, date('Y-m-d H:i:s'));
        
        $message = '安装成功！默认管理员账号：admin，密码：admin123';
    } catch(PDOException $e) {
        $error = '安装失败：' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据库安装</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 text-gray-900">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow">
            <h1 class="mb-6 text-center text-lg font-semibold">数据库安装</h1>
            <?php if ($message): ?>
                <div class="mb-4 rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-600"><?php echo htmlspecialchars($message); ?></div>
                <a href="index.php" class="block text-center text-sm text-red-600 hover:underline">前往首页</a>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="mb-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm text-gray-600">数据库主机</label>
                        <input type="text" name="host" value="localhost" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-gray-600">数据库名称</label>
                        <input type="text" name="dbname" value="video_system" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-gray-600">数据库用户名</label>
                        <input type="text" name="username" value="root" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-gray-600">数据库密码</label>
                        <input type="password" name="password" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                    </div>
                    <button type="submit" class="w-full rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">开始安装</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

