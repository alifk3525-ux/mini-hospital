<?php
// Menerima data dari dasbor petugas secara diam-diam
if (isset($_GET['nomor']) && isset($_GET['poli'])) {
    $nomor = $_GET['nomor'];
    $poli = $_GET['poli'];
    
    // Format: RJ-01|Rawat Jalan|1700000000
    // Waktu (time) ditambahkan agar file teks selalu terdeteksi berubah
    $data = $nomor . "|" . $poli . "|" . time();
    file_put_contents("pemicu_suara.txt", $data);
}
?>