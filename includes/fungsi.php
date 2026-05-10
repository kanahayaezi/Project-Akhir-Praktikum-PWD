<?php
$golongan_darah = ['A', 'B', 'AB', 'O'];

define('MASA_BERLAKU_KUESIONER', 24 * 60 * 60);

// Pendonor boleh donor lagi setelah 90 hari dari donor terakhir.
define('JEDA_DONOR_HARI', 90);

$pertanyaan = [
    ['id' => 'q1',  'teks' => 'Apakah Anda merasa sehat hari ini?', 'tolak' => 'tidak'],
    ['id' => 'q2',  'teks' => 'Apakah Anda tidur cukup minimal 5 jam?', 'tolak' => 'tidak'],
    ['id' => 'q3',  'teks' => 'Apakah Anda sudah makan sebelum donor?', 'tolak' => 'tidak'],
    ['id' => 'q4',  'teks' => 'Apakah berat badan Anda minimal 45 kg?', 'tolak' => 'tidak'],
    ['id' => 'q5',  'teks' => 'Apakah usia Anda 17 sampai 60 tahun?', 'tolak' => 'tidak'],
    ['id' => 'q6',  'teks' => 'Apakah Anda bersedia donor secara sukarela?', 'tolak' => 'tidak'],
    ['id' => 'q7',  'teks' => 'Apakah data yang Anda isi benar?', 'tolak' => 'tidak'],
    ['id' => 'q8',  'teks' => 'Apakah Anda sedang demam?', 'tolak' => 'ya'],
    ['id' => 'q9',  'teks' => 'Apakah Anda sedang batuk atau pilek?', 'tolak' => 'ya'],
    ['id' => 'q10', 'teks' => 'Apakah Anda sedang minum antibiotik?', 'tolak' => 'ya'],
    ['id' => 'q11', 'teks' => 'Apakah Anda pernah didiagnosis hepatitis B/C?', 'tolak' => 'ya'],
    ['id' => 'q12', 'teks' => 'Apakah Anda pernah didiagnosis HIV/AIDS?', 'tolak' => 'ya'],
    ['id' => 'q13', 'teks' => 'Apakah Anda pernah menderita penyakit jantung?', 'tolak' => 'ya'],
    ['id' => 'q14', 'teks' => 'Apakah Anda pernah menerima transfusi darah dalam 12 bulan terakhir?', 'tolak' => 'ya'],
    ['id' => 'q15', 'teks' => 'Apakah Anda donor darah dalam 3 bulan terakhir?', 'tolak' => 'ya'],
    ['id' => 'q16', 'teks' => 'Apakah Anda sedang hamil, menyusui, atau baru melahirkan?', 'tolak' => 'ya'],
];

function e($nilai)
{

    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

function bersihkan($nilai)
{
    return trim((string) $nilai);
}

function post($nama, $default = '')
{
    return bersihkan($_POST[$nama] ?? $default);
}

function getParam($nama, $default = '')
{
    return bersihkan($_GET[$nama] ?? $default);
}

function dbRun($conn, $sql, $types = '', $params = [])
{
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die('Query gagal disiapkan: ' . e(mysqli_error($conn)));
    }

    if ($types !== '' && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        die('Query gagal dijalankan: ' . e(mysqli_stmt_error($stmt)));
    }

    return $stmt;
}

function dbSelect($conn, $sql, $types = '', $params = [])
{
    $stmt = dbRun($conn, $sql, $types, $params);
    return mysqli_stmt_get_result($stmt);
}

function dbOne($conn, $sql, $types = '', $params = [])
{
    $result = dbSelect($conn, $sql, $types, $params);
    return mysqli_fetch_assoc($result) ?: null;
}

function dbAll($conn, $sql, $types = '', $params = [])
{
    $result = dbSelect($conn, $sql, $types, $params);
    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

function dbExec($conn, $sql, $types = '', $params = [])
{
    dbRun($conn, $sql, $types, $params);
    return true;
}

function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfInput()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function cekCsrf()
{
    $token = $_POST['csrf_token'] ?? '';

    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        die('Token keamanan tidak valid. Silakan ulangi dari halaman sebelumnya.');
    }
}

