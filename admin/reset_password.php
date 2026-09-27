<?php
require_once __DIR__ . '/../includes/config.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$valid = false;
$reset_row = null;

if ($token === '') {
    $error = "Missing or invalid reset link.";
} else {
    $stmt = $conn->prepare("
        SELECT * FROM password_resets
        WHERE token = ? AND user_type = 'admin' AND used = 0 AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $reset_row = $result->fetch_assoc();
        $valid = true;
    } else {
        $error = "This reset link is invalid or has expired. Please request a new one.";
    }
    $stmt->close();
}

if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $upd = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $upd->bind_param("ss", $hash, $reset_row['user_id']);
        $upd->execute();
        $upd->close();

        $mark = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
        $mark->bind_param("i", $reset_row['id']);
        $mark->execute();
        $mark->close();

        set_flash('success', "Your password has been reset successfully. Please log in.");
        redirect('login.php');
    }
}

$page_title = "Reset Password";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell-narrow">
    <div class="auth-card">
        <div class="logo-mark"><span>E</span></div>
        <h1>Reset Password</h1>
        <p class="sub">Choose a new password for your admin account.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><span><?= h($error) ?></span></div>
        <?php endif; ?>

        <?php if ($valid): ?>
            <form method="post" novalidate>
                <input type="hidden" name="token" value="<?= h($token) ?>">
                <div class="mb-3" style="margin-bottom:16px;">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" minlength="8" required autofocus>
                </div>
                <div class="mb-3" style="margin-bottom:20px;">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Update Password</button>
            </form>
        <?php else: ?>
            <p class="text-center"><a href="forgot_password.php" class="btn btn-outline">Request a new link</a></p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
