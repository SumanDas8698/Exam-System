<?php
require_once __DIR__ . '/../includes/config.php';

$error = '';
$reset_link = null; // shown on-screen since no mail server is configured

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin   = $result->fetch_assoc();
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            $ins = $conn->prepare("INSERT INTO password_resets (user_type, user_id, email, token, expires_at) VALUES ('admin', ?, ?, ?, ?)");
            $ins->bind_param("ssss", $admin['id'], $email, $token, $expires);
            $ins->execute();
            $ins->close();

            $reset_link = "reset_password.php?token=" . $token;
        }
        $stmt->close();
    }
}

$page_title = "Forgot Password";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell-narrow">
    <div class="auth-card">
        <div class="logo-mark"><span>E</span></div>
        <h1>Forgot Password</h1>
        <p class="sub">Enter your registered admin email to reset your password.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><span><?= h($error) ?></span></div>
        <?php endif; ?>

        <?php if ($reset_link !== null): ?>
            <div class="alert alert-success">
                <span>If that email is registered, a reset link has been generated.</span>
            </div>
            <div class="alert alert-info">
                <span>
                    <strong>Demo mode:</strong> no mail server is configured, so here is your reset link directly.
                    In production, this would be emailed instead.<br><br>
                    <a href="<?= h($reset_link) ?>"><?= h($reset_link) ?></a>
                </span>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error): ?>
            <div class="alert alert-success">
                <span>If that email is registered, a reset link has been generated.</span>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3" style="margin-bottom:20px;">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Send Reset Link</button>
        </form>

        <p class="text-center text-muted" style="margin-top:20px; font-size:0.88rem;">
            <a href="login.php">&larr; Back to login</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
