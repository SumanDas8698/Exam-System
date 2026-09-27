<?php
/**
 * setup_admin.php — ONE-TIME bootstrap script.
 *
 * Run this once in your browser after importing database.sql, to create
 * your first admin account. password_hash() is run on YOUR server, so the
 * hash is guaranteed to verify correctly with YOUR PHP version.
 *
 * DELETE THIS FILE after you've created your admin account.
 */
require_once __DIR__ . '/includes/config.php';

// Safety: refuse to run if an admin already exists, unless explicitly forced.
$existing = $conn->query("SELECT COUNT(*) AS c FROM admins")->fetch_assoc()['c'];
$force = isset($_GET['force']);

$errors = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '') $errors[] = "Username is required.";
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";

    if (empty($errors) && ($existing == 0 || $force)) {
        $new_id = next_id($conn, 'admins', 'A', 2);
        $hash   = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO admins (id, username, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $new_id, $username, $email, $hash);
        if ($stmt->execute()) {
            $done = true;
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Setup</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="es-shell-narrow">
    <div class="auth-card">
        <h1>One-Time Admin Setup</h1>

        <?php if ($existing > 0 && !$force): ?>
            <div class="alert alert-warning">
                <span>An admin account already exists. If you really want to add another, visit this page with <code>?force=1</code>.</span>
            </div>
            <p class="text-center"><a href="admin/login.php" class="btn btn-primary">Go to Admin Login</a></p>
        <?php elseif ($done): ?>
            <div class="alert alert-success">
                <span>Admin account created! You can now log in. <strong>Delete this file (setup_admin.php) now for security.</strong></span>
            </div>
            <p class="text-center"><a href="admin/login.php" class="btn btn-primary">Go to Admin Login</a></p>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><span><?php foreach ($errors as $e) echo h($e) . '<br>'; ?></span></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3" style="margin-bottom:16px;">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3" style="margin-bottom:16px;">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3" style="margin-bottom:20px;">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Create Admin Account</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
