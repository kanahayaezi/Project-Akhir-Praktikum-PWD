<?php
require_once 'includes/koneksi.php';
require_once 'includes/session.php';
require_once 'includes/fungsi.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'donor/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cekCsrf();
    $email = strtolower(post('email'));
    $password = $_POST['password'] ?? '';

    $user = dbOne($conn, 'SELECT * FROM users WHERE email = ? LIMIT 1', 's', [$email]);
    $password_ok = $user && password_verify($password, $user['password']);
    $password_lama = $user && strlen($user['password']) === 32 && md5($password) === $user['password'];

    if ($user && ($password_ok || $password_lama)) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['gol'] = ($user['golongan_darah'] ?? '') . ($user['rhesus'] ?? '');
        header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'donor/dashboard.php'));
        exit;
    }
}