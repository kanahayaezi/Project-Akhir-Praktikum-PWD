<?php
require_once 'includes/koneksi.php';
require_once 'includes/fungsi.php';
require_once 'includes/session.php';

$stok = dbAll($conn, 'SELECT * FROM stok_darah ORDER BY golongan_darah, rhesus DESC');
$total_donor = (int) (dbOne($conn, "SELECT COUNT(*) n FROM users WHERE role = 'donor'")['n'] ?? 0);
$donor_thn = (int) (dbOne($conn, 'SELECT COUNT(*) n FROM riwayat_donor WHERE YEAR(tanggal) = YEAR(NOW())')['n'] ?? 0);
$total_rw = (int) (dbOne($conn, 'SELECT COUNT(*) n FROM riwayat_donor')['n'] ?? 0);
