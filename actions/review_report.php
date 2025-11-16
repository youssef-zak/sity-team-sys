<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
if ($user['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

const BASE_COMPLETION_POINTS = 5;
const FAST_COMPLETION_BONUS_POINTS = 10;

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

$stmt = $pdo->prepare('SELECT tr.task_id, t.created_by, t.assigned_to, t.due_date, t.status AS task_status FROM task_reports tr INNER JOIN tasks t ON tr.task_id = t.id WHERE tr.id = ? LIMIT 1');
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

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('UPDATE task_reports SET review_status = ?, review_note = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
    $stmt->execute([$reviewStatus, $reviewNote ?: null, $user['id'], $reportId]);

    if ($reviewStatus === 'approved') {
        if ($report['task_status'] !== 'done') {
            $taskStmt = $pdo->prepare('UPDATE tasks SET status = "done", completed_at = NOW(), updated_at = NOW() WHERE id = ?');
            $taskStmt->execute([$report['task_id']]);

            $points = BASE_COMPLETION_POINTS;
            if (!empty($report['due_date'])) {
                $dueDate = new DateTime($report['due_date'] . ' 23:59:59');
                $now = new DateTime('now');
                if ($now <= $dueDate) {
                    $points += FAST_COMPLETION_BONUS_POINTS;
                }
            }

            $scoreStmt = $pdo->prepare('UPDATE users SET performance_points = performance_points + ? WHERE id = ?');
            $scoreStmt->execute([$points, $report['assigned_to']]);
        }
        $message = 'تم اعتماد التقرير وتسجيل النقاط';
    } else {
        $taskStmt = $pdo->prepare('UPDATE tasks SET status = "in_progress", updated_at = NOW() WHERE id = ?');
        $taskStmt->execute([$report['task_id']]);
        $message = 'تم طلب تعديل التقرير وإرجاع المهمة للعضو';
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ../dashboard.php?error=' . urlencode('تعذر حفظ قرار المراجعة'));
    exit;
}

header('Location: ../dashboard.php?success=' . urlencode($message));
exit;
