<?php
require_once '../includes/koneksi.php';
require_once '../includes/session.php';
require_once '../includes/fungsi.php';

cekAdmin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cekCsrf();

    $id = (int) ($_POST['id'] ?? 0);
    $jumlah = (int) ($_POST['jumlah_kantong'] ?? 0);
    $batas = (int) ($_POST['batas_kritis'] ?? 0);

    if ($jumlah < 0 || $batas < 1) {
        $error = 'Jumlah kantong tidak boleh negatif dan batas kritis minimal 1.';
    } else {
        dbExec($conn, 'UPDATE stok_darah SET jumlah_kantong = ?, batas_kritis = ? WHERE id = ?', 'iii', [$jumlah, $batas, $id]);
        $success = 'Stok berhasil diperbarui.';
    }
}

$stok = dbAll($conn, 'SELECT * FROM stok_darah ORDER BY golongan_darah, rhesus DESC');
$total_kantong = 0;
$total_kritis = 0;

foreach ($stok as $s) {
    $total_kantong += (int) $s['jumlah_kantong'];

    if ($s['jumlah_kantong'] <= $s['batas_kritis']) {
        $total_kritis++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stok Darah - PMI Sleman</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin/stok.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include '../includes/navbar_admin.php'; ?>

        <main class="admin-main">
            <div class="admin-topbar">
                <div>
                    <h1 class="admin-topbar-title">Stok Darah</h1>
                    <small class="text-muted">Diperbarui: <?= e(date('d M Y, H:i')) ?> WIB</small>
                </div>
                <div class="topbar-summary">
                    <div>
                        <strong><?= e($total_kantong) ?></strong>
                        <small>Total kantong</small>
                    </div>
                    <div>
                        <strong class="text-danger"><?= e($total_kritis) ?></strong>
                        <small>Kritis</small>
                    </div>
                </div>
            </div>

            <div class="admin-body">
                <?php if ($success): ?>
                    <div class="alert alert-success py-2 small">
                        <i class="bi bi-check-circle-fill"></i> <?= e($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <div class="stock-note mb-3">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>Ubah jumlah kantong dan batas kritis sesuai data stok terbaru.</span>
                </div>

                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
                    <?php foreach ($stok as $s): ?>
                        <?php
                        $status = statusStok((int) $s['jumlah_kantong'], (int) $s['batas_kritis']);

                        // rumus progress: stok sekarang / target tampilan x 100%
                        // target tampilan dibuat 4x batas kritis supaya bar tidak terlalu cepat penuh
                        $percent = min(100, (int) round($s['jumlah_kantong'] / max(1, $s['batas_kritis'] * 4) * 100));
                        ?>
                        <div class="col">
                            <div class="stok-admin-card stok-status-<?= e($status['kelas']) ?> h-100">
                                <div class="stok-card-accent bg-<?= e($status['kelas']) ?>"></div>

                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                        <div>
                                            <div class="stok-blood-code"><?= e($s['golongan_darah'] . $s['rhesus']) ?></div>
                                            <small class="text-muted">Golongan darah</small>
                                        </div>
                                        <span class="badge rounded-pill text-bg-<?= e($status['kelas']) ?>">
                                            <i class="bi <?= e($status['icon']) ?>"></i> <?= e($status['label']) ?>
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-end gap-2 mb-2">
                                        <div class="stok-admin-num text-<?= e($status['kelas']) ?>"><?= e($s['jumlah_kantong']) ?></div>
                                        <span class="text-muted mb-2">kantong tersedia</span>
                                    </div>

                                    <div class="stok-progress mb-3">
                                        <div class="stok-progress-bar bg-<?= e($status['kelas']) ?>" style="width: <?= e($percent) ?>%"></div>
                                    </div>

                                    <div class="stok-mini-info mb-3">
                                        <div>
                                            <small>Batas kritis</small>
                                            <strong><?= e($s['batas_kritis']) ?></strong>
                                        </div>
                                        <div>
                                            <small>Progress</small>
                                            <strong><?= e($percent) ?>%</strong>
                                        </div>
                                    </div>

                                    <form method="POST" class="row g-2">
                                        <?= csrfInput() ?>
                                        <input type="hidden" name="id" value="<?= e($s['id']) ?>">

                                        <div class="col-6">
                                            <label class="form-label small text-muted">Kantong</label>
                                            <input type="number" name="jumlah_kantong" class="form-control form-control-sm" value="<?= e($s['jumlah_kantong']) ?>" min="0" required>
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label small text-muted">Batas</label>
                                            <input type="number" name="batas_kritis" class="form-control form-control-sm" value="<?= e($s['batas_kritis']) ?>" min="1" required>
                                        </div>

                                        <div class="col-12">
                                            <button type="submit" class="btn btn-pmi btn-sm w-100 rounded-pill fw-bold">
                                                <i class="bi bi-save"></i> Simpan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>