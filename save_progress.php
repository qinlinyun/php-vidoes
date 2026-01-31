<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();
$user = getCurrentUser();
$pdo = getDB();

$videoId   = (int)($_POST['video_id'] ?? 0);
$episodeId = (int)($_POST['episode_id'] ?? 0);
$progress  = (int)($_POST['progress'] ?? 0);

if (!$videoId || !$episodeId) exit('invalid');

$stmt = $pdo->prepare("
    INSERT INTO video_progress (user_id, video_id, episode_id, progress_seconds)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE progress_seconds = VALUES(progress_seconds)
");
$stmt->execute([$user['id'], $videoId, $episodeId, $progress]);

echo 'ok';
