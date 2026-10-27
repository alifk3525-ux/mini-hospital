<?php
include 'koneksi.php';

// Proses Hapus Satuan Berdasarkan ID RM
if (isset($_GET['hapus'])) {
    $id_rm = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    mysqli_query($koneksi, "DELETE FROM rekam_medis WHERE id_rm = '$id_rm'");
    echo "<script>alert('Riwayat rekam medis berhasil dihapus!'); window.location='riwayat_medis.php';</script>";
    exit;
}

// Proses Kosongkan SELURUH Rekam Medis
if (isset($_POST['kosongkan_semua_rm'])) {
    mysqli_query($koneksi, "DELETE FROM rekam_medis");
    echo "<script>alert('Seluruh riwayat rekam medis berhasil dikosongkan!'); window.location='riwayat_medis.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Medis Pasien</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f4f8; padding: 20px; margin: 0; color: #1e293b; }
        .wadah { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 1100px; margin: 0 auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        th, td { border: 1px solid #e2e8f0; padding: 12px; text-align: left; vertical-align: middle; }
        th { background: #3b82f6; color: white; font-weight: 600; }
        tr:hover { background-color: #f8fafc; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block; margin-top: 4px; }
        .badge-aktif { background: #fef3c7; color: #d97706; }
        .badge-selesai { background: #dcfce7; color: #15803d; }
        .badge-koding { background: #e0f2fe; color: #0369a1; }
        .btn-edit { background: #f59e0b; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 12px; margin-right: 5px; display: inline-block; }
        .btn-edit:hover { background: #d97706; }
        .btn-hapus { background: #ef4444; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 12px; display: inline-block; }
        .btn-hapus:hover { background: #dc2626; }
        .btn-clear-all { background: #b91c1c; color: white; border: none; padding: 8px 14px; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 13px; }
        .btn-clear-all:hover { background: #991b1b; }
    </style>
</head>
<body>
    <div class="wadah">
        <div class="top-bar">
            <div>
                <h2 style="color: #2c3e50; margin: 0;">📂 Riwayat Rekam Medis & Pembersihan Data</h2>
                <p style="color: #64748b; font-size: 14px; margin: 5px 0 0 0;">Daftar seluruh rekam jejak pemeriksaan pasien yang valid (non-batal).</p>
            </div>
            <form method="POST" onsubmit="return confirm('PERINGATAN: Yakin ingin menghapus dan mengosongkan SELURUH riwayat rekam medis di sistem?');">
                <button type="submit" name="kosongkan_semua_rm" class="btn-clear-all">🗑️ Kosongkan Semua Rekam Medis</button>
            </form>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Tanggal Periksa</th>
                    <th>No. RM & Nama Pasien</th>
                    <th>No. Registrasi & Layanan</th>
                    <th>Dokter (DPJP)</th>
                    <th>Diagnosa Utama</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Kueri diperbaiki dengan COALESCE untuk DPJP & Diagnosa, serta penyaringan Kunjungan Batal
                $query = mysqli_query($koneksi, "SELECT rm.*, p.nama_lengkap, 
                            COALESCE(k.jenis_layanan, 'Rawat Jalan') AS jenis_layanan, 
                            COALESCE(k.status_kunjungan, 'Selesai') AS status_kunjungan,
                            COALESCE(rm.dpjp, k.dpjp, '-') AS dpjp_fix,
                            COALESCE(NULLIF(rm.diagnosa_utama, ''), rm.icd_utama, '-') AS diagnosa_fix
                         FROM rekam_medis rm 
                         JOIN pasien p ON rm.no_rm = p.no_rm 
                         LEFT JOIN kunjungan k ON rm.no_registrasi = k.no_registrasi 
                         WHERE (k.status_kunjungan IS NULL OR k.status_kunjungan != 'Batal')
                         ORDER BY rm.tgl_periksa DESC LIMIT 50");
                
                if(! $query || mysqli_num_rows($query) == 0) {
                    echo "<tr><td colspan='6' style='text-align:center; padding: 25px; color: #64748b;'>Belum ada data rekam medis tercatat.</td></tr>";
                } else {
                    while($d = mysqli_fetch_assoc($query)) {
                        $status = $d['status_kunjungan'];
                        $badge_class = 'badge-aktif';
                        if (strpos($status, 'Selesai') !== false || strpos($status, 'Pulang') !== false || strpos($status, 'Klaim') !== false) {
                            $badge_class = 'badge-selesai';
                        } elseif ($status == 'Menunggu Koding') {
                            $badge_class = 'badge-koding';
                        }
                ?>
                <tr>
                    <td><?php echo date('d-m-Y H:i', strtotime($d['tgl_periksa'])); ?></td>
                    <td>
                        <strong><?php echo strtoupper($d['nama_lengkap']); ?></strong><br>
                        <small style="color:#64748b;">RM: <?php echo $d['no_rm']; ?></small>
                    </td>
                    <td>
                        <strong style="color: #2563eb;"><?php echo $d['no_registrasi']; ?></strong><br>
                        <small style="color: #475569;"><?php echo $d['jenis_layanan']; ?></small><br>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                    </td>
                    <td><b><?php echo $d['dpjp_fix']; ?></b></td>
                    <td><?php echo $d['diagnosa_fix']; ?></td>
                    <td style="text-align: center; white-space: nowrap;">
                        <a href="edit_cppt.php?id=<?php echo $d['id_rm']; ?>" class="btn-edit">✏️ Edit</a>
                        <a href="?hapus=<?php echo $d['id_rm']; ?>" class="btn-hapus" onclick="return confirm('Yakin ingin menghapus riwayat rekam medis ini?');">🗑️ Hapus</a>
                    </td>
                </tr>
                <?php 
                    } 
                } 
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>