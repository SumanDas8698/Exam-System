<?php
require_once __DIR__ . '/../includes/config.php';
require_student_login();

$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("SELECT * FROM results WHERE student_id = ? ORDER BY date_time DESC");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = "My Results";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell">
    <div class="page-head">
        <div class="eyebrow">Exam History</div>
        <h1>My Results</h1>
        <p>All your completed exam attempts and certificates.</p>
    </div>

    <div class="es-table-wrap">
        <table class="es-table">
            <thead>
                <tr>
                    <th>Faculty</th>
                    <th>Score</th>
                    <th>Percentage</th>
                    <th>Grade</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="8" class="text-muted text-center" style="padding:30px;">No exam attempts yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($results as $r):
                    $percent = $r['total_questions'] > 0 ? round($r['score'] / $r['total_questions'] * 100, 2) : 0;
                    $badge = $percent >= 70 ? 'chip-good' : ($percent >= 50 ? 'chip-warn' : 'chip-bad');
                ?>
                    <tr>
                        <td><?= h($r['faculty_name']) ?></td>
                        <td><?= $r['score'] ?> / <?= $r['total_questions'] ?></td>
                        <td><span class="chip <?= $badge ?>"><?= $percent ?>%</span></td>
                        <td><?= h($r['grade']) ?></td>
                        <td class="stars" style="font-size:0.95rem;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?= $i <= $r['rating'] ? '★' : '<span class="off">☆</span>' ?>
                            <?php endfor; ?>
                        </td>
                        <td>
                            <span class="chip <?= $r['status'] === 'Completed' ? 'chip-good' : 'chip-bad' ?>"><?= h($r['status']) ?></span>
                        </td>
                        <td><?= date('d M Y, h:i A', strtotime($r['date_time'])) ?></td>
                        <td>
                            <?php if ($r['status'] === 'Completed'): ?>
                                <a href="certificate.php?id=<?= h($r['id']) ?>" class="btn btn-sm btn-outline">Certificate</a>
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
