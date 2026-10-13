<?php
define('APP_INIT', true);
$basePath = '../';
$currentPage = 'products';
$pageTitle = 'Daftar Produk';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';

require_login($basePath);

// Filter & Search Parameters
$search = trim($_GET['search'] ?? '');
$categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);

// Sorting with Allowlist (Slide 16 Modul)
$allowedSort = [
    'name' => 'p.name',
    'sku' => 'p.sku',
    'price' => 'p.price',
    'stock' => 'p.stock',
    'created_at' => 'p.created_at'
];
$sortBy = $_GET['sort'] ?? 'name';
if (!array_key_exists($sortBy, $allowedSort)) {
    $sortBy = 'name';
}
$orderCol = $allowedSort[$sortBy];

$orderDir = strtoupper($_GET['dir'] ?? 'ASC');
if ($orderDir !== 'ASC' && $orderDir !== 'DESC') {
    $orderDir = 'ASC';
}

// Build Query Conditions
$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = "(p.name LIKE :search_name OR p.sku LIKE :search_sku)";
    $params['search_name'] = "%$search%";
    $params['search_sku'] = "%$search%";
}

if (!empty($categoryId)) {
    $conditions[] = "p.category_id = :category_id";
    $params['category_id'] = $categoryId;
}

$whereClause = '';
if (!empty($conditions)) {
    $whereClause = ' WHERE ' . implode(' AND ', $conditions);
}

// Pagination (Slide 17 Modul)
$perPage = 6;
$countSql = "SELECT COUNT(*) as total FROM products p" . $whereClause;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetch()['total'];

$totalPages = max(1, (int)ceil($totalProducts / $perPage));
$page = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$offset = ($page - 1) * $perPage;

// Main Query with JOIN, Suppliers Aggregate, and LIMIT OFFSET
$sql = "
    SELECT p.*, c.name AS category_name,
           GROUP_CONCAT(s.name SEPARATOR ', ') AS suppliers_list
    FROM products p
    JOIN categories c ON c.id = p.category_id
    LEFT JOIN product_suppliers ps ON ps.product_id = p.id
    LEFT JOIN suppliers s ON s.id = ps.supplier_id
    $whereClause
    GROUP BY p.id
    ORDER BY $orderCol $orderDir
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue(":$key", $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Categories for dropdown filter
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

// Helper to preserve query strings for pagination and sort
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
        <h1>Katalog & Inventaris Produk</h1>
        <p class="subtitle">Kelola master data produk, status stok, relasi kategori dan supplier.</p>
    </div>
    <?php if (current_user()['role'] === 'admin'): ?>
        <a href="create.php" class="btn btn-primary">+ Tambah Produk Baru</a>
    <?php endif; ?>
</div>

<!-- Search, Filter & Sort Form -->
<form method="GET" action="index.php" class="top-bar">
    <div class="filter-bar">
        <div style="flex: 2; min-width: 220px;">
            <input type="text" name="search" placeholder="Cari nama kamera / SKU..." value="<?= e($search) ?>">
        </div>
        
        <div style="flex: 1.5; min-width: 180px;">
            <select name="category_id">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat['id']) ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex: 1.2; min-width: 150px;">
            <select name="sort">
                <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Urut: Nama</option>
                <option value="sku" <?= $sortBy === 'sku' ? 'selected' : '' ?>>Urut: SKU</option>
                <option value="price" <?= $sortBy === 'price' ? 'selected' : '' ?>>Urut: Harga</option>
                <option value="stock" <?= $sortBy === 'stock' ? 'selected' : '' ?>>Urut: Stok</option>
                <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Urut: Terbaru</option>
            </select>
        </div>

        <div style="flex: 1; min-width: 110px;">
            <select name="dir">
                <option value="ASC" <?= $orderDir === 'ASC' ? 'selected' : '' ?>>A-Z / Naik</option>
                <option value="DESC" <?= $orderDir === 'DESC' ? 'selected' : '' ?>>Z-A / Turun</option>
            </select>
        </div>

        <div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="index.php" class="btn btn-secondary">Reset</a>
        </div>
    </div>
</form>

<!-- Products Table -->
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Harga Satuan</th>
                <th>Stok</th>
                <th>Supplier Terhubung</th>
                <th style="text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: #94a3b8;">
                        Tidak ada produk yang sesuai dengan kriteria pencarian.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><code><?= e($p['sku']) ?></code></td>
                        <td>
                            <strong><?= e($p['name']) ?></strong>
                        </td>
                        <td><span class="badge badge-info"><?= e($p['category_name']) ?></span></td>
                        <td style="color: #4ade80; font-weight: 600;"><?= format_rupiah($p['price']) ?></td>
                        <td>
                            <?php if ($p['stock'] <= $p['minimum_stock']): ?>
                                <span class="badge badge-danger"><?= e($p['stock']) ?> pcs (Rendah &le; <?= e($p['minimum_stock']) ?>)</span>
                            <?php else: ?>
                                <span class="badge badge-success"><?= e($p['stock']) ?> pcs</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 0.85rem; color: #cbd5e1; max-width: 200px;">
                            <?= e($p['suppliers_list'] ?: 'Belum ada supplier') ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <div style="display: inline-flex; gap: 6px;">
                                <a href="../stock/create.php?product_id=<?= e($p['id']) ?>" class="btn btn-sm btn-success" title="Mutasi Stok">Mutasi</a>
                                <?php if (current_user()['role'] === 'admin'): ?>
                                    <a href="update.php?id=<?= e($p['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                        <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination (Slide 17 Modul) -->
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
