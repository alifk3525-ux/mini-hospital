<?php
include 'koneksi.php';

// Proses Simpan Data Baru
if (isset($_POST['simpan'])) {
    $nama = $_POST['nama_dokter'];
    $poli = $_POST['poli'];
    $hari = $_POST['hari'];
    $jam = $_POST['jam_praktik'];
    
    mysqli_query($koneksi, "INSERT INTO jadwal_dokter (nama_dokter, poli, hari, jam_praktik) VALUES ('$nama', '$poli', '$hari', '$jam')");
    echo "<script>alert('Jadwal berhasil ditambahkan!'); window.location='kelola_jadwal.php';</script>";
}

// Proses Hapus Data
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM jadwal_dokter WHERE id_dokter='$id'");
    echo "<script>alert('Jadwal dihapus!'); window.location='kelola_jadwal.php';</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Jadwal Dokter</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f4f4f9; }
        .kotak-form { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        input, select, button { padding: 10px; margin: 5px 0; width: 100%; box-sizing: border-box; }
        button { background: #28a745; color: white; border: none; cursor: pointer; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #333; color: white; }
    </style>
</head>
<body>
    <h2>Panel Kelola Jadwal Dokter</h2>
    
    <div class="kotak-form">
        <form method="POST">
            <input type="text" name="nama_dokter" placeholder="Nama Dokter (Misal: dr. Budi Santoso, Sp.A)" required>
            <input type="text" name="poli" placeholder="Poliklinik (Misal: Anak / Penyakit Dalam)" required>
            <input type="text" name="hari" placeholder="Hari Praktik (Misal: Senin - Rabu)" required>
            <input type="text" name="jam_praktik" placeholder="Jam Praktik (Misal: 08:00 - 12:00)" required>
            <button type="submit" name="simpan">+ Tambah Jadwal</button>
        </form>
    </div>

    <table>
        <tr>
            <th>Nama Dokter</th>
            <th>Poli</th>
            <th>Hari</th>
            <th>Jam</th>
            <th>Aksi</th>
        </tr>
        <?php
        $query = mysqli_query($koneksi, "SELECT * FROM jadwal_dokter ORDER BY poli ASC");
        while ($data = mysqli_fetch_assoc($query)) { ?>
        <tr>
            <td><?php echo $data['nama_dokter']; ?></td>
            <td><?php echo $data['poli']; ?></td>
            <td><?php echo $data['hari']; ?></td>
            <td><?php echo $data['jam_praktik']; ?></td>
            <td><a href="?hapus=<?php echo $data['id_dokter']; ?>" onclick="return confirm('Yakin hapus dokter ini?');" style="color: red;">Hapus</a></td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>