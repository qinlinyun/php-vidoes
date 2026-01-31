<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

requireAdmin();
$pdo = getDB();

$message = '';
$error = '';
$success = $_GET['success'] ?? '';
if ($success === 'created') {
    $message = '通知已发布';
} elseif ($success === 'deleted') {
    $message = '通知已删除';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        if ($notificationId <= 0) {
            $error = '通知不存在';
        } else {
            $stmt = $pdo->prepare("DELETE FROM notification_reads WHERE notification_id = ?");
            $stmt->execute([$notificationId]);
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
            $stmt->execute([$notificationId]);
            header('Location: notifications.php?success=deleted');
            exit;
        }
    } else {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $targetType = $_POST['target_type'] ?? 'all';
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);

        if ($title === '' || $content === '') {
            $error = '标题和内容不能为空';
        } elseif ($targetType === 'user' && $targetUserId <= 0) {
            $error = '请选择要通知的用户';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (title, content, target_type, target_user_id, created_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $title,
                $content,
                $targetType,
                $targetType === 'user' ? $targetUserId : null,
                $_SESSION['user_id']
            ]);
            header('Location: notifications.php?success=created');
            exit;
        }
    }
}

$users = $pdo->query("SELECT id, username FROM users ORDER BY id DESC")->fetchAll();
$notifications = $pdo->query("
    SELECT n.*, u.username AS creator_name, tu.username AS target_name
    FROM notifications n
    LEFT JOIN users u ON n.created_by = u.id
    LEFT JOIN users tu ON n.target_user_id = tu.id
    ORDER BY n.created_at DESC
    LIMIT 200
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>站内通知管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://css.qinlinyun.cn/uploads/css/68cd3c66163ff-20260129212429-5fce6eb9.css?v=20260129212429"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css">
    <style>
        .card-hover{transition:transform .2s ease, box-shadow .2s ease;}
        .card-hover:hover{transform:translateY(-4px);box-shadow:0 12px 30px rgba(15,23,42,.12);}
        .pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:12px;}
    </style>
</head>
<body class="bg-gray-100 text-gray-900">
<nav class="bg-white shadow-sm">
    <div class="mx-auto max-w-screen-xl px-4 py-3 flex gap-3 text-sm">
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../index.php">首页</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="users.php">用户管理</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="groups.php">分组管理</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="domains.php">域名管理</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="videos.php">视频管理</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="watch_records.php">记录管理</a>
        <a class="rounded-full bg-gray-100 px-3 py-1" href="notifications.php">站内通知</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="feedback.php">意见反馈</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../logout.php">退出</a>
    </div>
</nav>

<main class="mx-auto max-w-screen-xl px-4 py-6">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold animate__animated animate__fadeInDown">站内通知管理</h1>
            <p class="text-xs text-gray-500">发布全员或指定用户通知</p>
        </div>
        <div class="flex gap-2 text-xs">
            <?php
            $allCount = 0;
            $userCount = 0;
            foreach ($notifications as $n) {
                if ($n['target_type'] === 'user') {
                    $userCount++;
                } else {
                    $allCount++;
                }
            }
            ?>
            <span class="pill bg-green-50 text-green-600">全员 <?= $allCount ?></span>
            <span class="pill bg-blue-50 text-blue-600">指定用户 <?= $userCount ?></span>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="mb-4 rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-600">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="rounded-2xl bg-white p-5 shadow card-hover">
        <h2 class="mb-3 text-base font-semibold">发布通知</h2>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="create">
            <div>
                <label class="mb-1 block text-sm text-gray-600">标题</label>
                <input name="title" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm text-gray-600">内容</label>
                <textarea name="content" rows="4" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none"></textarea>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-sm">
                <label class="flex items-center gap-2">
                    <input type="radio" name="target_type" value="all" checked>
                    全员通知
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="target_type" value="user">
                    指定用户
                </label>
                <select name="target_user_id" class="rounded border border-gray-300 px-2 py-1 text-sm">
                    <option value="">请选择用户</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?> (#<?= $u['id'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">发布通知</button>
        </form>
    </div>

    <div class="mt-6 space-y-4">
        <?php foreach ($notifications as $notice): ?>
            <div class="rounded-2xl bg-white p-5 shadow card-hover animate__animated animate__fadeInUp">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-base font-semibold"><?= htmlspecialchars($notice['title']) ?></h3>
                    <span class="text-xs text-gray-500"><?= htmlspecialchars($notice['created_at']) ?></span>
                </div>
                <div class="mt-2 text-sm text-gray-700 whitespace-pre-line"><?= htmlspecialchars($notice['content']) ?></div>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                    <div>
                        发布者：<?= htmlspecialchars($notice['creator_name'] ?? '系统') ?>
                        <?php if ($notice['target_type'] === 'user'): ?>
                            <span class="ml-2 rounded-full bg-blue-50 px-2 py-0.5 text-blue-600">指定用户：<?= htmlspecialchars($notice['target_name'] ?? '未知') ?></span>
                        <?php else: ?>
                            <span class="ml-2 rounded-full bg-green-50 px-2 py-0.5 text-green-600">全员通知</span>
                        <?php endif; ?>
                    </div>
                    <form method="POST" onsubmit="return confirm('确定删除该通知吗？');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="notification_id" value="<?= (int)$notice['id'] ?>">
                        <button class="rounded border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50">删除</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($notifications)): ?>
            <div class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow">暂无通知记录。</div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>

