<?php
define('APP_INIT', true);
$basePath = '../';
$currentPage = 'suppliers';
$pageTitle = 'Master Supplier';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';

require_role('admin', $basePath);

// Suppliers with product count from junction table
$stmt = $pdo->query("
    SELECT s.*, COUNT(ps.product_id) AS supplied_count
    FROM suppliers s
    LEFT JOIN product_suppliers ps ON ps.supplier_id = s.id
    GROUP BY s.id
    ORDER BY s.name ASC
");
$suppliers = $stmt->fetchAll();

include __DIR__ . '/../views/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
    <div>
        <h1>Master Data Supplier (Vendor)</h1>
        <p class="subtitle">Kelola daftar rekanan vendor penyedia stok produk (Many-to-Many).</p>
    </div>
    <a href="create.php" class="btn btn-primary">+ Tambah Supplier Baru</a>
</div>

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Vendor / Supplier</th>
                <th>Kontak Person</th>
                <th>No. Telepon</th>
                <th>Alamat</th>
                <th>Produk Disuplai</th>
                <th style="text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($suppliers)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 25px;">Belum ada supplier terdaftar.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($suppliers as $s): ?>
                    <tr>
                        <td>#<?= e($s['id']) ?></td>
                        <td><strong><?= e($s['name']) ?></strong></td>
                        <td><?= e($s['contact_person'] ?: '-') ?></td>
                        <td><?= e($s['phone'] ?: '-') ?></td>
                        <td><?= e($s['address'] ?: '-') ?></td>
                        <td><span class="badge badge-info"><?= e($s['supplied_count']) ?> Item</span></td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="update.php?id=<?= e($s['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                            <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus supplier ini?');">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="id" value="<?= e($s['id']) ?>">
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
