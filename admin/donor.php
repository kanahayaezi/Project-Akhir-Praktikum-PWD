<?php
require_once '../includes/koneksi.php';
require_once '../includes/session.php';
require_once '../includes/fungsi.php';

cekAdmin();

$pesan = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cekCsrf();

    $aksi = post('aksi');

    if (in_array($aksi, ['setujui', 'tolak', 'hapus'], true)) {
        $id = (int) ($_POST['id'] ?? 0);

        if ($aksi === 'setujui') {
            dbExec($conn, "UPDATE jadwal_donor SET status = 'disetujui', keterangan_tolak = NULL WHERE id = ?", 'i', [$id]);
            $pesan = 'Jadwal berhasil disetujui.';
        } elseif ($aksi === 'tolak') {
            dbExec($conn, "UPDATE jadwal_donor SET status = 'ditolak', keterangan_tolak = 'Tidak memenuhi syarat' WHERE id = ?", 'i', [$id]);
            $pesan = 'Jadwal berhasil ditolak.';
        } else {
            dbExec($conn, 'DELETE FROM jadwal_donor WHERE id = ?', 'i', [$id]);
            $pesan = 'Jadwal berhasil dihapus.';
        }
    }

    if ($aksi === 'catat') {
        $jadwal_id = (int) ($_POST['jadwal_id'] ?? 0);
        $user_id = (int) ($_POST['user_id'] ?? 0);
        $tanggal = post('tanggal');
        $golongan = post('golongan_darah');
        $rhesus = post('rhesus');
        $volume = (int) ($_POST['volume_ml'] ?? 0);
        $hb = (float) ($_POST['hb'] ?? 0);
        $tekanan = post('tekanan_darah');
        $petugas = post('petugas');
        $catatan = post('catatan');

        if ($tanggal === '' || $volume <= 0 || $hb <= 0 || $tekanan === '' || $petugas === '') {
            $error = 'Data hasil donor belum lengkap.';
        } else {
            $sudah_dicatat = dbOne($conn, 'SELECT id FROM riwayat_donor WHERE jadwal_id = ? LIMIT 1', 'i', [$jadwal_id]);

            if ($sudah_dicatat) {
                $error = 'Jadwal ini sudah pernah dicatat.';
            } else {
                dbExec(
                    $conn,
                    'INSERT INTO riwayat_donor
                        (user_id, jadwal_id, tanggal, golongan_darah, rhesus, volume_ml, hb, tekanan_darah, petugas, catatan)
                     VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    'iisssidsss',
                    [$user_id, $jadwal_id, $tanggal, $golongan, $rhesus, $volume, $hb, $tekanan, $petugas, $catatan]
                );

                dbExec($conn, "UPDATE jadwal_donor SET status = 'disetujui' WHERE id = ?", 'i', [$jadwal_id]);
                dbExec($conn, 'UPDATE stok_darah SET jumlah_kantong = jumlah_kantong + 1 WHERE golongan_darah = ? AND rhesus = ?', 'ss', [$golongan, $rhesus]);

                $pesan = 'Hasil donor berhasil dicatat. Jadwal dianggap selesai dan stok diperbarui.';
            }
        }
    }
}

$status = getParam('status');
$cari = getParam('cari');
$status_valid = ['menunggu', 'disetujui', 'ditolak', 'selesai'];
$where = ['1 = 1'];
$types = '';
$params = [];

if (in_array($status, $status_valid, true)) {
    if ($status === 'selesai') {
        $where[] = 'r.id IS NOT NULL';
    } elseif ($status === 'disetujui') {
        $where[] = "j.status = 'disetujui' AND r.id IS NULL";
    } else {
        $where[] = 'j.status = ?';
        $types .= 's';
        $params[] = $status;
    }
} else {
    $status = '';
}

if ($cari !== '') {
    $where[] = 'u.nama LIKE ?';
    $types .= 's';
    $params[] = '%' . $cari . '%';
}

$sql = 'SELECT j.*, u.nama, u.golongan_darah, u.rhesus, u.id uid,
               r.id AS riwayat_id,
               CASE WHEN r.id IS NOT NULL THEN \'selesai\' ELSE j.status END AS status_tampil
        FROM jadwal_donor j
        JOIN users u ON j.user_id = u.id
        LEFT JOIN riwayat_donor r ON r.jadwal_id = j.id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY j.created_at DESC';
$data = dbSelect($conn, $sql, $types, $params);

