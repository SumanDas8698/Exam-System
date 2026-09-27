<?php
/**
 * get_result_fragment.php — returns a result summary as an HTML fragment
 * (no <html>/<head>), intended for AJAX injection into a modal.
 * Accessible by the owning student or any logged-in admin.
 */
require_once __DIR__ . '/../includes/config.php';

$id = trim($_GET['id'] ?? '');
$is_admin = isset($_SESSION['admin_id']);
$is_student = isset($_SESSION['student_id']);

if ($id === '' || (!$is_admin && !$is_student)) {
    http_response_code(403);
    echo '<div class="alert alert-danger"><span>Access denied.</span></div>';
    exit();
}

$stmt = $conn->prepare("SELECT * FROM results WHERE id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-warning"><span>Result not found.</span></div>';
    exit();
}

$row = $result->fetch_assoc();
$stmt->close();

if (!$is_admin && $_SESSION['student_id'] !== $row['student_id']) {
    http_response_code(403);
    echo '<div class="alert alert-danger"><span>Access denied.</span></div>';
    exit();
}

$percent = $row['total_questions'] > 0 ? round(($row['score'] / $row['total_questions']) * 100, 2) : 0;
$malpractice = $row['status'] === 'Malpractice';
?>

<?php if ($malpractice): ?>
    <div class="alert alert-danger">
        <span><strong>Malpractice detected.</strong> No certificate was issued for this attempt.</span>
    </div>
<?php endif; ?>

<div class="text-center">
    <p><strong>Student:</strong> <?= h($row['student_name']) ?> (<?= h($row['student_id']) ?>)</p>
    <p><strong>Faculty:</strong> <?= h($row['faculty_name']) ?></p>
    <h2 style="margin: 18px 0 4px;"><?= $row['score'] ?> / <?= $row['total_questions'] ?></h2>
    <p class="text-muted"><?= $percent ?>%</p>
    <p><strong>Grade:</strong> <?= h($row['grade']) ?></p>
    <p class="stars">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <?= $i <= $row['rating'] ? '★' : '<span class="off">☆</span>' ?>
        <?php endfor; ?>
        <span class="text-muted">(<?= $row['rating'] ?>/5)</span>
    </p>
    <p class="text-muted" style="font-size:0.85rem;">Date: <?= date('d M Y, h:i A', strtotime($row['date_time'])) ?></p>

    <?php if (!$malpractice): ?>
        <a href="../student/certificate.php?id=<?= h($row['id']) ?>" target="_blank" class="btn btn-amber" style="margin-top:14px;">
            Open Full Certificate
        </a>
    <?php endif; ?>
</div>
