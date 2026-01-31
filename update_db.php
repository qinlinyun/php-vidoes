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

    // 创建站内通知表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notifications` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `title` varchar(200) NOT NULL,
        `content` text NOT NULL,
        `target_type` enum('all','user') NOT NULL DEFAULT 'all',
        `target_user_id` int(11) DEFAULT NULL,
        `created_by` int(11) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_target_user` (`target_user_id`),
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "已检查 notifications 表<br>";

    // 创建通知已读表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notification_reads` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `notification_id` bigint unsigned NOT NULL,
        `user_id` int(11) NOT NULL,
        `read_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_read` (`notification_id`,`user_id`),
        KEY `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "已检查 notification_reads 表<br>";

    // 创建通知已读表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notification_reads` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `notification_id` bigint unsigned NOT NULL,
        `read_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_user_notice` (`user_id`,`notification_id`),
        KEY `idx_user` (`user_id`),
        KEY `idx_notice` (`notification_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "已检查 notification_reads 表<br>";

    // 创建意见反馈表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `feedbacks` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `title` varchar(200) DEFAULT NULL,
        `content` text NOT NULL,
        `image_path` varchar(255) DEFAULT NULL,
        `status` enum('open','replied','closed') NOT NULL DEFAULT 'open',
        `user_last_read_at` datetime DEFAULT NULL,
        `admin_last_read_at` datetime DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_user` (`user_id`),
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "已检查 feedbacks 表<br>";

    $stmt = $pdo->query("SHOW COLUMNS FROM feedbacks LIKE 'user_last_read_at'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE feedbacks ADD COLUMN user_last_read_at datetime DEFAULT NULL AFTER status");
        echo "已添加 feedbacks.user_last_read_at 字段<br>";
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM feedbacks LIKE 'admin_last_read_at'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE feedbacks ADD COLUMN admin_last_read_at datetime DEFAULT NULL AFTER user_last_read_at");
        echo "已添加 feedbacks.admin_last_read_at 字段<br>";
    }

    // 创建意见回复表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `feedback_replies` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `feedback_id` bigint unsigned NOT NULL,
        `user_id` int(11) NOT NULL,
        `role` enum('user','admin') NOT NULL,
        `content` text NOT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_feedback` (`feedback_id`),
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "已检查 feedback_replies 表<br>";

    // 创建反馈回复已读表
    $pdo->exec("CREATE TABLE IF NOT EXISTS `feedback_reply_reads` (
        `id` bigint unsigned NOT NULL AUTO_INCREMENT,
        `reply_id` bigint unsigned NOT NULL,
        `user_id` int(11) NOT NULL,
        `read_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_reply_user` (`reply_id`,`user_id`),
        KEY `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "已检查 feedback_reply_reads 表<br>";
    
    echo "数据库更新完成！";
} catch(PDOException $e) {
    echo "更新失败：" . $e->getMessage();
}
?>

