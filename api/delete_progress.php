<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
$user = getCurrentUser();
header('Content-Type: application/json; charset=utf-8');

$videoId   = (int)($_POST['video_id'] ?? 0);
$episodeId = (int)($_POST['episode_id'] ?? 0);
if ($videoId <= 0 || $episodeId <= 0) { echo json_encode(['ok'=>false,'msg'=>'bad params']); exit; }

$pdo = getDB();
$pdo->beginTransaction();

$pdo->prepare("DELETE FROM video_watch_progress WHERE user_id=? AND video_id=? AND episode_id=?")
    ->execute([$user['id'], $videoId, $episodeId]);

$pdo->prepare("
INSERT INTO watch_progress_events (actor_user_id, target_user_id, action, video_id, episode_id)
VALUES (?, ?, 'delete', ?, ?)
")->execute([$user['id'], $user['id'], $videoId, $episodeId]);

$pdo->commit();
echo json_encode(['ok'=>true]);
