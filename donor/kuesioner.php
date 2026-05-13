<?php
require_once '../includes/koneksi.php';
require_once '../includes/session.php';
require_once '../includes/fungsi.php';

cekDonor();

$uid = (int) $_SESSION['user_id'];
$user = dbOne($conn, 'SELECT * FROM users WHERE id = ? LIMIT 1', 'i', [$uid]);

// Kalau masih punya jadwal aktif, pendonor tidak perlu isi kuesioner lagi.
if (adaJadwalAktif($conn, $uid) || !cekInterval($conn, $uid)) {
    header('Location: dashboard.php');
    exit;
}

// Kalau kuesioner yang lama masih berlaku, langsung lanjut memilih jadwal.
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
