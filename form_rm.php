<?php
include 'koneksi.php';
$reg = isset($_GET['reg']) ? mysqli_real_escape_string($koneksi, $_GET['reg']) : '';

// Ambil data kunjungan dan pasien
$query = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, p.tgl_lahir 
         FROM kunjungan k JOIN pasien p ON k.no_rm = p.no_rm WHERE k.no_registrasi = '$reg'");
$pasien = mysqli_fetch_assoc($query);

if (!$pasien) {
    echo "<script>alert('Data kunjungan tidak ditemukan!'); window.location='dashboard_dokter.php';</script>";
    exit;
}

$no_rm = $pasien['no_rm'];

// Cek apakah rekam medis untuk registrasi ini sudah pernah diisi sebelumnya
$cek_rm = mysqli_query($koneksi, "SELECT * FROM rekam_medis WHERE no_registrasi = '$reg'");
$sudah_ada = mysqli_num_rows($cek_rm);
$data_rm = mysqli_fetch_assoc($cek_rm);

// TARIK DATA SKRINING AWAL / OBSERVASI PERAWAT
$q_skrining = mysqli_query($koneksi, "SELECT * FROM skrining_awal WHERE no_registrasi = '$reg'");
$skrining = mysqli_fetch_assoc($q_skrining);

// =========================================================================
// [PERBAIKAN 1]: TARIK DATA PENUNJANG (LABORATORIUM, RADIOLOGI, & APOTEK)
// =========================================================================

