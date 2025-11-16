<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$userId = (int)($_POST['user_id'] ?? 0);
if ($userId) {
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role != "admin"');
    $stmt->execute([$userId]);
}

header('Location: ../dashboard.php');
exit;
