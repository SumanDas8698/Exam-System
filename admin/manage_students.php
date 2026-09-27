<?php
require_once __DIR__ . '/../includes/config.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $action     = $_POST['action'] ?? '';

    if (!empty($student_id) && $action === 'toggle_status') {
        $stmt = $conn->prepare("SELECT status FROM students WHERE id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $new_status = $row['status'] === 'Active' ? 'Blocked' : 'Active';
            $upd = $conn->prepare("UPDATE students SET status = ? WHERE id = ?");
            $upd->bind_param("ss", $new_status, $student_id);
            $upd->execute();
            $upd->close();
            set_flash('success', "Student " . h($student_id) . " is now " . $new_status . ".");
        }
    } elseif (!empty($student_id) && $action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $stmt->close();
        set_flash('success', "Student " . h($student_id) . " has been deleted along with their records.");
    }
    redirect('manage_students.php');
}

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM students WHERE id LIKE ? OR student_name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $students = $conn->query("SELECT * FROM students ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
}

$page_title = "Manage Students";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell">
    <div class="page-head">
        <div class="eyebrow">Student Records</div>
        <h1>Manage Students</h1>
        <p>View registered students, block or unblock accounts, or remove a record entirely.</p>
    </div>

    <?php require __DIR__ . '/../includes/flash.php'; ?>

    <form method="get" style="margin-bottom: 18px; max-width:420px;">
        <input type="text" name="q" class="form-control" placeholder="Search by ID, name, or email"
               value="<?= h($search) ?>">
    </form>

    <div class="es-table-wrap">
        <table class="es-table">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="7" class="text-muted text-center" style="padding:30px;">No students found.</td></tr>
                <?php endif; ?>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td class="mono"><?= h($s['id']) ?></td>
                        <td><?= h($s['student_name']) ?></td>
                        <td><?= h($s['email']) ?></td>
                        <td><?= h($s['course']) ?></td>
                        <td>
                            <span class="chip <?= $s['status'] === 'Active' ? 'chip-good' : 'chip-bad' ?>"><?= h($s['status']) ?></span>
                        </td>
                        <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                        <td style="white-space:nowrap;">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="student_id" value="<?= h($s['id']) ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <button type="submit" class="btn btn-sm btn-outline">
                                    <?= $s['status'] === 'Active' ? 'Block' : 'Unblock' ?>
                                </button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return esConfirm('Delete this student and all their results permanently?');">
                                <input type="hidden" name="student_id" value="<?= h($s['id']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="../assets/js/app.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
