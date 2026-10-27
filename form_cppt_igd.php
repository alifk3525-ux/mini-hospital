<?php
include 'koneksi.php';
$reg = isset($_GET['reg']) ? mysqli_real_escape_string($koneksi, $_GET['reg']) : '';

$q = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, p.tgl_lahir, p.cara_bayar as cara_bayar_pasien 
                            FROM kunjungan k 
                            JOIN pasien p ON k.no_rm = p.no_rm 
                            WHERE k.no_registrasi = '$reg'");
$pasien = mysqli_fetch_assoc($q);

if (!$pasien) {
    echo "<script>alert('Data kunjungan IGD tidak ditemukan!'); window.location='ruang_periksa_igd.php';</script>";
    exit;
}

if(isset($_POST['simpan_igd'])) {
    $s = mysqli_real_escape_string($koneksi, $_POST['subjektif']);
    $o = mysqli_real_escape_string($koneksi, $_POST['objektif']);
    $a = mysqli_real_escape_string($koneksi, $_POST['asesmen']);
    $p = mysqli_real_escape_string($koneksi, $_POST['planning']);
    
    $du_teks = mysqli_real_escape_string($koneksi, $_POST['diagnosa_utama_teks']);
    $du_icd  = mysqli_real_escape_string($koneksi, $_POST['icd_utama']);
    
    $ds1_teks = mysqli_real_escape_string($koneksi, $_POST['diagnosa_sekunder_1_teks']);
    $is1_icd  = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_1']);
    
    $ds2_teks = mysqli_real_escape_string($koneksi, $_POST['diagnosa_sekunder_2_teks']);
    $is2_icd  = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_2']);
    
    $ds3_teks = mysqli_real_escape_string($koneksi, $_POST['diagnosa_sekunder_3_teks']);
    $is3_icd  = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_3']);
    
    $ds4_teks = mysqli_real_escape_string($koneksi, $_POST['diagnosa_sekunder_4_teks']);
    $is4_icd  = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_4']);

    $status_keluar = mysqli_real_escape_string($koneksi, $_POST['status_keluar']);
    $rm = $pasien['no_rm'];
    $dpjp = $pasien['dpjp'];

    // 1. Simpan/Perbarui Rekam Medis IGD
    $cek_rm = mysqli_query($koneksi, "SELECT id_rm FROM rekam_medis WHERE no_registrasi = '$reg'");
    if(mysqli_num_rows($cek_rm) > 0) {
        mysqli_query($koneksi, "UPDATE rekam_medis SET 
            subjektif = '$s', objektif = '$o', asesmen = '$a', plan_obat = '$p',
            diagnosa_utama = '$du_teks', icd_utama = '$du_icd',
            diagnosa_sekunder_1 = '$ds1_teks', icd_sekunder_1 = '$is1_icd',
            diagnosa_sekunder_2 = '$ds2_teks', icd_sekunder_2 = '$is2_icd',
            diagnosa_sekunder_3 = '$ds3_teks', icd_sekunder_3 = '$is3_icd',
            diagnosa_sekunder_4 = '$ds4_teks', icd_sekunder_4 = '$is4_icd',
            status_keluar = '$status_keluar'
            WHERE no_registrasi = '$reg'");
    } else {
        mysqli_query($koneksi, "INSERT INTO rekam_medis (no_registrasi, no_rm, dpjp, subjektif, objektif, asesmen, plan_obat, diagnosa_utama, icd_utama, diagnosa_sekunder_1, icd_sekunder_1, diagnosa_sekunder_2, icd_sekunder_2, diagnosa_sekunder_3, icd_sekunder_3, diagnosa_sekunder_4, icd_sekunder_4, status_keluar) 
                  VALUES ('$reg', '$rm', '$dpjp', '$s', '$o', '$a', '$p', '$du_teks', '$du_icd', '$ds1_teks', '$is1_icd', '$ds2_teks', '$is2_icd', '$ds3_teks', '$is3_icd', '$ds4_teks', '$is4_icd', '$status_keluar')");
    }

    // 2. LOGIKA KEMATIAN & STATUS KUNJUNGAN IGD
    if (strpos($status_keluar, 'Meninggal') !== false) {
        $status_kunjungan_baru = 'Selesai (Meninggal)';
    } else {
        $status_kunjungan_baru = 'Menunggu Koding';
    }

    // Update status kunjungan dan tgl_keluar di tabel kunjungan
    mysqli_query($koneksi, "UPDATE kunjungan SET 
        status_kunjungan = '$status_kunjungan_baru',
        tgl_keluar = NOW() 
        WHERE no_registrasi = '$reg'");

    // 3. Lepas kasur Triase IGD agar kembali kosong
    $kamar_igd = $pasien['tujuan_layanan'];
    mysqli_query($koneksi, "UPDATE kamar_rawat SET terisi = GREATEST(0, terisi - 1) WHERE nama_ruangan = '$kamar_igd'");

    // 4. JIKA STATUS "DIRAWAT INAP" -> TERBITKAN SPRI!
    if ($status_keluar == 'Dirawat Inap') {
        $dpjp_bangsal = mysqli_real_escape_string($koneksi, $_POST['dpjp_bangsal']);
        $catatan_spri = mysqli_real_escape_string($koneksi, $_POST['catatan_spri']);
        $no_spri = "SPRI-" . date('Ymd') . "-" . rand(100, 999);

        mysqli_query($koneksi, "INSERT INTO spri (no_spri, no_registrasi_asal, no_rm, unit_asal, dokter_perujuk, dpjp_bangsal, diagnosa_indikasi, catatan_khusus, status_spri)
            VALUES ('$no_spri', '$reg', '$rm', 'IGD', '$dpjp', '$dpjp_bangsal', '$du_teks', '$catatan_spri', 'Menunggu Kamar')");

        echo "<script>alert('SPRI Berhasil Diterbitkan ($no_spri)! Kasur IGD telah dikosongkan kembali. Pasien kini menunggu penempatan kamar di Admisi Rawat Inap.'); window.location='ruang_periksa_igd.php';</script>";
        exit;
    }

    // 5. NOTIFIKASI KHUSUS PASIEN MENINGGAL / PASIEN PULANG biasa
    if (strpos($status_keluar, 'Meninggal') !== false) {
        echo "<script>alert('Penanganan Pasien Meninggal di IGD Selesai! Status kunjungan dan tanggal kematian telah dicatat.'); window.location='ruang_periksa_igd.php';</script>";
        exit;
    }

    echo "<script>alert('Penanganan IGD Selesai! Kasur IGD telah dibebaskan. Berkas dikirim ke Coder IGD.'); window.location='ruang_periksa_igd.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Formulir Tindakan & CPPT IGD</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; padding: 20px; }
        .wadah { background: white; padding: 25px; border-radius: 8px; max-width: 950px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        label { display: block; font-weight: bold; margin: 10px 0 5px 0; font-size: 13px; color: #334155; }
        textarea, input, select { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; font-size: 14px; }
        button { background: #dc2626; color: white; border: none; padding: 12px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 20px; border-radius: 5px; font-size: 15px; }
        button:hover { background: #b91c1c; }
        .info-box { background: #fee2e2; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid #dc2626; }
        .section-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; margin-top: 15px; }
        .row-diagnosa { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        .row-diagnosa label { width: 140px; flex-shrink: 0; margin-bottom: 0; font-size: 12px; }
        .row-diagnosa input.teks-diag { flex: 2; }
        .row-diagnosa input.kode-icd { flex: 1; background: #fdf8f6; }
        #kotak-spri { display: none; background: #fef3c7; border: 2px dashed #d97706; padding: 15px; border-radius: 6px; margin-top: 15px; }
    </style>
    <script>
        function cekStatusKeluar() {
            var status = document.getElementById("status_keluar").value;
            var kotakSpri = document.getElementById("kotak-spri");
            var inputDpjp = document.getElementById("dpjp_bangsal");
            
            if (status === "Dirawat Inap") {
                kotakSpri.style.display = "block";
                inputDpjp.setAttribute("required", "required");
            } else {
                kotakSpri.style.display = "none";
                inputDpjp.removeAttribute("required");
            }
        }
    </script>
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
        <h3 style="margin-top: 0; color: #dc2626;">⚡ Lembar Tindakan & Observasi Gawat Darurat (IGD)</h3>
        
        <div class="info-box">
            <strong><?php echo $pasien['nama_lengkap']; ?> (<?php echo $pasien['jk']; ?>)</strong> | 
            Triase: <strong><?php echo $pasien['tujuan_layanan']; ?></strong> | 
            No. Reg: <strong><?php echo $reg; ?></strong><br>
            Keluhan: <i><?php echo $pasien['keluhan_utama']; ?></i>
        </div>

        <form method="POST">
            <label>S - Subjektif (Anamnesis Gawat Darurat)</label>
            <textarea name="subjektif" rows="2" required placeholder="Keluhan utama dan riwayat singkat..."></textarea>

            <label>O - Objektif (Tanda Vital & Pemeriksaan Fisik)</label>
            <textarea name="objektif" rows="2" required placeholder="Kesadaran, TD, Nadi, Pernapasan, Suhu..."></textarea>

            <label>A - Asesmen (Evaluasi Klinis)</label>
            <textarea name="asesmen" rows="2" required placeholder="Kesimpulan kondisi darurat pasien..."></textarea>

            <label>P - Planning (Tindakan Medis & Terapi Darurat)</label>
            <textarea name="planning" rows="2" required placeholder="Pemberian infus, oksigen, injeksi, dll..."></textarea>

            <!-- BAGIAN DIAGNOSA & KODE ICD-10 BERPASANGAN -->
            <div class="section-box">
                <h4 style="margin-top: 0; color: #dc2626; font-size: 15px; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px;">
                    📋 Perincian Diagnosa & Usulan Kode ICD-10 (Opsional)
                </h4>

                <div class="row-diagnosa">
                    <label style="color: #dc2626; font-weight: bold;">Diagnosa Utama:</label>
                    <input type="text" name="diagnosa_utama_teks" class="teks-diag" placeholder="Nama/deskripsi penyakit utama..." required>
                    <input type="text" name="icd_utama" class="kode-icd" list="kamus-icd10" placeholder="Opsional ICD-10...">
                </div>

                <div class="row-diagnosa">
                    <label style="color: #2563eb;">Diagnosa Sekunder 1:</label>
                    <input type="text" name="diagnosa_sekunder_1_teks" class="teks-diag" placeholder="Penyakit penyerta 1...">
                    <input type="text" name="icd_sekunder_1" class="kode-icd" list="kamus-icd10" placeholder="Opsional ICD-10...">
                </div>

                <div class="row-diagnosa">
                    <label style="color: #2563eb;">Diagnosa Sekunder 2:</label>
                    <input type="text" name="diagnosa_sekunder_2_teks" class="teks-diag" placeholder="Penyakit penyerta 2...">
                    <input type="text" name="icd_sekunder_2" class="kode-icd" list="kamus-icd10" placeholder="Opsional ICD-10...">
                </div>

                <div class="row-diagnosa">
                    <label style="color: #2563eb;">Diagnosa Sekunder 3:</label>
                    <input type="text" name="diagnosa_sekunder_3_teks" class="teks-diag" placeholder="Penyakit penyerta 3...">
                    <input type="text" name="icd_sekunder_3" class="kode-icd" list="kamus-icd10" placeholder="Opsional ICD-10...">
                </div>

                <div class="row-diagnosa">
                    <label style="color: #2563eb;">Diagnosa Sekunder 4:</label>
                    <input type="text" name="diagnosa_sekunder_4_teks" class="teks-diag" placeholder="Penyakit penyerta 4...">
                    <input type="text" name="icd_sekunder_4" class="kode-icd" list="kamus-icd10" placeholder="Opsional ICD-10...">
                </div>
            </div>

            <label style="margin-top: 15px; font-weight: bold; color: #1e293b;">Status Keluar Pasien dari IGD</label>
            <select name="status_keluar" id="status_keluar" onchange="cekStatusKeluar()" required>
                <option value="Pulang (Sembuh/Membaik)">Pulang (Sembuh / Membaik)</option>
                <option value="Dirawat Inap">Masuk Rawat Inap (Terbitkan SPRI & Transfer Bangsal)</option>
                <option value="Dirujuk ke RS Lain">Dirujuk ke Rumah Sakit Lain</option>
                <option value="Meninggal (DOA / Di IGD)">Meninggal (DOA / Meninggal di IGD)</option>
            </select>

            <!-- KOTAK TAMBAHAN KHUSUS SPRI RAWAT INAP -->
            <div id="kotak-spri">
                <h4 style="margin: 0 0 10px 0; color: #b45309;">📄 Penerbitan SPRI (Surat Perintah Rawat Inap)</h4>
                <p style="font-size: 13px; color: #92400e; margin-top: 0;">Pasien akan didaftarkan ke antrean transfer kamar bangsal dan kasur IGD akan otomatis dikosongkan.</p>
                
                <label>Dokter Penanggung Jawab Pelayanan (DPJP Bangsal)</label>
                <select name="dpjp_bangsal" id="dpjp_bangsal">
                    <option value="">-- Pilih DPJP Bangsal yang Dituju --</option>
                    <?php
                    $q_dok = mysqli_query($koneksi, "SELECT DISTINCT nama_dokter, poli FROM jadwal_dokter ORDER BY nama_dokter ASC");
                    while($dok = mysqli_fetch_assoc($q_dok)) {
                        echo "<option value='".$dok['nama_dokter']."'>".$dok['nama_dokter']." (".$dok['poli'].")</option>";
                    }
                    ?>
                </select>

                <label>Catatan Khusus / Indikasi Masuk Rawat Inap</label>
                <textarea name="catatan_spri" rows="2" placeholder="Contoh: Perlu observasi ketat TTV, terapi antibiotik intravena, bed rest total..."></textarea>
            </div>

            <button type="submit" name="simpan_igd">💾 Simpan Penanganan & Eksekusi Status Keluar</button>
        </form>
    </div>
</body>
</html>