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
