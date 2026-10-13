<?php
// helpers/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login($basePath = '') {
    if (!is_logged_in()) {
        header("Location: " . $basePath . "login.php");
        exit();
    }
}

function require_role($required_role, $basePath = '') {
    require_login($basePath);
    $user = current_user();
    if ($user['role'] !== $required_role) {
        http_response_code(403);
        include __DIR__ . '/../views/forbidden.php';
        exit();
    }
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}
