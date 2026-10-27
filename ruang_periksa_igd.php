<?php
include 'koneksi.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pelayanan IGD (Gawat Darurat)</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; margin: 0; }
        .header { background: #dc2626; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { margin: 0; color: #fee2e2; }
        
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 13px; vertical-align: middle; }
        th { background: #ef4444; color: white; }
        tr:hover { background-color: #fef2f2; }
        
        /* Tata Letak Tombol Aksi */
        .btn-aksi { display: block; text-decoration: none; padding: 6px 10px; border-radius: 4px; font-weight: bold; font-size: 11px; text-align: center; margin-bottom: 4px; }
        .btn-rawat { background: #10b981; color: white; }
        .btn-rawat:hover { background: #059669; }
        .btn-skrining { background: #0284c7; color: white; }
        .btn-skrining:hover { background: #0369a1; }
        .btn-consent { background: #dc2626; color: white; }
        .btn-consent:hover { background: #b91c1c; }
        
        .badge-triase { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; display: inline-block; background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h2>🚨 Instalasi Gawat Darurat (IGD)</h2>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: #fee2e2;">Manajemen Resusitasi, Observasi, dan Tindakan Darurat Pasien</p>
        </div>
    </div>

    <table>
        <tr>
            <th>No. Registrasi</th>
            <th>Pasien & No. RM</th>
            <th>Jalur Triase / Ruangan</th>
            <th>Keluhan Utama</th>
            <th>Waktu Masuk</th>
            <th style="text-align: center; width: 190px;">Aksi Penanganan</th>
        </tr>
        <?php
        $query = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk 
                 FROM kunjungan k 
                 JOIN pasien p ON k.no_rm = p.no_rm 
                 WHERE k.jenis_layanan = 'IGD' 
                 AND k.status_kunjungan = 'Aktif' 
                 ORDER BY k.tgl_masuk ASC");
        
        if(mysqli_num_rows($query) == 0) {
            echo "<tr><td colspan='6' style='text-align:center; padding: 30px; color: #64748b;'>Tidak ada pasien IGD aktif saat ini.</td></tr>";
        }
        while($d = mysqli_fetch_assoc($query)) {
        ?>
        <tr>
            <td><strong style="color: #dc2626;"><?php echo $d['no_registrasi']; ?></strong></td>
            <td>
                <strong><?php echo $d['nama_lengkap']; ?></strong><br>
                <small style="color: #64748b;">RM: <?php echo $d['no_rm']; ?> (<?php echo $d['jk']; ?>)</small>
            </td>
            <td><span class="badge-triase"><?php echo $d['tujuan_layanan']; ?></span></td>
            <td><i>"<?php echo $d['keluhan_utama']; ?>"</i></td>
            <td><?php echo date('d-m-Y H:i', strtotime($d['tgl_masuk'])); ?></td>
            <td style="text-align: center;">
                <a href="form_cppt_igd.php?reg=<?php echo $d['no_registrasi']; ?>" class="btn-aksi btn-rawat">⚡ Periksa & Tindakan IGD</a>
                <a href="skrining_awal.php?reg=<?php echo $d['no_registrasi']; ?>" target="_blank" class="btn-aksi btn-skrining">🩺 Lembar Skrining</a>
                <a href="informed_consent.php?reg=<?php echo $d['no_registrasi']; ?>" target="_blank" class="btn-aksi btn-consent">✍️ Informed Consent</a>
            </td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>