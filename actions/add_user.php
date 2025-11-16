<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'member';

if (!$name || !$email || !$password) {
    header('Location: ../dashboard.php?error=' . urlencode('بيانات ناقصة'));
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, "active")');
$stmt->execute([$name, $email, $hash, $role]);

header('Location: ../dashboard.php');
exit;
