<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$taskId = (int)($_POST['task_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($taskId && $comment) {
    $stmt = $pdo->prepare('INSERT INTO task_comments (task_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$taskId, $user['id'], $comment]);
}

header('Location: ../dashboard.php');
exit;
