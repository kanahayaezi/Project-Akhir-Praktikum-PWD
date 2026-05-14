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
