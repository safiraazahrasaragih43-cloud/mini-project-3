<?php
// views/forbidden.php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../helpers/escape.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>403 - Akses Ditolak</title>
    <link rel="stylesheet" href="<?= $basePath ?? '' ?>style.css">
</head>
<body>
    <div class="container" style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
        <div class="form-container" style="text-align: center;">
            <h1 style="color: #ef4444; font-size: 3rem; margin-bottom: 10px;">403</h1>
            <h2>Akses Ditolak (Forbidden)</h2>
            <p style="color: #94a3b8; margin: 15px 0 25px;">
                Maaf, Anda tidak memiliki izin hak akses untuk membuka halaman ini. Fitur ini dibatasi khusus untuk Administrator.
            </p>
            <a href="<?= $basePath ?? '' ?>dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>
