<?php
require_once 'includes/koneksi.php';
require_once 'includes/session.php';
require_once 'includes/fungsi.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'donor/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cekCsrf();

    $email = strtolower(post('email'));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Email dan password wajib diisi dengan benar.';
    } else {
        $user = dbOne($conn, 'SELECT * FROM users WHERE email = ? LIMIT 1', 's', [$email]);

        $password_ok = false;
        $password_lama = false;

        if ($user) {
            $password_ok = password_verify($password, $user['password']);
            $password_lama = strlen($user['password']) === 32 && md5($password) === $user['password'];
        }

        if ($user && ($password_ok || $password_lama)) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['gol']     = ($user['golongan_darah'] ?? '') . ($user['rhesus'] ?? '');

            if ($password_lama) {
                $hash_baru = password_hash($password, PASSWORD_DEFAULT);
                dbExec($conn, 'UPDATE users SET password = ? WHERE id = ?', 'si', [$hash_baru, $user['id']]);
            }

            header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'donor/dashboard.php'));
            exit;
        }

        $error = 'Email atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - PMI Sleman</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body class="auth-page">
    <nav class="navbar bg-white border-bottom px-3 px-md-4">
        <a href="index.php" class="navbar-brand fw-bold text-pmi">PMI Sleman</a>
        <a href="index.php" class="small text-decoration-none text-secondary">
            <i class="bi bi-arrow-left"></i> Beranda
        </a>
    </nav>

    <main class="auth-wrapper">
        <section class="auth-card card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="auth-icon mx-auto mb-3">
                        <i class="bi bi-droplet-fill"></i>
                    </div>
                    <h1 class="h4 mb-1">Masuk ke Akun</h1>
                    <p class="text-muted small mb-0">Sistem donor darah PMI Kabupaten Sleman</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="d-grid gap-3">
                    <?= csrfInput() ?>

                    <div>
                        <label class="form-label fw-semibold small">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@email.com" required autofocus>
                    </div>

                    <div>
                        <label class="form-label fw-semibold small">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>

                    <button type="submit" class="btn btn-pmi rounded-pill fw-bold mt-2">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk
                    </button>
                </form>

                <p class="text-center text-muted small mt-4 mb-0">
                    Belum punya akun?
                    <a href="register.php" class="text-pmi fw-bold text-decoration-none">Daftar di sini</a>
                </p>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>