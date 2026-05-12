<?php
require_once 'includes/koneksi.php';
require_once 'includes/fungsi.php';
require_once 'includes/session.php';

$stok = dbAll($conn, 'SELECT * FROM stok_darah ORDER BY golongan_darah, rhesus DESC');
$total_donor = (int) (dbOne($conn, "SELECT COUNT(*) n FROM users WHERE role = 'donor'")['n'] ?? 0);
$donor_thn = (int) (dbOne($conn, 'SELECT COUNT(*) n FROM riwayat_donor WHERE YEAR(tanggal) = YEAR(NOW())')['n'] ?? 0);
$total_rw = (int) (dbOne($conn, 'SELECT COUNT(*) n FROM riwayat_donor')['n'] ?? 0);

$langkah = [
    ['icon' => 'bi-person-plus-fill',      'judul' => 'Daftar & Isi Kuesioner',  'isi' => 'Buat akun lalu isi kuesioner kesehatan. Sistem menentukan kelayakan awal.'],
    ['icon' => 'bi-calendar-check-fill',   'judul' => 'Pilih Jadwal Donor',       'isi' => 'Pilih tanggal dan sesi yang tersedia, lalu tunggu konfirmasi petugas.'],
    ['icon' => 'bi-clipboard2-pulse-fill', 'judul' => 'Skrining Fisik',           'isi' => 'Petugas memeriksa tekanan darah, HB, berat badan, dan suhu tubuh.'],
    ['icon' => 'bi-droplet-fill',          'judul' => 'Pengambilan Darah',        'isi' => 'Dilakukan tenaga medis, hanya 8–10 menit. Proses aman dan steril.'],
    ['icon' => 'bi-cup-hot-fill',          'judul' => 'Istirahat & Snack',        'isi' => 'Istirahat 10–15 menit dan mendapat snack dari petugas PMI.'],
    ['icon' => 'bi-patch-check-fill',      'judul' => 'Riwayat Tercatat',         'isi' => 'Donasi tercatat otomatis. Cek riwayat kapan saja lewat akun Anda.'],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PMI Kabupaten Sleman — Donor Darah</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg home-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/img/logo-navbar.png" height="36" alt="PMI Sleman">
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto align-items-center gap-1">
                    <li class="nav-item"><a class="nav-link" href="#stok">Stok Darah</a></li>
                    <li class="nav-item"><a class="nav-link" href="#manfaat">Manfaat</a></li>
                    <li class="nav-item"><a class="nav-link" href="#syarat">Syarat</a></li>
                    <li class="nav-item"><a class="nav-link" href="#langkah">Langkah</a></li>
                    <li class="nav-item"><a class="nav-link" href="#lokasi">Lokasi</a></li>
                    <li class="nav-item ms-2">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a class="btn btn-pmi btn-sm rounded-pill px-3" href="<?= ($_SESSION['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'donor/dashboard.php' ?>">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        <?php else: ?>
                            <a class="btn btn-pmi btn-sm rounded-pill px-3" href="login.php">
                                <i class="bi bi-person-fill"></i> Masuk
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero dengan foto latar -->
    <section class="hero-section">
        <img src="assets/img/home-page.png" class="hero-bg" alt="Donor PMI">
        <div class="hero-overlay"></div>
        <div class="container h-100">
            <div class="hero-content">
                <span class="hero-eyebrow">PMI Kabupaten Sleman</span>
                <h1 class="hero-title">Setetes Darahmu<br><span>Selamatkan Nyawa</span></h1>
                <p class="hero-sub">Daftarkan diri sebagai pendonor sukarela dan jadilah pahlawan bagi yang membutuhkan.</p>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="register.php" class="btn btn-pmi rounded-pill px-4 fw-bold">
                        <i class="bi bi-heart-fill"></i> Daftar Donor
                    </a>
                    <a href="#stok" class="btn btn-outline-light rounded-pill px-4">
                        <i class="bi bi-droplet-half"></i> Cek Stok
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="stats-bar">
        <div class="container">
            <div class="row text-center py-3">
                <div class="col border-end border-white border-opacity-10">
                    <div class="stat-num"><?= number_format($total_donor) ?></div>
                    <div class="stat-label">Pendonor terdaftar</div>
                </div>
                <div class="col border-end border-white border-opacity-10">
                    <div class="stat-num"><?= number_format($donor_thn) ?></div>
                    <div class="stat-label">Donor tahun ini</div>
                </div>
                <div class="col border-end border-white border-opacity-10">
                    <div class="stat-num"><?= number_format($total_rw) ?></div>
                    <div class="stat-label">Total donasi</div>
                </div>
                <div class="col">
                    <div class="stat-num">±<?= number_format($total_rw * 3) ?></div>
                    <div class="stat-label">Nyawa terbantu</div>
                </div>
            </div>
        </div>
    </div>

    <section class="stok-section py-5" id="stok">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-4">
                <div>
                    <span class="section-label">Real-time</span>
                    <h2 class="section-title-lg mb-1">Stok Darah Saat Ini</h2>
                    <p class="text-muted small mb-0">Diperbarui berkala oleh petugas PMI Sleman.</p>
                </div>
                <a href="login.php" class="btn btn-outline-pmi btn-sm rounded-pill px-3">
                    <i class="bi bi-calendar-plus"></i> Daftar donor
                </a>
            </div>

<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3">
                <?php foreach ($stok as $s): ?>
                    <?php
                    $st = statusStok((int) $s['jumlah_kantong'], (int) $s['batas_kritis']);

                    // Rumus progress: stok sekarang / target tampilan x 100%.
                    // Target tampilan dibuat 4x batas kritis supaya bar tidak cepat penuh.
                    $percent = min(100, (int) round($s['jumlah_kantong'] / max(1, $s['batas_kritis'] * 4) * 100));
                    ?>
                    <div class="col">
                        <div class="stok-card stok-home-card h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="stok-gol"><?= e($s['golongan_darah'] . $s['rhesus']) ?></div>
                                    <small class="text-muted">Golongan darah</small>
                                </div>
                                <span class="badge rounded-pill text-bg-<?= e($st['kelas']) ?>">
                                    <i class="bi <?= e($st['icon']) ?>"></i> <?= e($st['label']) ?>
                                </span>
                            </div>

                            <div class="d-flex align-items-end gap-2 mb-2">
                                <div class="stok-num text-<?= e($st['kelas']) ?>"><?= e($s['jumlah_kantong']) ?></div>
                                <span class="text-muted mb-2">kantong</span>
                            </div>

                            <div class="stok-progress mb-2">
                                <div class="stok-progress-bar bg-<?= e($st['kelas']) ?>" style="width: <?= e($percent) ?>%"></div>
                            </div>

                            <div class="d-flex justify-content-between small text-muted">
                                <span>Stok tersedia</span>
                                <span>Batas <?= e($s['batas_kritis']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
