<?php
define('APP_INIT', true);
$basePath = '../';
$currentPage = 'categories';
$pageTitle = 'Edit Kategori';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';
require_once __DIR__ . '/../helpers/validation.php';

require_role('admin', $basePath);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['flash_error'] = "ID Kategori tidak valid.";
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
$stmt->execute(['id' => $id]);
$category = $stmt->fetch();

if (!$category) {
    $_SESSION['flash_error'] = "Kategori tidak ditemukan.";
    header("Location: index.php");
    exit();
}

$errors = [];
$name = $category['name'];
$description = $category['description'];

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
        $check = $pdo->prepare("SELECT id FROM categories WHERE name = :name AND id != :id LIMIT 1");
        $check->execute(['name' => $name, 'id' => $id]);
        if ($check->fetch()) {
            $errors[] = "Kategori dengan nama '{$name}' sudah digunakan.";
        }
    }

    if (empty($errors)) {
        try {
            $updateStmt = $pdo->prepare("UPDATE categories SET name = :name, description = :description WHERE id = :id");
            $updateStmt->execute([
                'name' => $name,
                'description' => $description,
                'id' => $id
            ]);

            $_SESSION['flash_success'] = "Kategori berhasil diperbarui!";
            header("Location: index.php");
            exit();
        } catch (Throwable $e) {
            $errors[] = "Gagal memperbarui: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../views/header.php';
?>

<div style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-secondary">&larr; Kembali ke Master Kategori</a>
</div>

<div class="form-container">
    <h2>Edit Data Kategori</h2>
    <p class="subtitle" style="margin-bottom: 20px;">Ubah rincian kategori produk.</p>

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
            <label for="name">Nama Kategori *</label>
            <input type="text" id="name" name="name" required value="<?= e($name) ?>">
        </div>

        <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="3"><?= e($description) ?></textarea>
        </div>

        <button type="submit" class="btn btn-warning" style="width: 100%; margin-top: 10px;">Simpan Perubahan</button>
    </form>
</div>

<?php
include __DIR__ . '/../views/footer.php';
?>
