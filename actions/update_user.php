<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$userId = (int)($_POST['user_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'member';
$status = $_POST['status'] ?? 'active';

if (!$userId || !$name || !$email) {
    header('Location: ../dashboard.php?error=' . urlencode('بيانات ناقصة'));
    exit;
}

$stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?');
$stmt->execute([$name, $email, $role, $status, $userId]);

header('Location: ../dashboard.php');
exit;
