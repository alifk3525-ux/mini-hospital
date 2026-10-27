<?php
include 'koneksi.php';

// =========================================================================
// SINKRONISASI OTOMATIS: Tarik pasien meninggal dari SIMRS ke Modul Skripsi
// =========================================================================
$q_sync = mysqli_query($koneksi, "
    SELECT k.no_registrasi, k.no_rm 
    FROM kunjungan k 
    LEFT JOIN rekam_medis rm ON k.no_registrasi = rm.no_registrasi 
    WHERE (k.status_kunjungan = 'Selesai (Meninggal)' 
           OR k.tujuan_layanan LIKE '%Jenazah%' 
           OR rm.status_keluar LIKE '%Meninggal%')
      AND k.no_registrasi NOT IN (SELECT no_registrasi FROM kasus_kematian_kerja)
");

while ($d_sync = mysqli_fetch_assoc($q_sync)) {
    $reg_sync = mysqli_real_escape_string($koneksi, $d_sync['no_registrasi']);
    $rm_sync  = mysqli_real_escape_string($koneksi, $d_sync['no_rm']);
    mysqli_query($koneksi, "INSERT INTO kasus_kematian_kerja (no_registrasi, no_rm) VALUES ('$reg_sync', '$rm_sync')");
}

// Filter Pencarian & Status
$filter_identifikasi = isset($_GET['identifikasi']) ? mysqli_real_escape_string($koneksi, $_GET['identifikasi']) : '';
$filter_proses       = isset($_GET['proses']) ? mysqli_real_escape_string($koneksi, $_GET['proses']) : '';

$where_clause = "WHERE 1=1";
if (!empty($filter_identifikasi)) {
    $where_clause .= " AND mkk.hasil_identifikasi_pekerjaan = '$filter_identifikasi'";
}
if (!empty($filter_proses)) {
    $where_clause .= " AND mkk.status_proses = '$filter_proses'";
}

// Query Utama Gabungan Data SIMRS + Modul Penelitian
$query = mysqli_query($koneksi, "
    SELECT 
        mkk.*,
        k.jenis_layanan,
        k.tujuan_layanan,
        k.tgl_masuk,
        k.tgl_keluar AS tgl_kematian,
        p.nama_lengkap,
        p.jk,
        p.tgl_lahir,
        p.pekerjaan AS pekerjaan_simrs,
        TIMESTAMPDIFF(YEAR, p.tgl_lahir, k.tgl_masuk) AS umur,
        rm.diagnosa_utama,
        rm.icd_utama
    FROM kasus_kematian_kerja mkk
    JOIN kunjungan k ON mkk.no_registrasi = k.no_registrasi
    JOIN pasien p ON mkk.no_rm = p.no_rm
    LEFT JOIN rekam_medis rm ON mkk.no_registrasi = rm.no_registrasi
    $where_clause
    ORDER BY k.tgl_keluar DESC
");

// Stat Summary Widget
$q_stat = mysqli_query($koneksi, "
    SELECT 
        COUNT(*) AS total_kasus,
        SUM(IF(hasil_identifikasi_pekerjaan = 'Belum Dapat Ditentukan', 1, 0)) AS perlu_identifikasi,
        SUM(IF(hasil_identifikasi_pekerjaan = 'Terindikasi Terkait Pekerjaan', 1, 0)) AS terindikasi_kerja,
        SUM(IF(status_verifikasi = 'Diverifikasi', 1, 0)) AS diverifikasi,
        SUM(IF(status_coding = 'Selesai Coding', 1, 0)) AS selesai_coding
    FROM kasus_kematian_kerja
");
$stat = mysqli_fetch_assoc($q_stat);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Monitoring Kematian Akibat Kerja</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f4f8; padding: 20px; color: #1e293b; margin: 0; }
        .header { background: #1e293b; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header h2 { margin: 0; color: #f8fafc; font-size: 20px; }

        /* Widget Statistik */
        .stat-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; border-left: 5px solid #64748b; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .stat-title { font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; }
        .stat-value { font-size: 22px; font-weight: bold; color: #0f172a; margin-top: 4px; }

        /* Filter Box */
        .filter-box { background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; gap: 15px; align-items: center; }
        .filter-box select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 13px; }

        /* Tabel Data */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.08); font-size: 13px; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        th { background: #0f172a; color: white; font-weight: 600; }
        tr:hover { background: #f8fafc; }

        /* Status Badge */
        .badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; display: inline-block; }
        .badge-kuning { background: #fef3c7; color: #b45309; }
        .badge-merah { background: #fee2e2; color: #b91c1c; }
        .badge-hijau { background: #dcfce7; color: #15803d; }
        .badge-biru { background: #e0f2fe; color: #0369a1; }
        .badge-abu { background: #f1f5f9; color: #475569; }

        .btn-penelusuran { background: #2563eb; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 11px; display: inline-block; }
        .btn-penelusuran:hover { background: #1d4ed8; }
    </style>
</head>
<body>

    <div class="header">
        <h2>📑 Dashboard Identifikasi & Monitoring Kematian Akibat Kerja</h2>
        <p style="margin: 5px 0 0 0; font-size: 12px; color: #94a3b8;">
            Sistem Pendukung Keputusan Penelusuran Kasus Kematian Terkait Pekerjaan & Pengkodean External Cause
        </p>
    </div>

    <!-- WIDGET RINGKASAN DATA -->
    <div class="stat-grid">
        <div class="stat-card" style="border-left-color: #3b82f6;">
            <div class="stat-title">Total Kasus Kematian</div>
            <div class="stat-value"><?php echo (int)$stat['total_kasus']; ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #f59e0b;">
            <div class="stat-title">Perlu Identifikasi</div>
            <div class="stat-value"><?php echo (int)$stat['perlu_identifikasi']; ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #ef4444;">
            <div class="stat-title">Terindikasi Kerja</div>
            <div class="stat-value"><?php echo (int)$stat['terindikasi_kerja']; ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #10b981;">
            <div class="stat-title">Diverifikasi</div>
            <div class="stat-value"><?php echo (int)$stat['diverifikasi']; ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #8b5cf6;">
            <div class="stat-title">Selesai Coding</div>
            <div class="stat-value"><?php echo (int)$stat['selesai_coding']; ?></div>
        </div>
    </div>

    <!-- FILTER PENCARIAN -->
    <form method="GET" class="filter-box">
        <strong>🔍 Filter Kasus:</strong>
        
        <select name="identifikasi" onchange="this.form.submit()">
            <option value="">-- Semua Indikasi Pekerjaan --</option>
            <option value="Belum Dapat Ditentukan" <?php echo ($filter_identifikasi == 'Belum Dapat Ditentukan') ? 'selected' : ''; ?>>Belum Dapat Ditentukan</option>
            <option value="Terindikasi Terkait Pekerjaan" <?php echo ($filter_identifikasi == 'Terindikasi Terkait Pekerjaan') ? 'selected' : ''; ?>>Terindikasi Terkait Pekerjaan</option>
            <option value="Tidak Terindikasi Terkait Pekerjaan" <?php echo ($filter_identifikasi == 'Tidak Terindikasi Terkait Pekerjaan') ? 'selected' : ''; ?>>Tidak Terindikasi Terkait Pekerjaan</option>
        </select>

        <select name="proses" onchange="this.form.submit()">
            <option value="">-- Semua Status Proses --</option>
            <option value="Perlu Identifikasi" <?php echo ($filter_proses == 'Perlu Identifikasi') ? 'selected' : ''; ?>>Perlu Identifikasi</option>
            <option value="Sedang Diidentifikasi" <?php echo ($filter_proses == 'Sedang Diidentifikasi') ? 'selected' : ''; ?>>Sedang Diidentifikasi</option>
            <option value="Menunggu Verifikasi" <?php echo ($filter_proses == 'Menunggu Verifikasi') ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
            <option value="Selesai" <?php echo ($filter_proses == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
        </select>

        <?php if (!empty($filter_identifikasi) || !empty($filter_proses)): ?>
            <a href="daftar_kasus_kematian.php" style="color: #dc2626; text-decoration: none; font-size: 12px; font-weight: bold;">❌ Reset Filter</a>
        <?php endif; ?>
    </form>

    <!-- TABEL DAFTAR KASUS KEMATIAN -->
    <table>
        <thead>
            <tr>
                <th>No. Registrasi & Unit</th>
                <th>Pasien & Pekerjaan (SIMRS)</th>
                <th>Waktu Kematian</th>
                <th>Diagnosa Medis (SIMRS)</th>
                <th>Hasil Identifikasi Pekerjaan</th>
                <th>Status Proses</th>
                <th>Status Verifikasi</th>
                <th style="text-align: center;">Aksi Penelusuran</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($query) == 0): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px; color: #64748b;">
                        Belum ada kasus kematian yang tercatat di SIMRS atau sesuai filter.
                    </td>
                </tr>
            <?php else: ?>
                <?php while ($d = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td>
                            <strong><?php echo $d['no_registrasi']; ?></strong><br>
                            <span class="badge badge-biru"><?php echo $d['jenis_layanan']; ?> (<?php echo $d['tujuan_layanan']; ?>)</span>
                        </td>
                        <td>
                            <strong><?php echo $d['nama_lengkap']; ?></strong> (<?php echo $d['jk']; ?>, <?php echo $d['umur']; ?> thn)<br>
                            <small style="color: #64748b;">RM: <?php echo $d['no_rm']; ?> | Pekerjaan: <b><?php echo !empty($d['pekerjaan_simrs']) ? $d['pekerjaan_simrs'] : '-'; ?></b></small>
                        </td>
                        <td>
                            <?php echo date('d-m-Y H:i', strtotime($d['tgl_kematian'])); ?> WIB
                        </td>
                        <td>
                            <b><?php echo !empty($d['icd_utama']) ? $d['icd_utama'] : '-'; ?></b><br>
                            <small style="color: #475569;"><?php echo !empty($d['diagnosa_utama']) ? $d['diagnosa_utama'] : 'Belum diisi dokter'; ?></small>
                        </td>
                        <td>
                            <?php 
                            if ($d['hasil_identifikasi_pekerjaan'] == 'Terindikasi Terkait Pekerjaan') {
                                echo '<span class="badge badge-merah">⚠️ Terindikasi Kerja</span>';
                            } elseif ($d['hasil_identifikasi_pekerjaan'] == 'Tidak Terindikasi Terkait Pekerjaan') {
                                echo '<span class="badge badge-hijau">Non-Pekerjaan</span>';
                            } else {
                                echo '<span class="badge badge-kuning">Belum Ditentukan</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <span class="badge badge-abu"><?php echo $d['status_proses']; ?></span>
                        </td>
                        <td>
                            <?php 
                            if ($d['status_verifikasi'] == 'Diverifikasi') {
                                echo '<span class="badge badge-hijau">✓ Diverifikasi</span>';
                            } elseif ($d['status_verifikasi'] == 'Ditolak / Perlu Revisi') {
                                echo '<span class="badge badge-merah">Revisi</span>';
                            } else {
                                echo '<span class="badge badge-kuning">Belum Diverifikasi</span>';
                            }
                            ?>
                        </td>
                        <td style="text-align: center;">
                            <a href="identifikasi_kematian.php?id=<?php echo $d['id_kasus']; ?>" class="btn-penelusuran">
                                🔍 Penelusuran Kasus
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>