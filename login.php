<?php
define('APP_INIT', true);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/escape.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Silakan isi username dan password.';
    } else {
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, 
                   COALESCE(u.password, u.password_hash) AS pwd_hash, 
                   u.role, 
                   COALESCE(p.full_name, u.name, u.username) AS display_name
            FROM users u
            LEFT JOIN user_profiles p ON p.user_id = u.id
            WHERE u.username = :username
            LIMIT 1
        ");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['pwd_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'full_name' => $user['display_name']
            ];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RetroCam IMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
    <div class="form-container" style="width: 100%; max-width: 420px;">
        <div style="text-align: center; margin-bottom: 25px;">
            <div style="font-size: 2.5rem; margin-bottom: 10px;">📦</div>
            <h1>RetroCam IMS</h1>
            <p class="subtitle">Inventory Management System</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username" placeholder="admin atau staff" value="<?= e($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Masukkan password">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Masuk ke Sistem</button>
        </form>

        <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #334155; font-size: 0.85rem; color: #94a3b8;">
            <p><strong>Akun Demo Bawaan:</strong></p>
            <ul style="padding-left: 20px; margin-top: 5px;">
                <li>Admin: <code>admin</code> / <code>password123</code></li>
                <li>Staff: <code>staff</code> / <code>password123</code></li>
            </ul>
        </div>
    </div>
</body>
</html>
