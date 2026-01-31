<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
$user = getCurrentUser();
header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
$pdo->beginTransaction();

$pdo->prepare("DELETE FROM video_watch_progress WHERE user_id=?")->execute([$user['id']]);

$pdo->prepare("
INSERT INTO watch_progress_events (actor_user_id, target_user_id, action)
VALUES (?, ?, 'clear')
")->execute([$user['id'], $user['id']]);

$pdo->commit();
echo json_encode(['ok'=>true]);
