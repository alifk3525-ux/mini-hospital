<?php
include 'koneksi.php';

// Menangkap jenis antrean dari tombol yang diklik (RJ, RI, atau G)
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'RJ';

// Cek nomor antrean paling terakhir KHUSUS untuk jenis yang dipilih
// Perintah LIKE '$jenis-%' memastikan RJ tidak tercampur dengan RI
$cek_data = mysqli_query($koneksi, "SELECT nomor_antrean FROM antrean WHERE nomor_antrean LIKE '$jenis-%' ORDER BY id_antrean DESC LIMIT 1");
$data_terakhir = mysqli_fetch_assoc($cek_data);

if ($data_terakhir) {
    // Memisahkan teks "RJ-01" menjadi "RJ" dan "01"
    $pecah = explode('-', $data_terakhir['nomor_antrean']); 
    $nomor_terakhir = (int)$pecah[1]; // Ambil angka "01"-nya saja
    $nomor_baru = $nomor_terakhir + 1;
} else {
    $nomor_baru = 1;
}

// Format gabungan: Contoh RJ-01, G-02
$kode_antrean = $jenis . "-" . sprintf("%02d", $nomor_baru);

// Simpan ke database
$simpan = mysqli_query($koneksi, "INSERT INTO antrean (nomor_antrean, status) VALUES ('$kode_antrean', 'Menunggu')");

if ($simpan) {
    header("Location: cetak_antrean.php?nomor=$kode_antrean");
    exit;
} else {
    echo "Gagal mengambil antrean!";
}
?>