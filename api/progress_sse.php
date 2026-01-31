<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

requireLogin();
$user = getCurrentUser();
$pdo  = getDB();

/* SSE 头 */
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

@ini_set('zlib.output_compression', 0);
@ini_set('output_buffering', 'off');
@ini_set('implicit_flush', 1);
while (ob_get_level() > 0) ob_end_flush();
ob_implicit_flush(true);

/* 参数 */
$mode   = ($_GET['mode'] ?? 'user') === 'admin' ? 'admin' : 'user';
$lastId = (int)($_SERVER['HTTP_LAST_EVENT_ID'] ?? 0);
$uid    = (int)$user['id'];

if ($mode === 'admin' && !isAdmin()) {
    echo "event:error\ndata:{}\n\n";
    exit;
}

$start = time();
$maxSeconds = 10;   // ❗最多 10 秒自动断开
$sleepSec   = 2;    // ❗2 秒轮询一次（非常轻）

echo "event:hello\ndata:{}\n\n";
flush();

while (time() - $start < $maxSeconds) {
    if (connection_aborted()) break;

    if ($mode === 'admin') {
        $stmt = $pdo->prepare("
            SELECT id FROM watch_progress_events
            WHERE id > ?
            ORDER BY id ASC
            LIMIT 1
        ");
        $stmt->execute([$lastId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id FROM watch_progress_events
            WHERE id > ? AND target_user_id = ?
            ORDER BY id ASC
            LIMIT 1
        ");
        $stmt->execute([$lastId, $uid]);
    }

    $row = $stmt->fetch();

    if ($row) {
        $lastId = (int)$row['id'];
        echo "id: {$lastId}\n";
        echo "event:update\n";
        echo "data: {}\n\n";
        flush();
        break; // ❗有事件立即结束
    }

    echo "event:ping\ndata:{}\n\n";
    flush();
    sleep($sleepSec);
}
