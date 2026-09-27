<?php
require_once __DIR__ . '/../includes/config.php';
require_student_login();

$student_id = $_SESSION['student_id'];
$errors = [];

// Faculties for dropdown
$faculties = $conn->query("SELECT id, name FROM faculty ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $faculty_id = $_POST['faculty_id'] ?? '';

    if (empty($faculty_id)) {
        $errors[] = "Please select a faculty/subject.";
    } else {
        // Lookup faculty name
        $f_stmt = $conn->prepare("SELECT name FROM faculty WHERE id = ?");
        $f_stmt->bind_param("s", $faculty_id);
        $f_stmt->execute();
        $f_res = $f_stmt->get_result();

        if ($f_res->num_rows === 0) {
            $errors[] = "Selected faculty not found.";
        } else {
            $faculty_name = $f_res->fetch_assoc()['name'];
            $f_stmt->close();

            // Prevent duplicate pending/approved request for same faculty
            $dup = $conn->prepare("
                SELECT id FROM exam_requests
                WHERE student_id = ? AND faculty_id = ? AND status IN ('Pending','Approved')
            ");
            $dup->bind_param("ss", $student_id, $faculty_id);
            $dup->execute();

            if ($dup->get_result()->num_rows > 0) {
                $errors[] = "You already have a pending or approved request for this faculty.";
            }
            $dup->close();

            // Already attempted today?
            $today = date('Y-m-d');
            $att = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND exam_topic_id = ? AND attempt_date = ?");
            $att->bind_param("sss", $student_id, $faculty_id, $today);
            $att->execute();
            if ($att->get_result()->num_rows > 0) {
                $errors[] = "You have already attempted this faculty's exam today.";
            }
            $att->close();

            if (empty($errors)) {
                $req_id = next_id($conn, 'exam_requests', 'REQ', 3);
                $ins = $conn->prepare("
                    INSERT INTO exam_requests (id, student_id, student_name, email, course, faculty_id, faculty_name, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
                ");
                $ins->bind_param(
                    "sssssss",
                    $req_id,
                    $student_id,
                    $_SESSION['student_name'],
                    $_SESSION['student_email'],
                    $_SESSION['student_course'],
                    $faculty_id,
                    $faculty_name
                );
                if ($ins->execute()) {
                    set_flash('success', "Exam request submitted! Waiting for admin approval.");
                    redirect('my_requests.php');
                } else {
                    $errors[] = "Could not submit request. Please try again.";
                }
                $ins->close();
            }
        }
    }
}

$page_title = "Book an Exam";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell">
    <div class="page-head">
        <div class="eyebrow">Exam Booking</div>
        <h1>Request an Exam</h1>
        <p>Select a faculty/subject below. Your request goes to the admin for approval before you can attempt the exam.</p>
    </div>

    <div class="es-card" style="max-width: 560px;">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <span><?php foreach ($errors as $e) echo h($e) . '<br>'; ?></span>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3" style="margin-bottom:16px;">
                <label class="form-label">Student ID</label>
                <input type="text" class="form-control" value="<?= h($student_id) ?>" disabled>
            </div>
            <div class="mb-3" style="margin-bottom:16px;">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" value="<?= h($_SESSION['student_name']) ?>" disabled>
            </div>
            <div class="mb-3" style="margin-bottom:16px;">
                <label class="form-label">Email</label>
                <input type="text" class="form-control" value="<?= h($_SESSION['student_email']) ?>" disabled>
            </div>
            <div class="mb-3" style="margin-bottom:16px;">
                <label class="form-label">Course</label>
                <input type="text" class="form-control" value="<?= h($_SESSION['student_course']) ?>" disabled>
            </div>
            <div class="mb-3" style="margin-bottom:20px;">
                <label class="form-label">Select Faculty / Subject</label>
                <select name="faculty_id" class="form-select" required>
                    <option value="">-- Choose Faculty --</option>
                    <?php foreach ($faculties as $f): ?>
                        <option value="<?= h($f['id']) ?>"><?= h($f['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Submit Request</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
