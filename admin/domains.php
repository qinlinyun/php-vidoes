<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

requireAdmin();

$pdo = getDB();
$message = '';
$error = '';

// ================== 处理操作 ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $domain = trim($_POST['domain'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = rtrim($domain, '/');

        if ($domain) {
            try {
                $stmt = $pdo->prepare("INSERT INTO domains (domain, display_name) VALUES (?, ?)");
                $stmt->execute([$domain, $displayName ?: null]);
                $message = '域名添加成功';
            } catch (PDOException $e) {
                $error = '域名已存在';
            }
        } else {
            $error = '请输入域名';
        }
    }

    if ($action === 'edit') {
        $domainId = $_POST['domain_id'] ?? 0;
        $domain = trim($_POST['domain'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = rtrim($domain, '/');

        if ($domainId && $domain) {
            $stmt = $pdo->prepare("UPDATE domains SET domain = ?, display_name = ? WHERE id = ?");
            $stmt->execute([$domain, $displayName ?: null, $domainId]);
            $message = '域名更新成功';
        }
    }

    if ($action === 'delete') {
        $domainId = $_POST['domain_id'] ?? 0;
        if ($domainId) {
            $pdo->prepare("DELETE FROM domains WHERE id = ?")->execute([$domainId]);
            $pdo->prepare("DELETE FROM group_domains WHERE domain_id = ?")->execute([$domainId]);
            $message = '域名删除成功';
        }
    }

    if ($action === 'assign') {
        $groupId = $_POST['group_id'] ?? 0;
        $domainIds = $_POST['domain_ids'] ?? [];

        if ($groupId) {
            $pdo->prepare("DELETE FROM group_domains WHERE group_id = ?")->execute([$groupId]);
            if (!empty($domainIds)) {
                $stmt = $pdo->prepare("INSERT INTO group_domains (group_id, domain_id) VALUES (?, ?)");
                foreach ($domainIds as $did) {
                    $stmt->execute([$groupId, $did]);
                }
            }
            $message = '域名分配成功';
        }
    }
}

// ================== 数据 ==================
$domains = $pdo->query("SELECT * FROM domains ORDER BY id")->fetchAll();
$groups  = $pdo->query("SELECT * FROM user_groups ORDER BY id")->fetchAll();

$groupDomains = [];
$stmt = $pdo->query("SELECT group_id, domain_id FROM group_domains");
while ($r = $stmt->fetch()) {
    $groupDomains[$r['group_id']][] = $r['domain_id'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>域名管理</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://css.qinlinyun.cn/uploads/css/68cd3c66163ff-20260129212429-5fce6eb9.css?v=20260129212429"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
</head>
<body class="bg-gray-100 text-gray-900">

<nav class="bg-white shadow-sm">
<div class="mx-auto max-w-screen-xl px-4 py-3 flex gap-3 text-sm">
<a href="../index.php" class="rounded-full px-3 py-1 hover:bg-gray-100">首页</a>
<a href="users.php" class="rounded-full px-3 py-1 hover:bg-gray-100">用户管理</a>
<a href="groups.php" class="rounded-full px-3 py-1 hover:bg-gray-100">分组管理</a>
<a href="domains.php" class="rounded-full bg-gray-100 px-3 py-1">域名管理</a>
<a href="videos.php" class="rounded-full px-3 py-1 hover:bg-gray-100">视频管理</a>
<a href="notifications.php" class="rounded-full px-3 py-1 hover:bg-gray-100">站内通知</a>
<a href="feedback.php" class="rounded-full px-3 py-1 hover:bg-gray-100">意见反馈</a>
<a href="../logout.php" class="rounded-full px-3 py-1 hover:bg-gray-100">退出</a>
</div>
</nav>

<main class="mx-auto max-w-screen-xl px-4 py-6">
<h1 class="mb-6 text-lg font-semibold animate__animated animate__fadeInDown">
<i class="fa fa-globe"></i> 域名管理
</h1>

<?php if ($message): ?>
<div class="mb-4 rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-600 animate__animated animate__fadeIn">
<?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600 animate__animated animate__headShake">
<?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- 添加域名 -->
<div class="mb-6 rounded-lg bg-white p-4 shadow" data-aos="fade-up">
<h2 class="mb-3 text-base font-semibold"><i class="fa fa-plus"></i> 添加域名</h2>
<form method="POST" class="space-y-3">
<input type="hidden" name="action" value="add">
<input type="text" name="domain" required placeholder="example.com" class="w-full rounded border px-3 py-2 text-sm">
<input type="text" name="display_name" placeholder="显示名称（可选）" class="w-full rounded border px-3 py-2 text-sm">
<button class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
<i class="fa fa-check"></i> 添加
</button>
</form>
</div>

<!-- 域名列表 -->
<div class="mb-6 rounded-lg bg-white p-4 shadow" data-aos="fade-up" data-aos-delay="80">
<h2 class="mb-3 text-base font-semibold"><i class="fa fa-list"></i> 域名列表</h2>
<table class="w-full text-sm">
<thead class="bg-gray-100"><tr><th class="px-3 py-2">ID</th><th>域名</th><th>显示名</th><th>操作</th></tr></thead>
<tbody class="divide-y">
<?php foreach ($domains as $d): ?>
<tr>
<td class="px-3 py-2"><?= $d['id'] ?></td>
<td><?= htmlspecialchars($d['domain']) ?></td>
<td><?= htmlspecialchars($d['display_name'] ?? '未设置') ?></td>
<td class="space-x-2">
<button onclick="showEditDomain(<?= $d['id'] ?>,'<?= htmlspecialchars($d['domain'],ENT_QUOTES) ?>','<?= htmlspecialchars($d['display_name'] ?? '',ENT_QUOTES) ?>')" class="bg-blue-600 text-white px-2 py-1 rounded text-xs">
<i class="fa fa-pen"></i>
</button>
<form method="POST" class="inline">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="domain_id" value="<?= $d['id'] ?>">
<button onclick="return confirm('确定删除？')" class="bg-red-600 text-white px-2 py-1 rounded text-xs hover:animate__animated hover:animate__headShake">
<i class="fa fa-trash"></i>
</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- 域名分配（恢复） -->
<div class="rounded-lg bg-white p-4 shadow" data-aos="fade-up" data-aos-delay="120">
<h2 class="mb-3 text-base font-semibold"><i class="fa fa-users"></i> 分配域名给用户组</h2>
<div class="space-y-4">
<?php foreach ($groups as $g): ?>
<div class="border rounded p-4">
<h3 class="mb-2 text-sm font-semibold"><?= htmlspecialchars($g['name']) ?></h3>
<form method="POST" class="space-y-3">
<input type="hidden" name="action" value="assign">
<input type="hidden" name="group_id" value="<?= $g['id'] ?>">
<div class="flex flex-wrap gap-4">
<?php foreach ($domains as $d): ?>
<label class="flex items-center gap-2 text-sm">
<input type="checkbox" name="domain_ids[]" value="<?= $d['id'] ?>" <?= (isset($groupDomains[$g['id']]) && in_array($d['id'],$groupDomains[$g['id']]))?'checked':''; ?>>
<span><?= htmlspecialchars($d['domain']) ?></span>
</label>
<?php endforeach; ?>
</div>
<button class="rounded bg-red-600 px-4 py-2 text-xs text-white">保存分配</button>
</form>
</div>
<?php endforeach; ?>
</div>
</div>
</main>

<!-- 编辑域名弹窗 -->
<div id="editDomainModal" class="fixed inset-0 hidden items-center justify-center bg-black/50" style="display:none">
<div class="relative bg-white rounded-lg p-5 w-full max-w-md animate__animated animate__fadeIn animate__faster">
<button class="absolute right-3 top-2 text-gray-400 hover:text-gray-700" onclick="closeEdit()">
<i class="fa fa-times"></i>
</button>
<form method="POST" class="space-y-3">
<input type="hidden" name="action" value="edit">
<input type="hidden" name="domain_id" id="edit_domain_id">
<input type="text" name="domain" id="edit_domain" required class="w-full border px-3 py-2">
<input type="text" name="display_name" id="edit_display_name" class="w-full border px-3 py-2">
<button class="bg-red-600 text-white px-4 py-2 rounded">保存</button>
</form>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({ duration:300, once:true, easing:'ease-out' });

function showEditDomain(id, domain, name){
edit_domain_id.value=id;
edit_domain.value=domain;
edit_display_name.value=name;
document.getElementById('editDomainModal').style.display='flex';
}
function closeEdit(){document.getElementById('editDomainModal').style.display='none';}
</script>
</body>
</html>
