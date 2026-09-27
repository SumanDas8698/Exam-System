<?php
require_once __DIR__ . '/../includes/config.php';
require_student_login();

$student_id = $_SESSION['student_id'];

// Stats
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM results WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$total_exams = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM exam_requests WHERE student_id = ? AND status = 'Pending'");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$pending_requests = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM exam_requests WHERE student_id = ? AND status = 'Approved'");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$ready_to_attempt = $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$stmt = $conn->prepare("SELECT AVG(ROUND(score/total_questions*100,2)) AS avgp FROM results WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$avg_percent = $stmt->get_result()->fetch_assoc()['avgp'];
$stmt->close();

$page_title = "Student Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell">
    <div class="page-head">
        <div class="eyebrow">Student Panel</div>
        <h1>Welcome, <?= h($_SESSION['student_name']) ?></h1>
        <p>Student ID: <strong><?= h($student_id) ?></strong> &middot; Course: <?= h($_SESSION['student_course']) ?></p>
    </div>

    <?php require __DIR__ . '/../includes/flash.php'; ?>

    <div class="stat-grid">
        <div class="stat-tile">
            <div class="num"><?= (int)$total_exams ?></div>
            <div class="label">Exams Completed</div>
        </div>
        <div class="stat-tile accent">
            <div class="num"><?= (int)$pending_requests ?></div>
            <div class="label">Pending Requests</div>
        </div>
        <div class="stat-tile">
            <div class="num"><?= (int)$ready_to_attempt ?></div>
            <div class="label">Approved &ndash; Ready</div>
        </div>
        <div class="stat-tile">
            <div class="num"><?= $avg_percent !== null ? round($avg_percent, 1) . '%' : '—' ?></div>
            <div class="label">Average Score</div>
        </div>
    </div>

    <div class="tile-grid">
        <div class="action-tile">
            <div class="icon-box">&#128214;</div>
            <h3>Book an Exam</h3>
            <p>Choose a faculty/subject and request approval to sit the exam.</p>
            <a class="go-link" href="book_exam.php">Book now &rarr;</a>
        </div>
        <div class="action-tile">
            <div class="icon-box">&#9989;</div>
            <h3>My Exam Requests</h3>
            <p>Track the approval status of your booking requests.</p>
            <a class="go-link" href="my_requests.php">View requests &rarr;</a>
        </div>
        <div class="action-tile">
            <div class="icon-box">&#127942;</div>
            <h3>My Results</h3>
            <p>View past results, grades, ratings, and download certificates.</p>
            <a class="go-link" href="my_results.php">View results &rarr;</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
