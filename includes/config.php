<?php
/**
 * config.php — Shared constants, helpers, session bootstrap.
 * Include this (not db.php directly) from every page.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

const APP_NAME     = "Online Exam System";
const EXAM_MINUTES = 30; // exam duration in minutes

/**
 * Generate the next sequential ID for a table, e.g. STU001, F01, R01.
 * $prefix    e.g. 'STU'
 * $digits    zero-pad width
 */
function next_id(mysqli $conn, string $table, string $prefix, int $digits): string {
    $stmt = $conn->prepare("
        SELECT COALESCE(MAX(CAST(SUBSTRING(id, ?) AS UNSIGNED)), 0) AS max_num
        FROM $table
        WHERE id REGEXP ?
    ");
    $start = strlen($prefix) + 1;
    $regex = '^' . $prefix . '[0-9]+$';
    $stmt->bind_param("is", $start, $regex);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $next = (int)$row['max_num'] + 1;
    return $prefix . str_pad((string)$next, $digits, '0', STR_PAD_LEFT);
}

/** Escape helper shortcut */
function h(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/** Redirect helper */
function redirect(string $path): void {
    header("Location: $path");
    exit();
}

/** Require an active student session, else redirect to student login */
function require_student_login(): void {
    if (!isset($_SESSION['student_id'])) {
        redirect('/student/login.php');
    }
}

/** Require an active admin session, else redirect to admin login */
function require_admin_login(): void {
    if (!isset($_SESSION['admin_id'])) {
        redirect('/admin/login.php');
    }
}

/** Flash message helpers (one-time session messages) */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
