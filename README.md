# RetroCam IMS - Mini Project 3: Inventory Management System

Aplikasi Web Inventory Management System untuk Toko Kamera Retro & Digicam berbasis **Relational Database + Advanced PHP** (Pertemuan 4). Dibangun dengan mengikuti standar arsitektur dan UI konsisten dari referensi `project-manager2`.

---

## 🚀 Panduan Cara Menjalankan

### 1. Import Database:
1. Pastikan Apache & MySQL di XAMPP / Laragon sudah menyala.
2. Buka phpMyAdmin (`http://localhost/phpmyadmin`).
3. Buat database baru dengan nama `inventory_db` (atau langsung import file SQL karena query sudah mencakup `CREATE DATABASE IF NOT EXISTS inventory_db`).
4. Import file `database/inventory_db.sql` ke dalam MySQL.

### 2. Jalankan Project:
1. Pindahkan folder `mini-project-3` ke direktori server web Anda (misalnya `htdocs/mini-project-3` di XAMPP).
2. Buka browser dan akses:
   ```
   http://localhost/mini-project-3/
   ```
3. Login menggunakan salah satu akun bawaan sistem berikut:

| Role | Username | Password | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `password123` | Akses penuh (Kategori, Produk, Supplier, Mutasi Stok, Dashboard) |
| **Staff** | `staff` | `password123` | Terbatas (Katalog Produk & Mencatat Mutasi Stok IN/OUT) |

---

## 📋 Fitur Utama & Kepatuhan Modul (Slide 1–38)

1. **Relasi Database Lengkap:**
   - **One-to-One:** `users` &harr; `user_profiles` (`user_id` Foreign Key + UNIQUE).
   - **One-to-Many:** `categories` &rarr; `products` (`category_id` FK).
   - **Many-to-Many:** `products` &harr; `suppliers` via Junction Table `product_suppliers` dengan Composite Primary Key `(product_id, supplier_id)`.
   - **Transaction Log:** `products` &harr; `stock_movements` mencatat histori keluar/masuk barang.

2. **Business Rules & Transaction Integrity:**
   - Formula stok: `Stok Akhir = Stok Awal + Total Masuk - Total Keluar`.
   - Kode SKU unik divalidasi di server (`SELECT ... WHERE sku = :sku`).
   - Validasi semantik harga & kuantitas tidak boleh negatif.
   - **Barang Keluar (OUT):** divalidasi agar kuantitas keluar tidak melebihi stok yang tersedia.
   - **Database Transaction (`beginTransaction`, `commit`, `rollBack`):** Menggunakan `SELECT ... FOR UPDATE` untuk mencegah *race condition* dan menjamin perubahan stok serta log `stock_movements` tersimpan secara atomik.
   - **Referential Integrity:** Produk yang sudah memiliki rekaman mutasi stok dilarang dihapus sembarangan guna menjaga integritas laporan audit.

3. **Pencarian, Filter, Sorting & Pagination:**
   - Pencarian berdasarkan Nama Produk atau SKU (`LIKE %keyword%`).
   - Filter dropdown berdasarkan Kategori.
   - Sorting aman berbasis *Allowlist* (`name`, `sku`, `price`, `stock`, `created_at`) dengan pilihan ASC/DESC.
   - Pagination (LIMIT & OFFSET) yang mempertahankan parameter query pencarian dan filter saat berpindah halaman.

4. **Dashboard & Aggregate Reports:**
   - Total produk (`COUNT(products.id)`).
   - Estimasi nilai inventory (`SUM(stock * price)`).
   - Widget peringatan stok rendah (`stock <= minimum_stock`).
   - Total akumulasi barang masuk (`SUM(quantity) WHERE type='IN'`) dan keluar (`SUM(quantity) WHERE type='OUT'`).
   - Laporan per kategori menggunakan `GROUP BY` dan fungsi agregat (`COUNT`, `SUM`, `AVG`).

---

## 🔒 Refleksi Keamanan Aplikasi

| Vektor Ancaman | Mitigasi / Kontrol Keamanan yang Diterapkan |
| :--- | :--- |
| **SQL Injection** | Menggunakan PDO Prepared Statements (`$pdo->prepare()`) dengan binding parameter untuk seluruh query (INSERT, SELECT, UPDATE, DELETE). Mematikan PDO emulation (`PDO::ATTR_EMULATE_PREPARES => false`). Validasi kolom sorting menggunakan tabel *Allowlist*. |
| **Cross-Site Scripting (XSS)** | Seluruh data yang di-render ke HTML diproses melalui fungsi helper `e()` / `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`. |
| **Broken Access Control & Session Fixation** | Memeriksa otentikasi via `require_login()` dan otorisasi level peran via `require_role('admin')`. Pemanggilan `session_regenerate_id(true)` setelah login berhasil untuk mencegah *Session Fixation*. Halaman terlarang merespons HTTP 403 Forbidden. |
| **Cross-Site Request Forgery (CSRF)** | Operasi pengubahan data (POST / DELETE / MUTASI) diwajibkan menyertakan token acak `csrf_token` yang divalidasi menggunakan `hash_equals()`. |
| **Insecure Password Storage** | Password disimpan menggunakan fungsi hashing modern PHP `password_hash($pass, PASSWORD_BCRYPT)` dan diverifikasi dengan `password_verify()`. |
