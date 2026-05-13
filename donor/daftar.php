<?php
require_once '../includes/koneksi.php';
require_once '../includes/session.php';
require_once '../includes/fungsi.php';

cekDonor();

$uid = (int) $_SESSION['user_id'];

// Kalau sudah ada jadwal aktif atau belum melewati 90 hari, kembali ke dashboard.
if (adaJadwalAktif($conn, $uid) || !cekInterval($conn, $uid)) {
    hapusKuesionerLulus($uid);
    header('Location: dashboard.php');
    exit;
}

// Pendonor wajib lulus kuesioner, dan hasilnya hanya berlaku 24 jam.
if (!kuesionerMasihBerlaku($uid)) {
    header('Location: kuesioner.php');
    exit;
}

$terakhir = donorTerakhir($conn, $uid);
$error = '';
$success = '';
$lokasi = 'PMI Kab. Sleman - Jl. Magelang No.6, Mlati';
$kuesioner_akhir = waktuKuesionerBerakhir($uid);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cekCsrf();

    $tanggal = post('tanggal_donor');
    $sesi = post('sesi');
    $sesi_valid = ['pagi', 'siang', 'sore'];

    if (!kuesionerMasihBerlaku($uid)) {
        $error = 'Kuesioner sudah kedaluwarsa. Silakan isi kuesioner lagi.';
    } elseif ($tanggal === '' || !in_array($sesi, $sesi_valid, true)) {
        $error = 'Tanggal dan sesi wajib dipilih.';
    } elseif (strtotime($tanggal) < strtotime('today')) {
        $error = 'Tanggal donor tidak boleh di masa lalu.';
    } elseif (!cekInterval($conn, $uid)) {
        $error = 'Interval donor belum 90 hari.';
    } elseif (adaJadwalAktif($conn, $uid)) {
        $error = 'Anda masih memiliki jadwal donor yang aktif.';
    } else {
        dbExec(
            $conn,
            'INSERT INTO jadwal_donor (user_id, tanggal_donor, sesi, lokasi, status) VALUES (?, ?, ?, ?, "menunggu")',
            'isss',
            [$uid, $tanggal, $sesi, $lokasi]
        );

        hapusKuesionerLulus($uid);
        $success = 'Pendaftaran berhasil. Menunggu konfirmasi petugas.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Donor - PMI Sleman</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/donor.css">
    <link rel="stylesheet" href="../assets/css/donor/daftar.css">
</head>
<body class="donor-page">
    <?php include '../includes/navbar_donor.php'; ?>

    <main class="container py-4 schedule-wrapper">
        <h1 class="h4 mb-1"><i class="bi bi-calendar-plus-fill text-pmi"></i> Pendaftaran Donor</h1>
        <p class="text-muted small mb-4">Pilih tanggal dan sesi yang tersedia.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success py-2 small">
                <i class="bi bi-check-circle-fill"></i> <?= e($success) ?>
                <a href="dashboard.php" class="fw-bold ms-2">Ke dashboard</a>
            </div>
        <?php endif; ?>

        <div class="alert alert-success small">
            <strong>Syarat dasar:</strong> usia 17-60 tahun, berat minimal 45 kg, sehat, dan jarak donor minimal 90 hari.
        </div>

        <div class="alert alert-info small">
            Kuesioner Anda berlaku sampai <strong><?= e(date('d M Y, H:i', $kuesioner_akhir)) ?></strong>.
            Setelah memilih jadwal, kuesioner akan terkunci.
        </div>

        <?php if ($terakhir): ?>
            <div class="alert alert-warning small">
                Donor terakhir: <strong><?= e(date('d M Y', strtotime($terakhir['tanggal']))) ?></strong>.
                Bisa donor kembali mulai <strong><?= e(date('d M Y', strtotime($terakhir['tanggal'] . ' +' . JEDA_DONOR_HARI . ' days'))) ?></strong>.
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" class="row g-3">
                        <?= csrfInput() ?>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal donor</label>
                            <input type="date" name="tanggal_donor" class="form-control" min="<?= e(date('Y-m-d')) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Sesi</label>
                            <select name="sesi" class="form-select" required>
                                <option value="">Pilih sesi</option>
                                <option value="pagi">Pagi (08.00-10.00)</option>
                                <option value="siang">Siang (10.00-12.00)</option>
                                <option value="sore">Sore (13.00-15.00)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Lokasi</label>
                            <input type="text" class="form-control bg-light" value="<?= e($lokasi) ?>" readonly>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-pmi w-100 rounded-pill fw-bold">
                                <i class="bi bi-send-check-fill"></i> Daftar Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
