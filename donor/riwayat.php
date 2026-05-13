<?php
require_once '../includes/koneksi.php';
require_once '../includes/session.php';
require_once '../includes/fungsi.php';

cekDonor();

$uid = (int) $_SESSION['user_id'];
$result = dbSelect($conn, 'SELECT * FROM riwayat_donor WHERE user_id = ? ORDER BY tanggal DESC', 'i', [$uid]);
$stats = dbOne($conn, 'SELECT COUNT(*) total, COALESCE(SUM(volume_ml), 0) vol FROM riwayat_donor WHERE user_id = ?', 'i', [$uid]);
$total = (int) ($stats['total'] ?? 0);
$volume = (int) ($stats['vol'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Donor - PMI Sleman</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/donor.css">
    <link rel="stylesheet" href="../assets/css/donor/riwayat.css">
</head>

<body class="donor-page">
    <?php include '../includes/navbar_donor.php'; ?>

    <main class="container-lg py-4">
        <h1 class="h4 mb-1"><i class="bi bi-clock-history text-pmi"></i> Riwayat Donor Saya</h1>
        <p class="text-muted small mb-4">Semua catatan donor yang sudah dicatat petugas.</p>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3">
                    <h2 class="h3 text-pmi mb-0"><?= e($total) ?></h2>
                    <small class="text-muted">Total donor</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3">
                    <h2 class="h3 text-primary mb-0"><?= e(number_format($volume)) ?></h2>
                    <small class="text-muted">ml darah</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-3">
                    <h2 class="h3 text-success mb-0"><?= e($total * 3) ?></h2>
                    <small class="text-muted">Perkiraan nyawa terbantu</small>
                </div>
            </div>
        </div>

        <section class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-table text-pmi"></i> Detail Riwayat
            </div>

            <?php if (mysqli_num_rows($result) === 0): ?>
                <div class="card-body text-center text-muted py-4">
                    Belum ada riwayat donor. <a href="kuesioner.php" class="text-pmi fw-bold">Mulai sekarang</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 small align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Gol. darah</th>
                                <th>Volume</th>
                                <th>HB</th>
                                <th>Tensi</th>
                                <th>Petugas</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= e(date('d M Y', strtotime($r['tanggal']))) ?></td>
                                    <td><span class="badge text-bg-danger"><?= e($r['golongan_darah'] . $r['rhesus']) ?></span></td>
                                    <td class="fw-bold text-primary"><?= e($r['volume_ml']) ?> ml</td>
                                    <td class="fw-bold text-success"><?= e($r['hb']) ?></td>
                                    <td><?= e($r['tekanan_darah']) ?></td>
                                    <td><?= e($r['petugas'] ?: '-') ?></td>
                                    <td class="text-muted"><?= e($r['catatan'] ?: '-') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>