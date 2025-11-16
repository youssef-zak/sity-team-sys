<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

function getCurrentUser(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare('SELECT id, name, email, role, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void
{
    $user = getCurrentUser();
    if (!$user || $user['role'] !== 'admin') {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied';
        exit;
    }
}