$counts = [
    'semua' => (int) (dbOne($conn, 'SELECT COUNT(*) n FROM jadwal_donor')['n'] ?? 0),
    'menunggu' => (int) (dbOne($conn, "SELECT COUNT(*) n FROM jadwal_donor WHERE status = 'menunggu'")['n'] ?? 0),
    'disetujui' => (int) (dbOne($conn, "SELECT COUNT(*) n FROM jadwal_donor j WHERE j.status = 'disetujui' AND NOT EXISTS (SELECT 1 FROM riwayat_donor r WHERE r.jadwal_id = j.id)")['n'] ?? 0),
    'ditolak' => (int) (dbOne($conn, "SELECT COUNT(*) n FROM jadwal_donor WHERE status = 'ditolak'")['n'] ?? 0),
    'selesai' => (int) (dbOne($conn, 'SELECT COUNT(*) n FROM riwayat_donor WHERE jadwal_id IS NOT NULL')['n'] ?? 0),
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Donor - PMI Sleman</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin/donor.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include '../includes/navbar_admin.php'; ?>

        <main class="admin-main">
            <div class="admin-topbar">
                <h1 class="admin-topbar-title">Jadwal Donor</h1>
            </div>

            <div class="admin-body">
                <?php if ($pesan): ?>
                    <div class="alert alert-success py-2 small"><i class="bi bi-check-circle-fill"></i> <?= e($pesan) ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?></div>
                <?php endif; ?>

                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <?php
                    $tabs = ['' => 'Semua', 'menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'selesai' => 'Selesai'];
                    ?>
                    <?php foreach ($tabs as $value => $label): ?>
                        <?php $aktif = $status === $value; ?>
                        <a href="donor.php<?= $value ? '?status=' . e($value) : '' ?>" class="btn btn-sm rounded-pill <?= $aktif ? 'btn-pmi' : 'btn-outline-secondary' ?>">
                            <?= e($label) ?>
                            <span class="badge text-bg-dark ms-1"><?= e($value === '' ? $counts['semua'] : $counts[$value]) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <form method="GET" class="row g-2 mb-3">
                    <input type="hidden" name="status" value="<?= e($status) ?>">
                    <div class="col-md-4">
                        <input type="text" name="cari" class="form-control" placeholder="Cari nama" value="<?= e($cari) ?>">
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-pmi rounded-pill px-4"><i class="bi bi-search"></i> Cari</button>
                    </div>
                </form>

                <section class="admin-panel">
                    <div class="panel-header">
                        <h2><i class="bi bi-calendar2-week-fill"></i> Daftar Jadwal</h2>
                        <span class="badge text-bg-secondary"><?= e(mysqli_num_rows($data)) ?> data</span>
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
                                <?php if (mysqli_num_rows($data) === 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Tidak ada data.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php while ($r = mysqli_fetch_assoc($data)): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($r['nama']) ?></td>
                                        <td><span class="badge text-bg-danger"><?= e($r['golongan_darah'] . $r['rhesus']) ?></span></td>
                                        <td><?= e(date('d M Y', strtotime($r['tanggal_donor']))) ?></td>
                                        <td><?= e(ucfirst($r['sesi'])) ?></td>
                                        <td>
                                            <span class="badge <?= e(badgeStatus($r['status_tampil'])) ?>"><?= e(ucfirst($r['status_tampil'])) ?></span>
                                            <?php if (!empty($r['keterangan_tolak'])): ?>
                                                <div><small class="text-muted"><?= e($r['keterangan_tolak']) ?></small></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <?php if ($r['status'] === 'menunggu'): ?>
                                                    <form method="POST" onsubmit="return confirm('Setujui jadwal ini?')">
                                                        <?= csrfInput() ?>
                                                        <input type="hidden" name="aksi" value="setujui">
                                                        <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                                                        <button class="btn-action approve" type="submit"><i class="bi bi-check-lg"></i> Setujui</button>
                                                    </form>
                                                    <form method="POST" onsubmit="return confirm('Tolak jadwal ini?')">
                                                        <?= csrfInput() ?>
                                                        <input type="hidden" name="aksi" value="tolak">
                                                        <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                                                        <button class="btn-action reject" type="submit"><i class="bi bi-x-lg"></i> Tolak</button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ($r['status'] === 'disetujui' && empty($r['riwayat_id'])): ?>
                                                    <button type="button" class="btn-action record btn-catat"
                                                        data-jid="<?= e($r['id']) ?>"
                                                        data-uid="<?= e($r['uid']) ?>"
                                                        data-gol="<?= e($r['golongan_darah']) ?>"
                                                        data-rh="<?= e($r['rhesus']) ?>">
                                                        <i class="bi bi-pencil-fill"></i> Catat
                                                    </button>
                                                <?php endif; ?>

                                                <form method="POST" onsubmit="return confirm('Hapus jadwal ini?')">
                                                    <?= csrfInput() ?>
                                                    <input type="hidden" name="aksi" value="hapus">
                                                    <input type="hidden" name="id" value="<?= e($r['id']) ?>">
                                                    <button class="btn-action delete" type="submit"><i class="bi bi-trash-fill"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <div class="modal fade" id="modalCatat" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <?= csrfInput() ?>
                <input type="hidden" name="aksi" value="catat">
                <input type="hidden" name="jadwal_id" id="m_jid">
                <input type="hidden" name="user_id" id="m_uid">

                <div class="modal-header">
                    <h2 class="modal-title h5">Catat Hasil Donor</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= e(date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Volume (ml)</label>
                            <input type="number" name="volume_ml" class="form-control" value="350" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Golongan darah</label>
                            <input type="text" name="golongan_darah" id="m_gol" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Rhesus</label>
                            <input type="text" name="rhesus" id="m_rh" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">HB (g/dL)</label>
                            <input type="number" name="hb" class="form-control" step="0.1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tekanan darah</label>
                            <input type="text" name="tekanan_darah" class="form-control" placeholder="120/80" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Petugas</label>
                            <input type="text" name="petugas" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan</label>
                            <textarea name="catatan" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-pmi rounded-pill px-4 fw-bold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modal = new bootstrap.Modal(document.getElementById('modalCatat'));

        document.querySelectorAll('.btn-catat').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById('m_jid').value = button.dataset.jid;
                document.getElementById('m_uid').value = button.dataset.uid;
                document.getElementById('m_gol').value = button.dataset.gol;
                document.getElementById('m_rh').value = button.dataset.rh;
                modal.show();
            });
        });
    </script>
</body>

</html>