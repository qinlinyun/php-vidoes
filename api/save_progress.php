<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
$user = getCurrentUser();
header('Content-Type: application/json; charset=utf-8');

// ✅ 排除管理员：不记录也不广播
if (isAdmin()) { echo json_encode(['ok'=>true,'skipped'=>true]); exit; }

$videoId   = (int)($_POST['video_id'] ?? 0);
$episodeId = (int)($_POST['episode_id'] ?? 0);
$progress  = (int)($_POST['progress'] ?? 0);
$duration  = (int)($_POST['duration'] ?? 0);

if ($videoId <= 0 || $episodeId <= 0) { echo json_encode(['ok'=>false,'msg'=>'bad params']); exit; }
if ($progress < 0) $progress = 0;
if ($duration < 0) $duration = 0;
if ($duration > 0 && $progress > $duration) $progress = $duration;

$pdo = getDB();
$pdo->beginTransaction();

$stmt = $pdo->prepare("
INSERT INTO video_watch_progress (user_id, video_id, episode_id, progress_seconds, duration_seconds)
VALUES (?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
  progress_seconds = VALUES(progress_seconds),
  duration_seconds = VALUES(duration_seconds),
  updated_at = CURRENT_TIMESTAMP
");
$stmt->execute([$user['id'], $videoId, $episodeId, $progress, $duration]);

$ev = $pdo->prepare("
INSERT INTO watch_progress_events (actor_user_id, target_user_id, action, video_id, episode_id)
VALUES (?, ?, 'save', ?, ?)
");
$ev->execute([$user['id'], $user['id'], $videoId, $episodeId]);

$pdo->commit();
echo json_encode(['ok'=>true]);
