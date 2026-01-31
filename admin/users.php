<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

requireAdmin();

$pdo = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? 0;

    if ($userId) {
        switch ($action) {
            case 'ban':
                $pdo->prepare("UPDATE users SET status='banned', ban_until=NULL WHERE id=?")->execute([$userId]);
                $message = '用户已封禁';
                break;
            case 'ban_custom':
                if (!empty($_POST['ban_until'])) {
                    $pdo->prepare("UPDATE users SET status='banned', ban_until=? WHERE id=?")
                        ->execute([$_POST['ban_until'], $userId]);
                    $message = '用户已定时封禁';
                }
                break;
            case 'delete':
                $pdo->prepare("DELETE FROM users WHERE id=? AND username!='admin'")->execute([$userId]);
                $message = '用户已删除';
                break;
            case 'freeze':
                $pdo->prepare("UPDATE users SET status='frozen' WHERE id=?")->execute([$userId]);
                $message = '用户已冻结';
                break;
            case 'unfreeze':
            case 'unban':
                $pdo->prepare("UPDATE users SET status='active', ban_until=NULL WHERE id=?")->execute([$userId]);
                $message = '用户状态已恢复';
                break;
            case 'reset_password':
                if (!empty($_POST['new_password']) && strlen($_POST['new_password']) >= 6) {
                    $pdo->prepare("UPDATE users SET password=? WHERE id=?")
                        ->execute([password_hash($_POST['new_password'], PASSWORD_DEFAULT), $userId]);
                    $message = '密码已重置';
                } else {
                    $error = '密码至少 6 位';
                }
                break;
            case 'change_group':
                if (!empty($_POST['group_id'])) {
                    $pdo->prepare("UPDATE users SET group_id=? WHERE id=?")
                        ->execute([$_POST['group_id'], $userId]);
                    $message = '用户分组已更改';
                }
                break;
        }
    }
}

