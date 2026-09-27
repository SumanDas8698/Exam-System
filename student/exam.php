<?php
require_once __DIR__ . '/../includes/config.php';
require_student_login();

if (!isset($_SESSION['exam_topic_id']) || !isset($_SESSION['exam_request_id'])) {
    set_flash('error', "No active exam session. Please start from your approved requests.");
    redirect('my_requests.php');
}

$topic_id   = $_SESSION['exam_topic_id'];
$faculty_name = $_SESSION['exam_faculty'];

// Fetch randomized questions for this faculty
$stmt = $conn->prepare("SELECT * FROM questions WHERE f_id = ? ORDER BY RAND()");
$stmt->bind_param("s", $topic_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows < 1) {
    set_flash('error', "No questions available for this faculty.");
    redirect('my_requests.php');
}

$questions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function display_option($opt) {
    $opt = trim($opt);
    if ($opt === '1') return 'True';
    if ($opt === '0') return 'False';
    return h($opt);
}

$page_title = "Exam in Progress";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($page_title) ?> · <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
    body { user-select: none; }
    .exam-top-bar {
        background: var(--navy-deep); color: #fff; padding: 16px 0;
        position: fixed; top: 0; width: 100%; z-index: 1030; box-shadow: var(--shadow);
    }
    .exam-top-bar .row3 { display:flex; align-items:center; justify-content:space-between; max-width:1100px; margin:0 auto; padding:0 20px; flex-wrap: wrap; gap: 8px;}
    .timer { font-family: var(--font-mono); font-size: 1.3rem; font-weight: 700; color: var(--amber); }
    .exam-wrap { max-width: 880px; margin: 110px auto 60px; padding: 0 20px; }
    .note-banner {
        background: var(--warn-bg); border: 1px solid #efddb2; color: #8a611f;
        padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 26px; font-weight: 500; font-size: 0.9rem;
    }
    .q-box { background: var(--paper-raised); border: 1px solid var(--rule); border-radius: var(--radius); box-shadow: var(--shadow); padding: 22px 24px; margin-bottom: 20px; }
    .q-badge { background: var(--navy-deep); color: var(--amber); font-family: var(--font-mono); font-size: 0.78rem; padding: 3px 10px; border-radius: 4px; margin-right: 10px; }
    .opt-row { display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius: var(--radius-sm); cursor:pointer; }
    .opt-row:hover { background: rgba(31,58,95,0.04); }
    .opt-row input[type=radio] { accent-color: var(--navy-deep); width:16px; height:16px; }
</style>
</head>
<body oncontextmenu="return false;" onselectstart="return false;">

<div class="exam-top-bar">
    <div class="row3">
        <div><strong><?= h($_SESSION['student_name']) ?></strong> &middot; <?= h($_SESSION['student_id']) ?></div>
        <div>Faculty: <strong><?= h($faculty_name) ?></strong></div>
        <div class="timer" id="timer">30:00</div>
    </div>
</div>

<div class="exam-wrap">
    <div class="note-banner">
        ⚠ Important: this exam can be attempted <strong>only once per day per faculty</strong>.
        Unanswered questions are marked incorrect. Do not switch tabs — this is treated as malpractice.
    </div>

    <h2 class="text-center" style="margin-bottom: 30px;">Exam: <?= h($_SESSION['student_course']) ?></h2>

    <form id="examForm" action="submit_exam.php" method="post">
        <?php foreach ($questions as $index => $q): ?>
            <div class="q-box">
                <p style="font-weight:600; margin-bottom:14px;">
                    <span class="q-badge">Q<?= $index + 1 ?></span>
                    <?= h($q['question_text']) ?>
                </p>
                <?php foreach (['A'=>'opt1','B'=>'opt2','C'=>'opt3','D'=>'opt4'] as $letter => $col): ?>
                    <label class="opt-row">
                        <input type="radio" name="ans[<?= h($q['id']) ?>]" value="<?= $letter ?>">
                        <span><?= $letter ?>) <?= display_option($q[$col]) ?></span>
                    </label>
                <?php endforeach; ?>
                <input type="hidden" name="qid[]" value="<?= h($q['id']) ?>">
            </div>
        <?php endforeach; ?>

        <div class="text-center" style="margin: 36px 0 60px;">
            <button type="submit" class="btn btn-success btn-lg">Submit Exam</button>
        </div>
    </form>
</div>

<script src="../assets/js/app.js"></script>
<script>
let timeLeft = <?= EXAM_MINUTES * 60 ?>;
const timerDisplay = document.getElementById('timer');
const form = document.getElementById('examForm');
let malpracticeDetected = false;
let isSubmitting = false;

function updateTimer() {
    if (timeLeft <= 0) {
        if (!isSubmitting) { isSubmitting = true; form.submit(); }
        return;
    }
    const m = Math.floor(timeLeft / 60);
    const s = timeLeft % 60;
    timerDisplay.textContent = `${m}:${s < 10 ? '0' : ''}${s}`;
    timeLeft--;
}
const timerInterval = setInterval(updateTimer, 1000);

document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === 'hidden' && !malpracticeDetected) {
        malpracticeDetected = true;
        clearInterval(timerInterval);
        alert("Malpractice detected: switching tabs is not allowed!\nYour exam will be cancelled.");
        window.location.href = "submit_exam.php?malpractice=1";
    }
});

document.addEventListener('keydown', (e) => {
    if (
        e.key === "F5" || (e.ctrlKey && e.key === "r") || (e.ctrlKey && e.key === "F5") ||
        e.key === "F12" || (e.ctrlKey && e.shiftKey && e.key === "I") ||
        (e.ctrlKey && e.key === "u") || (e.ctrlKey && e.key === "s")
    ) {
        e.preventDefault();
        alert("This action is disabled during the exam!");
        return false;
    }
});

updateTimer();
</script>
</body>
</html>
