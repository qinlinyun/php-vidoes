<?php
$authPath = __DIR__ . '/includes/auth.php';
$configPath = __DIR__ . '/config/database.php';
if (!file_exists($authPath)) {
    $authPath = dirname(__DIR__) . '/includes/auth.php';
}
if (!file_exists($configPath)) {
    $configPath = dirname(__DIR__) . '/config/database.php';
}
if (!file_exists($authPath) || !file_exists($configPath)) {
    die('找不到认证或数据库配置文件，请确认 includes/auth.php 与 config/database.php 是否存在。');
}
require_once $authPath;
require_once $configPath;

requireLogin();
$user = getCurrentUser();
$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT n.*, u.username AS creator_name,
           IF(r.id IS NULL, 0, 1) AS is_read
    FROM notifications n
    LEFT JOIN users u ON n.created_by = u.id
    LEFT JOIN notification_reads r
      ON r.notification_id = n.id AND r.user_id = ?
    WHERE n.target_type = 'all' OR n.target_user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$user['id'], $user['id']]);
$notifications = $stmt->fetchAll();

$unreadCount = 0;
if (!empty($notifications)) {
    foreach ($notifications as $notice) {
        if ((int)$notice['is_read'] === 0) {
            $unreadCount++;
        }
    }

    $notificationIds = array_column($notifications, 'id');
    $values = implode(',', array_fill(0, count($notificationIds), '(?, ?, NOW())'));
    $params = [];
    foreach ($notificationIds as $nid) {
        $params[] = $user['id'];
        $params[] = $nid;
    }
    $markStmt = $pdo->prepare("INSERT IGNORE INTO notification_reads (user_id, notification_id, read_at) VALUES $values");
    $markStmt->execute($params);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>站内通知</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://css.qinlinyun.cn/uploads/css/68cd3c66163ff-20260129212429-5fce6eb9.css?v=20260129212429"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
</head>
<body class="bg-gray-100 text-gray-900">
<nav class="bg-white shadow-sm">
    <div class="mx-auto max-w-screen-xl px-4 py-3 flex gap-3 text-sm">
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="index.php">首页</a>
        <a class="rounded-full bg-gray-100 px-3 py-1" href="notifications.php">站内通知</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="feedback.php">意见反馈</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="profile.php"><?= htmlspecialchars($user['username']) ?></a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="logout.php">退出</a>
    </div>
</nav>

<main class="mx-auto max-w-screen-xl px-4 py-6">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold animate__animated animate__fadeInDown">站内通知</h1>
            <p class="text-xs text-gray-500">仅显示你可见的通知</p>
        </div>
        <div class="flex gap-2 text-xs">
            <span class="rounded-full bg-blue-50 px-2 py-0.5 text-blue-600">📣 总数 <?= count($notifications) ?></span>
            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-amber-600">未读 <?= (int)$unreadCount ?></span>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow animate__animated animate__fadeIn">
            暂无通知。
        </div>
    <?php else: ?>
        <div class="grid gap-5 md:grid-cols-2">
            <?php foreach ($notifications as $notice): ?>
                <div class="group rounded-2xl bg-white p-5 shadow transition hover:-translate-y-1 hover:shadow-lg" data-aos="fade-up">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-base font-semibold text-gray-900 group-hover:text-red-600"><?= htmlspecialchars($notice['title']) ?></h2>
                        <span class="text-xs text-gray-500"><?= htmlspecialchars($notice['created_at']) ?></span>
                    </div>
                    <div class="mt-3 text-sm text-gray-700 whitespace-pre-line leading-relaxed"><?= htmlspecialchars($notice['content']) ?></div>
                    <div class="mt-4 flex items-center justify-between text-xs text-gray-400">
                        <span>发布者：<?= htmlspecialchars($notice['creator_name'] ?? '系统') ?></span>
                        <div class="flex flex-wrap items-center gap-2">
                            <?php if ($notice['target_type'] === 'user'): ?>
                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-blue-600">指定用户</span>
                            <?php else: ?>
                                <span class="rounded-full bg-green-50 px-2 py-0.5 text-green-600">全员通知</span>
                            <?php endif; ?>
                            <?php if ((int)$notice['is_read'] === 0): ?>
                                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-amber-600">未读</span>
                            <?php else: ?>
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-500">已读</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
  AOS.init({ duration: 600, once: true });
</script>
</body>
</html>

