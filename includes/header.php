<?php
/**
 * header.php — shared site header.
 * Expects $page_title to be set before include (optional).
 * Detects whether we're inside /student or /admin to build correct relative links.
 */
require_once __DIR__ . '/config.php';

$__title = isset($page_title) ? $page_title . ' · ' . APP_NAME : APP_NAME;

// Work out base path prefix so this header works from root, /student, /admin
$__script = $_SERVER['SCRIPT_NAME'];
$__in_sub = (strpos($__script, '/student/') !== false || strpos($__script, '/admin/') !== false);
$__root   = $__in_sub ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($__title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $__root ?>assets/css/style.css">
</head>
<body>

<nav class="es-nav">
    <a class="brand" href="<?= $__root ?>index.php">
        <span class="mark"></span> <?= APP_NAME ?>
    </a>
    <div class="nav-links">
        <?php if (isset($_SESSION['student_id'])): ?>
            <a href="<?= $__root ?>student/dashboard.php">Dashboard</a>
            <a href="<?= $__root ?>student/book_exam.php">Book Exam</a>
            <a href="<?= $__root ?>student/my_results.php">My Results</a>
            <a class="pill" href="<?= $__root ?>student/logout.php">Logout</a>
        <?php elseif (isset($_SESSION['admin_id'])): ?>
            <a href="<?= $__root ?>admin/dashboard.php">Dashboard</a>
            <a href="<?= $__root ?>admin/exam_requests.php">Exam Requests</a>
            <a href="<?= $__root ?>admin/manage_students.php">Students</a>
            <a class="pill" href="<?= $__root ?>admin/logout.php">Logout</a>
        <?php else: ?>
            <a href="<?= $__root ?>index.php">Home</a>
            <a href="<?= $__root ?>student/login.php">Student</a>
            <a class="pill" href="<?= $__root ?>admin/login.php">Admin</a>
        <?php endif; ?>
    </div>
</nav>
