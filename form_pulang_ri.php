<?php
include 'koneksi.php';
$reg = isset($_GET['reg']) ? mysqli_real_escape_string($koneksi, $_GET['reg']) : '';

// 1. Ambil data kunjungan dan identitas pasien
$q = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, p.tgl_lahir 
                            FROM kunjungan k 
                            JOIN pasien p ON k.no_rm = p.no_rm 
                            WHERE k.no_registrasi = '$reg'");
$pasien = mysqli_fetch_assoc($q);

if (!$pasien) {
    echo "<script>alert('Data kunjungan rawat inap tidak ditemukan!'); window.location='ruang_periksa_ri.php';</script>";
    exit;
}

// 2. Tarik data rekam medis yang sudah ada
$cek_rm = mysqli_query($koneksi, "SELECT * FROM rekam_medis WHERE no_registrasi = '$reg'");
$data_rm = mysqli_fetch_assoc($cek_rm);
$sudah_ada = $data_rm ? true : false;

// 3. Proses Eksekusi Pemulangan Pasien
if (isset($_POST['simpan_pulang'])) {
    $status_keluar = mysqli_real_escape_string($koneksi, $_POST['status_keluar']);
    
    $dau = mysqli_real_escape_string($koneksi, $_POST['diagnosa_utama']);
    $iau = mysqli_real_escape_string($koneksi, $_POST['icd_utama']);
    
    $ds1 = mysqli_real_escape_string($koneksi, $_POST['diagnosa_sekunder_1']);
    $is1 = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_1']);
    
    $ds2 = mysqli_real_escape_string($koneksi, $_POST['diagnosa_sekunder_2']);
    $is2 = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_2']);
    
    $ds3 = mysqli_real_escape_string($koneksi, $_POST['diagnosa_sekunder_3']);
    $is3 = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_3']);
    
    $ds4 = mysqli_real_escape_string($koneksi, $_POST['diagnosa_sekunder_4']);
    $is4 = mysqli_real_escape_string($koneksi, $_POST['icd_sekunder_4']);

    $rm = $pasien['no_rm'];
    $dpjp = $pasien['dpjp'];

    // A. Simpan atau Perbarui di Tabel rekam_medis
    if ($sudah_ada) {
        mysqli_query($koneksi, "UPDATE rekam_medis SET 
            status_keluar = '$status_keluar',
            diagnosa_utama = '$dau', icd_utama = '$iau',
            diagnosa_sekunder_1 = '$ds1', icd_sekunder_1 = '$is1',
            diagnosa_sekunder_2 = '$ds2', icd_sekunder_2 = '$is2',
            diagnosa_sekunder_3 = '$ds3', icd_sekunder_3 = '$is3',
            diagnosa_sekunder_4 = '$ds4', icd_sekunder_4 = '$is4'
            WHERE no_registrasi = '$reg'");
    } else {
        mysqli_query($koneksi, "INSERT INTO rekam_medis (no_registrasi, no_rm, dpjp, status_keluar, diagnosa_utama, icd_utama, diagnosa_sekunder_1, icd_sekunder_1, diagnosa_sekunder_2, icd_sekunder_2, diagnosa_sekunder_3, icd_sekunder_3, diagnosa_sekunder_4, icd_sekunder_4) 
            VALUES ('$reg', '$rm', '$dpjp', '$status_keluar', '$dau', '$iau', '$ds1', '$is1', '$ds2', '$is2', '$ds3', '$is3', '$ds4', '$is4')");
    }

    // B. LOGIKA KEMATIAN & STATUS KUNJUNGAN:
    // Jika status keluar mengandung kata 'Meninggal', set status_kunjungan = 'Selesai (Meninggal)'
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

    // C. Kosongkan kasur bangsal (kurangi 1 kasur terisi)
    $kamar = $pasien['tujuan_layanan'];
    mysqli_query($koneksi, "UPDATE kamar_rawat SET terisi = GREATEST(0, terisi - 1) WHERE nama_ruangan = '$kamar'");

    // D. Langsung alihkan ke Resume Medis
    echo "<script>alert('Pasien Berhasil Dinyatakan Keluar! Status kunjungan dan tanggal keluar telah diperbarui.'); window.location='resume_medis.php?reg=$reg';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Formulir Pulang & Diagnosis Akhir</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; margin: 0; color: #1e293b; }
        .wadah { background: white; padding: 25px; border-radius: 8px; max-width: 900px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header-form { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #d97706; padding-bottom: 10px; margin-bottom: 15px; }
        .header-form h3 { margin: 0; color: #b45309; font-size: 20px; }
        .info-box { background: #fef3c7; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 5px solid #d97706; font-size: 13px; }
        label { display: block; font-weight: bold; margin: 10px 0 5px 0; font-size: 13px; color: #334155; }
        input, select { width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; font-size: 14px; }
        input:focus, select:focus { border-color: #d97706; outline: none; }
        .row-diagnosa { display: grid; grid-template-columns: 2fr 1fr; gap: 10px; margin-bottom: 12px; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; }
        
        .btn-grup { display: grid; grid-template-columns: 1.5fr 1fr; gap: 12px; margin-top: 25px; }
        .btn-pulang { background: #dc2626; color: white; border: none; padding: 12px; font-weight: bold; cursor: pointer; border-radius: 6px; font-size: 14px; text-align: center; }
        .btn-pulang:hover { background: #b91c1c; }
        .btn-resume { background: #0f766e; color: white; border: none; padding: 12px; font-weight: bold; text-decoration: none; border-radius: 6px; font-size: 14px; text-align: center; display: inline-flex; align-items: center; justify-content: center; }
        .btn-resume:hover { background: #115e59; }
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
        <div class="header-form">
            <h3>🏁 Status Keluar & Diagnosis Akhir Kepulangan</h3>
            <a href="resume_medis.php?reg=<?php echo $reg; ?>" target="_blank" style="background: #0f766e; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: bold;">📄 Buka Resume Medis</a>
        </div>
        
        <div class="info-box">
            <strong><?php echo $pasien['nama_lengkap']; ?> (<?php echo $pasien['jk']; ?>)</strong> | 
            Kamar: <strong><?php echo $pasien['tujuan_layanan']; ?></strong> | 
            No. Reg: <strong><?php echo $reg; ?></strong><br>
            DPJP: <b><?php echo $pasien['dpjp']; ?></b> | Penjamin: <b><?php echo $pasien['cara_bayar']; ?></b>
        </div>

        <form method="POST">
            <label>Status Kepulangan Pasien</label>
            <select name="status_keluar" required>
                <?php 
                $status_tersimpan = ($sudah_ada && !empty($data_rm['status_keluar'])) ? $data_rm['status_keluar'] : 'Membaik';
                $opsi_status = ['Sembuh', 'Membaik', 'Dirujuk ke RS Lain', 'Pulang Atas Permintaan Sendiri (APS)', 'Meninggal'];
                foreach ($opsi_status as $opt) {
                    $sel = ($status_tersimpan == $opt) ? 'selected' : '';
                    echo "<option value='$opt' $sel>$opt</option>";
                }
                ?>
            </select>

            <h4 style="color: #1e293b; margin-top: 20px; border-bottom: 2px solid #cbd5e1; padding-bottom: 5px;">📋 Diagnosis Akhir Rawat Inap (Klaim)</h4>

            <!-- DIAGNOSA UTAMA -->
            <label style="color: #b45309;">Diagnosis Akhir Utama</label>
            <div class="row-diagnosa">
                <input type="text" name="diagnosa_utama" value="<?php echo $sudah_ada ? htmlspecialchars($data_rm['diagnosa_utama']) : ''; ?>" placeholder="Uraian diagnosis akhir utama..." required>
                <input type="text" name="icd_utama" list="kamus-icd10" value="<?php echo $sudah_ada ? htmlspecialchars($data_rm['icd_utama']) : ''; ?>" placeholder="Kode ICD-10 Utama..." required>
            </div>

            <!-- DIAGNOSA SEKUNDER 1 -->
            <label>Diagnosis Sekunder 1</label>
            <div class="row-diagnosa">
                <input type="text" name="diagnosa_sekunder_1" value="<?php echo $sudah_ada ? htmlspecialchars($data_rm['diagnosa_sekunder_1']) : ''; ?>" placeholder="Penyakit penyerta 1...">
                <input type="text" name="icd_sekunder_1" list="kamus-icd10" value="<?php echo $sudah_ada ? htmlspecialchars($data_rm['icd_sekunder_1']) : ''; ?>" placeholder="Kode ICD-10 Sekunder 1...">
            </div>

            <!-- DIAGNOSA SEKUNDER 2 -->
            <label>Diagnosis Sekunder 2</label>
            <div class="row-diagnosa">
                <input type="text" name="diagnosa_sekunder_2" value="<?php echo $sudah_ada ? htmlspecialchars($data_rm['diagnosa_sekunder_2']) : ''; ?>" placeholder="Penyakit penyerta 2...">
                <input type="text" name="icd_sekunder_2" list="kamus-icd10" value="<?php echo $sudah_ada ? htmlspecialchars($data_rm['icd_sekunder_2']) : ''; ?>" placeholder="Kode ICD-10 Sekunder 2...">
            </div>

            <!-- DIAGNOSA SEKUNDER 3 -->
            <label>Diagnosis Sekunder 3</label>
            <div class="row-diagnosa">
                <input type="text" name="diagnosa_sekunder_3" value="<?php echo $sudah_ada ? htmlspecialchars($data_rm['diagnosa_sekunder_3']) : ''; ?>" placeholder="Penyakit penyerta 3...">
                <input type="text" name="icd_sekunder_3" list="kamus-icd10" value="<?php echo $sudah_ada ? htmlspecialchars($data_rm['icd_sekunder_3']) : ''; ?>" placeholder="Kode ICD-10 Sekunder 3...">
            </div>

            <!-- DIAGNOSA SEKUNDER 4 -->
            <label>Diagnosis Sekunder 4</label>
            <div class="row-diagnosa">
                <input type="text" name="diagnosa_sekunder_4" value="<?php echo $sudah_ada ? htmlspecialchars($data_rm['diagnosa_sekunder_4']) : ''; ?>" placeholder="Penyakit penyerta 4...">
                <input type="text" name="icd_sekunder_4" list="kamus-icd10" value="<?php echo $sudah_ada ? htmlspecialchars($data_rm['icd_sekunder_4']) : ''; ?>" placeholder="Kode ICD-10 Sekunder 4...">
            </div>

            <!-- KELOMPOK TOMBOL AKSI -->
            <div class="btn-grup">
                <button type="submit" name="simpan_pulang" class="btn-pulang">💾 Simpan Kepulangan & Bebaskan Kasur</button>
                <a href="resume_medis.php?reg=<?php echo $reg; ?>" target="_blank" class="btn-resume">📄 Buka Resume Medis</a>
            </div>
        </form>
    </div>
</body>
</html>