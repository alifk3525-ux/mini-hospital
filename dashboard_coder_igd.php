<?php
include 'koneksi.php';
if (isset($_POST['validasi_koding_igd'])) {
    $reg = $_POST['no_registrasi'];
    $kode_final = mysqli_real_escape_string($koneksi, $_POST['kode_icd_final']);
    
    mysqli_query($koneksi, "UPDATE rekam_medis SET diagnosa_utama = '$kode_final' WHERE no_registrasi = '$reg'");
    mysqli_query($koneksi, "UPDATE kunjungan SET status_kunjungan = 'Selesai / Klaim Siap' WHERE no_registrasi = '$reg'");
    echo "<script>alert('Koding IGD Berhasil Divalidasi!'); window.location='dashboard_coder_igd.php';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Meja Coder IGD</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; }
        .wadah { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { border: 1px solid #e2e8f0; padding: 12px; text-align: left; vertical-align: middle; }
        th { background: #059669; color: white; }
        tr:hover { background-color: #f8fafc; }
        .btn-val { background: #10b981; color: white; padding: 6px 12px; border-radius: 4px; font-weight: bold; border: none; cursor: pointer; }
        .btn-val:hover { background: #047857; }
        input[type="text"] { padding: 6px; width: 220px; border: 1px solid #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="wadah">
        <h2 style="color: #047857; margin-top: 0; border-bottom: 2px solid #059669; padding-bottom: 10px;">🏷️ Meja Coder - Validasi ICD-10 IGD (Gawat Darurat)</h2>
        <p style="color: #64748b; font-size: 14px;">Hanya menampilkan pasien berstatus <strong>IGD</strong> yang menunggu koding.</p>
        
        <table>
            <tr>
                <th>Tgl Masuk</th>
                <th>No. RM & Nama Pasien</th>
                <th>No. Registrasi</th>
                <th>Triase / Keterangan</th>
                <th>Catatan Klinis Dokter</th>
                <th>Validasi Kode ICD-10 Final</th>
            </tr>
            <?php
            $query = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, rm.asesmen, rm.diagnosa_utama as diag_dokter, rm.icd10_usulan_dokter 
                     FROM kunjungan k 
                     JOIN pasien p ON k.no_rm = p.no_rm 
                     LEFT JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi
                     WHERE k.jenis_layanan = 'IGD' AND k.status_kunjungan = 'Menunggu Koding'
                     ORDER BY k.tgl_masuk ASC");
            
            if(mysqli_num_rows($query) == 0) {
                echo "<tr><td colspan='6' style='text-align:center; padding: 25px; color: #64748b;'>Tidak ada antrean koding IGD saat ini.</td></tr>";
            }

            while($d = mysqli_fetch_assoc($query)) {
            ?>
            <tr>
                <td><?php echo date('d-m-Y H:i', strtotime($d['tgl_masuk'])); ?></td>
                <td><strong><?php echo $d['nama_lengkap']; ?></strong><br><small style="color:#64748b;">RM: <?php echo $d['no_rm']; ?></small></td>
                <td style="font-weight: bold; color: #059669;"><?php echo $d['no_registrasi']; ?></td>
                <td><span style="background: #d1fae5; color: #065f46; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 12px;"><?php echo $d['tujuan_layanan']; ?></span></td>
                <td>
                    <strong>Asesmen:</strong> <?php echo $d['asesmen']; ?><br>
                    <strong>Catatan:</strong> <?php echo $d['diag_dokter']; ?>
                </td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="no_registrasi" value="<?php echo $d['no_registrasi']; ?>">
                        <input type="text" name="kode_icd_final" list="kamus-icd10" value="<?php echo $d['icd10_usulan_dokter']; ?>" placeholder="Masukkan kode final..." required>
                        <button type="submit" name="validasi_koding_igd" class="btn-val" style="margin-top: 5px;">✔ Validasi</button>
                    </form>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>

    <datalist id="kamus-icd10">
        <?php
        $q_icd = mysqli_query($koneksi, "SELECT * FROM icd10");
        while($icd = mysqli_fetch_assoc($q_icd)) {
            echo "<option value='".$icd['kode']." - ".$icd['diagnosa']."'>";
        }
        ?>
    </datalist>
</body>
</html>