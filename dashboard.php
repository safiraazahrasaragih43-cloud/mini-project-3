<?php
define('APP_INIT', true);
$basePath = '';
$currentPage = 'dashboard';
$pageTitle = 'Dashboard Inventory';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/escape.php';

require_login();

// 1. Total Produk: COUNT(products.id)
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM products");
$totalProducts = $stmt->fetch()['total'] ?? 0;

// 2. Nilai Inventory: SUM(stock * price)
$stmt = $pdo->query("SELECT SUM(stock * price) AS total_value FROM products");
$totalInventoryValue = $stmt->fetch()['total_value'] ?? 0;

// 3. Produk Stok Rendah: COUNT(*) WHERE stock <= minimum_stock
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM products WHERE stock <= minimum_stock");
$lowStockCount = $stmt->fetch()['total'] ?? 0;

// 4. Barang Masuk & Keluar: SUM(quantity) WHERE type = 'IN' / 'OUT'
$stmt = $pdo->query("SELECT 
    SUM(CASE WHEN type = 'IN' THEN quantity ELSE 0 END) AS total_in,
    SUM(CASE WHEN type = 'OUT' THEN quantity ELSE 0 END) AS total_out
    FROM stock_movements");
$stockStats = $stmt->fetch();
$totalIn = $stockStats['total_in'] ?? 0;
$totalOut = $stockStats['total_out'] ?? 0;

// 5. Daftar Produk Stok Rendah
$stmt = $pdo->query("
    SELECT p.*, c.name AS category_name 
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.stock <= p.minimum_stock
    ORDER BY p.stock ASC
");
$lowStockProducts = $stmt->fetchAll();

// 6. Ringkasan Kategori (GROUP BY & Aggregate)
$stmt = $pdo->query("
    SELECT c.id, c.name,
           COUNT(p.id) AS product_count,
           COALESCE(SUM(p.stock), 0) AS total_stock,
           COALESCE(AVG(p.price), 0) AS avg_price,
           COALESCE(SUM(p.stock * p.price), 0) AS category_value
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name
    ORDER BY c.name ASC
");
$categoryReports = $stmt->fetchAll();

// 7. 5 Riwayat Pergerakan Stok Terbaru
$stmt = $pdo->query("
    SELECT sm.*, p.name AS product_name, p.sku, u.username
    FROM stock_movements sm
    JOIN products p ON p.id = sm.product_id
    JOIN users u ON u.id = sm.user_id
    ORDER BY sm.created_at DESC
    LIMIT 5
");
$recentMovements = $stmt->fetchAll();

include __DIR__ . '/views/header.php';
?>

<header>
    <h1>Dashboard Ringkasan Inventory</h1>
    <p class="subtitle">Pantau kondisi stok kamera retro, nilai persediaan, dan log transaksi secara real-time.</p>
</header>

<!-- Widget Statistik Sesuai Slide 29 Modul -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Produk</div>
        <div class="stat-value"><?= number_format($totalProducts) ?></div>
        <div class="stat-desc">Item SKU terdaftar</div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-label">Nilai Inventory</div>
        <div class="stat-value"><?= format_rupiah($totalInventoryValue) ?></div>
        <div class="stat-desc">Estimasi total aset stok</div>
    </div>

    <div class="stat-card <?= $lowStockCount > 0 ? 'stat-danger' : 'stat-success' ?>">
        <div class="stat-label">Produk Stok Rendah</div>
        <div class="stat-value"><?= number_format($lowStockCount) ?></div>
        <div class="stat-desc">Stok &le; batas minimum</div>
    </div>

    <div class="stat-card stat-warning">
        <div class="stat-label">Total Masuk / Keluar</div>
        <div class="stat-value" style="font-size: 1.35rem;">
            <span style="color: #4ade80;">+<?= number_format($totalIn) ?></span> / 
            <span style="color: #f87171;">-<?= number_format($totalOut) ?></span>
        </div>
        <div class="stat-desc">Akumulasi pergerakan unit</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="top-bar">
    <div style="font-weight: 600; color: #e2e8f0;">⚡ Operasional Cepat:</div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="stock/create.php?type=IN" class="btn btn-success">+ Catat Barang Masuk</a>
        <a href="stock/create.php?type=OUT" class="btn btn-danger">- Catat Barang Keluar</a>
        <?php if (current_user()['role'] === 'admin'): ?>
            <a href="products/create.php" class="btn btn-primary">+ Tambah Produk Baru</a>
        <?php endif; ?>
    </div>
</div>

<!-- Peringatan Stok Menipis -->
<?php if (!empty($lowStockProducts)): ?>
<section style="margin-bottom: 30px;">
    <h2>⚠️ Peringatan Stok Menipis (Restock Diperlukan)</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Stok Sekarang</th>
                    <th>Batas Min.</th>
                    <th>Aksi Cepat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lowStockProducts as $lp): ?>
                    <tr>
                        <td><code><?= e($lp['sku']) ?></code></td>
                        <td><strong><?= e($lp['name']) ?></strong></td>
                        <td><span class="badge badge-info"><?= e($lp['category_name']) ?></span></td>
                        <td><span class="badge badge-danger"><?= e($lp['stock']) ?> Unit</span></td>
                        <td><?= e($lp['minimum_stock']) ?> Unit</td>
                        <td>
                            <a href="stock/create.php?type=IN&product_id=<?= e($lp['id']) ?>" class="btn btn-sm btn-success">+ Restock Masuk</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<!-- Laporan Kategori Berbasis Aggregate Function (Slide 14 & 28) -->
<section style="margin-bottom: 30px;">
    <h2>📊 Ringkasan Laporan per Kategori (Aggregate Queries)</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Jumlah Jenis Produk</th>
                    <th>Total Kuantitas Stok</th>
                    <th>Rata-rata Harga Satuan</th>
                    <th>Total Nilai Valuasi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categoryReports as $cr): ?>
                    <tr>
                        <td><strong><?= e($cr['name']) ?></strong></td>
                        <td><?= number_format($cr['product_count']) ?> SKU</td>
                        <td><?= number_format($cr['total_stock']) ?> pcs</td>
                        <td><?= format_rupiah($cr['avg_price']) ?></td>
                        <td><strong><?= format_rupiah($cr['category_value']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- 5 Riwayat Pergerakan Stok Terkini -->
<section>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h2>🕒 Transaksi Pergerakan Terkini</h2>
        <a href="stock/index.php" class="btn btn-sm btn-secondary">Lihat Semua Riwayat &rarr;</a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Tipe</th>
                    <th>SKU & Produk</th>
                    <th>Kuantitas</th>
                    <th>Oleh User</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentMovements)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #94a3b8;">Belum ada riwayat transaksi barang.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentMovements as $rm): ?>
                        <tr>
                            <td><?= e(date('d M Y, H:i', strtotime($rm['created_at']))) ?></td>
                            <td>
                                <?php if ($rm['type'] === 'IN'): ?>
                                    <span class="badge badge-success">MASUK (IN)</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">KELUAR (OUT)</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= e($rm['product_name']) ?></strong> <small>(<?= e($rm['sku']) ?>)</small></td>
                            <td><strong><?= ($rm['type'] === 'IN' ? '+' : '-') . e($rm['quantity']) ?></strong></td>
                            <td>👤 <?= e($rm['username']) ?></td>
                            <td><?= e($rm['notes'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
include __DIR__ . '/views/footer.php';
?>
