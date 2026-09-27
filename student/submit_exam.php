<?php
require_once __DIR__ . '/../includes/config.php';
require_student_login();

if (!isset($_SESSION['exam_topic_id']) || !isset($_SESSION['exam_request_id'])) {
    redirect('my_requests.php');
}

$student_id   = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'];
$course       = $_SESSION['student_course'];
$email        = $_SESSION['student_email'];
$topic_id     = $_SESSION['exam_topic_id'];
$faculty_name = $_SESSION['exam_faculty'];
$request_id   = $_SESSION['exam_request_id'];

$malpractice = isset($_GET['malpractice']) || !empty($_SESSION['malpractice']);
$status = $malpractice ? 'Malpractice' : 'Completed';

$score   = 0;
$total   = 0;
$percent = 0;
$grade   = 'Fail';
$rating  = 1;
$answers = [];
$qids    = [];

if (!$malpractice && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['ans'] ?? [];
    $qids    = $_POST['qid'] ?? [];
    $total   = count($qids);

    foreach ($qids as $qid) {
        $stmt_q = $conn->prepare("SELECT correct_opt FROM questions WHERE id = ?");
        $stmt_q->bind_param("s", $qid);
        $stmt_q->execute();
        $res_q = $stmt_q->get_result();
        if ($row_q = $res_q->fetch_assoc()) {
            if (isset($answers[$qid]) && $answers[$qid] === $row_q['correct_opt']) {
                $score++;
            }
        }
        $stmt_q->close();
    }

    $percent = $total > 0 ? round(($score / $total) * 100, 2) : 0;

    if ($percent >= 90)      { $grade = 'A';    $rating = 5; }
    elseif ($percent >= 80)  { $grade = 'B';    $rating = 4; }
    elseif ($percent >= 70)  { $grade = 'C';    $rating = 3; }
    elseif ($percent >= 60)  { $grade = 'D';    $rating = 2; }
    elseif ($percent >= 50)  { $grade = 'Poor'; $rating = 2; }
    else                     { $grade = 'Fail'; $rating = 1; }
}

if ($malpractice) {
    $grade   = 'Malpractice - Disqualified';
    $percent = 0;
    $score   = 0;
    $rating  = 0;
}

// === Generate next result ID (R01, R02...) ===
$result_id    = next_id($conn, 'results', 'R', 2);
$date_time    = date('Y-m-d H:i:s');
$attempt_date = date('Y-m-d');

// === INSERT result row (15 columns / 15 bound params) ===
$insert_sql = "
    INSERT INTO results
    (id, request_id, student_id, student_name, course, email, exam_topic_id, faculty_name,
     score, total_questions, date_time, status, grade, rating, attempt_date)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";
$stmt = $conn->prepare($insert_sql);
if (!$stmt) {
    die("Prepare failed: " . h($conn->error));
}
$stmt->bind_param(
    "ssssssssiisssis",
    $result_id,        // id            s
    $request_id,       // request_id    s
    $student_id,        // student_id    s
    $student_name,      // student_name  s
    $course,            // course        s
    $email,             // email         s
    $topic_id,          // exam_topic_id s
    $faculty_name,      // faculty_name  s
    $score,             // score         i
    $total,             // total_questions i
    $date_time,         // date_time     s
    $status,            // status        s
    $grade,             // grade         s
    $rating,            // rating        i
    $attempt_date       // attempt_date  s
);

if (!$stmt->execute()) {
    die("Insert failed: " . h($stmt->error));
}
$stmt->close();

// === Mark exam_request as Used ===
$upd = $conn->prepare("UPDATE exam_requests SET status = 'Used' WHERE id = ?");
$upd->bind_param("s", $request_id);
$upd->execute();
$upd->close();

// === SAVE INDIVIDUAL ANSWERS (only if not malpractice) ===
// Fixes the original bug: duplicate entry on (result_id, question_id) unique key.
// Since $result_id is freshly generated above, duplicates cannot occur per result,
// but we still guard with INSERT IGNORE in case of accidental double-submit/retry.
if (!$malpractice && !empty($qids)) {
    $ans_sql = "
        INSERT IGNORE INTO student_answers
        (id, result_id, question_id, selected_option, correct_option)
        VALUES (?, ?, ?, ?, ?)
    ";
    $stmt_ans = $conn->prepare($ans_sql);

    // Determine starting answer number
    $max_stmt = $conn->prepare("
        SELECT COALESCE(MAX(CAST(SUBSTRING(id, 3) AS UNSIGNED)), 0) AS max_num
        FROM student_answers WHERE id REGEXP '^RV[0-9]+$'
    ");
    $max_stmt->execute();
    $max_row = $max_stmt->get_result()->fetch_assoc();
    $next_ans_number = (int)$max_row['max_num'] + 1;
    $max_stmt->close();

    foreach ($qids as $qid_raw) {
        $qid = trim($qid_raw);
        if ($qid === '') continue;

        $answer_id = 'RV' . str_pad((string)$next_ans_number, 3, '0', STR_PAD_LEFT);
        $selected  = $answers[$qid] ?? null;
        $correct   = '';

        $stmt_q = $conn->prepare("SELECT correct_opt FROM questions WHERE id = ?");
        $stmt_q->bind_param("s", $qid);
        $stmt_q->execute();
        if ($row_q = $stmt_q->get_result()->fetch_assoc()) {
            $correct = $row_q['correct_opt'];
        }
        $stmt_q->close();

        $stmt_ans->bind_param("sssss", $answer_id, $result_id, $qid, $selected, $correct);
        if ($stmt_ans->execute()) {
            $next_ans_number++;
        }
    }
    $stmt_ans->close();
}

// Clean exam-related session keys (keep login intact)
unset($_SESSION['exam_topic_id'], $_SESSION['exam_faculty'], $_SESSION['exam_request_id'], $_SESSION['malpractice']);

redirect('exam_result.php?id=' . urlencode($result_id));
