<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

const BASE_COMPLETION_POINTS = 5;
const FAST_COMPLETION_BONUS_POINTS = 10;
const REPORT_MAX_BYTES = 5 * 1024 * 1024; // 5MB

$user = getCurrentUser();
$taskId = (int)($_POST['task_id'] ?? 0);
$status = $_POST['status'] ?? 'pending';
$comment = trim($_POST['comment'] ?? '');
$reportSummary = trim($_POST['report_summary'] ?? '');
$allowedStatuses = ['pending', 'in_progress', 'done'];

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'pending';
}

$stmt = $pdo->prepare('SELECT id, assigned_to, status AS current_status, report_requirement, due_date, created_by FROM tasks WHERE id = ? LIMIT 1');
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    header('Location: ../dashboard.php?error=' . urlencode('المهمة مش موجودة'));
    exit;
}

if ($user['role'] !== 'admin' && (int)$task['assigned_to'] !== (int)$user['id']) {
    header('Location: ../dashboard.php?error=' . urlencode('ما ينفعش تعدل مهمة غيرك'));
    exit;
}

$reportRequired = $task['report_requirement'] === 'required';
$hasUpload = isset($_FILES['report_attachment']) && $_FILES['report_attachment']['error'] !== UPLOAD_ERR_NO_FILE;
$shouldProcessUpload = $status === 'done' && $hasUpload;

if ($status === 'done' && $reportRequired && !$reportSummary && !$hasUpload) {
    header('Location: ../dashboard.php?error=' . urlencode('لازم ترفع تقرير قبل ما تعتمد المهمة'));
    exit;
}

$attachmentPath = null;
if ($shouldProcessUpload) {
    $file = $_FILES['report_attachment'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Location: ../dashboard.php?error=' . urlencode('ملف التقرير مش جاهز، حاول تاني'));
        exit;
    }
    if ($file['size'] > REPORT_MAX_BYTES) {
        header('Location: ../dashboard.php?error=' . urlencode('حجم الملف أكبر من 5 ميجا'));
        exit;
    }

    $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'png', 'jpg', 'jpeg'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension && !in_array($extension, $allowedExtensions, true)) {
        header('Location: ../dashboard.php?error=' . urlencode('نوع الملف غير مدعوم')); 
        exit;
    }

    $reportsDir = __DIR__ . '/../storage/reports';
    if (!is_dir($reportsDir) && !mkdir($reportsDir, 0755, true) && !is_dir($reportsDir)) {
        header('Location: ../dashboard.php?error=' . urlencode('تعذر تجهيز مجلد المرفقات'));
        exit;
    }

    $fileName = uniqid('report_', true) . ($extension ? '.' . $extension : '');
    $targetPath = $reportsDir . '/' . $fileName;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        header('Location: ../dashboard.php?error=' . urlencode('فشل رفع الملف'));
        exit;
    }
    $attachmentPath = 'storage/reports/' . $fileName;
}

try {
    $pdo->beginTransaction();

    $updateSql = $status === 'done'
        ? 'UPDATE tasks SET status = ?, updated_at = NOW(), completed_at = NOW() WHERE id = ?'
        : 'UPDATE tasks SET status = ?, updated_at = NOW(), completed_at = NULL WHERE id = ?';
    $stmt = $pdo->prepare($updateSql);
    $stmt->execute([$status, $taskId]);

    if ($comment) {
        $stmt = $pdo->prepare('INSERT INTO task_comments (task_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$taskId, $user['id'], $comment]);
    }

    if ($status === 'done' && ($reportSummary || $attachmentPath || $reportRequired)) {
        $finalSummary = $reportSummary ?: 'تم التسليم بدون تفاصيل إضافية.';
        $stmt = $pdo->prepare('INSERT INTO task_reports (task_id, submitted_by, report_text, attachment_path) VALUES (?, ?, ?, ?)');
        $stmt->execute([$taskId, $user['id'], $finalSummary, $attachmentPath]);
    }

    if ($status === 'done' && $task['current_status'] !== 'done' && (int)$task['assigned_to'] === (int)$user['id']) {
        $points = BASE_COMPLETION_POINTS;
        if (!empty($task['due_date'])) {
            $dueDate = new DateTime($task['due_date'] . ' 23:59:59');
            $now = new DateTime('now');
            if ($now <= $dueDate) {
                $points += FAST_COMPLETION_BONUS_POINTS;
            }
        }

        $stmt = $pdo->prepare('UPDATE users SET performance_points = performance_points + ? WHERE id = ?');
        $stmt->execute([$points, $user['id']]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ../dashboard.php?error=' . urlencode('حصل خطأ أثناء تحديث المهمة'));
    exit;
}

header('Location: ../dashboard.php?success=' . urlencode('تم حفظ التحديث بنجاح'));
exit;
