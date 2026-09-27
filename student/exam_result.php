<?php
require_once __DIR__ . '/../includes/config.php';
require_student_login();

$id = trim($_GET['id'] ?? '');
$student_id = $_SESSION['student_id'];

if ($id === '') {
    redirect('dashboard.php');
}

$stmt = $conn->prepare("SELECT * FROM results WHERE id = ? AND student_id = ?");
$stmt->bind_param("ss", $id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    set_flash('error', "Result not found.");
    redirect('dashboard.php');
}

$row = $result->fetch_assoc();
$stmt->close();

$malpractice = $row['status'] === 'Malpractice';
$percent = $row['total_questions'] > 0 ? round(($row['score'] / $row['total_questions']) * 100, 2) : 0;

$page_title = "Exam Result";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell" style="max-width: 680px;">
    <div class="es-card text-center">
        <h1 style="color: <?= $malpractice ? 'var(--bad)' : 'var(--good)' ?>;">
            <?= $malpractice ? 'Exam Cancelled — Malpractice Detected' : 'Exam Completed Successfully!' ?>
        </h1>

        <p class="text-muted">Result ID: <strong class="mono"><?= h($row['id']) ?></strong></p>

        <div class="divider"></div>

        <p><strong>Student:</strong> <?= h($row['student_name']) ?> (<?= h($row['student_id']) ?>)</p>
        <p><strong>Faculty:</strong> <?= h($row['faculty_name']) ?></p>

        <h1 style="font-size: 3rem; margin: 24px 0 6px; color: <?= $malpractice ? 'var(--bad)' : 'var(--navy-deep)' ?>;">
            <?= $row['score'] ?> / <?= $row['total_questions'] ?>
        </h1>
        <p style="font-size: 1.3rem; color: var(--ink-soft); margin-top:0;"><?= $percent ?>%</p>

        <div class="stars" style="margin: 14px 0;">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <?= $i <= $row['rating'] ? '★' : '<span class="off">☆</span>' ?>
            <?php endfor; ?>
        </div>

        <h2 style="margin-top: 18px;">Grade: <?= h($row['grade']) ?></h2>

        <div style="margin-top: 32px;">
            <?php if (!$malpractice): ?>
                <a href="certificate.php?id=<?= h($row['id']) ?>" class="btn btn-amber btn-lg">View Certificate</a>
            <?php endif; ?>
            <a href="dashboard.php" class="btn btn-outline btn-lg">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
