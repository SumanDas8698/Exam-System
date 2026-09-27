<?php
require_once __DIR__ . '/../includes/config.php';
require_admin_login();

// Approve / Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $req_id = $_POST['request_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!empty($req_id) && in_array($action, ['approve', 'reject'], true)) {
        $new_status = $action === 'approve' ? 'Approved' : 'Rejected';
        $stmt = $conn->prepare("UPDATE exam_requests SET status = ?, approved_at = NOW() WHERE id = ? AND status = 'Pending'");
        $stmt->bind_param("ss", $new_status, $req_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            set_flash('success', "Request " . h($req_id) . " has been " . strtolower($new_status) . ".");
        } else {
            set_flash('error', "Could not update request (it may already be processed).");
        }
        $stmt->close();
    }
    redirect('exam_requests.php');
}

$filter = $_GET['status'] ?? 'Pending';
$valid_filters = ['Pending', 'Approved', 'Rejected', 'Used', 'All'];
if (!in_array($filter, $valid_filters, true)) $filter = 'Pending';

if ($filter === 'All') {
    $requests = $conn->query("SELECT * FROM exam_requests ORDER BY requested_at DESC")->fetch_all(MYSQLI_ASSOC);
} else {
    $stmt = $conn->prepare("SELECT * FROM exam_requests WHERE status = ? ORDER BY requested_at DESC");
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$page_title = "Exam Requests";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell">
    <div class="page-head">
        <div class="eyebrow">Approvals</div>
        <h1>Exam Requests</h1>
        <p>Approve or reject student requests to sit a faculty exam.</p>
    </div>

    <?php require __DIR__ . '/../includes/flash.php'; ?>

    <div style="margin-bottom: 18px; display:flex; gap:8px; flex-wrap:wrap;">
        <?php foreach ($valid_filters as $f): ?>
            <a href="?status=<?= h($f) ?>" class="btn btn-sm <?= $filter === $f ? 'btn-primary' : 'btn-outline' ?>"><?= h($f) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="es-table-wrap">
        <table class="es-table">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Faculty</th>
                    <th>Requested On</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="7" class="text-muted text-center" style="padding:30px;">No requests found.</td></tr>
                <?php endif; ?>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td class="mono"><?= h($r['id']) ?></td>
                        <td><?= h($r['student_name']) ?> <span class="text-muted">(<?= h($r['student_id']) ?>)</span></td>
                        <td><?= h($r['course']) ?></td>
                        <td><?= h($r['faculty_name']) ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($r['requested_at'])) ?></td>
                        <td>
                            <?php
                                $map = ['Pending' => 'chip-warn', 'Approved' => 'chip-good', 'Rejected' => 'chip-bad', 'Used' => 'chip-neutral'];
                                $cls = $map[$r['status']] ?? 'chip-neutral';
                            ?>
                            <span class="chip <?= $cls ?>"><?= h($r['status']) ?></span>
                        </td>
                        <td>
                            <?php if ($r['status'] === 'Pending'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?= h($r['id']) ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <form method="post" style="display:inline;" onsubmit="return esConfirm('Reject this request?');">
                                    <input type="hidden" name="request_id" value="<?= h($r['id']) ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:0.85rem;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="../assets/js/app.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
