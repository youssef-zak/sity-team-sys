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

try {
    $totalMembers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    $pendingTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'")->fetchColumn();
    $inProgressTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'in_progress'")->fetchColumn();
    $doneTasks = (int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'done'")->fetchColumn();
} catch (Exception $e) {
    // ignore stats errors for demo
}

// Team members
$members = [];
if ($isAdmin) {
    $stmt = $pdo->query('SELECT id, name, email, role, status FROM users ORDER BY role DESC, name');
    $members = $stmt->fetchAll();
}

// Tasks
if ($isAdmin) {
    $stmt = $pdo->query('SELECT tasks.*, assignee.name AS assignee_name, creator.name AS creator_name FROM tasks INNER JOIN users AS assignee ON tasks.assigned_to = assignee.id INNER JOIN users AS creator ON tasks.created_by = creator.id ORDER BY tasks.priority DESC, tasks.due_date');
} else {
    $stmt = $pdo->prepare('SELECT tasks.*, assignee.name AS assignee_name, creator.name AS creator_name FROM tasks INNER JOIN users AS assignee ON tasks.assigned_to = assignee.id INNER JOIN users AS creator ON tasks.created_by = creator.id WHERE tasks.assigned_to = ? ORDER BY tasks.priority DESC, tasks.due_date');
    $stmt->execute([$user['id']]);
}
$tasks = $stmt->fetchAll();
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
</div>

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
                        <th>تحكم</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
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
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editMemberModal<?= $member['id']; ?>">تعديل</button>
                                <form action="actions/delete_user.php" method="POST" class="d-inline" onsubmit="return confirm('متأكد إنك عايز تمسحه؟');">
                                    <input type="hidden" name="user_id" value="<?= $member['id']; ?>">
                                    <button class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            </td>
                        </tr>

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
                            <span class="badge bg-<?php
                                switch ($task['status']) {
                                    case 'done':
                                        echo 'success';
                                        break;
                                    case 'in_progress':
                                        echo 'info';
                                        break;
                                    default:
                                        echo 'secondary';
                                }
                            ?> status-badge">
                                <?= $task['status']; ?>
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
                                    <form action="actions/update_task_status.php" method="POST" class="row g-2">
                                        <input type="hidden" name="task_id" value="<?= $task['id']; ?>">
                                        <div class="col-md-6">
                                            <label class="form-label">تعديل الحالة</label>
                                            <select class="form-select" name="status" <?= !$isAdmin && $task['assigned_to'] != $user['id'] ? 'disabled' : ''; ?>>
                                                <option value="pending" <?= $task['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="in_progress" <?= $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                <option value="done" <?= $task['status'] === 'done' ? 'selected' : ''; ?>>Done</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">تعليق</label>
                                            <input type="text" class="form-control" name="comment" placeholder="سيب ملاحظة لطيفة">
                                        </div>
                                        <div class="col-12 text-end">
                                            <button type="submit" class="btn btn-primary mt-3">حفظ التحديث</button>
                                        </div>
                                    </form>
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
