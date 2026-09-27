<?php
require_once __DIR__ . '/../includes/config.php';

$id = trim($_GET['id'] ?? '');
if ($id === '') {
    die("<div class='text-center mt-5'><h2>Invalid or missing Result ID</h2></div>");
}

// Allow access if: logged-in student owns this result, OR an admin is logged in.
$is_admin = isset($_SESSION['admin_id']);
$is_owner_student = isset($_SESSION['student_id']);

$stmt = $conn->prepare("SELECT * FROM results WHERE id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<div class='text-center mt-5'><h2>Result Not Found</h2></div>");
}

$row = $result->fetch_assoc();
$stmt->close();

if (!$is_admin && (!$is_owner_student || $_SESSION['student_id'] !== $row['student_id'])) {
    die("<div class='text-center mt-5'><h2>Access denied.</h2></div>");
}

$percent = $row['total_questions'] > 0 ? round(($row['score'] / $row['total_questions']) * 100, 2) : 0;
$is_pass = $percent >= 50;
$malpractice = $row['status'] === 'Malpractice';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificate - <?= h($row['student_name']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
    body { background: #EFEAE0; padding: 30px 0; }
    .certificate-container { max-width: 880px; margin: 0 auto; padding: 0 20px; }
    .certificate {
        background: #fff;
        border: 10px double var(--amber);
        border-radius: 8px;
        padding: 60px 56px;
        text-align: center;
        box-shadow: var(--shadow-lg);
        position: relative;
    }
    .cert-mark {
        width: 64px; height: 64px; margin: 0 auto 18px;
        background: var(--navy-deep); border-radius: 50%;
        display:flex; align-items:center; justify-content:center;
    }
    .cert-mark span { color: var(--amber); font-family: var(--font-display); font-size: 1.6rem; font-weight: 700; }
    .cert-title { font-family: var(--font-display); font-size: 2.4rem; color: var(--navy-deep); margin-bottom: 6px; }
    .cert-msg { color: var(--ink-soft); font-size: 1.05rem; margin: 16px 0; }
    .cert-name { font-family: var(--font-display); font-size: 2rem; font-weight: 700; color: var(--ink); margin: 18px 0; }
    .cert-topic { font-size: 1.3rem; color: var(--amber); font-weight: 700; margin: 18px 0; }
    .cert-details { font-size: 1.05rem; margin: 28px 0; line-height: 1.9; }
    .cert-footer { margin-top: 50px; color: var(--ink-soft); font-size: 0.92rem; }
    @media print {
        body { background: #fff !important; padding: 0 !important; }
        .certificate { box-shadow: none !important; }
        .no-print { display: none !important; }
    }
</style>
</head>
<body>

<div class="certificate-container">
    <div class="certificate">
        <div class="cert-mark"><span>E</span></div>

        <h1 class="cert-title"><?= $is_pass ? 'Certificate of Achievement' : 'Certificate of Participation' ?></h1>
        <p class="cert-msg">This is to certify that</p>
        <div class="cert-name"><?= h($row['student_name']) ?></div>
        <p class="cert-msg">
            has <?= $is_pass ? 'successfully completed' : 'participated in' ?> the examination in
        </p>
        <div class="cert-topic">Exam Topic: <?= h($row['course'] ?? '—') ?></div>

        <div class="cert-details">
            <strong>Score:</strong> <?= $row['score'] ?> / <?= $row['total_questions'] ?><br>
            <strong>Percentage:</strong> <?= $percent ?>%<br>
            <strong>Grade:</strong> <?= h($row['grade']) ?><br>
            <strong>Faculty:</strong> <?= h($row['faculty_name'] ?? '—') ?><br>
            <strong>Rating:</strong>
            <span class="stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <?= $i <= $row['rating'] ? '★' : '<span class="off">☆</span>' ?>
                <?php endfor; ?>
            </span>
            (<?= $row['rating'] ?>/5)
        </div>

        <?php if (!$is_pass && !$malpractice): ?>
            <p style="color: var(--bad); font-size: 1.1rem;">Keep practicing — better luck next time!</p>
        <?php endif; ?>

        <div class="cert-footer">
            Issued on: <?= date('d F Y', strtotime($row['date_time'])) ?><br>
            <?= APP_NAME ?>
        </div>
    </div>

    <div class="text-center no-print" style="margin-top: 24px;">
        <button onclick="window.print()" class="btn btn-primary btn-lg">Print Certificate</button>
        <a href="<?= $is_admin ? '../admin/dashboard.php' : 'dashboard.php' ?>" class="btn btn-outline btn-lg">Back</a>
    </div>
</div>

</body>
</html>
