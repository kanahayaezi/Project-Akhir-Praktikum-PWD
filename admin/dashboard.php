<?php
require_once '../includes/koneksi.php';
require_once '../includes/session.php';
require_once '../includes/fungsi.php';

cekAdmin();

$total = (int) (dbOne($conn, "SELECT COUNT(*) n FROM users WHERE role = 'donor'")['n'] ?? 0);
$pending = (int) (dbOne($conn, "SELECT COUNT(*) n FROM jadwal_donor WHERE status = 'menunggu'")['n'] ?? 0);
$kritis = (int) (dbOne($conn, 'SELECT COUNT(*) n FROM stok_darah WHERE jumlah_kantong <= batas_kritis')['n'] ?? 0);
$bulan = (int) (dbOne($conn, 'SELECT COUNT(*) n FROM riwayat_donor WHERE MONTH(tanggal) = MONTH(NOW()) AND YEAR(tanggal) = YEAR(NOW())')['n'] ?? 0);

$jadwal = dbSelect(
    $conn,
    "SELECT j.*, u.nama, u.golongan_darah, u.rhesus,
            CASE WHEN r.id IS NOT NULL THEN 'selesai' ELSE j.status END AS status_tampil
     FROM jadwal_donor j
     JOIN users u ON j.user_id = u.id
     LEFT JOIN riwayat_donor r ON r.jadwal_id = j.id
     ORDER BY j.created_at DESC
     LIMIT 8"
);
$stok = dbSelect($conn, 'SELECT * FROM stok_darah ORDER BY jumlah_kantong ASC');

$grafik = [];

for ($i = 5; $i >= 0; $i--) {
    $bulan_key = date('Y-m', strtotime("-$i months"));
    $jumlah = (int) (dbOne(
        $conn,
        "SELECT COUNT(*) n FROM riwayat_donor WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?",
        's',
        [$bulan_key]
    )['n'] ?? 0);

    $grafik[] = [
        'label' => date('M', strtotime("-$i months")),
        'n' => $jumlah,
    ];
}

$max_grafik = 1;
foreach ($grafik as $g) {
    if ($g['n'] > $max_grafik) {
        $max_grafik = $g['n'];
    }
}

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin - PMI Sleman</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin/dashboard.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include '../includes/navbar_admin.php'; ?>

        <main class="admin-main">
            <div class="admin-topbar">
                <div>
                    <h1 class="admin-topbar-title">Dashboard</h1>
                    <small class="text-muted">Selamat datang, <?= e($_SESSION['nama'] ?? 'Admin') ?> - <?= e(date('d M Y')) ?></small>
                </div>
                <a href="donor.php?status=menunggu" class="btn btn-pmi btn-sm rounded-pill">
                    <?= e($pending) ?> Jadwal Menunggu
                </a>
            </div>

            <div class="admin-body">
                <div class="row g-3 mb-4">
                    <?php
                    $cards = [
                        ['n' => $total, 'label' => 'Total pendonor', 'icon' => 'bi-people-fill', 'class' => 'text-success'],
                        ['n' => $pending, 'label' => 'Jadwal menunggu', 'icon' => 'bi-hourglass-split', 'class' => 'text-warning'],
                        ['n' => $kritis, 'label' => 'Stok kritis', 'icon' => 'bi-exclamation-triangle-fill', 'class' => 'text-danger'],
                        ['n' => $bulan, 'label' => 'Donor bulan ini', 'icon' => 'bi-droplet-fill', 'class' => 'text-primary'],
                    ];
                    ?>

                    <?php foreach ($cards as $card): ?>
                        <div class="col-6 col-xl-3">
                            <div class="admin-stat">
                                <i class="bi <?= e($card['icon']) ?> <?= e($card['class']) ?>"></i>
                                <div class="stat-val <?= e($card['class']) ?>"><?= e($card['n']) ?></div>
                                <div class="stat-label"><?= e($card['label']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-lg-7">
                        <section class="admin-panel h-100">
                            <div class="panel-header">
                                <h2><i class="bi bi-bar-chart-fill"></i> Donor 6 Bulan Terakhir</h2>
                            </div>
                            <div class="panel-body">
                                <div class="chart-bars">
                                    <?php foreach ($grafik as $g): ?>
                                        <?php
                                        // Rumus tinggi bar: jumlah bulan ini / jumlah tertinggi x 100%.
                                        $height = max(5, (int) round(($g['n'] / $max_grafik) * 100));
                                        ?>
                                        <div class="chart-item">
                                            <small><?= e($g['n']) ?></small>
                                            <div class="chart-bar" style="height: <?= e($height) ?>%"></div>
                                            <span><?= e($g['label']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="col-lg-5">
                        <section class="admin-panel h-100">
                            <div class="panel-header">
                                <h2><i class="bi bi-droplet-fill"></i> Stok Darah</h2>
                                <a href="stok.php" class="btn btn-sm btn-outline-secondary rounded-pill">Kelola</a>
                            </div>
                            <div class="panel-body">
                                <?php while ($s = mysqli_fetch_assoc($stok)): ?>
                                    <?php
                                    $status = statusStok((int) $s['jumlah_kantong'], (int) $s['batas_kritis']);
                                    $percent = min(100, (int) round($s['jumlah_kantong'] / max(1, $s['batas_kritis'] * 5) * 100));
                                    ?>
                                    <div class="stock-row">
                                        <strong><?= e($s['golongan_darah'] . $s['rhesus']) ?></strong>
                                        <div class="stok-progress flex-grow-1">
                                            <div class="stok-progress-bar bg-<?= e($status['kelas']) ?>" style="width: <?= e($percent) ?>%"></div>
                                        </div>
                                        <span><?= e($s['jumlah_kantong']) ?></span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </section>
                    </div>
                </div>

                <section class="admin-panel">
                    <div class="panel-header">
                        <h2><i class="bi bi-calendar2-week-fill"></i> Jadwal Donor Terbaru</h2>
                        <a href="donor.php" class="btn btn-sm btn-outline-secondary rounded-pill">Lihat semua</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Gol.</th>
                                    <th>Tanggal</th>
                                    <th>Sesi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($j = mysqli_fetch_assoc($jadwal)): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($j['nama']) ?></td>
                                        <td><span class="badge text-bg-danger"><?= e($j['golongan_darah'] . $j['rhesus']) ?></span></td>
                                        <td><?= e(date('d M Y', strtotime($j['tanggal_donor']))) ?></td>
                                        <td><?= e(ucfirst($j['sesi'])) ?></td>
                                        <td><span class="badge <?= e(badgeStatus($j['status_tampil'])) ?>"><?= e(ucfirst($j['status_tampil'])) ?></span></td>
                                        <td><a href="donor.php" class="small text-decoration-none">Detail</a></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

