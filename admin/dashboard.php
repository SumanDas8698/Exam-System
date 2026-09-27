<?php
require_once __DIR__ . '/../includes/config.php';
require_admin_login();

$total_students = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
$pending_requests = $conn->query("SELECT COUNT(*) AS c FROM exam_requests WHERE status = 'Pending'")->fetch_assoc()['c'];
$total_exams_taken = $conn->query("SELECT COUNT(*) AS c FROM results")->fetch_assoc()['c'];
$total_questions = $conn->query("SELECT COUNT(*) AS c FROM questions")->fetch_assoc()['c'];

$page_title = "Admin Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell">
    <div class="page-head">
        <div class="eyebrow">Admin Panel</div>
        <h1>Welcome, <?= h($_SESSION['admin_username']) ?></h1>
        <p>Manage students, exam requests, questions, and reports from here.</p>
    </div>

    <?php require __DIR__ . '/../includes/flash.php'; ?>

    <div class="stat-grid">
        <div class="stat-tile">
            <div class="num"><?= (int)$total_students ?></div>
            <div class="label">Registered Students</div>
        </div>
        <div class="stat-tile accent">
            <div class="num"><?= (int)$pending_requests ?></div>
            <div class="label">Pending Approvals</div>
        </div>
        <div class="stat-tile">
            <div class="num"><?= (int)$total_exams_taken ?></div>
            <div class="label">Exams Taken</div>
        </div>
        <div class="stat-tile">
            <div class="num"><?= (int)$total_questions ?></div>
            <div class="label">Questions in Bank</div>
        </div>
    </div>

    <div class="tile-grid">
        <div class="action-tile">
            <div class="icon-box">&#9989;</div>
            <h3>Exam Requests</h3>
            <p>Approve or reject student booking requests for faculty exams.</p>
            <a class="go-link" href="exam_requests.php">Review requests &rarr;</a>
        </div>
        <div class="action-tile">
            <div class="icon-box">&#128101;</div>
            <h3>Manage Students</h3>
            <p>View all registered students, their course, and account status.</p>
            <a class="go-link" href="manage_students.php">View students &rarr;</a>
        </div>
        <div class="action-tile">
            <div class="icon-box">&#128218;</div>
            <h3>Manage Topics</h3>
            <p>Add, edit, or remove faculties/subjects available for exams.</p>
            <a class="go-link" href="manage_topics.php">Manage topics &rarr;</a>
        </div>
        <div class="action-tile">
            <div class="icon-box">&#10067;</div>
            <h3>Manage Questions</h3>
            <p>Add, edit, or delete individual exam questions.</p>
            <a class="go-link" href="manage_questions.php">Manage questions &rarr;</a>
        </div>
        <div class="action-tile">
            <div class="icon-box">&#11014;</div>
            <h3>Bulk Upload Questions</h3>
            <p>Import question sets in bulk from an Excel (.xlsx) file.</p>
            <a class="go-link" href="bulk_upload.php">Upload questions &rarr;</a>
        </div>
        <div class="action-tile">
            <div class="icon-box">&#128270;</div>
            <h3>Review Exams</h3>
            <p>Inspect a student's answers question-by-question, and clear records.</p>
            <a class="go-link" href="review.php">Review answers &rarr;</a>
        </div>
        <div class="action-tile">
            <div class="icon-box">&#128203;</div>
            <h3>Exam History</h3>
            <p>Look up any student's full exam history by Student ID.</p>
            <a class="go-link" href="exam_history.php">Search history &rarr;</a>
        </div>
        <div class="action-tile">
            <div class="icon-box">&#128202;</div>
            <h3>Faculty Report &amp; Export</h3>
            <p>Per-faculty performance report, exportable to Excel.</p>
            <a class="go-link" href="faculty_report.php">Open report &rarr;</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
