<?php
define('APP_INIT', true);
$basePath = '../';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';

require_role('admin', $basePath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF Token validation failed.");
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        $_SESSION['flash_error'] = "ID Supplier tidak valid.";
        header("Location: index.php");
        exit();
    }

    try {
        $del = $pdo->prepare("DELETE FROM suppliers WHERE id = :id");
        $del->execute(['id' => $id]);

        $_SESSION['flash_success'] = "Supplier berhasil dihapus.";
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = "Gagal menghapus supplier: " . $e->getMessage();
    }

    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
