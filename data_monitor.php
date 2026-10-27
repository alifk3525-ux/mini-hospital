<?php
include 'koneksi.php';

// Ambil antrean terakhir status 'Dipanggil' untuk Rawat Jalan (RJ)
$q_rj = mysqli_query($koneksi, "SELECT nomor_antrean FROM antrean WHERE status='Dipanggil' AND nomor_antrean LIKE 'RJ-%' ORDER BY id_antrean DESC LIMIT 1");
$d_rj = mysqli_fetch_assoc($q_rj);

// Ambil untuk Rawat Inap (RI)
$q_ri = mysqli_query($koneksi, "SELECT nomor_antrean FROM antrean WHERE status='Dipanggil' AND nomor_antrean LIKE 'RI-%' ORDER BY id_antrean DESC LIMIT 1");
$d_ri = mysqli_fetch_assoc($q_ri);

// Ambil untuk Informasi (G)
$q_g = mysqli_query($koneksi, "SELECT nomor_antrean FROM antrean WHERE status='Dipanggil' AND nomor_antrean LIKE 'G-%' ORDER BY id_antrean DESC LIMIT 1");
$d_g = mysqli_fetch_assoc($q_g);

// Kirimkan dalam format JSON
echo json_encode([
    'rj' => $d_rj ? $d_rj['nomor_antrean'] : '---',
    'ri' => $d_ri ? $d_ri['nomor_antrean'] : '---',
    'g'  => $d_g ? $d_g['nomor_antrean'] : '---'
]);
?>