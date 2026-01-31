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

$message = '';
$error = '';
$success = $_GET['success'] ?? '';
if ($success === 'created') {
    $message = '反馈已提交';
} elseif ($success === 'replied') {
    $message = '回复已发送';
}
$uploadDir = __DIR__ . '/uploads/feedback';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $imagePath = null;

        if ($content === '') {
            $error = '请输入反馈内容';
        }

        if (!$error && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = '图片上传失败，请重试';
            } elseif ($_FILES['image']['size'] > 10 * 1024 * 1024) {
                $error = '图片大小不能超过 10MB';
            } else {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowedExt = ['jpg', 'jpeg', 'png'];
                if (!in_array($ext, $allowedExt, true)) {
                    $error = '仅支持 jpg / jpeg / png 格式';
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($_FILES['image']['tmp_name']);
                    $allowedMime = ['image/jpeg', 'image/png'];
                    if (!in_array($mime, $allowedMime, true)) {
                        $error = '图片格式校验失败';
                    }
                }
            }

            if (!$error) {
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $fileName = 'fb_' . $user['id'] . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $target = $uploadDir . '/' . $fileName;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $error = '图片保存失败';
                } else {
                    $imagePath = 'uploads/feedback/' . $fileName;
                }
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("INSERT INTO feedbacks (user_id, title, content, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], $title ?: null, $content, $imagePath]);
            header('Location: feedback.php?success=created');
            exit;
        }
    } elseif ($action === 'reply') {
        $feedbackId = (int)($_POST['feedback_id'] ?? 0);
        $replyContent = trim($_POST['reply_content'] ?? '');

        if ($feedbackId <= 0 || $replyContent === '') {
            $error = '回复内容不能为空';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM feedbacks WHERE id = ? AND user_id = ?");
            $stmt->execute([$feedbackId, $user['id']]);
            if (!$stmt->fetch()) {
                $error = '无效的反馈记录';
            } else {
                $stmt = $pdo->prepare("INSERT INTO feedback_replies (feedback_id, user_id, role, content) VALUES (?, ?, 'user', ?)");
                $stmt->execute([$feedbackId, $user['id'], $replyContent]);
                $pdo->prepare("UPDATE feedbacks SET status='open' WHERE id=?")->execute([$feedbackId]);
                header('Location: feedback.php?success=replied');
                exit;
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM feedbacks WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->execute([$user['id']]);
$feedbacks = $stmt->fetchAll();

$feedbackIds = array_column($feedbacks, 'id');
$repliesByFeedback = [];
if (!empty($feedbackIds)) {
    $placeholders = implode(',', array_fill(0, count($feedbackIds), '?'));
    $stmt = $pdo->prepare("
        SELECT r.*, u.username,
               IF(rr.id IS NULL, 0, 1) AS is_read
        FROM feedback_replies r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN feedback_reply_reads rr
          ON rr.reply_id = r.id AND rr.user_id = ?
        WHERE r.feedback_id IN ($placeholders)
        ORDER BY r.created_at ASC
    ");
    $stmt->execute(array_merge([$user['id']], $feedbackIds));
    foreach ($stmt->fetchAll() as $reply) {
        $repliesByFeedback[$reply['feedback_id']][] = $reply;
    }
}

// 标记管理员回复已读
$adminReplyIds = [];
foreach ($repliesByFeedback as $replyList) {
    foreach ($replyList as $reply) {
        if ($reply['role'] === 'admin') {
            $adminReplyIds[] = $reply['id'];
        }
    }
}
if (!empty($adminReplyIds)) {
    $values = implode(',', array_fill(0, count($adminReplyIds), '(?, ?, NOW())'));
    $params = [];
    foreach ($adminReplyIds as $rid) {
        $params[] = $rid;
        $params[] = $user['id'];
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO feedback_reply_reads (reply_id, user_id, read_at) VALUES $values");
    $stmt->execute($params);
}

$assetPrefix = '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>意见反馈</title>
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
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="index.php">首页</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="notifications.php">站内通知</a>
        <a class="rounded-full bg-gray-100 px-3 py-1" href="feedback.php">意见反馈</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="profile.php"><?= htmlspecialchars($user['username']) ?></a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="logout.php">退出</a>
    </div>
</nav>

<main class="mx-auto max-w-screen-xl px-4 py-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold animate__animated animate__fadeInDown">意见反馈</h1>
            <p class="text-xs text-gray-500">反馈问题与建议，管理员会尽快回复</p>
        </div>
        <?php
        $unreadReplies = 0;
        foreach ($repliesByFeedback as $replyList) {
            foreach ($replyList as $reply) {
                if ($reply['role'] === 'admin' && (int)$reply['is_read'] === 0) {
                    $unreadReplies++;
                }
            }
        }
        ?>
        <div class="flex gap-2 text-xs">
            <span class="pill bg-blue-50 text-blue-600">📮 反馈 <?= count($feedbacks) ?></span>
            <span class="pill bg-amber-50 text-amber-600">未读回复 <?= $unreadReplies ?></span>
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

    <div class="rounded-2xl bg-white p-5 shadow card-hover">
        <h2 class="mb-3 text-base font-semibold">提交反馈</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-3">
            <input type="hidden" name="action" value="create">
            <div>
                <label class="mb-1 block text-sm text-gray-600">标题（可选）</label>
                <input name="title" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
            </div>
            <div>
                <label class="mb-1 block text-sm text-gray-600">内容</label>
                <textarea name="content" rows="4" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm text-gray-600">上传图片（jpg / jpeg / png，≤10MB）</label>
                <input type="file" name="image" accept=".jpg,.jpeg,.png" class="text-sm">
            </div>
            <button class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">提交反馈</button>
        </form>
    </div>

    <div class="space-y-4">
        <?php if (empty($feedbacks)): ?>
            <div class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow">暂无反馈记录。</div>
        <?php endif; ?>
        <?php foreach ($feedbacks as $fb): ?>
            <div class="rounded-2xl bg-white p-5 shadow card-hover animate__animated animate__fadeInUp">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-base font-semibold"><?= htmlspecialchars($fb['title'] ?: '未命名反馈') ?></div>
                    <span class="text-xs text-gray-500"><?= htmlspecialchars($fb['created_at']) ?></span>
                </div>
                <div class="mt-2 text-sm text-gray-700 whitespace-pre-line"><?= htmlspecialchars($fb['content']) ?></div>
                <?php if (!empty($fb['image_path'])): ?>
                    <div class="mt-3">
                        <img src="<?= htmlspecialchars($assetPrefix . $fb['image_path']) ?>" alt="反馈图片" class="max-h-64 rounded border">
                    </div>
                <?php endif; ?>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                    <span>状态：<?= htmlspecialchars($fb['status']) ?></span>
                    <?php
                    $hasUnread = false;
                    $replies = $repliesByFeedback[$fb['id']] ?? [];
                    foreach ($replies as $reply) {
                        if ($reply['role'] === 'admin' && (int)$reply['is_read'] === 0) {
                            $hasUnread = true;
                            break;
                        }
                    }
                    ?>
                    <?php if ($hasUnread): ?>
                        <span class="pill bg-amber-50 text-amber-600">未读回复</span>
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
                                    <?php if ($reply['role'] === 'admin' && (int)$reply['is_read'] === 0): ?>
                                        <span class="ml-2 rounded-full bg-amber-50 px-2 py-0.5 text-amber-600">未读</span>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-1 text-gray-700 whitespace-pre-line"><?= htmlspecialchars($reply['content']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="mt-4 space-y-2">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                    <textarea name="reply_content" rows="2" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none" placeholder="回复管理员..."></textarea>
                    <button class="rounded bg-gray-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-900">发送回复</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</main>
</body>
</html>

