<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
$user = getCurrentUser();
header('Content-Type: application/json; charset=utf-8');

$videoId   = (int)($_GET['video_id'] ?? 0);
$episodeId = (int)($_GET['episode_id'] ?? 0);
if ($videoId <= 0 || $episodeId <= 0) { echo json_encode(['ok'=>false,'msg'=>'bad params']); exit; }

$pdo = getDB();
$stmt = $pdo->prepare("
  SELECT progress_seconds, duration_seconds
  FROM video_watch_progress
  WHERE user_id=? AND video_id=? AND episode_id=?
  LIMIT 1
");
$stmt->execute([$user['id'], $videoId, $episodeId]);
$row = $stmt->fetch();

echo json_encode([
  'ok' => true,
  'progress' => (int)($row['progress_seconds'] ?? 0),
  'duration' => (int)($row['duration_seconds'] ?? 0),
]);
