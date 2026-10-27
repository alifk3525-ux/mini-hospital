<?php
include 'koneksi.php';
$reg = isset($_GET['reg']) ? mysqli_real_escape_string($koneksi, $_GET['reg']) : '';

// Ambil data kunjungan dan identitas pasien
$q_pasien = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, p.tgl_lahir, rm.diagnosa_utama 
            FROM kunjungan k 
            JOIN pasien p ON k.no_rm = p.no_rm 
            LEFT JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi 
            WHERE k.no_registrasi = '$reg'");
$pasien = mysqli_fetch_assoc($q_pasien);

if (!$pasien) {
    echo "<script>alert('Data kunjungan tidak ditemukan!'); window.close();</script>";
    exit;
}

// Proses Pembuatan Order Lab
if (isset($_POST['kirim_order'])) {
    $no_rm = $pasien['no_rm'];
    $dokter = mysqli_real_escape_string($koneksi, $_POST['dokter_pengirim']);
    $unit = $pasien['jenis_layanan'] . " (" . $pasien['tujuan_layanan'] . ")";
    $diagnosa = mysqli_real_escape_string($koneksi, $_POST['diagnosa_klinis']);
    $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan_dokter']);
    
    // Generate Nomor Order Lab: LAB-YYYYMMDD-XXXX
    $no_order = "LAB-" . date('Ymd') . "-" . rand(1000, 9999);

    $simpan_order = mysqli_query($koneksi, "INSERT INTO order_lab 
                    (no_order, no_registrasi, no_rm, dokter_pengirim, unit_pengirim, diagnosa_klinis, catatan_dokter) 
                    VALUES ('$no_order', '$reg', '$no_rm', '$dokter', '$unit', '$diagnosa', '$catatan')");

    if ($simpan_order) {
        // Otomatis masukkan parameter yang dicentang beserta nilai rujukan standarnya
        $pemeriksaan = isset($_POST['tes']) ? $_POST['tes'] : [];

        // Kamus Parameter Uji Lab Standar Kemenkes
        $kamus_tes = [
            // Hematologi
            'hb' => ['Hematologi', 'Hemoglobin (Hb)', ($pasien['jk'] == 'L' ? '13,0 - 17,5' : '12,0 - 15,5'), 'g/dL'],
            'leukosit' => ['Hematologi', 'Leukosit', '4.000 - 10.000', '/uL'],
            'trombosit' => ['Hematologi', 'Trombosit', '150.000 - 450.000', '/uL'],
            'hematokrit' => ['Hematologi', 'Hematokrit', ($pasien['jk'] == 'L' ? '40 - 52' : '36 - 48'), '%'],
            'led' => ['Hematologi', 'Laju Endap Darah (LED)', ($pasien['jk'] == 'L' ? '0 - 15' : '0 - 20'), 'mm/jam'],
            
            // Kimia Darah / Metabolik
            'gds' => ['Kimia Darah', 'Glukosa Darah Sewaktu (GDS)', '< 140', 'mg/dL'],
            'gdp' => ['Kimia Darah', 'Glukosa Darah Puasa (GDP)', '70 - 100', 'mg/dL'],
            'kolesterol' => ['Kimia Darah', 'Kolesterol Total', '< 200', 'mg/dL'],
            'asam_urat' => ['Kimia Darah', 'Asam Urat', ($pasien['jk'] == 'L' ? '3,4 - 7,0' : '2,4 - 5,7'), 'mg/dL'],
            'ureum' => ['Kimia Darah', 'Ureum', '15 - 45', 'mg/dL'],
            'kreatinin' => ['Kimia Darah', 'Kreatinin', ($pasien['jk'] == 'L' ? '0,7 - 1,3' : '0,6 - 1,1'), 'mg/dL'],
            'sgot' => ['Kimia Darah', 'SGOT (AST)', '< 35', 'U/L'],
            'sgpt' => ['Kimia Darah', 'SGPT (ALT)', '< 45', 'U/L'],

            // Urinalisis & Serologi
            'urin_rutin' => ['Urinalisis', 'Urin Rutin (Warna/Kejernihan/Protein/Reduksi)', 'Normal / Negatif', 'Kualitatif'],
            'widal' => ['Serologi', 'Widal Test (S. Typhi)', 'Negatif / < 1/80', 'Titer']
        ];

        foreach ($pemeriksaan as $kode) {
            if (isset($kamus_tes[$kode])) {
                $kat = $kamus_tes[$kode][0];
                $param = $kamus_tes[$kode][1];
                $rujukan = $kamus_tes[$kode][2];
                $satuan = $kamus_tes[$kode][3];

                mysqli_query($koneksi, "INSERT INTO hasil_lab_detail 
                             (no_order, kategori, nama_parameter, nilai_rujukan, satuan) 
                             VALUES ('$no_order', '$kat', '$param', '$rujukan', '$satuan')");
            }
        }

        echo "<script>alert('Order Lab Berhasil Diterbitkan ($no_order)! Data sudah masuk ke Instalasi Laboratorium.'); window.location='dasbor_lab.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Permintaan Pemeriksaan Laboratorium</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; color: #1e293b; }
        .wadah { background: white; padding: 25px; border-radius: 8px; max-width: 850px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h3 { color: #0284c7; border-bottom: 2px solid #0284c7; padding-bottom: 8px; margin-top: 0; }
        .card-pasien { background: #f0f9ff; border-left: 5px solid #0284c7; padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; display: flex; justify-content: space-between; }
        .section-title { font-weight: bold; color: #0369a1; margin: 15px 0 10px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .box-pilihan { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 15px; margin-bottom: 15px; }
        .check-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        label { font-size: 13px; font-weight: 600; color: #334155; }
        input[type="text"], textarea { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; font-size: 13px; }
        button.btn-kirim { background: #0284c7; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 15px; }
        button.btn-kirim:hover { background: #0369a1; }
    </style>
</head>
<body>
    <div class="wadah">
        <h3>🔬 Formulir Permintaan Pemeriksaan Laboratorium (Order Lab)</h3>

        <div class="card-pasien">
            <div>
                <strong><?php echo $pasien['nama_lengkap']; ?> (<?php echo $pasien['jk']; ?>)</strong><br>
                <small>No. RM: <b><?php echo $pasien['no_rm']; ?></b> | Reg: <?php echo $pasien['no_registrasi']; ?></small>
            </div>
            <div style="text-align: right;">
                <span style="color: #0369a1; font-weight: bold;"><?php echo $pasien['jenis_layanan']; ?> (<?php echo $pasien['tujuan_layanan']; ?>)</span><br>
                <small>DPJP: <?php echo $pasien['dpjp']; ?></small>
            </div>
        </div>

        <form method="POST">
            <div class="grid-2">
                <div>
                    <label>Dokter Peminta / Pengirim</label>
                    <input type="text" name="dokter_pengirim" value="<?php echo $pasien['dpjp']; ?>" required>
                </div>
                <div>
                    <label>Diagnosis Klinis / Indikasi Pemeriksaan</label>
                    <input type="text" name="diagnosa_klinis" value="<?php echo $pasien['diagnosa_utama'] ? $pasien['diagnosa_utama'] : $pasien['keluhan_utama']; ?>" required>
                </div>
            </div>

            <!-- CHECKBOX DAFTAR PARAMETER LABORATORIUM -->
            <div class="box-pilihan">
                <div class="section-title">1. Hematologi & Darah Rutin</div>
                <div class="check-grid">
                    <div><label><input type="checkbox" name="tes[]" value="hb" checked> Hemoglobin (Hb)</label></div>
                    <div><label><input type="checkbox" name="tes[]" value="leukosit" checked> Leukosit</label></div>
                    <div><label><input type="checkbox" name="tes[]" value="trombosit" checked> Trombosit</label></div>
                    <div><label><input type="checkbox" name="tes[]" value="hematokrit" checked> Hematokrit</label></div>
                    <div><label><input type="checkbox" name="tes[]" value="led"> Laju Endap Darah (LED)</label></div>
                </div>

                <div class="section-title" style="margin-top: 20px;">2. Kimia Darah, Gula, & Profil Organ</div>
                <div class="check-grid">
                    <div><label><input type="checkbox" name="tes[]" value="gds"> Glukosa Darah Sewaktu (GDS)</label></div>
                    <div><label><input type="checkbox" name="tes[]" value="gdp"> Glukosa Darah Puasa (GDP)</label></div>
                    <div><label><input type="checkbox" name="tes[]" value="kolesterol"> Kolesterol Total</label></div>
                    <div><label><input type="checkbox" name="tes[]" value="asam_urat"> Asam Urat</label></div>
                    <div><label><input type="checkbox" name="tes[]" value="ureum"> Ureum Ginjal</label></div>
                    <div><label><input type="checkbox" name="tes[]" value="kreatinin"> Kreatinin Ginjal</label></div>
                    <div><label><input type="checkbox" name="tes[]" value="sgot"> SGOT (Fungsi Hati)</label></div>
                    <div><label><input type="checkbox" name="tes[]" value="sgpt"> SGPT (Fungsi Hati)</label></div>
                </div>

                <div class="section-title" style="margin-top: 20px;">3. Urinalisis & Serologi Khusus</div>
                <div class="check-grid">
                    <div><label><input type="checkbox" name="tes[]" value="urin_rutin"> Urin Rutin Lengkap</label></div>
                    <div><label><input type="checkbox" name="tes[]" value="widal"> Tes Widal (Demam Tifoid)</label></div>
                </div>
            </div>

            <div>
                <label>Catatan Tambahan untuk Petugas Lab</label>
                <textarea name="catatan_dokter" rows="2" placeholder="Catatan puasa, sampel cito / segera, dll..."></textarea>
            </div>

            <button type="submit" name="kirim_order" class="btn-kirim">🚀 Kirim Permintaan ke Laboratorium</button>
        </form>
    </div>
</body>
</html>