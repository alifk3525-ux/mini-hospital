<?php
$host = "localhost"; // Nama server lokal bawaan XAMPP
$user = "root";      // Username default database XAMPP
$pass = "";          // Password default XAMPP (dibiarkan kosong)
$db   = "db_hospital"; // Nama database yang tadi kita buat

// Perintah untuk menyambungkan PHP ke MySQL
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Cek apakah koneksi berhasil atau gagal
if (!$koneksi) {
    die("Yah, koneksi gagal: " . mysqli_connect_error());
} else {
    //echo "Mantap! Koneksi ke database berhasil.";
}
?>