<?php
define('APP_INIT', true);
$basePath = '../';
$currentPage = 'products';
$pageTitle = 'Tambah Produk Baru';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';
require_once __DIR__ . '/../helpers/validation.php';

require_role('admin', $basePath);

$errors = [];
$sku = '';
$name = '';
$categoryId = '';
$price = '';
$stock = '0';
$minimumStock = '5';
$selectedSuppliers = [];

// Load Categories & Suppliers
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$suppliers = $pdo->query("SELECT id, name FROM suppliers ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF Token validation failed.");
    }

    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $minimumStock = trim($_POST['minimum_stock'] ?? '5');
    $selectedSuppliers = $_POST['suppliers'] ?? [];

    // Syntactic Validation
    if ($err = validate_required($sku, 'SKU')) $errors[] = $err;
    if ($err = validate_required($name, 'Nama Produk')) $errors[] = $err;
    if (empty($categoryId)) $errors[] = 'Kategori wajib dipilih.';
    if ($err = validate_positive_number($price, 'Harga')) $errors[] = $err;
    if (!is_numeric($stock) || (int)$stock < 0) $errors[] = 'Stok awal tidak boleh bernilai negatif.';
    if (!is_numeric($minimumStock) || (int)$minimumStock < 0) $errors[] = 'Batas minimum stok tidak boleh negatif.';

    // Semantic Validation: Check SKU uniqueness
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = :sku LIMIT 1");
        $stmt->execute(['sku' => $sku]);
        if ($stmt->fetch()) {
            $errors[] = "SKU '{$sku}' sudah terdaftar. Silakan gunakan kode SKU lain.";
        }
    }

    // Save with Transaction
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO products (category_id, sku, name, price, stock, minimum_stock)
                VALUES (:category_id, :sku, :name, :price, :stock, :minimum_stock)
            ");
            $stmt->execute([
                'category_id' => $categoryId,
                'sku' => $sku,
                'name' => $name,
                'price' => $price,
                'stock' => (int)$stock,
                'minimum_stock' => (int)$minimumStock
            ]);
            $productId = $pdo->lastInsertId();

            // Insert many-to-many suppliers
            if (!empty($selectedSuppliers) && is_array($selectedSuppliers)) {
                $psStmt = $pdo->prepare("INSERT INTO product_suppliers (product_id, supplier_id) VALUES (:pid, :sid)");
                foreach ($selectedSuppliers as $supId) {
                    $supIdInt = (int)$supId;
                    if ($supIdInt > 0) {
                        $psStmt->execute(['pid' => $productId, 'sid' => $supIdInt]);
                    }
                }
            }

            // If initial stock > 0, log to stock_movements
            if ((int)$stock > 0) {
                $smStmt = $pdo->prepare("
                    INSERT INTO stock_movements (product_id, user_id, type, quantity, notes)
                    VALUES (:pid, :uid, 'IN', :qty, 'Stok awal input produk')
                ");
                $smStmt->execute([
                    'pid' => $productId,
                    'uid' => current_user()['id'],
                    'qty' => (int)$stock
                ]);
            }

            $pdo->commit();
            $_SESSION['flash_success'] = "Produk '{$name}' berhasil ditambahkan ke inventaris!";
            header("Location: index.php");
            exit();

        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = "Gagal menyimpan produk: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../views/header.php';
?>

<div style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-secondary">&larr; Kembali ke Katalog</a>
</div>

<div class="form-container">
    <h2>Tambah Produk Baru</h2>
    <p class="subtitle" style="margin-bottom: 20px;">Isi data detail produk dan hubungkan dengan supplier.</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="create.php">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

        <div class="form-group">
            <label for="sku">Kode SKU (Unik) *</label>
            <input type="text" id="sku" name="sku" required placeholder="Contoh: CAM-RET-01" value="<?= e($sku) ?>">
            <small>Kode pengenal unik untuk produk.</small>
        </div>

        <div class="form-group">
            <label for="name">Nama Produk *</label>
            <input type="text" id="name" name="name" required placeholder="Contoh: Olympus Trip 35 Black" value="<?= e($name) ?>">
        </div>

        <div class="form-group">
            <label for="category_id">Kategori *</label>
            <select id="category_id" name="category_id" required>
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat['id']) ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label for="price">Harga Satuan (Rp) *</label>
                <input type="number" id="price" name="price" step="1000" min="0" required placeholder="1500000" value="<?= e($price) ?>">
            </div>

            <div class="form-group" style="flex: 1; min-width: 120px;">
                <label for="stock">Stok Awal *</label>
                <input type="number" id="stock" name="stock" min="0" required value="<?= e($stock) ?>">
            </div>

            <div class="form-group" style="flex: 1; min-width: 120px;">
                <label for="minimum_stock">Batas Minimum Stok *</label>
                <input type="number" id="minimum_stock" name="minimum_stock" min="0" required value="<?= e($minimumStock) ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Pilih Supplier Terkait (Many-to-Many):</label>
            <div style="background-color: #0f172a; border: 1px solid #334155; border-radius: 6px; padding: 12px; max-height: 140px; overflow-y: auto;">
                <?php if (empty($suppliers)): ?>
                    <small style="color: #94a3b8;">Belum ada supplier terdaftar.</small>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                        <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; cursor: pointer; color: #f8fafc; font-weight: normal;">
                            <input type="checkbox" name="suppliers[]" value="<?= e($s['id']) ?>" 
                                <?= in_array($s['id'], $selectedSuppliers) ? 'checked' : '' ?>>
                            <?= e($s['name']) ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <small>Satu produk dapat disuplai oleh lebih dari satu vendor supplier.</small>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Simpan Produk</button>
    </form>
</div>

<?php
include __DIR__ . '/../views/footer.php';
?>
