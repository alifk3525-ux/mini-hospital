<?php
include 'koneksi.php';

// Proses Simpan Data Pasien
if (isset($_POST['simpan'])) {
    $no_rm = $_POST['no_rm'];
    $nama = $_POST['nama'];
    $nik = $_POST['nik'];
    $tempat = $_POST['tempat_lahir'];
    $tgl = $_POST['tgl_lahir'];
    $jk = $_POST['jk'];
    $telp = $_POST['telp'];
    $alamat = $_POST['alamat'];

    $query = "INSERT INTO pasien (no_rm, nama_lengkap, nik, tempat_lahir, tgl_lahir, jk, no_telp, alamat) 
              VALUES ('$no_rm', '$nama', '$nik', '$tempat', '$tgl', '$jk', '$telp', '$alamat')";
    
    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Data Pasien Berhasil Disimpan!'); window.location='pendaftaran.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data!');</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pendaftaran Pasien</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f4f9; padding: 20px; margin: 0; }
        .container { display: flex; gap: 20px; }
        
        .box-form, .box-data { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .box-form { flex: 1; }
        .box-data { flex: 2; }
        
        h3 { margin-top: 0; color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 12px; }
        label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #34495e; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        
        .btn-simpan { background: #3b82f6; color: white; border: none; padding: 10px 15px; font-weight: bold; border-radius: 4px; cursor: pointer; width: 100%; margin-top: 10px; }
        .btn-simpan:hover { background: #2563eb; }
        
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #2c3e50; color: white; }
    </style>
</head>
<body>

    <div class="container">
        <!-- Form Pendaftaran -->
        <div class="box-form">
            <h3>📝 Registrasi Pasien Baru</h3>
            <form method="POST">
                <div class="form-group">
                    <label>No. Rekam Medis (RM)</label>
                    <input type="text" name="no_rm" placeholder="Contoh: 00123456" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" required>
                </div>
                <div class="form-group">
                    <label>NIK / No. KTP</label>
                    <input type="text" name="nik" required>
                </div>
                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tgl_lahir" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="jk">
                        <option value="L">Laki-laki (L)</option>
                        <option value="P">Perempuan (P)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>No. Telepon / HP</label>
                    <input type="text" name="telp">
                </div>
                <div class="form-group">
                    <label>Alamat Lengkap</label>
                    <textarea name="alamat" rows="2"></textarea>
                </div>
                <button type="submit" name="simpan" class="btn-simpan">💾 Simpan Data Pasien</button>
            </form>
        </div>

        <!-- Tabel Data Pasien -->
        <div class="box-data">
            <h3>📋 Daftar Pasien Terdaftar</h3>
            <table>
                <tr>
                    <th>No. RM</th>
                    <th>Nama Pasien</th>
                    <th>JK</th>
                    <th>Tanggal Lahir</th>
                    <th>No. HP</th>
                    <th>Waktu Daftar</th>
                </tr>
                <?php
                $q_pasien = mysqli_query($koneksi, "SELECT * FROM pasien ORDER BY id_pasien DESC LIMIT 15");
                if(mysqli_num_rows($q_pasien) == 0) {
                    echo "<tr><td colspan='6' style='text-align:center;'>Belum ada data pasien.</td></tr>";
                }
                while ($d = mysqli_fetch_assoc($q_pasien)) {
                ?>
                <tr>
                    <td><b><?php echo $d['no_rm']; ?></b></td>
                    <td><?php echo $d['nama_lengkap']; ?></td>
                    <td><?php echo $d['jk']; ?></td>
                    <td><?php echo date('d-m-Y', strtotime($d['tgl_lahir'])); ?></td>
                    <td><?php echo $d['no_telp']; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($d['waktu_daftar'])); ?></td>
                </tr>
                <?php } ?>
            </table>
        </div>
    </div>

</body>
</html>