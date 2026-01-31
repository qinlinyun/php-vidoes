<?php
require_once 'includes/auth.php';
requireLogin();
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>观看记录</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

<div class="max-w-6xl mx-auto px-4 py-6">

<?php
$title = '我的观看记录';
$desc  = '自动保存的观看进度，可随时继续播放';
$actions = [
  ['label'=>'清空记录','icon'=>'fa-solid fa-trash','onclick'=>'clearAll()']
];
include 'components/page-header.php';
?>

<div id="emptyState" class="hidden">
  <?php include 'components/empty-state.php'; ?>
</div>

<div id="list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"></div>

</div>

<script>
let es = null;
let refreshing = false;

/* ================== SSE ================== */
function startSSE(){
  stopSSE();
  es = new EventSource('api/progress_sse.php');

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

/* ================== 数据 ================== */
async function loadList(){
  const r = await fetch('api/my_progress_list.php', {credentials:'same-origin'});
  const d = await r.json();

  const box = document.getElementById('list');
  const empty = document.getElementById('emptyState');

  if (!d.list || !d.list.length) {
    box.innerHTML = '';
    empty.classList.remove('hidden');
    return;
  }

  empty.classList.add('hidden');

  box.innerHTML = d.list.map(item => {
    const p = Number(item.progress_seconds||0);
    const du = Number(item.duration_seconds||0);
    const pct = du>0 ? Math.min(100, Math.round(p/du*100)) : 0;

    return `
      <div class="bg-white rounded-xl p-4 shadow-sm border">
        <div class="font-medium truncate">${escapeHtml(item.title)}</div>
        <div class="text-xs text-slate-500 mt-1">${escapeHtml(item.episode_name)}</div>

        <div class="mt-3 h-2 bg-slate-200 rounded">
          <div class="h-2 bg-red-500" style="width:${pct}%"></div>
        </div>

        <div class="mt-4 flex gap-2">
          <a onclick="stopSSE()"
             href="play.php?id=${item.video_id}&episode=${item.episode_id}&t=${p}"
             class="flex-1 text-center bg-red-500 text-white py-2 rounded">
            ▶ 继续观看
          </a>
          <button onclick="delOne(${item.video_id},${item.episode_id})"
            class="px-3 border rounded">🗑</button>
        </div>
      </div>
    `;
  }).join('');
}

/* ================== 操作 ================== */
function delOne(videoId, episodeId){
  Swal.fire({
    title:'确认删除？',
    icon:'warning',
    showCancelButton:true,
    confirmButtonColor:'#ef4444'
  }).then(async r=>{
    if(!r.isConfirmed) return;
    await fetch('api/delete_progress.php',{
      method:'POST',
      credentials:'same-origin',
      body:new URLSearchParams({video_id:videoId,episode_id:episodeId})
    });
  });
}

function clearAll(){
  Swal.fire({
    title:'清空所有记录？',
    icon:'warning',
    showCancelButton:true,
    confirmButtonColor:'#ef4444'
  }).then(async r=>{
    if(!r.isConfirmed) return;
    await fetch('api/clear_progress.php',{method:'POST',credentials:'same-origin'});
  });
}

function escapeHtml(str){
  return String(str)
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}

/* 启动 */
loadList();
startSSE();
</script>

</body>
</html>
