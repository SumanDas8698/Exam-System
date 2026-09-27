<?php
$page_title = "Home";
require_once __DIR__ . '/includes/header.php';
?>

<div class="es-shell">
    <div class="page-head text-center" style="margin-top: 30px;">
        <div class="eyebrow">Online Examination Platform</div>
        <h1 style="font-size: 2.4rem;">Take exams. Track results. Get certified.</h1>
        <p style="max-width: 560px; margin: 0 auto;">
            Register as a student, book an exam topic, and once your faculty approves it,
            sit a timed multiple-choice exam — with results and a printable certificate
            issued instantly.
        </p>
    </div>

    <div class="tile-grid" style="margin-top: 40px;">
        <div class="action-tile">
            <div class="icon-box">1</div>
            <h3>Register as a Student</h3>
            <p>Create your account with your name, email and course. Your password is stored securely.</p>
            <a class="go-link" href="student/register.php">Get started &rarr;</a>
        </div>
        <div class="action-tile">
            <div class="icon-box">2</div>
            <h3>Request an Exam</h3>
            <p>Log in, choose a faculty/subject, and send a request. An admin reviews and approves it.</p>
            <a class="go-link" href="student/login.php">Log in &rarr;</a>
        </div>
        <div class="action-tile">
            <div class="icon-box">3</div>
            <h3>Sit the Exam &amp; Get Certified</h3>
            <p>Once approved, attempt your timed exam and download your result certificate immediately.</p>
            <a class="go-link" href="student/login.php">View my results &rarr;</a>
        </div>
    </div>

    <div class="divider"></div>

    <div class="text-center">
        <p class="text-muted">Are you a faculty administrator?</p>
        <a href="admin/login.php" class="btn btn-outline">Go to Admin Login</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
