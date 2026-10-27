<?php
include 'koneksi.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // Ubah status dari Menunggu menjadi Dipanggil
    $update = mysqli_query($koneksi, "UPDATE antrean SET status='Dipanggil' WHERE id_antrean='$id'");
    
    if ($update) {
        header("Location: dasbor_petugas.php");
    } else {
        echo "Gagal mengupdate status.";
    }
}
?>