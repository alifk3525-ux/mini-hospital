<?php
include 'koneksi.php';

// Tambah Kamar Baru
if (isset($_POST['simpan'])) {
    $nama = $_POST['nama_ruangan'];
    $kelas = $_POST['kelas'];
    $kapasitas = $_POST['kapasitas'];
    
    mysqli_query($koneksi, "INSERT INTO kamar_rawat (nama_ruangan, kelas, kapasitas, terisi) VALUES ('$nama', '$kelas', '$kapasitas', 0)");
    echo "<script>alert('Kamar berhasil ditambahkan!'); window.location='kelola_kamar.php';</script>";
}

// Update Jumlah Terisi
if (isset($_POST['update'])) {
    $id = $_POST['id_kamar'];
    $terisi = $_POST['terisi'];
    
    mysqli_query($koneksi, "UPDATE kamar_rawat SET terisi='$terisi' WHERE id_kamar='$id'");
    echo "<script>alert('Status kamar diperbarui!'); window.location='kelola_kamar.php';</script>";
}

// Hapus Kamar
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM kamar_rawat WHERE id_kamar='$id'");
    echo "<script>alert('Kamar dihapus!'); window.location='kelola_kamar.php';</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola Kamar Rawat Inap</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f4f4f9; }
        .kotak-form { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        input, select, button { padding: 10px; margin: 5px 0; width: 100%; box-sizing: border-box; }
        button { background: #28a745; color: white; border: none; cursor: pointer; font-weight: bold; }
        .btn-update { background: #007bff; width: auto; padding: 5px 10px; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #333; color: white; }
    </style>
</head>
<body>
    <h2>Panel Kelola Kamar Rawat Inap</h2>
    
    <div class="kotak-form">
        <form method="POST">
            <input type="text" name="nama_ruangan" placeholder="Nama Ruangan (Misal: Melati)" required>
            <input type="text" name="kelas" placeholder="Kelas (Misal: VIP / Kelas 1)" required>
            <input type="number" name="kapasitas" placeholder="Total Kapasitas Kasur" required>
            <button type="submit" name="simpan">+ Tambah Kamar Baru</button>
        </form>
    </div>

    <table>
        <tr>
            <th>Ruangan</th>
            <th>Kelas</th>
            <th>Kapasitas</th>
            <th>Terisi</th>
            <th>Tersedia (Sisa)</th>
            <th>Aksi</th>
        </tr>
        <?php
        $query = mysqli_query($koneksi, "SELECT * FROM kamar_rawat ORDER BY kelas ASC");
        while ($data = mysqli_fetch_assoc($query)) { 
            $sisa = $data['kapasitas'] - $data['terisi'];
        ?>
        <tr>
            <td><?php echo $data['nama_ruangan']; ?></td>
            <td><?php echo $data['kelas']; ?></td>
            <td><?php echo $data['kapasitas']; ?> Kasur</td>
            <td>
                <!-- Form kecil untuk update jumlah terisi langsung di dalam tabel -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="id_kamar" value="<?php echo $data['id_kamar']; ?>">
                    <input type="number" name="terisi" value="<?php echo $data['terisi']; ?>" style="width: 70px; display:inline;" min="0" max="<?php echo $data['kapasitas']; ?>">
                    <button type="submit" name="update" class="btn-update">Update</button>
                </form>
            </td>
            <td style="font-weight: bold; color: <?php echo ($sisa > 0) ? 'green' : 'red'; ?>;">
                <?php echo $sisa; ?> Kosong
            </td>
            <td><a href="?hapus=<?php echo $data['id_kamar']; ?>" onclick="return confirm('Yakin hapus kamar ini?');" style="color: red;">Hapus</a></td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>