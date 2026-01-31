<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
$user = getCurrentUser();
header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
$stmt = $pdo->prepare("
  SELECT
    p.video_id,
    p.episode_id,
    p.progress_seconds,
    p.duration_seconds,
    p.updated_at,
    v.title,
    v.cover,
    e.episode_name
  FROM video_watch_progress p
  JOIN videos v ON v.id = p.video_id
  JOIN video_episodes e ON e.id = p.episode_id
  WHERE p.user_id = ?
  ORDER BY p.updated_at DESC
  LIMIT 500
");
$stmt->execute([$user['id']]);
echo json_encode(['ok'=>true, 'list'=>$stmt->fetchAll()]);
