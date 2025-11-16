<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$taskId = (int)($_POST['task_id'] ?? 0);
if ($taskId) {
    $pdo->prepare('DELETE FROM task_comments WHERE task_id = ?')->execute([$taskId]);
    $pdo->prepare('DELETE FROM tasks WHERE id = ?')->execute([$taskId]);
}

header('Location: ../dashboard.php');
exit;
