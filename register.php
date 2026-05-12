<?php
require_once 'includes/koneksi.php';
require_once 'includes/session.php';
require_once 'includes/fungsi.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'donor/dashboard.php'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cekCsrf();

    $nama   = post('nama');
    $email  = strtolower(post('email'));
    $pass   = $_POST['password'] ?? '';
    $telp   = post('no_telepon');
    $alamat = post('alamat');
    $tgl    = post('tanggal_lahir');
    $jk     = post('jenis_kelamin');
    $bb     = (int) ($_POST['berat_badan'] ?? 0);
    $gol    = post('golongan_darah');
    $rh     = post('rhesus');
    $umur   = hitungUmur($tgl);

    if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Nama dan email wajib diisi dengan benar.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif (!in_array($jk, ['L', 'P'], true)) {
        $error = 'Jenis kelamin tidak valid.';
    } elseif (!in_array($gol, $golongan_darah, true) || !in_array($rh, ['+', '-'], true)) {
        $error = 'Golongan darah atau rhesus tidak valid.';
    } elseif ($umur < 17 || $umur > 60) {
        $error = "Usia Anda ($umur tahun) belum memenuhi syarat donor 17-60 tahun.";
    } elseif ($bb < 45) {
        $error = "Berat badan ($bb kg) kurang dari syarat minimum 45 kg.";
    } else {
        $cek = dbOne($conn, 'SELECT id FROM users WHERE email = ? LIMIT 1', 's', [$email]);

        if ($cek) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);

            dbExec(
                $conn,
                'INSERT INTO users
                    (nama, email, password, role, jenis_kelamin, tanggal_lahir, berat_badan, golongan_darah, rhesus, no_telepon, alamat)
                 VALUES
                    (?, ?, ?, "donor", ?, ?, ?, ?, ?, ?, ?)',
                'sssssissss',
                [$nama, $email, $hash, $jk, $tgl, $bb, $gol, $rh, $telp, $alamat]
            );

            $success = 'Pendaftaran berhasil. Silakan login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar - PMI Sleman</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/register.css">
</head>

<body class="bg-light">
    <nav class="navbar bg-white border-bottom px-3 px-md-4">
        <a href="index.php" class="navbar-brand fw-bold text-pmi">PMI Sleman</a>
        <a href="index.php" class="small text-decoration-none text-secondary">
            <i class="bi bi-arrow-left"></i> Beranda
        </a>
    </nav>

    <main class="container register-wrapper py-4 py-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="mb-4 pb-3 border-bottom">
                    <h1 class="h4 mb-1">Daftar sebagai Pendonor</h1>
                    <p class="text-muted small mb-0">Isi data sesuai identitas dan kondisi kesehatan Anda.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success py-2 small">
                        <i class="bi bi-check-circle-fill"></i> <?= e($success) ?>
                        <a href="login.php" class="fw-bold ms-2">Login sekarang</a>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfInput() ?>

                    <h2 class="form-section-title">Data Akun</h2>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nama lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">No. telepon</label>
                            <input type="text" name="no_telepon" class="form-control" placeholder="08xx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" required>
                            <small class="text-muted">Minimal 6 karakter.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Alamat / Kecamatan</label>
                            <input type="text" name="alamat" class="form-control" placeholder="Kecamatan, Sleman">
                        </div>
                    </div>

                    <h2 class="form-section-title">Data Medis</h2>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tanggal lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Jenis kelamin</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="">Pilih</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Berat badan (kg)</label>
                            <input type="number" name="berat_badan" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Golongan darah</label>
                            <select name="golongan_darah" class="form-select" required>
                                <option value="">Pilih</option>
                                <?php foreach ($golongan_darah as $g): ?>
                                    <option value="<?= e($g) ?>"><?= e($g) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Rhesus</label>
                            <select name="rhesus" class="form-select" required>
                                <option value="">Pilih</option>
                                <option value="+">Positif (+)</option>
                                <option value="-">Negatif (-)</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-pmi w-100 rounded-pill fw-bold">
                        <i class="bi bi-person-plus-fill"></i> Daftar Sekarang
                    </button>
                </form>

                <p class="text-center text-muted small mt-3 mb-0">
                    Sudah punya akun?
                    <a href="login.php" class="text-pmi fw-bold text-decoration-none">Masuk di sini</a>
                </p>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>