<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'donor_darah';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die('Koneksi database gagal. Periksa nama database, user, dan password.');
}

mysqli_set_charset($conn, 'utf8mb4');
