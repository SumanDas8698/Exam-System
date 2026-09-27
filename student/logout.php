<?php
require_once __DIR__ . '/../includes/config.php';

unset($_SESSION['student_id'], $_SESSION['student_name'], $_SESSION['student_email'], $_SESSION['student_course']);
session_regenerate_id(true);

set_flash('info', "You have been logged out.");
redirect('login.php');
