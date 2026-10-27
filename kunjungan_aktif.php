<?php
include 'koneksi.php';

// Logika Pembatalan Kunjungan / Rawat
if (isset($_GET['batal'])) {
    $no_reg = mysqli_real_escape_string($koneksi, $_GET['batal']);

    // Cek detail layanan untuk mengembalikan kasur jika pasien batal rawat inap
    $cek = mysqli_query($koneksi, "SELECT jenis_layanan, tujuan_layanan, status_kunjungan FROM kunjungan WHERE no_registrasi='$no_reg'");
    $data_kunjungan = mysqli_fetch_assoc($cek);

    if ($data_kunjungan) {
        // Hanya kurangi kasur jika statusnya belum batal dan layanan Rawat Inap
        if ($data_kunjungan['jenis_layanan'] == 'Rawat Inap' && $data_kunjungan['status_kunjungan'] != 'Batal') {
            $nama_ruang = trim(explode(" - ", $data_kunjungan['tujuan_layanan'])[0]);
            $nama_ruang_esc = mysqli_real_escape_string($koneksi, $nama_ruang);
            
            // Mengurangi kapasitas terisi secara aman (mencegah nilai di bawah 0)
            mysqli_query($koneksi, "UPDATE kamar_rawat SET terisi = GREATEST(terisi - 1, 0) WHERE nama_ruangan = '$nama_ruang_esc'");
        }

        // Ubah status kunjungan menjadi Batal
        mysqli_query($koneksi, "UPDATE kunjungan SET status_kunjungan='Batal' WHERE no_registrasi='$no_reg'");
        
        echo "<script>alert('Kunjungan " . $no_reg . " berhasil dibatalkan!'); window.location='kunjungan_aktif.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Kunjungan Aktif - TPP Mini Hospital</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f4f8; padding: 20px; margin: 0; color: #1e293b; }
        .wadah { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 1100px; margin: 0 auto; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ef4444; padding-bottom: 12px; margin-bottom: 20px; }
        
        /* Filter Pencarian */
        .cari-box { display: flex; gap: 10px; margin-bottom: 20px; }
        .cari-box input { flex: 1; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; }
        .cari-box input:focus { border-color: #ef4444; box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.15); }
        .cari-box button { background: #3b82f6; color: white; border: none; padding: 9px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; transition: 0.2s; }
        .cari-box button:hover { background: #2563eb; }
        .btn-reset { background: #64748b; color: white; text-decoration: none; padding: 9px 15px; border-radius: 6px; font-weight: bold; font-size: 13px; display: inline-block; }
        .btn-reset:hover { background: #475569; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #e2e8f0; padding: 10px 12px; text-align: left; vertical-align: middle; }
        th { background: #ef4444; color: white; font-weight: 600; }
        tr:hover { background-color: #f8fafc; }
        
        .badge-status { background: #fef3c7; color: #d97706; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block; }
        .badge-layanan { padding: 3px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; }
        .badge-rj { background: #dcfce7; color: #15803d; }
        .badge-ri { background: #fef3c7; color: #b45309; }
        .badge-igd { background: #fee2e2; color: #b91c1c; }
        
        .btn-aksi { display: inline-block; text-decoration: none; padding: 6px 10px; border-radius: 4px; font-weight: bold; font-size: 12px; margin: 2px 0; transition: 0.2s; }
        .btn-batal { background: #ef4444; color: white; }
        .btn-batal:hover { background: #dc2626; }
        .btn-skrining { background: #0284c7; color: white; }
        .btn-skrining:hover { background: #0369a1; }
    </style>
</head>
<body>
    <div class="wadah">
        <div class="header-bar">
            <div>
                <h2 style="color: #2c3e50; margin: 0;">🛑 Manajemen Kunjungan Aktif & Batal Rawat</h2>
                <small style="color: #64748b;">Daftar pasien terdaftar hari ini (<b><?php echo date('d-m-Y'); ?></b>) yang berada dalam antrean aktif TPP.</small>
            </div>
        </div>

        <!-- Form Pencarian Cepat -->
        <form method="GET" class="cari-box">
            <input type="text" name="cari" value="<?php echo isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : ''; ?>" placeholder="Cari No. Registrasi / No. RM / Nama Pasien...">
            <button type="submit">🔍 Cari Data</button>
            <?php if(isset($_GET['cari']) && !empty($_GET['cari'])): ?>
                <a href="kunjungan_aktif.php" class="btn-reset">🔄 Reset Filter</a>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>No. Registrasi</th>
                    <th>Nama Pasien & RM</th>
                    <th>Layanan & Tujuan</th>
                    <th>DPJP & Cara Bayar</th>
                    <th>Status</th>
                    <th style="text-align: center; width: 210px;">Aksi TPP</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $hari_ini = date('Y-m-d');
                $kondisi_cari = "";

                if (isset($_GET['cari']) && !empty($_GET['cari'])) {
                    $keyword = mysqli_real_escape_string($koneksi, $_GET['cari']);
                    $kondisi_cari = " AND (k.no_registrasi LIKE '%$keyword%' OR p.nama_lengkap LIKE '%$keyword%' OR k.no_rm LIKE '%$keyword%') ";
                }

                // Menampilkan kunjungan hari ini yang masih dalam antrean aktif TPP
                $query = mysqli_query($koneksi, "SELECT k.*, p.nama_lengkap, p.jk, p.tgl_lahir 
                                                 FROM kunjungan k 
                                                 JOIN pasien p ON k.no_rm = p.no_rm 
                                                 WHERE DATE(k.tgl_masuk) = '$hari_ini' 
                                                 AND k.status_kunjungan NOT IN ('Selesai Diperiksa', 'Selesai (Pulang)', 'Selesai', 'Batal', 'Menunggu Koding', 'Selesai (Klaim Valid)') 
                                                 $kondisi_cari
                                                 ORDER BY k.tgl_masuk DESC");
                
                if(!$query || mysqli_num_rows($query) == 0) {
                    echo "<tr><td colspan='6' style='text-align:center; padding: 25px; color: #64748b;'>Tidak ada kunjungan aktif yang perlu diproses saat ini.</td></tr>";
                } else {
                    while($d = mysqli_fetch_assoc($query)) {
                        // Badge warna khusus per jenis layanan
                        $badge_jenis = 'badge-rj';
                        if ($d['jenis_layanan'] == 'Rawat Inap') {
                            $badge_jenis = 'badge-ri';
                        } elseif ($d['jenis_layanan'] == 'IGD') {
                            $badge_jenis = 'badge-igd';
                        }
                ?>
                <tr>
                    <td style="font-weight: bold; color: #ef4444;"><?php echo $d['no_registrasi']; ?></td>
                    <td>
                        <strong><?php echo strtoupper($d['nama_lengkap']); ?></strong><br>
                        <small style="color: #64748b;">RM: <?php echo $d['no_rm']; ?> (<?php echo $d['jk']; ?>)</small>
                    </td>
                    <td>
                        <span class="badge-layanan <?php echo $badge_jenis; ?>"><?php echo $d['jenis_layanan']; ?></span><br>
                        <small style="color: #475569; font-weight: 500;"><?php echo $d['tujuan_layanan']; ?></small>
                    </td>
                    <td>
                        <b><?php echo $d['dpjp']; ?></b><br>
                        <small style="color: #64748b;"><?php echo $d['cara_bayar']; ?></small>
                    </td>
                    <td><span class="badge-status"><?php echo $d['status_kunjungan']; ?></span></td>
                    <td style="text-align: center; white-space: nowrap;">
                        <a href="skrining_awal.php?reg=<?php echo $d['no_registrasi']; ?>" target="_blank" class="btn-aksi btn-skrining">🩺 Skrining Awal</a>
                        <a href="?batal=<?php echo $d['no_registrasi']; ?>" class="btn-aksi btn-batal" onclick="return confirm('Yakin ingin membatalkan pendaftaran pasien <?php echo $d['nama_lengkap']; ?> (<?php echo $d['no_registrasi']; ?>)?');">❌ Batal Rawat</a>
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