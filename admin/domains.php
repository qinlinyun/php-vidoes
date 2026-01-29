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
        $domain = trim($_POST['domain'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        // 去除协议
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = rtrim($domain, '/');
        
        if ($domain) {
            try {
                $stmt = $pdo->prepare("INSERT INTO domains (domain, display_name) VALUES (?, ?)");
                $stmt->execute([$domain, $displayName ?: null]);
                $message = '域名添加成功';
            } catch(PDOException $e) {
                $error = '域名已存在';
            }
        } else {
            $error = '请输入域名';
        }
    } elseif ($action === 'edit') {
        $domainId = $_POST['domain_id'] ?? 0;
        $domain = trim($_POST['domain'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        // 去除协议
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = rtrim($domain, '/');
        
        if ($domainId && $domain) {
            $stmt = $pdo->prepare("UPDATE domains SET domain = ?, display_name = ? WHERE id = ?");
            $stmt->execute([$domain, $displayName ?: null, $domainId]);
            $message = '域名更新成功';
        }
    } elseif ($action === 'delete') {
        $domainId = $_POST['domain_id'] ?? 0;
        if ($domainId) {
            $stmt = $pdo->prepare("DELETE FROM domains WHERE id = ?");
            $stmt->execute([$domainId]);
            $stmt = $pdo->prepare("DELETE FROM group_domains WHERE domain_id = ?");
            $stmt->execute([$domainId]);
            $message = '域名删除成功';
        }
    } elseif ($action === 'assign') {
        $groupId = $_POST['group_id'] ?? 0;
        $domainIds = $_POST['domain_ids'] ?? [];
        
        if ($groupId) {
            // 先删除该分组的所有域名
            $stmt = $pdo->prepare("DELETE FROM group_domains WHERE group_id = ?");
            $stmt->execute([$groupId]);
            
            // 添加新的域名分配
            if (!empty($domainIds)) {
                $stmt = $pdo->prepare("INSERT INTO group_domains (group_id, domain_id) VALUES (?, ?)");
                foreach ($domainIds as $domainId) {
                    $stmt->execute([$groupId, $domainId]);
                }
            }
            $message = '域名分配成功';
        }
    }
}

// 获取域名列表
$stmt = $pdo->query("SELECT * FROM domains ORDER BY id");
$domains = $stmt->fetchAll();

// 获取用户组列表
$stmt = $pdo->query("SELECT * FROM user_groups ORDER BY id");
$groups = $stmt->fetchAll();

// 获取分组域名分配情况
$groupDomains = [];
$stmt = $pdo->query("SELECT group_id, domain_id FROM group_domains");
while ($row = $stmt->fetch()) {
    if (!isset($groupDomains[$row['group_id']])) {
        $groupDomains[$row['group_id']] = [];
    }
    $groupDomains[$row['group_id']][] = $row['domain_id'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>域名管理 - 影视系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
    <nav class="bg-white shadow-sm">
        <div class="mx-auto max-w-screen-xl px-4 py-3">
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../index.php">首页</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="users.php">用户管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="groups.php">分组管理</a>
                <a class="rounded-full bg-gray-100 px-3 py-1" href="domains.php">域名管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="videos.php">视频管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../logout.php">退出</a>
            </div>
        </div>
    </nav>
    
    <main class="mx-auto max-w-screen-xl px-4 py-6">
        <h1 class="mb-4 text-lg font-semibold">域名管理</h1>
        
        <?php if ($message): ?>
            <div class="mb-4 rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-600"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="mb-6 rounded-lg bg-white p-4 shadow">
            <h2 class="mb-3 text-base font-semibold">添加域名</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add">
                <div>
                    <label class="mb-1 block text-sm text-gray-600">域名</label>
                    <input type="text" name="domain" placeholder="例如：example.com 或 https://example.com" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">显示名称（可选，用于播放页面显示）</label>
                    <input type="text" name="display_name" placeholder="例如：线路1、主线路等" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">添加域名</button>
            </form>
        </div>
        
        <div class="mb-6 rounded-lg bg-white p-4 shadow">
            <h2 class="mb-3 text-base font-semibold">域名列表</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">域名</th>
                            <th class="px-4 py-3">显示名称</th>
                            <th class="px-4 py-3">创建时间</th>
                            <th class="px-4 py-3">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php if (empty($domains)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">暂无域名</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($domains as $domain): ?>
                                <tr>
                                    <td class="px-4 py-3"><?php echo $domain['id']; ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($domain['domain']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($domain['display_name'] ?? '未设置'); ?></td>
                                    <td class="px-4 py-3"><?php echo $domain['created_at']; ?></td>
                                    <td class="px-4 py-3">
                                        <button class="rounded bg-blue-600 px-3 py-1 text-xs text-white hover:bg-blue-700" onclick="showEditDomain(<?php echo $domain['id']; ?>, '<?php echo htmlspecialchars($domain['domain'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($domain['display_name'] ?? '', ENT_QUOTES); ?>')">编辑</button>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="domain_id" value="<?php echo $domain['id']; ?>">
                                            <button type="submit" class="rounded bg-red-600 px-3 py-1 text-xs text-white hover:bg-red-700" onclick="return confirm('确定删除此域名？')">删除</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="rounded-lg bg-white p-4 shadow">
            <h2 class="mb-3 text-base font-semibold">分配域名给用户组</h2>
            <div class="space-y-4">
                <?php foreach ($groups as $group): ?>
                    <div class="rounded border border-gray-200 p-4">
                        <h3 class="mb-3 text-sm font-semibold"><?php echo htmlspecialchars($group['name']); ?></h3>
                        <form method="POST" class="space-y-3">
                            <input type="hidden" name="action" value="assign">
                            <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                            <div class="flex flex-wrap gap-4">
                                <?php foreach ($domains as $domain): ?>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="domain_ids[]" value="<?php echo $domain['id']; ?>"
                                               <?php echo (isset($groupDomains[$group['id']]) && in_array($domain['id'], $groupDomains[$group['id']])) ? 'checked' : ''; ?>>
                                        <span><?php echo htmlspecialchars($domain['domain']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="rounded bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">保存分配</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    
    <!-- 编辑域名模态框 -->
    <div id="editDomainModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50" style="display:none;">
        <div class="relative w-full max-w-md rounded-lg bg-white p-5 shadow">
            <button class="absolute right-3 top-2 text-xl text-gray-400 hover:text-gray-700" onclick="closeEditDomainModal()">&times;</button>
            <form method="POST" id="editDomainForm" class="space-y-3">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="domain_id" id="edit_domain_id">
                <h2 class="text-base font-semibold">编辑域名</h2>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">域名</label>
                    <input type="text" name="domain" id="edit_domain" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">显示名称</label>
                    <input type="text" name="display_name" id="edit_display_name" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">保存</button>
            </form>
        </div>
    </div>
    
    <script>
        function showEditDomain(id, domain, displayName) {
            document.getElementById('edit_domain_id').value = id;
            document.getElementById('edit_domain').value = domain;
            document.getElementById('edit_display_name').value = displayName || '';
            document.getElementById('editDomainModal').style.display = 'flex';
        }
        
        function closeEditDomainModal() {
            document.getElementById('editDomainModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('editDomainModal');
            if (event.target == modal) {
                closeEditDomainModal();
            }
        }
    </script>
</body>
</html>

