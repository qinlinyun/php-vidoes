<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

requireAdmin();
$pdo = getDB();
$message = '';
$error = '';
$success = $_GET['success'] ?? '';
if ($success === 'replied') {
    $message = '回复已发送';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedbackId = (int)($_POST['feedback_id'] ?? 0);
    $content = trim($_POST['reply_content'] ?? '');

    if ($feedbackId <= 0 || $content === '') {
        $error = '回复内容不能为空';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM feedbacks WHERE id = ?");
        $stmt->execute([$feedbackId]);
        if (!$stmt->fetch()) {
            $error = '反馈记录不存在';
        } else {
            $stmt = $pdo->prepare("INSERT INTO feedback_replies (feedback_id, user_id, role, content) VALUES (?, ?, 'admin', ?)");
            $stmt->execute([$feedbackId, $_SESSION['user_id'], $content]);
            $pdo->prepare("UPDATE feedbacks SET status='replied' WHERE id=?")->execute([$feedbackId]);
            header('Location: feedback.php?success=replied');
            exit;
        }
    }
}

$feedbacks = $pdo->query("
    SELECT f.*, u.username
    FROM feedbacks f
    LEFT JOIN users u ON f.user_id = u.id
    ORDER BY f.updated_at DESC
    LIMIT 200
")->fetchAll();

$feedbackIds = array_column($feedbacks, 'id');
$repliesByFeedback = [];
if (!empty($feedbackIds)) {
    $placeholders = implode(',', array_fill(0, count($feedbackIds), '?'));
    $stmt = $pdo->prepare("
        SELECT r.*, u.username
        FROM feedback_replies r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.feedback_id IN ($placeholders)
        ORDER BY r.created_at ASC
    ");
    $stmt->execute($feedbackIds);
    foreach ($stmt->fetchAll() as $reply) {
        $repliesByFeedback[$reply['feedback_id']][] = $reply;
    }
}

$assetPrefix = '../';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>意见反馈管理</title>
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
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="notifications.php">站内通知</a>
        <a class="rounded-full bg-gray-100 px-3 py-1" href="feedback.php">意见反馈</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../logout.php">退出</a>
    </div>
</nav>

<main class="mx-auto max-w-screen-xl px-4 py-6 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold animate__animated animate__fadeInDown">意见反馈管理</h1>
            <p class="text-xs text-gray-500">集中查看用户反馈并回复</p>
        </div>
        <div class="flex gap-2 text-xs">
            <?php
            $openCount = 0;
            $repliedCount = 0;
            foreach ($feedbacks as $f) {
                if ($f['status'] === 'open') $openCount++;
                if ($f['status'] === 'replied') $repliedCount++;
            }
            ?>
            <span class="pill bg-amber-50 text-amber-600">待处理 <?= $openCount ?></span>
            <span class="pill bg-blue-50 text-blue-600">已回复 <?= $repliedCount ?></span>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-600">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($feedbacks)): ?>
        <div class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow">暂无反馈记录。</div>
    <?php endif; ?>

    <?php foreach ($feedbacks as $fb): ?>
        <div class="rounded-2xl bg-white p-5 shadow card-hover animate__animated animate__fadeInUp">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="text-base font-semibold"><?= htmlspecialchars($fb['title'] ?: '未命名反馈') ?></div>
                <span class="text-xs text-gray-500"><?= htmlspecialchars($fb['created_at']) ?></span>
            </div>
            <div class="mt-1 text-xs text-gray-500">用户：<?= htmlspecialchars($fb['username'] ?? '未知') ?> (#<?= (int)$fb['user_id'] ?>)</div>
            <div class="mt-2 text-sm text-gray-700 whitespace-pre-line"><?= htmlspecialchars($fb['content']) ?></div>
            <?php if (!empty($fb['image_path'])): ?>
                <div class="mt-3">
                    <img src="<?= htmlspecialchars($assetPrefix . $fb['image_path']) ?>" alt="反馈图片" class="max-h-64 rounded border">
                </div>
            <?php endif; ?>
            <div class="mt-3 text-xs text-gray-500">
                状态：
                <?php if ($fb['status'] === 'open'): ?>
                    <span class="pill bg-amber-50 text-amber-600">待处理</span>
                <?php elseif ($fb['status'] === 'replied'): ?>
                    <span class="pill bg-blue-50 text-blue-600">已回复</span>
                <?php else: ?>
                    <span class="pill bg-gray-100 text-gray-500"><?= htmlspecialchars($fb['status']) ?></span>
                <?php endif; ?>
            </div>

            <?php $replies = $repliesByFeedback[$fb['id']] ?? []; ?>
            <?php if (!empty($replies)): ?>
                <div class="mt-4 space-y-3">
                    <?php foreach ($replies as $reply): ?>
                        <div class="rounded border border-gray-100 bg-gray-50 p-3 text-sm">
                            <div class="text-xs text-gray-500">
                                <?= $reply['role'] === 'admin' ? '管理员' : htmlspecialchars($reply['username'] ?? '用户') ?>
                                <span class="ml-2"><?= htmlspecialchars($reply['created_at']) ?></span>
                            </div>
                            <div class="mt-1 text-gray-700 whitespace-pre-line"><?= htmlspecialchars($reply['content']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="mt-4 space-y-2">
                <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                <textarea name="reply_content" rows="2" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none" placeholder="回复用户..."></textarea>
                <button class="rounded bg-gray-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-900">发送回复</button>
            </form>
        </div>
    <?php endforeach; ?>
</main>
</body>
</html>

