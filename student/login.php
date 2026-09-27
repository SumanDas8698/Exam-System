<?php
require_once __DIR__ . '/../includes/config.php';

if (isset($_SESSION['student_id'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Please enter both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, student_name, email, course, password, status FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $student = $result->fetch_assoc();

            if ($student['status'] === 'Blocked') {
                $error = "Your account has been blocked. Please contact the administrator.";
            } elseif (password_verify($password, $student['password'])) {
                $_SESSION['student_id']      = $student['id'];
                $_SESSION['student_name']    = $student['student_name'];
                $_SESSION['student_email']   = $student['email'];
                $_SESSION['student_course']  = $student['course'];
                $stmt->close();
                redirect('dashboard.php');
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    }
}

$page_title = "Student Login";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell-narrow">
    <div class="auth-card">
        <div class="logo-mark"><span>E</span></div>
        <h1>Student Login</h1>
        <p class="sub">Welcome back. Log in to book or review your exams.</p>

        <?php require __DIR__ . '/../includes/flash.php'; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><span><?= h($error) ?></span></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3" style="margin-bottom:16px;">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>
            <div class="mb-3" style="margin-bottom:8px;">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="text-end" style="text-align:right; margin-bottom:20px;">
                <a href="forgot_password.php" style="font-size:0.85rem;">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Log In</button>
        </form>

        <p class="text-center text-muted" style="margin-top:20px; font-size:0.88rem;">
            New here? <a href="register.php">Create an account</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
