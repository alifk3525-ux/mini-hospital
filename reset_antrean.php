<?php
include 'koneksi.php';

// Menghapus seluruh data antrean dan mengulang ID dari awal
$hapus = mysqli_query($koneksi, "TRUNCATE TABLE antrean");

if($hapus) {
    // Arahkan kembali ke dasbor petugas, BUKAN ke index.php
    echo "<script>
            alert('Seluruh data antrean berhasil direset dari angka 0!');
            window.location.href = 'dasbor_petugas.php';
          </script>";
} else {
    echo "<script>
            alert('Gagal mereset antrean!');
            window.location.href = 'dasbor_petugas.php';
          </script>";
}
?>