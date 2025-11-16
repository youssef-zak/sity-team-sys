<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$taskId = (int)($_POST['task_id'] ?? 0);
$status = $_POST['status'] ?? 'pending';
$comment = trim($_POST['comment'] ?? '');

$stmt = $pdo->prepare('SELECT assigned_to FROM tasks WHERE id = ?');
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    header('Location: ../dashboard.php?error=' . urlencode('المهمة مش موجودة'));
    exit;
}

if ($user['role'] !== 'admin' && $task['assigned_to'] != $user['id']) {
    header('Location: ../dashboard.php?error=' . urlencode('ما ينفعش تعدل مهمة غيرك'));
    exit;
}

$stmt = $pdo->prepare('UPDATE tasks SET status = ?, updated_at = NOW() WHERE id = ?');
$stmt->execute([$status, $taskId]);

if ($comment) {
    $stmt = $pdo->prepare('INSERT INTO task_comments (task_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$taskId, $user['id'], $comment]);
}

header('Location: ../dashboard.php');
exit;
