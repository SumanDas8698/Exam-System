<?php
require_once __DIR__ . '/../includes/config.php';

unset($_SESSION['admin_id'], $_SESSION['admin_username']);
session_regenerate_id(true);

set_flash('info', "You have been logged out.");
redirect('login.php');