// A. Tarik Data Laboratorium
$q_lab = mysqli_query($koneksi, "SELECT o.no_order, o.status_lab, o.tgl_hasil, 
                                        d.nama_parameter, d.hasil, d.nilai_rujukan, d.satuan, d.status_nilai
                                 FROM order_lab o
                                 LEFT JOIN hasil_lab_detail d ON o.no_order = d.no_order
                                 WHERE (o.no_registrasi = '$reg' OR o.no_rm = '$no_rm') AND d.hasil IS NOT NULL AND d.hasil != ''
                                 ORDER BY o.tgl_order DESC");

// B. Tarik Data Radiologi
$q_rad = mysqli_query($koneksi, "SELECT * FROM order_radiologi 
                                 WHERE (no_registrasi = '$reg' OR no_rm = '$no_rm')
                                 ORDER BY tgl_order DESC");

// C. Tarik Data Resep Obat / Apotek
$q_resep = mysqli_query($koneksi, "SELECT rm.plan_obat, rm.tgl_periksa 
                                   FROM rekam_medis rm 
                                   WHERE (rm.no_registrasi = '$reg' OR rm.no_rm = '$no_rm') 
                                   AND rm.plan_obat != '' 
                                   ORDER BY rm.id_rm DESC");

// Logika isi otomatis Kolom O (Objektif) dari Skrining jika rekam medis belum pernah disimpan dokter
$isi_objektif = '';
if ($sudah_ada > 0) {
    $isi_objektif = $data_rm['objektif'];
} elseif ($skrining) {
    $isi_objektif = "TD: " . $skrining['tekanan_darah'] . " mmHg | N: " . $skrining['nadi'] . "x/m | S: " . $skrining['suhu'] . "°C | RR: " . $skrining['pernapasan'] . "x/m | SpO2: " . $skrining['spo2'] . "% | BB: " . $skrining['berat_badan'] . "kg | TB: " . $skrining['tinggi_badan'] . "cm (IMT: " . $skrining['imt'] . " - " . $skrining['status_gizi'] . ") | Nyeri: " . $skrining['skala_nyeri'] . "/10 (" . ($skrining['lokasi_nyeri'] ? $skrining['lokasi_nyeri'] : 'Tidak ada') . ")";
}

// Cek data SPRI jika sebelumnya sudah pernah dibuat
$cek_spri = mysqli_query($koneksi, "SELECT * FROM spri WHERE no_registrasi_asal = '$reg'");
$data_spri = mysqli_fetch_assoc($cek_spri);

// Proses Simpan atau Update
if (isset($_POST['simpan_rm'])) {
    $s = mysqli_real_escape_string($koneksi, $_POST['subjektif']);
    $o = mysqli_real_escape_string($koneksi, $_POST['objektif']);
    $a = mysqli_real_escape_string($koneksi, $_POST['asesmen']);
    $po = mysqli_real_escape_string($koneksi, $_POST['plan_obat']);
    $pno = mysqli_real_escape_string($koneksi, $_POST['plan_non_obat']);
    
    // Data 5 Baris Diagnosa & ICD
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

    $tindak_lanjut = mysqli_real_escape_string($koneksi, $_POST['tindak_lanjut']);
    $dpjp = $pasien['dpjp'];

    if ($sudah_ada > 0) {
        // Mode Update
        mysqli_query($koneksi, "UPDATE rekam_medis SET 
                  subjektif='$s', objektif='$o', asesmen='$a', 
                  plan_obat='$po', plan_non_obat='$pno', 
                  diagnosa_utama='$du_teks', icd_utama='$du_icd',
                  diagnosa_sekunder_1='$ds1_teks', icd_sekunder_1='$ds1_icd',
                  diagnosa_sekunder_2='$ds2_teks', icd_sekunder_2='$ds2_icd',
                  diagnosa_sekunder_3='$ds3_teks', icd_sekunder_3='$ds3_icd',
                  diagnosa_sekunder_4='$ds4_teks', icd_sekunder_4='$ds4_icd'
                  WHERE no_registrasi='$reg'");
    } else {
        // Mode Insert Baru
        mysqli_query($koneksi, "INSERT INTO rekam_medis 
                  (no_registrasi, no_rm, dpjp, subjektif, objektif, asesmen, plan_obat, plan_non_obat, 
                   diagnosa_utama, icd_utama, diagnosa_sekunder_1, icd_sekunder_1, 
                   diagnosa_sekunder_2, icd_sekunder_2, diagnosa_sekunder_3, icd_sekunder_3, 
                   diagnosa_sekunder_4, icd_sekunder_4) 
                  VALUES ('$reg', '$no_rm', '$dpjp', '$s', '$o', '$a', '$po', '$pno', 
                          '$du_teks', '$du_icd', '$ds1_teks', '$ds1_icd', 
                          '$ds2_teks', '$ds2_icd', '$ds3_teks', '$ds3_icd', 
                          '$ds4_teks', '$ds4_icd')");
        
        mysqli_query($koneksi, "UPDATE kunjungan SET status_kunjungan='Menunggu Koding' WHERE no_registrasi='$reg'");
    }

    // LOGIKA PENERBITAN SPRI RAWAT JALAN
    if ($tindak_lanjut == 'Rawat Inap') {
        $dpjp_bangsal = mysqli_real_escape_string($koneksi, $_POST['dpjp_bangsal']);
        $catatan_spri = mysqli_real_escape_string($koneksi, $_POST['catatan_spri']);

        if (!$data_spri) {
            $no_spri = "SPRI-" . date('Ymd') . "-" . rand(100, 999);
            mysqli_query($koneksi, "INSERT INTO spri (no_spri, no_registrasi_asal, no_rm, unit_asal, dokter_perujuk, dpjp_bangsal, diagnosa_indikasi, catatan_khusus, status_spri)
                VALUES ('$no_spri', '$reg', '$no_rm', 'Rawat Jalan', '$dpjp', '$dpjp_bangsal', '$du_teks', '$catatan_spri', 'Menunggu Kamar')");
            
            echo "<script>alert('Rekam Medis Tersimpan & SPRI ($no_spri) Berhasil Diterbitkan!'); window.location='ruang_periksa.php?dpjp=$dpjp';</script>";
            exit;
        } else {
            mysqli_query($koneksi, "UPDATE spri SET dpjp_bangsal='$dpjp_bangsal', diagnosa_indikasi='$du_teks', catatan_khusus='$catatan_spri' WHERE no_registrasi_asal='$reg'");
            echo "<script>alert('Rekam Medis dan Data SPRI Berhasil Diperbarui!'); window.location='ruang_periksa.php?dpjp=$dpjp';</script>";
            exit;
        }
    }

    echo "<script>alert('Rekam Medis Berhasil Disimpan!'); window.location='ruang_periksa.php?dpjp=$dpjp';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Rekam Medis (CPPT) - Rawat Jalan</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; color: #1e293b; }
        .wadah { background: white; padding: 25px; border-radius: 8px; max-width: 950px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header-form { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; margin-bottom: 15px; }
        .header-form h3 { color: #2c3e50; margin: 0; }
        .btn-aksi-header { display: flex; gap: 8px; }
        .btn-link { padding: 8px 12px; border-radius: 5px; text-decoration: none; font-size: 12px; font-weight: bold; color: white; display: inline-flex; align-items: center; }
        .btn-link:hover { opacity: 0.9; }
        .btn-skrining-link { background: #0284c7; }
        .btn-consent-link { background: #dc2626; }
        
        .info-pasien { background: #e2e8f0; padding: 15px; border-radius: 5px; margin-bottom: 15px; display: flex; justify-content: space-between; }
        .box-skrining { background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 5px solid #22c55e; padding: 12px 15px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .box-skrining-kosong { background: #fff7ed; border: 1px solid #fed7aa; border-left: 5px solid #f97316; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
        .badge-alergi { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; padding: 6px 12px; border-radius: 5px; font-weight: bold; margin-bottom: 15px; display: block; }
        
        /* [PERBAIKAN 2]: STYLING PANEL PENUNJANG MEDIS */
        .box-penunjang { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .title-penunjang { font-weight: bold; font-size: 13.5px; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .badge-normal { background: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-abnormal { background: #fee2e2; color: #b91c1c; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .table-sub { width: 100%; border-collapse: collapse; font-size: 12.5px; margin-bottom: 10px; background: white; }
        .table-sub th, .table-sub td { border: 1px solid #e2e8f0; padding: 6px 10px; text-align: left; }
        .table-sub th { background: #e2e8f0; color: #334155; font-weight: 600; }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #475569; }
        textarea, input, select { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; font-size: 14px; }
        button { background: #10b981; color: white; border: none; padding: 12px; font-weight: bold; cursor: pointer; border-radius: 5px; width: 100%; font-size: 16px; margin-top: 15px; }
        button:hover { background: #059669; }
        .section-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; margin-bottom: 15px; }
        .row-diagnosa { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        .row-diagnosa label { width: 130px; flex-shrink: 0; margin-bottom: 0; font-size: 12px; }
        .row-diagnosa input.teks-diag { flex: 2; }
        .row-diagnosa input.kode-icd { flex: 1; background: #fdf8f6; }
        #kotak-spri { display: none; background: #fffbeb; border: 2px dashed #d97706; padding: 15px; border-radius: 6px; margin-top: 15px; }
    </style>
    <script>
        function toggleSPRI() {
            var tindakLanjut = document.getElementById("tindak_lanjut").value;
            var kotakSpri = document.getElementById("kotak-spri");
            var dpjpBangsal = document.getElementById("dpjp_bangsal");

            if (tindakLanjut === "Rawat Inap") {
                kotakSpri.style.display = "block";
                dpjpBangsal.setAttribute("required", "required");
            } else {
                kotakSpri.style.display = "none";
                dpjpBangsal.removeAttribute("required");
            }
        }
        window.onload = function() {
            toggleSPRI();
        };
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
        <!-- HEADER DENGAN TOMBOL NAVIGASI DOKUMEN KLINIS -->
        <div class="header-form">
            <h3>📝 Form Rekam Medis & CPPT Poli (<?php echo ($sudah_ada > 0) ? 'Mode Edit / Koreksi' : 'Pemeriksaan Baru'; ?>)</h3>
            <div class="btn-aksi-header">
                <a href="skrining_awal.php?reg=<?php echo $reg; ?>" target="_blank" class="btn-link btn-skrining-link">🩺 Lembar Skrining</a>
                <a href="informed_consent.php?reg=<?php echo $reg; ?>" target="_blank" class="btn-link btn-consent-link">✍️ Informed Consent</a>
                <a href="order_lab.php?reg=<?php echo $reg; ?>" target="_blank" class="btn-link" style="background: #0284c7;">🔬 Order Lab</a>
                <a href="order_radiologi.php?reg=<?php echo $reg; ?>" target="_blank" class="btn-link" style="background: #0284c7;">📷 Order Radiologi</a>
            </div>
        </div>
        
        <!-- Header Identitas Pasien -->
        <div class="info-pasien">
            <div>
                <strong><?php echo $pasien['nama_lengkap']; ?> (<?php echo $pasien['jk']; ?>)</strong><br>
                RM: <strong><?php echo $pasien['no_rm']; ?></strong> | Poli: <?php echo $pasien['tujuan_layanan']; ?>
            </div>
            <div style="text-align: right;">
                <strong style="color: #3b82f6;"><?php echo $reg; ?></strong><br>
                Keluhan: <i><?php echo $pasien['keluhan_utama']; ?></i>
            </div>
        </div>

        <!-- NOTIFIKASI HASIL SKRINING AWAL PERAWAT -->
        <?php if($skrining): ?>
            <?php if(!empty($skrining['riwayat_alergi']) && strtolower($skrining['riwayat_alergi']) != 'tidak ada alergi' && strtolower($skrining['riwayat_alergi']) != 'tidak ada'): ?>
                <div class="badge-alergi">
                    ⚠️ <b>PERINGATAN ALERGI PASIEN:</b> <?php echo strtoupper($skrining['riwayat_alergi']); ?>
                </div>
            <?php endif; ?>

            <div class="box-skrining">
                <div style="font-weight: bold; color: #166534; margin-bottom: 4px;">
                    🩺 Hasil Skrining Masuk (Perawat: <?php echo $skrining['petugas_skrining']; ?> - <?php echo date('H:i', strtotime($skrining['waktu_skrining'])); ?> WIB)
                </div>
                <div>
                    <b>TTV:</b> TD <?php echo $skrining['tekanan_darah']; ?> mmHg, Nadi <?php echo $skrining['nadi']; ?> x/m, Suhu <?php echo $skrining['suhu']; ?>°C, RR <?php echo $skrining['pernapasan']; ?> x/m, SpO2 <?php echo $skrining['spo2']; ?>% 
                    | <b>Fisik:</b> BB <?php echo $skrining['berat_badan']; ?>kg, TB <?php echo $skrining['tinggi_badan']; ?>cm (IMT: <?php echo $skrining['imt']; ?>)
                    | <b>Nyeri:</b> <?php echo $skrining['skala_nyeri']; ?>/10 | <b>Risiko Jatuh:</b> <?php echo $skrining['risiko_jatuh']; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="box-skrining-kosong">
                ⏳ <i>Pasien ini belum memiliki lembar skrining awal perawat. Nilai objektif dapat Anda input secara mandiri di bawah.</i>
            </div>
        <?php endif; ?>

        <!-- ========================================================================= -->
        <!-- [PERBAIKAN 3]: PANEL TAMPILAN TARIKAN DATA LAB, RADIOLOGI, & APOTEK -->
        <!-- ========================================================================= -->
        <div class="box-penunjang">
            <h4 style="margin-top:0; color:#0284c7; border-bottom: 2px solid #bae6fd; padding-bottom: 5px;">
                📊 Hasil Pemeriksaan Penunjang & Riwayat Terapi Obat Pasien
            </h4>

            <!-- A. HASIL LABORATORIUM -->
            <div class="title-penunjang" style="color: #0284c7;">🔬 1. Hasil Laboratorium Klinis</div>
            <?php if (!$q_lab || mysqli_num_rows($q_lab) == 0): ?>
                <p style="font-size: 12px; color: #64748b; font-style: italic; margin-top: 2px;">Belum ada riwayat/permintaan laboratorium untuk pasien ini.</p>
            <?php else: ?>
                <table class="table-sub">
                    <thead>
                        <tr>
                            <th>Parameter Uji</th>
                            <th>Hasil Analisis</th>
                            <th>Nilai Rujukan Standard</th>
                            <th>Satuan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($l = mysqli_fetch_assoc($q_lab)): ?>
                        <tr>
                            <td><b><?php echo $l['nama_parameter'] ?? '-'; ?></b></td>
                            <td><b><?php echo $l['hasil'] ?? 'Menunggu'; ?></b></td>
                            <td><?php echo $l['nilai_rujukan'] ?? '-'; ?></td>
                            <td><?php echo $l['satuan'] ?? '-'; ?></td>
                            <td>
                                <?php if (($l['status_nilai'] ?? '') == 'Normal'): ?>
                                    <span class="badge-normal">Normal</span>
                                <?php else: ?>
                                    <span class="badge-abnormal"><?php echo $l['status_nilai'] ?? 'Abnormal'; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <hr style="border: 0; border-top: 1px dashed #cbd5e1; margin: 12px 0;">

            <!-- B. HASIL RADIOLOGI -->
            <div class="title-penunjang" style="color: #0284c7;">📷 2. Hasil Radiologi & Imaging</div>
            <?php if (!$q_rad || mysqli_num_rows($q_rad) == 0): ?>
                <p style="font-size: 12px; color: #64748b; font-style: italic; margin-top: 2px;">Belum ada riwayat/permintaan foto radiologi untuk pasien ini.</p>
            <?php else: ?>
                <?php while ($r = mysqli_fetch_assoc($q_rad)): ?>
                    <div style="background: white; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 8px; font-size: 12.5px;">
                        <b>Jenis Pemeriksaan:</b> <?php echo $r['jenis_pemeriksaan']; ?> (<i>Status: <?php echo $r['status_radiologi']; ?></i>)<br>
                        <b>Ekspertise Dokter Radiolog:</b> <?php echo !empty($r['ekspertise_klinis']) ? $r['ekspertise_klinis'] : '<i>Belum ada bacaan radiolog</i>'; ?><br>
                        <b>Kesan Radiologi:</b> <?php echo !empty($r['kesan_radiologi']) ? $r['kesan_radiologi'] : '-'; ?>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

            <hr style="border: 0; border-top: 1px dashed #cbd5e1; margin: 12px 0;">

            <!-- C. RIWAYAT RESEP OBAT / APOTEK -->
            <div class="title-penunjang" style="color: #16a34a;">💊 3. E-Resep & Terapi Obat (Apotek / Rekam Medis)</div>
            <?php if (!$q_resep || mysqli_num_rows($q_resep) == 0): ?>
                <p style="font-size: 12px; color: #64748b; font-style: italic; margin-top: 2px;">Belum ada resep obat yang tercatat.</p>
            <?php else: ?>
                <?php while ($f = mysqli_fetch_assoc($q_resep)): ?>
                    <div style="background: #f0fdf4; padding: 8px 12px; border-left: 4px solid #16a34a; border-radius: 4px; font-family: monospace; font-size: 12px; white-space: pre-wrap; margin-bottom: 6px;"><?php echo $f['plan_obat']; ?></div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

        <!-- FORM INPUT SOAP KLINIS DOKTER -->
        <form method="POST">
            <div class="grid-2">
                <div>
                    <label>S - Subjektif (Anamnesis / Keluhan Pasien)</label>
                    <textarea name="subjektif" rows="4" required placeholder="Keluhan yang dirasakan pasien..."><?php echo ($sudah_ada > 0) ? $data_rm['subjektif'] : $pasien['keluhan_utama']; ?></textarea>
                </div>
                <div>
                    <label>O - Objektif (Pemeriksaan Fisik & TTV)</label>
                    <textarea name="objektif" rows="4" required placeholder="Hasil pemeriksaan fisik, tensi, lab..."><?php echo $isi_objektif; ?></textarea>
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label>A - Asesmen / Kesimpulan Klinis</label>
                <textarea name="asesmen" rows="3" required placeholder="Kesimpulan dokter atas kondisi klinis pasien..."><?php echo ($sudah_ada > 0) ? $data_rm['asesmen'] : ''; ?></textarea>
            </div>

            <!-- BAGIAN 5 BARIS DIAGNOSA & KODE ICD-10 BERPASANGAN -->
            <div class="section-box">
                <h4 style="margin-top: 0; color: #1e3a8a; font-size: 15px; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px;">
                    🏷️ Perincian Diagnosa Utama & Sekunder (Beserta Usulan ICD-10)
                </h4>

                <!-- Diagnosa Utama -->
                <div class="row-diagnosa">
                    <label style="color: #dc2626; font-weight: bold;">Diagnosa Utama:</label>
                    <input type="text" name="diagnosa_utama_teks" class="teks-diag" placeholder="Nama/deskripsi penyakit utama..." value="<?php echo ($sudah_ada > 0) ? $data_rm['diagnosa_utama'] : ''; ?>" required>
                    <input type="text" name="icd_utama" class="kode-icd" list="kamus-icd10" placeholder="Pilih/ketik ICD-10..." value="<?php echo ($sudah_ada > 0) ? $data_rm['icd_utama'] : ''; ?>">
                </div>

                <!-- Diagnosa Sekunder 1 -->
                <div class="row-diagnosa">
                    <label style="color: #2563eb;">Diagnosa Sekunder 1:</label>
                    <input type="text" name="diagnosa_sekunder_1_teks" class="teks-diag" placeholder="Penyakit penyerta 1..." value="<?php echo ($sudah_ada > 0) ? $data_rm['diagnosa_sekunder_1'] : ''; ?>">
                    <input type="text" name="icd_sekunder_1" class="kode-icd" list="kamus-icd10" placeholder="Pilih/ketik ICD-10..." value="<?php echo ($sudah_ada > 0) ? $data_rm['icd_sekunder_1'] : ''; ?>">
                </div>

                <!-- Diagnosa Sekunder 2 -->
                <div class="row-diagnosa">
                    <label style="color: #2563eb;">Diagnosa Sekunder 2:</label>
                    <input type="text" name="diagnosa_sekunder_2_teks" class="teks-diag" placeholder="Penyakit penyerta 2..." value="<?php echo ($sudah_ada > 0) ? $data_rm['diagnosa_sekunder_2'] : ''; ?>">
                    <input type="text" name="icd_sekunder_2" class="kode-icd" list="kamus-icd10" placeholder="Pilih/ketik ICD-10..." value="<?php echo ($sudah_ada > 0) ? $data_rm['icd_sekunder_2'] : ''; ?>">
                </div>

                <!-- Diagnosa Sekunder 3 -->
                <div class="row-diagnosa">
                    <label style="color: #2563eb;">Diagnosa Sekunder 3:</label>
                    <input type="text" name="diagnosa_sekunder_3_teks" class="teks-diag" placeholder="Penyakit penyerta 3..." value="<?php echo ($sudah_ada > 0) ? $data_rm['diagnosa_sekunder_3'] : ''; ?>">
                    <input type="text" name="icd_sekunder_3" class="kode-icd" list="kamus-icd10" placeholder="Pilih/ketik ICD-10..." value="<?php echo ($sudah_ada > 0) ? $data_rm['icd_sekunder_3'] : ''; ?>">
                </div>

                <!-- Diagnosa Sekunder 4 -->
                <div class="row-diagnosa">
                    <label style="color: #2563eb;">Diagnosa Sekunder 4:</label>
                    <input type="text" name="diagnosa_sekunder_4_teks" class="teks-diag" placeholder="Penyakit penyerta 4..." value="<?php echo ($sudah_ada > 0) ? $data_rm['diagnosa_sekunder_4'] : ''; ?>">
                    <input type="text" name="icd_sekunder_4" class="kode-icd" list="kamus-icd10" placeholder="Pilih/ketik ICD-10..." value="<?php echo ($sudah_ada > 0) ? $data_rm['icd_sekunder_4'] : ''; ?>">
                </div>
            </div>

            <div class="grid-2">
                <div>
                    <label>P - Planning (Terapi Obat)</label>
                    <textarea name="plan_obat" rows="3" placeholder="Resep obat, dosis, dan instruksi konsumsi..."><?php echo ($sudah_ada > 0) ? $data_rm['plan_obat'] : ''; ?></textarea>
                </div>
                <div>
                    <label>P - Planning (Tindakan / Non-Obat)</label>
                    <textarea name="plan_non_obat" rows="3" placeholder="Edukasi, diet makanan, fisioterapi, kontrol ulang..."><?php echo ($sudah_ada > 0) ? $data_rm['plan_non_obat'] : ''; ?></textarea>
                </div>
            </div>

            <!-- INSTRUKSI TINDAK LANJUT / STATUS KELUAR POLI -->
            <div style="margin-top: 15px;">
                <label style="color: #0f172a;">Rencana Tindak Lanjut Medis</label>
                <select name="tindak_lanjut" id="tindak_lanjut" onchange="toggleSPRI()" required>
                    <option value="Selesai" <?php echo ($data_spri) ? '' : 'selected'; ?>>Selesai Rawat Jalan (Boleh Pulang / Ambil Resep Farmasi)</option>
                    <option value="Rawat Inap" <?php echo ($data_spri) ? 'selected' : ''; ?>>Perlu Rawat Inap (Terbitkan SPRI Poliklinik)</option>
                </select>
            </div>

            <!-- KOTAK POPUP PENERBITAN SPRI RAWAT JALAN -->
            <div id="kotak-spri">
                <h4 style="margin: 0 0 10px 0; color: #b45309;">📄 Penerbitan SPRI (Surat Perintah Rawat Inap) Poliklinik</h4>
                <p style="font-size: 13px; color: #92400e; margin-top: 0;">Pasien akan dimasukkan ke antrean Admisi Tempat Tidur Bangsal Rawat Inap.</p>

                <label>Dokter DPJP Bangsal yang Dituju</label>
                <select name="dpjp_bangsal" id="dpjp_bangsal">
                    <option value="">-- Pilih Dokter DPJP Bangsal --</option>
                    <?php
                    $q_dok = mysqli_query($koneksi, "SELECT DISTINCT nama_dokter, poli FROM jadwal_dokter ORDER BY nama_dokter ASC");
                    while($dok = mysqli_fetch_assoc($q_dok)) {
                        $selected = ($data_spri && $data_spri['dpjp_bangsal'] == $dok['nama_dokter']) ? 'selected' : '';
                        echo "<option value='".$dok['nama_dokter']."' $selected>".$dok['nama_dokter']." (".$dok['poli'].")</option>";
                    }
                    ?>
                </select>

                <label style="margin-top: 10px;">Catatan Khusus / Indikasi Rawat Inap</label>
                <textarea name="catatan_spri" rows="2" placeholder="Contoh: Perlu observasi post-tindakan, terapi antibiotik intravena, atau persiapan operasi..."><?php echo ($data_spri) ? $data_spri['catatan_khusus'] : ''; ?></textarea>
            </div>

            <button type="submit" name="simpan_rm">💾 Simpan & Perbarui Rekam Medis</button>
        </form>
    </div>
</body>
</html>