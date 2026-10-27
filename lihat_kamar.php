<?php include 'koneksi.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Informasi Kamar - Mini Hospital</title>
    <style>
        body { font-family: Arial; text-align: center; margin: 0; padding: 40px; background-color: #4B5320; color: white; }
        .judul { color: #FFD700; font-size: 35px; font-weight: bold; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        table { width: 80%; margin: 0 auto; border-collapse: collapse; background: white; color: black; border-radius: 10px; overflow: hidden; box-shadow: 0 8px 16px rgba(0,0,0,0.3); }
        th, td { padding: 15px; border-bottom: 1px solid #ddd; font-size: 20px; }
        th { background: #333816; color: #FFD700; }
        tr:hover { background-color: #f1f1f1; }
        .btn-kembali { display: inline-block; padding: 10px 20px; margin-top: 30px; background: rgba(0,0,0,0.5); color: white; text-decoration: none; border: 1px solid white; border-radius: 5px; }
    </style>
</head>
<body>

    <div class="judul">INFORMASI KETERSEDIAAN KAMAR RAWAT INAP</div>
    
    <table>
        <tr>
            <th>Ruangan</th>
            <th>Kelas</th>
            <th>Tersedia (Kosong)</th>
        </tr>
        <?php
        $query = mysqli_query($koneksi, "SELECT * FROM kamar_rawat ORDER BY kelas ASC");
        while ($data = mysqli_fetch_assoc($query)) { 
            $sisa = $data['kapasitas'] - $data['terisi'];
        ?>
        <tr>
            <td style="font-weight: bold;"><?php echo $data['nama_ruangan']; ?></td>
            <td><?php echo $data['kelas']; ?></td>
            <td style="font-weight: bold; font-size: 24px; color: <?php echo ($sisa > 0) ? '#28a745' : '#dc3545'; ?>;">
                <?php echo ($sisa > 0) ? $sisa . ' KASUR' : 'PENUH'; ?>
            </td>
        </tr>
        <?php } ?>
    </table>

    <a href="apm.php" class="btn-kembali">Kembali ke Menu Utama</a>

</body>
</html>