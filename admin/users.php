<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

requireAdmin();

$pdo = getDB();
$message = '';
$error = '';

// 处理操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? 0;
    
    if ($userId) {
        switch ($action) {
            case 'ban':
                $stmt = $pdo->prepare("UPDATE users SET status = 'banned', ban_until = NULL WHERE id = ?");
                $stmt->execute([$userId]);
                $message = '用户已封禁';
                break;
            case 'ban_custom':
                $banUntil = $_POST['ban_until'] ?? '';
                if ($banUntil) {
                    $stmt = $pdo->prepare("UPDATE users SET status = 'banned', ban_until = ? WHERE id = ?");
                    $stmt->execute([$banUntil, $userId]);
                    $message = '用户已封禁至 ' . $banUntil;
                }
                break;
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND username != 'admin'");
                $stmt->execute([$userId]);
                $message = '用户已删除';
                break;
            case 'freeze':
                $stmt = $pdo->prepare("UPDATE users SET status = 'frozen' WHERE id = ?");
                $stmt->execute([$userId]);
                $message = '用户已冻结';
                break;
            case 'unfreeze':
                $stmt = $pdo->prepare("UPDATE users SET status = 'active', ban_until = NULL WHERE id = ?");
                $stmt->execute([$userId]);
                $message = '用户已解冻';
                break;
            case 'unban':
                $stmt = $pdo->prepare("UPDATE users SET status = 'active', ban_until = NULL WHERE id = ?");
                $stmt->execute([$userId]);
                $message = '用户已解封';
                break;
            case 'reset_password':
                $newPassword = $_POST['new_password'] ?? '';
                if ($newPassword && strlen($newPassword) >= 6) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $userId]);
                    $message = '密码已重置';
                } else {
                    $error = '密码长度至少6位';
                }
                break;
            case 'change_group':
                $groupId = $_POST['group_id'] ?? 0;
                if ($groupId) {
                    $stmt = $pdo->prepare("UPDATE users SET group_id = ? WHERE id = ?");
                    $stmt->execute([$groupId, $userId]);
                    $message = '用户分组已更改';
                }
                break;
        }
    }
}

