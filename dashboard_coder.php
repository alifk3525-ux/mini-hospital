<?php
include 'koneksi.php';

if (isset($_POST['validasi_koding'])) {
    $reg = $_POST['no_registrasi'];
    $rm = $_POST['no_rm'];
    $diag_dok = $_POST['diagnosa_dokter'];
    $icd_utama = $_POST['kode_icd_utama']; 
    $icd_sekunder = $_POST['kode_icd_sekunder'];

    $pecah_utama = explode(" - ", $icd_utama);
    $kode_utama = $pecah_utama[0];
    $nama_utama = isset($pecah_utama[1]) ? $pecah_utama[1] : '';

    mysqli_query($koneksi, "INSERT INTO koding_coder (no_registrasi, no_rm, diagnosa_dokter, kode_icd10_utama, diagnosa_icd10_utama, kode_icd10_sekunder, status_klaim) 
              VALUES ('$reg', '$rm', '$diag_dok', '$kode_utama', '$nama_utama', '$icd_sekunder', 'Validated / Final')");

    mysqli_query($koneksi, "UPDATE kunjungan SET status_kunjungan='Selesai (Klaim Valid)' WHERE no_registrasi='$reg'");

    echo "<script>alert('Validasi Koding ICD-10 Sukses dan Dikunci untuk Klaim!'); window.location='dashboard_coder.php';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Coder - Validasi ICD-10</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; }
        .wadah { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 1100px; margin: 0 auto; }
        h2 { color: #1e3a8a; border-bottom: 2px solid #93c5fd; padding-bottom: 10px; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; vertical-align: top; }
        th { background: #2563eb; color: white; }
        input { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .btn-val { background: #10b981; color: white; border: none; padding: 8px 12px; font-weight: bold; border-radius: 4px; cursor: pointer; }
        .btn-val:hover { background: #059669; }
        .teks-dokter { background: #fef3c7; padding: 8px; border-left: 3px solid #f59e0b; margin-bottom: 6px; }
        .usulan-badge { background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; font-size: 11px; display: inline-block; font-weight: bold; }
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
        <h2>🏷️ Meja Coder: Tinjau Usulan & Validasi ICD-10</h2>
        <p style="color: #64748b;">Periksa catatan klinis dan usulan kode dari dokter. Coder dapat menyetujui atau memperbaikinya sebelum dikunci untuk klaim.</p>

        <table>
            <tr>
                <th>No. Reg & Pasien</th>
                <th>DPJP & Poli</th>
                <th>Diagnosa & Usulan Dokter</th>
                <th>Verifikasi & Koreksi Coder (ICD-10)</th>
                <th>Aksi</th>
            </tr>
            <?php
            $query = mysqli_query($koneksi, "SELECT k.no_registrasi, k.no_rm, k.tujuan_layanan, p.nama_lengkap, rm.dpjp, 
                     rm.diagnosa_utama, rm.icd_utama, 
                     rm.diagnosa_sekunder_1, rm.icd_sekunder_1,
                     rm.diagnosa_sekunder_2, rm.icd_sekunder_2
                     FROM kunjungan k 
                     JOIN pasien p ON k.no_rm = p.no_rm 
                     JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi 
                     WHERE k.status_kunjungan = 'Menunggu Koding'");

            if(mysqli_num_rows($query) == 0) {
                echo "<tr><td colspan='5' style='text-align:center; padding: 25px; color: #64748b;'>Tidak ada antrean rekam medis yang menunggu koding saat ini.</td></tr>";
            }

            while($d = mysqli_fetch_assoc($query)) {
            ?>
            <form method="POST">
                <input type="hidden" name="no_registrasi" value="<?php echo $d['no_registrasi']; ?>">
                <input type="hidden" name="no_rm" value="<?php echo $d['no_rm']; ?>">
                <input type="hidden" name="diagnosa_dokter" value="<?php echo $d['diagnosa_utama']; ?>">
                <tr>
                    <td>
                        <strong style="color: #2563eb;"><?php echo $d['no_registrasi']; ?></strong><br>
                        <strong><?php echo $d['nama_lengkap']; ?></strong><br>
                        <small>RM: <?php echo $d['no_rm']; ?></small>
                    </td>
                    <td>
                        <?php echo $d['tujuan_layanan']; ?><br>
                        <small style="color: #64748b;">DPJP: <?php echo $d['dpjp']; ?></small>
                    </td>
                    <td style="width: 35%;">
                        <div class="teks-dokter">
                            <strong>Diagnosa Utama:</strong> "<?php echo $d['diagnosa_utama']; ?>"<br>
                            <?php if(!empty($d['icd_utama'])): ?>
                                <span class="usulan-badge">Usulan ICD: <?php echo $d['icd_utama']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if(!empty($d['diagnosa_sekunder_1'])): ?>
                            <div style="font-size: 12px; color: #475569; margin-top: 4px;">
                                <strong>Sekunder 1:</strong> <?php echo $d['diagnosa_sekunder_1']; ?> 
                                <?php if(!empty($d['icd_sekunder_1'])) echo "<i>(" . $d['icd_sekunder_1'] . ")</i>"; ?>
                            </div>
                        <?php endif; ?>

                        <?php if(!empty($d['diagnosa_sekunder_2'])): ?>
                            <div style="font-size: 12px; color: #475569; margin-top: 2px;">
                                <strong>Sekunder 2:</strong> <?php echo $d['diagnosa_sekunder_2']; ?> 
                                <?php if(!empty($d['icd_sekunder_2'])) echo "<i>(" . $d['icd_sekunder_2'] . ")</i>"; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="width: 25%;">
                        <label style="font-size: 11px; font-weight: bold; color: #1e3a8a;">Kode ICD-10 Utama (Final):</label>
                        <input type="text" name="kode_icd_utama" list="kamus-icd10" value="<?php echo $d['icd_utama']; ?>" placeholder="Validasi / koreksi kode..." required style="margin-bottom: 8px;">
                        
                        <label style="font-size: 11px; font-weight: bold; color: #64748b;">Kode ICD-10 Sekunder:</label>
                        <input type="text" name="kode_icd_sekunder" list="kamus-icd10" placeholder="Penyakit penyerta..." value="<?php echo $d['icd_sekunder_1']; ?>">
                    </td>
                    <td style="text-align: center; vertical-align: middle;">
                        <button type="submit" name="validasi_koding" class="btn-val" onclick="return confirm('Validasi kode ICD-10 ini dan kunci untuk klaim?');">✅ Validasi & Kunci</button>
                    </td>
                </tr>
            </form>
            <?php } ?>
        </table>
    </div>
</body>
</html>