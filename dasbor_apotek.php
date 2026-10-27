<?php
include 'koneksi.php';

// Proses saat apoteker menekan tombol "Serahkan Obat"
if (isset($_POST['serahkan_obat'])) {
    $no_reg = $_POST['no_registrasi'];
    // Ubah status kunjungan menjadi selesai sepenuhnya (atau selesai farmasi)
    mysqli_query($koneksi, "UPDATE kunjungan SET status_kunjungan='Selesai (Pulang)' WHERE no_registrasi='$no_reg'");
    echo "<script>alert('Obat telah diracik dan diserahkan kepada pasien!'); window.location='dasbor_apotek.php';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dasbor Farmasi - Mini Hospital</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; }
        .wadah { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 1100px; margin: 0 auto; }
        h2 { color: #16a34a; border-bottom: 2px solid #bbf7d0; padding-bottom: 10px; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { border: 1px solid #e2e8f0; padding: 12px; text-align: left; vertical-align: top; }
        th { background: #16a34a; color: white; }
        tr:nth-child(even) { background-color: #f0fdf4; }
        .resep-box { background: #fef3c7; padding: 12px; border-left: 4px solid #f59e0b; font-family: monospace; font-size: 13px; white-space: pre-wrap; }
        .btn-selesai { background: #3b82f6; color: white; border: none; padding: 8px 14px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-selesai:hover { background: #2563eb; }
    </style>
</head>
<body>

    <div class="wadah">
        <h2>💊 Antrean Resep Elektronik (E-Prescription) - Instalasi Farmasi</h2>
        <p style="color: #64748b; font-size: 14px;">Daftar resep obat dari ruang pemeriksaan dokter yang siap untuk divalidasi dan diserahkan kepada pasien.</p>

        <table>
            <tr>
                <th>Data Pasien & DPJP</th>
                <th>Rincian Resep (Plan Obat)</th>
                <th>Status / Aksi Farmasi</th>
            </tr>
            <?php
            // Menarik data kunjungan yang sudah diperiksa (memiliki plan obat) dan belum diserahkan obatnya
            $query = mysqli_query($koneksi, "SELECT k.no_registrasi, p.nama_lengkap, p.no_rm, k.tujuan_layanan, rm.dpjp, rm.plan_obat, k.status_kunjungan 
                     FROM kunjungan k 
                     JOIN pasien p ON k.no_rm = p.no_rm 
                     JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi 
                     WHERE rm.plan_obat != '' 
                     AND k.status_kunjungan NOT IN ('Selesai (Pulang)', 'Selesai (Klaim Valid)', 'Batal')
                     ORDER BY rm.tgl_periksa ASC");
            
            if(mysqli_num_rows($query) == 0) {
                echo "<tr><td colspan='3' style='text-align:center; padding: 25px; color: #64748b;'>Belum ada resep obat baru yang masuk dari ruang periksa dokter.</td></tr>";
            }
            
            while($d = mysqli_fetch_assoc($query)) {
            ?>
            <tr>
                <td style="width: 25%;">
                    <strong style="font-size: 16px; color: #1e3a8a;"><?php echo $d['nama_lengkap']; ?></strong><br>
                    <small>RM: <?php echo $d['no_rm']; ?></small><br>
                    <span style="color: #059669; font-weight: bold; font-size: 12px;"><?php echo $d['tujuan_layanan']; ?></span><br><br>
                    <span style="color: #64748b; font-size: 12px;">Dokter: <?php echo $d['dpjp']; ?></span>
                </td>
                <td style="width: 50%;">
                    <div class="resep-box"><?php echo $d['plan_obat']; ?></div>
                </td>
                <td style="width: 25%; text-align: center; vertical-align: middle;">
                    <div style="margin-bottom: 8px; font-size: 11px; font-weight: bold; color: #d97706; background: #fef3c7; padding: 4px; border-radius: 4px;">
                        Status: <?php echo $d['status_kunjungan']; ?>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="no_registrasi" value="<?php echo $d['no_registrasi']; ?>">
                        <button type="submit" name="serahkan_obat" class="btn-selesai" onclick="return confirm('Pastikan obat sudah diracik dan diserahkan kepada pasien!');">✅ Selesai & Serahkan Obat</button>
                    </form>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>

</body>
</html>