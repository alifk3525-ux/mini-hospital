<?php
include 'koneksi.php';

// ================= PROSES DATA DOKTER =================
if (isset($_POST['simpan_dokter'])) {
    $nama = $_POST['nama_dokter'];
    $poli = $_POST['poli'];
    $hari = $_POST['hari'];
    // Menggabungkan jam mulai dan jam selesai otomatis
    $jam = $_POST['jam_mulai'] . ' - ' . $_POST['jam_selesai']; 
    
    mysqli_query($koneksi, "INSERT INTO jadwal_dokter (nama_dokter, poli, hari, jam_praktik) VALUES ('$nama', '$poli', '$hari', '$jam')");
    echo "<script>alert('Jadwal dokter ditambahkan!'); window.location='admin.php';</script>";
}
if (isset($_GET['hapus_dokter'])) {
    $id = $_GET['hapus_dokter'];
    mysqli_query($koneksi, "DELETE FROM jadwal_dokter WHERE id_dokter='$id'");
    echo "<script>alert('Jadwal dokter dihapus!'); window.location='admin.php';</script>";
}

// ================= PROSES DATA KAMAR =================
if (isset($_POST['simpan_kamar'])) {
    $nama = $_POST['nama_ruangan'];
    $kelas = $_POST['kelas'];
    $kapasitas = $_POST['kapasitas'];
    mysqli_query($koneksi, "INSERT INTO kamar_rawat (nama_ruangan, kelas, kapasitas, terisi) VALUES ('$nama', '$kelas', '$kapasitas', 0)");
    echo "<script>alert('Kamar ditambahkan!'); window.location='admin.php';</script>";
}
if (isset($_POST['update_kamar'])) {
    $id = $_POST['id_kamar'];
    $terisi = $_POST['terisi'];
    mysqli_query($koneksi, "UPDATE kamar_rawat SET terisi='$terisi' WHERE id_kamar='$id'");
    echo "<script>alert('Status kamar diperbarui!'); window.location='admin.php';</script>";
}
if (isset($_GET['hapus_kamar'])) {
    $id = $_GET['hapus_kamar'];
    mysqli_query($koneksi, "DELETE FROM kamar_rawat WHERE id_kamar='$id'");
    echo "<script>alert('Kamar dihapus!'); window.location='admin.php';</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Panel Admin - Mini Hospital</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #e9ecef; }
        .header { background: #333816; color: #FFD700; padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .grid-container { display: flex; gap: 20px; align-items: flex-start; }
        .card { background: white; padding: 20px; border-radius: 8px; flex: 1; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        input, select, button { padding: 10px; margin: 5px 0; width: 100%; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .form-row { display: flex; gap: 10px; }
        .form-row > div { flex: 1; }
        .form-row label { font-size: 12px; font-weight: bold; color: #555; }
        .btn-tambah { background: #28a745; color: white; border: none; font-weight: bold; cursor: pointer; margin-top: 10px; padding: 12px; }
        .btn-tambah:hover { background: #218838; }
        .btn-update { background: #007bff; width: auto; padding: 5px 10px; color: white; border: none; cursor: pointer; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    
    <div class="header">
        <h2 style="margin: 0;">Dasbor Manajemen Data RS</h2>
        <p style="margin: 5px 0 0 0; color: white;">Kelola Jadwal Dokter & Ketersediaan Kamar</p>
    </div>
    
    <div class="grid-container">
        
        <!-- BAGIAN KIRI: PANEL DOKTER -->
        <div class="card">
            <h3 style="color: #4B5320; margin-top: 0;">🩺 Kelola Jadwal Dokter</h3>
            <form method="POST">
                <input type="text" name="nama_dokter" placeholder="Nama & Gelar (Misal: dr. Budi, Sp.PD)" required>
                
                <select name="poli" required>
                    <option value="">-- Pilih Poliklinik/Unit --</option>
                    <option value="Brakhiterapi">Brakhiterapi</option>
                    <option value="Endoscopie">Endoscopie</option>
                    <option value="Hemodialisa">Hemodialisa</option>
                    <option value="Kemoterapi">Kemoterapi</option>
                    <option value="Radioterapi">Radioterapi</option>
                    <option value="Poli Anak">Klinik Spesialis Anak</option>
                    <option value="Poli Penyakit Dalam">Klinik Spesialis Penyakit Dalam</option>
                    <option value="Poli Jantung & Pembuluh Darah">Klinik Spesialis Jantung & Pembuluh Darah</option>
                    <option value="Poli Bedah">Klinik Spesialis Bedah</option>
                    <option value="Poli Bedah Onkologi">Klinik Spesialis Bedah Onkologi</option>
                    <option value="Poli Bedah Syaraf">Klinik Spesialis Bedah Syaraf</option>
                    <option value="Poli Kebidanan (Obgyn)">Klinik Spesialis Kebidanan (Obgyn)</option>
                    <option value="Poli Gigi">Klinik Gigi</option>
                    <option value="Poli Mata">Klinik Spesialis Mata</option>
                    <option value="Poli THT">Klinik Spesialis THT</option>
                    <option value="Poli Paru">Klinik Spesialis Paru</option>
                    <option value="Poli Urologi">Klinik Spesialis Urologi</option>
                    <option value="Poli Psikiatri">Klinik Psikiatri</option>
                    <option value="Rehabilitasi Medik">Klinik Rehabilitasi Medik</option>
                </select>

                <select name="hari" required>
                    <option value="">-- Pilih Hari Praktik --</option>
                    <option value="Senin - Jumat">Senin - Jumat</option>
                    <option value="Senin - Rabu">Senin - Rabu</option>
                    <option value="Kamis - Sabtu">Kamis - Sabtu</option>
                    <option value="Senin & Kamis">Senin & Kamis</option>
                    <option value="Selasa & Jumat">Selasa & Jumat</option>
                </select>

                <div class="form-row">
                    <div>
                        <label>Jam Mulai</label>
                        <input type="time" name="jam_mulai" required>
                    </div>
                    <div>
                        <label>Jam Selesai</label>
                        <input type="time" name="jam_selesai" required>
                    </div>
                </div>
                
                <button type="submit" name="simpan_dokter" class="btn-tambah">+ Tambah Dokter</button>
            </form>
            
            <table>
                <tr><th>Nama</th><th>Poli</th><th>Jadwal</th><th>Aksi</th></tr>
                <?php
                $q_dok = mysqli_query($koneksi, "SELECT * FROM jadwal_dokter ORDER BY poli ASC");
                while ($d = mysqli_fetch_assoc($q_dok)) { ?>
                <tr>
                    <td style="font-weight:bold; color:#2c3e50;"><?php echo $d['nama_dokter']; ?></td>
                    <td><?php echo $d['poli']; ?></td>
                    <td><?php echo $d['hari']."<br>".$d['jam_praktik']; ?></td>
                    <td><a href="?hapus_dokter=<?php echo $d['id_dokter']; ?>" onclick="return confirm('Hapus dokter ini?');" style="color:red; text-decoration:none; font-weight:bold;">Hapus</a></td>
                </tr>
                <?php } ?>
            </table>
        </div>

        <!-- BAGIAN KANAN: PANEL KAMAR -->
        <div class="card">
            <h3 style="color: #4B5320; margin-top: 0;">🛏️ Kelola Kamar Rawat</h3>
            <form method="POST">
                <input type="text" name="nama_ruangan" placeholder="Nama Ruangan (Misal: Mawar)" required>
                <input type="text" name="kelas" placeholder="Kelas (Misal: Kelas 1)" required>
                <input type="number" name="kapasitas" placeholder="Kapasitas Total Kasur" required>
                <button type="submit" name="simpan_kamar" class="btn-tambah">+ Tambah Kamar</button>
            </form>
            
            <table>
                <tr><th>Ruangan</th><th>Kelas</th><th>Status (Terisi)</th><th>Aksi</th></tr>
                <?php
                $q_kam = mysqli_query($koneksi, "SELECT * FROM kamar_rawat ORDER BY kelas ASC");
                while ($k = mysqli_fetch_assoc($q_kam)) { ?>
                <tr>
                    <td style="font-weight:bold;"><?php echo $k['nama_ruangan']; ?></td>
                    <td><?php echo $k['kelas']; ?><br><small>Total: <?php echo $k['kapasitas']; ?></small></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id_kamar" value="<?php echo $k['id_kamar']; ?>">
                            <input type="number" name="terisi" value="<?php echo $k['terisi']; ?>" style="width: 60px; display:inline;" min="0" max="<?php echo $k['kapasitas']; ?>">
                            <button type="submit" name="update_kamar" class="btn-update">Save</button>
                        </form>
                    </td>
                    <td><a href="?hapus_kamar=<?php echo $k['id_kamar']; ?>" onclick="return confirm('Hapus kamar ini?');" style="color:red; text-decoration:none; font-weight:bold;">Hapus</a></td>
                </tr>
                <?php } ?>
            </table>
        </div>

    </div>
</body>
</html>