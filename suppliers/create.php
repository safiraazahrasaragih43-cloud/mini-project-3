<?php
define('APP_INIT', true);
$basePath = '../';
$currentPage = 'suppliers';
$pageTitle = 'Tambah Supplier';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';
require_once __DIR__ . '/../helpers/validation.php';

require_role('admin', $basePath);

$errors = [];
$name = '';
$contactPerson = '';
$phone = '';
$address = '';

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
            $stmt = $pdo->prepare("
                INSERT INTO suppliers (name, contact_person, phone, address)
                VALUES (:name, :contact_person, :phone, :address)
            ");
            $stmt->execute([
                'name' => $name,
                'contact_person' => $contactPerson,
                'phone' => $phone,
                'address' => $address
            ]);

            $_SESSION['flash_success'] = "Supplier '{$name}' berhasil ditambahkan!";
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
    <a href="index.php" class="btn btn-secondary">&larr; Kembali ke Master Supplier</a>
</div>

<div class="form-container">
    <h2>Tambah Supplier Baru</h2>
    <p class="subtitle" style="margin-bottom: 20px;">Daftarkan rekanan distributor / vendor baru.</p>

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
            <label for="name">Nama Vendor / Perusahaan Supplier *</label>
            <input type="text" id="name" name="name" required placeholder="Contoh: CV Prima Optik Sentosa" value="<?= e($name) ?>">
        </div>

        <div class="form-group">
            <label for="contact_person">Kontak Person (PIC)</label>
            <input type="text" id="contact_person" name="contact_person" placeholder="Contoh: Rian Gunawan" value="<?= e($contactPerson) ?>">
        </div>

        <div class="form-group">
            <label for="phone">Nomor Telepon / WhatsApp</label>
            <input type="text" id="phone" name="phone" placeholder="Contoh: 081298765432" value="<?= e($phone) ?>">
        </div>

        <div class="form-group">
            <label for="address">Alamat Kantor / Gudang</label>
            <textarea id="address" name="address" rows="3" placeholder="Alamat lengkap vendor..."><?= e($address) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Simpan Supplier</button>
    </form>
</div>

<?php
include __DIR__ . '/../views/footer.php';
?>
