<?php
define('APP_INIT', true);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit();
