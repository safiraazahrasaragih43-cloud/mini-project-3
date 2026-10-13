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
        $_SESSION['flash_error'] = "ID Produk tidak valid.";
        header("Location: index.php");
        exit();
    }

    // Business Rule: Produk yang memiliki histori transaksi stock_movements tidak boleh dihapus sembarangan (Slide 33)
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) AS total FROM stock_movements WHERE product_id = :id");
    $stmtCheck->execute(['id' => $id]);
    $historyCount = $stmtCheck->fetch()['total'];

    if ($historyCount > 0) {
        $_SESSION['flash_error'] = "Gagal menghapus produk: Produk memiliki {$historyCount} riwayat transaksi mutasi stok. Hapus dilarang untuk menjaga integritas data.";
        header("Location: index.php");
        exit();
    }

    try {
        $delStmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $delStmt->execute(['id' => $id]);

        $_SESSION['flash_success'] = "Produk berhasil dihapus.";
    } catch (Throwable $e) {
        $_SESSION['flash_error'] = "Terjadi kesalahan saat menghapus: " . $e->getMessage();
    }

    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
