<?php
/**
 * 公共JSON文件操作函数
 * 处理下载资源的增删改查
 */

// JSON文件路径
define('DOWNLOAD_JSON', __DIR__ . '/downloads.json');

// 会话用于获取登录用户
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 登录校验（避免相对路径错误，手动跳转）
if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

/**
 * 从数据库获取当前登录用户邮箱，并确保 URL 携带 email 参数
 */
function requireAccessEmail(PDO $pdo) {
    $email = $_GET['email'] ?? '';
    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }

    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userEmail = $stmt->fetchColumn();

    if (!$userEmail || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        http_response_code(403);
        echo '访问被拒绝：未找到有效邮箱。';
        exit;
    }

    $query = $_GET;
    $query['email'] = $userEmail;
    $target = strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($query);
    header('Location: ' . $target);
    exit;
}

// 入口处强制校验
$currentAccessEmail = requireAccessEmail(getDB());

/**
 * 初始化JSON文件
 */
function initJsonFile() {
    if (!file_exists(DOWNLOAD_JSON)) {
        $initialData = [];
        file_put_contents(DOWNLOAD_JSON, json_encode($initialData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        chmod(DOWNLOAD_JSON, 0755); // 设置文件权限
    }
}

/**
 * 获取所有下载资源
 */
function getDownloads() {
    initJsonFile();
    $jsonContent = file_get_contents(DOWNLOAD_JSON);
    return json_decode($jsonContent, true) ?: [];
}

/**
 * 添加新下载资源
 */
function addDownload($data) {
    $downloads = getDownloads();
    // 添加唯一ID和时间戳
    $newItem = [
        'id' => uniqid(),
        'name' => $data['name'],
        'url' => $data['url'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    $downloads[] = $newItem;
    
    // 保存到JSON文件
    file_put_contents(
        DOWNLOAD_JSON,
        json_encode($downloads, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
    return $newItem;
}