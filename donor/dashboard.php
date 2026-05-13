<?php
require_once '../includes/koneksi.php';
require_once '../includes/session.php';
require_once '../includes/fungsi.php';

cekDonor();

$uid = (int) $_SESSION['user_id'];
$user = dbOne($conn, 'SELECT * FROM users WHERE id = ? LIMIT 1', 'i', [$uid]);
$total = (int) (dbOne($conn, 'SELECT COUNT(*) n FROM riwayat_donor WHERE user_id = ?', 'i', [$uid])['n'] ?? 0);
$pending = (int) (dbOne($conn, "SELECT COUNT(*) n FROM jadwal_donor WHERE user_id = ? AND status = 'menunggu'", 'i', [$uid])['n'] ?? 0);
$jadwal_aktif = jadwalAktif($conn, $uid);
$punya_jadwal_aktif = (bool) $jadwal_aktif;
$boleh_interval = cekInterval($conn, $uid);
$hari = sisaHariDonor($conn, $uid);
$kuesioner_berlaku = kuesionerMasihBerlaku($uid);
$kuesioner_akhir = waktuKuesionerBerakhir($uid);
$bisa_mulai = $boleh_interval && !$punya_jadwal_aktif;

$jadwal = dbSelect(
    $conn,
    "SELECT j.*,
            CASE WHEN r.id IS NOT NULL THEN 'selesai' ELSE j.status END AS status_tampil
     FROM jadwal_donor j
     LEFT JOIN riwayat_donor r ON r.jadwal_id = j.id
     WHERE j.user_id = ?
     ORDER BY j.created_at DESC
     LIMIT 5",
    'i',
    [$uid]
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - PMI Sleman</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/donor.css">
    <link rel="stylesheet" href="../assets/css/donor/dashboard.css">
</head>

<body class="donor-page">
    <?php include '../includes/navbar_donor.php'; ?>

    <main class="container-lg py-4">
        <section class="donor-hero mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                <div class="avatar-circle"><?= e(inisial($user['nama'] ?? 'D')) ?></div>
                <div class="flex-grow-1">
                    <h1 class="h4 mb-1 text-white">Halo, <?= e($user['nama'] ?? 'Pendonor') ?></h1>
                    <p class="text-white-50 small mb-0">
                        Golongan darah <?= e(($user['golongan_darah'] ?? '') . ($user['rhesus'] ?? '')) ?>
                        &middot; <?= e($user['berat_badan'] ?? 0) ?> kg
                    </p>
                </div>

                <?php if ($punya_jadwal_aktif && $jadwal_aktif['status'] === 'menunggu'): ?>
                    <span class="badge text-bg-warning rounded-pill px-3 py-2">
                        <i class="bi bi-hourglass-split"></i> Jadwal menunggu
                    </span>
                <?php elseif ($punya_jadwal_aktif && $jadwal_aktif['status'] === 'disetujui'): ?>
                    <span class="badge text-bg-success rounded-pill px-3 py-2">
                        <i class="bi bi-calendar-check"></i> Jadwal disetujui
                    </span>
                <?php elseif (!$boleh_interval): ?>
                    <span class="badge text-bg-warning rounded-pill px-3 py-2">
                        <i class="bi bi-clock-history"></i> Tunggu <?= e($hari) ?> hari
                    </span>
                <?php elseif ($kuesioner_berlaku): ?>
                    <a href="daftar.php" class="btn btn-light rounded-pill fw-bold">
                        <i class="bi bi-calendar-plus text-danger"></i> Pilih Jadwal
                    </a>
                <?php else: ?>
                    <a href="kuesioner.php" class="btn btn-light rounded-pill fw-bold">
                        <i class="bi bi-clipboard2-pulse text-danger"></i> Isi Kuesioner
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($punya_jadwal_aktif && $jadwal_aktif['status'] === 'menunggu'): ?>
            <div class="alert alert-info small">
                <i class="bi bi-info-circle-fill"></i>
                Anda sudah memiliki jadwal yang menunggu konfirmasi petugas.
                Kuesioner tidak perlu diisi ulang.
            </div>
        <?php elseif ($punya_jadwal_aktif && $jadwal_aktif['status'] === 'disetujui'): ?>
            <div class="alert alert-success small">
                <i class="bi bi-calendar-check-fill"></i>
                Jadwal donor Anda sudah disetujui untuk
                <strong><?= e(date('d M Y', strtotime($jadwal_aktif['tanggal_donor']))) ?></strong>
                sesi <strong><?= e(ucfirst($jadwal_aktif['sesi'])) ?></strong>.
            </div>
        <?php elseif (!$boleh_interval && $hari > 0): ?>
            <div class="alert alert-warning small">
                <i class="bi bi-hourglass-split"></i>
                Anda bisa donor kembali dalam <strong><?= e($hari) ?> hari</strong> lagi.
                Masa jeda setelah donor selesai adalah <?= e(JEDA_DONOR_HARI) ?> hari.
            </div>
        <?php elseif ($kuesioner_berlaku): ?>
            <div class="alert alert-success small">
                <i class="bi bi-check-circle-fill"></i>
                Kuesioner Anda masih berlaku sampai
                <strong><?= e(date('d M Y, H:i', $kuesioner_akhir)) ?></strong>.
                <a href="daftar.php" class="fw-bold">Pilih jadwal donor</a>
            </div>
        <?php elseif ($bisa_mulai && $total > 0): ?>
            <div class="alert alert-success small">
                <i class="bi bi-calendar-check-fill"></i>
                Anda sudah bisa donor kembali.
                <a href="kuesioner.php" class="fw-bold">Isi kuesioner</a>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="donor-stat-card">
                    <div class="donor-stat-num text-pmi"><?= e($total) ?></div>
                    <small class="text-muted">Total donor</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="donor-stat-card">
                    <div class="donor-stat-num text-warning"><?= e($pending) ?></div>
                    <small class="text-muted">Jadwal menunggu</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="donor-stat-card">
                    <div class="donor-stat-num <?= $bisa_mulai ? 'text-success' : 'text-warning' ?>">
                        <?php if ($punya_jadwal_aktif): ?>
                            Proses
                        <?php elseif (!$boleh_interval): ?>
                            <?= e($hari) ?>
                        <?php elseif ($kuesioner_berlaku): ?>
                            Valid
                        <?php else: ?>
                            Siap
                        <?php endif; ?>
                    </div>
                    <small class="text-muted">
                        <?php if ($punya_jadwal_aktif): ?>
                            Jadwal aktif
                        <?php elseif (!$boleh_interval): ?>
                            Hari lagi
                        <?php elseif ($kuesioner_berlaku): ?>
                            Kuesioner
                        <?php else: ?>
                            Bisa donor
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>

        <section class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0"><i class="bi bi-calendar2-week-fill text-pmi"></i> Jadwal Terbaru</h2>
                <?php if ($bisa_mulai && $kuesioner_berlaku): ?>
                    <a href="daftar.php" class="btn btn-pmi btn-sm rounded-pill">Pilih Jadwal</a>
                <?php elseif ($bisa_mulai): ?>
                    <a href="kuesioner.php" class="btn btn-pmi btn-sm rounded-pill">Isi Kuesioner</a>
                <?php endif; ?>
            </div>

            <?php if (mysqli_num_rows($jadwal) === 0): ?>
                <div class="card-body text-center text-muted py-4">
                    Belum ada jadwal.
                    <?php if ($bisa_mulai): ?>
                        <a href="kuesioner.php" class="text-pmi fw-bold">Mulai sekarang</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php while ($j = mysqli_fetch_assoc($jadwal)): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                            <div>
                                <div class="fw-semibold small"><?= e(date('d M Y', strtotime($j['tanggal_donor']))) ?></div>
                                <small class="text-muted">
                                    <?= e($j['lokasi']) ?> &middot; <?= e(ucfirst($j['sesi'])) ?>
                                </small>
                            </div>
                            <span class="badge <?= e(badgeStatus($j['status_tampil'])) ?>">
                                <?= e(ucfirst($j['status_tampil'])) ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>