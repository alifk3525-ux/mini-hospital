<?php
include 'koneksi.php';

// Ambil daftar order laboratorium
$query = mysqli_query($koneksi, "SELECT o.*, p.nama_lengkap, p.jk 
         FROM order_lab o 
         JOIN pasien p ON o.no_rm = p.no_rm 
         ORDER BY o.tgl_order DESC LIMIT 50");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Instalasi Laboratorium - Mini Hospital</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; margin: 0; }
        .header { background: #0284c7; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { margin: 0; color: #e0f2fe; }
        
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.08); font-size: 13px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        th { background: #0369a1; color: white; font-weight: bold; }
        tr:hover { background: #f0f9ff; }
        
        .badge-status { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .badge-menunggu { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge-selesai { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        
        .btn-input { background: #0284c7; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 12px; display: inline-block; }
        .btn-input:hover { background: #0369a1; }
        .btn-cetak { background: #10b981; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 12px; display: inline-block; }
        .btn-cetak:hover { background: #059669; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h2>🔬 Instalasi Laboratorium Klinis</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: #bae6fd;">Daftar Antrean Sampel Pemeriksaan & Penginputan Hasil Analisis Laboratorium</p>
        </div>
    </div>

    <table>
        <tr>
            <th>No. Order Lab</th>
            <th>Pasien & No. RM</th>
            <th>Asal Unit & Dokter Pengirim</th>
            <th>Diagnosis Klinis</th>
            <th>Waktu Permintaan</th>
            <th>Status</th>
            <th style="text-align: center; width: 170px;">Aksi Analis</th>
        </tr>
        <?php
        if (mysqli_num_rows($query) == 0) {
            echo "<tr><td colspan='7' style='text-align:center; padding: 30px; color: #64748b;'>Belum ada permintaan uji laboratorium saat ini.</td></tr>";
        }
        while ($d = mysqli_fetch_assoc($query)) {
        ?>
        <tr>
            <td><strong style="color: #0284c7;"><?php echo $d['no_order']; ?></strong></td>
            <td>
                <strong><?php echo $d['nama_lengkap']; ?></strong> (<?php echo $d['jk']; ?>)<br>
                <small style="color: #64748b;">RM: <?php echo $d['no_rm']; ?> | Reg: <?php echo $d['no_registrasi']; ?></small>
            </td>
            <td>
                <b><?php echo $d['unit_pengirim']; ?></b><br>
                <small style="color: #64748b;"><?php echo $d['dokter_pengirim']; ?></small>
            </td>
            <td><i>"<?php echo $d['diagnosa_klinis']; ?>"</i></td>
            <td><?php echo date('d-m-Y H:i', strtotime($d['tgl_order'])); ?> WIB</td>
            <td>
                <span class="badge-status <?php echo ($d['status_lab'] == 'Selesai') ? 'badge-selesai' : 'badge-menunggu'; ?>">
                    <?php echo $d['status_lab']; ?>
                </span>
            </td>
            <td style="text-align: center;">
                <a href="input_hasil_lab.php?no_order=<?php echo $d['no_order']; ?>" class="btn-input">
                    <?php echo ($d['status_lab'] == 'Selesai') ? '✏️ Edit Nilai' : '🧪 Input Hasil'; ?>
                </a>
                <?php if ($d['status_lab'] == 'Selesai'): ?>
                    <a href="input_hasil_lab.php?no_order=<?php echo $d['no_order']; ?>&cetak=1" target="_blank" class="btn-cetak">🖨️ Cetak</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>