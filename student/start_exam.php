<?php
require_once __DIR__ . '/../includes/config.php';
require_student_login();

$student_id = $_SESSION['student_id'];
$req_id     = $_GET['req'] ?? '';

if (empty($req_id)) {
    set_flash('error', "Invalid exam request.");
    redirect('my_requests.php');
}

$stmt = $conn->prepare("SELECT * FROM exam_requests WHERE id = ? AND student_id = ?");
$stmt->bind_param("ss", $req_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    set_flash('error', "Exam request not found.");
    redirect('my_requests.php');
}

$request = $result->fetch_assoc();
$stmt->close();

if ($request['status'] !== 'Approved') {
    set_flash('error', "This exam request is not approved yet.");
    redirect('my_requests.php');
}

// Already attempted today for this faculty?
$today = date('Y-m-d');
$att = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND exam_topic_id = ? AND attempt_date = ?");
$att->bind_param("sss", $student_id, $request['faculty_id'], $today);
$att->execute();
if ($att->get_result()->num_rows > 0) {
    set_flash('error', "You have already attempted this faculty's exam today.");
    redirect('my_requests.php');
}
$att->close();

// Enough questions available?
$qcount_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM questions WHERE f_id = ?");
$qcount_stmt->bind_param("s", $request['faculty_id']);
$qcount_stmt->execute();
$qcount = $qcount_stmt->get_result()->fetch_assoc()['c'];
$qcount_stmt->close();

if ($qcount < 1) {
    set_flash('error', "No questions are currently available for this faculty. Please contact the admin.");
    redirect('my_requests.php');
}

// All good — push into exam session and go
$_SESSION['exam_request_id'] = $request['id'];
$_SESSION['exam_topic_id']   = $request['faculty_id'];
$_SESSION['exam_faculty']    = $request['faculty_name'];
$_SESSION['malpractice']     = false;

redirect('exam.php');
