<?php
// views/header.php
if (!defined('APP_INIT')) {
    exit('Direct access not permitted');
}
$currentUser = current_user();
$base = $basePath ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Inventory Management') ?> - RetroCam IMS</title>
    <link rel="stylesheet" href="<?= $base ?>style.css">
</head>
<body>
    <nav class="navbar">
        <a href="<?= $base ?>dashboard.php" class="brand">
            <span>📦</span> RetroCam IMS
        </a>
        <div class="nav-links">
            <a href="<?= $base ?>dashboard.php" class="<?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="<?= $base ?>products/index.php" class="<?= ($currentPage ?? '') === 'products' ? 'active' : '' ?>">Produk</a>
            <a href="<?= $base ?>stock/index.php" class="<?= ($currentPage ?? '') === 'stock' ? 'active' : '' ?>">Pergerakan Stok</a>
            <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                <a href="<?= $base ?>categories/index.php" class="<?= ($currentPage ?? '') === 'categories' ? 'active' : '' ?>">Kategori</a>
                <a href="<?= $base ?>suppliers/index.php" class="<?= ($currentPage ?? '') === 'suppliers' ? 'active' : '' ?>">Supplier</a>
            <?php endif; ?>
        </div>
        <div class="user-badge">
            <span>👤 <?= e($currentUser['username'] ?? 'Tamu') ?></span>
            <span class="role-tag role-<?= e($currentUser['role'] ?? 'staff') ?>"><?= e($currentUser['role'] ?? 'staff') ?></span>
            <a href="<?= $base ?>logout.php" class="btn btn-sm btn-danger" style="margin-left: 8px;">Logout</a>
        </div>
    </nav>
    <main class="container">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success">
                <?= e($_SESSION['flash_success']) ?>
                <?php unset($_SESSION['flash_success']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger">
                <?= e($_SESSION['flash_error']) ?>
                <?php unset($_SESSION['flash_error']); ?>
            </div>
        <?php endif; ?>
