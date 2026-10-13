<?php
define('APP_INIT', true);
$basePath = '../';
$currentPage = 'stock';
$pageTitle = 'Riwayat Pergerakan Stok';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';

require_login($basePath);

// Filters
$typeFilter = $_GET['type'] ?? '';
if ($typeFilter !== 'IN' && $typeFilter !== 'OUT') {
    $typeFilter = '';
}
$productId = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);

// Build conditions
$conditions = [];
$params = [];

if ($typeFilter !== '') {
    $conditions[] = "sm.type = :type";
    $params['type'] = $typeFilter;
}

if (!empty($productId)) {
    $conditions[] = "sm.product_id = :product_id";
    $params['product_id'] = $productId;
}

$whereClause = !empty($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '';

// Pagination
$perPage = 10;
$countSql = "SELECT COUNT(*) as total FROM stock_movements sm $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetch()['total'];

$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$page = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$offset = ($page - 1) * $perPage;

// Query movements
$sql = "
    SELECT sm.*, p.name AS product_name, p.sku, u.username
    FROM stock_movements sm
    JOIN products p ON p.id = sm.product_id
    JOIN users u ON u.id = sm.user_id
    $whereClause
    ORDER BY sm.created_at DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(":$k", $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$movements = $stmt->fetchAll();

// Product list for filter
$allProducts = $pdo->query("SELECT id, name, sku FROM products ORDER BY name ASC")->fetchAll();

function build_query_url($overrides = []) {
    $current = $_GET;
    foreach ($overrides as $k => $v) {
        $current[$k] = $v;
    }
    return '?' . http_build_query($current);
}

include __DIR__ . '/../views/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
    <div>
        <h1>Histori Mutasi & Pergerakan Stok</h1>
        <p class="subtitle">Log audit setiap barang masuk (IN) dan barang keluar (OUT) beserta pencatatnya.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="create.php?type=IN" class="btn btn-success">+ Transaksi Masuk (IN)</a>
        <a href="create.php?type=OUT" class="btn btn-danger">- Transaksi Keluar (OUT)</a>
    </div>
</div>

<!-- Filter Bar -->
<form method="GET" action="index.php" class="top-bar">
    <div class="filter-bar">
        <div style="flex: 2; min-width: 220px;">
            <select name="product_id">
                <option value="">Semua Produk Kamera</option>
                <?php foreach ($allProducts as $p): ?>
                    <option value="<?= e($p['id']) ?>" <?= $productId == $p['id'] ? 'selected' : '' ?>>
                        <?= e($p['name']) ?> (<?= e($p['sku']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex: 1; min-width: 140px;">
            <select name="type">
                <option value="">Semua Tipe Transaksi</option>
                <option value="IN" <?= $typeFilter === 'IN' ? 'selected' : '' ?>>Barang Masuk (IN)</option>
                <option value="OUT" <?= $typeFilter === 'OUT' ? 'selected' : '' ?>>Barang Keluar (OUT)</option>
            </select>
        </div>

        <div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="index.php" class="btn btn-secondary">Reset</a>
        </div>
    </div>
</form>

<!-- Table -->
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Waktu Transaksi</th>
                <th>Tipe</th>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th>Kuantitas</th>
                <th>Dicatat Oleh</th>
                <th>Catatan / Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($movements)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: #94a3b8;">
                        Tidak ada riwayat pergerakan stok yang cocok dengan kriteria.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($movements as $m): ?>
                    <tr>
                        <td><?= e(date('d M Y, H:i:s', strtotime($m['created_at']))) ?></td>
                        <td>
                            <?php if ($m['type'] === 'IN'): ?>
                                <span class="badge badge-success">MASUK (IN)</span>
                            <?php else: ?>
                                <span class="badge badge-danger">KELUAR (OUT)</span>
                            <?php endif; ?>
                        </td>
                        <td><code><?= e($m['sku']) ?></code></td>
                        <td><strong><?= e($m['product_name']) ?></strong></td>
                        <td style="font-weight: bold; font-size: 1.05rem; color: <?= $m['type'] === 'IN' ? '#4ade80' : '#f87171' ?>;">
                            <?= ($m['type'] === 'IN' ? '+' : '-') . e($m['quantity']) ?> pcs
                        </td>
                        <td>👤 <?= e($m['username']) ?></td>
                        <td><?= e($m['notes'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="<?= build_query_url(['page' => $page - 1]) ?>">&laquo; Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= build_query_url(['page' => $i]) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?= build_query_url(['page' => $page + 1]) ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
include __DIR__ . '/../views/footer.php';
?>
