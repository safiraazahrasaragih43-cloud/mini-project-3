<?php
define('APP_INIT', true);
$basePath = '../';
$currentPage = 'categories';
$pageTitle = 'Master Kategori';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';

require_role('admin', $basePath);

// Categories with total products count
$stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count 
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name ASC
");
$categories = $stmt->fetchAll();

include __DIR__ . '/../views/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
    <div>
        <h1>Master Data Kategori</h1>
        <p class="subtitle">Kelola kelompok kategori inventaris produk.</p>
    </div>
    <a href="create.php" class="btn btn-primary">+ Tambah Kategori Baru</a>
</div>

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Kategori</th>
                <th>Deskripsi</th>
                <th>Jumlah Produk Terkait</th>
                <th style="text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 25px;">Belum ada data kategori.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td>#<?= e($cat['id']) ?></td>
                        <td><strong><?= e($cat['name']) ?></strong></td>
                        <td><?= e($cat['description'] ?: '-') ?></td>
                        <td><span class="badge badge-info"><?= e($cat['product_count']) ?> Produk</span></td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="update.php?id=<?= e($cat['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                            <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="id" value="<?= e($cat['id']) ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
include __DIR__ . '/../views/footer.php';
?>
