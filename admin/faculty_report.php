<?php
require_once __DIR__ . '/../includes/config.php';
require_admin_login();
require_once __DIR__ . '/../includes/SimpleXLSXGen.php';

$faculties = $conn->query("SELECT id, name FROM faculty ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

$selected_faculty_id = $_GET['faculty_id'] ?? '';
$student_records = [];

if (!empty($selected_faculty_id)) {
    $stmt = $conn->prepare("
        SELECT r.student_id, r.student_name, r.course, f.name AS faculty_name,
               r.score, r.total_questions,
               ROUND((r.score / r.total_questions * 100), 2) AS percentage,
               r.grade, r.rating, r.status, r.date_time
        FROM results r
        JOIN faculty f ON r.exam_topic_id = f.id
        WHERE r.exam_topic_id = ?
        ORDER BY r.student_id ASC, r.date_time DESC
    ");
    $stmt->bind_param("s", $selected_faculty_id);
    $stmt->execute();
    $student_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// === Export to Excel ===
if (isset($_GET['export']) && $_GET['export'] === 'excel' && !empty($selected_faculty_id)) {
    $faculty_name_for_file = 'Unknown';
    foreach ($faculties as $f) {
        if ($f['id'] === $selected_faculty_id) { $faculty_name_for_file = $f['name']; break; }
    }

    $data = [['#', 'Student ID', 'Student Name', 'Course', 'Faculty', 'Score', 'Percentage', 'Grade', 'Rating', 'Status', 'Date']];

    $i = 1;
    foreach ($student_records as $rec) {
        $stars = '';
        for ($r = 1; $r <= 5; $r++) {
            $stars .= $r <= $rec['rating'] ? '★' : '☆';
        }
        $rating_text = $stars . ' (' . $rec['rating'] . '/5)';

        $data[] = [
            $i++, $rec['student_id'], $rec['student_name'], $rec['course'], $rec['faculty_name'],
            $rec['score'] . ' / ' . $rec['total_questions'], $rec['percentage'] . '%', $rec['grade'],
            $rating_text, $rec['status'], date('d M Y h:i A', strtotime($rec['date_time'])),
        ];
    }

    $filename = "Faculty_Report_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $faculty_name_for_file) . "_" . date('Y-m-d') . ".xlsx";

    $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
    $xlsx->downloadAs($filename);
    exit();
}

$page_title = "Faculty Report";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell">
    <div class="page-head">
        <div class="eyebrow">Reports</div>
        <h1>Faculty-wise Student Performance</h1>
        <p>Select a faculty to view all student attempts, and export to Excel.</p>
    </div>

    <div class="es-card" style="margin-bottom: 26px;">
        <form method="GET" style="display:flex; gap:16px; align-items:end; flex-wrap:wrap;">
            <div style="flex:1; min-width:240px;">
                <label class="form-label">Select Faculty</label>
                <select name="faculty_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Select Faculty --</option>
                    <?php foreach ($faculties as $f): ?>
                        <option value="<?= h($f['id']) ?>" <?= $selected_faculty_id === $f['id'] ? 'selected' : '' ?>><?= h($f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($selected_faculty_id)): ?>
                <a href="?faculty_id=<?= urlencode($selected_faculty_id) ?>&export=excel" class="btn btn-success">
                    Export to Excel (.xlsx)
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($selected_faculty_id)): ?>
        <div class="es-card-header" style="border:none; padding:0; margin-bottom:14px;">
            <h3>Students under Faculty ID: <?= h($selected_faculty_id) ?></h3>
        </div>
        <div class="es-table-wrap">
            <table class="es-table">
                <thead>
                    <tr>
                        <th>#</th><th>Student ID</th><th>Name</th><th>Course</th><th>Score</th>
                        <th>Percentage</th><th>Grade</th><th>Rating</th><th>Status</th><th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($student_records)): ?>
                        <tr><td colspan="10" class="text-muted text-center" style="padding:30px;">No student records found for this faculty.</td></tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($student_records as $rec): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td class="mono"><?= h($rec['student_id']) ?></td>
                                <td><?= h(strtoupper($rec['student_name'])) ?></td>
                                <td><?= h(strtoupper($rec['course'])) ?></td>
                                <td><?= $rec['score'] ?> / <?= $rec['total_questions'] ?></td>
                                <td>
                                    <span class="chip <?= $rec['percentage'] >= 70 ? 'chip-good' : ($rec['percentage'] >= 50 ? 'chip-warn' : 'chip-bad') ?>">
                                        <?= $rec['percentage'] ?>%
                                    </span>
                                </td>
                                <td><?= h($rec['grade']) ?></td>
                                <td class="stars" style="font-size:0.9rem;">
                                    <?php for ($r = 1; $r <= 5; $r++): ?>
                                        <?= $r <= $rec['rating'] ? '★' : '<span class="off">☆</span>' ?>
                                    <?php endfor; ?>
                                </td>
                                <td><span class="chip <?= $rec['status'] === 'Completed' ? 'chip-good' : 'chip-bad' ?>"><?= h($rec['status']) ?></span></td>
                                <td><?= date('d M Y h:i A', strtotime($rec['date_time'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info"><span>Please select a faculty from the dropdown above to view student records.</span></div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
