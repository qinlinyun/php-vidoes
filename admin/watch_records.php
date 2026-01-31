<?php
require_once '../includes/auth.php';
requireLogin();
if (!isAdmin()) {
    http_response_code(403);
    exit('403 Forbidden');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>所有用户观看记录</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-slate-100 text-slate-800">

    <!-- ✅ 顶部导航 -->
<div class="bg-white border-b sticky top-0 z-40">
  <div class="max-w-6xl mx-auto px-4 h-12 flex items-center justify-between">
    
    <a href="/"
       onclick="stopSSE()"
       class="flex items-center gap-2 text-sm text-slate-700 hover:text-red-500 transition">
      <i class="fa-solid fa-house"></i>
      <span>返回主页</span>
    </a>
  </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-6">

<?php
$title = '所有用户观看记录';
$desc  = '实时同步的全站观看行为（管理员）';
$actions = [
  ['label'=>'刷新','icon'=>'fa-solid fa-rotate','onclick'=>'loadList()']
];
include '../components/page-header.php';
?>

<div id="list" class="space-y-3"></div>

</div>

<script>
let es = null;
let refreshing = false;

/* ========== 加载数据 ========== */
async function loadList(){
  const r = await fetch('../api/admin_watch_records.php', {credentials:'same-origin'});
  const d = await r.json();
  const box = document.getElementById('list');

  if (!d.list || !d.list.length) {
    box.innerHTML = `
      <div class="text-center py-20 text-slate-400">
        <i class="fa-regular fa-folder-open text-4xl mb-3"></i>
        <div>暂无数据</div>
      </div>`;
    return;
  }

  box.innerHTML = d.list.map(item => {
    const pct = item.duration_seconds > 0
      ? Math.min(100, Math.round(item.progress_seconds / item.duration_seconds * 100))
      : 0;

    return `
      <div class="bg-white rounded-xl p-4 shadow-sm border">
        <div class="flex justify-between items-start gap-4">
          <div>
            <div class="font-medium">
              <i class="fa-solid fa-user mr-1 text-slate-400"></i>
              ${item.username}
            </div>
            <div class="text-sm text-slate-500">
              ${item.title} · ${item.episode_name}
            </div>
          </div>

          <a
            onclick="stopSSE()"
            href="../play.php?id=${item.video_id}&episode=${item.episode_id}&t=${item.progress_seconds}"
            class="text-sm text-red-500 hover:text-red-600">
            ▶ 查看
          </a>
        </div>

        <div class="mt-3 h-2 bg-slate-200 rounded">
          <div class="h-2 bg-red-500" style="width:${pct}%"></div>
        </div>

        <div class="mt-1 text-xs text-slate-500">
          ${pct}% · ${item.updated_at}
        </div>
      </div>
    `;
  }).join('');
}

/* ========== SSE ========== */
function startSSE(){
  stopSSE();
  es = new EventSource('../api/progress_sse.php?mode=admin');

  es.addEventListener('update', () => {
    if (refreshing) return;
    refreshing = true;
    setTimeout(async () => {
      await loadList();
      refreshing = false;
    }, 800);
  });
}

function stopSSE(){
  try { es && es.close(); } catch(e){}
  es = null;
}

document.addEventListener('visibilitychange', () => {
  if (document.hidden) stopSSE();
});

/* 启动 */
loadList();
startSSE();
</script>

</body>
</html>
