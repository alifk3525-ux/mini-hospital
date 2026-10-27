<?php
include 'koneksi.php';
$reg = isset($_GET['reg']) ? mysqli_real_escape_string($koneksi, $_GET['reg']) : '';

// Ambil data pasien & kunjungan
$q_pasien = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, p.tgl_lahir 
            FROM kunjungan k JOIN pasien p ON k.no_rm = p.no_rm WHERE k.no_registrasi = '$reg'");
$pasien = mysqli_fetch_assoc($q_pasien);

if (!$pasien) {
    echo "<script>alert('Data pasien tidak ditemukan!'); window.close();</script>";
    exit;
}

// Simpan Order Radiologi
if (isset($_POST['simpan_order'])) {
    $no_order = "RAD-" . date('Ymd') . "-" . rand(100, 999);
    $rm = $pasien['no_rm'];
    $dokter = $pasien['dpjp'] ? $pasien['dpjp'] : 'Dokter DPJP';
    $unit = $pasien['tujuan_layanan'];
    
    // Gabungkan pilihan checkbox jenis pemeriksaan
    $pemeriksaan_arr = isset($_POST['pemeriksaan']) ? $_POST['pemeriksaan'] : [];
    $pemeriksaan_custom = trim($_POST['pemeriksaan_custom']);
    if (!empty($pemeriksaan_custom)) {
        $pemeriksaan_arr[] = $pemeriksaan_custom;
    }
    $jenis_pemeriksaan = implode(", ", $pemeriksaan_arr);

    $diagnosa = mysqli_real_escape_string($koneksi, $_POST['diagnosa_klinis']);
    $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan_dokter']);

    if (empty($jenis_pemeriksaan)) {
        echo "<script>alert('Pilih minimal satu jenis pemeriksaan radiologi!');</script>";
    } else {
        $q_simpan = "INSERT INTO order_radiologi (no_order, no_registrasi, no_rm, dokter_pengirim, unit_pengirim, jenis_pemeriksaan, diagnosa_klinis, catatan_dokter)
                     VALUES ('$no_order', '$reg', '$rm', '$dokter', '$unit', '$jenis_pemeriksaan', '$diagnosa', '$catatan')";
        if (mysqli_query($koneksi, $q_simpan)) {
            echo "<script>alert('Order Radiologi ($no_order) Berhasil Dikirim!'); window.close();</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Permintaan Radiologi</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; color: #1e293b; }
        .wadah { background: white; padding: 25px; border-radius: 8px; max-width: 750px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h3 { color: #0284c7; border-bottom: 2px solid #0284c7; padding-bottom: 8px; margin-top: 0; }
        .info-box { background: #e0f2fe; border-left: 4px solid #0284c7; padding: 12px; margin-bottom: 15px; border-radius: 4px; font-size: 14px; }
        .grid-pemeriksaan { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: #f8fafc; padding: 15px; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 15px; }
        label { font-size: 13px; font-weight: bold; color: #475569; display: block; margin-bottom: 4px; }
        textarea, input[type="text"] { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        button { background: #0284c7; color: white; border: none; padding: 12px; font-weight: bold; cursor: pointer; border-radius: 5px; width: 100%; font-size: 15px; margin-top: 10px; }
        button:hover { background: #0369a1; }
    </style>
</head>
<body>
    <div class="wadah">
        <h3>📷 Form Permintaan Radiologi & Imaging</h3>
        
        <div class="info-box">
            <b>Pasien:</b> <?php echo $pasien['nama_lengkap']; ?> (<?php echo $pasien['no_rm']; ?>)<br>
            <b>No. Registrasi:</b> <?php echo $reg; ?> | <b>Unit asal:</b> <?php echo $pasien['tujuan_layanan']; ?>
        </div>

        <form method="POST">
            <label>Pilih Jenis Pemeriksaan Radiologi:</label>
            <div class="grid-pemeriksaan">
                <div><label><input type="checkbox" name="pemeriksaan[]" value="Rontgen Thorax PA/AP"> Rontgen Thorax PA/AP</label></div>
                <div><label><input type="checkbox" name="pemeriksaan[]" value="Rontgen Abdomen 3 Posisi"> Rontgen Abdomen 3 Posisi</label></div>
                <div><label><input type="checkbox" name="pemeriksaan[]" value="Rontgen Ekstremitas Atas/Bawah"> Rontgen Ekstremitas</label></div>
                <div><label><input type="checkbox" name="pemeriksaan[]" value="Rontgen Skull / Kepala"> Rontgen Kepala / Skull</label></div>
                <div><label><input type="checkbox" name="pemeriksaan[]" value="USG Abdomen Whole"> USG Abdomen Whole</label></div>
                <div><label><input type="checkbox" name="pemeriksaan[]" value="USG Obgyn / Kandungan"> USG Obgyn</label></div>
                <div><label><input type="checkbox" name="pemeriksaan[]" value="CT-Scan Kepala Non-Kontras"> CT-Scan Kepala</label></div>
                <div><label><input type="checkbox" name="pemeriksaan[]" value="CT-Scan Thorax / Abdomen"> CT-Scan Thorax/Abdomen</label></div>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Pemeriksaan Lainnya (Opsional):</label>
                <input type="text" name="pemeriksaan_custom" placeholder="Sebutkan jenis rontgen / USG khusus jika tidak ada di list...">
            </div>

            <div style="margin-bottom: 12px;">
                <label>Diagnosa Klinis / Indikasi Pemeriksaan:</label>
                <textarea name="diagnosa_klinis" rows="2" placeholder="Indikasi klinis pasien..."><?php echo $pasien['keluhan_utama']; ?></textarea>
            </div>

            <div style="margin-bottom: 12px;">
                <label>Catatan Tambahan untuk Radiolog:</label>
                <textarea name="catatan_dokter" rows="2" placeholder="Catatan khusus, posisi foto, dsb..."></textarea>
            </div>

            <button type="submit" name="simpan_order">🚀 Kirim Permintaan Radiologi</button>
        </form>
    </div>
</body>
</html>