<?php
include 'koneksi.php';
$dpjp = isset($_GET['dpjp']) ? $_GET['dpjp'] : '';

if(empty($dpjp)) {
    echo "<script>alert('Akses ditolak. Silakan login terlebih dahulu.'); window.location='dashboard_dokter.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ruang Periksa Dokter</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; padding: 20px; margin: 0; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        h2 { margin: 0; font-size: 24px; color: #FFD700; }
        .btn-keluar { background: #ef4444; color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; font-weight: bold; font-size: 14px; }
        
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        th { background: #3b82f6; color: white; }
        tr:hover { background: #f8fafc; }
        
        /* Tombol Aksi */
        .btn-aksi { display: block; text-decoration: none; padding: 6px 10px; border-radius: 4px; font-weight: bold; font-size: 11px; text-align: center; margin-bottom: 4px; }
        .btn-periksa { background: #10b981; color: white; }
        .btn-periksa:hover { background: #059669; }
        .btn-surat { background: #475569; color: white; }
        .btn-surat:hover { background: #334155; }
        .btn-consent { background: #dc2626; color: white; }
        .btn-consent:hover { background: #b91c1c; }
        
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h2>👨‍⚕️ <?php echo $dpjp; ?></h2>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #cbd5e1;">Daftar Antrean Pasien Hari Ini: <?php echo date('d M Y'); ?></p>
        </div>
        <a href="dashboard_dokter.php" class="btn-keluar">Ganti Dokter ➔</a>
    </div>

    <table>
        <tr>
            <th>No. Registrasi</th>
            <th>No. RM & Nama Pasien</th>
            <th>Tujuan/Poli</th>
            <th>Keluhan Utama</th>
            <th>Status</th>
            <th style="text-align: center; width: 190px;">Aksi</th>
        </tr>
        <?php
        // Menggabungkan tabel kunjungan dan biodata pasien, difilter berdasarkan nama DPJP hari ini dan status Aktif
        $hari_ini = date('Y-m-d');
        $query = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, p.tgl_lahir 
                 FROM kunjungan k 
                 JOIN pasien p ON k.no_rm = p.no_rm 
                 WHERE k.dpjp = '$dpjp' 
                 AND DATE(k.tgl_masuk) = '$hari_ini' 
                 AND k.status_kunjungan = 'Aktif'
                 ORDER BY k.tgl_masuk ASC");
        
        if(mysqli_num_rows($query) == 0) {
            echo "<tr><td colspan='6' style='text-align:center; padding: 30px; color: #64748b;'>Belum ada pasien aktif yang didaftarkan ke Anda hari ini.</td></tr>";
        }
        
        while($d = mysqli_fetch_assoc($query)) {
        ?>
        <tr>
            <td style="font-weight: bold; color: #3b82f6;"><?php echo $d['no_registrasi']; ?></td>
            <td>
                <strong><?php echo $d['nama_lengkap']; ?></strong><br>
                <small style="color: #64748b;">RM: <?php echo $d['no_rm']; ?> | <?php echo $d['jk']; ?></small>
            </td>
            <td><?php echo $d['tujuan_layanan']; ?></td>
            <td><i>"<?php echo $d['keluhan_utama']; ?>"</i></td>
            <td><span class="status-badge"><?php echo $d['status_kunjungan']; ?></span></td>
            <td style="text-align: center;">
                <a href="form_rm.php?reg=<?php echo $d['no_registrasi']; ?>" class="btn-aksi btn-periksa">📝 Rekam Medis</a>
                <a href="surat_dokter.php?reg=<?php echo $d['no_registrasi']; ?>" target="_blank" class="btn-aksi btn-surat">📄 Terbitkan Surat</a>
                <a href="informed_consent.php?reg=<?php echo $d['no_registrasi']; ?>" target="_blank" class="btn-aksi btn-consent">✍️ Informed Consent</a>
            </td>
        </tr>
        <?php } ?>
    </table>

</body>
</html>