// 获取用户列表
$stmt = $pdo->query("SELECT u.*, g.name as group_name FROM users u 
                    LEFT JOIN user_groups g ON u.group_id = g.id 
                    ORDER BY u.created_at DESC");
$users = $stmt->fetchAll();

// 获取用户组列表
$stmt = $pdo->query("SELECT * FROM user_groups ORDER BY id");
$groups = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户管理 - 影视系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
    <nav class="bg-white shadow-sm">
        <div class="mx-auto max-w-screen-xl px-4 py-3">
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../index.php">首页</a>
                <a class="rounded-full bg-gray-100 px-3 py-1" href="users.php">用户管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="groups.php">分组管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="domains.php">域名管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="videos.php">视频管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="register_info.php">注册信息</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../logout.php">退出</a>
            </div>
        </div>
    </nav>
    
    <main class="mx-auto max-w-screen-xl px-4 py-6">
        <h1 class="mb-4 text-lg font-semibold">用户管理</h1>
        
        <?php if ($message): ?>
            <div class="mb-4 rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-600"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="overflow-x-auto rounded-lg bg-white shadow">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">用户名</th>
                        <th class="px-4 py-3">邮箱</th>
                        <th class="px-4 py-3">用户组</th>
                        <th class="px-4 py-3">状态</th>
                        <th class="px-4 py-3">注册时间</th>
                        <th class="px-4 py-3">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-4 py-3"><?php echo $user['id']; ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($user['group_name'] ?? '未分组'); ?></td>
                            <td class="px-4 py-3">
                                <?php
                                $statusText = ['active' => '正常', 'banned' => '已封禁', 'frozen' => '已冻结'];
                                echo $statusText[$user['status']] ?? '未知';
                                if ($user['ban_until']) {
                                    echo ' (至 ' . $user['ban_until'] . ')';
                                }
                                ?>
                            </td>
                            <td class="px-4 py-3"><?php echo $user['created_at']; ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <?php if ($user['status'] === 'banned'): ?>
                                        <button class="rounded bg-green-600 px-2 py-1 text-xs text-white hover:bg-green-700" onclick="unbanUser(<?php echo $user['id']; ?>)">解封</button>
                                    <?php else: ?>
                                        <button class="rounded bg-red-600 px-2 py-1 text-xs text-white hover:bg-red-700" onclick="showModal('ban', <?php echo $user['id']; ?>)">封禁</button>
                                        <button class="rounded bg-orange-500 px-2 py-1 text-xs text-white hover:bg-orange-600" onclick="showModal('ban_custom', <?php echo $user['id']; ?>)">定时封禁</button>
                                    <?php endif; ?>
                                    <button class="rounded bg-red-600 px-2 py-1 text-xs text-white hover:bg-red-700" onclick="if(confirm('确定删除？')){deleteUser(<?php echo $user['id']; ?>)}">删除</button>
                                    <?php if ($user['status'] === 'frozen'): ?>
                                        <button class="rounded bg-green-600 px-2 py-1 text-xs text-white hover:bg-green-700" onclick="unfreezeUser(<?php echo $user['id']; ?>)">解冻</button>
                                    <?php else: ?>
                                        <button class="rounded bg-amber-500 px-2 py-1 text-xs text-white hover:bg-amber-600" onclick="freezeUser(<?php echo $user['id']; ?>)">冻结</button>
                                    <?php endif; ?>
                                    <button class="rounded bg-blue-600 px-2 py-1 text-xs text-white hover:bg-blue-700" onclick="showModal('reset_password', <?php echo $user['id']; ?>)">重置密码</button>
                                    <button class="rounded bg-gray-800 px-2 py-1 text-xs text-white hover:bg-gray-900" onclick="showModal('change_group', <?php echo $user['id']; ?>, '<?php echo $user['group_id']; ?>')">更改分组</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    
    <!-- 模态框 -->
    <div id="modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50" style="display:none;">
        <div class="relative w-full max-w-md rounded-lg bg-white p-5 shadow">
            <button class="absolute right-3 top-2 text-xl text-gray-400 hover:text-gray-700" onclick="closeModal()">&times;</button>
            <div id="modal-body"></div>
        </div>
    </div>
    
    <script>
        function showModal(action, userId, currentGroupId = '') {
            const modal = document.getElementById('modal');
            const modalBody = document.getElementById('modal-body');
            let html = '';
            
            if (action === 'ban') {
                html = `
                    <h2 class="mb-3 text-base font-semibold">封禁用户</h2>
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="ban">
                        <input type="hidden" name="user_id" value="${userId}">
                        <p class="text-sm text-gray-600">确定要封禁此用户吗？</p>
                        <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">确认封禁</button>
                    </form>
                `;
            } else if (action === 'ban_custom') {
                html = `
                    <h2 class="mb-3 text-base font-semibold">定时封禁用户</h2>
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="ban_custom">
                        <input type="hidden" name="user_id" value="${userId}">
                        <div>
                            <label class="mb-1 block text-sm text-gray-600">封禁至（日期时间）</label>
                            <input type="datetime-local" name="ban_until" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                        </div>
                        <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">确认封禁</button>
                    </form>
                `;
            } else if (action === 'reset_password') {
                html = `
                    <h2 class="mb-3 text-base font-semibold">重置用户密码</h2>
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" value="${userId}">
                        <div>
                            <label class="mb-1 block text-sm text-gray-600">新密码</label>
                            <input type="password" name="new_password" required minlength="6" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                        </div>
                        <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">确认重置</button>
                    </form>
                `;
            } else if (action === 'change_group') {
                html = `
                    <h2 class="mb-3 text-base font-semibold">更改用户分组</h2>
                    <form method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="change_group">
                        <input type="hidden" name="user_id" value="${userId}">
                        <div>
                            <label class="mb-1 block text-sm text-gray-600">选择用户组</label>
                            <select name="group_id" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?php echo $group['id']; ?>" ${currentGroupId == <?php echo $group['id']; ?> ? 'selected' : ''}>
                                        <?php echo htmlspecialchars($group['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="rounded bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-900">确认更改</button>
                    </form>
                `;
            }
            
            modalBody.innerHTML = html;
            modal.style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }
        
        function deleteUser(userId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function freezeUser(userId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="freeze">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function unfreezeUser(userId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="unfreeze">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function unbanUser(userId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="unban">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('modal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>

