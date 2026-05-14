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
