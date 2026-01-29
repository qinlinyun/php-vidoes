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
    
    if ($action === 'add_video') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $cover = trim($_POST['cover'] ?? '');
        
        if ($title) {
            $stmt = $pdo->prepare("INSERT INTO videos (title, description, cover) VALUES (?, ?, ?)");
            $stmt->execute([$title, $description, $cover]);
            $message = '视频添加成功';
        } else {
            $error = '请输入视频标题';
        }
    } elseif ($action === 'edit_video') {
        $videoId = $_POST['video_id'] ?? 0;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $cover = trim($_POST['cover'] ?? '');
        
        if ($videoId && $title) {
            $stmt = $pdo->prepare("UPDATE videos SET title = ?, description = ?, cover = ? WHERE id = ?");
            $stmt->execute([$title, $description, $cover, $videoId]);
            $message = '视频更新成功';
        }
    } elseif ($action === 'delete_video') {
        $videoId = $_POST['video_id'] ?? 0;
        if ($videoId) {
            $stmt = $pdo->prepare("DELETE FROM video_episodes WHERE video_id = ?");
            $stmt->execute([$videoId]);
            $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
            $stmt->execute([$videoId]);
            $message = '视频删除成功';
        }
    } elseif ($action === 'add_episode') {
        $videoId = $_POST['video_id'] ?? 0;
        $episodeName = trim($_POST['episode_name'] ?? '');
        $videoUrl = trim($_POST['video_url'] ?? '');
        
        // 去除域名和协议
        $videoUrl = preg_replace('/^https?:\/\//', '', $videoUrl);
        // 去除域名部分，只保留路径
        if (preg_match('/^[^\/]+(\/.+)$/', $videoUrl, $matches)) {
            $videoUrl = $matches[1];
        } elseif (!preg_match('/^\//', $videoUrl)) {
            $videoUrl = '/' . $videoUrl;
        }
        
        if ($videoId && $episodeName && $videoUrl) {
            // 获取当前最大集数
            $stmt = $pdo->prepare("SELECT MAX(episode_order) as max_order FROM video_episodes WHERE video_id = ?");
            $stmt->execute([$videoId]);
            $result = $stmt->fetch();
            $order = ($result['max_order'] ?? 0) + 1;
            
            $stmt = $pdo->prepare("INSERT INTO video_episodes (video_id, episode_name, video_url, episode_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$videoId, $episodeName, $videoUrl, $order]);
            $message = '集数添加成功';
        } else {
            $error = '请填写所有字段';
        }
    } elseif ($action === 'edit_episode') {
        $episodeId = $_POST['episode_id'] ?? 0;
        $episodeName = trim($_POST['episode_name'] ?? '');
        $videoUrl = trim($_POST['video_url'] ?? '');
        
        // 去除域名和协议
        $videoUrl = preg_replace('/^https?:\/\//', '', $videoUrl);
        // 去除域名部分，只保留路径
        if (preg_match('/^[^\/]+(\/.+)$/', $videoUrl, $matches)) {
            $videoUrl = $matches[1];
        } elseif (!preg_match('/^\//', $videoUrl)) {
            $videoUrl = '/' . $videoUrl;
        }
        
        if ($episodeId && $episodeName && $videoUrl) {
            $stmt = $pdo->prepare("UPDATE video_episodes SET episode_name = ?, video_url = ? WHERE id = ?");
            $stmt->execute([$episodeName, $videoUrl, $episodeId]);
            $message = '集数更新成功';
        }
    } elseif ($action === 'delete_episode') {
        $episodeId = $_POST['episode_id'] ?? 0;
        if ($episodeId) {
            $stmt = $pdo->prepare("DELETE FROM video_episodes WHERE id = ?");
            $stmt->execute([$episodeId]);
            $message = '集数删除成功';
        }
    }
}

