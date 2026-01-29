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
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            try {
                $stmt = $pdo->prepare("INSERT INTO user_groups (name) VALUES (?)");
                $stmt->execute([$name]);
                $message = '分组添加成功';
            } catch(PDOException $e) {
                $error = '分组名称已存在';
            }
        } else {
            $error = '请输入分组名称';
        }
    } elseif ($action === 'delete') {
        $groupId = $_POST['group_id'] ?? 0;
        if ($groupId) {
            // 检查是否为默认分组
            $stmt = $pdo->prepare("SELECT name FROM user_groups WHERE id = ?");
            $stmt->execute([$groupId]);
            $group = $stmt->fetch();
            
            if ($group && $group['name'] === '注册用户组') {
                $error = '不能删除默认分组';
            } else {
                // 将用户移回默认分组
                $stmt = $pdo->prepare("SELECT id FROM user_groups WHERE name = '注册用户组'");
                $stmt->execute();
                $defaultGroup = $stmt->fetch();
                
                if ($defaultGroup) {
                    $stmt = $pdo->prepare("UPDATE users SET group_id = ? WHERE group_id = ?");
                    $stmt->execute([$defaultGroup['id'], $groupId]);
                }
                
                $stmt = $pdo->prepare("DELETE FROM user_groups WHERE id = ?");
                $stmt->execute([$groupId]);
                $message = '分组删除成功';
            }
        }
    }
}

// 获取分组列表
$stmt = $pdo->query("SELECT g.*, COUNT(u.id) as user_count FROM user_groups g 
                    LEFT JOIN users u ON g.id = u.group_id 
                    GROUP BY g.id 
                    ORDER BY g.id");
$groups = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分组管理 - 影视系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
    <nav class="bg-white shadow-sm">
        <div class="mx-auto max-w-screen-xl px-4 py-3">
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../index.php">首页</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="users.php">用户管理</a>
                <a class="rounded-full bg-gray-100 px-3 py-1" href="groups.php">分组管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="domains.php">域名管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="videos.php">视频管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../logout.php">退出</a>
            </div>
        </div>
    </nav>
    
    <main class="mx-auto max-w-screen-xl px-4 py-6">
        <h1 class="mb-4 text-lg font-semibold">分组管理</h1>
        
        <?php if ($message): ?>
            <div class="mb-4 rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-600"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="mb-6 rounded-lg bg-white p-4 shadow">
            <h2 class="mb-3 text-base font-semibold">新增分组</h2>
            <form method="POST" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="action" value="add">
                <div class="flex-1">
                    <label class="mb-1 block text-sm text-gray-600">分组名称</label>
                    <input type="text" name="name" placeholder="分组名称" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">添加分组</button>
            </form>
        </div>
        
        <div class="overflow-x-auto rounded-lg bg-white shadow">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">分组名称</th>
                        <th class="px-4 py-3">用户数量</th>
                        <th class="px-4 py-3">创建时间</th>
                        <th class="px-4 py-3">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($groups as $group): ?>
                        <tr>
                            <td class="px-4 py-3"><?php echo $group['id']; ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($group['name']); ?></td>
                            <td class="px-4 py-3"><?php echo $group['user_count']; ?></td>
                            <td class="px-4 py-3"><?php echo $group['created_at']; ?></td>
                            <td class="px-4 py-3">
                                <?php if ($group['name'] !== '注册用户组'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                                        <button type="submit" class="rounded bg-red-600 px-3 py-1 text-xs text-white hover:bg-red-700" onclick="return confirm('确定删除此分组？该分组下的用户将移回默认分组')">删除</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-xs text-gray-500">默认分组</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>

