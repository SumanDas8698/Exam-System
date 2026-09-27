<?php
require_once __DIR__ . '/../includes/config.php';

if (isset($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id']       = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $stmt->close();
                redirect('dashboard.php');
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    }
}

$page_title = "Admin Login";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell-narrow">
    <div class="auth-card">
        <div class="logo-mark"><span>E</span></div>
        <h1>Admin Login</h1>
        <p class="sub">Manage exams, students, and approvals.</p>

        <?php require __DIR__ . '/../includes/flash.php'; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><span><?= h($error) ?></span></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3" style="margin-bottom:16px;">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required autofocus>
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
            No admin account yet? <a href="register.php">Register here</a>
        </p>
        <p class="text-center" style="margin-top:8px;">
            <a href="../index.php" style="font-size:0.85rem;">&larr; Back to Home</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
