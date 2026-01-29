<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// 检查登录状态
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 检查是否为管理员
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// 获取当前用户信息
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT u.*, g.name as group_name FROM users u 
                          LEFT JOIN user_groups g ON u.group_id = g.id 
                          WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// 检查用户状态
function checkUserStatus($user) {
    if (!$user) return false;
    
    // 检查是否被封禁
    if ($user['status'] === 'banned') {
        if ($user['ban_until'] && strtotime($user['ban_until']) > time()) {
            return false; // 仍在封禁期内
        } elseif ($user['ban_until'] && strtotime($user['ban_until']) <= time()) {
            // 封禁期已过，自动解封
            $pdo = getDB();
            $stmt = $pdo->prepare("UPDATE users SET status = 'active', ban_until = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
            return true;
        }
    }
    
    // 检查是否被冻结
    if ($user['status'] === 'frozen') {
        return false;
    }
    
    return $user['status'] === 'active';
}

// 需要登录
function requireLogin() {
    if (!isLoggedIn()) {
        // 判断当前是否在admin目录
        $basePath = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : '';
        header('Location: ' . $basePath . 'login.php');
        exit;
    }
    
    $user = getCurrentUser();
    if (!checkUserStatus($user)) {
        session_destroy();
        $basePath = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : '';
        header('Location: ' . $basePath . 'login.php?error=账户已被封禁或冻结');
        exit;
    }
}

// 需要管理员权限
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ../index.php?error=权限不足');
        exit;
    }
}
?>

