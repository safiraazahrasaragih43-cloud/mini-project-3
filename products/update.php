<?php
define('APP_INIT', true);
$basePath = '../';
$currentPage = 'products';
$pageTitle = 'Edit Produk';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';
require_once __DIR__ . '/../helpers/validation.php';

require_role('admin', $basePath);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['flash_error'] = "ID Produk tidak valid.";
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['flash_error'] = "Produk tidak ditemukan.";
    header("Location: index.php");
    exit();
}

// Current assigned suppliers
$assignedSuppliersStmt = $pdo->prepare("SELECT supplier_id FROM product_suppliers WHERE product_id = :pid");
$assignedSuppliersStmt->execute(['pid' => $id]);
$currentSuppliers = $assignedSuppliersStmt->fetchAll(PDO::FETCH_COLUMN);

// Categories & Suppliers lists
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$suppliers = $pdo->query("SELECT id, name FROM suppliers ORDER BY name ASC")->fetchAll();

$errors = [];
$sku = $product['sku'];
$name = $product['name'];
$categoryId = $product['category_id'];
$price = $product['price'];
$minimumStock = $product['minimum_stock'];
$selectedSuppliers = $currentSuppliers;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF Token validation failed.");
    }

    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $price = trim($_POST['price'] ?? '');
    $minimumStock = trim($_POST['minimum_stock'] ?? '5');
    $selectedSuppliers = $_POST['suppliers'] ?? [];

    // Syntactic Validation
    if ($err = validate_required($sku, 'SKU')) $errors[] = $err;
    if ($err = validate_required($name, 'Nama Produk')) $errors[] = $err;
    if (empty($categoryId)) $errors[] = 'Kategori wajib dipilih.';
    if ($err = validate_positive_number($price, 'Harga')) $errors[] = $err;
    if (!is_numeric($minimumStock) || (int)$minimumStock < 0) $errors[] = 'Batas minimum stok tidak boleh negatif.';

    // Semantic Validation: Check SKU uniqueness (excluding current product)
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE sku = :sku AND id != :id LIMIT 1");
        $stmt->execute(['sku' => $sku, 'id' => $id]);
        if ($stmt->fetch()) {
            $errors[] = "SKU '{$sku}' sudah digunakan oleh produk lain.";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $updateStmt = $pdo->prepare("
                UPDATE products 
                SET category_id = :category_id,
                    sku = :sku,
                    name = :name,
                    price = :price,
                    minimum_stock = :minimum_stock
                WHERE id = :id
            ");
            $updateStmt->execute([
                'category_id' => $categoryId,
                'sku' => $sku,
                'name' => $name,
                'price' => $price,
                'minimum_stock' => (int)$minimumStock,
                'id' => $id
            ]);

            // Sync product_suppliers junction table
            $delStmt = $pdo->prepare("DELETE FROM product_suppliers WHERE product_id = :pid");
            $delStmt->execute(['pid' => $id]);

            if (!empty($selectedSuppliers) && is_array($selectedSuppliers)) {
                $insPs = $pdo->prepare("INSERT INTO product_suppliers (product_id, supplier_id) VALUES (:pid, :sid)");
                foreach ($selectedSuppliers as $supId) {
                    $supIdInt = (int)$supId;
                    if ($supIdInt > 0) {
                        $insPs->execute(['pid' => $id, 'sid' => $supIdInt]);
                    }
                }
            }

            $pdo->commit();
            $_SESSION['flash_success'] = "Data produk '{$name}' berhasil diperbarui!";
            header("Location: index.php");
            exit();

        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = "Gagal memperbarui produk: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../views/header.php';
?>

<div style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-secondary">&larr; Kembali ke Katalog</a>
</div>

<div class="form-container">
    <h2>Edit Data Produk</h2>
    <p class="subtitle" style="margin-bottom: 20px;">Perbarui data inventaris produk (stok dikelola melalui mutasi stok).</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="update.php?id=<?= e($id) ?>">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

        <div class="form-group">
            <label for="sku">Kode SKU *</label>
            <input type="text" id="sku" name="sku" required value="<?= e($sku) ?>">
        </div>

        <div class="form-group">
            <label for="name">Nama Produk *</label>
            <input type="text" id="name" name="name" required value="<?= e($name) ?>">
        </div>

        <div class="form-group">
            <label for="category_id">Kategori *</label>
            <select id="category_id" name="category_id" required>
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
                <input type="number" id="price" name="price" step="1000" min="0" required value="<?= e($price) ?>">
            </div>

            <div class="form-group" style="flex: 1; min-width: 120px;">
                <label for="stock_static">Stok Saat Ini (Read-only)</label>
                <input type="text" id="stock_static" value="<?= e($product['stock']) ?> pcs" disabled style="background-color: #334155; opacity: 0.8;">
                <small>Ubah kuantitas melalui menu Mutasi Stok.</small>
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
        </div>

        <button type="submit" class="btn btn-warning" style="width: 100%; margin-top: 10px;">Simpan Perubahan</button>
    </form>
</div>

<?php
include __DIR__ . '/../views/footer.php';
?>
