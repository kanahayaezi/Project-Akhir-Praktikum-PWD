<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function cekLogin()
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
}

function cekAdmin()
{
    cekLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ../login.php');
        exit;
    }
}

function cekDonor()
{
    cekLogin();
    if (($_SESSION['role'] ?? '') !== 'donor') {
        header('Location: ../login.php');
        exit;
    }
}