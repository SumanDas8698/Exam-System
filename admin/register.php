<?php
require_once __DIR__ . '/../includes/config.php';

if (isset($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}

// Simple shared invite-code gate so random visitors can't self-register as admin.
// Change this value to your own secret before deploying, or remove the check entirely
// once you have trusted admins and want to lock registration down further.
define('ADMIN_INVITE_CODE', 'EXAM-ADMIN-2026');

$errors = [];
$old = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['username'] = trim($_POST['username'] ?? '');
    $old['email']    = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm          = $_POST['confirm_password'] ?? '';
    $invite_code      = trim($_POST['invite_code'] ?? '');

    if ($old['username'] === '') $errors[] = "Username is required.";
    if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";
    if ($invite_code !== ADMIN_INVITE_CODE) $errors[] = "Invalid invite code. Contact the system owner for the correct code.";

    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
        $check->bind_param("ss", $old['username'], $old['email']);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = "An admin with this username or email already exists.";
        }
        $check->close();
    }

    if (empty($errors)) {
        $new_id = next_id($conn, 'admins', 'A', 2);
        $hash   = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO admins (id, username, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $new_id, $old['username'], $old['email'], $hash);

        if ($stmt->execute()) {
            $stmt->close();
            set_flash('success', "Admin account created successfully. Please log in.");
            redirect('login.php');
        } else {
            $errors[] = "Could not register. Please try again.";
        }
    }
}

$page_title = "Admin Registration";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="es-shell-narrow">
    <div class="auth-card">
        <div class="logo-mark"><span>E</span></div>
        <h1>Admin Registration</h1>
        <p class="sub">Create a faculty administrator account.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <span><?php foreach ($errors as $e) echo h($e) . '<br>'; ?></span>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3" style="margin-bottom:16px;">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?= h($old['username']) ?>" required>
            </div>
            <div class="mb-3" style="margin-bottom:16px;">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= h($old['email']) ?>" required>
            </div>
            <div class="mb-3" style="margin-bottom:16px;">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" minlength="8" required>
                <div class="form-text">Minimum 8 characters.</div>
            </div>
            <div class="mb-3" style="margin-bottom:16px;">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" minlength="8" required>
            </div>
            <div class="mb-3" style="margin-bottom:20px;">
                <label class="form-label">Invite Code</label>
                <input type="text" name="invite_code" class="form-control" required>
                <div class="form-text">Provided by your system owner. Prevents open admin sign-up.</div>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Create Admin Account</button>
        </form>

        <p class="text-center text-muted" style="margin-top:20px; font-size:0.88rem;">
            Already have an account? <a href="login.php">Log in here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
