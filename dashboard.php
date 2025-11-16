<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = getCurrentUser();
$_SESSION['user_name'] = $user['name'];
$isAdmin = $user['role'] === 'admin';

// Overview stats
$totalMembers = 0;
$pendingTasks = 0;
$inProgressTasks = 0;
$doneTasks = 0;
$pendingReports = 0;
$awaitingReviewTasks = 0;
$topPerformers = [];
$myPoints = (int)($user['performance_points'] ?? 0);
$memberInsights = [];

try {
    $totalMembers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    $pendingTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'")->fetchColumn();
    $inProgressTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'in_progress'")->fetchColumn();
    $doneTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'done'")->fetchColumn();
    $pendingReports = (int)$pdo->query("SELECT COUNT(*) FROM task_reports WHERE review_status = 'pending'")->fetchColumn();
    $awaitingReviewTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'awaiting_review'")->fetchColumn();
    $topStmt = $pdo->query("SELECT name, performance_points FROM users WHERE role <> 'admin' ORDER BY performance_points DESC LIMIT 5");
    $topPerformers = $topStmt->fetchAll();
} catch (Exception $e) {
    // ignore stats errors for demo
}

// Team members
$members = [];
if ($isAdmin) {
    $stmt = $pdo->query('SELECT id, name, email, role, status, performance_points FROM users ORDER BY role DESC, name');
    $members = $stmt->fetchAll();

    if ($members) {
        $memberIds = array_column($members, 'id');
        $placeholders = implode(',', array_fill(0, count($memberIds), '?'));

        $statsSql = "SELECT assigned_to AS user_id,
                        COUNT(*) AS total_tasks,
                        SUM(status = 'done') AS completed_tasks,
                        SUM(status = 'awaiting_review') AS awaiting_review_tasks,
                        SUM(status = 'in_progress') AS in_progress_tasks,
                        SUM(status = 'pending') AS pending_tasks,
                        MAX(updated_at) AS last_activity
                    FROM tasks
                    WHERE assigned_to IN ($placeholders)
                    GROUP BY assigned_to";
        $statsStmt = $pdo->prepare($statsSql);
        $statsStmt->execute($memberIds);
        while ($row = $statsStmt->fetch()) {
            $memberInsights[$row['user_id']] = $row;
        }

        $reportSql = "SELECT submitted_by AS user_id, MAX(submitted_at) AS last_report
                      FROM task_reports
                      WHERE submitted_by IN ($placeholders)
                      GROUP BY submitted_by";
        $reportStmt = $pdo->prepare($reportSql);
        $reportStmt->execute($memberIds);
        while ($row = $reportStmt->fetch()) {
            $memberInsights[$row['user_id']]['last_report'] = $row['last_report'];
        }
    }
}

