<?php
// Set Cookie Session agar otomatis hancur saat browser/tab ditutup
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0);
    session_start();
}

// 1. Cek apakah user sudah login via index.php
if (!isset($_SESSION['login_simrs'])) {
    header("Location: index.php");
    exit;
}

// 2. ATUR BATAS WAKTU INAKTIF (900 detik = 15 Menit)
$durasi_timeout = 900; 

if (isset($_SESSION['last_activity'])) {
    $selisih_waktu = time() - $_SESSION['last_activity'];
    
    // Jika durasi diam melebihi batas waktu
    if ($selisih_waktu > $durasi_timeout) {
        session_unset();
        session_destroy();
        header("Location: index.php?pesan=timeout");
        exit;
    }
}

// Perbarui waktu aktivitas terakhir
$_SESSION['last_activity'] = time();
?>