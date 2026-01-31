<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

requireAdmin();

$pdo = getDB();

$stmt = $pdo->query("
    SELECT id, username, email, register_ip, register_device, created_at
    FROM users
    WHERE register_ip IS NOT NULL OR register_device IS NOT NULL
    ORDER BY created_at DESC
");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册信息 - 竹叶云控平台</title>

    <!-- Tailwind CSS -->
    <script src="https://css.qinlinyun.cn/uploads/css/68cd3c66163ff-20260129212429-5fce6eb9.css?v=20260129212429"></script>

    <!-- AOS -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
</head>

<body class="bg-gray-100 text-gray-900">

<nav class="bg-white shadow-sm">
    <div class="mx-auto max-w-screen-xl px-4 py-3">
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../index.php">首页</a>
            <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="users.php">用户管理</a>
            <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="groups.php">分组管理</a>
            <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="domains.php">域名管理</a>
            <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="videos.php">视频管理</a>
            <a class="rounded-full bg-gray-100 px-3 py-1" href="register_info.php">注册信息</a>
            <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="notifications.php">站内通知</a>
            <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="feedback.php">意见反馈</a>
            <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../logout.php">退出</a>
        </div>
    </div>
</nav>

<main class="mx-auto max-w-screen-xl px-4 py-6">
    <h1 class="mb-4 text-lg font-semibold">用户注册信息</h1>

    <!-- ⭐ 关键修复在这里：overflow-y-hidden -->
    <div class="overflow-x-auto overflow-y-hidden rounded-lg bg-white shadow"
         data-aos="fade-up"
         data-aos-duration="600">

        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-700">
            <tr>
                <th class="px-4 py-3">用户</th>
                <th class="px-4 py-3">邮箱</th>
            </tr>
            </thead>

            <tbody class="divide-y">
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="2" class="px-4 py-6 text-center text-gray-500">
                        暂无注册信息
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <!-- 概要行 -->
                    <tr
                        class="cursor-pointer hover:bg-gray-50 transition"
                        data-toggle="user"
                        data-user-id="<?= $user['id'] ?>"
                        data-aos="fade-up"
                        data-aos-delay="50"
                    >
                        <td class="px-4 py-3 font-medium flex items-center gap-2">
                            <i class="fa-solid fa-chevron-right text-gray-400 transition-transform duration-300"
                               data-icon="<?= $user['id'] ?>"></i>
                            <?= htmlspecialchars($user['username']) ?>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <?= htmlspecialchars($user['email']) ?>
                        </td>
                    </tr>

                    <!-- 折叠详情行 -->
                    <tr
                        class="hidden bg-gray-50"
                        data-detail="user"
                        data-user-id="<?= $user['id'] ?>"
                    >
                        <td colspan="2" class="px-6 py-4 text-sm text-gray-700">
                            <div
                                class="grid grid-cols-1 md:grid-cols-3 gap-3"
                                data-aos="fade-down"
                                data-aos-duration="300"
                            >
                                <div>
                                    <span class="text-gray-500">注册 IP：</span>
                                    <?= htmlspecialchars($user['register_ip'] ?? '未记录') ?>
                                </div>
                                <div>
                                    <span class="text-gray-500">注册设备：</span>
                                    <?= htmlspecialchars($user['register_device'] ?? '未记录') ?>
                                </div>
                                <div>
                                    <span class="text-gray-500">注册时间：</span>
                                    <?= $user['created_at'] ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- AOS JS -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>

<script>
AOS.init({
    once: true,
    easing: 'ease-out-cubic'
});

document.addEventListener('DOMContentLoaded', () => {
    const toggles = document.querySelectorAll('[data-toggle="user"]');
    const details = document.querySelectorAll('[data-detail="user"]');
    const icons = document.querySelectorAll('[data-icon]');

    toggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const userId = toggle.dataset.userId;

            details.forEach(detail => {
                const icon = document.querySelector(
                    `[data-icon="${detail.dataset.userId}"]`
                );

                if (detail.dataset.userId === userId) {
                    const isHidden = detail.classList.contains('hidden');

                    // 关闭全部
                    details.forEach(d => d.classList.add('hidden'));
                    icons.forEach(i => i.classList.remove('rotate-90'));

                    if (isHidden) {
                        detail.classList.remove('hidden');
                        icon.classList.add('rotate-90');

                        // 触发 AOS
                        AOS.refresh();
                    }
                } else {
                    detail.classList.add('hidden');
                    if (icon) icon.classList.remove('rotate-90');
                }
            });
        });
    });
});
</script>

</body>
</html>