$users = $pdo->query("
    SELECT u.*, g.name AS group_name
    FROM users u
    LEFT JOIN user_groups g ON u.group_id=g.id
    ORDER BY u.created_at DESC
")->fetchAll();

$groups = $pdo->query("SELECT * FROM user_groups ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>用户管理 - 竹叶云控平台</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://css.qinlinyun.cn/uploads/css/68cd3c66163ff-20260129212429-5fce6eb9.css?v=20260129212429"></script>
</head>

<body class="bg-gray-100 text-gray-900">

<!-- 顶部导航 -->
<nav class="bg-white shadow-sm">
    <div class="mx-auto max-w-screen-xl px-4 py-3 flex gap-3 text-sm">
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../index.php">首页</a>
        <a class="rounded-full bg-gray-100 px-3 py-1" href="users.php">用户管理</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="groups.php">分组管理</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="domains.php">域名管理</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="videos.php">视频管理</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="register_info.php">注册信息</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="notifications.php">站内通知</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="feedback.php">意见反馈</a>
        <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../logout.php">退出</a>
    </div>
</nav>

<main class="mx-auto max-w-screen-xl px-4 py-6">
<h1 class="mb-4 text-lg font-semibold">用户管理</h1>

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
<tr class="hover:bg-gray-50">
<td class="px-4 py-3"><?= $user['id'] ?></td>
<td class="px-4 py-3 font-medium"><?= htmlspecialchars($user['username']) ?></td>
<td class="px-4 py-3"><?= htmlspecialchars($user['email']) ?></td>
<td class="px-4 py-3"><?= htmlspecialchars($user['group_name'] ?? '未分组') ?></td>
<td class="px-4 py-3">
<?php
$badge = [
'active'=>'bg-green-100 text-green-700',
'banned'=>'bg-red-100 text-red-700',
'frozen'=>'bg-yellow-100 text-yellow-700'
][$user['status']] ?? 'bg-gray-100 text-gray-600';
?>
<span class="rounded-full px-2 py-0.5 text-xs <?= $badge ?>">
<?= $user['status'] ?><?= $user['ban_until'] ? ' (至 '.$user['ban_until'].')' : '' ?>
</span>
</td>
<td class="px-4 py-3"><?= $user['created_at'] ?></td>
<td class="px-4 py-3">
<div class="flex flex-wrap gap-2 text-xs">
<?php if ($user['status'] === 'banned'): ?>
<button class="rounded bg-green-600 px-2 py-1 text-white hover:bg-green-700" onclick="unbanUser(<?= $user['id'] ?>)">解封</button>
<?php else: ?>
<button class="rounded bg-red-600 px-2 py-1 text-white hover:bg-red-700" onclick="showModal('ban', <?= $user['id'] ?>)">封禁</button>
<button class="rounded bg-orange-500 px-2 py-1 text-white hover:bg-orange-600" onclick="showModal('ban_custom', <?= $user['id'] ?>)">定时封禁</button>
<?php endif; ?>

<button class="rounded bg-red-600 px-2 py-1 text-white hover:bg-red-700" onclick="if(confirm('确定删除？')){deleteUser(<?= $user['id'] ?>)}">删除</button>

<?php if ($user['status'] === 'frozen'): ?>
<button class="rounded bg-green-600 px-2 py-1 text-white hover:bg-green-700" onclick="unfreezeUser(<?= $user['id'] ?>)">解冻</button>
<?php else: ?>
<button class="rounded bg-amber-500 px-2 py-1 text-white hover:bg-amber-600" onclick="freezeUser(<?= $user['id'] ?>)">冻结</button>
<?php endif; ?>

<button class="rounded bg-blue-600 px-2 py-1 text-white hover:bg-blue-700" onclick="showModal('reset_password', <?= $user['id'] ?>)">重置密码</button>
<button class="rounded bg-gray-800 px-2 py-1 text-white hover:bg-gray-900" onclick="showModal('change_group', <?= $user['id'] ?>, '<?= $user['group_id'] ?>')">更改分组</button>

</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</main>

<!-- 模态框 -->
<div id="modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
<div class="relative w-full max-w-md rounded-lg bg-white p-5 shadow">
<button class="absolute right-3 top-2 text-xl text-gray-400 hover:text-gray-700" onclick="closeModal()">&times;</button>
<div id="modal-body"></div>
</div>
</div>

<script>
function showModal(action, userId, currentGroupId = '') {
    const modal = document.getElementById('modal');
    const body = document.getElementById('modal-body');
    let html = '';

    if(action==='ban'){
        html=`<h2 class="mb-3 text-base font-semibold">封禁用户</h2>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="ban">
            <input type="hidden" name="user_id" value="${userId}">
            <p class="text-sm text-gray-600">确定要封禁此用户吗？</p>
            <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">确认封禁</button>
        </form>`;
    } else if(action==='ban_custom'){
        html=`<h2 class="mb-3 text-base font-semibold">定时封禁用户</h2>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="ban_custom">
            <input type="hidden" name="user_id" value="${userId}">
            <div>
                <label class="block text-sm text-gray-600 mb-1">封禁至（日期时间）</label>
                <input type="datetime-local" name="ban_until" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
            </div>
            <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">确认封禁</button>
        </form>`;
    } else if(action==='reset_password'){
        html=`<h2 class="mb-3 text-base font-semibold">重置用户密码</h2>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" value="${userId}">
            <div>
                <label class="mb-1 block text-sm text-gray-600">新密码</label>
                <input type="password" name="new_password" required minlength="6" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
            </div>
            <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">确认重置</button>
        </form>`;
    } else if(action==='change_group'){
        html=`<h2 class="mb-3 text-base font-semibold">更改用户分组</h2>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="change_group">
            <input type="hidden" name="user_id" value="${userId}">
            <div>
                <label class="mb-1 block text-sm text-gray-600">选择用户组</label>
                <select name="group_id" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                    <?php foreach ($groups as $g): ?>
                    <option value="<?= $g['id'] ?>" ${currentGroupId=='<?= $g['id'] ?>'?'selected':''}>
                        <?= htmlspecialchars($g['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="rounded bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-900">确认更改</button>
        </form>`;
    }

    body.innerHTML = html;
    modal.style.display = 'flex';
}

function closeModal(){document.getElementById('modal').style.display='none'}

function deleteUser(userId){postAction('delete',userId)}
function freezeUser(userId){postAction('freeze',userId)}
function unfreezeUser(userId){postAction('unfreeze',userId)}
function unbanUser(userId){postAction('unban',userId)}

function postAction(action,userId){
    const f=document.createElement('form');
    f.method='POST';
    f.innerHTML=`<input type="hidden" name="action" value="${action}">
                 <input type="hidden" name="user_id" value="${userId}">`;
    document.body.appendChild(f);f.submit();
}

window.onclick=function(e){if(e.target==document.getElementById('modal'))closeModal()}
</script>

</body>
</html>