// 获取视频列表
$stmt = $pdo->query("SELECT v.*, COUNT(e.id) as episode_count FROM videos v 
                    LEFT JOIN video_episodes e ON v.id = e.video_id 
                    GROUP BY v.id 
                    ORDER BY v.created_at DESC");
$videos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>视频管理 - 影视系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
    <nav class="bg-white shadow-sm">
        <div class="mx-auto max-w-screen-xl px-4 py-3">
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../index.php">首页</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="users.php">用户管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="groups.php">分组管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="domains.php">域名管理</a>
                <a class="rounded-full bg-gray-100 px-3 py-1" href="videos.php">视频管理</a>
                <a class="rounded-full px-3 py-1 hover:bg-gray-100" href="../logout.php">退出</a>
            </div>
        </div>
    </nav>
    
    <main class="mx-auto max-w-screen-xl px-4 py-6">
        <h1 class="mb-4 text-lg font-semibold">视频管理</h1>
        
        <?php if ($message): ?>
            <div class="mb-4 rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-600"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-4 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="mb-6 rounded-lg bg-white p-4 shadow">
            <h2 class="mb-3 text-base font-semibold">添加视频</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_video">
                <div>
                    <label class="mb-1 block text-sm text-gray-600">视频标题</label>
                    <input type="text" name="title" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">视频描述</label>
                    <textarea name="description" rows="3" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">封面图片URL</label>
                    <input type="text" name="cover" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">添加视频</button>
            </form>
        </div>
        
        <div class="space-y-4">
            <?php foreach ($videos as $video): ?>
                <div class="rounded-lg bg-white p-4 shadow">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-base font-semibold"><?php echo htmlspecialchars($video['title']); ?></h3>
                        <div class="flex flex-wrap gap-2">
                            <button class="rounded bg-blue-600 px-3 py-1 text-xs text-white hover:bg-blue-700" onclick="showEditVideo(<?php echo $video['id']; ?>, '<?php echo htmlspecialchars($video['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($video['description'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($video['cover'] ?? '', ENT_QUOTES); ?>')">编辑</button>
                            <button class="rounded bg-gray-800 px-3 py-1 text-xs text-white hover:bg-gray-900" onclick="showEpisodes(<?php echo $video['id']; ?>)">管理集数</button>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="delete_video">
                                <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                                <button type="submit" class="rounded bg-red-600 px-3 py-1 text-xs text-white hover:bg-red-700" onclick="return confirm('确定删除？')">删除</button>
                            </form>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-600"><?php echo htmlspecialchars($video['description'] ?? ''); ?></p>
                    <p class="mt-1 text-xs text-gray-500">集数：<?php echo $video['episode_count']; ?></p>
                    
                    <div id="episodes-<?php echo $video['id']; ?>" class="mt-4 hidden border-t pt-4">
                        <h4 class="mb-2 text-sm font-semibold">集数管理</h4>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM video_episodes WHERE video_id = ? ORDER BY episode_order");
                        $stmt->execute([$video['id']]);
                        $episodes = $stmt->fetchAll();
                        ?>
                        <div class="space-y-2">
                            <?php foreach ($episodes as $episode): ?>
                                <div class="flex flex-wrap items-center gap-2 rounded border border-gray-200 p-2 text-sm">
                                    <span class="min-w-[80px] font-semibold"><?php echo htmlspecialchars($episode['episode_name']); ?></span>
                                    <span class="flex-1 truncate text-xs text-gray-500"><?php echo htmlspecialchars($episode['video_url']); ?></span>
                                    <button class="rounded bg-blue-600 px-2 py-1 text-xs text-white hover:bg-blue-700" onclick="showEditEpisode(<?php echo $episode['id']; ?>, '<?php echo htmlspecialchars($episode['episode_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($episode['video_url'], ENT_QUOTES); ?>')">编辑</button>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="delete_episode">
                                        <input type="hidden" name="episode_id" value="<?php echo $episode['id']; ?>">
                                        <button type="submit" class="rounded bg-red-600 px-2 py-1 text-xs text-white hover:bg-red-700" onclick="return confirm('确定删除？')">删除</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form method="POST" class="mt-3 flex flex-wrap gap-2">
                            <input type="hidden" name="action" value="add_episode">
                            <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                            <input type="text" name="episode_name" placeholder="集数名称" required class="flex-1 rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                            <input type="text" name="video_url" placeholder="视频链接（会自动去除域名）" required class="flex-1 rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                            <button type="submit" class="rounded bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">添加集数</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    
    <!-- 编辑视频模态框 -->
    <div id="editVideoModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50" style="display:none;">
        <div class="relative w-full max-w-md rounded-lg bg-white p-5 shadow">
            <button class="absolute right-3 top-2 text-xl text-gray-400 hover:text-gray-700" onclick="closeEditVideoModal()">&times;</button>
            <form method="POST" id="editVideoForm" class="space-y-3">
                <input type="hidden" name="action" value="edit_video">
                <input type="hidden" name="video_id" id="edit_video_id">
                <h2 class="text-base font-semibold">编辑视频</h2>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">视频标题</label>
                    <input type="text" name="title" id="edit_video_title" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">视频描述</label>
                    <textarea name="description" id="edit_video_description" rows="3" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">封面图片URL</label>
                    <input type="text" name="cover" id="edit_video_cover" class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">保存</button>
            </form>
        </div>
    </div>
    
    <!-- 编辑集数模态框 -->
    <div id="editEpisodeModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50" style="display:none;">
        <div class="relative w-full max-w-md rounded-lg bg-white p-5 shadow">
            <button class="absolute right-3 top-2 text-xl text-gray-400 hover:text-gray-700" onclick="closeEditEpisodeModal()">&times;</button>
            <form method="POST" id="editEpisodeForm" class="space-y-3">
                <input type="hidden" name="action" value="edit_episode">
                <input type="hidden" name="episode_id" id="edit_episode_id">
                <h2 class="text-base font-semibold">编辑集数</h2>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">集数名称</label>
                    <input type="text" name="episode_name" id="edit_episode_name" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-sm text-gray-600">视频链接</label>
                    <input type="text" name="video_url" id="edit_episode_url" required class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                </div>
                <button type="submit" class="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">保存</button>
            </form>
        </div>
    </div>
    
    <script>
        function showEpisodes(videoId) {
            const section = document.getElementById('episodes-' + videoId);
            section.classList.toggle('hidden');
        }
        
        function showEditVideo(id, title, description, cover) {
            document.getElementById('edit_video_id').value = id;
            document.getElementById('edit_video_title').value = title;
            document.getElementById('edit_video_description').value = description;
            document.getElementById('edit_video_cover').value = cover;
            document.getElementById('editVideoModal').style.display = 'flex';
        }
        
        function closeEditVideoModal() {
            document.getElementById('editVideoModal').style.display = 'none';
        }
        
        function showEditEpisode(id, name, url) {
            document.getElementById('edit_episode_id').value = id;
            document.getElementById('edit_episode_name').value = name;
            document.getElementById('edit_episode_url').value = url;
            document.getElementById('editEpisodeModal').style.display = 'flex';
        }
        
        function closeEditEpisodeModal() {
            document.getElementById('editEpisodeModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modals = ['editVideoModal', 'editEpisodeModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target == modal) {
                    if (modalId === 'editVideoModal') closeEditVideoModal();
                    if (modalId === 'editEpisodeModal') closeEditEpisodeModal();
                }
            });
        }
    </script>
</body>
</html>

