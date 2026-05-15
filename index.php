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

    <!-- navbar -->
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

    <!-- statistik -->
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

    <!-- stok darah real-time -->
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

                    // rumus progress: stok sekarang / target tampilan x 100%
                    // target tampilan dibuat 4x batas kritis supaya bar tidak cepat penuh
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

    <!-- manfaat donor -->
    <section class="manfaat-section py-5" id="manfaat">
        <div class="container">
            <div class="text-center mb-4">
                <span class="section-label" style="color:#ff9a8b">Mengapa donor?</span>
                <h2 class="section-title-lg" style="color:#fff">Manfaat Donor Darah</h2>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="manfaat-card">
                        <div class="icon-circle"><i class="bi bi-activity"></i></div>
                        <h6>Kesehatan Jantung</h6>
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check2 text-pmi me-2"></i>Menurunkan zat besi berlebih</li>
                            <li><i class="bi bi-check2 text-pmi me-2"></i>Mengurangi risiko penyakit jantung</li>
                            <li><i class="bi bi-check2 text-pmi me-2"></i>Memperlancar sirkulasi darah</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="manfaat-card">
                        <div class="icon-circle"><i class="bi bi-clipboard2-pulse-fill"></i></div>
                        <h6>Cek Kesehatan Gratis</h6>
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check2 text-pmi me-2"></i>Cek HB & tekanan darah gratis</li>
                            <li><i class="bi bi-check2 text-pmi me-2"></i>Deteksi dini penyakit menular</li>
                            <li><i class="bi bi-check2 text-pmi me-2"></i>Pemantauan kesehatan rutin</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="manfaat-card">
                        <div class="icon-circle"><i class="bi bi-people-fill"></i></div>
                        <h6>Sosial & Psikologis</h6>
                        <ul class="list-unstyled mb-0">
                            <li><i class="bi bi-check2 text-pmi me-2"></i>Menyelamatkan hingga 3 nyawa</li>
                            <li><i class="bi bi-check2 text-pmi me-2"></i>Meningkatkan rasa empati</li>
                            <li><i class="bi bi-check2 text-pmi me-2"></i>Mendapat snack & sertifikat</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- syarat donor -->
    <section class="py-5" id="syarat">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-md-5">
                    <span class="section-label">Sebelum donor</span>
                    <h2 class="section-title-lg mb-3">Syarat Donor Darah</h2>
                    <p class="text-muted">Pastikan Anda memenuhi syarat berikut sebelum datang ke PMI.</p>
                    <a href="register.php" class="btn btn-pmi rounded-pill px-4 mt-2">
                        <i class="bi bi-person-plus-fill"></i> Daftar Sekarang
                    </a>
                </div>
                <div class="col-md-7">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="card border p-3 h-100">
                                <h6 class="fw-bold small mb-3 text-pmi"><i class="bi bi-person-check-fill"></i> Syarat Umum</h6>
                                <?php foreach (['Usia 17–60 tahun', 'Berat badan min. 45 kg', 'Tekanan darah 100–160/70–100', 'Kadar HB 12,5–17 g/dL', 'Suhu tubuh 36,6–37,5 °C'] as $s): ?>
                                    <div class="syarat-item-row">
                                        <div class="syarat-bullet"><i class="bi bi-check2"></i></div><?= $s ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card border p-3 h-100">
                                <h6 class="fw-bold small mb-3 text-pmi"><i class="bi bi-calendar-check-fill"></i> Ketentuan Waktu</h6>
                                <?php foreach (['Interval min. 3 bulan', 'Tidur cukup min. 5 jam', 'Tidak konsumsi antibiotik', 'Tidak hamil/menyusui', 'Sehat, tidak demam'] as $s): ?>
                                    <div class="syarat-item-row">
                                        <div class="syarat-bullet"><i class="bi bi-check2"></i></div><?= $s ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- langkah-langkah donor -->
    <section class="py-5" id="langkah" style="background:#F8F7F5">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-label">Prosedur</span>
                <h2 class="section-title-lg">Langkah-Langkah Donor Darah</h2>
                <p class="text-muted small">Hanya butuh sekitar 30–45 menit</p>
            </div>
            <div class="row g-4">
                <?php foreach ($langkah as $i => $l): ?>
                    <div class="col-md-4">
                        <div class="h-100 p-4 rounded-4 border bg-white" style="position:relative">
                            <!-- nomor langkah -->
                            <div class="langkah-no"><?= $i + 1 ?></div>
                            <div class="langkah-icon mb-3">
                                <i class="bi <?= $l['icon'] ?> text-pmi"></i>
                            </div>
                            <h6 class="fw-bold mb-1" style="font-family:var(--font-head)"><?= $l['judul'] ?></h6>
                            <p class="text-muted small mb-0"><?= $l['isi'] ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-5 p-4 rounded-4 d-flex align-items-center justify-content-between flex-wrap gap-3" style="background:var(--dark)">
                <div>
                    <div class="fw-bold text-white">Siap memulai?</div>
                    <small style="color:rgba(255,255,255,.45)">Pendaftaran hanya butuh beberapa menit.</small>
                </div>
                <a href="register.php" class="btn btn-pmi rounded-pill px-4 fw-bold">
                    <i class="bi bi-person-plus-fill"></i> Daftar Sekarang
                </a>
            </div>
        </div>
    </section>

    <!-- lokasi dengan peta embed -->
    <section class="py-5" id="lokasi">
        <div class="container">
            <div class="text-center mb-4">
                <span class="section-label">Temukan kami</span>
                <h2 class="section-title-lg">Lokasi PMI Kabupaten Sleman</h2>
            </div>
            <div class="row g-4 align-items-start">
                <div class="col-md-7">
                    <div class="map-embed-wrap">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3952.1234!2d110.35!3d-7.75!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e7a5a5e5a5e5a5e%3A0x5a5e5a5e5a5e5a5e!2sPMI%20Kabupaten%20Sleman!5e0!3m2!1sid!2sid!4v1234567890" allowfullscreen loading="lazy"></iframe>
                    </div>
                    <a href="https://maps.google.com/?q=PMI+Kabupaten+Sleman" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill mt-2">
                        <i class="bi bi-box-arrow-up-right"></i> Buka Google Maps
                    </a>
                </div>
                <div class="col-md-5 d-flex flex-column gap-2">
                    <div class="lokasi-info-card">
                        <div class="lokasi-icon-box"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <div class="fw-semibold small mb-1">Alamat</div>
                            <div class="text-muted small">Jl. Magelang No.6, Mlati, Sleman, DIY 55284</div>
                        </div>
                    </div>
                    <div class="lokasi-info-card">
                        <div class="lokasi-icon-box"><i class="bi bi-clock-fill"></i></div>
                        <div>
                            <div class="fw-semibold small mb-1">Jam Operasional</div>
                            <div class="text-muted small">Sen–Jum: 08.00–15.00 · Sab: 08.00–12.00 · Min: Tutup</div>
                        </div>
                    </div>
                    <div class="lokasi-info-card">
                        <div class="lokasi-icon-box"><i class="bi bi-telephone-fill"></i></div>
                        <div>
                            <div class="fw-semibold small mb-1">Telepon & WhatsApp</div>
                            <div class="text-muted small">(0274) 869909 · WA: 0838-4622-6162</div>
                        </div>
                    </div>
                    <div class="lokasi-info-card flex-column align-items-start">
                        <div class="fw-semibold small mb-2">Ikuti PMI Sleman</div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="https://wa.me/6283846226162" target="_blank" class="sosmed-icon wa"><i class="bi bi-whatsapp"></i></a>
                            <a href="https://www.instagram.com/pmikabsleman?igsh=aXIzNHI5bjlnandk" class="sosmed-icon ig"><i class="bi bi-instagram"></i></a>
                            <a href="https://www.facebook.com/posko.pmisleman.3" class="sosmed-icon fb"><i class="bi bi-facebook"></i></a>
                            <a href="https://www.youtube.com/@pmikabsleman" class="sosmed-icon yt"><i class="bi bi-youtube"></i></a>
                            <a href="https://x.com/pmi_sleman" class="sosmed-icon tw"><i class="bi bi-twitter-x"></i></a>
                            <a href="mailto:pmi.sleman@pmi.or.id" class="sosmed-icon em"><i class="bi bi-envelope-fill"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA daftar -->
    <section class="cta-section text-center">
        <div class="container">
            <h2 style="color:#fff;font-size:2rem;margin-bottom:8px">Siap Menjadi Pendonor?</h2>
            <p style="color:rgba(255,255,255,.75);margin-bottom:28px">Daftarkan diri sekarang di PMI Kabupaten Sleman</p>
            <a href="register.php" class="btn btn-light fw-bold rounded-pill px-5 me-2">
                <i class="bi bi-person-plus-fill text-danger"></i> Daftar Sekarang
            </a>
            <a href="tel:02748682811" class="btn btn-outline-light rounded-pill px-4">
                <i class="bi bi-telephone-fill"></i> Hubungi Kami
            </a>
        </div>
    </section>

    <!-- footer -->
    <footer class="site-footer py-4">
        <div class="container d-flex justify-content-center align-items-center flex-wrap gap-2">
            <small style="color:rgba(255,255,255,.3)">© <?= date('Y') ?> PMI Kabupaten Sleman</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>