// Tasks
if ($isAdmin) {
    $stmt = $pdo->query('SELECT tasks.*, assignee.name AS assignee_name, creator.name AS creator_name FROM tasks INNER JOIN users AS assignee ON tasks.assigned_to = assignee.id INNER JOIN users AS creator ON tasks.created_by = creator.id ORDER BY tasks.priority DESC, tasks.due_date');
} else {
    $stmt = $pdo->prepare('SELECT tasks.*, assignee.name AS assignee_name, creator.name AS creator_name FROM tasks INNER JOIN users AS assignee ON tasks.assigned_to = assignee.id INNER JOIN users AS creator ON tasks.created_by = creator.id WHERE tasks.assigned_to = ? ORDER BY tasks.priority DESC, tasks.due_date');
    $stmt->execute([$user['id']]);
}
$tasks = $stmt->fetchAll();
$taskIds = array_column($tasks, 'id');
$reportsByTask = [];
if ($taskIds) {
    $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
    $reportStmt = $pdo->prepare("SELECT tr.*, submitter.name AS submitter_name, reviewer.name AS reviewer_name FROM task_reports tr INNER JOIN users submitter ON tr.submitted_by = submitter.id LEFT JOIN users reviewer ON tr.reviewed_by = reviewer.id WHERE tr.task_id IN ($placeholders) ORDER BY tr.submitted_at DESC");
    $reportStmt->execute($taskIds);
    while ($row = $reportStmt->fetch()) {
        $reportsByTask[$row['task_id']][] = $row;
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">عدد الفريق</p>
                        <h3><?= $totalMembers; ?></h3>
                    </div>
                    <span class="card-icon text-primary">👥</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Pending</p>
                        <h3><?= $pendingTasks; ?></h3>
                    </div>
                    <span class="card-icon text-warning">⏳</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">In Progress</p>
                        <h3><?= $inProgressTasks; ?></h3>
                    </div>
                    <span class="card-icon text-info">🚧</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Done</p>
                        <h3><?= $doneTasks; ?></h3>
                    </div>
                    <span class="card-icon text-success">✅</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">في انتظار مراجعة المدير</p>
                        <h3><?= $awaitingReviewTasks; ?></h3>
                    </div>
                    <span class="card-icon text-danger">📂</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php if ($isAdmin): ?>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100 bg-light">
            <div class="card-body">
                <p class="text-muted mb-1">تقارير تنتظر مراجعة مدير Sity Cloud</p>
                <div class="d-flex justify-content-between align-items-end">
                    <h2 class="mb-0 text-danger"><?= $pendingReports; ?></h2>
                    <small>راجع التقارير واعتمدها أو اطلب تعديلات</small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-1">رصيد نقاط Sity Cloud الخاص بك</p>
                <div class="d-flex justify-content-between align-items-end">
                    <h2 class="mb-0 text-primary"><?= $myPoints; ?></h2>
                    <small>كل تسليم منظم وسريع = نقاط أعلى وظهور في لوحة الشرف</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin && $topPerformers): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">لوحة الشرف – أسرع أعضاء Sity Cloud</h5>
            <span class="badge bg-primary">Top 5</span>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0">
                <thead>
                    <tr>
                        <th>العضو</th>
                        <th>النقاط</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topPerformers as $performer): ?>
                        <tr>
                            <td><?= htmlspecialchars($performer['name']); ?></td>
                            <td><span class="badge bg-success"><?= (int)$performer['performance_points']; ?> نقطة</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<ul class="nav nav-pills mb-3" id="dashboardTabs" role="tablist">
    <?php if ($isAdmin): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="members-tab" data-bs-toggle="pill" data-bs-target="#members" type="button" role="tab">الفريق</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tasks-tab" data-bs-toggle="pill" data-bs-target="#tasks" type="button" role="tab">المهام</button>
        </li>
    <?php else: ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tasks-tab" data-bs-toggle="pill" data-bs-target="#tasks" type="button" role="tab">مهامي</button>
        </li>
    <?php endif; ?>
