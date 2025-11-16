<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $user ?? ($_SESSION['user_name'] ?? null);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sity Cloud Control Room</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">Sity Cloud · TeamFlow</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (isset($_SESSION['user_name'])): ?>
                    <li class="nav-item text-white me-3 d-flex align-items-center">
                        أهلاً، <?= htmlspecialchars($_SESSION['user_name']); ?>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light" href="logout.php">تسجيل الخروج</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
