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
        $_SESSION['flash_error'] = "ID Kategori tidak valid.";
        header("Location: index.php");
        exit();
    }

    // Referential integrity check: Kategori yang memiliki produk tidak boleh dihapus (RESTRICT)
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) AS total FROM products WHERE category_id = :id");
    $stmtCheck->execute(['id' => $id]);
    $prodCount = $stmtCheck->fetch()['total'];

    if ($prodCount > 0) {
        $_SESSION['flash_error'] = "Gagal menghapus kategori: Masih ada {$prodCount} produk yang terhubung dengan kategori ini. Pindahkan atau hapus produk terlebih dahulu.";
        header("Location: index.php");
        exit();
    }

    try {
        $del = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $del->execute(['id' => $id]);

        $_SESSION['flash_success'] = "Kategori berhasil dihapus.";
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = "Gagal menghapus: " . $e->getMessage();
    }

    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
