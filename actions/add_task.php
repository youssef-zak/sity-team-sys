<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$currentUser = getCurrentUser();

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$assignedTo = (int)($_POST['assigned_to'] ?? 0);
$priority = $_POST['priority'] ?? 'medium';
$dueDate = $_POST['due_date'] ?? null;

if (!$title || !$assignedTo) {
    header('Location: ../dashboard.php?error=' . urlencode('كمل البيانات'));
    exit;
}

$stmt = $pdo->prepare('INSERT INTO tasks (title, description, assigned_to, status, priority, created_by, due_date, created_at) VALUES (?, ?, ?, "pending", ?, ?, ?, NOW())');
$stmt->execute([$title, $description, $assignedTo, $priority, $currentUser['id'], $dueDate]);

header('Location: ../dashboard.php');
exit;
