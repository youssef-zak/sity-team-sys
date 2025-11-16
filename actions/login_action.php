<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare('SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    header('Location: ../login.php?error=' . urlencode('بيانات الدخول مش صح'));
    exit;
}

if ($user['status'] !== 'active') {
    header('Location: ../login.php?error=' . urlencode('الحساب غير مفعل'));
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];

header('Location: ../dashboard.php');
exit;
