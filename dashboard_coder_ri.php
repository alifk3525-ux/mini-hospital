<?php
include 'koneksi.php';

if (isset($_POST['validasi_koding_ri'])) {
    $reg = mysqli_real_escape_string($koneksi, $_POST['no_registrasi']);
    $rm = mysqli_real_escape_string($koneksi, $_POST['no_rm']);
    $diag_dok = mysqli_real_escape_string($koneksi, $_POST['diagnosa_dokter']);
    
    $iau = mysqli_real_escape_string($koneksi, $_POST['icd_utama']);
    $is1 = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_1']);
    $is2 = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_2']);
    $is3 = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_3']);
    $is4 = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_4']);

    // 1. Simpan kode final ke rekam medis rawat inap
    mysqli_query($koneksi, "UPDATE rekam_medis SET 
        icd_utama = '$iau',
        icd_sekunder_1 = '$is1',
        icd_sekunder_2 = '$is2',
        icd_sekunder_3 = '$is3',
        icd_sekunder_4 = '$is4'
        WHERE no_registrasi = '$reg'");

    // 2. Masukkan ke tabel master Koding
    $pecah_utama = explode(" - ", $iau);
    $kode_utama = $pecah_utama[0];
    $nama_utama = isset($pecah_utama[1]) ? $pecah_utama[1] : '';
    $gabungan_sekunder = trim("$is1, $is2, $is3, $is4", ", ");

    mysqli_query($koneksi, "INSERT INTO koding_coder (no_registrasi, no_rm, diagnosa_dokter, kode_icd10_utama, diagnosa_icd10_utama, kode_icd10_sekunder, status_klaim) 
              VALUES ('$reg', '$rm', '$diag_dok', '$kode_utama', '$nama_utama', '$gabungan_sekunder', 'Validated / Final')");

    // 3. Ubah status kunjungan menjadi 'Selesai (Klaim Valid)'
    mysqli_query($koneksi, "UPDATE kunjungan SET status_kunjungan = 'Selesai (Klaim Valid)' WHERE no_registrasi = '$reg'");

    echo "<script>alert('Koding Rawat Inap Berhasil Divalidasi & Dikunci untuk Klaim!'); window.location='dashboard_coder_ri.php';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Meja Coder Rawat Inap</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; }
        .wadah { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 1200px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; vertical-align: top; }
        th { background: #b45309; color: white; }
        tr:hover { background-color: #f8fafc; }
        .btn-val { background: #10b981; color: white; padding: 8px 14px; border-radius: 4px; font-weight: bold; border: none; cursor: pointer; width: 100%; margin-top: 10px; }
        .btn-val:hover { background: #059669; }
        input[type="text"] { padding: 6px; width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; margin-bottom: 6px; font-size: 13px; }
        .diagnosa-box { background: #fef3c7; padding: 8px; border-left: 3px solid #d97706; margin-bottom: 6px; font-size: 12px; }
    </style>
</head>
<body>

    <datalist id="kamus-icd10">
        <?php
        $q_icd = mysqli_query($koneksi, "SELECT * FROM icd10");
        while($icd = mysqli_fetch_assoc($q_icd)) {
            echo "<option value='".$icd['kode']." - ".$icd['diagnosa']."'>";
        }
        ?>
    </datalist>

    <div class="wadah">
        <h2 style="color: #b45309; margin-top: 0; border-bottom: 2px solid #d97706; padding-bottom: 10px;">🏷️ Meja Coder - Validasi ICD-10 Rawat Inap (RI)</h2>
        <p style="color: #64748b; font-size: 14px;">Menampilkan pasien rawat inap yang sudah pulang dan menunggu verifikasi koding akhir untuk klaim.</p>
        
        <table>
            <tr>
                <th>No. Reg & Pasien</th>
                <th>Kamar & Status Keluar</th>
                <th>Diagnosis Dokter (Utama & Sekunder)</th>
                <th style="width: 25%;">Validasi Kode ICD-10 & Aksi</th>
            </tr>
            <?php
            // Memanggil data standar tanpa logika kolom ganda yang membingungkan
            $query = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, rm.* 
                     FROM kunjungan k 
                     JOIN pasien p ON k.no_rm = p.no_rm 
                     LEFT JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi
                     WHERE k.jenis_layanan = 'Rawat Inap' 
                     AND k.status_kunjungan = 'Menunggu Koding'
                     ORDER BY k.tgl_masuk ASC");
            
            if(mysqli_num_rows($query) == 0) {
                echo "<tr><td colspan='4' style='text-align:center; padding: 25px; color: #64748b;'>Tidak ada antrean koding Rawat Inap saat ini.</td></tr>";
            }

            while($d = mysqli_fetch_assoc($query)) {
                $status_keluar = !empty($d['status_keluar']) ? $d['status_keluar'] : 'Belum Ditentukan';
            ?>
            <tr>
                <td>
                    <strong style="color: #b45309;"><?php echo $d['no_registrasi']; ?></strong><br>
                    <strong><?php echo $d['nama_lengkap']; ?></strong> (<?php echo $d['jk']; ?>)<br>
                    <small style="color:#64748b;">RM: <?php echo $d['no_rm']; ?></small>
                </td>
                <td>
                    <span style="background: #fef3c7; color: #b45309; padding: 3px 6px; border-radius: 4px; font-weight: bold;"><?php echo $d['tujuan_layanan']; ?></span><br><br>
                    <strong>Status Keluar:</strong><br>
                    <span style="color: #0369a1; font-weight: bold;"><?php echo $status_keluar; ?></span>
                </td>
                <td>
                    <div class="diagnosa-box">
                        <strong>Utama:</strong> <?php echo isset($d['diagnosa_utama']) && $d['diagnosa_utama'] ? $d['diagnosa_utama'] : '-'; ?><br>
                        <i style="color: #64748b;">Usulan ICD: <?php echo isset($d['icd_utama']) && $d['icd_utama'] ? $d['icd_utama'] : '-'; ?></i>
                    </div>
                    <?php if(!empty($d['diagnosa_sekunder_1'])): ?>
                    <div style="font-size: 11px; color: #334155; margin-top: 4px;">
                        <strong>Sek 1:</strong> <?php echo $d['diagnosa_sekunder_1']; ?> <i>(<?php echo $d['icd_sekunder_1']; ?>)</i>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($d['diagnosa_sekunder_2'])): ?>
                    <div style="font-size: 11px; color: #334155; margin-top: 2px;">
                        <strong>Sek 2:</strong> <?php echo $d['diagnosa_sekunder_2']; ?> <i>(<?php echo $d['icd_sekunder_2']; ?>)</i>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($d['diagnosa_sekunder_3'])): ?>
                    <div style="font-size: 11px; color: #334155; margin-top: 2px;">
                        <strong>Sek 3:</strong> <?php echo $d['diagnosa_sekunder_3']; ?> <i>(<?php echo $d['icd_sekunder_3']; ?>)</i>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($d['diagnosa_sekunder_4'])): ?>
                    <div style="font-size: 11px; color: #334155; margin-top: 2px;">
                        <strong>Sek 4:</strong> <?php echo $d['diagnosa_sekunder_4']; ?> <i>(<?php echo $d['icd_sekunder_4']; ?>)</i>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="no_registrasi" value="<?php echo $d['no_registrasi']; ?>">
                        <input type="hidden" name="no_rm" value="<?php echo $d['no_rm']; ?>">
                        <input type="hidden" name="diagnosa_dokter" value="<?php echo isset($d['diagnosa_utama']) ? $d['diagnosa_utama'] : ''; ?>">
                        
                        <label style="font-size: 11px; font-weight: bold; color: #b45309;">ICD-10 Utama (Final):</label>
                        <input type="text" name="icd_utama" list="kamus-icd10" value="<?php echo isset($d['icd_utama']) ? $d['icd_utama'] : ''; ?>" placeholder="Kode utama..." required>
                        
                        <label style="font-size: 11px; font-weight: bold; color: #64748b;">Sekunder 1:</label>
                        <input type="text" name="icd_sekunder_1" list="kamus-icd10" value="<?php echo isset($d['icd_sekunder_1']) ? $d['icd_sekunder_1'] : ''; ?>" placeholder="...">

                        <label style="font-size: 11px; font-weight: bold; color: #64748b;">Sekunder 2:</label>
                        <input type="text" name="icd_sekunder_2" list="kamus-icd10" value="<?php echo isset($d['icd_sekunder_2']) ? $d['icd_sekunder_2'] : ''; ?>" placeholder="...">

                        <label style="font-size: 11px; font-weight: bold; color: #64748b;">Sekunder 3:</label>
                        <input type="text" name="icd_sekunder_3" list="kamus-icd10" value="<?php echo isset($d['icd_sekunder_3']) ? $d['icd_sekunder_3'] : ''; ?>" placeholder="...">

                        <label style="font-size: 11px; font-weight: bold; color: #64748b;">Sekunder 4:</label>
                        <input type="text" name="icd_sekunder_4" list="kamus-icd10" value="<?php echo isset($d['icd_sekunder_4']) ? $d['icd_sekunder_4'] : ''; ?>" placeholder="...">
                        
                        <button type="submit" name="validasi_koding_ri" class="btn-val" onclick="return confirm('Validasi dan kunci koding rawat inap ini?');">✔ Validasi & Kunci</button>
                    </form>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>