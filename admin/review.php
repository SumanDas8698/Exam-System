<?php
require_once __DIR__ . '/../includes/config.php';
require_admin_login();

$faculties = $conn->query("SELECT id, name FROM faculty ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$students  = $conn->query("SELECT DISTINCT student_id, student_name FROM results ORDER BY student_id ASC")->fetch_all(MYSQLI_ASSOC);

$reviews = [];
$student_id = '';
$faculty_id = '';
$faculty_name = '';
$incorrect_count = 0;

// === HANDLE CLEAR RECORD ===
if (isset($_POST['clear_record'])) {
    $student_id_clear = trim($_POST['student_id_clear'] ?? '');
    $faculty_id_clear = $_POST['faculty_id_clear'] ?? '';

    if (!empty($student_id_clear) && !empty($faculty_id_clear)) {
        $find_stmt = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND exam_topic_id = ? LIMIT 1");
        $find_stmt->bind_param("ss", $student_id_clear, $faculty_id_clear);
        $find_stmt->execute();
        $find_res = $find_stmt->get_result();

        if ($find_res->num_rows > 0) {
            $row = $find_res->fetch_assoc();
            $result_id = $row['id'];

            // 1. Delete student_answers first (foreign key dependency)
            $del_ans = $conn->prepare("DELETE FROM student_answers WHERE result_id = ?");
            $del_ans->bind_param("s", $result_id);
            $del_ans->execute();
            $del_ans->close();

            // 2. Delete the result itself (this step was missing in the original code)
            $del_res = $conn->prepare("DELETE FROM results WHERE id = ?");
            $del_res->bind_param("s", $result_id);
            $del_res->execute();
            $del_res->close();

            set_flash('success', "Record successfully cleared for Student ID: " . h($student_id_clear) . " in Faculty ID: " . h($faculty_id_clear));
        } else {
            set_flash('error', "No record found to delete.");
        }
        $find_stmt->close();
    }
    redirect('review.php');
}

// === HANDLE SEARCH ===
if (isset($_POST['search'])) {
    $student_id = trim($_POST['student_id'] ?? '');
    $faculty_id = $_POST['faculty_id'] ?? '';

    if (!empty($student_id) && !empty($faculty_id)) {
        $f_stmt = $conn->prepare("SELECT name FROM faculty WHERE id = ?");
        $f_stmt->bind_param("s", $faculty_id);
        $f_stmt->execute();
        $f_res = $f_stmt->get_result();
        $faculty_name = $f_res->num_rows > 0 ? $f_res->fetch_assoc()['name'] : 'Unknown Faculty';
        $f_stmt->close();

        $result_stmt = $conn->prepare("
            SELECT id FROM results
            WHERE student_id = ? AND exam_topic_id = ?
            ORDER BY date_time DESC
            LIMIT 1
        ");
        $result_stmt->bind_param("ss", $student_id, $faculty_id);
        $result_stmt->execute();
        $result_res = $result_stmt->get_result();

        if ($result_res->num_rows > 0) {
            $result_row = $result_res->fetch_assoc();
            $result_id = $result_row['id'];

            $review_stmt = $conn->prepare("
                SELECT sa.question_id, sa.selected_option, sa.correct_option,
                       q.question_text, q.opt1, q.opt2, q.opt3, q.opt4
                FROM student_answers sa
                JOIN questions q ON sa.question_id = q.id
                WHERE sa.result_id = ?
                ORDER BY sa.id
            ");
            $review_stmt->bind_param("s", $result_id);
            $review_stmt->execute();
            $reviews = $review_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $review_stmt->close();

            foreach ($reviews as $review) {
                if ($review['selected_option'] !== $review['correct_option']) {
                    $incorrect_count++;
                }
            }
        }
        $result_stmt->close();
    } else {
        set_flash('error', "Please enter both Student ID and select a Faculty.");
    }
}

$page_title = "Review Exams";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell">
    <div class="page-head">
        <div class="eyebrow">Exam Review</div>
        <h1>Review &amp; Record Management</h1>
        <p>Inspect a student's question-by-question answers, and clear a record if needed.</p>
    </div>

    <?php require __DIR__ . '/../includes/flash.php'; ?>

    <div class="es-card" style="margin-bottom: 28px;">
        <form method="post">
            <div style="display:grid; grid-template-columns: 1fr 1fr auto; gap: 16px; align-items:end;">
                <div>
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= h($s['student_id']) ?>" <?= $student_id === $s['student_id'] ? 'selected' : '' ?>>
                                <?= h($s['student_id']) ?> — <?= h($s['student_name'] ?? 'Name not available') ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if (empty($students)): ?>
                            <option value="" disabled>No students found in results</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Faculty</label>
                    <select name="faculty_id" class="form-select" required>
                        <option value="">-- Select Faculty --</option>
                        <?php foreach ($faculties as $f): ?>
                            <option value="<?= h($f['id']) ?>" <?= $faculty_id === $f['id'] ? 'selected' : '' ?>><?= h($f['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="search" class="btn btn-primary">Search Review</button>
            </div>
        </form>
    </div>

    <?php if (!empty($reviews)): ?>
        <h3 class="text-center">
            Review for Student ID: <strong><?= h($student_id) ?></strong> — Faculty: <strong><?= h($faculty_name) ?></strong>
        </h3>

        <div class="text-center" style="margin: 16px 0 24px;">
            <span class="chip chip-bad" style="font-size:1rem; padding:6px 18px;">
                Total Incorrect: <?= $incorrect_count ?> / <?= count($reviews) ?>
            </span>
        </div>

        <div class="text-center" style="margin-bottom: 28px;">
            <form method="post" onsubmit="return esConfirm('Are you sure you want to DELETE this exam record? This cannot be undone!');">
                <input type="hidden" name="student_id_clear" value="<?= h($student_id) ?>">
                <input type="hidden" name="faculty_id_clear" value="<?= h($faculty_id) ?>">
                <button type="submit" name="clear_record" class="btn btn-danger btn-lg">Clear This Student's Record</button>
            </form>
        </div>

        <div class="es-table-wrap">
            <table class="es-table">
                <thead>
                    <tr>
                        <th width="40%">Question</th>
                        <th width="25%">Student's Answer</th>
                        <th width="25%">Correct Answer</th>
                        <th width="10%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $opt_map = ['A' => 'opt1', 'B' => 'opt2', 'C' => 'opt3', 'D' => 'opt4'];
                    foreach ($reviews as $review):
                        $student_opt = $review['selected_option'] ?: 'Unanswered';
                        $correct_opt = $review['correct_option'];
                        $is_correct = $student_opt === $correct_opt;
                        $chip = $student_opt === 'Unanswered' ? 'chip-warn' : ($is_correct ? 'chip-good' : 'chip-bad');
                        $status_text = $student_opt === 'Unanswered' ? 'Unanswered' : ($is_correct ? 'Correct' : 'Incorrect');
                    ?>
                        <tr>
                            <td><?= h($review['question_text']) ?></td>
                            <td>
                                <?php if ($student_opt !== 'Unanswered'): ?>
                                    <?= h($student_opt) ?>) <?= h($review[$opt_map[$student_opt]]) ?>
                                <?php else: ?>
                                    <span class="text-muted">Unanswered</span>
                                <?php endif; ?>
                            </td>
                            <td><?= h($correct_opt) ?>) <?= h($review[$opt_map[$correct_opt]]) ?></td>
                            <td><span class="chip <?= $chip ?>"><?= $status_text ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif (isset($_POST['search'])): ?>
        <div class="alert alert-warning text-center">
            <span>No review found for Student ID: <strong><?= h($student_id) ?></strong> in selected faculty.</span>
        </div>
    <?php endif; ?>
</div>

<script src="../assets/js/app.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
