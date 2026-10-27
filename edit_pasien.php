<?php
include 'koneksi.php';

// Ambil No RM dari parameter URL
$rm = isset($_GET['rm']) ? $_GET['rm'] : '';

// Ambil data pasien berdasarkan No RM
$query = mysqli_query($koneksi, "SELECT * FROM pasien WHERE no_rm = '$rm'");
$d = mysqli_fetch_assoc($query);

if (!$d) {
    echo "<script>alert('Data pasien tidak ditemukan!'); window.location='pendaftaran_lama.php';</script>";
    exit;
}

// Proses Update Data Pasien
if (isset($_POST['update'])) {
    $nik        = $_POST['nik'];
    $nama       = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
    $tempat     = mysqli_real_escape_string($koneksi, $_POST['tempat_lahir']);
    $tgl_lahir  = $_POST['tgl_lahir'];
    $jk         = $_POST['jk'];
    $agama      = $_POST['agama'];
    $gol_darah  = $_POST['gol_darah'];
    $status     = $_POST['status_nikah'];
    $pekerjaan  = mysqli_real_escape_string($koneksi, $_POST['pekerjaan']);
    $alamat     = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $no_telp    = $_POST['no_telp'];
    $nama_pj    = mysqli_real_escape_string($koneksi, $_POST['nama_pj']);
    $hub_pj     = $_POST['hub_pj'];
    $telp_pj    = $_POST['telp_pj'];
    $cara_bayar = $_POST['cara_bayar'];
    $no_kartu   = $_POST['no_kartu'];

    $update = mysqli_query($koneksi, "UPDATE pasien SET 
                nik='$nik', 
                nama_lengkap='$nama', 
                tempat_lahir='$tempat', 
                tgl_lahir='$tgl_lahir', 
                jk='$jk', 
                agama='$agama', 
                gol_darah='$gol_darah', 
                status_nikah='$status', 
                pekerjaan='$pekerjaan', 
                alamat='$alamat', 
                no_telp='$no_telp', 
                nama_pj='$nama_pj', 
                hub_pj='$hub_pj', 
                telp_pj='$telp_pj', 
                cara_bayar='$cara_bayar', 
                no_kartu='$no_kartu' 
              WHERE no_rm='$rm'");

    if ($update) {
        echo "<script>alert('Data Pasien Berhasil Diperbarui!'); window.location='pendaftaran_lama.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data!');</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Data Pasien</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f4f8; padding: 20px; }
        .container { max-width: 800px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin: 0 auto; }
        h2 { color: #2c3e50; border-bottom: 2px solid #f59e0b; padding-bottom: 10px; margin-top: 0; }
        .section-title { font-size: 16px; font-weight: bold; color: #d97706; margin-top: 20px; margin-bottom: 10px; border-left: 4px solid #f59e0b; padding-left: 8px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 12px; }
        .form-group.full { grid-column: span 2; }
        label { display: block; font-weight: 600; font-size: 13px; color: #333; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        input:focus, select:focus, textarea:focus { border-color: #f59e0b; outline: none; }
        .btn-submit { background: #f59e0b; color: white; border: none; padding: 12px 20px; font-size: 16px; font-weight: bold; border-radius: 6px; cursor: pointer; width: 100%; margin-top: 20px; }
        .btn-submit:hover { background: #d97706; }
        .no-rm-box { background: #fef3c7; color: #b45309; padding: 10px; border-radius: 6px; font-weight: bold; text-align: center; font-size: 18px; margin-bottom: 20px; border: 1px dashed #fde68a; }
        .btn-batal { display: block; text-align: center; margin-top: 10px; color: #64748b; text-decoration: none; font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>

    <div class="container">
        <h2>✏️ Edit Data Pasien Lama</h2>
        
        <div class="no-rm-box">
            Nomor Rekam Medis: <?php echo $d['no_rm']; ?>
        </div>

        <form method="POST">
            <div class="section-title">Data Identitas Kependudukan</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>NIK (Nomor Induk Kependudukan)</label>
                    <input type="text" name="nik" value="<?php echo $d['nik']; ?>" maxlength="16" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap (Sesuai KTP)</label>
                    <input type="text" name="nama_lengkap" value="<?php echo $d['nama_lengkap']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" value="<?php echo $d['tempat_lahir']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="tgl_lahir" value="<?php echo $d['tgl_lahir']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="jk" required>
                        <option value="L" <?php if($d['jk']=='L') echo 'selected'; ?>>Laki-Laki</option>
                        <option value="P" <?php if($d['jk']=='P') echo 'selected'; ?>>Perempuan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Agama</label>
                    <select name="agama" required>
                        <option value="Islam" <?php if($d['agama']=='Islam') echo 'selected'; ?>>Islam</option>
                        <option value="Kristen" <?php if($d['agama']=='Kristen') echo 'selected'; ?>>Kristen</option>
                        <option value="Katolik" <?php if($d['agama']=='Katolik') echo 'selected'; ?>>Katolik</option>
                        <option value="Hindu" <?php if($d['agama']=='Hindu') echo 'selected'; ?>>Hindu</option>
                        <option value="Buddha" <?php if($d['agama']=='Buddha') echo 'selected'; ?>>Buddha</option>
                        <option value="Konghucu" <?php if($d['agama']=='Konghucu') echo 'selected'; ?>>Konghucu</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Golongan Darah</label>
                    <select name="gol_darah">
                        <option value="-" <?php if($d['gol_darah']=='-') echo 'selected'; ?>>- Tidak Tahu -</option>
                        <option value="A" <?php if($d['gol_darah']=='A') echo 'selected'; ?>>A</option>
                        <option value="B" <?php if($d['gol_darah']=='B') echo 'selected'; ?>>B</option>
                        <option value="AB" <?php if($d['gol_darah']=='AB') echo 'selected'; ?>>AB</option>
                        <option value="O" <?php if($d['gol_darah']=='O') echo 'selected'; ?>>O</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status Perkawinan</label>
                    <select name="status_nikah">
                        <option value="Belum Menikah" <?php if($d['status_nikah']=='Belum Menikah') echo 'selected'; ?>>Belum Menikah</option>
                        <option value="Menikah" <?php if($d['status_nikah']=='Menikah') echo 'selected'; ?>>Menikah</option>
                        <option value="Cerai" <?php if($d['status_nikah']=='Cerai') echo 'selected'; ?>>Cerai</option>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Pekerjaan</label>
                    <input type="text" name="pekerjaan" value="<?php echo $d['pekerjaan']; ?>">
                </div>
                <div class="form-group full">
                    <label>Alamat Lengkap</label>
                    <textarea name="alamat" rows="2" required><?php echo $d['alamat']; ?></textarea>
                </div>
                <div class="form-group">
                    <label>Nomor Telepon / HP Pasien</label>
                    <input type="text" name="no_telp" value="<?php echo $d['no_telp']; ?>">
                </div>
            </div>

            <div class="section-title">Data Penanggung Jawab (Keluarga)</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Penanggung Jawab</label>
                    <input type="text" name="nama_pj" value="<?php echo $d['nama_pj']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Hubungan dengan Pasien</label>
                    <select name="hub_pj" required>
                        <option value="Suami / Istri" <?php if($d['hub_pj']=='Suami / Istri') echo 'selected'; ?>>Suami / Istri</option>
                        <option value="Orang Tua" <?php if($d['hub_pj']=='Orang Tua') echo 'selected'; ?>>Orang Tua</option>
                        <option value="Anak Kandung" <?php if($d['hub_pj']=='Anak Kandung') echo 'selected'; ?>>Anak Kandung</option>
                        <option value="Saudara Kandung" <?php if($d['hub_pj']=='Saudara Kandung') echo 'selected'; ?>>Saudara Kandung</option>
                        <option value="Lainnya" <?php if($d['hub_pj']=='Lainnya') echo 'selected'; ?>>Lainnya</option>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Nomor Telepon Penanggung Jawab</label>
                    <input type="text" name="telp_pj" value="<?php echo $d['telp_pj']; ?>" required>
                </div>
            </div>

            <div class="section-title">Data Pembayaran / Asuransi</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Cara Bayar</label>
                    <select name="cara_bayar" required>
                        <option value="Umum / Mandiri" <?php if($d['cara_bayar']=='Umum / Mandiri') echo 'selected'; ?>>Umum / Mandiri (Pribadi)</option>
                        <option value="BPJS Kesehatan" <?php if($d['cara_bayar']=='BPJS Kesehatan') echo 'selected'; ?>>BPJS Kesehatan</option>
                        <option value="Asuransi Lain" <?php if($d['cara_bayar']=='Asuransi Lain') echo 'selected'; ?>>Asuransi Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nomor Kartu (BPJS / Asuransi)</label>
                    <input type="text" name="no_kartu" value="<?php echo $d['no_kartu']; ?>">
                </div>
            </div>

            <button type="submit" name="update" class="btn-submit">💾 Simpan Perubahan Data Pasien</button>
            <a href="pendaftaran_lama.php" class="btn-batal">← Batal & Kembali</a>
        </form>
    </div>

</body>
</html>