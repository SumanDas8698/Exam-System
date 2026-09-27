<?php
require_once __DIR__ . '/../includes/config.php';
require_admin_login();

$search_results = [];
$error = '';
$search_code = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['code']) && trim($_GET['code']) !== '') {
    $search_code = strtoupper(trim($_GET['code']));

    $stmt = $conn->prepare("
        SELECT id, student_id, student_name, course, email, exam_topic_id, faculty_name,
               score, total_questions,
               ROUND((score / total_questions * 100), 2) AS percentage,
               grade, status, date_time, rating, attempt_date
        FROM results
        WHERE student_id = ?
        ORDER BY date_time DESC
    ");
    $stmt->bind_param("s", $search_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $search_results = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($search_results)) {
        $error = "No exam records found for student ID " . h($search_code) . ".";
    }
}

$page_title = "Exam History";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell">
    <div class="page-head">
        <div class="eyebrow">Student Records</div>
        <h1>Student Exam History &amp; Results</h1>
        <p>Search a student's full exam record by Student ID.</p>
    </div>

    <div class="es-card" style="margin-bottom: 26px; max-width: 640px;">
        <form method="GET" style="display:flex; gap:12px;">
            <input type="text" name="code" class="form-control" placeholder="Enter Student ID (e.g., STU001)"
                   value="<?= h($search_code) ?>" required autofocus>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-warning"><span><?= h($error) ?></span></div>
    <?php elseif (!empty($search_results)): ?>
        <h3>Results for: <strong><?= h($search_results[0]['student_name']) ?></strong> (<?= h($search_code) ?>)</h3>

        <div class="es-table-wrap" style="margin-top: 16px;">
            <table class="es-table">
                <thead>
                    <tr>
                        <th>#</th><th>Course</th><th>Faculty</th><th>Score</th><th>Percentage</th>
                        <th>Grade</th><th>Rating</th><th>Status</th><th>Date</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($search_results as $row):
                        $percent = $row['percentage'] ?? 0;
                        $badge = $percent >= 70 ? 'chip-good' : ($percent >= 50 ? 'chip-warn' : 'chip-bad');
                        $rating = !empty($row['rating']) ? (int)$row['rating'] : 0;
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= h($row['course'] ?: 'N/A') ?></td>
                            <td><?= h($row['faculty_name'] ?: 'Not recorded') ?></td>
                            <td><?= $row['score'] ?> / <?= $row['total_questions'] ?></td>
                            <td><span class="chip <?= $badge ?>"><?= number_format($percent, 2) ?>%</span></td>
                            <td><?= h($row['grade'] ?: 'N/A') ?></td>
                            <td class="stars" style="font-size:0.9rem;">
                                <?php for ($s = 0; $s < 5; $s++): ?>
                                    <?= $s < $rating ? '★' : '<span class="off">☆</span>' ?>
                                <?php endfor; ?>
                            </td>
                            <td><span class="chip <?= $row['status'] === 'Completed' ? 'chip-good' : 'chip-bad' ?>"><?= h($row['status']) ?></span></td>
                            <td><?= date('d M Y, h:i A', strtotime($row['date_time'])) ?></td>
                            <td>
                                <button onclick="printResult('<?= h($row['id']) ?>')" class="btn btn-sm <?= $row['status'] === 'Malpractice' ? 'btn-danger' : 'btn-primary' ?>">
                                    <?= $row['status'] === 'Malpractice' ? 'View Warning' : 'Print Result' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Print Modal -->
<div class="es-modal-backdrop" id="printModal">
    <div class="es-modal" style="max-width: 800px;">
        <div class="es-modal-head">
            <h3>Student Result Certificate</h3>
            <button type="button" class="close-x" onclick="esCloseModal('printModal')">&times;</button>
        </div>
        <div class="es-modal-body" id="printContent">
            <div class="text-center" style="padding: 50px 0;">Loading result...</div>
        </div>
        <div class="es-modal-foot">
            <button type="button" class="btn btn-outline" onclick="esCloseModal('printModal')">Close</button>
        </div>
    </div>
</div>

<script src="../assets/js/app.js"></script>
<script>
function printResult(resultId) {
    const modalContent = document.getElementById('printContent');
    modalContent.innerHTML = '<div class="text-center" style="padding:50px 0;">Loading result...</div>';
    esOpenModal('printModal');

    fetch('../student/get_result_fragment.php?id=' + encodeURIComponent(resultId))
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(html => { modalContent.innerHTML = html; })
        .catch(error => {
            modalContent.innerHTML = '<div class="alert alert-danger"><span>Error loading result: ' + error.message + '</span></div>';
        });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
