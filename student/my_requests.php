<?php
require_once __DIR__ . '/../includes/config.php';
require_student_login();

$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("
    SELECT * FROM exam_requests
    WHERE student_id = ?
    ORDER BY requested_at DESC
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "My Exam Requests";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell">
    <div class="page-head flex-between">
        <div>
            <div class="eyebrow">Exam Booking</div>
            <h1>My Exam Requests</h1>
            <p>Track approval status. Once approved, click "Start Exam" to begin.</p>
        </div>
        <a href="book_exam.php" class="btn btn-primary">+ New Request</a>
    </div>

    <?php require __DIR__ . '/../includes/flash.php'; ?>

    <div class="es-table-wrap">
        <table class="es-table">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Faculty</th>
                    <th>Requested On</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="5" class="text-muted text-center" style="padding:30px;">No exam requests yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td class="mono"><?= h($r['id']) ?></td>
                        <td><?= h($r['faculty_name']) ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($r['requested_at'])) ?></td>
                        <td>
                            <?php
                                $map = [
                                    'Pending'  => 'chip-warn',
                                    'Approved' => 'chip-good',
                                    'Rejected' => 'chip-bad',
                                    'Used'     => 'chip-neutral',
                                ];
                                $cls = $map[$r['status']] ?? 'chip-neutral';
                            ?>
                            <span class="chip <?= $cls ?>"><?= h($r['status']) ?></span>
                        </td>
                        <td>
                            <?php if ($r['status'] === 'Approved'): ?>
                                <a href="start_exam.php?req=<?= h($r['id']) ?>" class="btn btn-sm btn-success">Start Exam</a>
                            <?php elseif ($r['status'] === 'Pending'): ?>
                                <span class="text-muted" style="font-size:0.85rem;">Awaiting approval</span>
                            <?php elseif ($r['status'] === 'Used'): ?>
                                <span class="text-muted" style="font-size:0.85rem;">Completed</span>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
