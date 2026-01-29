<?php
// 数据库更新脚本 - 为已安装的系统添加新字段
require_once 'config/database.php';

try {
    $pdo = getDB();
    
    // 检查并添加用户表的注册信息字段
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'register_ip'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN register_ip varchar(45) DEFAULT NULL AFTER ban_until");
        echo "已添加 register_ip 字段<br>";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'register_device'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN register_device varchar(255) DEFAULT NULL AFTER register_ip");
        echo "已添加 register_device 字段<br>";
    }
    
    // 检查并添加域名表的显示名称字段
    $stmt = $pdo->query("SHOW COLUMNS FROM domains LIKE 'display_name'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE domains ADD COLUMN display_name varchar(100) DEFAULT NULL AFTER domain");
        echo "已添加 display_name 字段<br>";
    }
    
    echo "数据库更新完成！";
} catch(PDOException $e) {
    echo "更新失败：" . $e->getMessage();
}
?>

