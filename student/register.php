<?php
require_once __DIR__ . '/../includes/config.php';

if (isset($_SESSION['student_id'])) {
    redirect('dashboard.php');
}

$errors = [];
$old = ['student_name' => '', 'email' => '', 'course' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['student_name'] = trim($_POST['student_name'] ?? '');
    $old['email']        = trim($_POST['email'] ?? '');
    $old['course']       = trim($_POST['course'] ?? '');
    $password             = $_POST['password'] ?? '';
    $confirm              = $_POST['confirm_password'] ?? '';

    if ($old['student_name'] === '') $errors[] = "Full name is required.";
    if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if ($old['course'] === '') $errors[] = "Course is required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    // Check duplicate email
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $check->bind_param("s", $old['email']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "An account with this email already exists. Please log in instead.";
        }
        $check->close();
    }

    if (empty($errors)) {
        $new_id = next_id($conn, 'students', 'STU', 3); // STU001, STU002...
        $hash   = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO students (id, student_name, email, course, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $new_id, $old['student_name'], $old['email'], $old['course'], $hash);

        if ($stmt->execute()) {
            $stmt->close();
            set_flash('success', "Registration successful! Your Student ID is <strong>" . h($new_id) . "</strong>. Please log in.");
            redirect('login.php');
        } else {
            $errors[] = "Could not register. Please try again.";
        }
    }
}

$page_title = "Student Registration";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell-narrow">
    <div class="auth-card">
        <div class="logo-mark"><span>E</span></div>
        <h1>Create Student Account</h1>
        <p class="sub">Register once, then book and sit exams anytime.</p>

        <?php require __DIR__ . '/../includes/flash.php'; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <span><?php foreach ($errors as $e) echo h($e) . '<br>'; ?></span>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3" style="margin-bottom:16px;">
                <label class="form-label">Full Name</label>
                <input type="text" name="student_name" class="form-control" value="<?= h($old['student_name']) ?>" required>
            </div>
            <div class="mb-3" style="margin-bottom:16px;">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= h($old['email']) ?>" required>
            </div>
            <div class="mb-3" style="margin-bottom:16px;">
                <label class="form-label">Course</label>
                <input type="text" name="course" class="form-control" value="<?= h($old['course']) ?>" placeholder="e.g. BCA, B.Com, Diploma in CS" required>
            </div>
            <div class="mb-3" style="margin-bottom:16px;">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" minlength="6" required>
                <div class="form-text">Minimum 6 characters.</div>
            </div>
            <div class="mb-3" style="margin-bottom:20px;">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
        </form>

        <p class="text-center text-muted" style="margin-top:20px; font-size:0.88rem;">
            Already registered? <a href="login.php">Log in here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
