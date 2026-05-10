<?php
$golongan_darah = ['A', 'B', 'AB', 'O'];

// Kuesioner hanya berlaku 24 jam setelah pendonor dinyatakan lolos.
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
    // Escape output agar teks dari database tidak menjadi script HTML.
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

