<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
if ($user['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

$reportId = (int)($_POST['report_id'] ?? 0);
$reviewStatus = $_POST['review_status'] ?? '';
$reviewNote = trim($_POST['review_note'] ?? '');
$allowedStatuses = ['approved', 'needs_changes'];

if (!$reportId || !in_array($reviewStatus, $allowedStatuses, true)) {
    header('Location: ../dashboard.php?error=' . urlencode('بيانات المراجعة غير مكتملة'));
    exit;
}

if ($reviewStatus === 'needs_changes' && !$reviewNote) {
    header('Location: ../dashboard.php?error=' . urlencode('لازم توضح المطلوب قبل طلب تعديل'));
    exit;
}

$stmt = $pdo->prepare('SELECT tr.task_id, t.created_by FROM task_reports tr INNER JOIN tasks t ON tr.task_id = t.id WHERE tr.id = ? LIMIT 1');
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    header('Location: ../dashboard.php?error=' . urlencode('التقرير غير موجود'));
    exit;
}

if ((int)$report['created_by'] !== (int)$user['id']) {
    header('Location: ../dashboard.php?error=' . urlencode('بس صاحب المهمة هو اللي يراجع التقرير'));
    exit;
}

$stmt = $pdo->prepare('UPDATE task_reports SET review_status = ?, review_note = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
$stmt->execute([$reviewStatus, $reviewNote ?: null, $user['id'], $reportId]);

header('Location: ../dashboard.php?success=' . urlencode('تم تسجيل قرار المراجعة'));
exit;
