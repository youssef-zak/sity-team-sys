<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - TeamFlow</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;">
<div class="card shadow-lg" style="min-width: 340px; max-width: 420px;">
    <div class="card-body p-4">
        <h3 class="text-center mb-3">TeamFlow</h3>
        <p class="text-muted text-center mb-4">سجّل دخولك وخش شوف الدنيا ماشية إزاي</p>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="actions/login_action.php">
            <div class="mb-3">
                <label for="email" class="form-label">الإيميل</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">الباسورد</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">دخول</button>
        </form>
    </div>
</div>
</body>
</html>
