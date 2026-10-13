<?php
define('APP_INIT', true);
$basePath = '../';
$currentPage = 'categories';
$pageTitle = 'Tambah Kategori';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';
require_once __DIR__ . '/../helpers/validation.php';

require_role('admin', $basePath);

$errors = [];
$name = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF Token validation failed.");
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($err = validate_required($name, 'Nama Kategori')) {
        $errors[] = $err;
    }

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM categories WHERE name = :name LIMIT 1");
        $check->execute(['name' => $name]);
        if ($check->fetch()) {
            $errors[] = "Kategori dengan nama '{$name}' sudah ada.";
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
            $stmt->execute([
                'name' => $name,
                'description' => $description
            ]);

            $_SESSION['flash_success'] = "Kategori '{$name}' berhasil ditambahkan!";
            header("Location: index.php");
            exit();
        } catch (Throwable $e) {
            $errors[] = "Gagal menyimpan: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../views/header.php';
?>

<div style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-secondary">&larr; Kembali ke Master Kategori</a>
</div>

<div class="form-container">
    <h2>Tambah Kategori Baru</h2>
    <p class="subtitle" style="margin-bottom: 20px;">Tambahkan grup kategori produk baru.</p>

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
            <label for="name">Nama Kategori *</label>
            <input type="text" id="name" name="name" required placeholder="Contoh: Kamera Medium Format" value="<?= e($name) ?>">
        </div>

        <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="3" placeholder="Deskripsi jenis produk dalam kategori ini"><?= e($description) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Simpan Kategori</button>
    </form>
</div>

<?php
include __DIR__ . '/../views/footer.php';
?>
