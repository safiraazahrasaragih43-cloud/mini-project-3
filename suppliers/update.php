<?php
define('APP_INIT', true);
$basePath = '../';
$currentPage = 'suppliers';
$pageTitle = 'Edit Supplier';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';
require_once __DIR__ . '/../helpers/validation.php';

require_role('admin', $basePath);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['flash_error'] = "ID Supplier tidak valid.";
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = :id");
$stmt->execute(['id' => $id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    $_SESSION['flash_error'] = "Supplier tidak ditemukan.";
    header("Location: index.php");
    exit();
}

$errors = [];
$name = $supplier['name'];
$contactPerson = $supplier['contact_person'];
$phone = $supplier['phone'];
$address = $supplier['address'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF Token validation failed.");
    }

    $name = trim($_POST['name'] ?? '');
    $contactPerson = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($err = validate_required($name, 'Nama Supplier')) {
        $errors[] = $err;
    }

    if (empty($errors)) {
        try {
            $update = $pdo->prepare("
                UPDATE suppliers 
                SET name = :name,
                    contact_person = :contact_person,
                    phone = :phone,
                    address = :address
                WHERE id = :id
            ");
            $update->execute([
                'name' => $name,
                'contact_person' => $contactPerson,
                'phone' => $phone,
                'address' => $address,
                'id' => $id
            ]);

            $_SESSION['flash_success'] = "Data supplier '{$name}' berhasil diperbarui!";
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
    <a href="index.php" class="btn btn-secondary">&larr; Kembali ke Master Supplier</a>
</div>

<div class="form-container">
    <h2>Edit Data Supplier</h2>
    <p class="subtitle" style="margin-bottom: 20px;">Perbarui profil dan kontak supplier.</p>

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
            <label for="name">Nama Vendor / Perusahaan Supplier *</label>
            <input type="text" id="name" name="name" required value="<?= e($name) ?>">
        </div>

        <div class="form-group">
            <label for="contact_person">Kontak Person (PIC)</label>
            <input type="text" id="contact_person" name="contact_person" value="<?= e($contactPerson) ?>">
        </div>

        <div class="form-group">
            <label for="phone">Nomor Telepon / WhatsApp</label>
            <input type="text" id="phone" name="phone" value="<?= e($phone) ?>">
        </div>

        <div class="form-group">
            <label for="address">Alamat Kantor / Gudang</label>
            <textarea id="address" name="address" rows="3"><?= e($address) ?></textarea>
        </div>

        <button type="submit" class="btn btn-warning" style="width: 100%; margin-top: 10px;">Simpan Perubahan</button>
    </form>
</div>

<?php
include __DIR__ . '/../views/footer.php';
?>