</ul>
<div class="tab-content" id="dashboardTabsContent">
    <?php if ($isAdmin): ?>
    <div class="tab-pane fade show active" id="members" role="tabpanel">
        <div class="d-flex justify-content-between mb-3">
            <h5>أعضاء الفريق</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">إضافة عضو</button>
        </div>
        <div class="table-responsive bg-white shadow-sm rounded p-3">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الإيميل</th>
                        <th>الدور</th>
                        <th>الحالة</th>
                        <th>النقاط</th>
                        <th>تحكم</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <?php
                        $insight = $memberInsights[$member['id']] ?? [
                            'total_tasks' => 0,
                            'completed_tasks' => 0,
                            'awaiting_review_tasks' => 0,
                            'in_progress_tasks' => 0,
                            'pending_tasks' => 0,
                            'last_activity' => null,
                            'last_report' => null,
                        ];
                        $completionRate = $insight['total_tasks'] ? round(($insight['completed_tasks'] / $insight['total_tasks']) * 100) : 0;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($member['name']); ?></td>
                            <td><?= htmlspecialchars($member['email']); ?></td>
                            <td><?= $member['role'] === 'admin' ? 'مدير' : 'عضو'; ?></td>
                            <td>
                                <span class="badge bg-<?= $member['status'] === 'active' ? 'success' : 'secondary'; ?> status-badge">
                                    <?= $member['status'] === 'active' ? 'شغال' : 'متوقف'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-dark"><?= (int)$member['performance_points']; ?></span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#memberProfileModal<?= $member['id']; ?>">بروفايل</button>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editMemberModal<?= $member['id']; ?>">تعديل</button>
                                <form action="actions/delete_user.php" method="POST" class="d-inline" onsubmit="return confirm('متأكد إنك عايز تمسحه؟');">
                                    <input type="hidden" name="user_id" value="<?= $member['id']; ?>">
                                    <button class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            </td>
                        </tr>

                        <div class="modal fade" id="memberProfileModal<?= $member['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">بروفايل <?= htmlspecialchars($member['name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                            <div>
                                                <p class="mb-1"><strong>الإيميل:</strong> <?= htmlspecialchars($member['email']); ?></p>
                                                <p class="mb-1"><strong>الدور:</strong> <?= $member['role'] === 'admin' ? 'مدير Sity Cloud' : 'عضو فريق'; ?></p>
                                                <p class="mb-0"><strong>الحالة:</strong> <?= $member['status'] === 'active' ? 'شغال' : 'متوقف'; ?></p>
                                            </div>
                                            <div class="text-end">
                                                <p class="text-muted mb-1">رصيد النقاط</p>
                                                <h3 class="mb-0 text-primary"><?= (int)$member['performance_points']; ?> نقطة</h3>
                                                <small>نسبة الإنجاز: <?= $completionRate; ?>%</small>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <div class="border rounded-3 p-3 text-center">
                                                    <p class="text-muted mb-1">إجمالي المهام</p>
                                                    <h4 class="mb-0"><?= (int)$insight['total_tasks']; ?></h4>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="border rounded-3 p-3 text-center">
                                                    <p class="text-muted mb-1">منتهية</p>
                                                    <h4 class="text-success mb-0"><?= (int)$insight['completed_tasks']; ?></h4>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="border rounded-3 p-3 text-center">
                                                    <p class="text-muted mb-1">بانتظار المراجعة</p>
                                                    <h4 class="text-warning mb-0"><?= (int)$insight['awaiting_review_tasks']; ?></h4>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="border rounded-3 p-3 text-center">
                                                    <p class="text-muted mb-1">قيد التنفيذ</p>
                                                    <h4 class="text-info mb-0"><?= (int)$insight['in_progress_tasks']; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-3 mt-1">
                                            <div class="col-md-6">
                                                <div class="border rounded-3 p-3 h-100">
                                                    <p class="text-muted mb-1">مهام لم تبدأ بعد</p>
                                                    <h4 class="mb-0"><?= (int)$insight['pending_tasks']; ?></h4>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="border rounded-3 p-3 h-100">
                                                    <p class="text-muted mb-1">آخر تقرير</p>
                                                    <h5 class="mb-0"><?= !empty($insight['last_report']) ? htmlspecialchars($insight['last_report']) : 'لسه مفيش تقرير'; ?></h5>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <p class="mb-1"><strong>آخر نشاط على مهمة:</strong> <?= !empty($insight['last_activity']) ? htmlspecialchars($insight['last_activity']) : 'لم يحدث تحديث بعد'; ?></p>
                                            <p class="mb-0 text-muted">كل الأرقام بتتحدث تلقائي بعد كل اعتماد أو تعديل مهمة.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="editMemberModal<?= $member['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">تعديل بيانات <?= htmlspecialchars($member['name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="actions/update_user.php" method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="user_id" value="<?= $member['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">الاسم</label>
                                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($member['name']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">الإيميل</label>
                                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($member['email']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">الدور</label>
                                                <select class="form-select" name="role">
                                                    <option value="member" <?= $member['role'] === 'member' ? 'selected' : ''; ?>>عضو</option>
                                                    <option value="admin" <?= $member['role'] === 'admin' ? 'selected' : ''; ?>>مدير</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">الحالة</label>
                                                <select class="form-select" name="status">
                                                    <option value="active" <?= $member['status'] === 'active' ? 'selected' : ''; ?>>شغال</option>
                                                    <option value="inactive" <?= $member['status'] === 'inactive' ? 'selected' : ''; ?>>متوقف</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                            <button type="submit" class="btn btn-primary">حفظ</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="tab-pane fade <?= $isAdmin ? '' : 'show active'; ?>" id="tasks" role="tabpanel">
        <div class="d-flex justify-content-between mb-3">
            <h5><?= $isAdmin ? 'كل المهام' : 'المهام المكلف بيها'; ?></h5>
            <?php if ($isAdmin): ?>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTaskModal">إضافة مهمة</button>
            <?php endif; ?>
        </div>
        <div class="table-responsive bg-white shadow-sm rounded p-3">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>العنوان</th>
                        <th>الوصف</th>
                        <th>المسؤول</th>
                        <th>الأولوية</th>
                        <th>التقرير</th>
                        <th>الحالة</th>
                        <th>الموعد النهائي</th>
                        <th>تحكم</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= htmlspecialchars($task['title']); ?></td>
                        <td><?= htmlspecialchars($task['description']); ?></td>
                        <td><?= htmlspecialchars($task['assignee_name']); ?></td>
                        <td><?= htmlspecialchars($task['priority']); ?></td>
                        <td>
                            <span class="badge bg-<?= $task['report_requirement'] === 'required' ? 'danger' : 'secondary'; ?>">
                                <?= $task['report_requirement'] === 'required' ? 'إلزامي' : 'اختياري'; ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $statusLabelMap = [
                                'pending' => 'بانتظار البدء',
                                'in_progress' => 'قيد التنفيذ',
                                'awaiting_review' => 'بانتظار المراجعة',
                                'done' => 'منتهي'
                            ];
                            switch ($task['status']) {
                                case 'done':
                                    $statusClass = 'success';
                                    break;
                                case 'in_progress':
                                    $statusClass = 'info';
                                    break;
                                case 'awaiting_review':
                                    $statusClass = 'warning';
                                    break;
                                default:
                                    $statusClass = 'secondary';
                            }
                            ?>
                            <span class="badge bg-<?= $statusClass; ?> status-badge">
                                <?= htmlspecialchars($statusLabelMap[$task['status']] ?? $task['status']); ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($task['due_date']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewTaskModal<?= $task['id']; ?>">عرض</button>
                            <?php if ($isAdmin): ?>
                                <form action="actions/delete_task.php" method="POST" class="d-inline" onsubmit="return confirm('تمسح المهمة؟');">
                                    <input type="hidden" name="task_id" value="<?= $task['id']; ?>">
                                    <button class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <div class="modal fade" id="viewTaskModal<?= $task['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">تفاصيل <?= htmlspecialchars($task['title']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>الوصف:</strong> <?= nl2br(htmlspecialchars($task['description'])); ?></p>
                                    <p><strong>المسؤول:</strong> <?= htmlspecialchars($task['assignee_name']); ?></p>
                                    <p><strong>الأولوية:</strong> <?= htmlspecialchars($task['priority']); ?></p>
                                    <p><strong>الحالة الحالية:</strong> <?= htmlspecialchars($task['status']); ?></p>
                                    <p><strong>تم الإنشاء بواسطة:</strong> <?= htmlspecialchars($task['creator_name']); ?></p>
                                    <p><strong>موعد التسليم:</strong> <?= htmlspecialchars($task['due_date']); ?></p>
                                    <hr>
                                    <form action="actions/update_task_status.php" method="POST" class="row g-3" enctype="multipart/form-data">
                                        <input type="hidden" name="task_id" value="<?= $task['id']; ?>">
                                        <div class="col-md-6">
                                            <label class="form-label">تعديل الحالة</label>
                                            <select class="form-select" name="status" <?= !$isAdmin && $task['assigned_to'] != $user['id'] ? 'disabled' : ''; ?>>
                                                <option value="pending" <?= $task['status'] === 'pending' ? 'selected' : ''; ?>>بانتظار البدء</option>
                                                <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : ''; ?>>قيد التنفيذ</option>
                                                <option value="awaiting_review" <?= $task['status'] === 'awaiting_review' ? 'selected' : ''; ?> <?= $isAdmin ? '' : 'disabled'; ?>>بانتظار المراجعة</option>
                                                <option value="done" <?= $task['status'] === 'done' ? 'selected' : ''; ?>>منتهي</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">تعليق</label>
                                            <input type="text" class="form-control" name="comment" placeholder="سيب ملاحظة لطيفة">
                                        </div>
                                        <div class="col-12">
                                            <div class="alert alert-warning py-2 small mb-0 <?= $task['report_requirement'] === 'required' ? '' : 'd-none'; ?>">
                                                هذه المهمة تتبع معايير Sity Cloud؛ عند اختيار "Done" لازم ترفق تقرير مختصر وملفات إن وجدت.
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">تقرير التسليم</label>
                                            <textarea class="form-control" name="report_summary" rows="3" placeholder="اشرح اللي اتعمل والملفات اللي اترفعت" <?= !$isAdmin && $task['assigned_to'] != $user['id'] ? 'disabled' : ''; ?>></textarea>
                                            <div class="form-text">المحتوى ده بيروح مباشرةً للمدير المسؤول عن المهمة.</div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">مرفق (اختياري)</label>
                                            <input type="file" class="form-control" name="report_attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.png,.jpg,.jpeg" <?= !$isAdmin && $task['assigned_to'] != $user['id'] ? 'disabled' : ''; ?>>
                                            <div class="form-text">حد أقصى 5 ميجا. استخدمه لرفع مستندات أو لقطات تدعم التقرير.</div>
                                        </div>
                                        <div class="col-12 text-end">
                                            <button type="submit" class="btn btn-primary">حفظ التحديث</button>
                                        </div>
                                    </form>

                                    <?php $taskReports = $reportsByTask[$task['id']] ?? []; ?>
                                    <hr>
                                    <h6>تقارير المهمة</h6>
                                    <?php if ($taskReports): ?>
                                        <?php foreach ($taskReports as $report): ?>
                                            <div class="border rounded-3 p-3 mb-3">
                                                <div class="d-flex justify-content-between">
                                                    <strong><?= htmlspecialchars($report['submitter_name']); ?></strong>
                                                    <?php
                                                    $statusClass = 'secondary';
                                                    if ($report['review_status'] === 'approved') {
                                                        $statusClass = 'success';
                                                    } elseif ($report['review_status'] === 'needs_changes') {
                                                        $statusClass = 'warning';
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?= $statusClass; ?>"><?= htmlspecialchars($report['review_status']); ?></span>
                                                </div>
                                                <small class="text-muted">مرسل بتاريخ <?= htmlspecialchars($report['submitted_at']); ?></small>
                                                <p class="mt-2 mb-1"><?= nl2br(htmlspecialchars($report['report_text'])); ?></p>
                                                <?php if (!empty($report['attachment_path'])): ?>
                                                    <a href="<?= htmlspecialchars($report['attachment_path']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">تحميل المرفق</a>
                                                <?php endif; ?>
                                                <?php if (!empty($report['review_note'])): ?>
                                                    <div class="alert alert-info mt-2 py-2 px-3">
                                                        <strong>ملحوظة المراجع:</strong> <?= nl2br(htmlspecialchars($report['review_note'])); ?>
                                                        <?php if ($report['reviewer_name']): ?>
                                                            <br><small class="text-muted">بواسطة <?= htmlspecialchars($report['reviewer_name']); ?> في <?= htmlspecialchars($report['reviewed_at']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($isAdmin && $task['created_by'] == $user['id'] && $report['review_status'] === 'pending'): ?>
                                                    <form action="actions/review_report.php" method="POST" class="mt-3">
                                                        <input type="hidden" name="report_id" value="<?= $report['id']; ?>">
                                                        <div class="row g-2 align-items-end">
                                                            <div class="col-md-4">
                                                                <label class="form-label">قرار المراجعة</label>
                                                                <select class="form-select" name="review_status" required>
                                                                    <option value="approved">اعتماد</option>
                                                                    <option value="needs_changes">مطلوب تعديل</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">ملاحظة للعضو</label>
                                                                <input type="text" class="form-control" name="review_note" placeholder="وضح المطلوب">
                                                            </div>
                                                            <div class="col-md-2 text-end">
                                                                <button type="submit" class="btn btn-success">حفظ</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">لا يوجد تقارير بعد.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة عضو جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/add_user.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الاسم</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الإيميل</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الباسورد</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الدور</label>
                        <select class="form-select" name="role">
                            <option value="member">عضو</option>
                            <option value="admin">مدير</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">مهمة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/add_task.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">العنوان</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تعيين إلى</label>
                        <select class="form-select" name="assigned_to" required>
                            <?php foreach ($members as $member): ?>
                                <?php if ($member['role'] !== 'admin'): ?>
                                    <option value="<?= $member['id']; ?>"><?= htmlspecialchars($member['name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الأولوية</label>
                        <select class="form-select" name="priority">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">وضع التقرير</label>
                        <select class="form-select" name="report_requirement">
                            <option value="required">إلزامي - لازم العضو يرفع تقرير</option>
                            <option value="optional">اختياري - على حسب المهمة</option>
                        </select>
                        <div class="form-text">سيتم تنبيه العضو أن كل تسليم في Sity Cloud يحتاج توثيق واضح.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ التسليم</label>
                        <input type="date" class="form-control" name="due_date">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success">إضافة</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
