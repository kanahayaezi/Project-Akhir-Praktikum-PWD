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
    $id = (int) ($_POST['id'] ?? 0);

    if ($aksi === 'hapus') {
        dbExec($conn, 'DELETE FROM riwayat_donor WHERE user_id = ?', 'i', [$id]);
        dbExec($conn, 'DELETE FROM jadwal_donor WHERE user_id = ?', 'i', [$id]);
        dbExec($conn, "DELETE FROM users WHERE id = ? AND role = 'donor'", 'i', [$id]);
        $pesan = 'Pendonor berhasil dihapus.';
    }

    if ($aksi === 'edit') {
        $nama = post('nama');
        $telp = post('no_telepon');
        $alamat = post('alamat');
        $bb = (int) ($_POST['berat_badan'] ?? 0);
        $gol = post('golongan_darah');
        $rh = post('rhesus');

        if ($nama === '' || $bb < 1 || !in_array($gol, $golongan_darah, true) || !in_array($rh, ['+', '-'], true)) {
            $error = 'Data pendonor belum valid.';
        } else {
            dbExec(
                $conn,
                'UPDATE users
                 SET nama = ?, no_telepon = ?, alamat = ?, berat_badan = ?, golongan_darah = ?, rhesus = ?
                 WHERE id = ? AND role = "donor"',
                'sssissi',
                [$nama, $telp, $alamat, $bb, $gol, $rh, $id]
            );

            $pesan = 'Data pendonor berhasil diperbarui.';
        }
    }
}

$cari = getParam('cari');
$gol = getParam('gol');
$where = ["u.role = 'donor'"];
$types = '';
$params = [];

if ($cari !== '') {
    $where[] = '(u.nama LIKE ? OR u.email LIKE ?)';
    $types .= 'ss';
    $params[] = '%' . $cari . '%';
    $params[] = '%' . $cari . '%';
}

if (in_array($gol, $golongan_darah, true)) {
    $where[] = 'u.golongan_darah = ?';
    $types .= 's';
    $params[] = $gol;
} else {
    $gol = '';
}

$data = dbSelect(
    $conn,
    'SELECT u.*, (SELECT COUNT(*) FROM riwayat_donor r WHERE r.user_id = u.id) total_donor
     FROM users u
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY u.created_at DESC',
    $types,
    $params
);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Pendonor - PMI Sleman</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin/pendonor.css">
</head>

<body>
    <div class="admin-wrapper">
        <?php include '../includes/navbar_admin.php'; ?>

        <main class="admin-main">
            <div class="admin-topbar">
                <h1 class="admin-topbar-title">Kelola Pendonor</h1>
            </div>

            <div class="admin-body">
                <?php if ($pesan): ?>
                    <div class="alert alert-success py-2 small"><i class="bi bi-check-circle-fill"></i> <?= e($pesan) ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle-fill"></i> <?= e($error) ?></div>
                <?php endif; ?>

                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" name="cari" class="form-control" placeholder="Nama atau email" value="<?= e($cari) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="gol" class="form-select">
                            <option value="">Semua golongan</option>
                            <?php foreach ($golongan_darah as $g): ?>
                                <option value="<?= e($g) ?>" <?= $gol === $g ? 'selected' : '' ?>><?= e($g) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-pmi rounded-pill px-4"><i class="bi bi-search"></i> Cari</button>
                    </div>
                </form>

                <section class="admin-panel">
                    <div class="panel-header">
                        <h2><i class="bi bi-person-lines-fill"></i> Daftar Pendonor</h2>
                        <span class="badge text-bg-secondary"><?= e(mysqli_num_rows($data)) ?> data</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table admin-table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Pendonor</th>
                                    <th>Kontak</th>
                                    <th>Data Medis</th>
                                    <th>Total Donor</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($data) === 0): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Tidak ada data.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php while ($d = mysqli_fetch_assoc($data)): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= e($d['nama']) ?></div>
                                            <small class="text-muted"><?= e($d['email']) ?></small>
                                        </td>
                                        <td>
                                            <div><?= e($d['no_telepon'] ?: '-') ?></div>
                                            <small class="text-muted"><?= e($d['alamat'] ?: '-') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge text-bg-danger"><?= e($d['golongan_darah'] . $d['rhesus']) ?></span>
                                            <small class="text-muted ms-1"><?= e($d['berat_badan']) ?> kg - <?= e(hitungUmur($d['tanggal_lahir'])) ?> thn</small>
                                        </td>
                                        <td><strong class="text-pmi"><?= e($d['total_donor']) ?>x</strong></td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <button type="button" class="btn-action edit btn-edit"
                                                    data-id="<?= e($d['id']) ?>"
                                                    data-nama="<?= e($d['nama']) ?>"
                                                    data-telepon="<?= e($d['no_telepon']) ?>"
                                                    data-alamat="<?= e($d['alamat']) ?>"
                                                    data-bb="<?= e($d['berat_badan']) ?>"
                                                    data-gol="<?= e($d['golongan_darah']) ?>"
                                                    data-rh="<?= e($d['rhesus']) ?>">
                                                    <i class="bi bi-pencil-fill"></i> Edit
                                                </button>

                                                <form method="POST" onsubmit="return confirm('Hapus pendonor ini?')">
                                                    <?= csrfInput() ?>
                                                    <input type="hidden" name="aksi" value="hapus">
                                                    <input type="hidden" name="id" value="<?= e($d['id']) ?>">
                                                    <button type="submit" class="btn-action delete"><i class="bi bi-trash-fill"></i> Hapus</button>
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

    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <?= csrfInput() ?>
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" id="e_id">

                <div class="modal-header">
                    <h2 class="modal-title h5">Edit Pendonor</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Nama</label>
                            <input type="text" name="nama" id="e_nama" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Telepon</label>
                            <input type="text" name="no_telepon" id="e_telepon" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Berat (kg)</label>
                            <input type="number" name="berat_badan" id="e_bb" class="form-control" min="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Alamat</label>
                            <input type="text" name="alamat" id="e_alamat" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Golongan darah</label>
                            <select name="golongan_darah" id="e_gol" class="form-select" required>
                                <?php foreach ($golongan_darah as $g): ?>
                                    <option value="<?= e($g) ?>"><?= e($g) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Rhesus</label>
                            <select name="rhesus" id="e_rh" class="form-select" required>
                                <option value="+">Positif (+)</option>
                                <option value="-">Negatif (-)</option>
                            </select>
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
        const modalEdit = new bootstrap.Modal(document.getElementById('modalEdit'));

        document.querySelectorAll('.btn-edit').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById('e_id').value = button.dataset.id;
                document.getElementById('e_nama').value = button.dataset.nama;
                document.getElementById('e_telepon').value = button.dataset.telepon;
                document.getElementById('e_alamat').value = button.dataset.alamat;
                document.getElementById('e_bb').value = button.dataset.bb;
                document.getElementById('e_gol').value = button.dataset.gol;
                document.getElementById('e_rh').value = button.dataset.rh;
                modalEdit.show();
            });
        });
    </script>
</body>

</html>