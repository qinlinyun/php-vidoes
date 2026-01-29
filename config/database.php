<?php
// 检查配置文件是否存在，如果不存在则使用默认值（用于安装前）
if (!defined('DB_HOST')) {
    // 尝试从配置文件读取
    $config_file = __DIR__ . '/database_config.php';
    if (file_exists($config_file)) {
        require_once $config_file;
    } else {
        // 默认配置（安装时会更新）
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'video_system');
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('DB_CHARSET', 'utf8mb4');
    }
}

// 数据库连接
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }
    return $pdo;
}
?>

