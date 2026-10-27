<?php include 'koneksi.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Dasbor Petugas - Mini Hospital</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; margin: 0; }
        
        /* Header dengan posisi relatif agar tombol reset bisa diposisikan di kanan */
        .header { position: relative; background: #4B5320; color: #FFD700; padding: 15px; text-align: center; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .header h2 { margin: 0; }
        
        /* Tombol Reset Global */
        .btn-reset-global { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background-color: #dc3545; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 14px; transition: 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        .btn-reset-global:hover { background-color: #c82333; }

        /* Layout 3 Kolom */
        .container-kolom { display: flex; gap: 20px; }
        .kolom { flex: 1; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); display: flex; flex-direction: column; gap: 15px; }
        
        /* Warna Header Masing-masing Kolom */
        .title-rj { background: #28a745; color: white; padding: 10px; text-align: center; border-radius: 5px; font-weight: bold; }
        .title-ri { background: #ffc107; color: black; padding: 10px; text-align: center; border-radius: 5px; font-weight: bold; }
        .title-g  { background: #17a2b8; color: white; padding: 10px; text-align: center; border-radius: 5px; font-weight: bold; }

        /* Kotak Pasien Aktif / Sedang Dipanggil */
        .panel-aktif { background: #f8f9fa; border: 2px dashed #ccc; padding: 15px; border-radius: 6px; text-align: center; }
        .nomor-aktif { font-size: 32px; font-weight: bold; color: #333; margin: 5px 0; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: center; font-size: 14px; }
        th { background: #333; color: white; }
        
        .btn-panggil { background: #28a745; color: white; border: none; padding: 6px 10px; font-size: 13px; border-radius: 4px; cursor: pointer; }
        .btn-panggil:hover { background: #218838; }
        .btn-recall { background: #ffc107; color: black; border: none; padding: 8px 15px; font-size: 14px; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 5px; }
        .btn-recall:hover { background: #e0a800; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Dasbor Pemanggilan Petugas per Loket</h2>
        
        <!-- Form Reset Dipindahkan ke Sini -->
        <form action="reset_antrean.php" method="POST" onsubmit="return confirm('Peringatan: Yakin ingin menghapus dan mengulang semua antrean dari angka 0?');" style="margin: 0;">
            <button type="submit" class="btn-reset-global">🗑️ Reset Antrean</button>
        </form>
    </div>

    <div class="container-kolom">
        
        <!-- KOLOM 1: RAWAT JALAN -->
        <div class="kolom">
            <div class="title-rj">LOKET RAWAT JALAN (RJ)</div>
            
            <div class="panel-aktif">
                <small style="color: #666; font-weight: bold;">SEDANG DILAYANI</small>
                <?php
                $akt_rj = mysqli_query($koneksi, "SELECT * FROM antrean WHERE status='Dipanggil' AND nomor_antrean LIKE 'RJ-%' ORDER BY id_antrean DESC LIMIT 1");
                $d_akt_rj = mysqli_fetch_assoc($akt_rj);
                if ($d_akt_rj) {
                    echo '<div class="nomor-aktif">'.$d_akt_rj['nomor_antrean'].'</div>';
                    echo '<button class="btn-recall" onclick="panggilUlang(\''.$d_akt_rj['nomor_antrean'].'\', \'Rawat Jalan\')">🔄 Panggil Ulang</button>';
                } else {
                    echo '<div class="nomor-aktif" style="font-size: 20px; color: #999;">- Kosong -</div>';
                }
                ?>
            </div>

            <div style="font-weight: bold; font-size: 14px; color: #444;">Antrean Menunggu:</div>
            <table>
                <tr><th>Nomor</th><th>Aksi</th></tr>
                <?php
                $q_rj = mysqli_query($koneksi, "SELECT * FROM antrean WHERE status='Menunggu' AND nomor_antrean LIKE 'RJ-%' ORDER BY id_antrean ASC");
                if(mysqli_num_rows($q_rj) == 0) {
                    echo "<tr><td colspan='2' style='color:#777;'>Tidak ada antrean</td></tr>";
                }
                while ($d_rj = mysqli_fetch_assoc($q_rj)) {
                ?>
                <tr>
                    <td style="font-size: 16px; font-weight: bold;"><?php echo $d_rj['nomor_antrean']; ?></td>
                    <td>
                        <button class="btn-panggil" onclick="panggilSuara(<?php echo $d_rj['id_antrean']; ?>, '<?php echo $d_rj['nomor_antrean']; ?>', 'Rawat Jalan')">🔊 Panggil</button>
                    </td>
                </tr>
                <?php } ?>
            </table>
        </div>

        <!-- KOLOM 2: RAWAT INAP -->
        <div class="kolom">
            <div class="title-ri">LOKET RAWAT INAP (RI)</div>
            
            <div class="panel-aktif">
                <small style="color: #666; font-weight: bold;">SEDANG DILAYANI</small>
                <?php
                $akt_ri = mysqli_query($koneksi, "SELECT * FROM antrean WHERE status='Dipanggil' AND nomor_antrean LIKE 'RI-%' ORDER BY id_antrean DESC LIMIT 1");
                $d_akt_ri = mysqli_fetch_assoc($akt_ri);
                if ($d_akt_ri) {
                    echo '<div class="nomor-aktif">'.$d_akt_ri['nomor_antrean'].'</div>';
                    echo '<button class="btn-recall" onclick="panggilUlang(\''.$d_akt_ri['nomor_antrean'].'\', \'Rawat Inap\')">🔄 Panggil Ulang</button>';
                } else {
                    echo '<div class="nomor-aktif" style="font-size: 20px; color: #999;">- Kosong -</div>';
                }
                ?>
            </div>

            <div style="font-weight: bold; font-size: 14px; color: #444;">Antrean Menunggu:</div>
            <table>
                <tr><th>Nomor</th><th>Aksi</th></tr>
                <?php
                $q_ri = mysqli_query($koneksi, "SELECT * FROM antrean WHERE status='Menunggu' AND nomor_antrean LIKE 'RI-%' ORDER BY id_antrean ASC");
                if(mysqli_num_rows($q_ri) == 0) {
                    echo "<tr><td colspan='2' style='color:#777;'>Tidak ada antrean</td></tr>";
                }
                while ($d_ri = mysqli_fetch_assoc($q_ri)) {
                ?>
                <tr>
                    <td style="font-size: 16px; font-weight: bold;"><?php echo $d_ri['nomor_antrean']; ?></td>
                    <td>
                        <button class="btn-panggil" onclick="panggilSuara(<?php echo $d_ri['id_antrean']; ?>, '<?php echo $d_ri['nomor_antrean']; ?>', 'Rawat Inap')">🔊 Panggil</button>
                    </td>
                </tr>
                <?php } ?>
            </table>
        </div>

        <!-- KOLOM 3: INFORMASI -->
        <div class="kolom">
            <div class="title-g">LOKET INFORMASI (G)</div>
            
            <div class="panel-aktif">
                <small style="color: #666; font-weight: bold;">SEDANG DILAYANI</small>
                <?php
                $akt_g = mysqli_query($koneksi, "SELECT * FROM antrean WHERE status='Dipanggil' AND nomor_antrean LIKE 'G-%' ORDER BY id_antrean DESC LIMIT 1");
                $d_akt_g = mysqli_fetch_assoc($akt_g);
                if ($d_akt_g) {
                    echo '<div class="nomor-aktif">'.$d_akt_g['nomor_antrean'].'</div>';
                    echo '<button class="btn-recall" onclick="panggilUlang(\''.$d_akt_g['nomor_antrean'].'\', \'Informasi\')">🔄 Panggil Ulang</button>';
                } else {
                    echo '<div class="nomor-aktif" style="font-size: 20px; color: #999;">- Kosong -</div>';
                }
                ?>
            </div>

            <div style="font-weight: bold; font-size: 14px; color: #444;">Antrean Menunggu:</div>
            <table>
                <tr><th>Nomor</th><th>Aksi</th></tr>
                <?php
                $q_g = mysqli_query($koneksi, "SELECT * FROM antrean WHERE status='Menunggu' AND nomor_antrean LIKE 'G-%' ORDER BY id_antrean ASC");
                if(mysqli_num_rows($q_g) == 0) {
                    echo "<tr><td colspan='2' style='color:#777;'>Tidak ada antrean</td></tr>";
                }
                while ($d_g = mysqli_fetch_assoc($q_g)) {
                ?>
                <tr>
                    <td style="font-size: 16px; font-weight: bold;"><?php echo $d_g['nomor_antrean']; ?></td>
                    <td>
                        <button class="btn-panggil" onclick="panggilSuara(<?php echo $d_g['id_antrean']; ?>, '<?php echo $d_g['nomor_antrean']; ?>', 'Informasi')">🔊 Panggil</button>
                    </td>
                </tr>
                <?php } ?>
            </table>
        </div>

    </div>

    <script>
    function panggilSuara(id, nomor, poli) {
        window.location.href = "update_status.php?id=" + id;
    }

    function panggilUlang(nomor, poli) {
        // Mengirim sinyal pemicu ke server tanpa melakukan refresh halaman
        fetch("aksi_recall.php?nomor=" + nomor + "&poli=" + poli);
    }

    // Karena suara murni keluar dari server monitor, dasbor bebas di-refresh 
    // tanpa takut memotong suara
    setInterval(function() {
        location.reload();
    }, 4000);
</script>

</body>
</html>