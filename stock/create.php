<?php
define('APP_INIT', true);
$basePath = '../';
$currentPage = 'stock';
$pageTitle = 'Catat Mutasi Stok';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';
require_once __DIR__ . '/../helpers/validation.php';

require_login($basePath);

$errors = [];
$defaultType = strtoupper($_GET['type'] ?? 'IN');
if ($defaultType !== 'IN' && $defaultType !== 'OUT') {
    $defaultType = 'IN';
}

$productId = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT) ?? '';
$quantity = '';
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF Token validation failed.");
    }

    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $type = strtoupper($_POST['type'] ?? 'IN');
    $quantity = trim($_POST['quantity'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($type !== 'IN' && $type !== 'OUT') {
        $errors[] = "Tipe transaksi harus bernilai 'IN' atau 'OUT'.";
    }

    if (empty($productId)) {
        $errors[] = "Produk wajib dipilih.";
    }

    if ($err = validate_positive_int($quantity, 'Kuantitas')) {
        $errors[] = $err;
    }

    $qtyInt = (int)$quantity;

    if (empty($errors)) {
        try {
            // Business Logic: Database Transaction (Slide 25, 26, 27)
            $pdo->beginTransaction();

            // Lock product row using FOR UPDATE to prevent race condition
            $stmt = $pdo->prepare("SELECT id, name, sku, stock FROM products WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $productId]);
            $prod = $stmt->fetch();

            if (!$prod) {
                throw new Exception("Produk tidak ditemukan dalam database.");
            }

            if ($type === 'OUT') {
                // Semantic Rule: Barang keluar tidak boleh melebihi stok tersedia
                if ($prod['stock'] < $qtyInt) {
                    throw new Exception("Stok tidak mencukupi! Stok saat ini untuk '{$prod['name']}' hanya {$prod['stock']} unit, sedangkan permintaan keluar adalah {$qtyInt} unit.");
                }
                $newStock = $prod['stock'] - $qtyInt;
            } else {
                $newStock = $prod['stock'] + $qtyInt;
            }

            // Update stok produk
            $updateStmt = $pdo->prepare("UPDATE products SET stock = :new_stock WHERE id = :id");
            $updateStmt->execute([
                'new_stock' => $newStock,
                'id' => $productId
            ]);

            // Catat log stock movements
            $logStmt = $pdo->prepare("
                INSERT INTO stock_movements (product_id, user_id, type, quantity, notes)
                VALUES (:product_id, :user_id, :type, :quantity, :notes)
            ");
            $logStmt->execute([
                'product_id' => $productId,
                'user_id' => current_user()['id'],
                'type' => $type,
                'quantity' => $qtyInt,
                'notes' => $notes
            ]);

            $pdo->commit();

            $labelType = ($type === 'IN') ? 'Masuk' : 'Keluar';
            $_SESSION['flash_success'] = "Berhasil mencatat transaksi Barang {$labelType} sebanyak {$qtyInt} unit untuk {$prod['name']}. Sisa stok sekarang: {$newStock} unit.";
            header("Location: index.php");
            exit();

        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

// Fetch products for dropdown
$products = $pdo->query("SELECT id, sku, name, stock FROM products ORDER BY name ASC")->fetchAll();

include __DIR__ . '/../views/header.php';
?>

<div style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-secondary">&larr; Kembali ke Histori Mutasi</a>
</div>

<div class="form-container">
    <h2>Catat Transaksi Mutasi Stok</h2>
    <p class="subtitle" style="margin-bottom: 20px;">
        Pastikan input valid. Transaksi berjalan secara atomik (ACID) menggunakan PDO Transaction.
    </p>

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
            <label for="type">Jenis Mutasi *</label>
            <select id="type" name="type" required>
                <option value="IN" <?= $defaultType === 'IN' ? 'selected' : '' ?>>➕ BARANG MASUK (IN) - Menambah Stok</option>
                <option value="OUT" <?= $defaultType === 'OUT' ? 'selected' : '' ?>>➖ BARANG KELUAR (OUT) - Mengurangi Stok</option>
            </select>
        </div>

        <div class="form-group">
            <label for="product_id">Pilih Produk Kamera / Item *</label>
            <select id="product_id" name="product_id" required>
                <option value="">-- Pilih Produk --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= e($p['id']) ?>" <?= $productId == $p['id'] ? 'selected' : '' ?>>
                        <?= e($p['name']) ?> (SKU: <?= e($p['sku']) ?> | Stok: <?= e($p['stock']) ?> pcs)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="quantity">Kuantitas / Jumlah Unit *</label>
            <input type="number" id="quantity" name="quantity" min="1" required placeholder="Contoh: 5" value="<?= e($quantity) ?>">
            <small>Kuantitas harus bilangan bulat positif lebih besar dari nol.</small>
        </div>

        <div class="form-group">
            <label for="notes">Catatan Transaksi / Keterangan</label>
            <textarea id="notes" name="notes" rows="3" placeholder="Contoh: Suplai batch PO #102, atau Penjualan invoice #551"><?= e($notes) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
            Simpan & Eksekusi Mutasi Stok
        </button>
    </form>
</div>

<?php
include __DIR__ . '/../views/footer.php';
?>