function hitungUmur($tanggal_lahir)
{
    if (!$tanggal_lahir) {
        return 0;
    }

    return (new DateTime($tanggal_lahir))->diff(new DateTime())->y;
}

function donorTerakhir($conn, $user_id)
{
    return dbOne(
        $conn,
        'SELECT tanggal FROM riwayat_donor WHERE user_id = ? ORDER BY tanggal DESC LIMIT 1',
        'i',
        [$user_id]
    );
}

function cekInterval($conn, $user_id)
{
    $row = donorTerakhir($conn, $user_id);

    if (!$row) {
        return true;
    }

    // jarak donor berikutnya minimal 90 hari dari donor terakhir.
    return (new DateTime($row['tanggal']))->diff(new DateTime())->days >= JEDA_DONOR_HARI;
}

function sisaHariDonor($conn, $user_id)
{
    $row = donorTerakhir($conn, $user_id);

    if (!$row) {
        return 0;
    }

    // tanggal donor terakhir + 90 hari - hari ini.
    $tanggal_boleh = strtotime($row['tanggal'] . ' +' . JEDA_DONOR_HARI . ' days');
    return max(0, (int) ceil(($tanggal_boleh - time()) / 86400));
}

function jadwalAktif($conn, $user_id)
{
    // Aktif berarti masih menunggu, atau sudah disetujui tetapi belum dicatat sebagai riwayat donor.
    return dbOne(
        $conn,
        "SELECT j.*
         FROM jadwal_donor j
         WHERE j.user_id = ?
           AND (
                j.status = 'menunggu'
                OR (
                    j.status = 'disetujui'
                    AND NOT EXISTS (
                        SELECT 1 FROM riwayat_donor r WHERE r.jadwal_id = j.id
                    )
                )
           )
         ORDER BY j.created_at DESC
         LIMIT 1",
        'i',
        [$user_id]
    );
}

function adaJadwalAktif($conn, $user_id)
{
    return (bool) jadwalAktif($conn, $user_id);
}

function adaJadwalMenunggu($conn, $user_id)
{
    // Cek apakah pendonor masih punya jadwal yang menunggu konfirmasi.
    $row = dbOne(
        $conn,
        "SELECT COUNT(*) n FROM jadwal_donor WHERE user_id = ? AND status = 'menunggu'",
        'i',
        [$user_id]
    );

    return (int) ($row['n'] ?? 0) > 0;
}

function waktuKuesionerBerakhir($user_id)
{
    return (int) ($_SESSION['kuesioner_lulus_until'][$user_id] ?? 0);
}

function kuesionerMasihBerlaku($user_id)
{
    $berakhir = waktuKuesionerBerakhir($user_id);

    if ($berakhir > time()) {
        return true;
    }

    hapusKuesionerLulus($user_id);
    return false;
}

function setKuesionerLulus($user_id)
{
    // Kuesioner lulus disimpan sementara di session selama 24 jam.
    $_SESSION['kuesioner_lulus_until'][$user_id] = time() + MASA_BERLAKU_KUESIONER;
}

function hapusKuesionerLulus($user_id)
{
    unset($_SESSION['kuesioner_lulus_until'][$user_id]);
    unset($_SESSION['lulus_kuesioner']); // membersihkan session lama dari versi sebelumnya
}

function statusStok($jumlah, $batas)
{
    if ($jumlah <= $batas) {
        return ['label' => 'Kritis', 'kelas' => 'danger', 'icon' => 'bi-exclamation-triangle-fill'];
    }

    if ($jumlah <= $batas * 2) {
        return ['label' => 'Sedang', 'kelas' => 'warning', 'icon' => 'bi-exclamation-circle-fill'];
    }

    return ['label' => 'Aman', 'kelas' => 'success', 'icon' => 'bi-check-circle-fill'];
}

function badgeStatus($status)
{
    if ($status === 'selesai') {
        return 'text-bg-primary';
    }

    if ($status === 'disetujui') {
        return 'text-bg-success';
    }

    if ($status === 'ditolak') {
        return 'text-bg-danger';
    }

    return 'text-bg-warning';
}

function inisial($nama)
{
    return strtoupper(substr((string) $nama, 0, 2));
}
