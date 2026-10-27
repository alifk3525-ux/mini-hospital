<?php
include 'koneksi.php';
$id_rm = isset($_GET['id']) ? $_GET['id'] : '';

// Tarik data rekam medis berdasarkan id_rm
$query = mysqli_query($koneksi, "SELECT rm.*, p.nama_lengkap, p.jk, p.tgl_lahir FROM rekam_medis rm JOIN pasien p ON rm.no_rm = p.no_rm WHERE rm.id_rm = '$id_rm'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>alert('Data rekam medis tidak ditemukan!'); window.location='riwayat_medis.php';</script>";
    exit;
}

// Proses Hapus dari Halaman Edit
if (isset($_POST['hapus_rm'])) {
    mysqli_query($koneksi, "DELETE FROM rekam_medis WHERE id_rm = '$id_rm'");
    echo "<script>alert('Rekam medis berhasil dihapus!'); window.location='riwayat_medis.php';</script>";
    exit;
}

// Proses Update Rekam Medis
if (isset($_POST['update_cppt'])) {
    $s = mysqli_real_escape_string($koneksi, $_POST['subjektif']);
    $a = mysqli_real_escape_string($koneksi, $_POST['asesmen']);
    $du = mysqli_real_escape_string($koneksi, $_POST['diagnosa_utama']);
    
    mysqli_query($koneksi, "UPDATE rekam_medis SET subjektif='$s', asesmen='$a', diagnosa_utama='$du' WHERE id_rm='$id_rm'");
    echo "<script>alert('Perubahan Diagnosa & CPPT Berhasil Disimpan!'); window.location='riwayat_medis.php';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit / Hapus CPPT</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; }
        .wadah { background: white; padding: 25px; border-radius: 8px; max-width: 800px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        label { display: block; font-weight: bold; margin: 15px 0 5px 0; font-size: 13px; color: #475569; }
        textarea, input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 5px; box-sizing: border-box; font-size: 14px; }
        .btn-simpan { background: #10b981; color: white; border: none; padding: 12px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 20px; border-radius: 5px; font-size: 15px; }
        .btn-simpan:hover { background: #059669; }
        .btn-hapus-form { background: #ef4444; color: white; border: none; padding: 12px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 10px; border-radius: 5px; font-size: 15px; }
        .btn-hapus-form:hover { background: #dc2626; }
    </style>
</head>
<body>

    <datalist id="data-icd10">
        <?php
        $q_icd = mysqli_query($koneksi, "SELECT * FROM icd10");
        while($icd = mysqli_fetch_assoc($q_icd)) {
            echo "<option value='".$icd['kode']." - ".$icd['diagnosa']."'>";
        }
        ?>
    </datalist>

    <div class="wadah">
        <h3 style="margin-top: 0; color: #2c3e50; border-bottom: 2px solid #3b82f6; padding-bottom: 10px;">✏️ Edit / Hapus Rekam Medis: <?php echo $data['nama_lengkap']; ?></h3>
        <p style="font-size: 13px; color: #64748b;">No. Registrasi: <strong><?php echo $data['no_registrasi']; ?></strong> | No. RM: <strong><?php echo $data['no_rm']; ?></strong></p>

        <form method="POST">
            <label>Subjektif</label>
            <textarea name="subjektif" rows="3"><?php echo $data['subjektif']; ?></textarea>
            
            <label>Asesmen / Kesimpulan Medis</label>
            <textarea name="asesmen" rows="4"><?php echo $data['asesmen']; ?></textarea>
            
            <label>Diagnosa Utama (ICD-10)</label>
            <input type="text" name="diagnosa_utama" list="data-icd10" value="<?php echo $data['diagnosa_utama']; ?>" required>
            
            <button type="submit" name="update_cppt" class="btn-simpan">💾 Simpan Perubahan</button>
            <button type="submit" name="hapus_rm" class="btn-hapus-form" onclick="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus data rekam medis duplikat ini secara permanen?');">🗑️ Hapus Rekam Medis Ini</button>
            
            <a href="riwayat_medis.php" style="display:block; text-align:center; margin-top:15px; color:#4b5563; text-decoration:none; font-weight:bold;">← Kembali ke Daftar Riwayat</a>
        </form>
    </div>
</body>
</html>