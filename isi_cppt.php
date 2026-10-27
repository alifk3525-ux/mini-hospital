<?php
include 'koneksi.php';
$reg = isset($_GET['reg']) ? $_GET['reg'] : '';

$query = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, p.tgl_lahir, p.alamat 
         FROM kunjungan k JOIN pasien p ON k.no_rm = p.no_rm WHERE k.no_registrasi = '$reg'");
$pasien = mysqli_fetch_assoc($query);

if (isset($_POST['simpan_cppt'])) {
    $s = mysqli_real_escape_string($koneksi, $_POST['subjektif']);
    $o = mysqli_real_escape_string($koneksi, $_POST['objektif']);
    $a = mysqli_real_escape_string($koneksi, $_POST['asesmen']);
    $po = mysqli_real_escape_string($koneksi, $_POST['plan_obat']);
    $pno = mysqli_real_escape_string($koneksi, $_POST['plan_non_obat']);
    
    $diagnosa_bebas = mysqli_real_escape_string($koneksi, $_POST['diagnosa_dokter']);
    
    // Usulan diagnosa & ICD-10 terstruktur
    $du_teks = mysqli_real_escape_string($koneksi, $_POST['diagnosa_utama_teks']);
    $du_icd  = mysqli_real_escape_string($koneksi, $_POST['icd_utama']);
    
    $ds1_teks = mysqli_real_escape_string($koneksi, $_POST['diagnosa_sekunder_1_teks']);
    $ds1_icd  = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_1']);
    
    $ds2_teks = mysqli_real_escape_string($koneksi, $_POST['diagnosa_sekunder_2_teks']);
    $ds2_icd  = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_2']);
    
    $ds3_teks = mysqli_real_escape_string($koneksi, $_POST['diagnosa_sekunder_3_teks']);
    $ds3_icd  = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_3']);
    
    $ds4_teks = mysqli_real_escape_string($koneksi, $_POST['diagnosa_sekunder_4_teks']);
    $ds4_icd  = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_4']);

    $rm = $pasien['no_rm'];
    $dpjp = $pasien['dpjp'];

    // Simpan ke rekam medis (kita gabungkan atau simpan terstruktur ke kolom utama)
    mysqli_query($koneksi, "INSERT INTO rekam_medis 
              (no_registrasi, no_rm, dpjp, subjektif, objektif, asesmen, plan_obat, plan_non_obat, diagnosa_utama, icd10_usulan_dokter) 
              VALUES ('$reg', '$rm', '$dpjp', '$s', '$o', '$a', '$po', '$pno', '$du_teks', '$du_icd')");
    
    // Kirim status ke meja coder
    mysqli_query($koneksi, "UPDATE kunjungan SET status_kunjungan='Menunggu Koding' WHERE no_registrasi='$reg'");
    
    echo "<script>alert('CPPT Berhasil Disimpan! Menunggu Validasi Coder.'); window.location='ruang_periksa.php?dpjp=$dpjp';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>E-Rekam Medis (CPPT) - Dokter</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; }
        .wadah { background: white; padding: 25px; border-radius: 8px; max-width: 950px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h3 { color: #2c3e50; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; margin-top: 0; }
        .info-pasien { background: #e2e8f0; padding: 15px; border-radius: 5px; margin-bottom: 20px; display: flex; justify-content: space-between; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #475569; }
        textarea, input { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; font-size: 14px; }
        button { background: #10b981; color: white; border: none; padding: 12px; font-weight: bold; cursor: pointer; border-radius: 5px; width: 100%; font-size: 16px; margin-top: 15px; }
        button:hover { background: #059669; }
        
        .section-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; margin-bottom: 15px; }
        .row-diagnosa { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        .row-diagnosa label { width: 130px; flex-shrink: 0; margin-bottom: 0; font-size: 12px; }
        .row-diagnosa input.teks-diag { flex: 2; }
        .row-diagnosa input.kode-icd { flex: 1; background: #fdf8f6; }
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
        <h3>📝 Pengisian CPPT & Diagnosa Terstruktur (Dokter)</h3>
        <div class="info-pasien">
            <div>
                <strong><?php echo $pasien['nama_lengkap']; ?> (<?php echo $pasien['jk']; ?>)</strong><br>
                RM: <?php echo $pasien['no_rm']; ?>
            </div>
            <div style="text-align: right;">
                <strong style="color: #3b82f6;"><?php echo $reg; ?></strong><br>
                Keluhan: <i><?php echo $pasien['keluhan_utama']; ?></i>
            </div>
        </div>

        <form method="POST">
            <div class="grid-2">
                <div>
                    <label>S - Subjektif</label>
                    <textarea name="subjektif" rows="3" required></textarea>
                </div>
                <div>
                    <label>O - Objektif</label>
                    <textarea name="objektif" rows="3" required></textarea>
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label>A - Asesmen / Kesimpulan Klinis</label>
                <textarea name="asesmen" rows="3" required></textarea>
            </div>

            <!-- BAGIAN DIAGNOSA & KODE ICD-10 BERPASANGAN -->
            <div class="section-box">
                <h4 style="margin-top: 0; color: #1e3a8a; font-size: 15px; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px;">
                    🏷️ Perincian Diagnosa & Usulan Kode ICD-10 (Opsional)
                </h4>

                <!-- Diagnosa Utama -->
                <div class="row-diagnosa">
                    <label style="color: #dc2626; font-weight: bold;">Diagnosa Utama:</label>
                    <input type="text" name="diagnosa_utama_teks" class="teks-diag" placeholder="Nama/deskripsi penyakit utama..." required>
                    <input type="text" name="icd_utama" class="kode-icd" list="kamus-icd10" placeholder="Pilih/ketik ICD-10...">
                </div>

                <!-- Diagnosa Sekunder 1 -->
                <div class="row-diagnosa">
                    <label style="color: #2563eb;">Diagnosa Sekunder 1:</label>
                    <input type="text" name="diagnosa_sekunder_1_teks" class="teks-diag" placeholder="Penyakit penyerta 1...">
                    <input type="text" name="icd_sekunder_1" class="kode-icd" list="kamus-icd10" placeholder="Pilih/ketik ICD-10...">
                </div>

                <!-- Diagnosa Sekunder 2 -->
                <div class="row-diagnosa">
                    <label style="color: #2563eb;">Diagnosa Sekunder 2:</label>
                    <input type="text" name="diagnosa_sekunder_2_teks" class="teks-diag" placeholder="Penyakit penyerta 2...">
                    <input type="text" name="icd_sekunder_2" class="kode-icd" list="kamus-icd10" placeholder="Pilih/ketik ICD-10...">
                </div>

                <!-- Diagnosa Sekunder 3 -->
                <div class="row-diagnosa">
                    <label style="color: #2563eb;">Diagnosa Sekunder 3:</label>
                    <input type="text" name="diagnosa_sekunder_3_teks" class="teks-diag" placeholder="Penyakit penyerta 3...">
                    <input type="text" name="icd_sekunder_3" class="kode-icd" list="kamus-icd10" placeholder="Pilih/ketik ICD-10...">
                </div>

                <!-- Diagnosa Sekunder 4 -->
                <div class="row-diagnosa">
                    <label style="color: #2563eb;">Diagnosa Sekunder 4:</label>
                    <input type="text" name="diagnosa_sekunder_4_teks" class="teks-diag" placeholder="Penyakit penyerta 4...">
                    <input type="text" name="icd_sekunder_4" class="kode-icd" list="kamus-icd10" placeholder="Pilih/ketik ICD-10...">
                </div>
            </div>

            <div class="grid-2">
                <div>
                    <label>P - Planning (Terapi Obat)</label>
                    <textarea name="plan_obat" rows="3" placeholder="R/ Paracetamol..."></textarea>
                </div>
                <div>
                    <label>P - Planning (Tindakan / Non-Obat)</label>
                    <textarea name="plan_non_obat" rows="3" placeholder="Edukasi..."></textarea>
                </div>
            </div>

            <button type="submit" name="simpan_cppt">💾 Simpan & Kirim ke Meja Coder</button>
        </form>
    </div>
</body>
</html>