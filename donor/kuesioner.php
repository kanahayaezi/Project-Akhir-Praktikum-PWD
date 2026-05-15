<?php
require_once '../includes/koneksi.php';
require_once '../includes/session.php';
require_once '../includes/fungsi.php';

cekDonor();

$uid = (int) $_SESSION['user_id'];
$user = dbOne($conn, 'SELECT * FROM users WHERE id = ? LIMIT 1', 'i', [$uid]);

// kalau masih punya jadwal aktif, pendonor tidak perlu isi kuesioner lagi
if (adaJadwalAktif($conn, $uid) || !cekInterval($conn, $uid)) {
    header('Location: dashboard.php');
    exit;
}

// kalau kuesioner yang lama masih berlaku, langsung lanjut memilih jadwal
if (kuesionerMasihBerlaku($uid)) {
    header('Location: daftar.php');
    exit;
}

$diblokir = false;
$boleh_coba = '';

if (isset($_SESSION['gagal_skrining'][$uid])) {
    $selisih_jam = (time() - $_SESSION['gagal_skrining'][$uid]) / 3600;

    if ($selisih_jam < 24) {
        $diblokir = true;
        $boleh_coba = date('d M Y, H:i', $_SESSION['gagal_skrining'][$uid] + 86400);
    } else {
        unset($_SESSION['gagal_skrining'][$uid]);
    }
}

if (!$diblokir && $_SERVER['REQUEST_METHOD'] === 'POST') {
    cekCsrf();

    $gagal = false;

    foreach ($pertanyaan as $p) {
        $jawaban = $_POST[$p['id']] ?? '';

        if (!in_array($jawaban, ['ya', 'tidak'], true) || $jawaban === $p['tolak']) {
            $gagal = true;
            break;
        }
    }

    if ($gagal) {
        $_SESSION['gagal_skrining'][$uid] = time();
        $diblokir = true;
        $boleh_coba = date('d M Y, H:i', time() + 86400);
    } else {
        setKuesionerLulus($uid);
        header('Location: daftar.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kuesioner - PMI Sleman</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/donor.css">
    <link rel="stylesheet" href="../assets/css/donor/kuesioner.css">
</head>

<body class="donor-page">
    <?php include '../includes/navbar_donor.php'; ?>

    <main class="container py-4 questionnaire-wrapper">
        <h1 class="h4 mb-1"><i class="bi bi-clipboard2-pulse-fill text-pmi"></i> Kuesioner Pra-Donor</h1>
        <p class="text-muted small mb-4">Jawab sesuai kondisi Anda hari ini. Kuesioner berlaku 24 jam.</p>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="donor-card-avatar"><?= e(inisial($user['nama'] ?? 'D')) ?></div>
                <div>
                    <div class="fw-semibold"><?= e($user['nama'] ?? 'Pendonor') ?></div>
                    <small class="text-muted">
                        <?= e(($user['golongan_darah'] ?? '') . ($user['rhesus'] ?? '')) ?>
                        &middot; <?= e($user['berat_badan'] ?? 0) ?> kg
                    </small>
                </div>
            </div>
        </div>

        <?php if ($diblokir): ?>
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body p-4">
                    <div class="blocked-icon mx-auto mb-3"><i class="bi bi-shield-x"></i></div>
                    <h2 class="h5">Belum Lolos Skrining Awal</h2>
                    <p class="text-muted small">
                        Kondisi Anda belum memenuhi syarat untuk donor darah hari ini.
                    </p>
                    <div class="alert alert-warning small mb-3">
                        Bisa mencoba kembali pada <strong><?= e($boleh_coba) ?></strong>
                    </div>
                    <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill">Kembali ke Dashboard</a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" class="card border-0 shadow-sm">
                <?= csrfInput() ?>

                <div class="list-group list-group-flush">
                    <?php foreach ($pertanyaan as $i => $p): ?>
                        <div class="list-group-item question-item">
                            <div class="question-text">
                                <span class="question-number"><?= e($i + 1) ?>.</span>
                                <?= e($p['teks']) ?>
                            </div>
                            <div class="radio-btn-group">
                                <label>
                                    <input type="radio" name="<?= e($p['id']) ?>" value="ya" required>
                                    <span>Ya</span>
                                </label>
                                <label>
                                    <input type="radio" name="<?= e($p['id']) ?>" value="tidak">
                                    <span>Tidak</span>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card-body border-top">
                    <button type="submit" class="btn btn-pmi w-100 rounded-pill fw-bold">
                        <i class="bi bi-send-check-fill"></i> Kirim Kuesioner
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>